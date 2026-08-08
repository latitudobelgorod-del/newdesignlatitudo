<? /** @var $block array */ ?><?

global $arTheme;
global $APPLICATION;

/* Показываем ровно те элементы и в том порядке, как их перечислил менеджер
   в редакторе блоков: фильтр по ID, порядок восстанавливает result_modifier. */
global $sprintSearchFilter;
$sprintSearchFilter = array(
	'ID' => $block['element_ids'],
);

/* Постранички здесь нет: список ограничен выбранными элементами, так что
   «все» — это ровно столько, сколько отмечено. Ноль ставить нельзя —
   news.list поймёт его как «без ограничения» и вернёт весь инфоблок. */
$ldCertsCount = is_array($block['element_ids']) ? count($block['element_ids']) : 0;

?><? $APPLICATION->IncludeComponent(
    "bitrix:news.list",
    // Сетка сертификатов нового дизайна: три колонки, карточка без рамки,
    // подпись слева под картинкой. Старый вид — в news-licenses-editor.
    "news-licenses-editor_newdesign",
    array(
        "IBLOCK_TYPE" => "aspro_next_content",
        "IBLOCK_ID" => $block['iblock_id'],
        "NEWS_COUNT" => (string)($ldCertsCount > 0 ? $ldCertsCount : 500),
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
            0 => "",
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
