<?php
/**
 * Картинки «склад в городе» для фида Яндекс Бизнеса.
 *
 * Для активных торговых предложений EasyDecking и LATITUDO берёт главное фото
 * и делает четыре версии — с подписью «СКЛАД / в Краснодаре (Москве, Белгороде,
 * Воронеже)» — как в образце: жёлтый текст с мягкой тенью, логотип-домик
 * в левом верхнем углу. Готовые файлы пишутся в свойства предложения
 * PICTURE_FID_KRD / _MSK / _BEL / _VRN (ID 456–459).
 *
 * Зачем: в Яндекс Бизнесе у каждого города своя карточка компании и свой фид,
 * и картинка должна говорить про склад этого города, а не общая.
 *
 * Запуск (на сервере):
 *   php local/tools/nd_feed_pictures.php --dry-run
 *   php local/tools/nd_feed_pictures.php --limit=3          # проба на трёх позициях
 *   php local/tools/nd_feed_pictures.php                    # все позиции
 *   php local/tools/nd_feed_pictures.php --force            # перезаписать уже заполненные
 *   php local/tools/nd_feed_pictures.php --cities=krd,msk   # только эти города
 *
 * По умолчанию уже заполненные свойства не трогаются: часть картинок сделана
 * вручную (в них ещё «Гарантия до 25 лет»), их перезапись — только с --force.
 */

// =========================================================================
// Bootstrap
// =========================================================================

$SITE_ROOT = dirname(__DIR__, 2);
$_SERVER['DOCUMENT_ROOT']  = $SITE_ROOT;
$_SERVER['HTTP_HOST']      = 'latitudo.ru';
$_SERVER['SERVER_NAME']    = 'latitudo.ru';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI']    = '/';

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('DisableEventsCheck', true);
define('NO_AGENT_CHECK', true);

require $SITE_ROOT . '/bitrix/modules/main/include/prolog_before.php';

if (!CModule::IncludeModule('iblock')) {
    fwrite(STDERR, "Не подключился модуль iblock\n");
    exit(1);
}
@set_time_limit(0);
ini_set('memory_limit', '512M');

// =========================================================================
// Настройки
// =========================================================================

const IB_PRODUCTS = 19;
const IB_OFFERS   = 20;
const PROP_BRAND  = 134;   // BRAND, инфоблок товаров
const PROP_LINK   = 167;   // CML2_LINK, инфоблок предложений
const PROP_PHOTOS = 171;   // MORE_PHOTO, инфоблок предложений

const BRANDS = [26365 => 'EasyDecking', 24936 => 'LATITUDO'];

/** Код города => [ID свойства, «в …», файл-префикс] */
const CITIES = [
    'krd' => [456, 'Краснодаре', 'krd'],
    'bel' => [457, 'Белгороде', 'bel'],
    'vrn' => [458, 'Воронеже', 'vrn'],
    'msk' => [459, 'Москве', 'msk'],
];

const MAX_SIDE  = 1200;
const JPEG_Q    = 88;

// =========================================================================
// Ключи запуска
// =========================================================================

$opts = ['dry-run' => false, 'force' => false, 'limit' => 0, 'offer' => 0, 'cities' => ''];
foreach (array_slice($argv ?? [], 1) as $arg) {
    if ($arg === '--dry-run') {
        $opts['dry-run'] = true;
    } elseif ($arg === '--force') {
        $opts['force'] = true;
    } elseif (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $opts['limit'] = (int)$m[1];
    } elseif (preg_match('/^--offer=(\d+)$/', $arg, $m)) {
        $opts['offer'] = (int)$m[1];
    } elseif (preg_match('/^--cities=([a-z,]+)$/i', $arg, $m)) {
        $opts['cities'] = strtolower($m[1]);
    } else {
        fwrite(STDERR, "Неизвестный ключ: {$arg}\n");
        exit(2);
    }
}

$cities = CITIES;
if ($opts['cities'] !== '') {
    $want = array_filter(array_map('trim', explode(',', $opts['cities'])));
    $cities = array_intersect_key(CITIES, array_flip($want));
    if (!$cities) {
        fwrite(STDERR, "Неизвестные города: {$opts['cities']}\n");
        exit(2);
    }
}

// =========================================================================
// Отрисовка картинки
// =========================================================================

/** Домик из общего логотипа: белый фон — прозрачный. */
function nd_logo(int $size): ?GdImage
{
    global $SITE_ROOT;

    static $trimmed = null;
    if ($trimmed === null) {
        $path = $SITE_ROOT . '/images/company/logo.png';
        $info = @getimagesize($path);
        $logo = $info ? match ($info[2]) {
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            default        => false,
        } : false;
        if (!$logo) {
            $trimmed = false;
        } else {
            imagealphablending($logo, false);
            imagesavealpha($logo, true);
            $w = imagesx($logo);
            $h = imagesy($logo);
            for ($x = 0; $x < $w; $x++) {
                for ($y = 0; $y < $h; $y++) {
                    $c = imagecolorat($logo, $x, $y);
                    if ((($c >> 16) & 0xFF) > 246 && ((($c >> 8) & 0xFF)) > 246 && (($c & 0xFF)) > 246) {
                        imagesetpixel($logo, $x, $y, imagecolorallocatealpha($logo, 255, 255, 255, 127));
                    }
                }
            }
            $trimmed = $logo;
        }
    }
    if ($trimmed === false) {
        return null;
    }
    $src = $trimmed;
    $w = imagesx($src);
    $h = imagesy($src);
    $cropW = min($w, (int)round($h * 0.95));
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefilledrectangle($dst, 0, 0, $size, $size, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagealphablending($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, $cropW, $h);
    return $dst;
}

/** Жёлтый текст с мягкой тенью (тень — уменьшенный слой, растянутый обратно). */
function nd_caption(GdImage $im, string $city, string $font): void
{
    $W = imagesx($im);
    $H = imagesy($im);

    $lines = ['СКЛАД', 'в ' . $city];
    $baseLast = (int)($H - max(18, $H * 0.055));

    // Кегль по самой длинной строке.
    $longest = $lines[1];
    $b = imagettfbbox(100, 0, $font, $longest);
    $fs = (int)floor(100 * ($W * 0.76) / max(1, $b[2] - $b[0]));
    $fs = max(18, min($fs, (int)($H * 0.17)));
    $lineH = (int)round($fs * 1.12);
    $bases = [$baseLast - $lineH, $baseLast];

    // Тень: текст в полный размер, затем слой уменьшаем и растягиваем обратно —
    // так края размываются, а размер букв остаётся прежним.
    $layer = imagecreatetruecolor($W, $H);
    imagealphablending($layer, false);
    imagesavealpha($layer, true);
    imagefilledrectangle($layer, 0, 0, $W, $H, imagecolorallocatealpha($layer, 0, 0, 0, 127));
    imagealphablending($layer, true);
    $sc = imagecolorallocatealpha($layer, 45, 35, 25, 55);
    foreach ($lines as $i => $text) {
        $bb = imagettfbbox($fs, 0, $font, $text);
        $x = (int)(($W - ($bb[2] - $bb[0])) / 2);
        imagettftext($layer, $fs, 0, $x + 3, $bases[$i] + 4, $sc, $font, $text);
    }
    $ss = 5;
    $sw = max(1, (int)($W / $ss));
    $sh = max(1, (int)($H / $ss));
    $small = imagecreatetruecolor($sw, $sh);
    imagealphablending($small, false);
    imagesavealpha($small, true);
    imagefilledrectangle($small, 0, 0, $sw, $sh, imagecolorallocatealpha($small, 0, 0, 0, 127));
    imagecopyresampled($small, $layer, 0, 0, 0, 0, $sw, $sh, $W, $H);
    imagealphablending($im, true);
    imagecopyresampled($im, $small, 0, 0, 0, 0, $W, $H, $sw, $sh);
    imagedestroy($layer);
    imagedestroy($small);

    // Текст.
    $yellow = imagecolorallocate($im, 255, 224, 0);
    foreach ($lines as $i => $text) {
        $bb = imagettfbbox($fs, 0, $font, $text);
        $x = (int)(($W - ($bb[2] - $bb[0])) / 2);
        imagettftext($im, $fs, 0, $x, $bases[$i], $yellow, $font, $text);
    }
}

/** Загрузить исходное фото (jpg/png/webp) с учётом EXIF-поворота. */
function nd_load_image(string $path): ?GdImage
{
    $info = @getimagesize($path);
    if (!$info) {
        return null;
    }
    $im = match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
        IMAGETYPE_PNG  => @imagecreatefrompng($path),
        IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        default        => false,
    };
    return $im ?: null;
}

/** Собрать картинку города во временный файл; вернуть путь или null. */
function nd_render(string $srcPath, array $city, string $font): ?string
{
    $src = nd_load_image($srcPath);
    if (!$src) {
        return null;
    }
    $w0 = imagesx($src);
    $h0 = imagesy($src);
    $k = min(1, MAX_SIDE / max($w0, $h0));
    $w = (int)round($w0 * $k);
    $h = (int)round($h0 * $k);

    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
    imagecopyresampled($im, $src, 0, 0, 0, 0, $w, $h, $w0, $h0);
    imagedestroy($src);

    $logo = nd_logo(max(56, (int)round($w * 0.13)));
    if ($logo) {
        $pad = max(12, (int)round($w * 0.035));
        imagecopy($im, $logo, $pad, $pad, 0, 0, imagesx($logo), imagesy($logo));
        imagedestroy($logo);
    }

    nd_caption($im, $city[1], $font);

    $tmp = tempnam(sys_get_temp_dir(), 'ndfeed');
    $tmpJpg = $tmp . '.jpg';
    imagejpeg($im, $tmpJpg, JPEG_Q);
    imagedestroy($im);
    @unlink($tmp);
    return is_file($tmpJpg) ? $tmpJpg : null;
}

// =========================================================================
// Что обрабатываем
// =========================================================================

$font = $SITE_ROOT . '/fonts/PTC75F_W.ttf';
if (!is_file($font)) {
    fwrite(STDERR, "Не найден шрифт {$font}\n");
    exit(1);
}

// Товары нужных брендов.
$productIds = [];
$rs = CIBlockElement::GetList([], [
    'IBLOCK_ID'        => IB_PRODUCTS,
    'ACTIVE'           => 'Y',
    'PROPERTY_BRAND'   => array_keys(BRANDS),
], false, false, ['ID', 'IBLOCK_ID']);
while ($row = $rs->GetNext()) {
    $productIds[] = (int)$row['ID'];
}
if (!$productIds) {
    fwrite(STDERR, "Не нашлось товаров нужных брендов\n");
    exit(1);
}

// Их активные предложения.
$offers = [];
foreach (array_chunk($productIds, 500) as $chunk) {
    $rs = CIBlockElement::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], [
        'IBLOCK_ID'          => IB_OFFERS,
        'ACTIVE'             => 'Y',
        'PROPERTY_CML2_LINK' => $chunk,
    ], false, false, ['ID', 'NAME', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'PROPERTY_CML2_LINK']);
    while ($row = $rs->GetNext()) {
        $offers[(int)$row['ID']] = [
            'ID'      => (int)$row['ID'],
            'NAME'    => $row['NAME'],
            'DETAIL'  => (int)$row['DETAIL_PICTURE'],
            'PREVIEW' => (int)$row['PREVIEW_PICTURE'],
            'PRODUCT' => (int)$row['PROPERTY_CML2_LINK_VALUE'],
        ];
    }
}
if ($opts['offer']) {
    $offers = array_intersect_key($offers, [$opts['offer'] => true]);
    if (!$offers) {
        fwrite(STDERR, "Предложение {$opts['offer']} не найдено среди активных нужных брендов\n");
        exit(1);
    }
}
if (!$offers) {
    fwrite(STDERR, "Не нашлось активных предложений\n");
    exit(1);
}

printf("Товаров брендов: %d, активных предложений: %d, городов: %d\n",
    count($productIds), count($offers), count($cities));

// Уже заполненные свойства и фотографии — двумя пачками.
$filled = [];   // offerId => [propId => true]
$photos = [];   // offerId => [fileId, ...] (MORE_PHOTO)
foreach (array_chunk(array_keys($offers), 500) as $chunk) {
    $propIds = array_merge([PROP_PHOTOS], array_column($cities, 0));
    $values = [];
    CIBlockElement::GetPropertyValuesArray($values, IB_OFFERS, ['ID' => $chunk], ['ID' => $propIds]);
    foreach ($values as $offerId => $props) {
        foreach ($props as $prop) {
            $id = (int)$prop['ID'];
            if ($id === PROP_PHOTOS) {
                $photos[(int)$offerId] = array_values(array_filter(array_map('intval', (array)$prop['VALUE'])));
            } elseif ((int)$prop['VALUE'] > 0) {
                $filled[(int)$offerId][$id] = true;
            }
        }
    }
}

$stats = [
    'offers' => 0, 'pics' => 0, 'skipped_filled' => 0, 'skipped_no_photo' => 0,
    'skipped_missing_file' => 0, 'skip_existing_city' => 0, 'errors' => 0,
];
$started = microtime(true);
$processed = 0;

foreach ($offers as $offerId => $offer) {
    if ($opts['limit'] && $processed >= $opts['limit']) {
        break;
    }
    $processed++;
    $stats['offers']++;

    // Города, куда ещё не заполнено.
    $todo = [];
    foreach ($cities as $code => $city) {
        if (!$opts['force'] && isset($filled[$offerId][$city[0]])) {
            $stats['skip_existing_city']++;
            continue;
        }
        $todo[$code] = $city;
    }
    if (!$todo) {
        $stats['skipped_filled']++;
        continue;
    }

    // Исходное фото: своё у предложения, затем галерея, затем фото товара.
    $srcId = $offer['DETAIL'] ?: $offer['PREVIEW'] ?: ($photos[$offerId][0] ?? 0);
    if (!$srcId) {
        $stats['skipped_no_photo']++;
        continue;
    }
    $srcPath = $_SERVER['DOCUMENT_ROOT'] . CFile::GetPath($srcId);
    if (!is_file($srcPath)) {
        $stats['skipped_missing_file']++;
        fwrite(STDERR, "нет файла: {$srcPath} (предложение {$offerId})\n");
        continue;
    }

    if ($opts['dry-run']) {
        $stats['pics'] += count($todo);
        continue;
    }

    foreach ($todo as $code => $city) {
        $tmp = nd_render($srcPath, $city, $font);
        if (!$tmp) {
            $stats['errors']++;
            continue;
        }
        $file = CFile::MakeFileArray($tmp);
        if (!$file) {
            @unlink($tmp);
            $stats['errors']++;
            continue;
        }
        $file['name'] = 'feed-' . $city[2] . '-' . $offerId . '.jpg';
        $file['type'] = 'image/jpeg';
        CIBlockElement::SetPropertyValuesEx(
            $offerId,
            IB_OFFERS,
            [$city[0] => ['VALUE' => $file]]
        );
        // SetPropertyValuesEx ничего не возвращает — проверяем, что значение записалось.
        $check = CIBlockElement::GetProperty(IB_OFFERS, $offerId, [], ['ID' => $city[0]])->Fetch();
        @unlink($tmp); // удаляем только после того, как Bitrix скопировал файл
        if ((int)($check['VALUE'] ?? 0) <= 0) {
            $stats['errors']++;
            fwrite(STDERR, "не записалось свойство {$city[0]} у предложения {$offerId}\n");
            continue;
        }
        $stats['pics']++;
    }

    if ($processed % 25 === 0) {
        printf("  … %d предложений, картинок %d, %s\n", $processed, $stats['pics'], date('H:i:s'));
    }
}

printf(
    "%sПредложений: %d | картинок: %d | уже было (город): %d | пропущено: без фото %d, нет файла %d, свойство уже заполнено %d | ошибок: %d | %.1f мин\n",
    $opts['dry-run'] ? '[dry-run] ' : '',
    $stats['offers'], $stats['pics'], $stats['skip_existing_city'],
    $stats['skipped_no_photo'], $stats['skipped_missing_file'], $stats['skipped_filled'],
    $stats['errors'], (microtime(true) - $started) / 60
);
