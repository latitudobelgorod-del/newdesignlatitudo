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

// разметка карточки и попапа — общая со страницей главной
require_once $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/parts/review_card.php';
?>
<?
/* ---------- панель фильтров ----------
   Оформление как на странице проектов: тумблер, выпадающие списки и сброс.
   Раскрытие списков — на <details>, чтобы работало и без JS; скрипт только
   отправляет форму сразу после выбора и закрывает чужие списки. */
$ndF = is_array($arParams['ND_FILTER']) ? $arParams['ND_FILTER'] : [];
$ndStat = $ndF['stat'] ?? ['cities' => [], 'cityNames' => [], 'rate' => [], 'photo' => []];
$ndSelCity = array_flip($ndF['city'] ?? []);
$ndSelRate = array_flip($ndF['rate'] ?? []);
$ndRateTitles = ['good' => 'Хорошие', 'neutral' => 'Нейтральные', 'bad' => 'Плохие'];

// города сортируем по числу отзывов — как в макете, от крупных к мелким
$ndCityList = $ndStat['cities'] ?? [];
arsort($ndCityList);

$ndResetUrl = $APPLICATION->GetCurPage(false);

/* Общий скрипт фильтра и кнопки «Показать ещё» — тот же, что на портфолио.
   Подключаем тегом здесь: компонент выводится, когда <head> уже отдан. */
if (!defined('ND_UI_JS')) {
	define('ND_UI_JS', true);
	$ndUi = SITE_TEMPLATE_PATH.'/js/newdesign-ui.js';
	$ndUiAbs = $_SERVER['DOCUMENT_ROOT'].$ndUi;
	?><script src="<?= $ndUi ?><?= is_file($ndUiAbs) ? '?'.filemtime($ndUiAbs) : '' ?>"></script><?
}
?>
<?/* H1 страницы. В макете он есть (десктоп 21289:57101 — 52/57.2 800,
   мобильный 21289:60504 — 30/33), а на странице не выводился вовсе:
   шаблон комплексного компонента печатает его только для блоговых разделов.
   Текст берём настоящий — из SetTitle() страницы /company/reviews/index.php,
   а не из макета. ShowTitle() тут звать нельзя: это отложенная функция,
   внутри шаблона компонента она оставляет маркер вместо текста. */?>
<? if ($ndTitle = $APPLICATION->GetTitle(false)): ?>
	<h1 class="nd-reviews__h1"><?= $ndTitle ?></h1>
<? endif; ?>

<form class="nd-filter" method="get" action="<?= $ndResetUrl ?>">
	<label class="nd-filter__toggle<?= !empty($ndF['photo']) ? ' is-on' : '' ?>">
		<input type="checkbox" name="photo" value="y"<?= !empty($ndF['photo']) ? ' checked' : '' ?>>
		<span class="nd-filter__switch" aria-hidden="true"></span>
		<span class="nd-filter__toggle-text">С фото</span>
	</label>

	<details class="nd-filter__drop">
		<summary class="nd-filter__head">
			<span>Город<?= $ndSelCity ? ' ('.count($ndSelCity).')' : '' ?></span>
			<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
			</svg>
		</summary>
		<div class="nd-filter__panel">
			<? foreach ($ndCityList as $cityId => $cnt): ?>
				<label class="nd-filter__opt">
					<input type="checkbox" name="city[]" value="<?= $cityId ?>"<?= isset($ndSelCity[$cityId]) ? ' checked' : '' ?>>
					<span class="nd-filter__box" aria-hidden="true"></span>
					<span class="nd-filter__opt-name"><?= htmlspecialcharsbx($ndStat['cityNames'][$cityId] ?? ('#'.$cityId)) ?></span>
					<span class="nd-filter__opt-cnt"><?= $cnt ?></span>
				</label>
			<? endforeach; ?>
			<button type="submit" class="nd-filter__apply">Применить</button>
		</div>
	</details>

	<details class="nd-filter__drop">
		<summary class="nd-filter__head">
			<span>Оценка<?= $ndSelRate ? ' ('.count($ndSelRate).')' : '' ?></span>
			<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
				<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
			</svg>
		</summary>
		<div class="nd-filter__panel">
			<? foreach ($ndRateTitles as $key => $title): ?>
				<label class="nd-filter__opt">
					<input type="checkbox" name="rate[]" value="<?= $key ?>"<?= isset($ndSelRate[$key]) ? ' checked' : '' ?>>
					<span class="nd-filter__box" aria-hidden="true"></span>
					<span class="nd-filter__opt-name"><?= $title ?></span>
					<span class="nd-filter__opt-cnt"><?= (int) ($ndStat['rate'][$key] ?? 0) ?></span>
				</label>
			<? endforeach; ?>
			<button type="submit" class="nd-filter__apply">Применить</button>
		</div>
	</details>

	<? if (!empty($ndF['active'])): ?>
		<a class="nd-filter__reset" href="<?= $ndResetUrl ?>">Сбросить фильтры</a>
	<? endif; ?>
</form>

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
			<p class="nd-reviews__empty"><?= !empty($ndF['active']) ? 'По выбранным фильтрам отзывов нет.' : 'Отзывов пока нет.' ?></p>
		<? else: ?>
			<div class="nd-reviews__list">
				<? foreach ($arResult['ITEMS'] as $arItem): ?>
					<?
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

					ndReviewCard($arItem, $ndStat['cityNames'] ?? [], $this->GetEditAreaId($arItem['ID']));
					?>
				<? endforeach; ?>
			</div>
		<? endif; ?>

		<? if ($arResult['NAV_STRING']): ?>
			<div class="nd-reviews__nav"><?= $arResult['NAV_STRING'] ?></div>
		<? endif; ?>
	</div>
</section>

<? ndReviewModal(); ?>
