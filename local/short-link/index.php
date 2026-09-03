<?php
/**
 * API коротких ссылок на товары каталога.
 *
 *     GET /local/short-link/?id=27873
 *     GET /local/short-link/?xml_id=00000012345
 *     GET /local/short-link/?code=terrasnaya-doska-latitudo-neo-135kh21-venge
 *
 *     Заголовок: X-Api-Token: <токен из config.php>
 *
 * Ответ:
 *     {"id":27873,"name":"…","url":"https://latitudo.ru/catalog/…/",
 *      "short":"https://latitudo.ru/~a1b2c3"}
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

// --- поиск товара ---

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$id = (int) $request->get('id');
$xmlId = trim((string) $request->get('xml_id'));
$code = trim((string) $request->get('code'));
$url = trim((string) $request->get('url'));

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

$filter = ['IBLOCK_ID' => ND_SHORT_LINK_IBLOCK, 'ACTIVE' => 'Y'];
if ($id > 0) {
    $filter['ID'] = $id;
} elseif ($xmlId !== '') {
    $filter['=XML_ID'] = $xmlId;
} elseif ($code !== '') {
    $filter['=CODE'] = $code;
} else {
    nd_json(400, ['detail' => 'Pass one of: url, id, xml_id, code']);
}

$row = CIBlockElement::GetList(
    [],
    $filter,
    false,
    ['nTopCount' => 1],
    ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'XML_ID', 'DETAIL_PAGE_URL']
)->GetNext();

if (!$row) {
    nd_json(404, ['detail' => 'Element not found']);
}

// GetNext() экранирует значения — для адреса нужен оригинал.
$uri = $row['~DETAIL_PAGE_URL'];
if ($uri === '' || $uri === null) {
    nd_json(500, ['detail' => 'Element has no detail page url']);
}

// --- короткая ссылка ---

/**
 * Достаём код из уже сохранённого значения свойства: в нём лежит полная
 * ссылка, а для сверки с b_short_uri нужен только хвост после последнего «/».
 *
 * Тильда — ЧАСТЬ кода, а не разделитель: GenerateShortUri() собирает его как
 * "~" . randString(5), в колонке SHORT_URI лежит «~JH8uT», а GetShortUri()
 * возвращает уже готовый путь «/~JH8uT». Своей тильды дописывать не надо —
 * иначе выходит «/~~JH8uT» (поймано на первом же прогоне).
 */
/* Сигнатура именно такая: GetProperty($iblock, $element, $by, $order, $filter).
   Третий и четвёртый аргументы — сортировка, фильтр только пятый. Если сунуть
   фильтр четвёртым, он молча игнорируется и метод отдаёт ПЕРВОЕ попавшееся
   свойство элемента — самолечение при этом не срабатывает, а ошибки не видно:
   ссылка каждый раз выглядит правильной, потому что GetShortUri() и сам
   идемпотентен по адресу (поймано на переезде товара). */
$saved = CIBlockElement::GetProperty(
    ND_SHORT_LINK_IBLOCK,
    (int) $row['ID'],
    'sort',
    'asc',
    ['CODE' => ND_SHORT_LINK_PROP]
)->Fetch();

$shortCode = '';
/* Разделитель регулярки — «#», а не «~»: тильда есть внутри шаблона, и с ней
   в роли разделителя выражение просто не компилируется. */
if ($saved && preg_match('#/(~[0-9a-zA-Z]+)$#', (string) $saved['VALUE'], $m)) {
    $shortCode = $m[1];
}

if ($shortCode !== '') {
    // Ссылка уже выдавалась. Проверяем, туда ли она ведёт сейчас.
    $exists = CBXShortUri::GetList([], ['SHORT_URI' => $shortCode])->Fetch();
    if (!$exists) {
        $shortCode = '';                       // строку удалили — заведём заново
    } elseif ($exists['URI'] !== $uri) {
        CBXShortUri::Update($exists['ID'], ['URI' => $uri, 'STATUS' => 301]);
    }
}

if ($shortCode === '') {
    $shortCode = ltrim((string) CBXShortUri::GetShortUri($uri), '/');
    if ($shortCode === '') {
        nd_json(500, ['detail' => 'Cannot create short uri', 'errors' => CBXShortUri::GetErrors()]);
    }
}

$short = ND_SHORT_LINK_HOST . '/' . $shortCode;

// Витрина в админке. Пишем только при изменении: SetPropertyValuesEx поднимает
// события инфоблока, а лишние сохранения сбрасывают кеш каталога.
if (!$saved || (string) $saved['VALUE'] !== $short) {
    CIBlockElement::SetPropertyValuesEx(
        (int) $row['ID'],
        ND_SHORT_LINK_IBLOCK,
        [ND_SHORT_LINK_PROP => $short]
    );
}

// --- торговые предложения и цены по единицам измерения ---

/**
 * Приложению мало ссылки: по тому же адресу оно хочет увидеть предложения
 * товара (длины, цвета) и цену каждого в тех же единицах, что показывает сайт.
 *
 * Как это устроено на сайте:
 * - предложения лежат в отдельном инфоблоке (20), связь — свойство, которое
 *   отдаёт CCatalogSKU::GetInfoByProductIBlock();
 * - базовая единица предложения — поле MEASURE карточки товара каталога
 *   (b_catalog_product), символ берём из справочника CCatalogMeasure;
 * - дополнительные единицы задаёт множественное свойство UNIT_KOEF модуля
 *   maxyss.measureunits: ЗНАЧЕНИЕ — коэффициент, ОПИСАНИЕ — id единицы из того
 *   же справочника. Так его читает и сам модуль (см. component.php: он получает
 *   свойство параметром MEASURE_RESULT и раскладывает VALUE/DESCRIPTION).
 *
 * Пересчёт: цена за единицу = базовая цена × коэффициент. Коэффициент — это
 * «сколько базовых единиц в одной такой»: у доски 3 м коэффициент п.м равен
 * 1/3, а м² — 2,33 (в квадратном метре 2,33 доски). Сверено с витриной:
 * 1575 ₽/шт × 2,38 = 3750 ₽/м², ровно как на странице товара.
 *
 * Цену берём как гость (группа 2) и с учётом скидок — GetOptimalPrice отдаёт
 * ровно то число, что видит посетитель.
 */
$offers = [];
$skuInfo = CModule::IncludeModule('catalog')
    ? CCatalogSKU::GetInfoByProductIBlock(ND_SHORT_LINK_IBLOCK)
    : false;

if ($skuInfo) {
    $offerRows = [];
    $res = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        [
            'IBLOCK_ID' => $skuInfo['IBLOCK_ID'],
            'ACTIVE' => 'Y',
            'PROPERTY_' . $skuInfo['SKU_PROPERTY_ID'] => (int) $row['ID'],
        ],
        false,
        false,
        ['ID', 'IBLOCK_ID', 'NAME', 'XML_ID']
    );
    while ($o = $res->GetNext()) {
        $offerRows[(int) $o['ID']] = $o;
    }

    if ($offerRows) {
        $ids = array_keys($offerRows);

        /* Единицы читаем поэлементно. Пакетный GetPropertyValues тут не годится:
           даже в расширенном режиме он отдаёт по UNIT_KOEF пусто, а нам нужны
           ОБА поля — значение (коэффициент) и описание (id единицы). Проверено;
           предложений у товара единицы, так что цикл дешёвый. */
        $koef = [];
        foreach ($ids as $offerId) {
            $koef[$offerId] = [];
            $propRes = CIBlockElement::GetProperty(
                $skuInfo['IBLOCK_ID'],
                $offerId,
                'sort',
                'asc',
                ['CODE' => 'UNIT_KOEF']
            );
            while ($pv = $propRes->Fetch()) {
                $ratio = (float) str_replace(',', '.', (string) $pv['VALUE']);
                $measureId = (int) $pv['DESCRIPTION'];
                if ($ratio > 0 && $measureId > 0) {
                    $koef[$offerId][] = ['ratio' => $ratio, 'measure_id' => $measureId];
                }
            }
        }

        // Базовая единица и остаток — одним запросом на все предложения.
        $product = [];
        $prodRes = CCatalogProduct::GetList([], ['ID' => $ids], false, false, ['ID', 'MEASURE', 'QUANTITY']);
        while ($pr = $prodRes->Fetch()) {
            $product[(int) $pr['ID']] = $pr;
        }

        // Справочник единиц: тянем разом и держим в памяти.
        $measures = [];
        $mRes = CCatalogMeasure::GetList([], []);
        while ($mm = $mRes->Fetch()) {
            $measures[(int) $mm['ID']] = $mm['SYMBOL_RUS'] ?: $mm['MEASURE_TITLE'];
        }

        /* Длина предложения. Свойство списочное, поэтому берём не VALUE (там
           id значения), а VALUE_ENUM — уже «3000». Приложению этот размер
           нужен, чтобы не выкусывать его из названия товара. */
        $offerLength = [];
        foreach ($ids as $offerId) {
            $lenRes = CIBlockElement::GetProperty($skuInfo['IBLOCK_ID'], $offerId, 'sort', 'asc', ['CODE' => 'DLINA']);
            while ($lv = $lenRes->Fetch()) {
                $len = trim((string) ($lv['VALUE_ENUM'] !== null && $lv['VALUE_ENUM'] !== '' ? $lv['VALUE_ENUM'] : $lv['VALUE']));
                if ($len !== '') {
                    $offerLength[$offerId] = is_numeric($len) ? (float) $len : $len;
                    break;
                }
            }
        }

        foreach ($offerRows as $offerId => $o) {
            $optimal = CCatalogProduct::GetOptimalPrice($offerId, 1, [2], 'N');
            /* Отдаём ОБЕ цены: базовую и со скидкой. Раньше была только вторая,
               и приложение не могло решить, что печатать, когда акция кончится
               (замечание от команды приложения, 3 сентября 2026). Сегодня они
               часто равны — это значит, что скидки на товар просто нет. */
            $base = $optimal ? (float) $optimal['RESULT_PRICE']['DISCOUNT_PRICE'] : 0.0;
            $basePrice = $optimal ? (float) $optimal['RESULT_PRICE']['BASE_PRICE'] : 0.0;
            $currency = $optimal ? (string) $optimal['RESULT_PRICE']['CURRENCY'] : '';

            $baseMeasureId = (int) ($product[$offerId]['MEASURE'] ?? 0);
            $baseUnit = $measures[$baseMeasureId] ?? '';

            $prices = [];
            if ($base > 0) {
                // Первой идёт базовая единица — та, в которой товар кладут в корзину.
                $prices[] = [
                    'unit' => $baseUnit,
                    'measure_id' => $baseMeasureId ?: null,
                    'ratio' => 1,
                    'value' => round($base, 2),
                    'base' => round($basePrice, 2),
                ];
                foreach ($koef[$offerId] as $u) {
                    $prices[] = [
                        'unit' => $measures[$u['measure_id']] ?? '',
                        'measure_id' => $u['measure_id'],
                        'ratio' => $u['ratio'],
                        'value' => round($base * $u['ratio'], 2),
                        'base' => round($basePrice * $u['ratio'], 2),
                    ];
                }
            }

            $offers[] = [
                'id' => $offerId,
                'name' => $o['~NAME'],
                'xml_id' => $o['~XML_ID'],
                'length_mm' => $offerLength[$offerId] ?? null,
                'quantity' => isset($product[$offerId]) ? (float) $product[$offerId]['QUANTITY'] : null,
                'available' => isset($product[$offerId]) && (float) $product[$offerId]['QUANTITY'] > 0,
                'currency' => $currency,
                'has_discount' => $basePrice > 0 && $base > 0 && $basePrice > $base,
                'prices' => $prices,
            ];
        }
    }
}

/**
 * Характеристики товара — те же свойства, что сайт печатает в блоке
 * «Характеристики»: профиль, ширина, толщина, длины, расход, вес, гарантия и
 * прочее. Приложению они нужны, чтобы собрать подпись вида «146×23 мм | 3 и 4 м»
 * и не выкусывать числа из названия («146х23х3010») — замечание команды
 * приложения, 3 сентября 2026.
 *
 * Отдаём ВСЕ заполненные пользовательские свойства, а не список кодов из
 * шаблона: там он свой для каждого раздела (в карточке доски это условие по
 * SECTION_ID 98/510), и повторять его здесь значило бы ломаться на любом другом
 * разделе. Пусть приложение берёт нужные по коду.
 *
 * Служебное отсекаем: файлы, привязки к элементам и разделам, обменные поля 1С
 * и наши собственные (короткая ссылка, коэффициенты единиц — они уже отданы
 * отдельно, в prices).
 *
 * Читаем одним запросом без фильтра: фильтр GetProperty по CODE принимает
 * только одну строку, массив кодов роняет запрос («real_escape_string():
 * Argument #1 must be of type string, array given»).
 */
$skipCodes = [
    ND_SHORT_LINK_PROP => true,   // короткая ссылка — она уже в поле short
    'UNIT_KOEF' => true,          // коэффициенты единиц — уже посчитаны в prices
    'BASE_KOEF' => true,
    'MINIMUM_PRICE' => true,      // цены отдаём предложениями, а не строкой
    'MAXIMUM_PRICE' => true,
    'LIST_PRISE' => true,         // «назначение фида» — внутренняя разметка выгрузок
    'COLOR_MAIN_EL' => true,      // технический код цвета для подбора
    'ND_BRAND_WEIGHT' => true,    // наш вес марки для сортировки выдачи
];

$chars = [];
$sizes = [
    'width_mm' => null,
    'thickness_mm' => null,
    'lengths_mm' => [],
];

$propRes = CIBlockElement::GetProperty(ND_SHORT_LINK_IBLOCK, (int) $row['ID'], 'sort', 'asc', ['ACTIVE' => 'Y']);

while ($sp = $propRes->Fetch()) {
    $code = (string) $sp['CODE'];

    // Служебное и обменное наружу не отдаём.
    if (isset($skipCodes[$code]) || strncmp($code, 'CML2_', 5) === 0) {
        continue;
    }
    /* EDITOR1/EDITOR2/… — содержимое редактора блоков sprint.editor: там лежит
       json со всей вёрсткой описания, характеристикой это не является и весит
       десятки килобайт (Ирина, 3 сентября 2026). */
    if (preg_match('/^EDITOR\d*$/', $code)) {
        continue;
    }
    // Файлы и привязки — не характеристики.
    if (in_array($sp['PROPERTY_TYPE'], ['F', 'E', 'G'], true)) {
        continue;
    }

    // У списочных свойств VALUE — это id значения, подпись лежит в VALUE_ENUM.
    $raw = $sp['VALUE_ENUM'] !== null && $sp['VALUE_ENUM'] !== '' ? $sp['VALUE_ENUM'] : $sp['VALUE'];
    if (is_array($raw)) {
        continue;
    }
    $raw = trim((string) $raw);
    if ($raw === '') {
        continue;
    }
    /* Страховка на случай, если вёрстка редактора приедет под другим кодом:
       характеристика — это короткое значение, а не json на килобайты. */
    if (strlen($raw) > 300 || strncmp($raw, '{"version"', 10) === 0) {
        continue;
    }

    $value = is_numeric($raw) ? (float) $raw : $raw;

    // Множественные свойства приходят несколькими строками — копим массивом.
    if (isset($chars[$code])) {
        $chars[$code]['value'] = array_merge((array) $chars[$code]['value'], [$value]);
    } else {
        $chars[$code] = [
            'code' => $code,
            'name' => (string) $sp['NAME'],
            'value' => $sp['MULTIPLE'] === 'Y' ? [$value] : $value,
        ];
    }

    // Размеры дублируем отдельно — они нужны почти всегда, а искать их по коду
    // в общем списке приложению неудобно.
    if ($code === 'IT_8') {
        $sizes['width_mm'] = $value;
    } elseif ($code === 'IT_6') {
        $sizes['thickness_mm'] = $value;
    } elseif ($code === 'DLINA_DOSKA_DPK') {
        $sizes['lengths_mm'][] = $value;
    }
}

sort($sizes['lengths_mm']);
$chars = array_values($chars);

nd_json(200, [
    'id'     => (int) $row['ID'],
    'name'   => $row['~NAME'],
    'xml_id' => $row['~XML_ID'],
    'url'    => ND_SHORT_LINK_HOST . $uri,
    'short'  => $short,
    'sizes'  => $sizes,
    'chars'  => $chars,
    'offers' => $offers,
]);
