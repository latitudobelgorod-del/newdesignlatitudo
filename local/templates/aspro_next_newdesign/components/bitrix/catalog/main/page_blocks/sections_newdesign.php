<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Корень каталога /catalog/ в новом дизайне — замена page_blocks/sections_1.php.
 *
 * По макету Figma («Чистовик», фрейм «Каталог страница» 21349:57069):
 * баннеры (их рисует sections.php выше) → H1 → плитки разделов → бренды.
 *
 * Ветка поиска (?q=) перенесена из sections_1.php без изменений: страница
 * результатов поиска по каталогу под новый дизайн пока не переделывалась.
 *
 * Имя блока приходит из /catalog/index.php параметром SECTIONS_TYPE_VIEW —
 * старый дизайн получает прежний sections_1, новый этот файл.
 */

if (isset($_GET['q'])) {
	$APPLICATION->IncludeComponent(
		"bitrix:catalog.search",
		"main",
		Array(
			"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
			"IBLOCK_ID" => $arParams["IBLOCK_ID"],
			"SORT_BUTTONS" => $arParams["SORT_BUTTONS"],
			"SORT_PRICES" => $arParams["SORT_PRICES"],
			"ELEMENT_SORT_FIELD" => "name",
			"ELEMENT_SORT_ORDER" => $arParams["ELEMENT_SORT_ORDER"],
			"PAGE_ELEMENT_COUNT" => $arParams["PAGE_ELEMENT_COUNT"],
			"LINE_ELEMENT_COUNT" => $arParams["LINE_ELEMENT_COUNT"],
			"PROPERTY_CODE" => $arParams["SEARCH_PROPERTY_CODE"],

			"OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
			"OFFERS_LIMIT" => $arParams["OFFERS_LIMIT"],

			"SHOW_ARTICLE_SKU" => $arParams["SHOW_ARTICLE_SKU"],
			"SHOW_MEASURE_WITH_RATIO" => $arParams["SHOW_MEASURE_WITH_RATIO"],

			"OFFERS_FIELD_CODE" => $arParams["LIST_OFFERS_FIELD_CODE"],
			"OFFERS_PROPERTY_CODE" => $arParams["LIST_OFFERS_PROPERTY_CODE"],
			"OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
			"OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
			"OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
			"OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
			'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'],

			"SECTION_URL" => $arParams["SECTION_URL"],
			"DETAIL_URL" => $arParams["DETAIL_URL"],
			"BASKET_URL" => $arParams["BASKET_URL"],
			"ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
			"PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
			"PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
			"PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
			"SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"CACHE_TIME" => $arParams["CACHE_TIME"],
			"USE_COMPARE" => $arParams["USE_COMPARE"],
			"PRICE_CODE" => $arParams["PRICE_CODE"],
			"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
			"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
			"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
			"PRODUCT_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],
			"USE_PRODUCT_QUANTITY" => $arParams["USE_PRODUCT_QUANTITY"],
			"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
			"CURRENCY_ID" => $arParams["CURRENCY_ID"],
			"DISPLAY_TOP_PAGER" => $arParams["DISPLAY_TOP_PAGER"],
			"DISPLAY_BOTTOM_PAGER" => $arParams["DISPLAY_BOTTOM_PAGER"],
			"PAGER_TITLE" => $arParams["PAGER_TITLE"],
			"PAGER_SHOW_ALWAYS" => $arParams["PAGER_SHOW_ALWAYS"],
			"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
			"PAGER_TEMPLATE" => "",
			"PAGER_DESC_NUMBERING" => $arParams["PAGER_DESC_NUMBERING"],
			"PAGER_DESC_NUMBERING_CACHE_TIME" => $arParams["PAGER_DESC_NUMBERING_CACHE_TIME"],
			"PAGER_SHOW_ALL" => $arParams["PAGER_SHOW_ALL"],
			"FILTER_NAME" => "searchFilter",
			"SECTION_ID" => "",
			"SECTION_CODE" => "",
			"SECTION_USER_FIELDS" => array(),
			"INCLUDE_SUBSECTIONS" => "Y",
			"SHOW_ALL_WO_SECTION" => "Y",
			"META_KEYWORDS" => "",
			"META_DESCRIPTION" => "",
			"BROWSER_TITLE" => "",
			"ADD_SECTIONS_CHAIN" => "N",
			"SET_TITLE" => "N",
			"SET_STATUS_404" => "Y",
			"CACHE_FILTER" => "N",
			"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
			"RESTART" => "N",
			"NO_WORD_LOGIC" => ($arParams["NO_WORD_LOGIC"] != "N" ? "Y" : "N"),
			"USE_LANGUAGE_GUESS" => ($arParams["USE_LANGUAGE_GUESS"] != "N" ? "Y" : "N"),
			"CHECK_DATES" => "Y",
			"DISPLAY_WISH_BUTTONS" => $arParams["DISPLAY_WISH_BUTTONS"],
			"DISPLAY_COMPARE" => $arParams["DISPLAY_COMPARE"],
			"DEFAULT_COUNT" => $arParams["DEFAULT_COUNT"],
			"SHOW_HINTS" => $arParams["SHOW_HINTS"],
			"SHOW_DISCOUNT_PERCENT" => $arParams["SHOW_DISCOUNT_PERCENT"],
			"SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
			"SALE_STIKER" => $arParams["SALE_STIKER"],
			"SHOW_RATING" => $arParams["SHOW_RATING"],
			"SHOW_DISCOUNT_TIME" => $arParams["SHOW_DISCOUNT_TIME"],
			"ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
			"PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
			"USE_MAIN_ELEMENT_SECTION" => $arParams["USE_MAIN_ELEMENT_SECTION"],
			"OFFER_HIDE_NAME_PROPS" => $arParams["OFFER_HIDE_NAME_PROPS"],
			"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
		),
		$component
	);

	return;
}
?>

<h1 class="nd-catalog__h1"><?= $APPLICATION->GetTitle() ?></h1>

<?
$APPLICATION->IncludeComponent(
	"bitrix:catalog.section.list",
	"list_catalog_sections_newdesign",
	array(
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
		"COUNT_ELEMENTS" => "N",
		"TOP_DEPTH" => "2",
		"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
		"SET_STATUS_404" => "Y",
		"VIEW_MODE" => "LIST",
		"SHOW_PARENT_NAME" => "N",
		"ADD_SECTIONS_CHAIN" => "N",
		// PICTURE нужен как запасная иконка карточки, CODE — чтобы найти иконку из макета
		"SECTION_FIELDS" => array("NAME", "CODE", "PICTURE"),
		// UF_ND_ICON — «фото-иконка раздела», главный источник картинки карточки
		"SECTION_USER_FIELDS" => array("UF_SECTION_IN_MENU", "UF_MENULINK_TOP", "UF_ND_ICON"),
		"USE_FILTER" => "Y",
		"FILTER_NAME" => "arrFilterLinked",
		"DISPLAY_PANEL" => $arParams["DISPLAY_PANEL"],
	),
	$component
);
?>

<?/* Бренды — тот же слайдер, что на главной. Обёртка нужна только затем, чтобы
     погасить его собственные поля: на внутренней странице контейнер задаёт тема. */?>
<div class="nd-catalog-brands">
<?
$APPLICATION->IncludeComponent(
	'bitrix:news.list',
	'list_brands_newdesign',
	[
		'IBLOCK_TYPE' => 'aspro_next_content',
		'IBLOCK_ID' => '12',
		'NEWS_COUNT' => '80',
		'SORT_BY1' => 'SORT',
		'SORT_ORDER1' => 'ASC',
		'SORT_BY2' => 'NAME',
		'SORT_ORDER2' => 'ASC',
		'FIELD_CODE' => ['NAME', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL'],
		'PROPERTY_CODE' => ['LINK'],
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
		'TITLE_BLOCK' => 'Бренды',
		'ALL_URL' => SITE_DIR.'brands/',
		'COMPONENT_TEMPLATE' => 'list_brands_newdesign',
	],
	false,
	['HIDE_ICONS' => 'Y']
);
?>
</div>
