<?php
/**
 * «Вариации цветов» (ASSOCIATED) товаров 4SIS — по «Доступным вариантам» с 4sis.ru (variants.json от variants_4sis.py).
 *
 *   php variants_apply_4sis.php                          — сухой прогон, план в ~/4sis-sync/<дата>_variants_dry/plan.csv
 *   php variants_apply_4sis.php apply                    — записать (резервная копия backup.json)
 *   php variants_apply_4sis.php rollback=<backup.json>   — вернуть как было
 *
 * Карточка 4sis.ru ↔ наш товар — по точному названию. У товара в списке: он сам, его варианты на 4sis.ru
 * и те, у кого он сам в вариантах (списки у 4SIS не всегда взаимные). Только товары, которые есть у нас;
 * выключенные тоже ставим — карточка и плитка показывают только включённые, а включится товар — появится и в вариантах.
 * Список из одного товара (только он сам) не ставим. Не больше 30 вариантов.
 */
$args = array_slice($argv, 1);
$mode = 'dry';
$rollbackFile = '';
$json = getenv('HOME') . '/4sis-sync/variants.json';
foreach ($args as $a) {
	if ($a === 'apply') {
		$mode = 'apply';
	} elseif (strpos($a, 'rollback=') === 0) {
		$mode = 'rollback';
		$rollbackFile = substr($a, 9);
	} elseif (strpos($a, '--json=') === 0) {
		$json = substr($a, 7);
	}
}

$_SERVER['DOCUMENT_ROOT'] = getenv('HOME') . '/www/latitudo.ru';
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
\Bitrix\Main\Loader::includeModule('iblock');

$outDir = getenv('HOME') . '/4sis-sync/' . date('Y-m-d_His') . '_variants_' . $mode;
mkdir($outDir, 0700, true);

if ($mode === 'rollback') {
	$backup = json_decode((string)file_get_contents($rollbackFile), true);
	foreach ($backup as $id => $vals) {
		CIBlockElement::SetPropertyValuesEx((int)$id, 19, ['ASSOCIATED' => $vals ?: false]);
	}
	CIBlock::clearIblockTagCache(19);
	echo 'Возвращено товаров: ' . count($backup) . "\n";
	exit(0);
}

function ndNorm(string $s): string
{
	$s = mb_strtolower(str_replace(['ё', 'Ё'], 'е', $s));
	$s = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $s);
	return trim(preg_replace('/\s+/', ' ', $s));
}

$pages = json_decode((string)file_get_contents($json), true);
if (!$pages) {
	fwrite(STDERR, "Не читается $json\n");
	exit(1);
}

// наши товары 4SIS по названию
$ours = [];
$byName = [];
$rs = CIBlockElement::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => 19, 'PROPERTY_BRAND' => 28619], false, false, ['ID', 'NAME', 'ACTIVE']);
while ($r = $rs->Fetch()) {
	$ours[(int)$r['ID']] = $r;
	$byName[ndNorm($r['NAME'])][] = (int)$r['ID'];
}
// карточка 4sis.ru → наш ID (только если название однозначно)
$slugToId = [];
foreach ($pages as $slug => $p) {
	$ids = $byName[ndNorm($p['name'])] ?? [];
	if (count($ids) === 1) {
		$slugToId[$slug] = $ids[0];
	}
}
// связи: свои варианты + обратные
$links = [];
foreach ($pages as $slug => $p) {
	if (!isset($slugToId[$slug])) {
		continue;
	}
	$id = $slugToId[$slug];
	foreach ($p['variants'] as $v) {
		if (isset($slugToId[$v]) && $slugToId[$v] !== $id) {
			$links[$id][$slugToId[$v]] = true;
			$links[$slugToId[$v]][$id] = true;
		}
	}
}

$plan = [];
$fh = fopen($outDir . '/plan.csv', 'w');
fwrite($fh, "\xEF\xBB\xBF");
fputcsv($fh, ['ID', 'Активен', 'Название', 'Вариантов', 'Варианты (ID)', 'Варианты (названия)'], ';');
foreach ($links as $id => $set) {
	$list = array_merge([$id], array_keys($set));
	$list = array_slice($list, 0, 30);
	sort($list);
	$plan[$id] = $list;
	fputcsv($fh, [$id, $ours[$id]['ACTIVE'], $ours[$id]['NAME'], count($list), implode(',', $list),
		implode(' | ', array_map(function ($v) use ($ours) { return $ours[$v]['NAME']; }, $list))], ';');
}
fclose($fh);
echo 'Карточек 4sis.ru: ' . count($pages) . ', сопоставлено с нашими: ' . count($slugToId) . "\n";
echo 'Товаров с вариантами: ' . count($plan) . ' (включённых ' . count(array_filter(array_keys($plan), function ($id) use ($ours) { return $ours[$id]['ACTIVE'] === 'Y'; })) . ")\n";
echo "План: $outDir/plan.csv\n";
if ($mode !== 'apply') {
	echo "Сухой прогон — в базе ничего не изменено. Записать: добавьте apply\n";
	exit(0);
}

$backup = [];
foreach ($plan as $id => $list) {
	$old = [];
	$pr = CIBlockElement::GetProperty(19, $id, [], ['CODE' => 'ASSOCIATED']);
	while ($x = $pr->Fetch()) {
		if ($x['VALUE']) {
			$old[] = (int)$x['VALUE'];
		}
	}
	$backup[$id] = $old;
}
file_put_contents($outDir . '/backup.json', json_encode($backup));
foreach ($plan as $id => $list) {
	CIBlockElement::SetPropertyValuesEx($id, 19, ['ASSOCIATED' => $list]);
}
CIBlock::clearIblockTagCache(19);
echo 'Записано: ' . count($plan) . "\nОткат: php " . __FILE__ . " rollback=$outDir/backup.json\n";
