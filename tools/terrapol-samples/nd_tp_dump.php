<?php
$_SERVER['DOCUMENT_ROOT'] = getenv('HOME') . '/www/latitudo.ru';
define('NO_KEEP_STATISTIC', true); define('NOT_CHECK_PERMISSIONS', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('iblock'); CModule::IncludeModule('catalog');
$b = CIBlockElement::GetList([], ['IBLOCK_ID' => 12, '%NAME' => 'рапол'], false, false, ['ID', 'NAME'])->Fetch();
$b2 = CIBlockElement::GetList([], ['IBLOCK_ID' => 12, '%NAME' => 'rrapol'], false, false, ['ID', 'NAME']);
$brands = []; if ($b) $brands[$b['ID']] = $b['NAME']; while ($x = $b2->Fetch()) $brands[$x['ID']] = $x['NAME'];
$out = ['brands' => $brands, 'items' => []];
$secs = [];
if (!$brands) exit('нет бренда');
$r = CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => 19, 'PROPERTY_BRAND' => array_keys($brands)], false, false, ['ID', 'NAME', 'ACTIVE', 'IBLOCK_SECTION_ID', 'DETAIL_PAGE_URL']);
while ($p = $r->GetNext()) {
    $sid = (int)$p['IBLOCK_SECTION_ID'];
    if (!isset($secs[$sid])) { $s = CIBlockSection::GetByID($sid)->Fetch(); $secs[$sid] = [$s['NAME'], $s['ACTIVE'] ?? 'N']; }
    $offers = [];
    $o = CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => 20, 'PROPERTY_CML2_LINK' => $p['ID']], false, false, ['ID', 'NAME', 'ACTIVE']);
    while ($x = $o->Fetch()) $offers[] = ['id' => (int)$x['ID'], 'name' => trim($x['NAME']), 'active' => $x['ACTIVE'], 'price' => (float)(CPrice::GetBasePrice($x['ID'])['PRICE'] ?? 0)];
    $out['items'][] = ['id' => (int)$p['ID'], 'name' => trim($p['~NAME']), 'active' => $p['ACTIVE'], 'sec' => $secs[$sid][0], 'secon' => $secs[$sid][1], 'url' => 'https://latitudo.ru' . $p['DETAIL_PAGE_URL'], 'offers' => $offers, 'price' => $offers ? 0 : (float)(CPrice::GetBasePrice($p['ID'])['PRICE'] ?? 0)];
}
echo json_encode($out, JSON_UNESCAPED_UNICODE);
