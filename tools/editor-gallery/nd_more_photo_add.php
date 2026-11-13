<?php
/**
 * Дописать картинки из папки в основную галерею товара (MORE_PHOTO) – после тех, что уже есть.
 * Пергола 34833, 8 фото шоу-рума (Ирина, 13.11.2026: «лучше в галерею», а не блоком редактора).
 *
 *   php nd_more_photo_add.php <DOCROOT> <ID> <папка>               – сухой прогон
 *   php nd_more_photo_add.php <DOCROOT> <ID> <папка> apply         – добавить (по имени файла по алфавиту)
 *   php nd_more_photo_add.php <DOCROOT> <ID> rollback=<added.json> – убрать добавленные
 */
$args = array_slice($argv, 1);
$root = array_shift($args);
$id = (int)array_shift($args);
$mode = 'dry';
$dir = '';
$rollbackFile = '';
foreach ($args as $a) {
	if ($a === 'apply') {
		$mode = 'apply';
	} elseif (strpos($a, 'rollback=') === 0) {
		$mode = 'rollback';
		$rollbackFile = substr($a, 9);
	} elseif (is_dir($a)) {
		$dir = rtrim($a, '/');
	} else {
		fwrite(STDERR, "Неизвестный параметр: $a\n");
		exit(1);
	}
}
if (!$root || !is_dir($root) || !$id || ($mode !== 'rollback' && !$dir)) {
	fwrite(STDERR, "php nd_more_photo_add.php <DOCROOT> <ID> <папка> [apply] | <DOCROOT> <ID> rollback=<файл>\n");
	exit(1);
}
$_SERVER['DOCUMENT_ROOT'] = realpath($root);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');

$el = CIBlockElement::GetList([], ['ID' => $id], false, false, ['ID', 'IBLOCK_ID', 'NAME'])->Fetch();
$ib = (int)$el['IBLOCK_ID'];
$have = []; // ID значения свойства => ID файла
$rs = CIBlockElement::GetProperty($ib, $id, ['sort' => 'asc', 'id' => 'asc'], ['CODE' => 'MORE_PHOTO']);
while ($p = $rs->Fetch()) {
	if ($p['VALUE']) {
		$have[(int)$p['PROPERTY_VALUE_ID']] = (int)$p['VALUE'];
	}
}

if ($mode === 'rollback') {
	$added = array_map('intval', (array)json_decode((string)file_get_contents($rollbackFile), true));
	$del = [];
	foreach ($have as $vid => $fid) {
		if (in_array($fid, $added, true)) {
			$del[$vid] = ['VALUE' => ['del' => 'Y']];
		}
	}
	if ($del) {
		CIBlockElement::SetPropertyValueCode($id, 'MORE_PHOTO', $del);
	}
	CIBlock::clearIblockTagCache($ib);
	echo 'Убрано из галереи: ' . count($del) . "\n";
	exit(0);
}

$files = glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
sort($files, SORT_NATURAL);
echo "$id {$el['NAME']}\nсейчас в галерее: " . count($have) . "\n";
foreach ($files as $f) {
	echo '  + ' . basename($f) . "\n";
}
if ($mode !== 'apply') {
	echo "Сухой прогон – ничего не изменено. Записать: добавьте apply\n";
	exit(0);
}
$new = [];
foreach ($files as $n => $f) {
	$new['n' . $n] = ['VALUE' => CFile::MakeFileArray($f)];
}
CIBlockElement::SetPropertyValueCode($id, 'MORE_PHOTO', $new);
$added = [];
$rs = CIBlockElement::GetProperty($ib, $id, ['sort' => 'asc', 'id' => 'asc'], ['CODE' => 'MORE_PHOTO']);
while ($p = $rs->Fetch()) {
	if ($p['VALUE'] && !isset($have[(int)$p['PROPERTY_VALUE_ID']])) {
		$added[] = (int)$p['VALUE'];
	}
}
$out = (getenv('HOME') ?: __DIR__) . '/nd_editor_gallery/' . date('Y-m-d_His') . '_more_' . $id;
mkdir($out, 0700, true);
file_put_contents($out . '/added.json', json_encode($added));
CIBlock::clearIblockTagCache($ib);
echo 'Добавлено: ' . count($added) . ', всего в галерее ' . (count($have) + count($added)) . "\nОткат: php " . __FILE__ . " $root $id rollback=$out/added.json\n";
