<?php
/**
 * Заполнение «Линейки» (LINE) и «Диапазона высоты, мм» (HEIGHT_RANGE) у опор по названию.
 * Пишет только пустые поля. Бэкап прежних значений — рядом с DOCUMENT_ROOT (вне www).
 *
 *   php local/tools/nd_line_fill.php <DOCUMENT_ROOT> <LINE_XML_ID|all> [--apply]
 *   php local/tools/nd_line_fill.php <DOCUMENT_ROOT> rollback <файл бэкапа>
 * Без --apply только показывает, что будет записано.
 */
$_SERVER['DOCUMENT_ROOT'] = rtrim($argv[1] ?? '', '/');
if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/bitrix')) {
	exit("usage: php nd_line_fill.php <DOCUMENT_ROOT> <LINE_XML_ID|all|rollback> [--apply|файл]\n");
}
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

const ND_IB = 19;
// линейка → раздел и шаблон названия; диапазон — первая группа «мин-макс»
$rules = [
	'HILST_LIFT_SL' => [335, '~^Регулируемая опора HILST ЛИФТ с автокоррекцией уклона ХЛ\d+ \((\d+)-(\d+) мм\)$~u'],
	'HILST_LIFT_F'  => [338, '~^Регулируемая опора HILST ЛИФТ F\d+ \(?(\d+)-(\d+) мм\)?$~u'],
	'LEVEL_3D'      => [426, '~^Регулируемая опора Level 3Д (\d+)-(\d+)$~u'],
	'LEVEL_PRO'     => [426, '~^Регулируемая опора Level ПРО (\d+)-(\d+)$~u'],
];

$mode = $argv[2] ?? '';
if ($mode === 'rollback') {
	$bk = json_decode(file_get_contents($argv[3]), true);
	foreach ($bk as $id => $v) {
		CIBlockElement::SetPropertyValuesEx($id, ND_IB, ['LINE' => $v['LINE'] ?: false, 'HEIGHT_RANGE' => $v['HEIGHT_RANGE'] ?: false]);
		echo "откат $id\n";
	}
	exit;
}
$apply = in_array('--apply', $argv, true);
$enum = [];
$r = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => ND_IB, 'CODE' => 'LINE']);
while ($e = $r->Fetch()) {
	$enum[$e['XML_ID']] = $e['ID'];
}
$todo = $mode === 'all' ? array_keys($rules) : [$mode];
$backup = [];
foreach ($todo as $xml) {
	if (!isset($rules[$xml], $enum[$xml])) {
		exit("нет линейки $xml\n");
	}
	[$sec, $re] = $rules[$xml];
	echo "== $xml (раздел $sec)\n";
	$res = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => ND_IB, 'SECTION_ID' => $sec],
		false, false, ['ID', 'NAME', 'ACTIVE', 'PROPERTY_LINE', 'PROPERTY_HEIGHT_RANGE']);
	$seen = [];
	while ($el = $res->Fetch()) {
		if (isset($seen[$el['ID']]) || !preg_match($re, trim($el['NAME']), $m)) {
			continue;
		}
		$seen[$el['ID']] = true;
		$range = "{$m[1]}-{$m[2]}";
		$curLine = (string) $el['PROPERTY_LINE_ENUM_ID'];
		$curRange = (string) $el['PROPERTY_HEIGHT_RANGE_VALUE'];
		$set = [];
		if ($curLine === '') {
			$set['LINE'] = $enum[$xml];
		} elseif ($curLine !== (string) $enum[$xml]) {
			echo "  {$el['ID']} ПРОПУСК: уже в другой линейке ($curLine)\n";
			continue;
		}
		if ($curRange === '') {
			$set['HEIGHT_RANGE'] = $range;
		} elseif ($curRange !== $range) {
			echo "  {$el['ID']} внимание: HEIGHT_RANGE «{$curRange}», в названии $range — не трогаю\n";
		}
		echo "  {$el['ID']} [{$el['ACTIVE']}] $range ".($set ? 'запишу '.implode(',', array_keys($set)) : 'уже заполнено')."  {$el['NAME']}\n";
		if ($apply && $set) {
			$backup[$el['ID']] = ['LINE' => $curLine, 'HEIGHT_RANGE' => $curRange];
			CIBlockElement::SetPropertyValuesEx($el['ID'], ND_IB, $set);
		}
	}
}
if ($backup) {
	$fn = dirname($_SERVER['DOCUMENT_ROOT']).'/nd_line_fill_backup_'.date('Ymd-His').'.json';
	file_put_contents($fn, json_encode($backup, JSON_PRETTY_PRINT));
	// SetPropertyValuesEx не сбрасывает тегированный кеш — сбрасываем, как это сделала бы админка
	CIBlock::clearIblockTagCache(ND_IB);
	echo "бэкап: $fn\n";
}
