<?php
/**
 * Разовая заливка приоритета марок по всему каталогу.
 *
 * Запускать из консоли, из корня сайта:
 *     php local/tools/nd_brand_weight_fill.php
 *
 * Скрипт создаёт служебное свойство ND_BRAND_WEIGHT (если его ещё нет) и
 * проставляет его каждому товару: EasyDecking — 1, LATITUDO — 2, остальные — 3.
 * По этому свойству сортируется выдача поиска, см. комментарии в
 * local/php_interface/include/latitudo_brand_weight.php.
 *
 * Дальше вес держит в актуальном состоянии обработчик из local/init.php, так
 * что повторно скрипт нужен только при смене состава марок в списке весов.
 *
 * Через веб не запускается: заливка идёт минуты и не должна висеть на
 * посетителе или уехать по таймауту.
 */

if (PHP_SAPI !== 'cli') {
    die('Только из консоли: php local/tools/nd_brand_weight_fill.php'.PHP_EOL);
}

$_SERVER['DOCUMENT_ROOT'] = dirname(dirname(__DIR__));
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';

if (!CModule::IncludeModule('iblock')) {
    die('Модуль iblock не подключился'.PHP_EOL);
}

require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/latitudo_brand_weight.php';

$propId = LatitudoBrandWeight::propertyId();
if (!$propId) {
    die('Не удалось создать свойство '.LatitudoBrandWeight::CODE.PHP_EOL);
}
echo 'Свойство '.LatitudoBrandWeight::CODE.': ID '.$propId.PHP_EOL;

$start = microtime(true);
$count = LatitudoBrandWeight::fillAll();

echo 'Обработано товаров: '.$count.PHP_EOL;
echo 'Время: '.round(microtime(true) - $start, 1).' с'.PHP_EOL;

/* Сводка — чтобы сразу видеть, что веса разошлись как задумано. */
global $DB;
$rs = $DB->Query(
    'SELECT p.VALUE_NUM w, COUNT(*) c FROM b_iblock_element_property p '
    .'WHERE p.IBLOCK_PROPERTY_ID = '.$propId.' GROUP BY p.VALUE_NUM ORDER BY w'
);
while ($row = $rs->Fetch()) {
    echo '  вес '.(int)$row['w'].': '.$row['c'].' товаров'.PHP_EOL;
}
