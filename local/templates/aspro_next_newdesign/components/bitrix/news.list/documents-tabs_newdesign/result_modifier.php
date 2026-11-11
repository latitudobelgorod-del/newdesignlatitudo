<?

foreach($arResult['ITEMS'] as $key => $arItem)
{
	/*if($SID = $arItem['IBLOCK_SECTION_ID']){
		$arSectionsIDs[] = $SID;
	}*/
	if($arItem['IBLOCK_SECTION_ID']){
		$resGroups = CIBlockElement::GetElementGroups($arItem['ID'], true, array('ID'));
		while($arGroup = $resGroups->Fetch())
		{
			$arResult['ITEMS'][$key]['SECTIONS'][$arGroup['ID']] = $arGroup['ID'];
			$arGoodsSectionsIDs[$arGroup['ID']] = $arGroup['ID'];
		}
	}
	CNext::getFieldImageData($arResult['ITEMS'][$key], array('PREVIEW_PICTURE'));
}
/*if($arSectionsIDs){
	$arResult['SECTIONS'] = CNextCache::CIBLockSection_GetList(array('SORT' => 'ASC', 'NAME' => 'ASC', 'CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'GROUP' => array('ID'), 'MULTI' => 'N')), array('ID' => $arSectionsIDs));
}*/
if($arGoodsSectionsIDs){
	$arResult['SECTIONS'] = CNextCache::CIBLockSection_GetList(array('SORT' => 'ASC', 'NAME' => 'ASC', 'CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'GROUP' => array('ID'), 'MULTI' => 'N')), 
	array('ID' => $arGoodsSectionsIDs));
	
	
	foreach($arResult['ITEMS'] as $key => $arItem)
	{
		if($arItem['IBLOCK_SECTION_ID'])
		{
			foreach($arItem['SECTIONS'] as $id => $name)
				$arResult['ITEMS'][$key]['SECTIONS'][$id] = $arGoodsSections[$id];
		}
	}
}








?><?
/**
 * Табы по производителям (documents-tabs_newdesign, 11.11.2026) — как на странице
 * «Сертификаты и лицензии» (licenses-tabs_newdesign/result_modifier.php).
 * Таб — у активного производителя (ИБ 12) с хотя бы одним документом. Документ без
 * файла не выводится — и не считается. Сначала свои марки, затем по алфавиту.
 */
$ndActive = array();
$rs = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 12, 'ACTIVE' => 'Y'), false, false, array('ID', 'NAME'));
while($ar = $rs->Fetch())
	$ndActive[(int)$ar['ID']] = $ar['NAME'];

$ndTabs = array();
foreach($arResult['ITEMS'] as $key => $arItem)
{
	if(empty($arItem['PROPERTIES']['INSTRUCTIONS']['VALUE']))
	{
		unset($arResult['ITEMS'][$key]);
		continue;
	}
	$ndBrands = array();
	foreach((array)$arItem['PROPERTIES']['BRAND']['VALUE'] as $bid)
	{
		$bid = (int)$bid;
		if(!$bid || !isset($ndActive[$bid]) || in_array($bid, $ndBrands))
			continue;
		$ndBrands[] = $bid;
		if(!isset($ndTabs[$bid]))
			$ndTabs[$bid] = array('NAME' => $ndActive[$bid], 'COUNT' => 0);
		$ndTabs[$bid]['COUNT']++;
	}
	$arResult['ITEMS'][$key]['ND_BRANDS'] = $ndBrands;
}

$ndOwn = array('LATITUDO' => 0, 'EASYDECKING' => 1);
uasort($ndTabs, function($a, $b) use ($ndOwn) {
	$ka = mb_strtoupper($a['NAME']); $kb = mb_strtoupper($b['NAME']);
	$oa = isset($ndOwn[$ka]) ? $ndOwn[$ka] : 9; $ob = isset($ndOwn[$kb]) ? $ndOwn[$kb] : 9;
	return $oa != $ob ? $oa - $ob : strcasecmp($a['NAME'], $b['NAME']);
});
$arResult['ND_TABS'] = $ndTabs;

// Во «Все» сначала документы своих марок (EasyDecking и LATITUDO — наравне, между ними решает
// сортировка элемента: «Свидетельство на товарный знак Latitudo» первым), потом остальные
// в прежнем порядке (Ирина, 11.11.2026).
$ndOwnDocs = array('EASYDECKING' => 0, 'LATITUDO' => 0);
$ndRank = array();
$ndPos = 0;
foreach($arResult['ITEMS'] as $key => $arItem)
{
	$r = 9;
	foreach((array)$arItem['ND_BRANDS'] as $bid)
	{
		$bn = mb_strtoupper($ndActive[$bid]);
		if(isset($ndOwnDocs[$bn]) && $ndOwnDocs[$bn] < $r)
			$r = $ndOwnDocs[$bn];
	}
	$ndRank[$key] = array($r, $ndPos++);
}
uksort($arResult['ITEMS'], function($a, $b) use ($ndRank) {
	return $ndRank[$a][0] != $ndRank[$b][0] ? $ndRank[$a][0] - $ndRank[$b][0] : $ndRank[$a][1] - $ndRank[$b][1];
});

