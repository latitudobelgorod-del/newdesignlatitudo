<?php
/**
 * Террапол, 12.11.2026: новые документы с terrapol.ru/company/docs/ и привязка к товарам бренда (1581).
 *  «Уход за ДПК Террапол» – террасная доска и ступени;
 *  «Схема скрытого крепления полнотелой доски и ступени Террапол» – полнотелая доска без паза и ступени;
 *  схема заглушки ступени (уже есть, 34830) – ступени.
 * Документы добавляются в конец INSTRUCTIONS_FILE, существующие не трогаются. Журнал – <папка>/docs2_before.json.
 *   php nd_terrapol_docs2.php <DOCROOT> <папка с pdf> [--apply | rollback]
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
$dir = rtrim($argv[2] ?? '', '/');
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');
$mode = $argv[3] ?? '';
$log = "$dir/docs2_before.json";
const DOCS = [
	'care' => ['Уход за ДПК Террапол', 'ukhod-za-dpk-terrapol.pdf'],
	'hidden' => ['Схема скрытого крепления полнотелой доски и ступени Террапол', 'skhema-skrytogo-krepleniya-terrapol.pdf'],
];
const CAP = 34830;

if ($mode === 'rollback') {
	$b = json_decode(file_get_contents($log), true);
	foreach ($b['products'] as $id => $vals) {
		CIBlockElement::SetPropertyValuesEx((int)$id, 19, ['INSTRUCTIONS_FILE' => $vals ?: false]);
	}
	foreach ($b['docs'] as $d) {
		CIBlockElement::Delete($d);
	}
	CIBlock::clearIblockTagCache(19);
	CIBlock::clearIblockTagCache(13);
	unlink($log);
	exit('возвращено товаров ' . count($b['products']) . "\n");
}
$vals = function ($id) {
	$v = [];
	$r = CIBlockElement::GetProperty(19, $id, ['sort' => 'asc'], ['CODE' => 'INSTRUCTIONS_FILE']);
	while ($x = $r->Fetch()) {
		if ($x['VALUE']) {
			$v[] = (int)$x['VALUE'];
		}
	}
	return $v;
};
// что к чему цеплять
$plan = [];
$r = CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => 19, 'PROPERTY_BRAND' => 1581], false, false, ['ID', 'NAME', 'ACTIVE']);
while ($e = $r->Fetch()) {
	$n = mb_strtolower($e['NAME']);
	$add = [];
	$board = mb_strpos($n, 'террасная доска') === 0;
	$step = mb_strpos($n, 'ступень') === 0;
	if ($board || $step) {
		$add[] = 'care';
	}
	if (($board && mb_strpos($n, 'без паза') !== false) || $step) {   // скрытое крепление – через тело доски, у доски с пазом клипсы
		$add[] = 'hidden';
	}
	if ($step) {
		$add[] = 'cap';
	}
	if ($add) {
		$plan[$e['ID']] = ['name' => $e['NAME'], 'active' => $e['ACTIVE'], 'add' => $add, 'cur' => $vals($e['ID'])];
	}
}
$cnt = [];
foreach ($plan as $id => $p) {
	foreach ($p['add'] as $a) {
		$cnt[$a] = ($cnt[$a] ?? 0) + 1;
	}
	echo sprintf("%6d %s %-80s + %s\n", $id, $p['active'], mb_substr($p['name'], 0, 80), implode(', ', $p['add']));
}
echo json_encode($cnt) . "\n";
if ($mode !== '--apply') {
	exit("Прогон без записи. Для записи добавьте --apply\n");
}
if (is_file($log)) {
	exit("уже выполнено – сначала rollback\n");
}
$ref = CIBlockElement::GetList([], ['ID' => 25310], false, false, ['ID', 'IBLOCK_SECTION_ID', 'SORT'])->Fetch();
$en = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => 13, 'CODE' => 'MONTAJ_INSTR', 'XML_ID' => 'Y'])->Fetch();
$el = new CIBlockElement();
$ids = ['cap' => CAP];
foreach (DOCS as $k => [$name, $file]) {
	$id = (int)$el->Add([
		'IBLOCK_ID' => 13, 'IBLOCK_SECTION_ID' => $ref['IBLOCK_SECTION_ID'], 'ACTIVE' => 'Y', 'NAME' => $name, 'SORT' => $ref['SORT'],
		'CODE' => CUtil::translit($name, 'ru', ['replace_space' => '-', 'replace_other' => '-']),
		'PROPERTY_VALUES' => ['INSTRUCTIONS' => CFile::MakeFileArray("$dir/$file"), 'BRAND' => 1581, 'MONTAJ_INSTR' => $en['ID']],
	]);
	if (!$id) {
		exit("документ $name: " . $el->LAST_ERROR . "\n");
	}
	$ids[$k] = $id;
	echo "документ $id $name\n";
}
file_put_contents($log, json_encode(['docs' => [$ids['care'], $ids['hidden']], 'products' => array_map(function ($p) {
	return $p['cur'];
}, $plan)]));
foreach ($plan as $id => $p) {
	$new = $p['cur'];
	foreach ($p['add'] as $a) {
		if (!in_array($ids[$a], $new)) {
			$new[] = $ids[$a];
		}
	}
	CIBlockElement::SetPropertyValuesEx($id, 19, ['INSTRUCTIONS_FILE' => $new]);
}
CIBlock::clearIblockTagCache(19);
CIBlock::clearIblockTagCache(13);
echo 'готово, товаров ' . count($plan) . "\n";
