<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Вызов списка отзывов для нового дизайна.
 *
 * Отличия от версии aspro_next:
 *  - шаблон вывода `list_reviews_newdesign` вместо `front_reviews`;
 *  - шаблон навигации жёстко `pagination_newdesign` (страница /company/reviews/index.php
 *    лежит в корне сайта, вне git, и общая для обоих дизайнов — параметр оттуда
 *    приходит как «.default», поэтому переопределяем здесь);
 *  - к списку свойств принудительно добавлены поля импорта отзывов с ЯКарт
 *    (DATE_REVIEW, RATING_REVIEW, PHOTOS_REVIEW, CITY_REVIEW) — на странице
 *    в LIST_PROPERTY_CODE их нет, а карточке нового дизайна они нужны все.
 */
$arNdReviewProps = array_values(array_unique(array_filter(array_merge(
	is_array($arParams['LIST_PROPERTY_CODE']) ? $arParams['LIST_PROPERTY_CODE'] : [],
	['DATE_REVIEW', 'RATING_REVIEW', 'PHOTOS_REVIEW', 'CITY_REVIEW']
))));

$arNdReviewFields = array_values(array_unique(array_filter(array_merge(
	is_array($arParams['LIST_FIELD_CODE']) ? $arParams['LIST_FIELD_CODE'] : [],
	['NAME', 'DETAIL_TEXT', 'DATE_ACTIVE_FROM']
))));

/* ======================= фильтры страницы отзывов =======================
   Состояние держим в GET: city (ID региона), rate (good|neutral|bad),
   photo (y|n). Счётчики для чипов считаем здесь же одним проходом — шаблону
   остаётся только нарисовать их, а в ключ кэша компонента они попадают
   параметром, поэтому кэш сам сбросится, когда отзывов станет больше. */
$ndIblock = (int) $arParams['IBLOCK_ID'];

/** Группы оценок: как в макете — хорошие 4–5, нейтральные 3, плохие 1–2. */
$ndRateGroups = ['good' => [4, 5], 'neutral' => [3], 'bad' => [1, 2]];

$ndCity = array_values(array_filter(array_map('intval', (array) ($_GET['city'] ?? []))));
$ndRate = array_values(array_intersect((array) ($_GET['rate'] ?? []), array_keys($ndRateGroups)));
$ndPhoto = ($_GET['photo'] ?? '') === 'y';

$ndStat = ['total' => 0, 'cities' => [], 'rate' => ['good' => 0, 'neutral' => 0, 'bad' => 0], 'photo' => ['y' => 0, 'n' => 0]];
$ndWithPhoto = [];

if ($ndIblock && CModule::IncludeModule('iblock')) {
	// город и оценка — одиночные свойства, строк ровно столько же, сколько отзывов
	$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => $ndIblock, 'ACTIVE' => 'Y'], false, false,
		['ID', 'PROPERTY_CITY_REVIEW', 'PROPERTY_RATING_REVIEW']);
	while ($r = $rs->Fetch()) {
		$ndStat['total']++;
		$cityId = (int) $r['PROPERTY_CITY_REVIEW_VALUE'];
		if ($cityId) {
			$ndStat['cities'][$cityId] = ($ndStat['cities'][$cityId] ?? 0) + 1;
		}
		$v = (int) round((float) $r['PROPERTY_RATING_REVIEW_VALUE']);
		if ($v >= 4) {
			$ndStat['rate']['good']++;
		} elseif ($v == 3) {
			$ndStat['rate']['neutral']++;
		} elseif ($v > 0) {
			$ndStat['rate']['bad']++;
		}
	}

	// PHOTOS_REVIEW — множественное свойство: фильтровать по нему напрямую нельзя,
	// выборка размножит элементы. Поэтому собираем список ID и фильтруем по нему.
	$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => $ndIblock, 'ACTIVE' => 'Y', '!PROPERTY_PHOTOS_REVIEW' => false], false, false, ['ID']);
	while ($r = $rs->Fetch()) {
		$ndWithPhoto[(int) $r['ID']] = true;
	}
	$ndStat['photo']['y'] = count($ndWithPhoto);
	$ndStat['photo']['n'] = $ndStat['total'] - $ndStat['photo']['y'];

	// названия городов — из инфоблока регионов, на который смотрит CITY_REVIEW
	if ($ndStat['cities']) {
		$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => 7, 'ID' => array_keys($ndStat['cities'])], false, false, ['ID', 'NAME']);
		while ($r = $rs->Fetch()) {
			$ndStat['cityNames'][(int) $r['ID']] = $r['NAME'];
		}
	}
}

$arNdReviewsFilter = [];
if ($ndCity) {
	$arNdReviewsFilter['PROPERTY_CITY_REVIEW'] = $ndCity;
}
if ($ndRate) {
	// несколько групп складываем в один список допустимых оценок
	$marks = [];
	foreach ($ndRate as $g) {
		$marks = array_merge($marks, $ndRateGroups[$g]);
	}
	$arNdReviewsFilter['PROPERTY_RATING_REVIEW'] = $marks;
}
if ($ndPhoto) {
	// пустой список превращаем в заведомо несуществующий ID, иначе фильтр отвалится
	$arNdReviewsFilter['ID'] = $ndWithPhoto ? array_keys($ndWithPhoto) : [-1];
}
$GLOBALS['arNdReviewsFilter'] = $arNdReviewsFilter;

$ndFilterState = [
	'city' => $ndCity,
	'rate' => $ndRate,
	'photo' => $ndPhoto,
	'active' => (bool) ($ndCity || $ndRate || $ndPhoto),
	'stat' => $ndStat,
	'groups' => array_keys($ndRateGroups),
];

$APPLICATION->IncludeComponent(
	'bitrix:news.list',
	'list_reviews_newdesign',
	[
		'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
		'IBLOCK_ID' => $arParams['IBLOCK_ID'],
		'NEWS_COUNT' => 6, // по макету на странице шесть отзывов
		'ND_FILTER' => $ndFilterState,
		// Сортировка своя, а не со страницы: там SORT ASC / ID DESC, то есть порядок
		// импорта. В новом дизайне отзывы идут от свежих по дате самого отзыва.
		// DATE_REVIEW хранится как «Y-m-d», поэтому строковая сортировка = хронологическая.
		'SORT_BY1' => 'PROPERTY_DATE_REVIEW',
		'SORT_ORDER1' => 'DESC',
		'SORT_BY2' => 'ID',
		'SORT_ORDER2' => 'DESC',
		'FIELD_CODE' => $arNdReviewFields,
		'PROPERTY_CODE' => $arNdReviewProps,
		'FILTER_NAME' => 'arNdReviewsFilter',
		'AJAX_MODE' => 'N',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',
		'AJAX_OPTION_ADDITIONAL' => '',
		'CACHE_TYPE' => $arParams['CACHE_TYPE'],
		'CACHE_TIME' => $arParams['CACHE_TIME'],
		// со страницы приходит «N», а нам фильтр обязан попадать в ключ кэша,
		// иначе все выборки склеятся в один кэш
		'CACHE_FILTER' => 'Y',
		'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
		'PREVIEW_TRUNCATE_LEN' => $arParams['PREVIEW_TRUNCATE_LEN'],
		'ACTIVE_DATE_FORMAT' => $arParams['LIST_ACTIVE_DATE_FORMAT'],
		'DISPLAY_PANEL' => $arParams['DISPLAY_PANEL'],
		'SET_TITLE' => $arParams['SET_TITLE'],
		'SET_BROWSER_TITLE' => 'N',
		'SET_LAST_MODIFIED' => 'N',
		'SET_STATUS_404' => $arParams['SET_STATUS_404'],
		'SHOW_404' => $arParams['SHOW_404'],
		'MESSAGE_404' => '',
		'INCLUDE_IBLOCK_INTO_CHAIN' => $arParams['INCLUDE_IBLOCK_INTO_CHAIN'],
		'ADD_SECTIONS_CHAIN' => $arParams['ADD_SECTIONS_CHAIN'],
		'HIDE_LINK_WHEN_NO_DETAIL' => $arParams['HIDE_LINK_WHEN_NO_DETAIL'],
		'CHECK_DATES' => $arParams['CHECK_DATES'],
		'PARENT_SECTION' => $arResult['VARIABLES']['SECTION_ID'],
		'PARENT_SECTION_CODE' => $arResult['VARIABLES']['SECTION_CODE'],
		'INCLUDE_SUBSECTIONS' => 'Y',
		'STRICT_SECTION_CHECK' => $arParams['STRICT_SECTION_CHECK'],
		'DISPLAY_TOP_PAGER' => 'N',
		'DISPLAY_BOTTOM_PAGER' => 'Y',
		'PAGER_TITLE' => $arParams['PAGER_TITLE'],
		'PAGER_TEMPLATE' => 'pagination_newdesign',
		'PAGER_SHOW_ALWAYS' => $arParams['PAGER_SHOW_ALWAYS'],
		'PAGER_DESC_NUMBERING' => $arParams['PAGER_DESC_NUMBERING'],
		'PAGER_DESC_NUMBERING_CACHE_TIME' => $arParams['PAGER_DESC_NUMBERING_CACHE_TIME'],
		'PAGER_SHOW_ALL' => $arParams['PAGER_SHOW_ALL'],
		'PAGER_BASE_LINK_ENABLE' => 'N',
		'DISPLAY_DATE' => $arParams['DISPLAY_DATE'],
		'DISPLAY_NAME' => $arParams['DISPLAY_NAME'],
		'DISPLAY_PICTURE' => $arParams['DISPLAY_PICTURE'],
		'DISPLAY_PREVIEW_TEXT' => $arParams['DISPLAY_PREVIEW_TEXT'],
		'USE_PERMISSIONS' => $arParams['USE_PERMISSIONS'],
		'GROUP_PERMISSIONS' => $arParams['GROUP_PERMISSIONS'],
		'SHOW_DETAIL_LINK' => 'N',
		'DETAIL_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['detail'],
		'SECTION_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['section'],
		'IBLOCK_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['news'],
		'COMPONENT_TEMPLATE' => 'list_reviews_newdesign',
		// кнопку «Оставить отзыв» рисует шаблон списка в левой колонке
		'SHOW_ADD_REVIEW_BUTTON' => $arParams['SHOW_ADD_REVIEW_BUTTON'],
		'ADD_REVIEW_BUTTON' => $arParams['ADD_REVIEW_BUTTON'],
		'FORM_CREATE_DEACTIVATED' => $arParams['FORM_CREATE_DEACTIVATED'],
	],
	$component
);
