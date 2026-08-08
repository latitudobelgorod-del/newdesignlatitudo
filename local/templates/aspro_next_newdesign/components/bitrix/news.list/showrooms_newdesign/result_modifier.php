<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Шоу-румы для страницы «О компании»: подготовка данных.
 *
 * Порядок карточек задаём списком ID из фильтра — сортировка инфоблока
 * контактов рассчитана на раздел /contacts/ и здесь не подходит.
 * Город берём из связанного региона (ИБ 7, свойство LINK_REGION) — так же,
 * как в contacts_region_newdesign. Фото офиса — первый кадр галереи
 * MORE_PHOTOS, режем под 488x395 из макета с двойной плотностью.
 */

global $ndCoShowroomsFilter;
$arOrder = isset($ndCoShowroomsFilter['ID']) ? (array) $ndCoShowroomsFilter['ID'] : array();

if ($arOrder) {
	$arSorted = array();
	foreach ($arOrder as $id) {
		foreach ($arResult['ITEMS'] as $arItem) {
			if ((int) $id === (int) $arItem['ID']) {
				$arSorted[] = $arItem;
			}
		}
	}
	if ($arSorted) {
		$arResult['ITEMS'] = $arSorted;
	}
}

foreach ($arResult['ITEMS'] as &$arItem) {
	$regionId = $arItem['PROPERTIES']['LINK_REGION']['VALUE'] ?? 0;
	if (is_array($regionId)) {
		$regionId = reset($regionId);
	}
	$regionId = (int) $regionId;

	$arItem['ND_CITY'] = '';
	if ($regionId > 0) {
		$res = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 7, 'ID' => $regionId), false, false, array('ID', 'NAME'));
		if ($row = $res->GetNext()) {
			$arItem['ND_CITY'] = $row['NAME'];
		}
	}

	$arItem['ND_PHOTO'] = '';
	$photos = $arItem['PROPERTIES']['MORE_PHOTOS']['VALUE'] ?? array();
	foreach ((array) $photos as $fileId) {
		$img = CFile::ResizeImageGet((int) $fileId, array('width' => 976, 'height' => 790), BX_RESIZE_IMAGE_EXACT, true);
		if (!empty($img['src'])) {
			$arItem['ND_PHOTO'] = $img['src'];
			break;
		}
	}
}
unset($arItem);
?>
