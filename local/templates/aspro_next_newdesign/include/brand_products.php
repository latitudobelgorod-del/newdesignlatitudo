<?php
/* Список товаров бренда, разложенный по разделам.

   Один и тот же файл зовут два места, и это не случайно:
     - page_blocks/element_1.php шаблона news/partners_newdesign — сама страница;
     - /local/ajax/brand_products.php — догрузка хвоста раздела.
   Параметры каталога (свойства, торговые предложения, цены) обязаны совпадать
   до буквы, иначе догруженные карточки отличаются от напечатанных страницей.
   Поэтому вызов компонента здесь ровно один.

   Вход — массив $ldBrand:
     MODE        'sections' — раскладка по разделам с якорями (у бренда выбран
                 «Шаблон №1, вывод по разделам»), 'flat' — сплошной список
                 («Шаблон №2, вывод списком»)
     FILTER      фильтр товаров: бренд, а для догрузки ещё и раздел
     PER_SECTION сколько карточек показывать сразу — в разделе или всего
     ITEMS_ONLY  'Y' — отдать голые карточки без обёрток, стилей и скриптов
     OFFSET      сколько карточек уже на странице (только с ITEMS_ONLY)
     TITLE       заголовок над списком
     PRICE_CODE  типы цен, если region-логика уже посчитала свои
     STORES      склады, туда же

   Файл лежит в /local: за пределами /local ничего не версионируется, а этот
   код должен уезжать на прод и на вторую машину вместе с шаблоном (WORKFLOW.md). */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $ldBrand */
/** @var CMain $APPLICATION */

global $APPLICATION, $arTheme, $arRegion;

/* Настройки темы страница получает при сборке шапки (header.php), а догрузка
   идёт без неё — без них у карточек не было бы ни типа торговых предложений,
   ни галереи. Зовём тот же компонент, что и шапка: CNext::GetFrontParametrsValues()
   отдаёт плоские значения, а шаблонам нужен вид ['TYPE_SKU']['VALUE']. */
if (!isset($arTheme['TYPE_SKU']) || !is_array($arTheme['TYPE_SKU'])) {
	$arTheme = $APPLICATION->IncludeComponent(
		'aspro:theme.next',
		'.default',
		['COMPONENT_TEMPLATE' => '.default'],
		false,
		['HIDE_ICONS' => 'Y']
	);
}

$ldBrand = (array)($ldBrand ?? []);
$ldItemsOnly = (($ldBrand['ITEMS_ONLY'] ?? 'N') === 'Y');
$ldFlat = (($ldBrand['MODE'] ?? 'sections') === 'flat');
$ldPriceCode = !empty($ldBrand['PRICE_CODE']) ? (array)$ldBrand['PRICE_CODE'] : ['BASE'];
$ldStores = !empty($ldBrand['STORES']) ? (array)$ldBrand['STORES'] : [];

// Регион переопределяет цены и склады — как в include/news.detail.products_slider.php
if ($arRegion) {
	if (!empty($arRegion['LIST_PRICES']) && reset($arRegion['LIST_PRICES']) != 'component') {
		$ldPriceCode = array_keys($arRegion['LIST_PRICES']);
	}
	if (!empty($arRegion['LIST_STORES']) && reset($arRegion['LIST_STORES']) != 'component') {
		$ldStores = $arRegion['LIST_STORES'];
	}
}

$GLOBALS['arrProductsFilter'] = (array)($ldBrand['FILTER'] ?? []);

$ldIblockId = (int)\Bitrix\Main\Config\Option::get('aspro.next', 'CATALOG_IBLOCK_ID', 19);
$ldPerPortion = (int)($ldBrand['PER_SECTION'] ?? ($ldFlat ? 20 : 8));
$ldOffset = (int)($ldBrand['OFFSET'] ?? 0);

/* Сколько элементов вообще читать.

   По разделам — весь бренд: чтобы разложить товары по разделам и посчитать,
   сколько в каждом спрятано за кнопкой, нужен весь список.

   Сплошным списком — только показываемая часть. У 4SiS в бренде 819 товаров,
   и вычитывать их все ради двадцати карточек значит ждать лишние две секунды;
   общее число берём отдельным счётным запросом, он дешёвый. */
$ldTotal = 0;
if ($ldFlat) {
	$ldTotal = (int)CIBlockElement::GetList(
		[],
		array_merge($GLOBALS['arrProductsFilter'], ['IBLOCK_ID' => $ldIblockId]),
		[]
	);
	$ldElementCount = $ldOffset + $ldPerPortion;
} else {
	$ldElementCount = 1000;
}

$APPLICATION->IncludeComponent(
	'bitrix:catalog.section',
	'catalog_blockcolors_newdesign',
	[
		// раскладку и нарезку делает result_modifier шаблона
		'LD_GROUP_BY_SECTION' => ($ldFlat ? 'N' : 'Y'),
		'LD_FLAT' => ($ldFlat ? 'Y' : 'N'),
		'LD_PER_SECTION' => $ldPerPortion,
		'LD_TOTAL' => $ldTotal,
		'LD_ITEMS_ONLY' => ($ldItemsOnly ? 'Y' : 'N'),
		'LD_OFFSET' => $ldOffset,
		// чем шаблон адресует догрузку: бренд и свойство связи (см. AJAX_QUERY)
		'LD_AJAX_QUERY' => (string)($ldBrand['AJAX_QUERY'] ?? ''),

		'IBLOCK_TYPE' => 'aspro_next_catalog',
		'IBLOCK_ID' => \Bitrix\Main\Config\Option::get('aspro.next', 'CATALOG_IBLOCK_ID', 19),
		'ELEMENT_SORT_FIELD' => 'SORT',
		'ELEMENT_SORT_ORDER' => 'ASC',
		'ELEMENT_SORT_FIELD2' => 'ID',
		'ELEMENT_SORT_ORDER2' => 'DESC',
		'FILTER_NAME' => 'arrProductsFilter',
		'ELEMENT_COUNT' => $ldElementCount,
		'SHOW_ALL_WO_SECTION' => 'Y',
		'SECTION_ID' => '',
		'SECTION_CODE' => '',
		'COMPONENT_TEMPLATE' => 'catalog_blockcolors_newdesign',
		'USE_REGION' => 'N',
		'SHOW_UNABLE_SKU_PROPS' => 'N',
		'AJAX_REQUEST' => 'N',
		'INCLUDE_SUBSECTIONS' => 'N',
		'PAGE_ELEMENT_COUNT' => '1000',
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
		'CACHE_FILTER' => 'N',
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
		'PRICE_CODE' => $ldPriceCode,
		'USE_PRICE_COUNT' => 'Y',
		'SHOW_PRICE_COUNT' => '1',
		'PRICE_VAT_INCLUDE' => 'N',
		'USE_PRODUCT_QUANTITY' => 'Y',
		'OFFERS_CART_PROPERTIES' => [],
		// свою постраничную навигацию список бренда не рисует: у каждого
		// раздела своя кнопка «Показать ещё», сквозной пагинации тут нет
		'DISPLAY_TOP_PAGER' => 'N',
		'DISPLAY_BOTTOM_PAGER' => 'N',
		'PAGER_TITLE' => 'Товары',
		'PAGER_SHOW_ALWAYS' => 'N',
		'PAGER_TEMPLATE' => 'only-pokaz-ewe',
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
		'STORES' => $ldStores,
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
		'TITLE' => ($ldItemsOnly ? '' : (string)($ldBrand['TITLE'] ?? '')),
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
	false
);
