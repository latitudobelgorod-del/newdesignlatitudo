<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Сетка логотипов на странице «Производители и марки» /brands/.
 *
 * Размеры из макета Figma («Чистовик», фрейм «Производители» 20738:55460):
 * контейнер 1336, четыре карточки по 316×208 с зазором 24, рамка
 * rgba(82,82,100,.15) 2px, радиус 6, логотип по центру.
 * Стили — в css/newdesign.css рядом с остальными внутренними страницами.
 *
 * Это не слайдер с главной (.nd-brands): там карточки режутся на слайды и
 * ведут на detail только при наличии ссылки. Здесь простая сетка на всю
 * страницу, поэтому классы свои.
 */
$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
	return;
}
?>
<div class="nd-brandlist">
	<? foreach ($arResult['ITEMS'] as $arItem): ?>
		<?
		$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

		$src = $arItem['PREVIEW_PICTURE']['SRC'] ?? ($arItem['DETAIL_PICTURE']['SRC'] ?? '');
		$link = $arParams['SHOW_DETAIL_LINK'] !== 'N' ? $arItem['DETAIL_PAGE_URL'] : '';
		$tag = $link ? 'a' : 'div';
		?>
		<<?= $tag ?> class="nd-brandlist__item"<?= $link ? ' href="'.$link.'"' : '' ?> id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
			<? if ($src): ?>
				<img class="nd-brandlist__logo" src="<?= $src ?>" alt="<?= $arItem['NAME'] ?>" title="<?= $arItem['NAME'] ?>" loading="lazy">
			<? else: ?>
				<span class="nd-brandlist__name"><?= $arItem['NAME'] ?></span>
			<? endif; ?>
		</<?= $tag ?>>
	<? endforeach; ?>
</div>

<? if ($arParams['DISPLAY_BOTTOM_PAGER'] === 'Y' && $arResult['NAV_STRING']): ?>
	<div class="nd-brandlist__nav"><?= $arResult['NAV_STRING'] ?></div>
<? endif; ?>
