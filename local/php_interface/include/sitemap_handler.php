<?php
// sitemap_handler.php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Context;
use Bitrix\Main\Web\Uri;

// ----- 1. Определяем текущий домен (регион) -----
$request = Context::getCurrent()->getRequest();
$host = $request->getHttpHost();
$host = parse_url('http://' . $host, PHP_URL_HOST); // очищаем от порта

// Карта соответствия доменов и ID регионов (замените на свои данные)
// Если у вас динамическое определение, можно получать ID из кода региона по домену
$regionMap = [
   'latitudo.ru'  => 10039,  // ID региона Москва
    'belgorod.latitudo.ru'  => 9277,  // ID региона Питер
    'krasnodar.latitudo.ru'      => 9568,  // Основной домен (по умолчанию Москва)
    // ... остальные
];
$regionId = isset($regionMap[$host]) ? $regionMap[$host] : 1; // по умолчанию 1

// ----- 2. Определяем, какой файл sitemap запрошен -----
$requestUri = $request->getRequestUri();
$path = parse_url($requestUri, PHP_URL_PATH);
$filename = basename($path); // например: sitemap.xml, sitemap-iblock-12.xml

// ----- 3. Функция для генерации карты инфоблока с фильтром по региону -----
function generateIblockSitemap($iblockId, $host, $regionId) {
    CModule::IncludeModule('iblock');

    $regionPropertyCode = 'LINK_REGION'; // ЗАМЕНИТЕ НА ВАШ КОД СВОЙСТВА

    // Фильтр для элементов
    $elementFilter = [
        'ACTIVE' => 'Y',
        'IBLOCK_ID' => $iblockId,
        [
            'LOGIC' => 'OR',
            ['PROPERTY_' . $regionPropertyCode => $regionId],   // элементы для этого региона
            ['PROPERTY_' . $regionPropertyCode => false],       // общие элементы (без региона)
        ]
    ];

    // Фильтр для разделов (если они должны быть в карте)
    $sectionFilter = [
        'ACTIVE' => 'Y',
        'IBLOCK_ID' => $iblockId,
        // У разделов тоже может быть привязка к регионам? Обычно нет, но если есть - добавьте аналогично.
    ];

    $urls = [];

    // Добавляем разделы (если нужно)
    $sections = CIBlockSection::GetList(
        ['ID' => 'ASC'],
        $sectionFilter,
        false,
        ['ID', 'SECTION_PAGE_URL', 'TIMESTAMP_X']
    );
    while ($section = $sections->GetNext()) {
        $urls[] = [
            'loc' => 'https://' . $host . $section['SECTION_PAGE_URL'],
            'lastmod' => date('c', MakeTimeStamp($section['TIMESTAMP_X'])),
            'priority' => '0.8',
        ];
    }

    // Добавляем элементы
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
            'priority' => '0.7',
        ];
    }

    return $urls;
}

// ----- 4. Обработка запросов -----
header('Content-Type: application/xml; charset=UTF-8');

if ($filename === 'sitemap.xml') {
    // Генерируем индекс, взяв за основу оригинальный индекс основного домена
    // Скачиваем оригинальный индекс latitudo.ru/sitemap.xml
    $originalIndex = file_get_contents('https://latitudo.ru/sitemap.xml');
    if (!$originalIndex) {
        // Если не удалось получить, можно сгенерировать вручную, но для простоты возьмём локальный файл
        $originalIndex = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/sitemap.xml');
    }

    // Парсим оригинальный индекс, извлекаем все <loc> для карт инфоблоков
    $xml = new SimpleXMLElement($originalIndex);
    $newIndex = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>');

    foreach ($xml->sitemap as $sitemap) {
        $loc = (string)$sitemap->loc;
        // Заменяем домен на текущий
        $uri = new Uri($loc);
        $newLoc = 'https://' . $host . $uri->getPath();
        $sitemapNode = $newIndex->addChild('sitemap');
        $sitemapNode->addChild('loc', htmlspecialchars($newLoc));
        $sitemapNode->addChild('lastmod', date('c'));
    }

    echo $newIndex->asXML();
    exit;
}

elseif (preg_match('/^sitemap-iblock-(\d+)\.xml$/', $filename, $matches)) {
    $iblockId = (int)$matches[1];

    // Получаем URL-ы для этого инфоблока с учётом региона
    $urls = generateIblockSitemap($iblockId, $host, $regionId);

    // Генерируем XML
    $urlset = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
    foreach ($urls as $url) {
        $urlNode = $urlset->addChild('url');
        $urlNode->addChild('loc', htmlspecialchars($url['loc']));
        $urlNode->addChild('lastmod', $url['lastmod']);
        $urlNode->addChild('priority', $url['priority']);
    }

    echo $urlset->asXML();
    exit;
}

else {
    // Для других файлов (sitemap-files.xml и т.п.) можно отдать оригинал или 404
    // Пробуем отдать файл из корня, если он есть
    $localFile = $_SERVER['DOCUMENT_ROOT'] . '/' . $filename;
    if (file_exists($localFile)) {
        readfile($localFile);
        exit;
    } else {
        header('HTTP/1.0 404 Not Found');
        echo 'Sitemap not found';
        exit;
    }
}