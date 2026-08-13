<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Группировка вакансий по городам для нового дизайна.
 *
 * Город — это раздел инфоблока «Вакансии» (Москва, Белгород, Воронеж…),
 * отдельного свойства под него нет. Логика та же, что у боевого шаблона
 * items-accordion-vacancy, с одной разницей: разделы берём только активные.
 * Выключенный раздел — это закрытый филиал, его вакансии на странице не нужны,
 * а без проверки они бы вылезли группой без заголовка.
 */

$arNdVacSectionIds = array();
foreach ($arResult['ITEMS'] as $arNdVacItem) {
	if ($arNdVacItem['IBLOCK_SECTION_ID']) {
		$arNdVacSectionIds[] = $arNdVacItem['IBLOCK_SECTION_ID'];
	}
}

$arResult['SECTIONS'] = array();

if ($arNdVacSectionIds) {
	$arResult['SECTIONS'] = CNextCache::CIBLockSection_GetList(
		array(
			'SORT' => 'ASC',
			'NAME' => 'ASC',
			'CACHE' => array(
				'TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID']),
				'GROUP' => array('ID'),
				'MULTI' => 'N',
			),
		),
		array('ID' => array_unique($arNdVacSectionIds), 'ACTIVE' => 'Y')
	);
}

foreach ($arResult['ITEMS'] as $arNdVacItem) {
	$iNdVacSection = (int)$arNdVacItem['IBLOCK_SECTION_ID'];

	// Вакансия без раздела — общая, показываем её списком без заголовка города.
	if (!$iNdVacSection) {
		$arResult['SECTIONS'][0]['ITEMS'][$arNdVacItem['ID']] = $arNdVacItem;
		continue;
	}
	// Раздел выключен — вакансию пропускаем (см. комментарий выше).
	if (isset($arResult['SECTIONS'][$iNdVacSection])) {
		$arResult['SECTIONS'][$iNdVacSection]['ITEMS'][$arNdVacItem['ID']] = $arNdVacItem;
	}
}

foreach ($arResult['SECTIONS'] as $iNdVacKey => $arNdVacSection) {
	if (empty($arNdVacSection['ITEMS'])) {
		unset($arResult['SECTIONS'][$iNdVacKey]);
	}
}
?>
