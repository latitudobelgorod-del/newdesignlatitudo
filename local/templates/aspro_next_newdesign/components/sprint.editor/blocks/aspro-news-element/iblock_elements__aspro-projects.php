<? /** @var $block array */ ?><?

global $arTheme;
global $APPLICATION;
 $GLOBALS['sprintSearchFilter'] =  array(
 "ID" => $block['element_ids'],
); 
?>

<? $APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "news-project-catalog-editor",
    array(
        "IBLOCK_TYPE" => "aspro_next_content",
		"IBLOCK_ID" => $block['iblock_id'],
        "NEWS_COUNT" => "500",
		"FILTER_NAME" => "sprintSearchFilter",
        "FIELD_CODE" => array(
           0 => "ID",
			1 => "NAME",
			2 => "PREVIEW_TEXT",
			3 => "PREVIEW_PICTURE",
			4 => "IBLOCK_ID",
			5 => "",
        ),
"PROPERTY_CODE" => array(
			0 => "LINK_YOUTUBE",
			1 => "REVIEW",
			2 => "FORM_CALCULATE",
			3 => "EDITOR1",
			4 => "EDITOR2",
			5 => "UF_EDITOR2",
			6 => "UF_EDITOR1_BEL",
			7 => "UF_EDITOR2_BEL",
			8 => "GALLEY_BIG",
			9 => "",
		),
        
        "CHECK_DATES" => "Y",
        "DETAIL_URL" => "",
        "AJAX_MODE" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "Y",
        "AJAX_OPTION_HISTORY" => "N",
        "CACHE_TYPE" => "N",
        "CACHE_TIME" => "36000000",
        "CACHE_FILTER" => "Y",
        "CACHE_GROUPS" => "N",
        "PREVIEW_TRUNCATE_LEN" => "",
        "ACTIVE_DATE_FORMAT" => "j F Y",
        "SET_TITLE" => "N",
        "SET_STATUS_404" => "N",
        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
        "ADD_SECTIONS_CHAIN" => "N",
        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
        "PARENT_SECTION" => "",
        "PARENT_SECTION_CODE" => "",
        "INCLUDE_SUBSECTIONS" => "Y",
        "PAGER_TEMPLATE" => "",
        "DISPLAY_TOP_PAGER" => "N",
        "DISPLAY_BOTTOM_PAGER" => "N",
        "PAGER_TITLE" => "",
        "PAGER_SHOW_ALWAYS" => "N",
        "PAGER_DESC_NUMBERING" => "N",
        
        "PAGER_SHOW_ALL" => "N",
        "AJAX_OPTION_ADDITIONAL" => "",
        "COMPONENT_TEMPLATE" => "next",
        "SET_BROWSER_TITLE" => "N",
        "SET_META_KEYWORDS" => "N",
        "SET_META_DESCRIPTION" => "Y",
        "SET_LAST_MODIFIED" => "N",
        "PAGER_BASE_LINK_ENABLE" => "N",
        "SHOW_404" => "N",
        "MESSAGE_404" => ""
    ),
    false
); ?>
