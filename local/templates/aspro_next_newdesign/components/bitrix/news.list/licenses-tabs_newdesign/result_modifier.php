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
