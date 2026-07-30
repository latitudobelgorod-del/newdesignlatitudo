<?
global $arRegion;
$bRegionContact = (\Bitrix\Main\Config\Option::get('aspro.next', 'SHOW_REGION_CONTACT', 'N') == 'Y');
if($arParams['USE_REGION_DATA'] == 'Y' && $arRegion && $arRegion["PROPERTY_REGION_TAG_YANDEX_MAP_VALUE"] && $bRegionContact)
{

	$phones = '';
	if($arRegion['PHONES']) {
		foreach ($arRegion['PHONES'] as $phone) {
			$phones .= '<div class="value"><a class="dark_link" rel= "nofollow" href="tel:'.str_replace(array(' ', ',', '-', '(', ')'), '', $phone).'">'.$phone.'</a></div>';
		}
	}

	$emails = '';
	if($arRegion['PROPERTY_EMAIL_VALUE']) {
		foreach ($arRegion['PROPERTY_EMAIL_VALUE'] as $email) {
			$emails .= '<a class="dark_link" href="mailto:' .$email. '">' .$email . '</a><br>';
		}
	}

	$metrolist = '';
	foreach ($arRegion['PROPERTY_METRO_VALUE'] as $metro) {
		$metrolist .= '<div class="metro"><i></i>'. $metro . '</div>';
	}

	$address = ($arRegion['PROPERTY_ADDRESS_VALUE']['TEXT'] ? $arRegion['PROPERTY_ADDRESS_VALUE']['TEXT'] : $arItem['NAME']);

	$popupOptions = [
			'EMAIL' => is_array($arRegion['PROPERTY_EMAIL_VALUE']) ? implode(",", $arRegion['PROPERTY_EMAIL_VALUE']) : $arRegion['PROPERTY_EMAIL_VALUE'],
			'PHONE' => $arRegion['PHONES'],
			'ADDRESS' => $address,
			'METRO' => $arRegion['PROPERTY_METRO_VALUE'],
			'SCHEDULE' => $arRegion['PROPERTY_SHCEDULE_VALUE']['TEXT'],
			'DISPLAY_PROPERTIES' => [
				'METRO' => [
					'NAME' => GetMessage('MYMS_TPL_ADRESS'),
				],
				'SCHEDULE' => [
					'NAME' => GetMessage('MYMS_TPL_SCHEDULE'),
				],
				'PHONE' => [
					'NAME' =>  GetMessage('MYMS_TPL_PHONE'),
				],
				'EMAIL' => [
					'NAME' => GetMessage('MYMS_TPL_EMAIL'),
				]
			],
	];

	$arCoord = explode(",", $arRegion["PROPERTY_REGION_TAG_YANDEX_MAP_VALUE"]);
	$arResult['POSITION']['yandex_lat'] = $arCoord[0];
	$arResult['POSITION']['yandex_lon'] = $arCoord[1];
	$arTmpMark = array(
		"LON" => $arResult['POSITION']['yandex_lon'],
		"LAT" => $arResult['POSITION']['yandex_lat'],
		"TEXT" => \CNext::prepareItemMapHtml($popupOptions),
	);
	$arResult['POSITION']['PLACEMARKS'] = array();
	$arResult['POSITION']['PLACEMARKS'][] = $arTmpMark;
}
?>