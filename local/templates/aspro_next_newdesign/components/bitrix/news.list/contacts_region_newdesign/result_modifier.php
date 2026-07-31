<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Контакты региона (новый дизайн): подготовка данных.
 * Название города берём из связанного региона (ИБ 7, свойство LINK_REGION) —
 * им подписаны кнопки «Схема проезда … г. Москва». LINK_REGION множественное,
 * значение приходит массивом.
 * Галерея — свойство MORE_PHOTOS (тип «файл», множественное): режем под
 * размер макета 441×294 с двойной плотностью.
 */

foreach ($arResult['ITEMS'] as &$arItem) {
	$regionId = $arItem['PROPERTIES']['LINK_REGION']['VALUE'] ?? 0;
	if (is_array($regionId)) {
		$regionId = reset($regionId);
	}
	$regionId = (int) $regionId;

	$arItem['ND_CITY'] = '';
	if ($regionId > 0) {
		$res = CIBlockElement::GetList([], ['IBLOCK_ID' => 7, 'ID' => $regionId], false, false, ['ID', 'NAME']);
		if ($row = $res->GetNext()) {
			$arItem['ND_CITY'] = $row['NAME'];
		}
	}

	$arItem['ND_GALLERY'] = [];
	$photos = $arItem['PROPERTIES']['MORE_PHOTOS']['VALUE'] ?? [];
	foreach ((array) $photos as $fileId) {
		$fileId = (int) $fileId;
		if ($fileId <= 0) {
			continue;
		}
		$img = CFile::ResizeImageGet($fileId, ['width' => 882, 'height' => 588], BX_RESIZE_IMAGE_EXACT, true);
		if (!empty($img['src'])) {
			$arItem['ND_GALLERY'][] = $img['src'];
		}
	}

	// Карты из конструктора Яндекса: свойство множественное, приходит массивом
	// строк с готовым <iframe>. Первая — офис, вторая (если заведут) — склад.
	$maps = $arItem['PROPERTIES']['YMAP_CONSTR']['~VALUE'] ?? '';
	$arItem['ND_MAPS'] = array_values(array_filter(array_map('trim', (array) $maps)));
}
unset($arItem);
?>
