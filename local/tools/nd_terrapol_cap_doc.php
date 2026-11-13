<?php
/**
 * Террапол, 13.11.2026: документ 34830 (схема заглушки ступени, файл Террапола с 12.11) – название с маркой,
 * привязка BRAND к Терраполу (1581) и раздел ИБ 13 «Террапол» (585). Стоит только у ступеней Террапол.
 * Прежние значения – <папка>/cap_doc_before.json.
 *   php nd_terrapol_cap_doc.php <DOCROOT> <папка> [--apply | rollback]
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
$dir = rtrim($argv[2] ?? '', '/');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');
const ID = 34830;
const NAME = 'Схема крепления торцевой заглушки ступени Террапол';
const BRAND = 1581;
const SECTION = 585;
$mode = $argv[3] ?? '';
$log = "$dir/cap_doc_before.json";

$clear = function () {
	CIBlock::clearIblockTagCache(13);
	CIBlock::clearIblockTagCache(12);
	BXClearCache(true, '/nd_brand_anchors/');
};

if ($mode === 'rollback') {
	$b = json_decode(file_get_contents($log), true);
	(new CIBlockElement())->Update(ID, ['NAME' => $b['name'], 'IBLOCK_SECTION' => $b['sections']]);
	CIBlockElement::SetPropertyValuesEx(ID, 13, ['BRAND' => $b['brand'] ?: false]);
	$clear();
	unlink($log);
	exit("возвращено\n");
}

$e = CIBlockElement::GetByID(ID)->Fetch();
$sections = [];
$r = CIBlockElement::GetElementGroups(ID, true, ['ID']);
while ($s = $r->Fetch()) {
	$sections[] = (int)$s['ID'];
}
$brand = [];
$r = CIBlockElement::GetProperty(13, ID, [], ['CODE' => 'BRAND']);
while ($p = $r->Fetch()) {
	if ($p['VALUE']) {
		$brand[] = (int)$p['VALUE'];
	}
}
echo 'было: ', $e['NAME'], ' | разделы ', implode(',', $sections), ' | BRAND ', implode(',', $brand), "\n";
echo 'станет: ', NAME, ' | раздел ', SECTION, ' | BRAND ', BRAND, "\n";
if ($mode !== '--apply') {
	exit("прогон без записи\n");
}
if (file_exists($log)) {
	exit("журнал уже есть: $log\n");
}
file_put_contents($log, json_encode(['name' => $e['NAME'], 'sections' => $sections, 'brand' => $brand], JSON_UNESCAPED_UNICODE));
$el = new CIBlockElement();
if (!$el->Update(ID, ['NAME' => NAME, 'IBLOCK_SECTION' => [SECTION]])) {
	exit('ошибка: ' . $el->LAST_ERROR . "\n");
}
CIBlockElement::SetPropertyValuesEx(ID, 13, ['BRAND' => [BRAND]]);
$clear();
echo "готово\n";
