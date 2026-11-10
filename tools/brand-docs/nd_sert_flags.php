<?php
/**
 * Галочка «Показывать на странице Лицензии и сертификаты» (SERT_LICENSE, ИБ 13) по правилу
 * (Ирина, 10.11.2026): на странице /info/sertifikaty-i-litsenzii/ только
 *   - активные документы;
 *   - сертификаты соответствия, пожарные сертификаты и свидетельства на товарный знак
 *     (название начинается с «Сертификат» или «Свидетельство»);
 *   - привязанные к активному производителю (свойство BRAND → ИБ 12).
 * Остальным галочка снимается. Дальше страница работает только по галочке.
 *
 *   php nd_sert_flags.php <DOCUMENT_ROOT> dry|run|rollback
 */
$root = $argv[1] ?? ''; $mode = $argv[2] ?? 'dry';
if (!$root || !is_dir($root)) die("usage: php nd_sert_flags.php <DOCUMENT_ROOT> <mode>\n");
$_SERVER['DOCUMENT_ROOT'] = $root;
define('NO_KEEP_STATISTIC', true); define('NOT_CHECK_PERMISSIONS', true); define('NO_AGENT_CHECK', true);
require $root.'/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock');

$IB = 13; $dir = (getenv('HOME') ?: sys_get_temp_dir()).'/nd_brand_docs'; @mkdir($dir);
$bak = "$dir/sert_flags_before.json";
$enumY = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $IB, 'CODE' => 'SERT_LICENSE', 'XML_ID' => 'Y'])->Fetch()
	?: CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $IB, 'CODE' => 'SERT_LICENSE'])->Fetch();
$Y = (int)$enumY['ID'];

if ($mode === 'rollback') {
	foreach (json_decode(file_get_contents($bak), true) as $id => $v) CIBlockElement::SetPropertyValuesEx($id, $IB, ['SERT_LICENSE' => $v ?: false]);
	CIBlock::clearIblockTagCache($IB); echo "restored\n"; exit;
}

$activeBrands = []; $r = CIBlockElement::GetList([], ['IBLOCK_ID' => 12, 'ACTIVE' => 'Y'], false, false, ['ID', 'NAME']);
while ($a = $r->Fetch()) $activeBrands[$a['ID']] = $a['NAME'];

$on = $off = $old = []; $noPic = [];
$r = CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => $IB], false, false, ['ID', 'IBLOCK_ID', 'NAME', 'ACTIVE', 'PREVIEW_PICTURE']);
while ($ob = $r->GetNextElement()) {
	$f = $ob->GetFields(); $p = $ob->GetProperties([], []);
	$cur = $p['SERT_LICENSE']['VALUE_ENUM_ID'] ? (int)$p['SERT_LICENSE']['VALUE_ENUM_ID'] : 0;
	$old[$f['ID']] = $cur;
	$brands = array_values(array_intersect_key($activeBrands, array_flip(array_filter((array)$p['BRAND']['VALUE']))));
	$want = $f['ACTIVE'] === 'Y'
		&& preg_match('/^\s*(Сертификат|Свидетельство)/u', $f['~NAME'])
		&& $brands;
	$label = $f["ID"]." ".$f["~NAME"]." [".implode(', ', $brands)."]";
	if ($want) { $on[$f['ID']] = $label.($cur ? '' : '  (+ галочка)'); if (!$f['PREVIEW_PICTURE']) $noPic[] = $label; }
	elseif ($cur) $off[$f['ID']] = $label;
}
echo "== показывать (", count($on), "):\n  ", implode("\n  ", $on), "\n";
echo "== снять галочку (", count($off), "):\n  ", implode("\n  ", $off), "\n";
if ($noPic) echo "== без картинки анонса:\n  ", implode("\n  ", $noPic), "\n";
if ($mode !== 'run') { echo "dry run\n"; exit; }
if (!file_exists($bak)) file_put_contents($bak, json_encode($old));
foreach ($on as $id => $_) if (!$old[$id]) CIBlockElement::SetPropertyValuesEx($id, $IB, ['SERT_LICENSE' => $Y]);
foreach ($off as $id => $_) CIBlockElement::SetPropertyValuesEx($id, $IB, ['SERT_LICENSE' => false]);
CIBlock::clearIblockTagCache($IB);
echo "done\n";
