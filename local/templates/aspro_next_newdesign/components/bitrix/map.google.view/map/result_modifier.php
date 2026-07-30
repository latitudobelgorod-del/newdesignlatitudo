<?
global $arRegion;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if($arParams['USE_REGION_DATA'] == 'Y' && $arRegion && $arRegion["PROPERTY_REGION_TAG_YANDEX_MAP_VALUE"])
{
	$arCoord = explode(",", $arRegion["PROPERTY_REGION_TAG_YANDEX_MAP_VALUE"]);
	$arResult['POSITION']['google_lat'] = $arCoord[0];
	$arResult['POSITION']['google_lon'] = $arCoord[1];

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
			'EMAIL' => $arEmails = is_array($arRegion['PROPERTY_EMAIL_VALUE']) ? implode(",", $arRegion['PROPERTY_EMAIL_VALUE']) : $arRegion['PROPERTY_EMAIL_VALUE'],
			'PHONE' => $arRegion['PHONES'],
			'ADDRESS' => $address,
			'METRO' => $arRegion['PROPERTY_METRO_VALUE'],
			'SCHEDULE' => $arRegion['PROPERTY_SHCEDULE_VALUE']['TEXT'],
			'DISPLAY_PROPERTIES' => [
				'METRO' => [
					'NAME' => Loc::getMessage('MYMS_TPL_ADRESS'),
				],
				'SCHEDULE' => [
					'NAME' => Loc::getMessage('MYMS_TPL_SCHEDULE'),
				],
				'PHONE' => [
					'NAME' =>  Loc::getMessage('MYMS_TPL_PHONE'),
				],
				'EMAIL' => [
					'NAME' => Loc::getMessage('MYMS_TPL_EMAIL'),
				]
			],
	];

	$arResult['POSITION']['PLACEMARKS'] = array(
		array(
			"LON" => $arResult['POSITION']['google_lon'],
			"LAT" => $arResult['POSITION']['google_lat'],
			"TEXT" => \CNext::prepareItemMapHtml($popupOptions),
		)
	);
}
?>