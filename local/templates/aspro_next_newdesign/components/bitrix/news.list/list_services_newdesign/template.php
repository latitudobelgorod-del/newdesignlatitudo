<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Сетка услуг на /services/ в новом дизайне.
 *
 * Размеры из макета Figma («Чистовик», фрейм «Услуги» 20669:39737):
 * колонка 1008, три карточки по 320 с зазором 24, картинка 320×197
 * со скруглением 4, плашка «Услуга» поверх неё в левом нижнем углу,
 * название 18/22 700 через 12 под картинкой.
 * Стили — в css/newdesign.css.
 */
$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
	return;
}
?>
<div class="nd-services">
	<? foreach ($arResult['ITEMS'] as $arItem): ?>
		<?
		$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

		// news.list раскладывает поля по FIELDS, если задан FIELD_CODE, но
		// PREVIEW_PICTURE приезжает и в корне элемента — берём то, что есть.
		$pic = $arItem['PREVIEW_PICTURE'] ?: ($arItem['FIELDS']['PREVIEW_PICTURE'] ?? null);
		if (!$pic) {
			$pic = $arItem['DETAIL_PICTURE'] ?: ($arItem['FIELDS']['DETAIL_PICTURE'] ?? null);
		}

		$src = '';
		if (is_array($pic) && (int) $pic['ID'] > 0) {
			$img = CFile::ResizeImageGet((int) $pic['ID'], ['width' => 640, 'height' => 394], BX_RESIZE_IMAGE_EXACT, true);
			$src = $img['src'] ?? $pic['SRC'];
		}
		?>
		<a class="nd-services__item" href="<?= $arItem['DETAIL_PAGE_URL'] ?>" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
			<span class="nd-services__pic">
				<? if ($src): ?>
					<img src="<?= $src ?>" alt="<?= $arItem['NAME'] ?>" loading="lazy">
				<? endif; ?>
				<span class="nd-services__badge">Услуга</span>
			</span>
			<span class="nd-services__name"><?= $arItem['NAME'] ?></span>
		</a>
	<? endforeach; ?>
</div>

<? if ($arParams['DISPLAY_BOTTOM_PAGER'] === 'Y' && $arResult['NAV_STRING']): ?>
	<div class="nd-services__nav"><?= $arResult['NAV_STRING'] ?></div>
<? endif; ?>
