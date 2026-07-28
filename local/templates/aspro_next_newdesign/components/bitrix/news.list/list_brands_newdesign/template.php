<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Бренды на главной нового дизайна.
 *
 * Логотипы выводятся плоским списком, а на слайды их режет скрипт по ширине экрана:
 * 10 штук (5x2) на десктопе и 6 (3x2 / 2x3) на планшете и мобильном — как в макете.
 * Без скрипта список остаётся видимым обычной сеткой.
 * Стили и скрипт лежат рядом (style.css, script.js) — Битрикс подключает их сам.
 */
$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
    return;
}

$allUrl = trim($arParams['ALL_URL'] ?? '') ?: SITE_DIR.'brands/';
?>
<div class="nd-brands" data-nd-brands>
	<?/* Плоская разметка — раскладку задаёт grid: на десктопе всё в одну строку,
	   на мобильном кнопка «Смотреть все» уходит под слайдер во всю ширину */?>
	<h2 class="nd-brands__title"><?= $arParams['TITLE_BLOCK'] ?: 'Бренды' ?></h2>

	<a class="nd-brands__all" href="<?= $allUrl ?>">Смотреть все</a>

	<div class="nd-brands__nav" data-nd-brands-nav hidden>
		<button type="button" class="nd-brands__arrow nd-brands__arrow--prev" data-nd-brands-prev aria-label="Предыдущие бренды">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
		<button type="button" class="nd-brands__arrow nd-brands__arrow--next" data-nd-brands-next aria-label="Следующие бренды">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
	</div>

	<div class="nd-brands__viewport" data-nd-brands-viewport>
		<div class="nd-brands__track" data-nd-brands-track>
			<? foreach ($arResult['ITEMS'] as $arItem): ?>
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
	</div>
</div>
