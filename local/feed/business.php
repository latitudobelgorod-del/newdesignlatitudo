<?php
/**
 * Фид «Товары и услуги» для Яндекс Бизнеса (YAML/YML), один город — один файл.
 *
 * Источник — полный товарный фид города, который модуль «Маркет для продавцов»
 * собирает раз в сутки: /bitrix/catalog_export/<код>_latitudo_full.xml. Он уже
 * содержит актуальные цены, остатки, ссылки и фото, поэтому второй раз ходить
 * в базу нет смысла: фид для Бизнеса — это переупаковка того же каталога под
 * требования карточки компании в Яндекс Картах.
 *
 * Чем отличается от полного фида (требования Яндекс Бизнеса,
 * https://yandex.ru/support/business-priority/ru/manage/price-list.html):
 *   - <vendor> обязателен, поэтому без бренда позиция не уходит: подставляем
 *     «Латитудо»;
 *   - валюта только RUB/KZT/BYN, в полном фиде — RUR;
 *   - одна картинка на позицию (в карточке компании показывается одна);
 *   - <shortDescription> до 250 символов: в Картах и Поиске видно первые
 *     250 символов описания, лучше отдать их как отдельное поле;
 *   - описание — простой текст без HTML, ссылок и телефонов: правила Карт
 *     запрещают ссылки и номера в текстах и на изображениях;
 *   - нет <param>, <weight>, <dimensions>, <vendorCode> — Бизнес их не читает;
 *   - позиции без наличия помечаются available="unknown" (в профиле не
 *     показываются, но в поиске по товарам участвуют) — так же советует
 *     документация для товаров «под заказ»;
 *   - лимиты Бизнеса: не больше 10 000 позиций и 15 МБ на файл.
 *
 * Запуск:
 *   php local/feed/business.php --code=rostov
 *   php local/feed/business.php --code=rostov --source=/path/to/feed.xml
 *   php local/feed/business.php --code=rostov --out=/upload/feed/business-feed-rostov.yml
 *   php local/feed/business.php --code=rostov --dry-run
 *
 * Коды городов — как у полных фидов: msk, vrn, rostov, krd, spb, …
 * Адрес фида: https://<город>.latitudo.ru/upload/feed/business-feed-<код>.yml
 */

$opts = ['code' => 'rostov', 'source' => '', 'out' => '', 'dry-run' => false, 'db' => false];
foreach (array_slice($argv ?? [], 1) as $arg) {
    if (preg_match('/^--code=([a-z0-9-]+)$/i', $arg, $m)) {
        $opts['code'] = strtolower($m[1]);
    } elseif (preg_match('/^--source=(.+)$/', $arg, $m)) {
        $opts['source'] = $m[1];
    } elseif (preg_match('/^--out=(.+)$/', $arg, $m)) {
        $opts['out'] = $m[1];
    } elseif ($arg === '--dry-run') {
        $opts['dry-run'] = true;
    } elseif ($arg === '--db') {
        // Читать из базы отметку «Выгрузка в Яндекс Бизнес» и картинки склада.
        $opts['db'] = true;
    } else {
        fwrite(STDERR, "Неизвестный ключ: {$arg}\n");
        exit(2);
    }
}

$root = dirname(__DIR__, 2);

$src = $opts['source'] !== ''
    ? $opts['source']
    : $root . '/bitrix/catalog_export/' . $opts['code'] . '_latitudo_full.xml';
$out = $opts['out'] !== ''
    ? $opts['out']
    : '/upload/feed/business-feed-' . $opts['code'] . '.yml';
// Путь из --out: «/upload/…» — от корня сайта, «C:\…» — как есть.
$outPath = preg_match('~^[A-Za-z]:~', $out) ? $out : $root . '/' . ltrim($out, '/');

const LIMIT_OFFERS        = 10000;
const NAME_MAX            = 250;
const SHORT_DESC_MAX      = 250;
const DESCRIPTION_MAX     = 3000;
const URL_MAX             = 512;
const DEFAULT_VENDOR      = 'Латитудо';

ini_set('memory_limit', '768M');
@set_time_limit(0);

if (!is_file($src)) {
    fwrite(STDERR, "Не найден исходный фид: {$src}\n");
    exit(1);
}

/** Текст без разметки, ссылок и телефонов: правила Карт запрещают их в описании. */
function biz_plain(string $html): string
{
    $s = str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>', '</div>'], ' ', $html);
    $s = html_entity_decode(strip_tags($s), ENT_QUOTES, 'UTF-8');
    // Ссылки и почта.
    $s = preg_replace('~\b(?:https?://|www\.)\S+~iu', ' ', $s);
    $s = preg_replace('~\b[\w.+-]+@[\w-]+\.[\w.-]+~u', ' ', $s);
    // Телефоны: +7/8 с разделителями, минимум 10 цифр.
    $s = preg_replace('~(?:\+7|8)[\s\-(]*\d{3,4}[\s\-)]*\d{2,3}[\s\-]?\d{2}[\s\-]?\d{2}~u', ' ', $s);
    // Управляющие символы и повторные пробелы.
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);
    return trim(preg_replace('/\s+/u', ' ', $s));
}

function biz_cut(string $s, int $max): string
{
    return mb_strlen($s) <= $max ? $s : rtrim(mb_substr($s, 0, $max - 1)) . '…';
}

function biz_xml(string $s): string
{
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function biz_num(float $v): string
{
    return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
}

$xml = simplexml_load_file($src, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_PARSEHUGE);
if ($xml === false || !isset($xml->shop)) {
    fwrite(STDERR, "Не удалось разобрать XML: {$src}\n");
    exit(1);
}

// --- Категории ------------------------------------------------------------
$cats = [];
foreach ($xml->shop->categories->category as $c) {
    $id = (int)trim((string)$c['id']);
    $name = biz_cut(trim(preg_replace('/\s+/u', ' ', (string)$c)), NAME_MAX);
    if ($id > 0 && $name !== '') {
        $cats[$id] = $name;
    }
}

// --- Данные из базы (--db): отметка для фида и картинки склада ------------
//
// Отметка «Выгрузка в Яндекс Бизнес» (свойство LIST_BUSINESS в инфоблоке
// товаров) задаёт состав фида: в карточку компании идут только отмеченные
// товары. Картинка склада берётся из свойства предложения «Картинка фида
// <город>» и ставится первой — вместо фото из общего фида.
$feedHost = rtrim((string)($xml->shop->url ?? ''), '/');
if ($feedHost === '') {
    $feedHost = 'https://' . ($opts['code'] === 'msk' ? 'latitudo.ru' : $opts['code'] . '.latitudo.ru');
}

$markedOffers = []; // offerId => true
$cityPics     = []; // offerId => файл

if ($opts['db']) {
    $cityPropMap = [
        'krasnodar' => 456, 'krd' => 456,
        'belgorod'  => 457, 'bel'  => 457,
        'vrn'       => 458,
        'msk'       => 459,
        'rostov'    => 460, 'rnd'  => 460,
    ];
    $cityPropId = $cityPropMap[$opts['code']] ?? 0;

    $_SERVER['DOCUMENT_ROOT']  = $root;
    $_SERVER['HTTP_HOST']      = parse_url($feedHost, PHP_URL_HOST) ?: 'latitudo.ru';
    $_SERVER['SERVER_NAME']    = $_SERVER['HTTP_HOST'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI']    = '/';
    define('NO_KEEP_STATISTIC', true);
    define('NOT_CHECK_PERMISSIONS', true);
    define('BX_NO_ACCELERATOR_RESET', true);
    define('DisableEventsCheck', true);
    define('NO_AGENT_CHECK', true);
    require $root . '/bitrix/modules/main/include/prolog_before.php';
    if (!CModule::IncludeModule('iblock')) {
        fwrite(STDERR, "Не подключился модуль iblock\n");
        exit(1);
    }
    $conn = \Bitrix\Main\Application::getConnection();

    // Товары с отметкой.
    $markedProducts = [];
    $rows = $conn->query(
        "SELECT p.IBLOCK_ELEMENT_ID
           FROM b_iblock_element_property p
           JOIN b_iblock_property pr ON pr.ID = p.IBLOCK_PROPERTY_ID
           JOIN b_iblock_property_enum e ON e.ID = p.VALUE
          WHERE pr.IBLOCK_ID = 19 AND pr.CODE = 'LIST_BUSINESS' AND e.VALUE = 'Да'"
    );
    while ($row = $rows->fetch()) {
        $markedProducts[(int)$row['IBLOCK_ELEMENT_ID']] = true;
    }
    if (!$markedProducts) {
        fwrite(STDERR, "Нет товаров с отметкой «Выгрузка в Яндекс Бизнес» — фид не собран\n");
        exit(1);
    }

    // Их предложения.
    $rows = $conn->query(
        "SELECT IBLOCK_ELEMENT_ID FROM b_iblock_element_property
          WHERE IBLOCK_PROPERTY_ID = 167 AND VALUE IN (" . implode(',', array_keys($markedProducts)) . ")"
    );
    while ($row = $rows->fetch()) {
        $markedOffers[(int)$row['IBLOCK_ELEMENT_ID']] = true;
    }

    // Картинки склада этого города.
    if ($cityPropId) {
        $rows = $conn->query(
            "SELECT IBLOCK_ELEMENT_ID, VALUE FROM b_iblock_element_property
              WHERE IBLOCK_PROPERTY_ID = {$cityPropId} AND VALUE > 0"
        );
        while ($row = $rows->fetch()) {
            $cityPics[(int)$row['IBLOCK_ELEMENT_ID']] = (int)$row['VALUE'];
        }
    }

    printf("отметка: товаров %d, предложений %d; картинок склада: %d\n",
        count($markedProducts), count($markedOffers), count($cityPics));
}

// --- Позиции --------------------------------------------------------------
$stats = [
    'total' => 0, 'included' => 0, 'no_price' => 0, 'no_category' => 0,
    'no_id' => 0, 'bad_id' => 0, 'no_vendor' => 0, 'no_picture' => 0,
    'no_description' => 0, 'unknown' => 0, 'truncated' => 0,
    'unmarked' => 0, 'city_pic' => 0,
];
$usedCats = [];
$offers = [];

foreach ($xml->shop->offers->offer as $o) {
    $stats['total']++;
    if ($stats['included'] >= LIMIT_OFFERS) {
        $stats['truncated']++;
        continue;
    }

    $id = trim((string)$o['id']);
    if ($id === '') {
        $stats['no_id']++;
        continue;
    }
    // Состав фида: только товары с отметкой «Выгрузка в Яндекс Бизнес».
    if ($opts['db'] && !isset($markedOffers[(int)$id])) {
        $stats['unmarked']++;
        continue;
    }
    // Яндекс Бизнес: цифры, латиница, кириллица (кроме «ё»), . , \ / ( ) [ ] - =
    if (preg_match('/[^0-9A-Za-zА-Яа-яЁё.,\\\\\/()\[\]-]/u', $id)) {
        $stats['bad_id']++;
        continue;
    }

    $price = (float)str_replace(',', '.', trim((string)$o->price));
    if ($price <= 0) {
        $stats['no_price']++;
        continue;
    }

    $catId = (int)trim((string)$o->categoryId);
    if ($catId <= 0 || !isset($cats[$catId])) {
        $stats['no_category']++;
        continue;
    }

    $name = biz_cut(trim(preg_replace('/\s+/u', ' ', (string)$o->name)), NAME_MAX);
    if ($name === '') {
        continue;
    }

    $vendor = biz_cut(trim(preg_replace('/\s+/u', ' ', (string)$o->vendor)), NAME_MAX);
    if ($vendor === '') {
        $vendor = DEFAULT_VENDOR;
        $stats['no_vendor']++;
    }

    $picture = trim((string)($o->picture[0] ?? ''));
    // Картинка склада города важнее обычного фото.
    if (isset($cityPics[(int)$id])) {
        $path = CFile::GetPath($cityPics[(int)$id]);
        if ($path !== '' && is_file($_SERVER['DOCUMENT_ROOT'] . $path)) {
            $picture = $feedHost . $path;
            $stats['city_pic']++;
        }
    }
    if ($picture === '') {
        $stats['no_picture']++;
    }

    $description = biz_plain((string)$o->description);
    if ($description === '') {
        $stats['no_description']++;
    }
    $description = biz_cut($description, DESCRIPTION_MAX);
    // Короткое описание нужно, только если текст длиннее 250 символов: иначе
    // Карты и так покажут его целиком, а в фиде это просто дубль.
    $short = mb_strlen($description) > SHORT_DESC_MAX ? biz_cut($description, SHORT_DESC_MAX) : '';

    $url = trim((string)$o->url);
    if (mb_strlen($url) > URL_MAX) {
        $url = mb_substr($url, 0, URL_MAX);
    }

    // RUR → RUB: Бизнес принимает только RUB, KZT и BYN.
    $currency = strtoupper(trim((string)$o->currencyId));
    if ($currency === 'RUR' || $currency === '') {
        $currency = 'RUB';
    }

    $available = strtolower(trim((string)$o['available']));
    $isAvailable = ($available === '' || $available === 'true');
    if (!$isAvailable) {
        $stats['unknown']++;
    }

    $usedCats[$catId] = true;

    $x  = '      <offer id="' . biz_xml($id) . '"' . ($isAvailable ? '' : ' available="unknown"') . ">\n";
    $x .= '      <name>' . biz_xml($name) . "</name>\n";
    $x .= '      <vendor>' . biz_xml($vendor) . "</vendor>\n";
    $x .= '      <price>' . biz_num($price) . "</price>\n";
    $x .= '      <currencyId>' . $currency . "</currencyId>\n";
    $x .= '      <categoryId>' . $catId . "</categoryId>\n";
    if ($picture !== '') {
        $x .= '      <picture>' . biz_xml($picture) . "</picture>\n";
    }
    if ($description !== '') {
        $x .= '      <description>' . biz_xml($description) . "</description>\n";
    }
    if ($short !== '') {
        $x .= '      <shortDescription>' . biz_xml($short) . "</shortDescription>\n";
    }
    if ($url !== '') {
        $x .= '      <url>' . biz_xml($url) . "</url>\n";
    }
    $x .= "    </offer>\n";

    $offers[] = $x;
    $stats['included']++;
}

if (!$offers) {
    fwrite(STDERR, "Ни одной позиции не собралось — файл не тронут.\n");
    exit(1);
}

// --- Сборка файла ---------------------------------------------------------
$xmlOut  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xmlOut .= '<yml_catalog date="' . date('Y-m-d\TH:i:sP') . '">' . "\n";
$xmlOut .= "  <shop>\n";
$xmlOut .= "    <categories>\n";
foreach (array_keys($usedCats) as $cid) {
    $xmlOut .= '      <category id="' . $cid . '">' . biz_xml($cats[$cid]) . "</category>\n";
}
$xmlOut .= "    </categories>\n";
$xmlOut .= "    <offers>\n";
$xmlOut .= implode('', $offers);
$xmlOut .= "    </offers>\n";
$xmlOut .= "  </shop>\n";
$xmlOut .= "</yml_catalog>\n";

if ($opts['dry-run']) {
    printf("[dry-run] %s → %s\n", $src, $outPath);
} else {
    $dir = dirname($outPath);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        fwrite(STDERR, "Не создать каталог: {$dir}\n");
        exit(1);
    }
    $tmp = $outPath . '.tmp';
    if (@file_put_contents($tmp, $xmlOut) === false) {
        fwrite(STDERR, "Не записать: {$tmp}\n");
        exit(1);
    }
    if (!@rename($tmp, $outPath)) {
        @unlink($tmp);
        fwrite(STDERR, "Не переименовать: {$tmp}\n");
        exit(1);
    }
    @chmod($outPath, 0644);
}

printf(
    "%s: позиций %d из %d | категорий %d | с картинкой склада %d | без наличия %d | без фото %d | без описания %d\n"
    . "пропущено: не отмечено для Бизнеса %d, без цены %d, без категории %d, без ID %d, битый ID %d | бренд подставлен %d | сверх лимита %d\n"
    . "файл: %s (%s МБ)\n",
    $opts['dry-run'] ? '[dry-run]' : 'Готово',
    $stats['included'], $stats['total'], count($usedCats), $stats['city_pic'], $stats['unknown'],
    $stats['no_picture'], $stats['no_description'],
    $stats['unmarked'], $stats['no_price'], $stats['no_category'], $stats['no_id'], $stats['bad_id'],
    $stats['no_vendor'], $stats['truncated'],
    $outPath, number_format(strlen($xmlOut) / 1048576, 2, '.', ' ')
);
