<? /** @var $block array */ ?><?

global $arTheme;
global $APPLICATION;

global $sprintSearchFilter;
$sprintSearchFilter = array(
    "=ID" => $block['section_ids'],
);

?>
<div class="block">



<?$APPLICATION->IncludeComponent(
	"bitrix:news", 
	"staff_2022", 
	array(
		"IBLOCK_TYPE" => "aspro_next_content",
		"IBLOCK_ID" => $block["iblock_id"],
		"NEWS_COUNT" => "50",
		"USE_SEARCH" => "N",
		"FILTER_NAME" => "sprintSearchFilter",
		"USE_RSS" => "N",
		"USE_RATING" => "N",
		"USE_CATEGORIES" => "N",
		"USE_FILTER" => "N",
		"SORT_BY1" => "SORT",
		"SORT_ORDER1" => "ASC",
		"SORT_BY2" => "ID",
		"SORT_ORDER2" => "DESC",
		"CHECK_DATES" => "Y",
		"SEF_MODE" => "N",
		"SECTION_ID" => $_REQUEST["SECTION_ID"],
		"AJAX_MODE" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"AJAX_OPTION_HISTORY" => "N",
		"CACHE_TYPE" => "N",
		"CACHE_TIME" => "100000",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "N",
		"SET_TITLE" => "Y",
		"SET_STATUS_404" => "Y",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"ADD_SECTIONS_CHAIN" => "Y",
		"USE_PERMISSIONS" => "N",
		"PREVIEW_TRUNCATE_LEN" => "",
		"LIST_ACTIVE_DATE_FORMAT" => "d.m.Y",
		"LIST_FIELD_CODE" => array(
			0 => "NAME",
			1 => "PREVIEW_TEXT",
			2 => "PREVIEW_PICTURE",
			3 => "",
		),
		"LIST_PROPERTY_CODE" => array(
			0 => "",
			1 => "EMAIL",
			2 => "POST",
			3 => "PHONE",
			4 => "",
		),
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"DISPLAY_NAME" => "N",
		"META_KEYWORDS" => "-",
		"META_DESCRIPTION" => "-",
		"BROWSER_TITLE" => "-",
		"DETAIL_ACTIVE_DATE_FORMAT" => "d.m.Y",
		"DETAIL_FIELD_CODE" => array(
			0 => "PREVIEW_TEXT",
			1 => "PREVIEW_PICTURE",
			2 => "DETAIL_TEXT",
			3 => "",
		),
		"DETAIL_PROPERTY_CODE" => array(
			0 => "",
			1 => "EMAIL",
			2 => "POST",
			3 => "PHONE",
			4 => "",
		),
		"DETAIL_DISPLAY_TOP_PAGER" => "N",
		"DETAIL_DISPLAY_BOTTOM_PAGER" => "Y",
		"DETAIL_PAGER_TITLE" => "Страница",
		"DETAIL_PAGER_TEMPLATE" => "",
		"DETAIL_PAGER_SHOW_ALL" => "Y",
		"PAGER_TEMPLATE" => ".default",
		"DISPLAY_TOP_PAGER" => "N",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"PAGER_TITLE" => "Новости",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"VIEW_TYPE" => "table",
		"SHOW_TABS" => "N",
		"SHOW_SECTION_PREVIEW_DESCRIPTION" => "Y",
		"COUNT_IN_LINE" => "4",
		"AJAX_OPTION_ADDITIONAL" => "",
		"USE_REVIEW" => "N",
		"ADD_ELEMENT_CHAIN" => "Y",
		"SHOW_DETAIL_LINK" => "Y",
		"IMAGE_POSITION" => "left",
		"COMPONENT_TEMPLATE" => "staff_2022",
		"SET_LAST_MODIFIED" => "N",
		"DETAIL_SET_CANONICAL_URL" => "N",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"SHOW_404" => "N",
		"MESSAGE_404" => "",
		"STRICT_SECTION_CHECK" => "Y",
		"SECTION_ELEMENTS_TYPE_VIEW" => "list_elements_1",
		"ELEMENT_TYPE_VIEW" => "element_1",
		"VARIABLE_ALIASES" => array(
			"SECTION_ID" => "SECTION_ID",
			"ELEMENT_ID" => "ELEMENT_ID",
		)
	),
	false
);?>






</div>
