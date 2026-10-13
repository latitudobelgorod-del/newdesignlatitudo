<?php
/**
 * Свойства «Линейка» (LINE) и «Диапазон высоты, мм» (HEIGHT_RANGE) в каталоге (ИБ 19)
 * для блока высот на карточке опоры (ТЗ сеошников, п. 3.4, 13.10.2026).
 * Карточка выводит соседей по LINE кнопками с подписью HEIGHT_RANGE; минимальную
 * высоту для сортировки берёт из HEIGHT_RANGE (первое число), отдельного свойства нет.
 *
 *   php local/tools/nd_line_props.php <DOCUMENT_ROOT> [--dry-run]
 * Повторный запуск ничего не дублирует: существующие свойства и значения пропускает.
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
$dry = in_array('--dry-run', $argv, true);
if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/bitrix')) {
	exit("usage: php nd_line_props.php <DOCUMENT_ROOT> [--dry-run]\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

const ND_IB = 19;
$lines = [
	'HILST_LIFT_SL' => 'HILST ЛИФТ с автокоррекцией уклона',
	'HILST_LIFT_F'  => 'HILST ЛИФТ противопожарные',
	'LEVEL_3D'      => 'Level 3Д',
	'LEVEL_PRO'     => 'Level ПРО',
	'LEVEL_MUFTA'   => 'Удлинительная муфта Level', // кнопки — размер, подпись блока «Размер»
];

$prop = new CIBlockProperty;

$p = CIBlockProperty::GetList([], ['IBLOCK_ID' => ND_IB, 'CODE' => 'LINE'])->Fetch();
if ($p) {
	echo "LINE уже есть (ID {$p['ID']})\n";
	$lineId = $p['ID'];
} elseif ($dry) {
	echo "LINE: будет создано\n";
	$lineId = 0;
} else {
	$lineId = $prop->Add([
		'IBLOCK_ID' => ND_IB, 'CODE' => 'LINE', 'NAME' => 'Линейка',
		'PROPERTY_TYPE' => 'L', 'LIST_TYPE' => 'L', 'MULTIPLE' => 'N',
		'ACTIVE' => 'Y', 'SORT' => 5000, 'FILTRABLE' => 'N', 'SEARCHABLE' => 'N',
		'HINT' => 'Товары одной линейки показываются на карточке кнопками «Диапазон высоты, мм»',
	]) or exit("ошибка LINE: {$prop->LAST_ERROR}\n");
	echo "LINE создано (ID $lineId)\n";
}

if ($lineId) {
	$have = [];
	$r = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $lineId]);
	while ($e = $r->Fetch()) {
		$have[$e['XML_ID']] = $e['ID'];
	}
	$sort = 100;
	foreach ($lines as $xml => $name) {
		if (isset($have[$xml])) {
			echo "  значение $xml уже есть\n";
		} elseif ($dry) {
			echo "  значение $xml «{$name}»: будет создано\n";
		} else {
			(new CIBlockPropertyEnum)->Add(['PROPERTY_ID' => $lineId, 'VALUE' => $name, 'XML_ID' => $xml, 'SORT' => $sort]);
			echo "  значение $xml «{$name}» создано\n";
		}
		$sort += 100;
	}
}

$p = CIBlockProperty::GetList([], ['IBLOCK_ID' => ND_IB, 'CODE' => 'HEIGHT_RANGE'])->Fetch();
if ($p) {
	echo "HEIGHT_RANGE уже есть (ID {$p['ID']})\n";
} elseif ($dry) {
	echo "HEIGHT_RANGE: будет создано\n";
} else {
	$id = $prop->Add([
		'IBLOCK_ID' => ND_IB, 'CODE' => 'HEIGHT_RANGE', 'NAME' => 'Диапазон высоты, мм',
		'PROPERTY_TYPE' => 'S', 'MULTIPLE' => 'N', 'ACTIVE' => 'Y', 'SORT' => 5010,
		'FILTRABLE' => 'N', 'SEARCHABLE' => 'N',
		'HINT' => 'Через дефис без пробелов, например 35-50. Подпись кнопки в блоке линейки',
	]) or exit("ошибка HEIGHT_RANGE: {$prop->LAST_ERROR}\n");
	echo "HEIGHT_RANGE создано (ID $id)\n";
}
