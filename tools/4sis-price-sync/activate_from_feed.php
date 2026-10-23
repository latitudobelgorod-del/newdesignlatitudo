<?php
/**
 * Включить выключенные товары 4SIS, которые снова есть в фиде поставщика (появились в наличии / под заказ).
 *
 *   php activate_from_feed.php --feed=<файл>          — сухой прогон: кого включили бы
 *   php activate_from_feed.php --feed=<файл> apply    — включить; список включённых — в ~/4sis-sync/<дата>_activate/activated.txt
 *
 * Товар сопоставляется с фидом так же, как в sync_4sis.php: по коду 1С (XML_ID), иначе по «Артикулу
 * производителя», если он в фиде у одного товара. Включаем, только если в фиде есть цена.
 * Выключать (когда товар пропал из фида) — не выключаем: фид у 4SIS неполный.
 */

$args = array_slice($argv, 1);
$feedFile = '';
$apply = false;
foreach ($args as $a) {
	if ($a === 'apply') {
		$apply = true;
	} elseif (strpos($a, '--feed=') === 0) {
		$feedFile = substr($a, 7);
	}
}

$_SERVER['DOCUMENT_ROOT'] = getenv('HOME') . '/www/latitudo.ru';
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');

$doc = $feedFile ? @simplexml_load_file($feedFile) : false;
if (!$doc || count($doc->shop->offers->offer) < 500) {
	fwrite(STDERR, "Фид не разобрался или в нём мало товаров\n");
	exit(1);
}
$feed = [];
$byVendor = [];
foreach ($doc->shop->offers->offer as $o) {
	$regular = max((float)$o->price, (float)$o->oldprice);
	$feed[(string)$o['id']] = $regular;
	$byVendor[trim((string)$o->vendorCode)][] = (string)$o['id'];
}

$list = [];
$rs = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => 19, 'PROPERTY_BRAND' => 28619, 'ACTIVE' => 'N'], false, false, ['ID', 'NAME', 'XML_ID']);
while ($r = $rs->Fetch()) {
	$code = isset($feed[$r['XML_ID']]) ? $r['XML_ID'] : '';
	if ($code === '') {
		$pr = CIBlockElement::GetProperty(19, $r['ID'], [], ['CODE' => 'CML2_ATTRIBUTES']);
		while ($p = $pr->Fetch()) {
			$v = trim((string)$p['VALUE']);
			if (trim((string)$p['DESCRIPTION']) === 'Артикул производителя' && $v !== '' && $v !== 'б/а' && count($byVendor[$v] ?? []) === 1) {
				$code = $byVendor[$v][0];
			}
		}
	}
	if ($code !== '' && $feed[$code] > 0) {
		$list[(int)$r['ID']] = $r['NAME'];
	}
}

echo 'Выключенных товаров 4SIS, которые есть в фиде с ценой: ' . count($list) . "\n";
foreach ($list as $id => $name) {
	echo "  $id $name\n";
}
if (!$apply || !$list) {
	exit(0);
}
$dir = getenv('HOME') . '/4sis-sync/' . date('Y-m-d_His') . '_activate';
mkdir($dir, 0700, true);
$el = new CIBlockElement();
$ok = [];
foreach ($list as $id => $name) {
	if ($el->Update($id, ['ACTIVE' => 'Y'])) {
		$ok[] = $id;
	} else {
		fwrite(STDERR, "$id: {$el->LAST_ERROR}\n");
	}
}
file_put_contents($dir . '/activated.txt', implode(',', $ok));
CIBlock::clearIblockTagCache(19);
echo 'Включено: ' . count($ok) . "; выключить обратно: php ~/4sis-sync/nd_activate.php N " . implode(',', $ok) . "\n";
