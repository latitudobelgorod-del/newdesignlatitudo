<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Блок «Полезно знать» — статьи инфоблока «Материалы» (14).
 *
 * Отбираются статьи, у которых свойство SHOW_ON_MAINPAGE = «да» (по умолчанию «нет»).
 * Свойство списочное, поэтому фильтруем по ID варианта, а сам ID ищем по XML_ID «yes»:
 * свойство заводилось на каждой среде отдельно, совпадение автоинкрементов
 * не гарантировано.
 *
 * Разметка и стили — в шаблоне компонента list_articles_newdesign этого же шаблона.
 */
if (!CModule::IncludeModule('iblock')) {
    return;
}

$ndArticlesEnumId = null;
$rsEnum = CIBlockPropertyEnum::GetList(
    [],
    ['IBLOCK_ID' => 14, 'CODE' => 'SHOW_ON_MAINPAGE', 'XML_ID' => 'yes']
);
if ($arEnum = $rsEnum->Fetch()) {
    $ndArticlesEnumId = $arEnum['ID'];
}

// Свойства ещё нет (не перенесено на эту среду) — блок просто не выводим,
// иначе без фильтра на главную вывалятся все статьи подряд.
if (!$ndArticlesEnumId) {
    return;
}

global $arNdArticlesFilter;
$arNdArticlesFilter = ['PROPERTY_SHOW_ON_MAINPAGE' => $ndArticlesEnumId];

$APPLICATION->IncludeComponent(
	'bitrix:news.list',
	'list_articles_newdesign',
	[
		'IBLOCK_TYPE' => 'aspro_next_content',
		'IBLOCK_ID' => '14',
		'NEWS_COUNT' => '3',
		'SORT_BY1' => 'ACTIVE_FROM',
		'SORT_ORDER1' => 'DESC',
		'SORT_BY2' => 'SORT',
		'SORT_ORDER2' => 'ASC',
		'FIELD_CODE' => ['NAME', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL'],
		'PROPERTY_CODE' => ['SHOW_ON_MAINPAGE'],
		'FILTER_NAME' => 'arNdArticlesFilter',
		'CHECK_DATES' => 'Y',
		'DETAIL_URL' => '',
		'AJAX_MODE' => 'N',
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => '36000000',
		'CACHE_FILTER' => 'Y',
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
		'DISPLAY_PICTURE' => 'Y',
		'DISPLAY_PREVIEW_TEXT' => 'N',
		'TITLE_BLOCK' => 'Полезно знать',
		'BADGE_TEXT' => 'Статья',
		'ALL_URL' => SITE_DIR.'materials/',
		'COMPONENT_TEMPLATE' => 'list_articles_newdesign',
	],
	false,
	['HIDE_ICONS' => 'Y']
);
