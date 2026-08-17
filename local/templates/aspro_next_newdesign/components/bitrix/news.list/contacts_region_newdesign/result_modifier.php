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

	/* Карты из конструктора Яндекса (готовый <iframe> строкой).
	   Основные свойства — YMAP_CONSTR_OFFICE и YMAP_CONSTR_SKLAD: у каждой
	   карты своя кнопка-переключатель, и офис со складом больше не зависят
	   от порядка значений. Оба множественные — берём первое непустое.

	   Если новых свойств нет (их не завели или это старая копия базы),
	   откатываемся на общее YMAP_CONSTR: там первое значение — офис,
	   второе — склад, как было до появления отдельных свойств. */
	$ndFirstMap = static function ($values) {
		foreach ((array) $values as $value) {
			$value = trim((string) $value);
			if ($value !== '') {
				return $value;
			}
		}

		return '';
	};

	$ndOffice = $ndFirstMap($arItem['PROPERTIES']['YMAP_CONSTR_OFFICE']['~VALUE'] ?? '');
	$ndStore = $ndFirstMap($arItem['PROPERTIES']['YMAP_CONSTR_SKLAD']['~VALUE'] ?? '');

	$arItem['ND_MAPS'] = [];

	if ($ndOffice !== '' || $ndStore !== '') {
		if ($ndOffice !== '') {
			$arItem['ND_MAPS'][] = ['TYPE' => 'office', 'HTML' => $ndOffice];
		}
		if ($ndStore !== '') {
			$arItem['ND_MAPS'][] = ['TYPE' => 'store', 'HTML' => $ndStore];
		}
	} else {
		$maps = array_values(array_filter(array_map('trim', (array) ($arItem['PROPERTIES']['YMAP_CONSTR']['~VALUE'] ?? ''))));
		foreach ($maps as $i => $html) {
			$arItem['ND_MAPS'][] = ['TYPE' => $i === 0 ? 'office' : 'store', 'HTML' => $html];
		}
	}
}
unset($arItem);
?>
