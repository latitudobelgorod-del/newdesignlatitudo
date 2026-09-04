<?php
/**
 * API коротких ссылок и данных товаров каталога.
 *
 * Одиночный режим — один товар:
 *     GET /local/short-link/?id=27873
 *     GET /local/short-link/?xml_id=00000012345
 *     GET /local/short-link/?code=terrasnaya-doska-latitudo-neo-135kh21-venge
 *     GET /local/short-link/?url=https://latitudo.ru/catalog/…/
 *
 * Режим списка — все товары раздела (с подразделами):
 *     GET /local/short-link/?section=zabor-iz-dpk&limit=50&offset=0
 *     GET /local/short-link/?section=512&limit=50&page=2
 *
 *     Заголовок: X-Api-Token: <токен из config.php>
 *
 * Ответ одиночного режима:
 *     {"id":27873,"name":"…","active":true,"active_from":null,"active_to":null,
 *      "url":"https://latitudo.ru/catalog/…/","short":"https://latitudo.ru/~a1b2c3",
 *      "sizes":{…},"brand":{…},"chars":[…],"offers":[…]}
 *
 * Ответ списка — та же структура товара, без изменений:
 *     {"section":{"id":512,"code":"zabor-iz-dpk","name":"Заборы","url":"…"},
 *      "total":61,"limit":50,"offset":0,"items":[{…},{…}]}
 *
 * Выключенный товар в ОДИНОЧНОМ режиме отдаётся так же, с "active":false, а не
 * 404: приложению нужно отличать снятый с публикации товар от несуществующего.
 * В СПИСКЕ неактивных нет вовсе — там нужен ассортимент, как на сайте.
 *
 * Как это устроено. Хранилище коротких ссылок — штатная таблица Битрикса
 * b_short_uri, её ведёт CBXShortUri: по одному и тому же адресу метод
 * GetShortUri() всегда возвращает один и тот же код и заводит новый только
 * для нового адреса. Свойство ND_SHORT_URI у элемента — не хранилище, а
 * витрина: туда кладём готовую ссылку, чтобы её было видно и можно было
 * скопировать прямо из карточки товара в админке.
 *
 * Самолечение при переезде товара. b_short_uri привязывает код к СТРОКЕ
 * адреса. Если у товара сменится символьный код или раздел, старая короткая
 * ссылка начала бы вести на 404. Поэтому на каждом запросе сверяем адрес в
 * таблице с текущим и при расхождении переписываем строку — код остаётся
 * прежним, а ведёт уже куда надо. Отдельный обработчик события для этого не
 * нужен: приложение и так дёргает ручку.
 *
 * Регионы сознательно не учитываем: приложению нужен один постоянный адрес,
 * поэтому ссылка всегда собирается от ND_SHORT_LINK_HOST.
 *
 * Сборка самого товара — в product.php: у обоих режимов она общая.
 */

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_CHECK', true);

require __DIR__ . '/config.php';

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

/**
 * Ответ и выход. Битриксовый эпилог не подключаем сознательно: он дописал бы
 * в ответ разметку, а модуль капчи — свои <script> и <style> (та же грабля,
 * что чинится в local/init.php для ajax-ответов).
 */
function nd_json(int $status, array $data): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// --- доступ ---

if (!empty($ND_SHORT_LINK_IPS) && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $ND_SHORT_LINK_IPS, true)) {
    nd_json(403, ['detail' => 'Forbidden']);
}

$token = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if (!defined('ND_SHORT_LINK_TOKEN') || ND_SHORT_LINK_TOKEN === '' || ND_SHORT_LINK_TOKEN === 'ПОДСТАВЬ_СВОЙ_ТОКЕН') {
    nd_json(500, ['detail' => 'Token is not configured on the server']);
}
if (!hash_equals(ND_SHORT_LINK_TOKEN, $token)) {
    nd_json(401, ['detail' => 'Invalid or missing X-Api-Token']);
}

if (!CModule::IncludeModule('iblock')) {
    nd_json(500, ['detail' => 'Module iblock is not installed']);
}

require __DIR__ . '/product.php';

// --- параметры ---

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$id = (int) $request->get('id');
$xmlId = trim((string) $request->get('xml_id'));
$code = trim((string) $request->get('code'));
$url = trim((string) $request->get('url'));
$section = trim((string) $request->get('section'));

/**
 * Приложению удобнее оперировать адресом страницы товара, а не его ID, поэтому
 * принимаем и его. Адрес приводим к символьному коду: у детальных страниц
 * каталога последний сегмент пути — это CODE элемента, дальше работает тот же
 * поиск, что и по ?code=.
 *
 * Берём именно path: и полный адрес с доменом, и один путь дают одинаковый
 * результат, а хвосты вида ?utm_source=… и #anchor отбрасываются сами.
 * Региональные поддомены тем самым тоже не мешают — товар один и тот же.
 */
if ($code === '' && $url !== '') {
    $path = parse_url($url, PHP_URL_PATH);
    if (is_string($path)) {
        $segments = array_values(array_filter(explode('/', trim($path, '/')), 'strlen'));
        if ($segments) {
            $code = rawurldecode(end($segments));
        }
    }
}

// =====================  режим списка товаров раздела  =====================

if ($section !== '') {
    /* Раздел ищем по символьному коду или по числовому ID — приложение может
       знать любой из них (просьба команды приложения, 4 сентября 2026).
       Числом считаем только строку целиком из цифр: у разделов бывают коды
       вроде «3d», и превращать их в ID нельзя. */
    $sectionFilter = ['IBLOCK_ID' => ND_SHORT_LINK_IBLOCK, 'ACTIVE' => 'Y'];
    if (preg_match('/^\d+$/', $section)) {
        $sectionFilter['ID'] = (int) $section;
    } else {
        $sectionFilter['=CODE'] = $section;
    }

    $sectionRow = CIBlockSection::GetList(
        ['DEPTH_LEVEL' => 'ASC'],
        $sectionFilter,
        false,
        ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'SECTION_PAGE_URL']
    )->GetNext();

    if (!$sectionRow) {
        nd_json(404, ['detail' => 'Section not found']);
    }

    /* Постраничность. В разделе бывает 60+ товаров, и приложение забирает их
       частями. Принимаем и offset, и page (1-based) — что удобнее вызывающему;
       page считаем от limit. Верхнюю границу держим: на каждый товар идёт
       разбор свойств и цен предложений, и сотня за раз — это уже секунды. */
    $limit = (int) $request->get('limit');
    if ($limit <= 0) {
        $limit = 50;
    }
    $limit = min($limit, 100);

    $offset = (int) $request->get('offset');
    $page = (int) $request->get('page');
    if ($offset <= 0 && $page > 1) {
        $offset = ($page - 1) * $limit;
    }
    $offset = max($offset, 0);

    /* Товары берём как на сайте: только активные и попадающие в срок показа.
       INCLUDE_SUBSECTIONS — обязательно: у «Заборов» товары лежат в
       подразделах («Штакетник», «Панели для забора»), и без этого список
       пришёл бы почти пустым. */
    $itemsFilter = [
        'IBLOCK_ID' => ND_SHORT_LINK_IBLOCK,
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
        'SECTION_ID' => (int) $sectionRow['ID'],
        'INCLUDE_SUBSECTIONS' => 'Y',
    ];

    // Отдельным запросом только количество: с ним приложение знает, сколько
    // страниц забирать, и не гадает по длине последней порции.
    $total = (int) CIBlockElement::GetList([], $itemsFilter, []);

    /* Смещение отрабатываем сами, а не параметрами навигации: у
       CIBlockElement::GetList нет nOffset (проверено — постранично он ходит
       только через iNumPage, а это не даёт произвольный offset). Просим
       offset+limit строк и первые offset пропускаем: лишние строки стоят
       дёшево — тяжёлую сборку делаем только для тех, что отдаём. */
    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        $itemsFilter,
        false,
        ['nTopCount' => $offset + $limit],
        nd_short_link_fields()
    );

    $items = [];
    $skipped = 0;
    while ($row = $res->GetNext()) {
        if ($skipped < $offset) {
            $skipped++;
            continue;
        }
        $items[] = nd_short_link_product($row);
        if (count($items) >= $limit) {
            break;
        }
    }

    nd_json(200, [
        'section' => [
            'id'   => (int) $sectionRow['ID'],
            'code' => $sectionRow['~CODE'],
            'name' => $sectionRow['~NAME'],
            'url'  => $sectionRow['~SECTION_PAGE_URL'] ? ND_SHORT_LINK_HOST . $sectionRow['~SECTION_PAGE_URL'] : '',
        ],
        'total'  => $total,
        'limit'  => $limit,
        'offset' => $offset,
        'items'  => $items,
    ]);
}

// =========================  одиночный режим  =========================

/* ACTIVE в фильтр НЕ кладём: приложению нужно отличать «товара нет» от
   «товар снят с публикации», а под фильтром выключенный товар отдавал 404,
   как несуществующий (просьба команды приложения, 4 сентября 2026).
   Состояние отдаём полем active, а сроки — active_from / active_to. */
$filter = ['IBLOCK_ID' => ND_SHORT_LINK_IBLOCK];
if ($id > 0) {
    $filter['ID'] = $id;
} elseif ($xmlId !== '') {
    $filter['=XML_ID'] = $xmlId;
} elseif ($code !== '') {
    $filter['=CODE'] = $code;
} else {
    nd_json(400, ['detail' => 'Pass one of: url, id, xml_id, code, section']);
}

$row = CIBlockElement::GetList([], $filter, false, ['nTopCount' => 1], nd_short_link_fields())->GetNext();

if (!$row) {
    nd_json(404, ['detail' => 'Element not found']);
}

// GetNext() экранирует значения — для адреса нужен оригинал.
if ((string) $row['~DETAIL_PAGE_URL'] === '') {
    nd_json(500, ['detail' => 'Element has no detail page url']);
}

$product = nd_short_link_product($row);

if ($product['short'] === '') {
    nd_json(500, ['detail' => 'Cannot create short uri', 'errors' => CBXShortUri::GetErrors()]);
}

nd_json(200, $product);
