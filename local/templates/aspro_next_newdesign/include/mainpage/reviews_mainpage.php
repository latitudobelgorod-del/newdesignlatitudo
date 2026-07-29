<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Блок «Что говорят о нас» — три свежих отзыва
 * из инфоблока 33, залитых с Яндекс.Карт.
 *
 * Сортировка та же, что на странице /company/reviews/: по свойству
 * DATE_REVIEW от свежих (в БД оно лежит как «Y-m-d», поэтому строковая
 * сортировка совпадает с хронологической).
 *
 * Разметка и стили — в шаблоне компонента list_reviews_main_newdesign;
 * сама карточка общая со страницей отзывов (include/parts/review_card.php).
 */
if (!CModule::IncludeModule('iblock')) {
	return;
}

$APPLICATION->IncludeComponent(
	'bitrix:news.list',
	'list_reviews_main_newdesign',
	[
		'IBLOCK_TYPE' => 'aspro_next_content',
		'IBLOCK_ID' => '33',
		'NEWS_COUNT' => '3',
		'SORT_BY1' => 'PROPERTY_DATE_REVIEW',
		'SORT_ORDER1' => 'DESC',
		'SORT_BY2' => 'ID',
		'SORT_ORDER2' => 'DESC',
		'FIELD_CODE' => ['NAME', 'DETAIL_TEXT', 'DATE_ACTIVE_FROM'],
		'PROPERTY_CODE' => ['DATE_REVIEW', 'RATING_REVIEW', 'PHOTOS_REVIEW', 'CITY_REVIEW'],
		'FILTER_NAME' => '',
		'CHECK_DATES' => 'Y',
		'DETAIL_URL' => '',
		'AJAX_MODE' => 'N',
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => '36000000',
		'CACHE_FILTER' => 'N',
		'CACHE_GROUPS' => 'N',
		'PREVIEW_TRUNCATE_LEN' => '',
		'ACTIVE_DATE_FORMAT' => 'd.m.Y',
		'SET_TITLE' => 'N',
		'SET_BROWSER_TITLE' => 'N',
		'SET_META_KEYWORDS' => 'N',
		'SET_META_DESCRIPTION' => 'N',
		'SET_LAST_MODIFIED' => 'N',
		'SET_STATUS_404' => 'N',
		'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
		'ADD_SECTIONS_CHAIN' => 'N',
		'HIDE_LINK_WHEN_NO_DETAIL' => 'N',
		'PARENT_SECTION' => '',
		'PARENT_SECTION_CODE' => '',
		'INCLUDE_SUBSECTIONS' => 'Y',
		'DISPLAY_TOP_PAGER' => 'N',
		'DISPLAY_BOTTOM_PAGER' => 'N',
		'PAGER_TEMPLATE' => '',
		'PAGER_SHOW_ALWAYS' => 'N',
		'PAGER_DESC_NUMBERING' => 'N',
		'PAGER_SHOW_ALL' => 'N',
		'DISPLAY_DATE' => 'N',
		'DISPLAY_NAME' => 'Y',
		'DISPLAY_PICTURE' => 'N',
		'DISPLAY_PREVIEW_TEXT' => 'N',
		'TITLE_BLOCK' => 'Что говорят о нас',
		'SUBTITLE_BLOCK' => 'Наши любимые клиенты',
		'ALL_URL' => SITE_DIR.'company/reviews/',
		'COMPONENT_TEMPLATE' => 'list_reviews_main_newdesign',
	],
	false,
	['HIDE_ICONS' => 'Y']
);
