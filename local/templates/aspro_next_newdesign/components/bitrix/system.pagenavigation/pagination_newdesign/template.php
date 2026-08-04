<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Постраничная навигация нового дизайна: стрелки, номера страниц и кнопка
 * «Показать ещё» справа. Разметка по макету (Figma «Чистовик», фрейм 21289:57094,
 * компонент Pagination): ячейка 48×48, радиус 4, активная — красная #c60000.
 *
 * «Показать ещё» — обычная ссылка на следующую страницу; скрипт списка
 * (list_reviews_newdesign/script.js) перехватывает клик и дописывает карточки
 * в конец, а без JS остаётся рабочий переход.
 *
 * Сборка адресов повторяет штатный шаблон .default, иначе ломается SEF
 * и сохранение номера страницы (bSavePage).
 */
$strNavQueryString = $arResult['NavQueryString'] != '' ? $arResult['NavQueryString'].'&amp;' : '';
$strNavQueryStringFull = $arResult['NavQueryString'] != '' ? '?'.$arResult['NavQueryString'] : '';

/** Адрес страницы $page с учётом bSavePage. */
$ndPageUrl = function ($page) use ($arResult, $strNavQueryString, $strNavQueryStringFull) {
	if ($page == 1 && $arResult['bSavePage'] !== true) {
		return $arResult['sUrlPath'].$strNavQueryStringFull;
	}
	return $arResult['sUrlPath'].'?'.$strNavQueryString.'PAGEN_'.$arResult['NavNum'].'='.$page;
};

$current = (int) $arResult['NavPageNomer'];
$count = (int) $arResult['NavPageCount'];
if ($count < 2 && $arResult['NavShowAlways'] === false) {
	return;
}

// Окно номеров считает сам компонент; по краям при необходимости
// доклеиваем первую и последнюю страницу с многоточием.
$start = (int) $arResult['nStartPage'];
$end = (int) $arResult['nEndPage'];

$arrow = function ($dir) {
	$d = $dir === 'prev' ? 'm15 6-6 6 6 6' : 'm9 6 6 6-6 6';
	return '<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
		.'<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="'.$d.'"/></svg>';
};
?>
<nav class="nd-pager" aria-label="Страницы">
	<div class="nd-pager__pages">
		<? if ($current > 1): ?>
			<a class="nd-pager__arrow" href="<?= $ndPageUrl($current - 1) ?>" rel="prev" aria-label="Предыдущая страница"><?= $arrow('prev') ?></a>
		<? else: ?>
			<span class="nd-pager__arrow nd-pager__arrow--off" aria-hidden="true"><?= $arrow('prev') ?></span>
		<? endif; ?>

		<? if ($start > 1): ?>
			<a class="nd-pager__page" href="<?= $ndPageUrl(1) ?>">1</a>
			<? if ($start > 2): ?><span class="nd-pager__gap">…</span><? endif; ?>
		<? endif; ?>

		<? for ($i = $start; $i <= $end; $i++): ?>
			<? if ($i == $current): ?>
				<span class="nd-pager__page nd-pager__page--current" aria-current="page"><?= $i ?></span>
			<? else: ?>
				<a class="nd-pager__page" href="<?= $ndPageUrl($i) ?>"><?= $i ?></a>
			<? endif; ?>
		<? endfor; ?>

		<? if ($end < $count): ?>
			<? if ($end < $count - 1): ?><span class="nd-pager__gap">…</span><? endif; ?>
			<a class="nd-pager__page" href="<?= $ndPageUrl($count) ?>"><?= $count ?></a>
		<? endif; ?>

		<? if ($current < $count): ?>
			<a class="nd-pager__arrow" href="<?= $ndPageUrl($current + 1) ?>" rel="next" aria-label="Следующая страница"><?= $arrow('next') ?></a>
		<? else: ?>
			<span class="nd-pager__arrow nd-pager__arrow--off" aria-hidden="true"><?= $arrow('next') ?></span>
		<? endif; ?>
	</div>

	<? if ($current < $count): ?>
		<a class="nd-pager__more" href="<?= $ndPageUrl($current + 1) ?>" data-nd-pager-more>Показать еще</a>
	<? endif; ?>
</nav>
