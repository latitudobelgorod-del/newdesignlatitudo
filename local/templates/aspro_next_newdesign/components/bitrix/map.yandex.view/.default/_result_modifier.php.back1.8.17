<?
global $arRegion;
$bRegionContact = (\Bitrix\Main\Config\Option::get('aspro.next', 'SHOW_REGION_CONTACT', 'N') == 'Y');
if($arParams['USE_REGION_DATA'] == 'Y' && $arRegion && $arRegion["PROPERTY_REGION_TAG_YANDEX_MAP_VALUE"] && $bRegionContact)
{
	$arCoord = explode(",", $arRegion["PROPERTY_REGION_TAG_YANDEX_MAP_VALUE"]);
	$arResult['POSITION']['yandex_lat'] = $arCoord[0];
	$arResult['POSITION']['yandex_lon'] = $arCoord[1];
	$arTmpMark = array(
		"LON" => $arResult['POSITION']['yandex_lon'],
		"LAT" => $arResult['POSITION']['yandex_lat'],
		"TEXT" => $arResult['POSITION']['PLACEMARKS'][0]['TEXT'],
	);
	$arResult['POSITION']['PLACEMARKS'] = array();
	$arResult['POSITION']['PLACEMARKS'][] = $arTmpMark;
}
?>