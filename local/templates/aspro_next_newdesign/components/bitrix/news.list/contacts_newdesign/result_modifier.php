<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Общая страница контактов нового дизайна: подготовка данных.
 *
 * Карточка подписана городом, а не названием элемента («Контакты Латитудо в
 * Москве») — название города лежит в связанном элементе регионов (ИБ 7,
 * свойство LINK_REGION). Оттуда же берём MAIN_DOMAIN для ссылки на
 * региональные контакты. Старый шаблон делал по запросу на карточку прямо
 * в разметке — здесь один запрос на всё.
 */

/** LINK_REGION — множественное свойство, значение приходит массивом. */
$ndRegionId = static function ($arItem) {
	$value = $arItem['PROPERTIES']['LINK_REGION']['VALUE'] ?? 0;
	if (is_array($value)) {
		$value = reset($value);
	}
	return (int) $value;
};

$ndRegionIds = [];
foreach ($arResult['ITEMS'] as $arItem) {
	$id = $ndRegionId($arItem);
	if ($id > 0) {
		$ndRegionIds[$id] = $id;
	}
}

$ndRegions = [];
if ($ndRegionIds) {
	$res = CIBlockElement::GetList(
		[],
		['IBLOCK_ID' => 7, 'ID' => array_values($ndRegionIds)],
		false,
		false,
		['ID', 'NAME', 'PROPERTY_MAIN_DOMAIN']
	);
	while ($row = $res->GetNext()) {
		$ndRegions[(int) $row['ID']] = [
			'NAME' => $row['NAME'],
			'DOMAIN' => $row['PROPERTY_MAIN_DOMAIN_VALUE'],
		];
	}
}

foreach ($arResult['ITEMS'] as &$arItem) {
	$id = $ndRegionId($arItem);
	$arItem['ND_CITY'] = $ndRegions[$id]['NAME'] ?? $arItem['NAME'];
	$arItem['ND_URL'] = !empty($ndRegions[$id]['DOMAIN']) ? 'https://'.$ndRegions[$id]['DOMAIN'].'/contacts/' : '';

	// Картинка в макете 256×415, берём с двойной плотностью и режем по центру.
	$arItem['ND_PIC'] = '';
	$picId = (int) ($arItem['PREVIEW_PICTURE']['ID'] ?? 0);
	if ($picId > 0) {
		$img = CFile::ResizeImageGet($picId, ['width' => 512, 'height' => 830], BX_RESIZE_IMAGE_EXACT, true);
		$arItem['ND_PIC'] = $img['src'] ?? $arItem['PREVIEW_PICTURE']['SRC'];
	}
}
unset($arItem);
?>
