<?php
/**
 * Карта изображений для Google — sitemap-images.xml.
 *
 *     php local/tools/sitemap_images_generate.php
 *     php local/tools/sitemap_images_generate.php --quiet   # только ошибки
 *     php local/tools/sitemap_images_generate.php --dry-run # не писать файл
 *
 * Зачем. Обычная карта сайта (её собирает модуль seo, см. sitemap_generate.php)
 * перечисляет только адреса страниц. Google умеет читать расширение
 * sitemap-image: рядом с адресом страницы перечисляются снимки, которые на ней
 * опубликованы, — так робот находит фотографии, до которых иначе добирается
 * долго или не добирается вовсе. Яндекс это расширение не читает, у него
 * картинки берутся из самой страницы (микроразметка и alt, сентябрь 2026).
 *
 * Что попадает в карту: детальные страницы каталога, портфолио и «Материалов»
 * со всеми их фотографиями — детальная картинка, галереи из свойств и снимки
 * из редактора блоков (sprint.editor). Разбор свойств редактора общий с
 * микроразметкой — LatitudoSchema (local/php_interface/include).
 *
 * Свой файл, а не строчка в общей карте: индекс sitemap.xml переписывает
 * модуль seo при каждой генерации, и всё дописанное туда пропало бы. Поэтому
 * карта отдельная, а роботам её показывает вторая строка Sitemap в robots.txt.
 *
 * Заголовки image:title и image:caption не печатаем: Google перестал их
 * учитывать, значение имеет только image:loc.
 *
 * Крон рядом с обычной картой:
 *     40 2 * * * /opt/php/8.2/bin/php <корень>/local/tools/sitemap_images_generate.php --quiet
 */

if (PHP_SAPI !== 'cli') {
    die('CLI only');
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$opts = getopt('', ['quiet', 'dry-run']);
$quiet = isset($opts['quiet']);
$dryRun = isset($opts['dry-run']);

/** Адрес сайта: скрипт запускает крон, HTTP_HOST в нём пустой. */
const ND_SITEMAP_IMG_HOST = 'https://latitudo.ru';

/** Имя файла в корне сайта. */
const ND_SITEMAP_IMG_FILE = '/sitemap-images.xml';

/** Больше этого числа снимков на страницу Google не читает. */
const ND_SITEMAP_IMG_MAX_PER_PAGE = 1000;

/** Ограничения формата: адресов в файле и его размер. */
const ND_SITEMAP_IMG_MAX_URLS = 50000;
const ND_SITEMAP_IMG_MAX_BYTES = 47185920; // 45 МБ

/**
 * Инфоблоки и где у них лежат фотографии.
 *
 * PICTURES  — поля элемента; PHOTOS — свойства-картинки (одно или много
 * значений); EDITOR — свойства редактора блоков.
 */
$ndSources = [
    19 => [ // Каталог
        'PICTURES' => ['DETAIL_PICTURE', 'PREVIEW_PICTURE'],
        'PHOTOS' => ['MORE_PHOTO'],
        'EDITOR' => ['EDITOR1'],
        /* Поля снимков у торговых предложений — их показывает та же страница. */
        'OFFERS' => ['DETAIL_PICTURE', 'PREVIEW_PICTURE'],
    ],
    18 => [ // Портфолио
        'PICTURES' => ['DETAIL_PICTURE'],
        'PHOTOS' => ['GALLEY_BIG'],
        'EDITOR' => ['EDITOR1', 'EDITOR2'],
    ],
    14 => [ // Материалы
        'PICTURES' => ['DETAIL_PICTURE'],
        'PHOTOS' => ['PHOTOS'],
        'EDITOR' => ['EDITOR1'],
    ],
];

function say(string $text): void
{
    if (!$GLOBALS['quiet']) {
        echo $text . PHP_EOL;
    }
}

function fail(string $text): void
{
    fwrite(STDERR, $text . PHP_EOL);
    exit(1);
}

/**
 * Значения свойства из строки GetPropertyValues.
 *
 * Разные версии ядра кладут значения то по коду свойства, то по его номеру,
 * поэтому смотрим оба ключа. Одиночное значение возвращаем списком из одного.
 */
function ndPropertyValues(array $row, string $code, array $propertyIds): array
{
    $value = null;
    if (isset($row[$code])) {
        $value = $row[$code];
    } elseif (isset($propertyIds[$code], $row[$propertyIds[$code]])) {
        $value = $row[$propertyIds[$code]];
    }

    if ($value === null || $value === '' || $value === []) {
        return [];
    }

    return is_array($value) ? array_values($value) : [$value];
}

/**
 * Снимки торговых предложений, разложенные по ID товара-родителя.
 *
 * @return array array(<ID товара> => array(<ID файла>, ...))
 */
function ndOfferImages(int $skuIblockId, int $linkPropertyId, array $pictures): array
{
    $result = [];
    $rs = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        ['IBLOCK_ID' => $skuIblockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'],
        false,
        false,
        array_merge(['ID', 'PROPERTY_' . $linkPropertyId], $pictures)
    );
    while ($row = $rs->GetNext(true, false)) {
        $parentId = (int) $row['PROPERTY_' . $linkPropertyId . '_VALUE'];
        if (!$parentId) {
            continue;
        }
        foreach ($pictures as $field) {
            if ((int) $row[$field] > 0) {
                $result[$parentId][] = (int) $row[$field];
            }
        }
    }

    return $result;
}

if (!CModule::IncludeModule('iblock')) {
    fail('Модуль iblock не установлен');
}
if (!class_exists('LatitudoSchema')) {
    fail('Не подключён LatitudoSchema (local/php_interface/include/latitudo_schema.php)');
}

$started = microtime(true);

/** Страницы: адрес → список ID файлов. */
$pages = [];
/** Все встреченные файлы — адреса запрашиваем одним махом в конце. */
$fileIds = [];

foreach ($ndSources as $iblockId => $where) {
    $pagesBefore = count($pages);

    /* Элементы: адрес страницы и картинки-поля. */
    $elements = [];
    $rs = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'],
        false,
        false,
        array_merge(['ID', 'DETAIL_PAGE_URL'], $where['PICTURES'])
    );
    while ($row = $rs->GetNext(true, false)) {
        $url = trim((string) $row['DETAIL_PAGE_URL']);
        if ($url === '') {
            continue;
        }

        $ids = [];
        foreach ($where['PICTURES'] as $field) {
            if ((int) $row[$field] > 0) {
                $ids[] = (int) $row[$field];
            }
        }

        $elements[(int) $row['ID']] = ['URL' => $url, 'FILES' => $ids];
    }

    if (!$elements) {
        say(sprintf('Инфоблок %d: активных элементов нет', $iblockId));
        continue;
    }

    /* Свойства всех элементов одним проходом: по запросу на элемент было бы
       несколько тысяч обращений к базе. */
    $propertyCodes = array_merge($where['PHOTOS'], $where['EDITOR']);
    if ($propertyCodes) {
        /* GetPropertyValues раскладывает значения по ID свойства, а не по коду,
           поэтому сначала переводим коды в номера. */
        $propertyIds = [];
        /* Список кодов фильтру GetList не скормить — отбираем на месте. */
        $rsProp = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId]);
        while ($prop = $rsProp->Fetch()) {
            if (in_array($prop['CODE'], $propertyCodes, true)) {
                $propertyIds[$prop['CODE']] = (int) $prop['ID'];
            }
        }

        $missing = array_diff($propertyCodes, array_keys($propertyIds));
        if ($missing) {
            say(sprintf('Инфоблок %d: нет свойств %s', $iblockId, implode(', ', $missing)));
        }

        $rs = CIBlockElement::GetPropertyValues(
            $iblockId,
            ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'],
            false
        );
        while ($row = $rs->Fetch()) {
            $elementId = (int) $row['IBLOCK_ELEMENT_ID'];
            if (!isset($elements[$elementId])) {
                continue;
            }

            foreach ($where['PHOTOS'] as $code) {
                foreach (ndPropertyValues($row, $code, $propertyIds) as $value) {
                    if ((int) $value > 0) {
                        $elements[$elementId]['FILES'][] = (int) $value;
                    }
                }
            }

            foreach ($where['EDITOR'] as $code) {
                foreach (ndPropertyValues($row, $code, $propertyIds) as $value) {
                    foreach (LatitudoSchema::editorImagesFromValue($value) as $image) {
                        $elements[$elementId]['FILES'][] = (int) $image['ID'];
                    }
                }
            }
        }
    }

    /* Торговые предложения. Их снимки показывает страница товара, но лежат
       они в отдельном инфоблоке, поэтому приписываем их адресу родителя. */
    if (!empty($where['OFFERS']) && CModule::IncludeModule('catalog')) {
        $sku = CCatalogSKU::GetInfoByProductIBlock($iblockId);
        if ($sku && $sku['IBLOCK_ID']) {
            $offerFiles = ndOfferImages((int) $sku['IBLOCK_ID'], (int) $sku['SKU_PROPERTY_ID'], $where['OFFERS']);
            $attached = 0;
            foreach ($offerFiles as $parentId => $ids) {
                if (!isset($elements[$parentId])) {
                    continue;
                }
                foreach ($ids as $id) {
                    $elements[$parentId]['FILES'][] = $id;
                }
                ++$attached;
            }
            say(sprintf('Инфоблок %d: снимки предложений добавлены %d товарам', $iblockId, $attached));
        }
    }

    foreach ($elements as $element) {
        $ids = array_slice(array_values(array_unique($element['FILES'])), 0, ND_SITEMAP_IMG_MAX_PER_PAGE);
        if (!$ids) {
            continue;
        }

        $url = ND_SITEMAP_IMG_HOST . '/' . ltrim($element['URL'], '/');
        $pages[$url] = $ids;
        foreach ($ids as $id) {
            $fileIds[$id] = true;
        }
    }

    say(sprintf('Инфоблок %d: страниц с фотографиями %d', $iblockId, count($pages) - $pagesBefore));
}

if (!$pages) {
    fail('Не нашлось ни одной страницы с фотографиями — проверьте настройки инфоблоков');
}

/* Адреса файлов: порциями, чтобы не собирать запрос на десятки тысяч ID. */
$fileSrc = [];
$fileIds = array_keys($fileIds);
foreach (array_chunk($fileIds, 500) as $chunk) {
    $rs = CFile::GetList([], ['@ID' => implode(',', $chunk)]);
    while ($file = $rs->Fetch()) {
        $src = CFile::GetFileSRC($file);
        if ($src) {
            $fileSrc[(int) $file['ID']] = $src;
        }
    }
}

say(sprintf('Файлов: найдено %d из %d', count($fileSrc), count($fileIds)));

/* Сборка XML. */
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
    . ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

$urlCount = 0;
$imageCount = 0;
foreach ($pages as $url => $ids) {
    $images = '';
    foreach ($ids as $id) {
        if (empty($fileSrc[$id])) {
            continue;
        }
        $images .= '<image:image><image:loc>'
            . htmlspecialchars(ND_SITEMAP_IMG_HOST . $fileSrc[$id], ENT_XML1 | ENT_QUOTES, 'UTF-8')
            . '</image:loc></image:image>';
        ++$imageCount;
    }

    if ($images === '') {
        continue;
    }

    $xml .= '<url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>'
        . $images . '</url>' . "\n";
    ++$urlCount;
}

$xml .= '</urlset>' . "\n";

if ($urlCount > ND_SITEMAP_IMG_MAX_URLS || strlen($xml) > ND_SITEMAP_IMG_MAX_BYTES) {
    fail(sprintf(
        'Карта переросла ограничения формата (%d адресов, %d байт) — пора делить её на части',
        $urlCount,
        strlen($xml)
    ));
}

say(sprintf(
    'Собрано: адресов %d, снимков %d, размер %s КБ, за %.1f с',
    $urlCount,
    $imageCount,
    number_format(strlen($xml) / 1024, 0, ',', ' '),
    microtime(true) - $started
));

if ($dryRun) {
    say('Пробный запуск: файл не записан');
    exit(0);
}

$path = $_SERVER['DOCUMENT_ROOT'] . ND_SITEMAP_IMG_FILE;
/* Пишем через временный файл: полуготовую карту роботы не увидят. */
$tmp = $path . '.tmp';
if (file_put_contents($tmp, $xml) === false || !rename($tmp, $path)) {
    @unlink($tmp);
    fail('Не удалось записать ' . $path);
}

say('Записан ' . $path);
