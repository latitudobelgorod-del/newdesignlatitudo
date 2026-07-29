<? /** @var $block array */ ?><?

global $arTheme;
global $APPLICATION;

global $sprintSearchFilter;
$sprintSearchFilter = array(
    "=ID" => $block['section_ids'],
	"GLOBAL_ACTIVE" => "Y",
);

?>


<div class="block">



  	<?$APPLICATION->IncludeComponent(
	"bitrix:catalog.section.list", 
	"subsections_list_3el_kont_manager", 
	array(
		"IBLOCK_TYPE" => "aspro_next_content",
		 "IBLOCK_ID" => $block['iblock_id'],
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "172800",
		"CACHE_FILTER" => "Y",
		"CACHE_GROUPS" => "N",
		"COUNT_ELEMENTS" => "N",
		"FILTER_NAME" => "sprintSearchFilter",
		"TOP_DEPTH" => "",
		"SECTION_URL" => "",
		"VIEW_MODE" => "",
		"SHOW_PARENT_NAME" => "N",
		"HIDE_SECTION_NAME" => "N",
		"ADD_SECTIONS_CHAIN" => "N",
		"SHOW_SECTIONS_LIST_PREVIEW" => "N",
		"SECTIONS_LIST_PREVIEW_PROPERTY" => "N",
		"SECTIONS_LIST_PREVIEW_DESCRIPTION" => "N",
		"SHOW_SECTION_LIST_PICTURES" => "N",
		
		"DISPLAY_PANEL" => "N",
		"COMPONENT_TEMPLATE" => "subsections_list_3el_kont_manager",
		"SECTION_ID" => $_REQUEST["SECTION_ID"],
		"SECTION_CODE" => "",
		"SECTION_FIELDS" => array(
			0 => "",
			1 => "",
		),
		"SECTION_USER_FIELDS" => array(
			0 => "UF_LINK_REGION",
			1 => "",
		),
		
		
		"COMPOSITE_FRAME_MODE" => "A",
		"COMPOSITE_FRAME_TYPE" => "AUTO"
	),
	false
);?>







</div>
