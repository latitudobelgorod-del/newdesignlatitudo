<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Плитки каталога нового дизайна: подготовка данных для template.php.
 *
 * Компонент отдаёт разделы плоским списком, поэтому здесь:
 *  1) собираем дерево «раздел первого уровня → его подразделы»;
 *  2) подбираем иконку карточки.
 *
 * Подразделы отбираем по UF_SECTION_IN_MENU — ровно как в старом шаблоне
 * 2025_1_catalog_sections_list_desktop, иначе в карточки полезут технические
 * разделы, которых нет в меню.
 *
 * Иконки (макет Figma, фрейм «Каталог страница» 21349:57069) — это не
 * фотографии разделов, а отрисованные вырезки товаров.
 *
 * Порядок источников:
 *  1) UF_ND_ICON («фото-иконка раздела») — поле нового дизайна, заполняется
 *     из админки, эта же картинка идёт в меню каталога;
 *  2) файл из макета в шаблоне: images/newdesign/catalog/<CODE>.png —
 *     ими блок жил до появления поля, оставлены запасным вариантом;
 *  3) PICTURE раздела — широкое фото сцены, режется в квадрат;
 *  4) ничего — карточка рисуется без иконки.
 *
 * UF_CATALOG_ICON не трогаем: он пуст и его читает меню старого дизайна.
 */

$ndIconDir = SITE_TEMPLATE_PATH.'/images/newdesign/catalog/';

$ndSections = [];
foreach ($arResult['SECTIONS'] as $arItem) {
	if ((int) $arItem['DEPTH_LEVEL'] !== 1) {
		continue;
	}
	$arItem['CHILDS'] = [];
	$ndSections[(int) $arItem['ID']] = $arItem;
}

foreach ($arResult['SECTIONS'] as $arItem) {
	if ((int) $arItem['DEPTH_LEVEL'] !== 2) {
		continue;
	}
	$parent = (int) $arItem['IBLOCK_SECTION_ID'];
	if (!isset($ndSections[$parent]) || (string) ($arItem['UF_SECTION_IN_MENU'] ?? '') !== '1') {
		continue;
	}
	$ndSections[$parent]['CHILDS'][] = $arItem;
}

foreach ($ndSections as &$arSection) {
	$arSection['ND_ICON'] = '';
	$arSection['ND_ICON_IS_PHOTO'] = false;

	// Главный источник — поле раздела UF_ND_ICON («фото-иконка раздела»):
	// картинку заводит контент-менеджер, и та же попадает в меню каталога.
	$ndUfIcon = $arSection['UF_ND_ICON'] ?? null;
	$ndUfFileId = is_array($ndUfIcon) ? (int) ($ndUfIcon['ID'] ?? 0) : (int) $ndUfIcon;

	if ($ndUfFileId > 0) {
		$img = CFile::ResizeImageGet($ndUfFileId, ['width' => 128, 'height' => 128], BX_RESIZE_IMAGE_PROPORTIONAL, true);
		if (!empty($img['src'])) {
			$arSection['ND_ICON'] = $img['src'];
			continue;
		}
	}

	$code = trim((string) ($arSection['CODE'] ?? ''));
	if ($code !== '' && is_file($_SERVER['DOCUMENT_ROOT'].$ndIconDir.$code.'.png')) {
		$arSection['ND_ICON'] = $ndIconDir.$code.'.png';
		continue;
	}

	// PICTURE после компонента — массив (Tools::getFieldImageData), но на всякий
	// случай понимаем и «сырой» идентификатор файла.
	$fileId = is_array($arSection['PICTURE'])
		? (int) ($arSection['PICTURE']['ID'] ?? 0)
		: (int) $arSection['PICTURE'];

	if ($fileId > 0) {
		// Фото раздела широкое (600×350): вписанное в квадрат оно превращается
		// в узкую полоску, поэтому режем по центру — получается миниатюра.
		$img = CFile::ResizeImageGet($fileId, ['width' => 128, 'height' => 128], BX_RESIZE_IMAGE_EXACT, true);
		$arSection['ND_ICON'] = $img['src'] ?? '';
		$arSection['ND_ICON_IS_PHOTO'] = (bool) $arSection['ND_ICON'];
	}
}
unset($arSection);

$arResult['ND_SECTIONS'] = $ndSections;
?>
