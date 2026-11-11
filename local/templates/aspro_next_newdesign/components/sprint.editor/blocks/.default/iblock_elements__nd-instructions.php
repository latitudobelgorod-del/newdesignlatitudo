<? /** @var $block array */ ?><?
/* Вариант «Монтажные схемы и инструкции — все» (param1 = style6, Ирина, 11.11.2026).
   Выбранные в блоке ID не важны: выводятся все активные документы инфоблока
   «Лицензии и сертификаты» (13), у которых в названии есть слово про монтаж
   (инструкция, монтаж, схема, установка, сборка, подключение, альбом технических решений)
   и в «Привязке к производителю» (BRAND) есть хотя бы одна активная марка (ИБ 12).
   Вывод — news.list documents-tabs_newdesign: табы по производителям (BRAND), 11.11.2026.
   Так работает /materials/montazhnye-skhemy-i-instruktsii/. Подключается из
   iblock_elements__aspro-licenses.php всех шаблонов блоков. */

global $APPLICATION;

$ndActiveBrands = array();
$ndRs = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 12, 'ACTIVE' => 'Y'), false, false, array('ID'));
while ($ndAr = $ndRs->Fetch()) {
    $ndActiveBrands[] = (int)$ndAr['ID'];
}
if (!$ndActiveBrands) {
    return;
}

$ndNameOr = array('LOGIC' => 'OR');
foreach (array('монтаж', 'инструкц', 'схем', 'установк', 'сборк', 'подключени', 'альбом технических') as $ndWord) {
    $ndNameOr[] = array('%NAME' => $ndWord);
}
// список активных марок — в фильтре, а значит и в ключе кеша (CACHE_FILTER):
// выключили марку — выдача пересоберётся сама
$GLOBALS['ndInstructionsFilter'] = array(
    'PROPERTY_BRAND' => $ndActiveBrands,
    $ndNameOr,
);

$APPLICATION->IncludeComponent(
    "bitrix:news.list",
    "documents-tabs_newdesign",
    array(
        "IBLOCK_TYPE" => "aspro_next_content",
        "IBLOCK_ID" => $block['iblock_id'],
        "NEWS_COUNT" => "500",
        "SORT_BY1" => "NAME",
        "SORT_ORDER1" => "ASC",
        "SORT_BY2" => "ID",
        "SORT_ORDER2" => "ASC",
        "FILTER_NAME" => "ndInstructionsFilter",
        "FIELD_CODE" => array("", ""),
        "PROPERTY_CODE" => array("LINK", ""),
        "CHECK_DATES" => "Y",
        "DETAIL_URL" => "",
        "AJAX_MODE" => "N",
        "CACHE_TYPE" => "A",
        "CACHE_TIME" => "36000000",
        "CACHE_FILTER" => "Y",
        "CACHE_GROUPS" => "N",
        "ACTIVE_DATE_FORMAT" => "j F Y",
        "SET_TITLE" => "N",
        "SET_BROWSER_TITLE" => "N",
        "SET_META_KEYWORDS" => "N",
        "SET_META_DESCRIPTION" => "N",
        "SET_LAST_MODIFIED" => "N",
        "SET_STATUS_404" => "N",
        "SHOW_404" => "N",
        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
        "ADD_SECTIONS_CHAIN" => "N",
        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
        "INCLUDE_SUBSECTIONS" => "Y",
        "PAGER_TEMPLATE" => "pagination_newdesign",
        "DISPLAY_TOP_PAGER" => "N",
        "DISPLAY_BOTTOM_PAGER" => "N",
        "PAGER_SHOW_ALWAYS" => "N",
    ),
    false
);
