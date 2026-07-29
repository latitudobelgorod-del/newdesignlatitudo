<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Общая разметка карточки отзыва и попапа полного отзыва.
 *
 * Используется двумя шаблонами: списком на /company/reviews/
 * (list_reviews_newdesign) и блоком «Что говорят о нас» на главной
 * (list_reviews_main_newdesign). Держим в одном месте, чтобы карточка
 * не разъезжалась между страницами.
 *
 * Стили и скрипт карточки лежат в шаблоне list_reviews_newdesign —
 * блок главной подключает их к себе явно.
 */

if (!function_exists('ndReviewMeta')) {
	/**
	 * Строка «5 ★ оценка · 19 дек 2025 · Город».
	 * Возвращает готовый HTML: он одинаковый в карточке и в попапе.
	 *
	 * @param array $item      элемент инфоблока отзывов
	 * @param array $cityNames справочник «ID региона => название»
	 */
	function ndReviewMeta(array $item, array $cityNames = [])
	{
		$html = '';

		$rating = (float) ($item['PROPERTIES']['RATING_REVIEW']['VALUE'] ?? 0);
		if ($rating > 0) {
			$html .= '<span class="nd-reviews__mark">'
				.'<span class="nd-reviews__mark-num">'.(int) round($rating).'</span>'
				.'<svg class="nd-reviews__star" width="19" height="19" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
				.'<path fill="#ffcc02" d="M12 17.27 5.82 21l1.64-7.03L2 9.24l7.19-.61L12 2l2.81 6.63 7.19.61-5.46 4.73L18.18 21z"/></svg>'
				.'<span class="nd-reviews__mark-word">оценка</span></span>';
		}

		$raw = $item['PROPERTIES']['DATE_REVIEW']['VALUE'] ?? '';
		$ts = $raw ? MakeTimeStamp($raw) : 0;
		if (!$ts && !empty($item['ACTIVE_FROM'])) {
			$ts = MakeTimeStamp($item['ACTIVE_FROM']);
		}
		if ($ts) {
			$html .= '<span class="nd-reviews__date">'.htmlspecialcharsbx(FormatDate('j M Y', $ts)).'</span>';
		}

		// город отзыва — меткой; название берём из справочника регионов
		$cityId = (int) ($item['PROPERTIES']['CITY_REVIEW']['VALUE'] ?? 0);
		$cityName = $cityId ? ($cityNames[$cityId] ?? '') : '';
		if ($cityName) {
			$html .= '<span class="nd-reviews__city">'
				.'<svg width="14" height="14" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
				.'<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
				.' d="M12 21s7-5.686 7-11a7 7 0 1 0-14 0c0 5.314 7 11 7 11"/>'
				.'<circle cx="12" cy="10" r="2.5" fill="none" stroke="currentColor" stroke-width="2"/></svg>'
				.htmlspecialcharsbx($cityName).'</span>';
		}

		return $html;
	}
}

if (!function_exists('ndReviewPhotos')) {
	/** ID картинок отзыва в том порядке, в каком их отдал инфоблок. */
	function ndReviewPhotos(array $item)
	{
		$v = $item['PROPERTIES']['PHOTOS_REVIEW']['VALUE'] ?? [];
		if (!is_array($v)) {
			$v = $v ? [$v] : [];
		}
		return array_values(array_filter(array_map('intval', $v)));
	}
}

if (!function_exists('ndReviewThumb')) {
	/** Превью фиксированного квадрата (×2 для retina). */
	function ndReviewThumb($fileId, $size)
	{
		$img = CFile::ResizeImageGet($fileId, ['width' => $size * 2, 'height' => $size * 2], BX_RESIZE_IMAGE_EXACT, true);
		return $img['src'] ?? CFile::GetPath($fileId);
	}
}

if (!function_exists('ndReviewCard')) {
	/**
	 * Карточка отзыва вместе со скрытым <template> для попапа.
	 *
	 * @param array  $item      элемент инфоблока отзывов
	 * @param array  $cityNames справочник «ID региона => название»
	 * @param string $editId    id области редактирования (GetEditAreaId шаблона)
	 */
	function ndReviewCard(array $item, array $cityNames = [], $editId = '')
	{
		$photos = ndReviewPhotos($item);
		$meta = ndReviewMeta($item, $cityNames);
		// news.list уже прогнал текст через TxtToHTML: переносы строк и &nbsp;
		// в нём проставлены, экранировать второй раз нельзя
		$textHtml = (string) $item['DETAIL_TEXT'];
		?>
		<article class="nd-reviews__card"<?= $editId ? ' id="'.$editId.'"' : '' ?>>
			<header class="nd-reviews__head">
				<h3 class="nd-reviews__name"><?= htmlspecialcharsbx($item['NAME']) ?></h3>
				<? if ($meta): ?><div class="nd-reviews__meta"><?= $meta ?></div><? endif; ?>
			</header>

			<? if ($photos): ?>
				<?/* Лента фото: прокручивается вбок, справа — затемнение со стрелкой
				     (появляется, только когда есть что листать; ставит его скрипт).
				     Клик по фото открывает оригинал во весь экран через FancyBox. */?>
				<div class="nd-reviews__photos" data-nd-photos>
					<div class="nd-reviews__track">
						<? foreach ($photos as $fileId): ?>
							<a class="nd-reviews__photo" href="<?= CFile::GetPath($fileId) ?>" data-fancybox="review-<?= $item['ID'] ?>">
								<img src="<?= ndReviewThumb($fileId, 80) ?>" alt="" loading="lazy" width="80" height="80">
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
			     как есть, поэтому никаких данных в data-атрибутах и сборки на JS. */?>
			<template class="nd-reviews__full">
				<div class="nd-modal__title"><?= htmlspecialcharsbx($item['NAME']) ?></div>
				<? if ($meta): ?><div class="nd-modal__meta"><?= $meta ?></div><? endif; ?>
				<? if ($photos): ?>
					<div class="nd-modal__photos">
						<? foreach ($photos as $fileId): ?>
							<a class="nd-modal__photo" href="<?= CFile::GetPath($fileId) ?>" data-fancybox="review-popup-<?= $item['ID'] ?>">
								<img src="<?= ndReviewThumb($fileId, 106) ?>" alt="" loading="lazy">
							</a>
						<? endforeach; ?>
					</div>
				<? endif; ?>
				<div class="nd-modal__text"><?= $textHtml ?></div>
			</template>
		</article>
		<?
	}
}

if (!function_exists('ndReviewModal')) {
	/** Одно окно на всю страницу — наполняется содержимым карточки при открытии. */
	function ndReviewModal()
	{
		if (defined('ND_REVIEW_MODAL_PRINTED')) {
			return; // на странице может быть несколько блоков с отзывами
		}
		define('ND_REVIEW_MODAL_PRINTED', true);
		?>
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
		<?
	}
}
