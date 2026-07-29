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

// разметка карточки — общая с блоком «Вдохновитесь нашими проектами» на главной
require_once $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/parts/project_card.php';

/* ---------- панель фильтров ----------
   Оформление общее с фильтром отзывов (классы .nd-filter*, стили переехали
   в css/newdesign.css). Раскрытие списков — на <details>, чтобы работало
   и без JS; скрипт лишь отправляет форму сразу после выбора. */
$ndF = is_array($arParams['ND_FILTER']) ? $arParams['ND_FILTER'] : [];
$ndSelColor = array_flip($ndF['color'] ?? []);
$ndSelBrand = array_flip($ndF['brand'] ?? []);
$ndResetUrl = $APPLICATION->GetCurPage(false);

/** Кружки у названий цветов — из макета, по XML_ID справочника. */
$ndColorDots = [
	'i3EfDm7T' => '#ff9500', // Яркий
	'x020vRDl' => '#8b5a2b', // Коричневый
	'S67Rr1nK' => '#9a9a9a', // Серый
	'fYCKV9cP' => '#1a1a1a', // Черный
	'56F41bIv' => '#e8dcc8', // Светлый
	'sm1J8msp' => '#ffffff', // Белый
];

$ndChevron = '<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
	.'<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>';

/* Общий скрипт фильтра и кнопки «Показать ещё». Подключаем тегом здесь:
   компонент выводится, когда <head> уже отдан, и AddHeadScript туда не попадёт. */
if (!defined('ND_UI_JS')) {
	define('ND_UI_JS', true);
	$ndUi = SITE_TEMPLATE_PATH.'/js/newdesign-ui.js';
	$ndUiAbs = $_SERVER['DOCUMENT_ROOT'].$ndUi;
	?><script src="<?= $ndUi ?><?= is_file($ndUiAbs) ? '?'.filemtime($ndUiAbs) : '' ?>"></script><?
}
?>
<form class="nd-filter" method="get" action="<?= $ndResetUrl ?>">
	<label class="nd-filter__toggle">
		<input type="checkbox" name="review" value="y"<?= !empty($ndF['review']) ? ' checked' : '' ?>>
		<span class="nd-filter__switch" aria-hidden="true"></span>
		<span class="nd-filter__toggle-text">Есть отзыв</span>
	</label>

	<label class="nd-filter__toggle">
		<input type="checkbox" name="video" value="y"<?= !empty($ndF['video']) ? ' checked' : '' ?>>
		<span class="nd-filter__switch" aria-hidden="true"></span>
		<span class="nd-filter__toggle-text">Есть видео</span>
	</label>

	<? if (!empty($ndF['colorOptions'])): ?>
		<details class="nd-filter__drop">
			<summary class="nd-filter__head">
				<span>Цвет<?= $ndSelColor ? ' ('.count($ndSelColor).')' : '' ?></span><?= $ndChevron ?>
			</summary>
			<div class="nd-filter__panel">
				<? foreach ($ndF['colorOptions'] as $xmlId => $name): ?>
					<label class="nd-filter__opt">
						<input type="checkbox" name="color[]" value="<?= htmlspecialcharsbx($xmlId) ?>"<?= isset($ndSelColor[$xmlId]) ? ' checked' : '' ?>>
						<span class="nd-filter__box" aria-hidden="true"></span>
						<? if (isset($ndColorDots[$xmlId])): ?>
							<span class="nd-filter__dot" style="background: <?= $ndColorDots[$xmlId] ?>" aria-hidden="true"></span>
						<? endif; ?>
						<span class="nd-filter__opt-name"><?= htmlspecialcharsbx($name) ?></span>
					</label>
				<? endforeach; ?>
				<button type="submit" class="nd-filter__apply">Применить</button>
			</div>
		</details>
	<? endif; ?>

	<? if (!empty($ndF['brandOptions'])): ?>
		<details class="nd-filter__drop">
			<summary class="nd-filter__head">
				<span>Бренд<?= $ndSelBrand ? ' ('.count($ndSelBrand).')' : '' ?></span><?= $ndChevron ?>
			</summary>
			<div class="nd-filter__panel">
				<? foreach ($ndF['brandOptions'] as $enumId => $name): ?>
					<label class="nd-filter__opt">
						<input type="checkbox" name="brand[]" value="<?= (int) $enumId ?>"<?= isset($ndSelBrand[$enumId]) ? ' checked' : '' ?>>
						<span class="nd-filter__box" aria-hidden="true"></span>
						<span class="nd-filter__opt-name"><?= htmlspecialcharsbx($name) ?></span>
					</label>
				<? endforeach; ?>
				<button type="submit" class="nd-filter__apply">Применить</button>
			</div>
		</details>
	<? endif; ?>

	<? if (!empty($ndF['active'])): ?>
		<a class="nd-filter__reset" href="<?= $ndResetUrl ?>">Сбросить фильтры</a>
	<? endif; ?>
</form>

<section class="nd-projects">
	<? if (!$arResult['ITEMS']): ?>
		<p class="nd-projects__empty"><?= !empty($ndF['active']) ? 'По выбранным фильтрам проектов нет.' : 'Проектов не найдено.' ?></p>
	<? else: ?>
		<div class="nd-projects__list">
			<? foreach ($arResult['ITEMS'] as $arItem): ?>
				<?
				$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

					ndProjectCard($arItem, $this->GetEditAreaId($arItem['ID']));
					?>
			<? endforeach; ?>
		</div>
	<? endif; ?>

	<? if ($arResult['NAV_STRING']): ?>
		<div class="nd-projects__nav"><?= $arResult['NAV_STRING'] ?></div>
	<? endif; ?>
</section>
