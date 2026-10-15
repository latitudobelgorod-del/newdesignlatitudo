<?php
/**
 * Millargo: переключатель цветов для групп, которые Ирина называет вручную
 * (15.10.2026) — их нет в файле с цветами, и nd_millargo_colors.php их не брал.
 * Группа = одна модель (тип + коллекция) в разных цветах ротанга:
 *   ROTANG_COLOR — цвет элемента (нет значения в списке — добавляется);
 *   COLOR_MAIN_EL — свотч millargo_<xml>, если он есть в справочнике HL 9,
 *       иначе пусто (кружок — фото товара);
 *   ASSOCIATED — по элементу на цвет: у цвета с несколькими тканями основной —
 *       меньший ID, а в собственном списке элемента — он сам.
 *
 *   php nd_millargo_manual_groups.php <DOCROOT> [--apply]
 *   php nd_millargo_manual_groups.php <DOCROOT> rollback <файл бэкапа>
 * Без --apply — только показывает. Бэкап — в домашнюю папку.
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/bitrix')) {
	exit("usage: см. шапку файла\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');
CModule::IncludeModule('highloadblock');

const ND_IB = 19;
const ND_HL = 9;
const ND_PROPS = ['ASSOCIATED', 'COLOR_MAIN_EL', 'ROTANG_COLOR'];
$home = getenv('HOME') ?: dirname($_SERVER['DOCUMENT_ROOT']);

/* Новые цвета вне справочника: название => XML_ID значения списка. */
const ND_NEW_COLORS = [
	'Грецкий орех' => 'walnut',
	'Цитрус'       => 'citrus',
];

/* Группы: ID элемента => цвет ротанга. */
const ND_GROUPS = [
	'Кресло ПРИМО' => [
		33237 => 'Американский орех',
		33238 => 'Американский орех',
		33239 => 'Индийское дерево',
		33240 => 'Индийское дерево',
		33241 => 'Лунный свет',
		33242 => 'Лунный свет',
		33243 => 'Млечный путь',
		33245 => 'Цитрус',
		33246 => 'Цитрус',
		33247 => 'Шоколад металлик',
		34190 => 'Сицилия',
	],
	'Комплект ЛОТУС 1' => [
		33300 => 'Африканский закат',
		33304 => 'Черный металлик',
	],
];

function ndVals(int $id): array
{
	$o = [];
	foreach (ND_PROPS as $code) {
		$v = [];
		$r = CIBlockElement::GetProperty(ND_IB, $id, [], ['CODE' => $code]);
		while ($p = $r->Fetch()) {
			if ($p['VALUE'] !== null && $p['VALUE'] !== '' && $p['VALUE'] !== false) {
				$v[] = $p['VALUE'];
			}
		}
		$o[$code] = $v;
	}
	return $o;
}

$prop = CIBlockProperty::GetList([], ['IBLOCK_ID' => ND_IB, 'CODE' => 'ROTANG_COLOR'])->Fetch();

if (($argv[2] ?? '') === 'rollback') {
	$bk = json_decode(file_get_contents($argv[3]), true);
	foreach ($bk['elements'] as $id => $vals) {
		$set = [];
		foreach ($vals as $code => $v) {
			$set[$code] = $v ? ($code === 'ASSOCIATED' ? $v : $v[0]) : false;
		}
		CIBlockElement::SetPropertyValuesEx((int)$id, ND_IB, $set);
	}
	foreach ($bk['created_enum'] as $enumId) {
		CIBlockPropertyEnum::Delete((int)$enumId);
	}
	CIBlock::clearIblockTagCache(ND_IB);
	echo 'откат: ' . count($bk['elements']) . ' элементов, значений списка ' . count($bk['created_enum']) . "\n";
	exit;
}

$apply = in_array('--apply', $argv, true);

$enum = [];
$r = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $prop['ID']]);
while ($x = $r->Fetch()) {
	$enum[$x['VALUE']] = ['ID' => (int)$x['ID'], 'XML_ID' => $x['XML_ID']];
}
$hlClass = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity(
	\Bitrix\Highloadblock\HighloadBlockTable::getById(ND_HL)->fetch()
)->getDataClass();
$swatches = [];
$r = $hlClass::getList(['filter' => ['%UF_XML_ID' => 'millargo_'], 'select' => ['UF_XML_ID']]);
while ($x = $r->fetch()) {
	$swatches[$x['UF_XML_ID']] = true;
}

$backup = ['elements' => [], 'created_enum' => []];
$plan = [];
foreach (ND_GROUPS as $groupName => $items) {
	// основной элемент цвета — меньший ID
	$main = [];
	foreach ($items as $id => $color) {
		if (!isset($main[$color]) || $id < $main[$color]) {
			$main[$color] = $id;
		}
	}
	foreach ($items as $id => $color) {
		$e = CIBlockElement::GetByID($id)->Fetch();
		if (!$e || (int)$e['IBLOCK_ID'] !== ND_IB) {
			exit("нет элемента $id\n");
		}
		$assoc = [];
		foreach ($main as $c => $mainId) {
			$assoc[] = $c === $color ? $id : $mainId;
		}
		$plan[$id] = ['color' => $color, 'assoc' => $assoc];
		echo "$groupName | $id {$e['NAME']} | $color | вариации " . implode(',', $assoc) . "\n";
	}
}
if (!$apply) {
	echo "Прогон без записи. Для записи добавьте --apply\n";
	exit;
}

foreach ($plan as $id => $p) {
	$backup['elements'][$id] = ndVals($id);
}
foreach (ND_NEW_COLORS as $name => $xml) {
	if (!isset($enum[$name])) {
		$newId = (int)(new CIBlockPropertyEnum)->Add(['PROPERTY_ID' => $prop['ID'], 'VALUE' => $name, 'XML_ID' => $xml, 'SORT' => 200]);
		$enum[$name] = ['ID' => $newId, 'XML_ID' => $xml];
		$backup['created_enum'][] = $newId;
		echo "добавлено значение «{$name}» = $newId\n";
	}
}
$bkFile = "$home/nd_millargo_groups_backup_" . date('Ymd-His') . '.json';
file_put_contents($bkFile, json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "бэкап: $bkFile\n";

foreach ($plan as $id => $p) {
	$sw = 'millargo_' . $enum[$p['color']]['XML_ID'];
	CIBlockElement::SetPropertyValuesEx($id, ND_IB, [
		'ROTANG_COLOR'  => $enum[$p['color']]['ID'],
		'COLOR_MAIN_EL' => isset($swatches[$sw]) ? $sw : false,
		'ASSOCIATED'    => $p['assoc'],
	]);
}
// SetPropertyValuesEx кеш инфоблока не сбрасывает
CIBlock::clearIblockTagCache(ND_IB);
echo 'готово: ' . count($plan) . " элементов\n";
