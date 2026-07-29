<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Вывод списка статей для нового дизайна.
 *
 * Рядом лежат асprovские list_elements_*.php — они не тронуты, ими пользуется
 * старый шаблон `blog`. Здесь свой вызов: шаблон карточек
 * `list_articles_page_newdesign` и навигация `pagination_newdesign`, та же,
 * что на отзывах и портфолио.
 *
 * По макету на странице три ряда по четыре карточки, поэтому 12 элементов,
 * а не 6, как приходит со страницы.
 */
$APPLICATION->IncludeComponent(
	'bitrix:news.list',
	'list_articles_page_newdesign',
	[
		'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
		'IBLOCK_ID' => $arParams['IBLOCK_ID'],
		'NEWS_COUNT' => 12,
		'SORT_BY1' => $arParams['SORT_BY1'],
		'SORT_ORDER1' => $arParams['SORT_ORDER1'],
		'SORT_BY2' => $arParams['SORT_BY2'],
		'SORT_ORDER2' => $arParams['SORT_ORDER2'],
		'FIELD_CODE' => ['ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'],
		'PROPERTY_CODE' => is_array($arParams['LIST_PROPERTY_CODE']) ? $arParams['LIST_PROPERTY_CODE'] : [],
		'FILTER_NAME' => $arParams['FILTER_NAME'],
		'AJAX_MODE' => 'N',
		'CACHE_TYPE' => $arParams['CACHE_TYPE'],
		'CACHE_TIME' => $arParams['CACHE_TIME'],
		'CACHE_FILTER' => 'Y',
		'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
		'PREVIEW_TRUNCATE_LEN' => $arParams['PREVIEW_TRUNCATE_LEN'],
		'ACTIVE_DATE_FORMAT' => $arParams['LIST_ACTIVE_DATE_FORMAT'],
		'SET_TITLE' => 'N',
		'SET_BROWSER_TITLE' => 'N',
		'SET_LAST_MODIFIED' => 'N',
		'SET_STATUS_404' => $arParams['SET_STATUS_404'],
		'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
		'ADD_SECTIONS_CHAIN' => 'N',
		'HIDE_LINK_WHEN_NO_DETAIL' => 'N',
		'CHECK_DATES' => $arParams['CHECK_DATES'],
		'PARENT_SECTION' => $arResult['VARIABLES']['SECTION_ID'],
		'PARENT_SECTION_CODE' => $arResult['VARIABLES']['SECTION_CODE'],
		'INCLUDE_SUBSECTIONS' => 'Y',
		'STRICT_SECTION_CHECK' => $arParams['STRICT_SECTION_CHECK'],
		'DISPLAY_TOP_PAGER' => 'N',
		'DISPLAY_BOTTOM_PAGER' => 'Y',
		'PAGER_TITLE' => $arParams['PAGER_TITLE'],
		'PAGER_TEMPLATE' => 'pagination_newdesign',
		'PAGER_SHOW_ALWAYS' => 'N',
		'PAGER_DESC_NUMBERING' => 'N',
		'PAGER_SHOW_ALL' => 'N',
		'PAGER_BASE_LINK_ENABLE' => 'N',
		'DISPLAY_DATE' => 'N',
		'DISPLAY_NAME' => 'Y',
		'DISPLAY_PICTURE' => 'Y',
		'DISPLAY_PREVIEW_TEXT' => 'N',
		'USE_PERMISSIONS' => $arParams['USE_PERMISSIONS'],
		'GROUP_PERMISSIONS' => $arParams['GROUP_PERMISSIONS'],
		'DETAIL_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['detail'],
		'SECTION_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['section'],
		'IBLOCK_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['news'],
		'COMPONENT_TEMPLATE' => 'list_articles_page_newdesign',
	],
	$component
);
