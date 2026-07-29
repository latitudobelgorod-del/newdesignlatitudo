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

$APPLICATION->IncludeComponent(
	'bitrix:news.list',
	'list_reviews_newdesign',
	[
		'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
		'IBLOCK_ID' => $arParams['IBLOCK_ID'],
		'NEWS_COUNT' => $arParams['NEWS_COUNT'],
		'SORT_BY1' => $arParams['SORT_BY1'],
		'SORT_ORDER1' => $arParams['SORT_ORDER1'],
		'SORT_BY2' => $arParams['SORT_BY2'],
		'SORT_ORDER2' => $arParams['SORT_ORDER2'],
		'FIELD_CODE' => $arNdReviewFields,
		'PROPERTY_CODE' => $arNdReviewProps,
		'FILTER_NAME' => $arParams['FILTER_NAME'],
		'AJAX_MODE' => 'N',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',
		'AJAX_OPTION_ADDITIONAL' => '',
		'CACHE_TYPE' => $arParams['CACHE_TYPE'],
		'CACHE_TIME' => $arParams['CACHE_TIME'],
		'CACHE_FILTER' => $arParams['CACHE_FILTER'],
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
