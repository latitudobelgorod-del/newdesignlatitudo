<? /** @var $block array */ ?><?

global $arTheme;
global $APPLICATION;

global $sprintSearchFilter;
$sprintSearchFilter = array(
    "=ID" => $block['element_ids'],
);

/* Показываем все файлы блока разом, без постранички. Разрастись список не
   может: он ограничен теми элементами, которые менеджер отметил в редакторе
   (фильтр по =ID выше), так что «все» — это ровно столько, сколько выбрано. */
$ldDocsCount = is_array($block['element_ids']) ? count($block['element_ids']) : 0;

?><? $APPLICATION->IncludeComponent(
    "bitrix:news.list",
    // Список документов в новом дизайне рисуется своей копией шаблона —
    // с иконкой и подписью «Скачать PDF (…)» по макету.
    "news-documents_newdesign",
    array(
      //  "SORT_BY_FILTER_ID" => 'Y',
        "IBLOCK_TYPE" => "aspro_next_content",
        "IBLOCK_ID" => $block['iblock_id'],
        // Ровно столько, сколько файлов в блоке — тогда всё умещается на одну
        // страницу и навигация не появляется. Ноль здесь ставить нельзя:
        // news.list поймёт его как «без ограничения» и вернёт весь инфоблок.
        "NEWS_COUNT" => (string)($ldDocsCount > 0 ? $ldDocsCount : 9),
//		"SORT_BY1" => "SORT",
//		"SORT_ORDER1" => "ASC",
//		"SORT_BY2" => "ID",
//		"SORT_ORDER2" => "DESC",
        "FILTER_NAME" => "sprintSearchFilter",
        "FIELD_CODE" => array(
            0 => "",
            1 => "",
        ),
        "PROPERTY_CODE" => array(
            0 => "LINK",
            1 => "",
        ),
        "CHECK_DATES" => "Y",
        "DETAIL_URL" => "",
        "AJAX_MODE" => "N",
        "AJAX_OPTION_JUMP" => "N",
        "AJAX_OPTION_STYLE" => "Y",
        "AJAX_OPTION_HISTORY" => "N",
        "CACHE_TYPE" => "A",
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
        "PAGER_TEMPLATE" => "pagination_newdesign",
        "DISPLAY_TOP_PAGER" => "N",
        "DISPLAY_BOTTOM_PAGER" => "N",
        "PAGER_TITLE" => "",
        "PAGER_SHOW_ALWAYS" => "N",
        "PAGER_DESC_NUMBERING" => "N",
        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
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
