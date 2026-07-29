<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Страница «Портфолио» нового дизайна — сетка проектов.
 *
 * Размеры из макета (Figma «Чистовик», фрейм 20517:160693): контент 1336,
 * три карточки в ряд по 429 с зазором 24, картинка со скруглением 6,
 * подпись под ней. Навигация — общий с отзывами `pagination_newdesign`,
 * компонент отдаёт её в $arResult['NAV_STRING'].
 *
 * Плашки на картинке собираются из свойств инфоблока 18:
 *  SET_BRAND  — ярлык производителя (белая плашка сверху слева);
 *  VIDEO      — «Видео»;
 *  GALLEY_BIG — по длине галереи «N фото»;
 *  REVIEW     — «Отзыв», если текст отзыва заполнен.
 */
$this->setFrameMode(true);
?>
<section class="nd-projects">
	<? if (!$arResult['ITEMS']): ?>
		<p class="nd-projects__empty">Проектов не найдено.</p>
	<? else: ?>
		<div class="nd-projects__list">
			<? foreach ($arResult['ITEMS'] as $arItem): ?>
				<?
				$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

				$pic = is_array($arItem['PREVIEW_PICTURE']) ? $arItem['PREVIEW_PICTURE'] : $arItem['DETAIL_PICTURE'];
				$src = '';
				if (is_array($pic) && $pic['ID']) {
					$img = CFile::ResizeImageGet($pic['ID'], ['width' => 858, 'height' => 544], BX_RESIZE_IMAGE_EXACT, true);
					$src = $img['src'] ?? $pic['SRC'];
				}

				$brand = $arItem['PROPERTIES']['SET_BRAND'];
				$hasVideo = !empty($arItem['PROPERTIES']['VIDEO']['VALUE']);

				$gallery = $arItem['PROPERTIES']['GALLEY_BIG']['VALUE'] ?? [];
				$photoCnt = is_array($gallery) ? count($gallery) : 0;

				// REVIEW — текстовое свойство, у HTML-варианта значение приходит массивом
				$review = $arItem['PROPERTIES']['REVIEW']['~VALUE'] ?? $arItem['PROPERTIES']['REVIEW']['VALUE'] ?? '';
				$hasReview = is_array($review) ? (trim((string) $review['TEXT']) !== '') : (trim((string) $review) !== '');
				?>
				<a class="nd-projects__item" href="<?= $arItem['DETAIL_PAGE_URL'] ?>" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
					<span class="nd-projects__pic">
						<? if ($src): ?>
							<img src="<?= $src ?>" alt="<?= htmlspecialcharsbx($arItem['NAME']) ?>" loading="lazy">
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

					<span class="nd-projects__name"><?= htmlspecialcharsbx($arItem['NAME']) ?></span>
				</a>
			<? endforeach; ?>
		</div>
	<? endif; ?>

	<? if ($arResult['NAV_STRING']): ?>
		<div class="nd-projects__nav"><?= $arResult['NAV_STRING'] ?></div>
	<? endif; ?>
</section>
