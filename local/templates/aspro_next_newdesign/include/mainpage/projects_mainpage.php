<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Блок «Вдохновитесь нашими проектами» —
 * шесть проектов из инфоблока 18 на тёмной подложке во всю ширину экрана.
 *
 * Сортировка та же, что на странице /projects/, чтобы порядок совпадал.
 * Разметка и стили — в шаблоне компонента list_projects_main_newdesign;
 * сама карточка общая со страницей портфолио (include/parts/project_card.php).
 */
if (!CModule::IncludeModule('iblock')) {
	return;
}

$APPLICATION->IncludeComponent(
	'bitrix:news.list',
	'list_projects_main_newdesign',
	[
		'IBLOCK_TYPE' => 'aspro_next_content',
		'IBLOCK_ID' => '18',
		'NEWS_COUNT' => '6',
		'SORT_BY1' => 'SORT',
		'SORT_ORDER1' => 'ASC',
		'SORT_BY2' => 'ID',
		'SORT_ORDER2' => 'ASC',
		'FIELD_CODE' => ['ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'],
		'PROPERTY_CODE' => ['SET_BRAND', 'VIDEO', 'GALLEY_BIG', 'REVIEW'],
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
		'DISPLAY_PICTURE' => 'Y',
		'DISPLAY_PREVIEW_TEXT' => 'N',
		'TITLE_BLOCK' => 'Вдохновитесь нашими проектами',
		'SECTION_COUNT' => '5',
		'ALL_URL' => SITE_DIR.'projects/',
		'COMPONENT_TEMPLATE' => 'list_projects_main_newdesign',
	],
	false,
	['HIDE_ICONS' => 'Y']
);
