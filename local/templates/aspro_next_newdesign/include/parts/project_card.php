<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Общая разметка карточки проекта.
 *
 * Используется двумя шаблонами: сеткой на /projects/ (list_projects_newdesign)
 * и блоком «Вдохновитесь нашими проектами» на главной
 * (list_projects_main_newdesign). Держим в одном месте, чтобы карточка
 * не разъезжалась между страницами.
 *
 * Стили карточки лежат в шаблоне list_projects_newdesign — блок главной
 * подключает их к себе явно.
 */

if (!function_exists('ndProjectCard')) {
	/**
	 * Карточка проекта: картинка с ярлыком производителя и плашками,
	 * подпись под ней.
	 *
	 * @param array  $item   элемент инфоблока «Проекты»
	 * @param string $editId id области редактирования (GetEditAreaId шаблона)
	 */
	function ndProjectCard(array $item, $editId = '')
	{
		$pic = is_array($item['PREVIEW_PICTURE']) ? $item['PREVIEW_PICTURE'] : $item['DETAIL_PICTURE'];
		$src = '';
		if (is_array($pic) && $pic['ID']) {
			$img = CFile::ResizeImageGet($pic['ID'], ['width' => 858, 'height' => 544], BX_RESIZE_IMAGE_EXACT, true);
			$src = $img['src'] ?? $pic['SRC'];
		}

		$brand = $item['PROPERTIES']['SET_BRAND'] ?? [];
		$hasVideo = !empty($item['PROPERTIES']['VIDEO']['VALUE']);

		$gallery = $item['PROPERTIES']['GALLEY_BIG']['VALUE'] ?? [];
		$photoCnt = is_array($gallery) ? count($gallery) : 0;

		// REVIEW — текстовое свойство, у HTML-варианта значение приходит массивом
		$review = $item['PROPERTIES']['REVIEW']['~VALUE'] ?? $item['PROPERTIES']['REVIEW']['VALUE'] ?? '';
		$hasReview = is_array($review) ? (trim((string) $review['TEXT']) !== '') : (trim((string) $review) !== '');
		?>
		<a class="nd-projects__item" href="<?= $item['DETAIL_PAGE_URL'] ?>"<?= $editId ? ' id="'.$editId.'"' : '' ?>>
			<span class="nd-projects__pic">
				<? if ($src): ?>
					<img src="<?= $src ?>" alt="<?= htmlspecialcharsbx($item['NAME']) ?>" loading="lazy">
				<? endif; ?>

				<? if (!empty($brand['VALUE'])): ?>
					<span class="nd-projects__brand nd-projects__brand--<?= htmlspecialcharsbx($brand['VALUE_XML_ID']) ?>"><?= htmlspecialcharsbx($brand['VALUE']) ?></span>
				<? endif; ?>

				<? if ($hasVideo || $photoCnt || $hasReview): ?>
					<span class="nd-projects__tags">
						<? if ($hasVideo): ?><span class="nd-projects__tag nd-projects__tag--video">Видео</span><? endif; ?>
						<? if ($photoCnt): ?><span class="nd-projects__tag nd-projects__tag--photo"><?= $photoCnt ?> фото</span><? endif; ?>
						<? if ($hasReview): ?><span class="nd-projects__tag nd-projects__tag--review">Отзыв</span><? endif; ?>
					</span>
				<? endif; ?>
			</span>

			<span class="nd-projects__name"><?= htmlspecialcharsbx($item['NAME']) ?></span>
		</a>
		<?
	}
}
