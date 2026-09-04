<?php
// /local/php_interface/include/sitemap_handler.php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Context;
use Bitrix\Main\Web\Uri;

// ----- 1. Определяем текущий домен (регион) -----
$request = Context::getCurrent()->getRequest();
$host = $request->getHttpHost();
$host = parse_url('http://' . $host, PHP_URL_HOST); // очищаем от порта

// Если скрипт вызван из командной строки (cron), принимаем домен как аргумент
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $host = parse_url($argv[1], PHP_URL_HOST);
    $_SERVER['HTTP_HOST'] = $host;
}

// Карта соответствия доменов и ID регионов (ваши данные)
$regionMap = [
    'latitudo.ru'                    => 10039,
    'belgorod.latitudo.ru'           => 9277,
    'vrn.latitudo.ru'                => 9278,
    'krasnodar.latitudo.ru'          => 9568,
    'oskol.latitudo.ru'              => 10101,
    'kursk.latitudo.ru'              => 10102,
    'astrahan.latitudo.ru'           => 21989,
    'bryansk.latitudo.ru'            => 21990,
    'cheboksari.latitudo.ru'         => 21992,
    'chelyabinsk.latitudo.ru'        => 21993,
    'ekb.latitudo.ru'                => 21994,
    'ivanovo.latitudo.ru'            => 21996,
    'kaluga.latitudo.ru'             => 21997,
    'kazan.latitudo.ru'              => 21998,
    'kirov.latitudo.ru'              => 21999,
    'kostroma.latitudo.ru'           => 22000,
    'lipetsk.latitudo.ru'            => 22002,
    'nn.latitudo.ru'                 => 22003,
    'novgorod.latitudo.ru'           => 22004,
    'orel.latitudo.ru'               => 22005,
    'orenburg.latitudo.ru'           => 22006,
    'penza.latitudo.ru'              => 22007,
    'perm.latitudo.ru'               => 22008,
    'pskov.latitudo.ru'              => 22010,
    'ryazan.latitudo.ru'             => 22011,
    'samara.latitudo.ru'             => 22012,
    'saratov.latitudo.ru'            => 22013,
    'smolensk.latitudo.ru'           => 22014,
    'sochi.latitudo.ru'              => 22015,
    'spb.latitudo.ru'                => 22016,
    'tambov.latitudo.ru'             => 22017,
    'rostov.latitudo.ru'             => 22018,
    'tolyatti.latitudo.ru'           => 22019,
    'tula.latitudo.ru'               => 22020,
    'tver.latitudo.ru'               => 22021,
    'ufa.latitudo.ru'                => 22022,
    'ulyanovsk.latitudo.ru'          => 22023,
    'vladimir.latitudo.ru'           => 22024,
    'volgograd.latitudo.ru'          => 22025,
    'vologda.latitudo.ru'            => 22026,
    'yaroslavl.latitudo.ru'          => 22027,
    'yoshkarola.latitudo.ru'         => 22028,
    'stavropol.latitudo.ru'          => 22029,
    'simferopol.latitudo.ru'         => 24619,
    'sevastopol.latitudo.ru'         => 24620,
    'novosibirsk.latitudo.ru'        => 24621,
    'krasnoyarsk.latitudo.ru'        => 24622,
    'tumen.latitudo.ru'              => 24623,
    'omsk.latitudo.ru'               => 24624,
    'surgut.latitudo.ru'             => 24625,
    'tomsk.latitudo.ru'              => 24626,
    'barnaul.latitudo.ru'            => 24627,
    'volgodonsk.latitudo.ru'         => 24628,
    'novorossiysk.latitudo.ru'       => 24629,
    'kislovodsk.latitudo.ru'         => 24630,
    'pyatigorsk.latitudo.ru'         => 24631,
    'vladikavkaz.latitudo.ru'        => 24632,
    'kurgan.latitudo.ru'             => 24633,
    'kemerovo.latitudo.ru'           => 24634,
    'novokuznetsk.latitudo.ru'       => 24635,
    'izhevsk.latitudo.ru'            => 24636,
    'naberezhnye-chelny.latitudo.ru' => 24637,
    'nizhnekamsk.latitudo.ru'        => 24638,
    'maykop.latitudo.ru'             => 24639,
    'sterlitamak.latitudo.ru'        => 24640,
    'nizhniy-tagil.latitudo.ru'      => 24641,
    'orsk.latitudo.ru'               => 24642,
    'mahachkala.latitudo.ru'         => 24643,
    'grozny.latitudo.ru'             => 24644,
    'nalchik.latitudo.ru'            => 24645,
    'cherkessk.latitudo.ru'          => 24646,
    'armavir.latitudo.ru'            => 24647,
    'nevinnomissk.latitudo.ru'       => 24648,
    'donetsk.latitudo.ru'            => 27599,
    'lugansk.latitudo.ru'            => 27600,
    'mariupol.latitudo.ru'           => 27601,
    'melitopol.latitudo.ru'          => 27602,
    'berdyansk.latitudo.ru'          => 27603,
    'abkhazia.latitudo.ru'           => 27604,
];

// ----- НОВАЯ ФУНКЦИЯ ДЛЯ МАССОВОЙ ГЕНЕРАЦИИ -----
// Если передан параметр ALL — генерируем для всех доменов из карты
if (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === 'ALL') {
    echo "Начало массовой генерации sitemap для всех регионов...\n";
    foreach (array_keys($regionMap) as $domain) {
        echo "Генерация для домена: $domain\n";
        $_SERVER['HTTP_HOST'] = $domain;
        $host = $domain;
        
        // Генерируем индекс sitemap.xml
        $filename = 'sitemap.xml';
        $cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/sitemap_regions/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $cacheFile = $cacheDir . md5($domain . '_' . $filename) . '.xml';
        
        // Генерация индекса
        $originalIndexPath = $_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml';
        if (file_exists($originalIndexPath)) {
            $originalIndex = file_get_contents($originalIndexPath);
            $xml = simplexml_load_string($originalIndex);
            $newIndex = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>');
            
            foreach ($xml->sitemap as $sitemap) {
                $loc = (string)$sitemap->loc;
                $uri = new Uri($loc);
                $newLoc = 'https://' . $domain . $uri->getPath();
                $sitemapNode = $newIndex->addChild('sitemap');
                $sitemapNode->addChild('loc', htmlspecialchars($newLoc));
                $sitemapNode->addChild('lastmod', date('c'));
            }
            file_put_contents($cacheFile, $newIndex->asXML());
            echo "  - Индекс sitemap.xml сохранён\n";
        }
        
        // Генерируем карты для всех инфоблоков из индекса
        $indexContent = file_get_contents($originalIndexPath);
        $indexXml = simplexml_load_string($indexContent);
        foreach ($indexXml->sitemap as $sitemap) {
            $loc = (string)$sitemap->loc;
            $path = parse_url($loc, PHP_URL_PATH);
            $iblockFilename = basename($path);
            
            if (preg_match('/^sitemap-iblock-(\d+)\.xml$/', $iblockFilename, $matches)) {
                $iblockId = (int)$matches[1];
                $cacheFile = $cacheDir . md5($domain . '_' . $iblockFilename) . '.xml';
                
                // Если инфоблок в списке фильтруемых, генерируем с фильтром
                $filteredIblocks = [15, 17, 25];
                $regionPropertyCode = 'LINK_REGION';
                $regionId = $regionMap[$domain];
                
                if (in_array($iblockId, $filteredIblocks)) {
                    CModule::IncludeModule('iblock');
                    
                    $urls = [];
                    
                    // Разделы
                    $sections = CIBlockSection::GetList(
                        ['ID' => 'ASC'],
                        ['ACTIVE' => 'Y', 'IBLOCK_ID' => $iblockId],
                        false,
                        ['ID', 'SECTION_PAGE_URL', 'TIMESTAMP_X']
                    );
                    while ($section = $sections->GetNext()) {
                        $urls[] = [
                            'loc' => 'https://' . $domain . $section['SECTION_PAGE_URL'],
                            'lastmod' => date('c', MakeTimeStamp($section['TIMESTAMP_X'])),
                            'changefreq' => 'weekly',
                            'priority' => '0.8',
                        ];
                    }
                    
                    // Элементы с фильтром по региону
                    $elementFilter = [
                        'ACTIVE' => 'Y',
                        'IBLOCK_ID' => $iblockId,
                        [
                            'LOGIC' => 'OR',
                            ['PROPERTY_' . $regionPropertyCode => $regionId],
                            ['PROPERTY_' . $regionPropertyCode => false],
                        ]
                    ];
                    
                    $elements = CIBlockElement::GetList(
                        ['ID' => 'ASC'],
                        $elementFilter,
                        false,
                        false,
                        ['ID', 'DETAIL_PAGE_URL', 'TIMESTAMP_X']
                    );
                    while ($elem = $elements->GetNext()) {
                        $urls[] = [
                            'loc' => 'https://' . $domain . $elem['DETAIL_PAGE_URL'],
                            'lastmod' => date('c', MakeTimeStamp($elem['TIMESTAMP_X'])),
                            'changefreq' => 'weekly',
                            'priority' => '0.7',
                        ];
                    }
                    
                    // Создаём XML
                    $urlset = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
                    foreach ($urls as $url) {
                        $urlNode = $urlset->addChild('url');
                        $urlNode->addChild('loc', htmlspecialchars($url['loc']));
                        $urlNode->addChild('lastmod', $url['lastmod']);
                        $urlNode->addChild('changefreq', $url['changefreq']);
                        $urlNode->addChild('priority', $url['priority']);
                    }
                    file_put_contents($cacheFile, $urlset->asXML());
                    echo "  - $iblockFilename (с фильтром) сохранён\n";
                } else {
                    // Для остальных инфоблоков копируем оригинал с заменой домена
                    $originalFilePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $iblockFilename;
                    if (file_exists($originalFilePath)) {
                        $content = file_get_contents($originalFilePath);
                        $content = preg_replace('/(https?:\/\/)latitudo\.ru/', '$1' . $domain, $content);
                        file_put_contents($cacheFile, $content);
                        echo "  - $iblockFilename (без фильтра) сохранён\n";
                    }
                }
            }
        }
        echo "Генерация для домена $domain завершена\n";
    }
    echo "Массовая генерация для всех регионов завершена!\n";
    exit;
}
// ----- КОНЕЦ НОВОЙ ФУНКЦИИ -----

$regionId = isset($regionMap[$host]) ? $regionMap[$host] : 10039; // по умолчанию Москва

// ----- 2. Определяем, какой файл sitemap запрошен -----
$requestUri = $request->getRequestUri();
$path = parse_url($requestUri, PHP_URL_PATH);
$filename = basename($path); // например: sitemap.xml, sitemap-iblock-12.xml

// ----- 3. Настройки фильтрации -----
// Инфоблоки, которые нужно фильтровать по региону
$filteredIblocks = [15, 17, 25];
// Код свойства для фильтрации (используется только для фильтруемых инфоблоков)
$regionPropertyCode = 'LINK_REGION';

// ----- 4. Кеширование -----
$cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/sitemap_regions/';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}
$cacheFile = $cacheDir . md5($host . '_' . $filename) . '.xml';
$cacheLifetime = 86400; // 24 часа

/* Кеш годен, только если он не старше суток И НЕ СТАРШЕ ИСХОДНОГО ФАЙЛА.

   Вторая половина условия добавлена 4 сентября 2026. Без неё после ручной
   пересборки карт в админке все домены сутки продолжали отдавать предыдущую
   копию: исходные файлы обновились 4 сентября, а в кеше лежали слепки мартовских
   — Яндекс.Вебмастер видел карту полугодовой давности с адресами удалённых
   товаров и писал об ошибках в файлах Sitemap. Теперь пересборка подхватывается
   первым же запросом.

   Индекс sitemap.xml сверяем с ним же в корне: его правит та же генерация. */
$sourceFile = $_SERVER['DOCUMENT_ROOT'] . '/' . $filename;
$sourceTime = file_exists($sourceFile) ? filemtime($sourceFile) : 0;

if (
    file_exists($cacheFile)
    && (time() - filemtime($cacheFile) < $cacheLifetime)
    && filemtime($cacheFile) >= $sourceTime
) {
    header('Content-Type: application/xml; charset=UTF-8');
    readfile($cacheFile);
    exit;
}

// ----- 5. Вспомогательная функция для замены домена в XML-строке -----
function replaceDomainInXml($xmlContent, $newDomain) {
    // Заменяем все вхождения https://latitudo.ru на https://$newDomain
    // (аккуратно, чтобы не заменить внутри других ссылок)
    return preg_replace('/(https?:\/\/)latitudo\.ru/', '$1' . $newDomain, $xmlContent);
}

// ----- 6. Функция для генерации карты инфоблока с фильтром по региону -----
function generateFilteredIblockSitemap($iblockId, $host, $regionId, $propertyCode) {
    CModule::IncludeModule('iblock');

    // Фильтр для элементов: активные, принадлежат инфоблоку, и (регион = текущий ИЛИ свойство не заполнено)
    $elementFilter = [
        'ACTIVE' => 'Y',
        'IBLOCK_ID' => $iblockId,
        [
            'LOGIC' => 'OR',
            ['PROPERTY_' . $propertyCode => $regionId],   // элементы для этого региона
            ['PROPERTY_' . $propertyCode => false],       // общие элементы (без региона)
        ]
    ];

    // Если нужно добавлять разделы (оставим включено, при необходимости отключите)
    $includeSections = true;
    $urls = [];

    if ($includeSections) {
        $sections = CIBlockSection::GetList(
            ['ID' => 'ASC'],
            ['ACTIVE' => 'Y', 'IBLOCK_ID' => $iblockId],
            false,
            ['ID', 'SECTION_PAGE_URL', 'TIMESTAMP_X']
        );
        while ($section = $sections->GetNext()) {
            $urls[] = [
                'loc' => 'https://' . $host . $section['SECTION_PAGE_URL'],
                'lastmod' => date('c', MakeTimeStamp($section['TIMESTAMP_X'])),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }
    }

    $elements = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        $elementFilter,
        false,
        false,
        ['ID', 'DETAIL_PAGE_URL', 'TIMESTAMP_X']
    );
    while ($elem = $elements->GetNext()) {
        $urls[] = [
            'loc' => 'https://' . $host . $elem['DETAIL_PAGE_URL'],
            'lastmod' => date('c', MakeTimeStamp($elem['TIMESTAMP_X'])),
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ];
    }

    return $urls;
}

// ----- 7. Основная логика обработки запросов -----
ob_start(); // включаем буферизацию, чтобы потом сохранить в кеш

header('Content-Type: application/xml; charset=UTF-8');

// 7.1. Запрос индекса sitemap.xml
if ($filename === 'sitemap.xml') {
    // Читаем оригинальный индекс (из корня сайта)
    $originalIndexPath = $_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml';
    if (!file_exists($originalIndexPath)) {
        header('HTTP/1.0 404 Not Found');
        echo 'Sitemap index not found';
        exit;
    }
    $originalIndex = file_get_contents($originalIndexPath);

    // Парсим и создаём новый индекс с заменой домена
    $xml = simplexml_load_string($originalIndex);
    $newIndex = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>');

    foreach ($xml->sitemap as $sitemap) {
        $loc = (string)$sitemap->loc;
        $uri = new Uri($loc);
        $newLoc = 'https://' . $host . $uri->getPath();
        $sitemapNode = $newIndex->addChild('sitemap');
        $sitemapNode->addChild('loc', htmlspecialchars($newLoc));
        // Можно сохранить оригинальный lastmod, но для простоты ставим текущую дату
        $sitemapNode->addChild('lastmod', date('c'));
    }

    $content = $newIndex->asXML();
    echo $content;
}
// 7.2. Запрос к карте инфоблока sitemap-iblock-XX.xml
elseif (preg_match('/^sitemap-iblock-(\d+)\.xml$/', $filename, $matches)) {
    $iblockId = (int)$matches[1];

    // Если инфоблок в списке фильтруемых, генерируем кастомную карту
    if (in_array($iblockId, $filteredIblocks)) {
        $urls = generateFilteredIblockSitemap($iblockId, $host, $regionId, $regionPropertyCode);

        $urlset = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
        foreach ($urls as $url) {
            $urlNode = $urlset->addChild('url');
            $urlNode->addChild('loc', htmlspecialchars($url['loc']));
            $urlNode->addChild('lastmod', $url['lastmod']);
            $urlNode->addChild('changefreq', $url['changefreq']);
            $urlNode->addChild('priority', $url['priority']);
        }

        $content = $urlset->asXML();
        echo $content;
    }
    // Иначе отдаём оригинальный файл (из корня) с заменой домена
    else {
        $originalFilePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $filename;
        if (file_exists($originalFilePath)) {
            $content = file_get_contents($originalFilePath);
            // Заменяем домен на текущий (на случай, если в файле есть абсолютные ссылки)
            $content = replaceDomainInXml($content, $host);
            echo $content;
        } else {
            header('HTTP/1.0 404 Not Found');
            echo 'Sitemap not found';
            exit;
        }
    }
}
// 7.3. Запрос к другим sitemap-файлам (например, sitemap-files.xml)
else {
    $originalFilePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $filename;
    if (file_exists($originalFilePath)) {
        $content = file_get_contents($originalFilePath);
        // Заменяем домен на текущий
        $content = replaceDomainInXml($content, $host);
        echo $content;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo 'Sitemap not found';
        exit;
    }
}

// ----- 8. Сохраняем сгенерированный контент в кеш -----
$content = ob_get_clean(); // получаем вывод и очищаем буфер

/* Пишем через временный файл с переименованием: file_put_contents() пишет не
   атомарно, и параллельный запрос успевал прочитать кеш обрезанным — а такой
   обрезок жил бы в кеше сутки и отдавался бы роботам как «битый XML».
   Заодно не кешируем заведомо неполный ответ. */
$looksComplete = strpos($content, '</urlset>') !== false
    || strpos($content, '</sitemapindex>') !== false;

if (strlen($content) > 100 && $looksComplete) {
    $tmpFile = $cacheFile . '.' . getmypid() . '.tmp';
    if (file_put_contents($tmpFile, $content) !== false) {
        @rename($tmpFile, $cacheFile);
    }
}

echo $content; // отдаём пользователю