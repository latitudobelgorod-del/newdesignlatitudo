<? /** @var $block array */ ?><?
/**
 * Блок редактора «Элементы инфоблока», указывающий на каталог.
 *
 * Вид — как карточки в списке раздела каталога (Ирина, 2026-08-11): тот же
 * шаблон catalog.section/catalog_blockcolors_newdesign, что рисует
 * /catalog/<раздел>/. Копию не заводим: шаблон сам тянет свои
 * newdesign-catalog.css/js (защита ND_CATALOG_ASSETS, несколько вызовов на
 * странице безопасны), а копия развела бы карточку с каталогом при первой
 * правке макета. Прежний шаблон catalog_blockcolors_slide_editor рисовал
 * карточки старой темы.
 *
 * Набор параметров взят с include/brand_products.php — это уже работающий
 * вызов того же шаблона ВНЕ раздела каталога. Главное из него:
 *  - TYPE_SKU и OFFERS_FIELD_CODE/OFFERS_PROPERTY_CODE обязательны, без них
 *    компонент не грузит торговые предложения и карточка сваливается на
 *    «от N ₽» с кнопкой «Подробнее» вместо цены со счётчиком и корзиной;
 *  - цены и склады переопределяет регион, иначе в регионах не те остатки.
 *
 * Постраничной навигации и кнопки «Показать ещё» у блока нет: состав задаёт
 * контент-менеджер, листать нечего. Одного DISPLAY_BOTTOM_PAGER=N мало —
 * шаблон всё равно печатал пустую обёртку навигации, а это 64 px пустоты под
 * сеткой; поэтому у него появился флаг ND_NO_PAGER.
 * Плиток акций тоже нет: их разметку печатает пул #nd-promo-pool со страницы
 * раздела, здесь его нет.
 */

global $arTheme;
global $arRegion;
global $APPLICATION;

if (!$arTheme) {
	$arTheme = $APPLICATION->IncludeComponent(
		'aspro:theme.next',
		'.default',
		['COMPONENT_TEMPLATE' => '.default'],
		false,
		['HIDE_ICONS' => 'Y']
	);
}

$ndPriceCode = ['BASE'];
$ndStores = [];
if ($arRegion) {
	if (!empty($arRegion['LIST_PRICES']) && reset($arRegion['LIST_PRICES']) != 'component') {
		$ndPriceCode = array_keys($arRegion['LIST_PRICES']);
	}
	if (!empty($arRegion['LIST_STORES']) && reset($arRegion['LIST_STORES']) != 'component') {
		$ndStores = $arRegion['LIST_STORES'];
	}
}

global $sprintSearchFilter;
$sprintSearchFilter = [
	'=ID' => $block['element_ids'],
];

$APPLICATION->IncludeComponent(
	'bitrix:catalog.section',
	'catalog_blockcolors_newdesign',
	[
		'IBLOCK_TYPE' => 'aspro_next_catalog',
		'IBLOCK_ID' => $block['iblock_id'],
		'COMPONENT_TEMPLATE' => 'catalog_blockcolors_newdesign',
		'ELEMENT_SORT_FIELD' => 'SORT',
		'ELEMENT_SORT_ORDER' => 'ASC',
		'ELEMENT_SORT_FIELD2' => 'ID',
		'ELEMENT_SORT_ORDER2' => 'DESC',
		'FILTER_NAME' => 'sprintSearchFilter',
		'SHOW_ALL_WO_SECTION' => 'Y',
		'SECTION_ID' => '',
		'SECTION_CODE' => '',
		'USE_REGION' => 'N',
		'SHOW_UNABLE_SKU_PROPS' => 'N',
		'AJAX_REQUEST' => 'N',
		'INCLUDE_SUBSECTIONS' => 'N',
		'PAGE_ELEMENT_COUNT' => '500',
		'LINE_ELEMENT_COUNT' => '',
		'DISPLAY_TYPE' => 'block',
		'TYPE_SKU' => $arTheme['TYPE_SKU']['VALUE'],
		'PROPERTY_CODE' => [
			'MINIMUM_PRICE',
			'MAXIMUM_PRICE',
			'HIT',
			'BRAND',
			'PROP_2065',
			'CML2_ARTICLE',
			'PROP_2052',
			'SET',
			'UNIT_KOEF',
			'BASE_KOEF',
			'ASSOCIATED',
			'TOLSHINA',
			'ATTRIBUTES',
			'PROP_2083',
			'CML2_LINK',
			'DECKING_PROFILE',
			'COLOR_REF2',
		],
		'SHOW_ARTICLE_SKU' => 'N',
		'SHOW_MEASURE_WITH_RATIO' => 'N',
		'OFFERS_FIELD_CODE' => ['NAME', 'CML2_LINK', 'DETAIL_PAGE_URL'],
		'OFFERS_PROPERTY_CODE' => [
			'DLINA_STR',
			'ARTICLE',
			'VWS_N',
			'DLINA',
			'MODEL_OP',
			'NORM',
			'VALUE_TOV',
			'SVET',
			'TYPE_PRODUCTS',
			'COLOR_REF2',
			'COLOR_REF',
			'WIDTH_D',
			'NORM_PACKAGE',
			'SERIES',
			'COLOR_SHIFR',
			'DECKING_PROFILE',
			'TYPE',
		],
		'OFFERS_SORT_FIELD' => 'sort',
		'OFFERS_SORT_ORDER' => 'asc',
		'OFFERS_SORT_FIELD2' => 'sort',
		'OFFERS_SORT_ORDER2' => 'asc',
		'OFFER_TREE_PROPS' => [
			'COLOR_REF',
			'DLINA',
			'COLOR_REF2',
			'MONTAZ_PAZ',
			'MODEL_OP',
			'VALUE_TOV',
			'SVET',
			'WIDTH_D',
			'VWS_N',
		],
		'OFFERS_LIMIT' => '',
		'SECTION_URL' => '',
		'DETAIL_URL' => '',
		'BASKET_URL' => '/basket/',
		'ACTION_VARIABLE' => 'action',
		'PRODUCT_ID_VARIABLE' => 'id',
		'PRODUCT_QUANTITY_VARIABLE' => 'quantity',
		'PRODUCT_PROPS_VARIABLE' => 'prop',
		'SECTION_ID_VARIABLE' => 'SECTION_ID',
		'SET_LAST_MODIFIED' => 'N',
		'AJAX_MODE' => 'N',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_STYLE' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
		'AJAX_OPTION_ADDITIONAL' => '',
		'CACHE_TYPE' => 'N',
		'CACHE_TIME' => '3600000',
		'CACHE_GROUPS' => 'N',
		'CACHE_FILTER' => 'Y',
		'META_KEYWORDS' => '-',
		'META_DESCRIPTION' => '-',
		'BROWSER_TITLE' => '-',
		'ADD_SECTIONS_CHAIN' => 'N',
		'ADD_CHAIN_ITEM' => 'N',
		'HIDE_NOT_AVAILABLE' => 'N',
		'HIDE_NOT_AVAILABLE_OFFERS' => 'Y',
		'DISPLAY_COMPARE' => 'N',
		'SET_TITLE' => 'N',
		'SET_STATUS_404' => 'N',
		'SHOW_404' => 'N',
		'MESSAGE_404' => '',
		'PRICE_CODE' => $ndPriceCode,
		'USE_PRICE_COUNT' => 'Y',
		'SHOW_PRICE_COUNT' => '1',
		'PRICE_VAT_INCLUDE' => 'N',
		'USE_PRODUCT_QUANTITY' => 'Y',
		'OFFERS_CART_PROPERTIES' => [],
		// обёртку навигации шаблон при этом флаге не печатает вовсе
		'ND_NO_PAGER' => 'Y',
		'DISPLAY_TOP_PAGER' => 'N',
		'DISPLAY_BOTTOM_PAGER' => 'N',
		'PAGER_TITLE' => 'Товары',
		'PAGER_SHOW_ALWAYS' => 'N',
		'PAGER_TEMPLATE' => '',
		'PAGER_DESC_NUMBERING' => 'N',
		'PAGER_DESC_NUMBERING_CACHE_TIME' => '36000',
		'PAGER_SHOW_ALL' => 'N',
		'PAGER_BASE_LINK_ENABLE' => 'N',
		'DISABLE_INIT_JS_IN_COMPONENT' => 'N',
		'SHOW_QUANTITY' => 'Y',
		'SHOW_QUANTITY_COUNT' => 'Y',
		'SHOW_OLD_PRICE' => 'Y',
		'SHOW_DISCOUNT_PERCENT' => 'Y',
		'SHOW_DISCOUNT_PERCENT_NUMBER' => 'Y',
		'SHOW_DISCOUNT_TIME' => 'N',
		'CONVERT_CURRENCY' => 'Y',
		'CURRENCY_ID' => 'RUB',
		'USE_STORE' => 'N',
		'STORES' => $ndStores,
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
		'PRODUCT_PROPERTIES' => [],
		'SALE_STIKER' => 'SALE_TEXT',
		'STIKERS_PROP' => 'HIT',
		'CUSTOM_FILTER' => '{"CLASS_ID":"CondGroup","DATA":{"All":"AND","True":"True"},"CHILDREN":[]}',
		'SHOW_RATING' => 'N',
		'SECTION_USER_FIELDS' => [],
		'BACKGROUND_IMAGE' => '-',
		'SEF_MODE' => 'N',
		'ADD_PICT_PROP' => 'MORE_PHOTO',
		'OFFER_ADD_PICT_PROP' => 'MORE_PHOTO',
		'GALLERY_ITEM_SHOW' => $arTheme['GALLERY_ITEM_SHOW']['VALUE'],
		'MAX_GALLERY_ITEMS' => $arTheme['GALLERY_ITEM_SHOW']['DEPENDENT_PARAMS']['MAX_GALLERY_ITEMS']['VALUE'],
		'ADD_DETAIL_TO_GALLERY_IN_LIST' => $arTheme['GALLERY_ITEM_SHOW']['DEPENDENT_PARAMS']['ADD_DETAIL_TO_GALLERY_IN_LIST']['VALUE'],
		'REVIEWS_VIEW' => ($arTheme['REVIEWS_VIEW']['VALUE'] == 'EXTENDED'),
		'SET_BROWSER_TITLE' => 'N',
		'SET_META_KEYWORDS' => 'N',
		'SET_META_DESCRIPTION' => 'N',
		'COMPATIBLE_MODE' => 'Y',
	],
	false,
	['HIDE_ICONS' => 'Y']
);
?>
