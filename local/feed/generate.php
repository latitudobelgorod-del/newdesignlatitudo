<?php
/**
 * Полный товарный фид latitudo.ru (YML) — по файлу на каждый город.
 *
 * Собирает ВЕСЬ каталог: активные товары ИБ 19 из активных разделов и их
 * активные торговые предложения (ИБ 20). Товар с предложениями даёт по
 * <offer> на каждое предложение, простой товар — один <offer> сам по себе.
 *
 * Файлы: /upload/feed/full-feed-<город>.yml, основной домен — full-feed-msk.yml.
 * Адрес фида у каждого города свой: https://vrn.latitudo.ru/upload/feed/full-feed-vrn.yml
 * (все поддомены смотрят в одну папку сайта).
 *
 * Запуск из cron раз в сутки:
 *   /opt/php/8.2/bin/php /var/www/.../latitudo.ru/local/feed/generate.php --quiet
 *
 * Ключи:
 *   --dry-run   посчитать и показать итог, файлы не трогать
 *   --quiet     без вывода в stdout (для cron)
 *
 * Сделан по образцу фида easydecking.ru (там же, в local/feed/), с тремя
 * отличиями: выгружается весь каталог, а не одна марка; идут и простые
 * товары без предложений (на easydecking их в Яндекс не отдавали — у них
 * там нет цены, здесь цена есть у самого товара); фид получает и основной
 * домен, а не только поддомены.
 *
 * Файлы пишутся во временный .tmp и переименовываются на месте: если прогон
 * упадёт на середине, Яндекс продолжит читать прошлую целую версию.
 */

// =========================================================================
// Bootstrap
// =========================================================================

$SITE_ROOT = dirname(__DIR__, 2);

$cfg = require __DIR__ . '/config.php';

$opts = ['dry-run' => false, 'quiet' => false];
foreach (array_slice($argv ?? [], 1) as $arg) {
    if ($arg === '--dry-run') {
        $opts['dry-run'] = true;
    } elseif ($arg === '--quiet') {
        $opts['quiet'] = true;
    } else {
        fwrite(STDERR, "Неизвестный ключ: {$arg}\n");
        exit(2);
    }
}

// Ядру нужен веб-подобный контекст даже в CLI, иначе часть обработчиков Aspro
// спотыкается об отсутствующие $_SERVER-ключи ещё в init.php.
$_SERVER['DOCUMENT_ROOT']  = $SITE_ROOT;
$_SERVER['HTTP_HOST']      = parse_url($cfg['BASE_URL'], PHP_URL_HOST);
$_SERVER['SERVER_NAME']    = $_SERVER['HTTP_HOST'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '/';

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('DisableEventsCheck', true);
// Агенты не должны запускаться из выгрузки: cron ходит сюда ночью, и случайный
// тяжёлый агент растянет прогон на непредсказуемое время.
define('NO_AGENT_CHECK', true);

require $SITE_ROOT . '/bitrix/modules/main/include/prolog_before.php';

if (!CModule::IncludeModule('iblock') || !CModule::IncludeModule('catalog')) {
    fwrite(STDERR, "Не подключились модули iblock/catalog\n");
    exit(1);
}

@set_time_limit(0);

// Всё, что подставляется в XML по городам, пишем метками и подменяем в самом
// конце: так в текстах описаний случайный «https://latitudo.ru» не задевается.
const FEED_HOST_TOKEN = '@@FEED_HOST@@';
const FEED_NAME_TOKEN = '@@FEED_SHOP_NAME@@';

// =========================================================================
// Хелперы
// =========================================================================

$LOG_DIR = __DIR__ . '/logs';

function feed_log(string $message): void
{
    global $LOG_DIR, $cfg, $opts;

    if (!is_dir($LOG_DIR)) {
        @mkdir($LOG_DIR, 0755, true);
    }
    $file = $LOG_DIR . '/feed.log';
    $max  = ($cfg['LOG_MAX_MB'] ?? 5) * 1024 * 1024;

    if (file_exists($file) && filesize($file) > $max) {
        @rename($file, $LOG_DIR . '/feed.' . date('Ymd_His') . '.log');
    }

    @file_put_contents($file, date('Y-m-d H:i:s') . ' | ' . $message . "\n", FILE_APPEND | LOCK_EX);

    if (empty($opts['quiet'])) {
        echo $message . "\n";
    }
}

/** Абсолютный адрес с меткой хоста вместо домена: /catalog/... → @@FEED_HOST@@/catalog/... */
function feed_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return FEED_HOST_TOKEN . '/' . ltrim($path, '/');
}

/**
 * Адрес картинки по её ID в b_file.
 *
 * В b_file остаются записи о давно удалённых файлах — такая ссылка ушла бы в
 * фид битой, и Яндекс отклонил бы позицию. Проверяем, что файл лежит на диске.
 */
function feed_image($fileId): string
{
    $fileId = (int)$fileId;
    if (!$fileId) {
        return '';
    }
    $path = CFile::GetPath($fileId);
    if (!$path || !is_file($_SERVER['DOCUMENT_ROOT'] . $path)) {
        return '';
    }
    return feed_url($path);
}

/** Значение свойства из GetProperties() строкой; множественные — через $glue. */
function feed_prop(array $props, string $code, string $glue = ', '): string
{
    if (!isset($props[$code])) {
        return '';
    }
    $v = $props[$code]['VALUE'];
    if ($v === null || $v === '' || $v === false) {
        return '';
    }
    if (is_array($v)) {
        if (isset($v['TEXT'])) {
            return feed_plain($v['TEXT']);
        }
        $v = array_filter(array_map('strval', $v), static fn($x) => trim($x) !== '');
        return implode($glue, $v);
    }
    return trim((string)$v);
}

/** Список ID файлов множественного свойства-файла (MORE_PHOTO). */
function feed_prop_files(array $props, string $code): array
{
    $v = $props[$code]['VALUE'] ?? [];
    return array_values(array_filter(array_map('intval', (array)$v)));
}

/**
 * Коэффициенты пересчёта цены из свойства UNIT_KOEF.
 *
 * В VALUE — множитель, в DESCRIPTION — ID единицы измерения. Цена, которую
 * видит покупатель, считается как базовая × множитель основной единицы:
 * доска 2265 ₽/шт × 2.2075 = 5000 ₽/м².
 *
 * @return array<int, float> ID единицы => множитель
 */
function feed_unit_koefs(array $props): array
{
    $values = (array)($props['UNIT_KOEF']['VALUE'] ?? []);
    $units  = (array)($props['UNIT_KOEF']['DESCRIPTION'] ?? []);

    $out = [];
    foreach ($values as $i => $value) {
        $unit = (int)trim((string)($units[$i] ?? ''));
        $koef = (float)str_replace(',', '.', (string)$value);
        if ($unit > 0 && $koef > 0) {
            $out[$unit] = $koef;
        }
    }
    return $out;
}

/** Основная единица, в которой цена показывается покупателю (BASE_KOEF.DESCRIPTION). */
function feed_base_unit(array $props): int
{
    return (int)trim((string)($props['BASE_KOEF']['DESCRIPTION'] ?? ''));
}

/** Текст без HTML, переводов строк и повторных пробелов. */
function feed_plain(?string $html): string
{
    $s = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ' ', (string)$html)), ENT_QUOTES, 'UTF-8');
    $s = preg_replace('/\s+/u', ' ', $s);
    return trim($s);
}

/** Число без хвостовых нулей: 2295.00 → 2295, 0.6200 → 0.62 */
function feed_num($v): string
{
    if ($v === null || $v === '' || $v === false) {
        return '';
    }
    if (!is_numeric($v)) {
        return trim((string)$v);
    }
    return rtrim(rtrim(number_format((float)$v, 4, '.', ''), '0'), '.');
}

/** Экранирование для XML: без него амперсанд в названии рвёт весь фид. */
function feed_xml(string $s): string
{
    // Управляющие символы из текстов (бывают в описаниях из 1С) XML не пропускает.
    $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $s);
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * Описание под конкретное предложение.
 *
 * У товара с несколькими предложениями текст анонса один на всех — для
 * Яндекса такие позиции выглядят дублями. Дописываем то, что у каждого
 * своё: единицу цены, размеры, вес и артикул.
 */
function feed_offer_description(string $base, array $of): string
{
    $tail = [];
    if ($of['PRICE_UNIT'] !== '') {
        $tail[] = 'Цена указана за 1 ' . $of['PRICE_UNIT'] . '.';
    }
    $dims = array_filter([$of['LENGTH'], $of['WIDTH'], $of['HEIGHT']], static fn($x) => $x !== '' && (float)$x > 0);
    if (count($dims) === 3) {
        $tail[] = 'Размеры (ДxШxВ): ' . implode('x', $dims) . ' мм.';
    } elseif ($of['LENGTH'] !== '' && (float)$of['LENGTH'] > 0) {
        $tail[] = 'Длина: ' . $of['LENGTH'] . ' мм.';
    }
    // Вес в каталоге хранится в граммах, покупателю понятнее в килограммах.
    if ($of['WEIGHT'] !== '' && (float)$of['WEIGHT'] > 0) {
        $tail[] = 'Вес: ' . feed_num(round((float)$of['WEIGHT'] / 1000, 2)) . ' кг.';
    }
    if ($of['ARTICLE'] !== '') {
        $tail[] = 'Артикул: ' . $of['ARTICLE'] . '.';
    }
    return trim($base . ($tail ? ' ' . implode(' ', $tail) : ''));
}

// =========================================================================
// Справочники: разделы, единицы измерения
// =========================================================================

feed_log('--- старт выгрузки ---');

$sections = [];
$rsSect = CIBlockSection::GetList(
    ['LEFT_MARGIN' => 'ASC'],
    ['IBLOCK_ID' => $cfg['IBLOCK_PRODUCTS'], 'GLOBAL_ACTIVE' => 'Y'],
    false,
    ['ID', 'NAME', 'IBLOCK_SECTION_ID']
);
while ($s = $rsSect->Fetch()) {
    $sections[(int)$s['ID']] = ['NAME' => $s['NAME'], 'PARENT' => (int)$s['IBLOCK_SECTION_ID']];
}

$measures = [];
$rsM = CCatalogMeasure::GetList([], [], false, false, ['ID', 'SYMBOL_RUS']);
while ($m = $rsM->Fetch()) {
    $measures[(int)$m['ID']] = $m['SYMBOL_RUS'];
}

// =========================================================================
// Товары
// =========================================================================

$products = [];
$rs = CIBlockElement::GetList(
    ['SORT' => 'ASC', 'ID' => 'ASC'],
    [
        'IBLOCK_ID'             => $cfg['IBLOCK_PRODUCTS'],
        'ACTIVE'                => 'Y',
        'ACTIVE_DATE'           => 'Y',
        // Товар без активного раздела на сайте не открывается — в фиде он
        // был бы битой ссылкой.
        'SECTION_GLOBAL_ACTIVE' => 'Y',
    ],
    false,
    false,
    // IBLOCK_ID в select обязателен: без него GetProperties() не понимает,
    // свойства какого инфоблока читать, и молча возвращает пустой массив.
    // GetNextElement(true, true) — второй флаг даёт «сырые» поля с тильдой
    // (~NAME, ~DETAIL_PAGE_URL): без него их нет вовсе, а в обычных уже
    // экранированы кавычки, и в XML они ушли бы дважды экранированными.
    ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_TEXT', 'DETAIL_TEXT', 'DETAIL_PAGE_URL',
     'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'IBLOCK_SECTION_ID']
);

while ($el = $rs->GetNextElement(true, true)) {
    $f  = $el->GetFields();
    $id = (int)$f['ID'];

    // Товар бывает привязан к нескольким разделам — GetList отдаёт строку на
    // каждую привязку. Первая выигрывает, дубли отбрасываем.
    if (isset($products[$id])) {
        continue;
    }
    $p = $el->GetProperties();

    $params = [];
    foreach ($cfg['PARAMS'] as $code => $label) {
        $v = feed_prop($p, $code);
        if ($v !== '') {
            $params[$label] = $v;
        }
    }

    $sectId = (int)$f['IBLOCK_SECTION_ID'];
    $products[$id] = [
        'ID'         => $id,
        'NAME'       => $f['~NAME'],
        'URL'        => feed_url($f['~DETAIL_PAGE_URL']),
        'SECTION_ID' => isset($sections[$sectId]) ? $sectId : 0,
        'BRAND_ID'   => (int)feed_prop($p, 'BRAND'),
        'TEXT'       => feed_plain($f['~PREVIEW_TEXT']) !== '' ? feed_plain($f['~PREVIEW_TEXT']) : feed_plain($f['~DETAIL_TEXT']),
        'PICS'       => array_values(array_filter(array_merge(
            [feed_image($f['DETAIL_PICTURE']), feed_image($f['PREVIEW_PICTURE'])],
            array_map('feed_image', feed_prop_files($p, 'MORE_PHOTO'))
        ))),
        'ARTICLE'    => feed_prop($p, 'CML2_ARTICLE'),
        'KOEFS'      => feed_unit_koefs($p),
        'BASE_UNIT'  => feed_base_unit($p),
        'PARAMS'     => $params,
        'OFFERS'     => [],
    ];
}

if (!$products) {
    feed_log('ОШИБКА: не нашлось ни одного активного товара — выгрузка отменена, старые файлы не тронуты');
    exit(1);
}
feed_log('Активных товаров: ' . count($products));

// --- Бренды --------------------------------------------------------------

$brands   = [];
$brandIds = array_values(array_filter(array_unique(array_column($products, 'BRAND_ID'))));
if ($brandIds) {
    $rsB = CIBlockElement::GetList([], ['ID' => $brandIds], false, false, ['ID', 'NAME']);
    while ($b = $rsB->Fetch()) {
        $brands[(int)$b['ID']] = $b['NAME'];
    }
}

// =========================================================================
// Торговые предложения
// =========================================================================

$offerOwner = []; // ID предложения => ID товара

foreach (array_chunk(array_keys($products), 500) as $chunk) {
    $rsO = CIBlockElement::GetList(
        ['SORT' => 'ASC', 'ID' => 'ASC'],
        [
            'IBLOCK_ID'          => $cfg['IBLOCK_OFFERS'],
            'ACTIVE'             => 'Y',
            'ACTIVE_DATE'        => 'Y',
            'PROPERTY_CML2_LINK' => $chunk,
        ],
        false,
        false,
        ['ID', 'IBLOCK_ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'PROPERTY_CML2_LINK']
    );
    while ($el = $rsO->GetNextElement(true, true)) {
        $f = $el->GetFields();
        $offerId   = (int)$f['ID'];
        $productId = (int)$f['PROPERTY_CML2_LINK_VALUE'];
        if (!isset($products[$productId]) || isset($offerOwner[$offerId])) {
            continue;
        }
        $p = $el->GetProperties();
        $offerOwner[$offerId] = $productId;

        $koefs = feed_unit_koefs($p);
        $products[$productId]['OFFERS'][$offerId] = [
            'ID'        => $offerId,
            'NAME'      => $f['~NAME'],
            'ARTICLE'   => feed_prop($p, 'ARTICLE'),
            'PICS'      => array_values(array_filter(array_merge(
                [feed_image($f['DETAIL_PICTURE']), feed_image($f['PREVIEW_PICTURE'])],
                array_map('feed_image', feed_prop_files($p, 'MORE_PHOTO'))
            ))),
            // Коэффициенты и основная единица — у предложения свои, а если
            // не заведены, берём товара.
            'KOEFS'     => $koefs,
            'BASE_UNIT' => feed_base_unit($p),
        ];
    }
}
feed_log('Активных торговых предложений: ' . count($offerOwner));

// =========================================================================
// Позиции фида: предложение или простой товар
// =========================================================================

$items = []; // ID позиции => данные
foreach ($products as $pr) {
    if ($pr['OFFERS']) {
        foreach ($pr['OFFERS'] as $of) {
            $items[$of['ID']] = [
                'ID'         => $of['ID'],
                'PRODUCT_ID' => $pr['ID'],
                'NAME'       => $of['NAME'],
                'URL'        => $pr['URL'] . (!empty($cfg['OFFER_URL_PID']) ? '?pid=' . $of['ID'] : ''),
                'ARTICLE'    => $of['ARTICLE'] !== '' ? $of['ARTICLE'] : $pr['ARTICLE'],
                'PICS'       => array_values(array_unique(array_merge($of['PICS'], $pr['PICS']))),
                'KOEFS'      => $of['KOEFS'] ?: $pr['KOEFS'],
                'BASE_UNIT'  => $of['BASE_UNIT'] ?: $pr['BASE_UNIT'],
            ];
        }
    } else {
        $items[$pr['ID']] = [
            'ID'         => $pr['ID'],
            'PRODUCT_ID' => $pr['ID'],
            'NAME'       => $pr['NAME'],
            'URL'        => $pr['URL'],
            'ARTICLE'    => $pr['ARTICLE'],
            'PICS'       => $pr['PICS'],
            'KOEFS'      => $pr['KOEFS'],
            'BASE_UNIT'  => $pr['BASE_UNIT'],
        ];
    }
}

// --- Остатки, габариты, единица, цены — пачками --------------------------

$ids = array_keys($items);
foreach (array_chunk($ids, 500) as $chunk) {
    $rsP = CCatalogProduct::GetList([], ['ID' => $chunk], false, false,
        ['ID', 'QUANTITY', 'MEASURE', 'LENGTH', 'WIDTH', 'HEIGHT', 'WEIGHT']);
    while ($cp = $rsP->Fetch()) {
        $iid = (int)$cp['ID'];
        if (!isset($items[$iid])) {
            continue;
        }
        $items[$iid]['QUANTITY'] = (float)$cp['QUANTITY'];
        $items[$iid]['MEASURE']  = $measures[(int)$cp['MEASURE']] ?? '';
        $items[$iid]['LENGTH']   = feed_num($cp['LENGTH']);
        $items[$iid]['WIDTH']    = feed_num($cp['WIDTH']);
        $items[$iid]['HEIGHT']   = feed_num($cp['HEIGHT']);
        $items[$iid]['WEIGHT']   = feed_num($cp['WEIGHT']);
    }

    $rsPr = CPrice::GetList([], ['PRODUCT_ID' => $chunk, 'CATALOG_GROUP_ID' => $cfg['PRICE_TYPE_ID']],
        false, false, ['PRODUCT_ID', 'PRICE', 'CURRENCY']);
    while ($pp = $rsPr->Fetch()) {
        $iid = (int)$pp['PRODUCT_ID'];
        if (isset($items[$iid]) && (float)$pp['PRICE'] > 0) {
            $items[$iid]['BASE_PRICE'] = (float)$pp['PRICE'];
        }
    }
}

// =========================================================================
// Сборка предложений
// =========================================================================

$offersXml    = [];
$usedSections = [];
$stats = ['no_price' => 0, 'no_image' => 0, 'in_stock' => 0, 'simple' => 0];

foreach ($items as $it) {
    $pr = $products[$it['PRODUCT_ID']];

    // Цена в фид идёт та же, что покупатель видит на карточке: в ОСНОВНОЙ
    // единице (BASE_KOEF). У террасной доски это м², и вместо 2265 ₽ за штуку
    // человек видит 5000 ₽/м². Базовую цену Яндекс считает расхождением с
    // фактической и блокирует источник — так случилось с easydecking.ru
    // 30.07.2026. Где основная единица не задана — цена за штуку.
    $basePrice = $it['BASE_PRICE'] ?? 0;
    if ($basePrice <= 0) {
        $stats['no_price']++;
        continue;
    }
    $unit = $it['BASE_UNIT'];
    if ($unit > 0 && isset($it['KOEFS'][$unit])) {
        $price     = round($basePrice * $it['KOEFS'][$unit], 2);
        $priceUnit = $measures[$unit] ?? '';
    } else {
        $price     = $basePrice;
        $priceUnit = $it['MEASURE'] ?? '';
    }

    // Без картинки позицию Яндекс всё равно отклонит.
    $pics = array_slice($it['PICS'], 0, $cfg['PICTURES_MAX']);
    if (!$pics) {
        $stats['no_image']++;
        continue;
    }

    $inStock = ($it['QUANTITY'] ?? 0) >= $cfg['IN_STOCK_THRESHOLD'];
    if ($inStock) {
        $stats['in_stock']++;
    }
    if (!$pr['OFFERS']) {
        $stats['simple']++;
    }

    $of = [
        'PRICE_UNIT' => $priceUnit,
        'LENGTH'     => $it['LENGTH'] ?? '',
        'WIDTH'      => $it['WIDTH'] ?? '',
        'HEIGHT'     => $it['HEIGHT'] ?? '',
        'WEIGHT'     => $it['WEIGHT'] ?? '',
        'ARTICLE'    => $it['ARTICLE'],
    ];
    $description = mb_substr(feed_offer_description($pr['TEXT'], $of), 0, $cfg['DESCRIPTION_MAX']);

    $params = [];
    if ($priceUnit !== '') {
        $params['Цена за'] = '1 ' . $priceUnit;
    }
    foreach (['Длина, мм' => $of['LENGTH'], 'Ширина, мм' => $of['WIDTH'], 'Высота, мм' => $of['HEIGHT']] as $label => $v) {
        if ($v !== '' && (float)$v > 0) {
            $params[$label] = $v;
        }
    }
    $params += $pr['PARAMS'];

    if ($pr['SECTION_ID']) {
        $usedSections[$pr['SECTION_ID']] = true;
    }

    $x  = '      <offer id="' . $it['ID'] . '" available="' . ($inStock ? 'true' : 'false') . "\">\n";
    $x .= '        <url>' . feed_xml($it['URL']) . "</url>\n";
    $x .= '        <price>' . feed_num($price) . "</price>\n";
    $x .= '        <currencyId>' . feed_xml($cfg['CURRENCY']) . "</currencyId>\n";
    if ($pr['SECTION_ID']) {
        $x .= '        <categoryId>' . $pr['SECTION_ID'] . "</categoryId>\n";
    }
    foreach ($pics as $pic) {
        $x .= '        <picture>' . feed_xml($pic) . "</picture>\n";
    }
    $x .= '        <name>' . feed_xml($it['NAME']) . "</name>\n";
    $brand = $brands[$pr['BRAND_ID']] ?? '';
    if ($brand !== '') {
        $x .= '        <vendor>' . feed_xml($brand) . "</vendor>\n";
    }
    if ($it['ARTICLE'] !== '') {
        $x .= '        <vendorCode>' . feed_xml($it['ARTICLE']) . "</vendorCode>\n";
    }
    if ($description !== '') {
        $x .= '        <description>' . feed_xml($description) . "</description>\n";
    }
    foreach ($params as $name => $value) {
        $x .= '        <param name="' . feed_xml((string)$name) . '">' . feed_xml((string)$value) . "</param>\n";
    }
    $x .= "      </offer>\n";

    $offersXml[] = $x;
}

feed_log(sprintf(
    'Позиций в фиде: %d (из них простых товаров %d) | пропущено без цены %d, без картинки %d | в наличии %d',
    count($offersXml), $stats['simple'], $stats['no_price'], $stats['no_image'], $stats['in_stock']
));

if (!$offersXml) {
    feed_log('ОШИБКА: нет ни одной позиции — выгрузка отменена, старые файлы не тронуты');
    exit(1);
}

// =========================================================================
// XML: шапка, категории, предложения — с метками хоста и названия
// =========================================================================

// Категории — только встретившиеся в фиде, со всеми предками: ссылаться на
// категорию, которой нет в <categories>, YML не позволяет.
$need = [];
foreach (array_keys($usedSections) as $sid) {
    $cur = (int)$sid;
    $guard = 0;
    while ($cur && isset($sections[$cur]) && !isset($need[$cur]) && $guard++ < 20) {
        $need[$cur] = true;
        $cur = $sections[$cur]['PARENT'];
    }
}

$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<yml_catalog date="' . date('Y-m-d\TH:i:sP') . '">' . "\n";
$xml .= "  <shop>\n";
$xml .= '    <name>' . FEED_NAME_TOKEN . "</name>\n";
$xml .= '    <company>' . feed_xml($cfg['SHOP']['company']) . "</company>\n";
$xml .= '    <url>' . FEED_HOST_TOKEN . "/</url>\n";
$xml .= "    <currencies>\n";
$xml .= '      <currency id="' . feed_xml($cfg['CURRENCY']) . '" rate="1"/>' . "\n";
$xml .= "    </currencies>\n";
$xml .= "    <categories>\n";
foreach (array_keys($need) as $sid) {
    $parent = $sections[$sid]['PARENT'];
    $attr = ($parent && isset($need[$parent])) ? ' parentId="' . $parent . '"' : '';
    $xml .= '      <category id="' . $sid . '"' . $attr . '>' . feed_xml($sections[$sid]['NAME']) . "</category>\n";
}
$xml .= "    </categories>\n";
$xml .= "    <offers>\n";
$xml .= implode('', $offersXml);
$xml .= "    </offers>\n  </shop>\n</yml_catalog>\n";
unset($offersXml);

// =========================================================================
// Города
// =========================================================================

$rc = $cfg['REGIONS'];
$baseHost = parse_url($cfg['BASE_URL'], PHP_URL_HOST);
$regions = [];
$rsR = CIBlockElement::GetList(
    ['SORT' => 'ASC', 'NAME' => 'ASC'],
    ['IBLOCK_ID' => $rc['iblock'], 'ACTIVE' => 'Y'],
    false,
    false,
    ['ID', 'IBLOCK_ID', 'NAME', 'PROPERTY_' . $rc['domain_prop']]
);
while ($r = $rsR->Fetch()) {
    $domain = strtolower(trim((string)($r['PROPERTY_' . $rc['domain_prop'] . '_VALUE'] ?? '')));
    if ($domain === '') {
        continue;
    }
    // «vrn.latitudo.ru» → «vrn», основной домен → apex_code
    $code = ($domain === $baseHost) ? $rc['apex_code'] : explode('.', $domain)[0];
    $code = preg_replace('/[^a-z0-9-]/', '', $code);
    if ($code === '' || isset($regions[$code])) {
        continue;
    }
    $regions[$code] = ['name' => $r['NAME'], 'url' => 'https://' . $domain];
}

if (!$regions) {
    feed_log('ОШИБКА: не нашлось ни одного города в инфоблоке регионов');
    exit(1);
}

// =========================================================================
// Запись
// =========================================================================

$outDir = $SITE_ROOT . $cfg['OUTPUT_DIR'];
if (!$opts['dry-run'] && !is_dir($outDir) && !@mkdir($outDir, 0755, true) && !is_dir($outDir)) {
    feed_log('ОШИБКА: не создать каталог ' . $outDir);
    exit(1);
}

/** Атомарная запись: пишем во временный файл и подменяем им боевой одним движением. */
function feed_put(string $path, string $content): bool
{
    $tmp = $path . '.tmp';
    if (@file_put_contents($tmp, $content) === false) {
        feed_log('ОШИБКА: не записать ' . $tmp);
        return false;
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        feed_log('ОШИБКА: не переименовать ' . $tmp . ' → ' . $path);
        return false;
    }
    @chmod($path, 0644);
    return true;
}

$exit  = 0;
$done  = 0;
$bytes = 0;
$files = [];
foreach ($regions as $code => $region) {
    $file = str_replace('{code}', $code, $rc['file_pattern']);
    $files[$file] = true;
    $content = str_replace(
        [FEED_NAME_TOKEN, FEED_HOST_TOKEN],
        [feed_xml(str_replace('{region}', $region['name'], $cfg['SHOP']['name_pattern'])), $region['url']],
        $xml
    );
    if ($opts['dry-run']) {
        $done++;
        $bytes += strlen($content);
        continue;
    }
    if (feed_put($outDir . '/' . $file, $content)) {
        $done++;
        $bytes += strlen($content);
    } else {
        $exit = 1;
    }
}

// Город убрали из инфоблока — его старый файл больше не обновлялся бы и
// висел с устаревшими ценами. Удаляем фиды городов, которых больше нет.
if (!$opts['dry-run'] && $exit === 0) {
    $mask = str_replace('{code}', '*', $rc['file_pattern']);
    foreach (glob($outDir . '/' . $mask) ?: [] as $old) {
        if (!isset($files[basename($old)])) {
            @unlink($old);
            feed_log('Удалён фид города, которого больше нет: ' . basename($old));
        }
    }
}

feed_log(sprintf(
    '%sФидов: %d из %d городов, по %.1f МБ, всего %.1f МБ',
    $opts['dry-run'] ? '[dry-run] ' : '',
    $done, count($regions), $done ? $bytes / $done / 1048576 : 0, $bytes / 1048576
));
feed_log('--- готово ---');
exit($exit);
