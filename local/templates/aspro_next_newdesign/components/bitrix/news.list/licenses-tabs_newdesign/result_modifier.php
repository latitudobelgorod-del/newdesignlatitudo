<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * Табы по производителям для страницы «Сертификаты и лицензии».
 *
 * У каждого документа — свойство BRAND («Привязка к производителю», ИБ 12, множественное).
 * Таб есть только у активного производителя, у которого есть хотя бы один документ
 * в выдаче. Порядок табов: сначала свои марки (LATITUDO, EasyDecking), затем по алфавиту.
 * Документ без активного производителя попадает только во «Все».
 */
$ndActive = array();
$rs = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 12, 'ACTIVE' => 'Y'), false, false, array('ID', 'NAME'));
while($ar = $rs->Fetch())
	$ndActive[(int)$ar['ID']] = $ar['NAME'];

$ndTabs = array();
foreach($arResult['ITEMS'] as $key => $arItem)
{
	// Без картинки карточку не показать — не считаем её ни во «Все», ни в табах.
	if(empty($arItem['PREVIEW_PICTURE']['SRC']) && empty($arItem['DETAIL_PICTURE']['SRC']))
	{
		unset($arResult['ITEMS'][$key]);
		continue;
	}
	CNext::getFieldImageData($arResult['ITEMS'][$key], array('PREVIEW_PICTURE'));

	$ndBrands = array();
	foreach((array)$arItem['PROPERTIES']['BRAND']['VALUE'] as $bid)
	{
		$bid = (int)$bid;
		if(!$bid || !isset($ndActive[$bid]))
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

