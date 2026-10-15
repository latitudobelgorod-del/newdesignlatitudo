<?php
/**
 * Millargo: кружок цвета у товара, у которого цвет один (Ирина, 15.10.2026).
 * nd_millargo_colors.php ставил «Вариации цветов» (ASSOCIATED) только группам
 * из двух и больше цветов — у одноцветных ряд кружков оставался пустым.
 * Делаем, как у белых ограждений Ко-Экструзия: в вариациях стоит сам товар.
 * Берём только товары со свотчем (COLOR_MAIN_EL) и пустым ASSOCIATED —
 * без свотча кружку нечего показывать.
 *
 *   php nd_millargo_single_color.php <DOCROOT> [--apply]
 *   php nd_millargo_single_color.php <DOCROOT> rollback <файл бэкапа>
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

const ND_IB = 19;
const ND_BRAND = 28153; // Millargo
$home = getenv('HOME') ?: dirname($_SERVER['DOCUMENT_ROOT']);

function ndPropValues(int $id, string $code): array
{
	$v = [];
	$r = CIBlockElement::GetProperty(ND_IB, $id, [], ['CODE' => $code]);
	while ($p = $r->Fetch()) {
		if ($p['VALUE'] !== null && $p['VALUE'] !== '' && $p['VALUE'] !== false) {
			$v[] = $p['VALUE'];
		}
	}
	return $v;
}

if (($argv[2] ?? '') === 'rollback') {
	$bk = json_decode(file_get_contents($argv[3]), true);
	foreach ($bk['elements'] as $id => $assoc) {
		CIBlockElement::SetPropertyValuesEx((int)$id, ND_IB, ['ASSOCIATED' => $assoc ?: false]);
	}
	CIBlock::clearIblockTagCache(ND_IB);
	echo 'откат: ' . count($bk['elements']) . " элементов\n";
	exit;
}

$apply = in_array('--apply', $argv, true);
$todo = [];
$rs = CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => ND_IB, 'PROPERTY_BRAND' => ND_BRAND], false, false, ['ID', 'NAME']);
while ($e = $rs->Fetch()) {
	$id = (int)$e['ID'];
	if (ndPropValues($id, 'ASSOCIATED') || !ndPropValues($id, 'COLOR_MAIN_EL')) {
		continue;
	}
	$todo[$id] = $e['NAME'];
	echo "$id\t{$e['NAME']}\n";
}
echo 'к правке: ' . count($todo) . "\n";
if (!$apply || !$todo) {
	exit;
}

$bkFile = "$home/nd_millargo_single_backup_" . date('Ymd-His') . '.json';
file_put_contents($bkFile, json_encode(['elements' => array_fill_keys(array_keys($todo), [])], JSON_PRETTY_PRINT));
echo "бэкап: $bkFile\n";
foreach ($todo as $id => $name) {
	CIBlockElement::SetPropertyValuesEx($id, ND_IB, ['ASSOCIATED' => [$id]]);
}
// SetPropertyValuesEx кеш инфоблока не сбрасывает
CIBlock::clearIblockTagCache(ND_IB);
echo "готово\n";
