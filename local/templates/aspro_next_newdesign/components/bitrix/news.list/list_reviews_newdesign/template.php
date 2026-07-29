<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Страница «Отзывы» нового дизайна — список карточек.
 *
 * Размеры и типографика сняты из макета (Figma «Чистовик»): десктоп — фрейм
 * 21289:57094, мобильный — 21289:60498 (в макете он ошибочно назван «Статьи»),
 * карточка — компонент card_Отзыв, попап — 21289:55578 (600) и 21289:56020 (360).
 *
 * Данные — поля импорта отзывов с Яндекс.Карт в инфоблоке 33:
 * DATE_REVIEW (дата), RATING_REVIEW (оценка), PHOTOS_REVIEW (фото), CITY_REVIEW (город).
 *
 * Полный текст отзыва и все фото открываются в попапе: у каждой карточки лежит
 * скрытый <template> с готовой разметкой, скрипт переносит её в единое окно.
 * Без JS карточка остаётся читаемой, просто текст обрезан по высоте.
 */
$this->setFrameMode(true);

/** @var array $arParams @var array $arResult @var CBitrixComponentTemplate $this */

$ndIblockId = (int) $arParams['IBLOCK_ID'];

/* ---------- левая колонка: общий рейтинг и число отзывов ----------
   Считаем по всему инфоблоку, а не по текущей странице. Запрос лёгкий
   (одно свойство), и он всё равно попадает в кэш компонента. */
$ndSum = 0;
$ndRated = 0;
$ndCounted = 0;

if ($ndIblockId && CModule::IncludeModule('iblock')) {
	$rsRating = CIBlockElement::GetList(
		[],
		['IBLOCK_ID' => $ndIblockId, 'ACTIVE' => 'Y'],
		false,
		false,
		['ID', 'PROPERTY_RATING_REVIEW']
	);
	while ($row = $rsRating->Fetch()) {
		$ndCounted++;
		$v = (float) $row['PROPERTY_RATING_REVIEW_VALUE'];
		if ($v > 0) {
			$ndSum += $v;
			$ndRated++;
		}
	}
}
$ndAvg = $ndRated ? $ndSum / $ndRated : 0;

// Число отзывов берём у навигации — она знает про фильтр списка; если её нет
// (навигация отключена), падаем на пересчёт по инфоблоку, а в крайнем случае —
// на количество элементов текущей страницы.
$ndTotal = isset($arResult['NAV_RESULT']) && is_object($arResult['NAV_RESULT'])
	? (int) $arResult['NAV_RESULT']->NavRecordCount
	: ($ndCounted ?: count($arResult['ITEMS']));

/** «42 отзыва» / «1 отзыв» / «5 отзывов» */
$ndPlural = function ($n, array $forms) {
	$n = abs($n) % 100;
	$n1 = $n % 10;
	if ($n > 10 && $n < 20) return $forms[2];
	if ($n1 > 1 && $n1 < 5) return $forms[1];
	if ($n1 == 1) return $forms[0];
	return $forms[2];
};

/* ---------- кнопка «Оставить отзыв» ----------
   Ведёт на карточку организации в Яндекс.Картах того региона, в котором сейчас
   находится посетитель. Ссылки лежат в пользовательском поле UF_LINK_MAP
   разделов этого же инфоблока (разделы названы по городам). Если у региона
   ссылки нет — ведём на Москву, как договорились. */
$ndAddButtonText = trim((string) $arParams['ADD_REVIEW_BUTTON']) ?: 'Оставить отзыв';

$ndMapLinks = [];
if ($ndIblockId) {
	$rsSect = CIBlockSection::GetList([], ['IBLOCK_ID' => $ndIblockId], false, ['ID', 'NAME', 'UF_LINK_MAP']);
	while ($sect = $rsSect->Fetch()) {
		if (!empty($sect['UF_LINK_MAP'])) {
			$ndMapLinks[$sect['NAME']] = $sect['UF_LINK_MAP'];
		}
	}
}

$ndRegionName = '';
if (class_exists('CNextRegionality')) {
	$ndRegion = CNextRegionality::getCurrentRegion();
	if (is_array($ndRegion) && !empty($ndRegion['NAME'])) {
		$ndRegionName = (string) $ndRegion['NAME'];
	}
}

$ndMapLink = $ndMapLinks[$ndRegionName] ?? ($ndMapLinks['Москва'] ?? '');
$ndShowAddButton = $arParams['SHOW_ADD_REVIEW_BUTTON'] !== 'N' && $ndMapLink !== '';

/** Звезда рядом с оценкой — иконка из макета (ico/star), заливка #ffcc02. */
$ndStar = '<svg class="nd-reviews__star" width="19" height="19" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
	.'<path fill="#ffcc02" d="M12 17.27 5.82 21l1.64-7.03L2 9.24l7.19-.61L12 2l2.81 6.63 7.19.61-5.46 4.73L18.18 21z"/></svg>';

/** Дата отзыва в виде «19 дек 2025». */
$ndDate = function (array $item) {
	$raw = $item['PROPERTIES']['DATE_REVIEW']['VALUE'] ?? '';
	$ts = $raw ? MakeTimeStamp($raw) : 0;
	if (!$ts && !empty($item['ACTIVE_FROM'])) {
		$ts = MakeTimeStamp($item['ACTIVE_FROM']);
	}
	return $ts ? FormatDate('j M Y', $ts) : '';
};

/** ID картинок отзыва в том порядке, в каком их отдал инфоблок. */
$ndPhotos = function (array $item) {
	$v = $item['PROPERTIES']['PHOTOS_REVIEW']['VALUE'] ?? [];
	if (!is_array($v)) {
		$v = $v ? [$v] : [];
	}
	return array_values(array_filter(array_map('intval', $v)));
};

/** Превью фиксированного квадрата: в карточке 80×80, в попапе 106×106 (×2 для retina). */
$ndThumb = function ($fileId, $size) {
	$img = CFile::ResizeImageGet(
		$fileId,
		['width' => $size * 2, 'height' => $size * 2],
		BX_RESIZE_IMAGE_EXACT,
		true
	);
	return $img['src'] ?? CFile::GetPath($fileId);
};

/** Оригинал фото — его открывает FancyBox по клику. */
$ndFull = function ($fileId) {
	return CFile::GetPath($fileId);
};
?>
<section class="nd-reviews">
	<aside class="nd-reviews__summary">
		<div class="nd-reviews__summary-box">
			<div class="nd-reviews__avg">Общий рейтинг: <?= number_format($ndAvg, 2, '.', '') ?></div>
			<div class="nd-reviews__total"><?= $ndTotal ?> <?= $ndPlural($ndTotal, ['отзыв', 'отзыва', 'отзывов']) ?></div>
			<? if ($ndShowAddButton): ?>
				<a class="nd-reviews__add" href="<?= htmlspecialcharsbx($ndMapLink) ?>"
					target="_blank" rel="nofollow noopener"><?= htmlspecialcharsbx($ndAddButtonText) ?></a>
			<? endif; ?>
		</div>
	</aside>

	<div class="nd-reviews__main">
		<? if (!$arResult['ITEMS']): ?>
			<p class="nd-reviews__empty">Отзывов пока нет.</p>
		<? else: ?>
			<div class="nd-reviews__list">
				<? foreach ($arResult['ITEMS'] as $arItem): ?>
					<?
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

					$photos = $ndPhotos($arItem);
					$photoCnt = count($photos);
					$rating = (float) ($arItem['PROPERTIES']['RATING_REVIEW']['VALUE'] ?? 0);
					$dateText = $ndDate($arItem);
					$author = $arItem['NAME'];
					// news.list уже прогнал текст через TxtToHTML: переносы строк и &nbsp;
					// в нём проставлены. Экранировать второй раз нельзя — сущности
					// вылезут в вёрстку как есть, поэтому выводим как штатные шаблоны.
					$textHtml = (string) $arItem['DETAIL_TEXT'];

					$metaHtml = '';
					if ($rating > 0) {
						$metaHtml .= '<span class="nd-reviews__mark">'
							.'<span class="nd-reviews__mark-num">'.(int) round($rating).'</span>'
							.$ndStar
							.'<span class="nd-reviews__mark-word">оценка</span></span>';
					}
					if ($dateText) {
						$metaHtml .= '<span class="nd-reviews__date">'.htmlspecialcharsbx($dateText).'</span>';
					}
					?>
					<article class="nd-reviews__card" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
						<header class="nd-reviews__head">
							<h3 class="nd-reviews__name"><?= htmlspecialcharsbx($author) ?></h3>
							<? if ($metaHtml): ?><div class="nd-reviews__meta"><?= $metaHtml ?></div><? endif; ?>
						</header>

						<? if ($photoCnt): ?>
							<?/* Лента фото: прокручивается вбок, справа — затемнение со стрелкой
							     (появляется, только когда есть что листать; ставит его скрипт).
							     Клик по фото открывает оригинал во весь экран через FancyBox. */?>
							<div class="nd-reviews__photos" data-nd-photos>
								<div class="nd-reviews__track">
									<? foreach ($photos as $fileId): ?>
										<a class="nd-reviews__photo" href="<?= $ndFull($fileId) ?>" data-fancybox="review-<?= $arItem['ID'] ?>">
											<img src="<?= $ndThumb($fileId, 80) ?>" alt="" loading="lazy" width="80" height="80">
										</a>
									<? endforeach; ?>
								</div>
								<button type="button" class="nd-reviews__scroll" data-nd-photos-next aria-label="Следующие фото">
									<svg width="40" height="40" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
										<path fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/>
									</svg>
								</button>
							</div>
						<? endif; ?>

						<div class="nd-reviews__text"><?= $textHtml ?></div>

						<button type="button" class="nd-reviews__more" data-nd-review-open>Читать весь отзыв</button>

						<?/* Готовая разметка для попапа: скрипт переносит её в общее окно
						     как есть, поэтому никаких данных в data-атрибутах и экранирования на JS. */?>
						<template class="nd-reviews__full">
							<div class="nd-modal__title"><?= htmlspecialcharsbx($author) ?></div>
							<? if ($metaHtml): ?><div class="nd-modal__meta"><?= $metaHtml ?></div><? endif; ?>
							<? if ($photoCnt): ?>
								<div class="nd-modal__photos">
									<? foreach ($photos as $fileId): ?>
										<a class="nd-modal__photo" href="<?= $ndFull($fileId) ?>" data-fancybox="review-popup-<?= $arItem['ID'] ?>">
											<img src="<?= $ndThumb($fileId, 106) ?>" alt="" loading="lazy">
										</a>
									<? endforeach; ?>
								</div>
							<? endif; ?>
							<div class="nd-modal__text"><?= $textHtml ?></div>
						</template>
					</article>
				<? endforeach; ?>
			</div>
		<? endif; ?>

		<? if ($arResult['NAV_STRING']): ?>
			<div class="nd-reviews__nav"><?= $arResult['NAV_STRING'] ?></div>
		<? endif; ?>
	</div>
</section>

<?/* Одно окно на всю страницу — наполняется содержимым карточки при открытии. */?>
<div class="nd-modal" id="nd-review-modal" hidden>
	<div class="nd-modal__overlay" data-nd-review-close></div>
	<div class="nd-modal__win" role="dialog" aria-modal="true" aria-label="Отзыв">
		<div class="nd-modal__head">
			<div class="nd-modal__head-content"></div>
			<button type="button" class="nd-modal__close" data-nd-review-close aria-label="Закрыть">
				<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
					<path fill="none" stroke="#525264" stroke-width="2" stroke-linecap="round" d="m6 6 12 12M18 6 6 18"/>
				</svg>
			</button>
		</div>
		<div class="nd-modal__body"></div>
		<div class="nd-modal__action">
			<button type="button" class="nd-modal__btn" data-nd-review-close>Закрыть</button>
		</div>
	</div>
</div>
