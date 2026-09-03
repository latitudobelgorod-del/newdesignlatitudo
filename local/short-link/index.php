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

$filter = ['IBLOCK_ID' => ND_SHORT_LINK_IBLOCK, 'ACTIVE' => 'Y'];
if ($id > 0) {
    $filter['ID'] = $id;
} elseif ($xmlId !== '') {
    $filter['=XML_ID'] = $xmlId;
} elseif ($code !== '') {
    $filter['=CODE'] = $code;
} else {
    nd_json(400, ['detail' => 'Pass one of: id, xml_id, code']);
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

nd_json(200, [
    'id'     => (int) $row['ID'],
    'name'   => $row['~NAME'],
    'xml_id' => $row['~XML_ID'],
    'url'    => ND_SHORT_LINK_HOST . $uri,
    'short'  => $short,
]);
