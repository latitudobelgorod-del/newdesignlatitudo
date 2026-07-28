<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Бренды на главной нового дизайна. Слайдер: на одном слайде 10 логотипов (5 в ряд, 2 ряда).
 * Разметка и размеры — по макету Figma: карточка 248x164, рамка 2px, радиус 6, промежутки 24.
 * Стили и скрипт лежат рядом (style.css, script.js) — Битрикс подключает их сам.
 */
$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
    return;
}

$perSlide = 10;                                   // 5 в ряд x 2 ряда
$slides = array_chunk($arResult['ITEMS'], $perSlide);
$allUrl = trim($arParams['ALL_URL'] ?? '') ?: SITE_DIR.'brands/';
?>
<div class="nd-brands" data-nd-brands>
	<div class="nd-brands__head">
		<h2 class="nd-brands__title"><?= $arParams['TITLE_BLOCK'] ?: 'Бренды' ?></h2>

		<div class="nd-brands__controls">
			<a class="nd-brands__all" href="<?= $allUrl ?>">Смотреть все</a>

			<? if (count($slides) > 1): ?>
				<span class="nd-brands__counter" data-nd-brands-counter>01/<?= str_pad(count($slides), 2, '0', STR_PAD_LEFT) ?></span>
				<div class="nd-brands__nav">
					<button type="button" class="nd-brands__arrow nd-brands__arrow--prev" data-nd-brands-prev aria-label="Предыдущие бренды">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
					<button type="button" class="nd-brands__arrow nd-brands__arrow--next" data-nd-brands-next aria-label="Следующие бренды">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
					</button>
				</div>
			<? endif; ?>
		</div>
	</div>

	<div class="nd-brands__viewport" data-nd-brands-viewport>
		<div class="nd-brands__track">
			<? foreach ($slides as $slide): ?>
				<div class="nd-brands__slide">
					<? foreach ($slide as $arItem): ?>
						<?
						$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

						$src = '';
						if (is_array($arItem['PREVIEW_PICTURE'])) {
							$src = $arItem['PREVIEW_PICTURE']['SRC'];
						} elseif (is_array($arItem['DETAIL_PICTURE'])) {
							$src = $arItem['DETAIL_PICTURE']['SRC'];
						}
						$link = $arItem['DETAIL_PAGE_URL'];
						$tag = $link ? 'a' : 'div';
						?>
						<<?= $tag ?> class="nd-brands__item"<?= $link ? ' href="'.$link.'"' : '' ?> id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
							<? if ($src): ?>
								<img class="nd-brands__logo" src="<?= $src ?>" alt="<?= $arItem['NAME'] ?>" title="<?= $arItem['NAME'] ?>" loading="lazy">
							<? else: ?>
								<span class="nd-brands__name"><?= $arItem['NAME'] ?></span>
							<? endif; ?>
						</<?= $tag ?>>
					<? endforeach; ?>
				</div>
			<? endforeach; ?>
		</div>
	</div>
</div>
