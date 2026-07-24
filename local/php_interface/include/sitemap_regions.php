<?php
// sitemap_regions.php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Context;
use Bitrix\Iblock\ElementTable;

// ----- 1. Определяем текущий регион (поддомен) -----
$request = Context::getCurrent()->getRequest();
$host = $request->getHttpHost(); // получили spb.site.ru

// Избавляемся от порта, если есть
$host = parse_url('http://' . $host, PHP_URL_HOST);

// Замените на ваш способ получения ID региона по домену
// Обычно в Аспро это хранится в настройках, но для простоты сделаем массив
$regionMap = [
    'latitudo.ru'  => 10039,  // ID региона Москва
    'belgorod.latitudo.ru'  => 9277,  // ID региона Питер
    'krasnodar.latitudo.ru'      => 9568,  // Основной домен (по умолчанию Москва)
];

$regionId = isset($regionMap[$host]) ? $regionMap[$host] : 1;

// ----- 2. Формируем фильтр для элементов -----
// Внимание! Узнайте точный код свойства регионов в вашей установке.
// Это может быть PROPERTY_REGIONS, PROPERTY_CITY, или другое.
$regionPropertyCode = 'REGIONS'; // ИСПРАВЬТЕ НА ВАШ КОД

$filter = [
    'ACTIVE' => 'Y',
    'IBLOCK_ID' => [15, 17, 25], // ID ваших инфоблоков
    [
        'LOGIC' => 'OR',
        ['LINK_REGION' . $regionPropertyCode => $regionId],          // товары для этого города
        ['LINK_REGION' . $regionPropertyCode => false],              // общие товары (без региона)
    ]
];

// ----- 3. Получаем элементы и формируем XML -----
$items = [];
$elems = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    $filter,
    false,
    false,
    ['ID', 'DETAIL_PAGE_URL', 'TIMESTAMP_X']
);

while ($elem = $elems->GetNext()) {
    $lastmod = date('c', MakeTimeStamp($elem['TIMESTAMP_X']));
    $items[] = [
        'loc' => 'https://' . $host . $elem['DETAIL_PAGE_URL'],
        'lastmod' => $lastmod,
        'priority' => '0.7', // приоритет для товаров
    ];
}

// ----- 4. Добавляем разделы каталога (если нужно) -----
// Аналогично можно получить разделы инфоблоков и добавить их в $items

// ----- 5. Генерируем XML и сохраняем в папку Аспро -----
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

foreach ($items as $item) {
    $xml .= '<url>' . PHP_EOL;
    $xml .= '  <loc>' . htmlspecialchars($item['loc']) . '</loc>' . PHP_EOL;
    $xml .= '  <lastmod>' . $item['lastmod'] . '</lastmod>' . PHP_EOL;
    $xml .= '  <priority>' . $item['priority'] . '</priority>' . PHP_EOL;
    $xml .= '</url>' . PHP_EOL;
}

$xml .= '</urlset>';

// Сохраняем в файл, используя структуру Аспро
$sitemapDir = $_SERVER['DOCUMENT_ROOT'] . '/aspro_regions/sitemap/';
if (!is_dir($sitemapDir)) {
    mkdir($sitemapDir, 0755, true);
}

$filename = 'sitemap_' . str_replace('.', '_', $host) . '.xml';
file_put_contents($sitemapDir . $filename, $xml);

// Отдаём как XML, если нужно сразу показать
header('Content-Type: application/xml; charset=UTF-8');
echo $xml;