<? /** @var $block array */ ?><?
global $arTheme;
global $APPLICATION;
global $sprintSearchFilter, $arNewsFilter;
?>



<? /** @var $block array */ ?><?

global $arTheme;
global $APPLICATION;

global $sprintSearchFilter;
$sprintSearchFilter = array(
    "=ID" => $block['element_ids'],
);

?>
<div class="block">
    <? $APPLICATION->IncludeComponent(
        "bitrix:catalog.section",
        'catalog_blockcolors',
        Array(
            "IBLOCK_TYPE" => 'aspro_next_catalog',
            "IBLOCK_ID" => $block['iblock_id'],

            "ELEMENT_SORT_FIELD" => "SORT",
            "ELEMENT_SORT_ORDER" => "asc",
            "FILTER_NAME" => "sprintSearchFilter",

            "SHOW_ALL_WO_SECTION" => "Y",
            "SECTION_ID" => '',
            "SECTION_CODE" => '',

            "USE_REGION" => "N",
            "STORES" => array(),
            "SHOW_UNABLE_SKU_PROPS" => 'Y',

            "AJAX_REQUEST" => 'N',

            "INCLUDE_SUBSECTIONS" => 'N',
            "PAGE_ELEMENT_COUNT" => '20',
            "LINE_ELEMENT_COUNT" => '4',
          //  "DISPLAY_TYPE" => 'block',
            "TYPE_SKU" => $arTheme["TYPE_SKU"]["VALUE"],
            "PROPERTY_CODE" => array(
                0 => 'BRAND',
                1 => 'PROP_2049',
                2 => 'COLOR_REF2',
                3 => 'PROP_2083',
                4 => 'PROP_2027',
                5 => 'PROP_2026',
                6 => 'PROP_2065',
                7 => 'PROP_159',
                8 => 'PROP_2033',
                9 => 'PROP_162',
                10 => 'PROP_2054',
                11 => 'PROP_2052',
                12 => 'PROP_2055',
                13 => 'PROP_2069',
                14 => 'PROP_2062',
                15 => 'PROP_2061',
                16 => 'CML2_LINK',
                17 => '',
            ),
            'SHOW_ARTICLE_SKU' => 'N',
            'SHOW_MEASURE_WITH_RATIO' => 'N',

            "OFFERS_FIELD_CODE" => array(
                0 => 'NAME',
                1 => 'CML2_LINK',
                2 => 'DETAIL_PAGE_URL',
                3 => '',
            ),
            "OFFERS_PROPERTY_CODE" => array(
                0 => 'ARTICLE',
                1 => 'VOLUME',
                2 => 'SIZES',
                3 => 'COLOR_REF',
                4 => '',
            ),
            'OFFERS_SORT_FIELD' => 'sort',
            'OFFERS_SORT_ORDER' => 'asc',
            'OFFERS_SORT_FIELD2' => 'name',
            'OFFERS_SORT_ORDER2' => 'asc',
            'OFFER_TREE_PROPS' =>
                array(
                    0 => 'SIZES',
                    1 => 'COLOR_REF',
                ),

            "OFFERS_LIMIT" => '10',

            "SECTION_URL" => '',
            "DETAIL_URL" => '',

            'BASKET_URL' => '/basket/',

            'ACTION_VARIABLE' => 'action',
            'PRODUCT_ID_VARIABLE' => 'id',
            "PRODUCT_QUANTITY_VARIABLE" => "quantity",
            "PRODUCT_PROPS_VARIABLE" => "prop",


            'SECTION_ID_VARIABLE' => 'SECTION_ID',
            'SET_LAST_MODIFIED' => 'N',
            'AJAX_MODE' => 'N',
            'AJAX_OPTION_JUMP' => 'N',
            'AJAX_OPTION_STYLE' => 'Y',
            'AJAX_OPTION_HISTORY' => 'Y',

            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '3600000',

            'CACHE_GROUPS' => 'N',
            "CACHE_FILTER" => "Y",

            "META_KEYWORDS" => '-',
            "META_DESCRIPTION" => '-',
            "BROWSER_TITLE" => '-',
            'ADD_SECTIONS_CHAIN' => 'N',
            'HIDE_NOT_AVAILABLE' => 'N',
            'HIDE_NOT_AVAILABLE_OFFERS' => 'N',

            "DISPLAY_COMPARE" => 'N',

            'SET_TITLE' => 'N',

            'SET_STATUS_404' => 'N',

            'SHOW_404' => 'N',
            'MESSAGE_404' => '',

            'PRICE_CODE' =>
                array(
                    0 => 'BASE',
                ),

            'USE_PRICE_COUNT' => 'Y',
            'SHOW_PRICE_COUNT' => '1',
            'PRICE_VAT_INCLUDE' => 'Y',
            'USE_PRODUCT_QUANTITY' => 'Y',
            'OFFERS_CART_PROPERTIES' => array(),

            'DISPLAY_TOP_PAGER' => 'N',
            "DISPLAY_BOTTOM_PAGER" => 'N',

            'PAGER_TITLE' => 'Товары',
            'PAGER_SHOW_ALWAYS' => 'N',
            'PAGER_TEMPLATE' => 'main',

            'PAGER_DESC_NUMBERING' => 'N',
            'PAGER_DESC_NUMBERING_CACHE_TIME' => '36000',

            'PAGER_SHOW_ALL' => 'N',

            "AJAX_OPTION_ADDITIONAL" => "",
            "ADD_CHAIN_ITEM" => "N",

            'SHOW_QUANTITY' => 'Y',
            'SHOW_QUANTITY_COUNT' => 'Y',

            'SHOW_DISCOUNT_PERCENT' => 'Y',
            'SHOW_DISCOUNT_TIME' => 'N',
            'SHOW_OLD_PRICE' => 'Y',
            'CONVERT_CURRENCY' => 'Y',
            'CURRENCY_ID' => 'RUB',
            'USE_STORE' => 'N',
            'MAX_AMOUNT' => '20',
            'MIN_AMOUNT' => '10',

            'USE_MIN_AMOUNT' => 'N',
            'USE_ONLY_MAX_AMOUNT' => 'Y',

            'DISPLAY_WISH_BUTTONS' => 'N',
            'LIST_DISPLAY_POPUP_IMAGE' => 'Y',

            'DEFAULT_COUNT' => '1',

            'SHOW_MEASURE' => 'Y',
            'SHOW_HINTS' => 'Y',

            'OFFER_HIDE_NAME_PROPS' => 'N',

            'SECTIONS_LIST_PREVIEW_PROPERTY' => 'UF_SECTION_DESCR',
            'SHOW_SECTION_LIST_PICTURES' => 'Y',
            'USE_MAIN_ELEMENT_SECTION' => 'N',
            'ADD_PROPERTIES_TO_BASKET' => 'Y',
            'PARTIAL_PRODUCT_PROPERTIES' => 'Y',
            "PRODUCT_PROPERTIES" => array(),
            'SALE_STIKER' => 'SALE_TEXT',
            'STIKERS_PROP' => 'HIT',
            'SHOW_RATING' => 'N',
        ), $component, array("HIDE_ICONS" => $isAjax)
    ); ?>
</div>

