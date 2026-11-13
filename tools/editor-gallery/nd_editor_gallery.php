<?php
/**
 * Галерея блоком редактора (sprint.editor, свойство EDITOR1) у товара – из папки с картинками.
 * Первое применение: пергола 34833, фото с Яндекс.Диска (Ирина, 13.11.2026).
 *
 *   php nd_editor_gallery.php <DOCROOT> <ID товара> <папка с jpg>          – сухой прогон
 *   php nd_editor_gallery.php <DOCROOT> <ID товара> <папка с jpg> apply    – добавить блок «Галерея»
 *       в КОНЕЦ редактора (что там уже было – остаётся); картинки по имени файла по алфавиту
 *   php nd_editor_gallery.php <DOCROOT> <ID товара> rollback=<backup.json>  – вернуть редактор как был
 *       (загруженные файлы удаляются)
 *
 * Блок такой же, какой ставит редактор: {"name":"gallery","images":[{"file":{…CFile},"desc":""}]}.
 * Миниатюры делает шаблон блока (Gallery::getImages по ID файла), поэтому картинки заранее
 * ужимаем до ~2000 px, чтобы не хранить многомегабайтные PNG.
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
	fwrite(STDERR, "php nd_editor_gallery.php <DOCROOT> <ID> <папка> [apply] | <DOCROOT> <ID> rollback=<файл>\n");
	exit(1);
}
$_SERVER['DOCUMENT_ROOT'] = realpath($root);
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');

$el = CIBlockElement::GetList([], ['ID' => $id], false, false, ['ID', 'IBLOCK_ID', 'NAME'])->Fetch();
if (!$el) {
	fwrite(STDERR, "Нет товара $id\n");
	exit(1);
}
$ib = (int)$el['IBLOCK_ID'];
$cur = CIBlockElement::GetProperty($ib, $id, [], ['CODE' => 'EDITOR1'])->Fetch();
$curVal = is_array($cur['VALUE'] ?? null) ? (string)($cur['VALUE']['TEXT'] ?? '') : (string)($cur['VALUE'] ?? '');

if ($mode === 'rollback') {
	$bak = json_decode((string)file_get_contents($rollbackFile), true);
	CIBlockElement::SetPropertyValuesEx($id, $ib, ['EDITOR1' => $bak['before']]);
	foreach ((array)$bak['files'] as $f) {
		CFile::Delete((int)$f);
	}
	CIBlock::clearIblockTagCache($ib);
	echo "Редактор товара $id возвращён, удалено файлов: " . count((array)$bak['files']) . "\n";
	exit(0);
}

$files = glob($dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
sort($files, SORT_NATURAL);
echo "$id {$el['NAME']}\nредактор сейчас: " . ($curVal === '' ? 'пустой' : mb_strlen($curVal) . ' символов') . "\n";
foreach ($files as $f) {
	echo '  + ' . basename($f) . ', ' . round(filesize($f) / 1024) . " КБ\n";
}
if ($mode !== 'apply') {
	echo "Сухой прогон – ничего не изменено. Записать: добавьте apply\n";
	exit(0);
}

$out = (getenv('HOME') ?: __DIR__) . '/nd_editor_gallery/' . date('Y-m-d_His') . '_' . $id;
mkdir($out, 0700, true);
$bak = ['id' => $id, 'before' => $curVal, 'files' => []];
$images = [];
foreach ($files as $f) {
	$arr = CFile::MakeFileArray($f);
	$arr['MODULE_ID'] = 'sprint.editor';
	$fid = (int)CFile::SaveFile($arr, 'sprint.editor');
	if (!$fid) {
		echo '  ! не сохранился ' . basename($f) . "\n";
		continue;
	}
	$bak['files'][] = $fid;
	$fa = CFile::GetFileArray($fid);
	$fa['ORIGIN_SRC'] = $fa['SRC'];
	$images[] = ['file' => $fa, 'desc' => ''];
}
file_put_contents($out . '/backup.json', json_encode($bak, JSON_UNESCAPED_UNICODE));

$data = $curVal !== '' ? json_decode($curVal, true) : null;
if (!is_array($data) || empty($data['layouts'])) {
	$data = ['version' => 2, 'blocks' => [], 'layouts' => [['settings' => [], 'columns' => [['css' => '']]]]];
}
// в последний ряд раскладки, первая колонка: так же ставит блок сам редактор
$data['blocks'][] = ['images' => $images, 'name' => 'gallery', 'settings' => [], 'layout' => (count($data['layouts']) - 1) . ',0', 'meta' => []];
CIBlockElement::SetPropertyValuesEx($id, $ib, ['EDITOR1' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
CIBlock::clearIblockTagCache($ib);
echo 'Галерея добавлена: ' . count($images) . " фото\nОткат: php " . __FILE__ . " $root $id rollback=$out/backup.json\n";
