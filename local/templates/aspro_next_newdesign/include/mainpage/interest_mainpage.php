<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Блок «Может заинтересовать» — две вкладки:
 *
 *   «Акции»       — товары, привязанные к активным акциям (ИБ 17, свойство
 *                   LINK_GOODS «Элементы участвующие в акции»);
 *   «Хиты месяца» — товары каталога со значением HIT_MONTH в свойстве HIT.
 *
 * Карточка та же, что в списке раздела и в блоке «С этим товаром покупают» —
 * шаблон catalog.section/catalog_blockcolors_newdesign, поэтому отдельную
 * копию не заводим: у него уже есть и вёрстка карточки, и подключение
 * css/newdesign-catalog.css с js/newdesign-catalog.js (по одному разу
 * на страницу, защита через ND_CATALOG_ASSETS).
 *
 * Заголовок блока — включаемая область include/newdesign/mainpage/,
 * как у остальных блоков главной.
 *
 * Стили вкладок и сетки 5 в ряд — .nd-interest в css/newdesign.css,
 * переключение — js/newdesign-ui.js.
 */
if (!CModule::IncludeModule('iblock')) {
	return;
}

$ndCatalogIblock = 19;
$ndSalesIblock = 17;

/* Регион: акции привязаны к нему свойством LINK_REGION, как в блоке «Акции»
   ниже по странице. Без привязки акция общая для всех. */
$ndRegionId = 0;
if (class_exists('CNextRegionality')) {
	$ndRegion = CNextRegionality::getCurrentRegion();
	$ndRegionId = is_array($ndRegion) ? (int) ($ndRegion['ID'] ?? 0) : 0;
}

/**
 * Товары активных акций. Свойство LINK_GOODS множественное, поэтому выборка
 * отдаёт по строке на каждое значение — просто собираем ID.
 */
$ndSaleGoods = [];
$arSalesFilter = [
	'IBLOCK_ID' => $ndSalesIblock,
	'ACTIVE' => 'Y',
	'ACTIVE_DATE' => 'Y',
	'!PROPERTY_LINK_GOODS' => false,
];
if ($ndRegionId) {
	// ИЛИ обязательно подгруппой: на верхнем уровне оно распространится
	// на весь фильтр, включая IBLOCK_ID, и в выборку полезут чужие инфоблоки
	$arSalesFilter[] = [
		'LOGIC' => 'OR',
		['PROPERTY_LINK_REGION' => $ndRegionId],
		['PROPERTY_LINK_REGION' => false],
	];
}
$rsSales = CIBlockElement::GetList(
	['SORT' => 'ASC', 'ID' => 'DESC'],
	$arSalesFilter,
	false,
	false,
	['ID', 'IBLOCK_ID', 'PROPERTY_LINK_GOODS']
);
while ($arSale = $rsSales->Fetch()) {
	$goodsId = (int) $arSale['PROPERTY_LINK_GOODS_VALUE'];
	if ($goodsId > 0) {
		$ndSaleGoods[$goodsId] = $goodsId;
	}
}

/**
 * Хиты месяца — значение HIT_MONTH списочного свойства HIT. ID значения
 * ищем по XML_ID: он переносится между средами, а числовой ID у каждой свой.
 * Запасной вариант — поиск по названию, если значение заводили руками.
 */
$ndHitEnumId = 0;
$rsEnum = CIBlockPropertyEnum::GetList(
	[],
	['IBLOCK_ID' => $ndCatalogIblock, 'CODE' => 'HIT', 'XML_ID' => 'HIT_MONTH']
);
if ($arEnum = $rsEnum->Fetch()) {
	$ndHitEnumId = (int) $arEnum['ID'];
}
if (!$ndHitEnumId) {
	$rsEnum = CIBlockPropertyEnum::GetList([], ['IBLOCK_ID' => $ndCatalogIblock, 'CODE' => 'HIT']);
	while ($arEnum = $rsEnum->Fetch()) {
		if (mb_strtolower(trim($arEnum['VALUE'])) === 'хит месяца') {
			$ndHitEnumId = (int) $arEnum['ID'];
			break;
		}
	}
}

$GLOBALS['arNdInterestSale'] = ['ID' => array_values($ndSaleGoods)];
$GLOBALS['arNdInterestHit'] = ['PROPERTY_HIT' => $ndHitEnumId];

/**
 * Вкладку показываем, только если по её условию реально есть активные
 * товары (Ирина, 2026-08-05: «если хитов месяца нет, то и кнопки не должно
 * быть»). Проверять наличие самой привязки мало: акция может ссылаться на
 * снятый с публикации товар, а значение HIT_MONTH — быть заведённым
 * в справочнике, но никому не проставленным.
 * Третьим аргументом пустой массив — это COUNT одним запросом, без выборки.
 */
$ndCount = function ($arFilter) use ($ndCatalogIblock) {
	if (!$arFilter) {
		return 0;
	}

	return (int) CIBlockElement::GetList(
		[],
		array_merge(
			['IBLOCK_ID' => $ndCatalogIblock, 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y'],
			$arFilter
		),
		[]
	);
};

$ndTabs = [];
if ($ndSaleGoods && $ndCount($GLOBALS['arNdInterestSale'])) {
	$ndTabs['sale'] = ['NAME' => 'Акции', 'FILTER' => 'arNdInterestSale'];
}
if ($ndHitEnumId && $ndCount($GLOBALS['arNdInterestHit'])) {
	$ndTabs['hit'] = ['NAME' => 'Хиты месяца', 'FILTER' => 'arNdInterestHit'];
}

// Ни одной вкладки — блока на странице нет вовсе
if (!$ndTabs) {
	return;
}

/**
 * Параметры списка. Набор тот же, что у блока «С этим товаром покупают»
 * на детальной, только своя постраничка (10 карточек — два ряда по пять
 * из макета) и свой фильтр.
 */
$ndListParams = [
	'IBLOCK_TYPE' => 'aspro_next_catalog',
	'IBLOCK_ID' => $ndCatalogIblock,
	'ELEMENT_SORT_FIELD' => 'SORT',
	'ELEMENT_SORT_ORDER' => 'asc',
	'ELEMENT_SORT_FIELD2' => 'id',
	'ELEMENT_SORT_ORDER2' => 'desc',
	'SHOW_ALL_WO_SECTION' => 'Y',
	'SECTION_ID' => '',
	'SECTION_CODE' => '',
	'USE_REGION' => 'Y',
	'STORES' => '',
	'SHOW_UNABLE_SKU_PROPS' => 'Y',
	'AJAX_REQUEST' => 'N',
	'INCLUDE_SUBSECTIONS' => 'N',
	'PAGE_ELEMENT_COUNT' => '10',
	'LINE_ELEMENT_COUNT' => '5',
	// TYPE_1 — режим, в котором торговые предложения показываются прямо
	// на карточке (цена, единицы, счётчик, корзина). При других значениях
	// шаблон сваливается на «от N ₽» и кнопку «Подробнее»
	'TYPE_SKU' => 'TYPE_1',
	'SHOW_ARTICLE_SKU' => 'Y',
	'SHOW_MEASURE_WITH_RATIO' => 'N',
	'SHOW_MEASURE' => 'Y',
	'OFFERS_SORT_FIELD' => 'sort',
	'OFFERS_SORT_ORDER' => 'asc',
	'OFFERS_SORT_FIELD2' => 'name',
	'OFFERS_SORT_ORDER2' => 'asc',
	'OFFERS_LIMIT' => '300',
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
	'CACHE_TYPE' => 'A',
	'CACHE_TIME' => '36000',
	'CACHE_GROUPS' => 'N',
	// фильтр у вкладок разный, а кэш один на компонент — учитываем его в ключе
	'CACHE_FILTER' => 'Y',
	'META_KEYWORDS' => '-',
	'META_DESCRIPTION' => '-',
	'BROWSER_TITLE' => '-',
	'ADD_SECTIONS_CHAIN' => 'N',
	'HIDE_NOT_AVAILABLE' => 'N',
	'HIDE_NOT_AVAILABLE_OFFERS' => 'N',
	'DISPLAY_COMPARE' => 'N',
	'SET_TITLE' => 'N',
	'SET_STATUS_404' => 'N',
	'SHOW_404' => 'N',
	'MESSAGE_404' => '',
	'PRICE_CODE' => ['BASE'],
	'USE_PRICE_COUNT' => 'Y',
	'SHOW_PRICE_COUNT' => '1',
	'PRICE_VAT_INCLUDE' => 'Y',
	'USE_PRODUCT_QUANTITY' => 'Y',
	// набор свойств взят со страницы каталога (/catalog/index.php): без него
	// у товара с торговыми предложениями карточка сваливается в «Подробнее»
	// вместо цены со счётчиком и корзиной
	'PRODUCT_DISPLAY_MODE' => 'Y',
	// без списка полей/свойств торговых предложений компонент не грузит
	// OFFERS, и карточка показывает «от N ₽» с кнопкой «Подробнее»
	'OFFERS_FIELD_CODE' => ['ID', 'XML_ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'CML2_LINK', 'DETAIL_PAGE_URL'],
	'OFFERS_PROPERTY_CODE' => ['VID', 'DLINA_STR', 'WIDTH', 'THICK', 'VES', 'MATERIAL', 'GARANTY', 'ARTICLE', 'VWS_N', 'DLINA', 'UNIT_KOEF', 'MODEL_OP', 'MONTAZ_PAZ', 'NORM', 'VALUE_TOV', 'BASE_KOEF', 'RAZM', 'SVET', 'TYPE_PRODUCTS', 'PRODUCT_VIDEO', 'COLOR_REF2', 'COLOR_REF', 'TSVET_OGRAZHDENIY', 'WIDTH_D', 'TOLSHINA', 'DECKING_PROFILE', 'TYPE'],
	'ADD_PICT_PROP' => 'MORE_PHOTO',
	'OFFER_ADD_PICT_PROP' => 'MORE_PHOTO',
	'LABEL_PROP' => '',
	'PRODUCT_SUBSCRIPTION' => 'N',
	'OFFER_TREE_PROPS' => [
		'COLOR_REF', 'DLINA', 'COLOR_REF2', 'MONTAZ_PAZ', 'MODEL_OP',
		'VALUE_TOV', 'RAZM', 'WIDTH_D', 'SVET', 'VWS_N',
	],
	'OFFERS_CART_PROPERTIES' => [
		'ARTICLE', 'MODEL_OP', 'NORM', 'VALUE_TOV', 'TYPE_PRODUCTS',
		'PRODUCT_VIDEO', 'COLOR_REF2', 'COLOR_REF', 'WIDTH_D',
	],
	'SHOW_DISCOUNT_PERCENT_NUMBER' => 'Y',
	'LIST_PROPERTY_CODE' => [
		'MINIMUM_PRICE', 'MAXIMUM_PRICE', 'HIT', 'BRAND', 'PROP_2065', 'POPUP_VIDEO',
		'CML2_ARTICLE', 'ASSOCIATED', 'PROP_2052', 'SET', 'UNIT_KOEF', 'BASE_KOEF',
		'COLOR_MAIN_EL', 'USAGE_DOSKA_DPK', 'ATTRIBUTES', 'TOLSHINA', 'PROP_2083',
		'CML2_LINK', 'DECKING_PROFILE', 'COLOR_REF2',
	],
	'DISPLAY_TOP_PAGER' => 'N',
	'DISPLAY_BOTTOM_PAGER' => 'N',
	'PAGER_SHOW_ALWAYS' => 'N',
	'PAGER_TEMPLATE' => 'main',
	'PAGER_DESC_NUMBERING' => 'N',
	'PAGER_SHOW_ALL' => 'N',
	'ADD_CHAIN_ITEM' => 'N',
	'SHOW_QUANTITY' => 'Y',
	'SHOW_QUANTITY_COUNT' => 'Y',
	'SHOW_DISCOUNT_PERCENT' => 'Y',
	'SHOW_DISCOUNT_TIME' => 'N',
	'SHOW_OLD_PRICE' => 'Y',
	'CONVERT_CURRENCY' => 'Y',
	'CURRENCY_ID' => 'RUB',
	'USE_STORE' => 'N',
	'DISPLAY_WISH_BUTTONS' => 'N',
	'LIST_DISPLAY_POPUP_IMAGE' => 'Y',
	'DEFAULT_COUNT' => '1',
	'SHOW_HINTS' => 'Y',
	'OFFER_HIDE_NAME_PROPS' => 'N',
	'ADD_PROPERTIES_TO_BASKET' => 'Y',
	'PARTIAL_PRODUCT_PROPERTIES' => 'Y',
	'PRODUCT_PROPERTIES' => [],
	'SALE_STIKER' => 'SALE_TEXT',
	'STIKERS_PROP' => 'HIT',
	'SHOW_RATING' => 'N',
	'COMPONENT_TEMPLATE' => 'catalog_blockcolors_newdesign',
	'COMPATIBLE_MODE' => 'Y',
	'DISABLE_INIT_JS_IN_COMPONENT' => 'N',
];

$ndFirst = key($ndTabs);
?>
<section class="nd-interest">
	<div class="nd-interest__title">
		<? $APPLICATION->IncludeFile(
			SITE_DIR.'include/newdesign/mainpage/interest_title.php',
			[],
			['MODE' => 'html', 'NAME' => 'Заголовок блока «Может заинтересовать»']
		); ?>
	</div>

	<? /* Вкладки: активная красная, вторая серая — по макету во всю ширину,
	      пополам. Переключение — js/newdesign-ui.js. */ ?>
	<div class="nd-interest__tabs">
		<? foreach ($ndTabs as $ndKey => $ndTab): ?>
			<button class="nd-interest__tab<?= $ndKey === $ndFirst ? ' is-active' : '' ?>"
				type="button" data-nd-interest-tab="<?= $ndKey ?>"
				aria-selected="<?= $ndKey === $ndFirst ? 'true' : 'false' ?>">
				<? if ($ndKey === 'sale'): ?>
					<?// Иконки рисуем обводкой в currentColor: на активной вкладке
					   // фон красный, и залитый значок на нём пропадал.?>
					<svg class="nd-interest__ico" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
						<circle cx="10" cy="10" r="8.2" stroke="currentColor" stroke-width="1.6"/>
						<path d="M7.2 12.8l5.6-5.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
						<circle cx="7.6" cy="7.6" r="1.3" stroke="currentColor" stroke-width="1.4"/>
						<circle cx="12.4" cy="12.4" r="1.3" stroke="currentColor" stroke-width="1.4"/>
					</svg>
				<? else: ?>
					<svg class="nd-interest__ico" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
						<path d="M10 1.5s1.2 2.6.2 4.5c-.8 1.5-2.6 2-2.6 4 0 .9.4 1.6.9 2-1.7-.4-3.5-2-3.5-4.6 0-.6.1-1.1.3-1.6C3.6 7.2 3 8.9 3 10.7 3 15 6.4 18.5 10 18.5s7-3.5 7-7.8c0-4.6-4-7.6-7-9.2z" fill="currentColor"/>
					</svg>
				<? endif; ?>
				<span><?= htmlspecialcharsbx($ndTab['NAME']) ?></span>
			</button>
		<? endforeach; ?>
	</div>

	<? foreach ($ndTabs as $ndKey => $ndTab): ?>
		<div class="nd-interest__pane<?= $ndKey === $ndFirst ? ' is-active' : '' ?>" data-nd-interest-pane="<?= $ndKey ?>"<?= $ndKey === $ndFirst ? '' : ' hidden' ?>>
			<? $APPLICATION->IncludeComponent(
				'bitrix:catalog.section',
				'catalog_blockcolors_newdesign',
				array_merge($ndListParams, ['FILTER_NAME' => $ndTab['FILTER']]),
				false,
				['HIDE_ICONS' => 'Y']
			); ?>
		</div>
	<? endforeach; ?>
</section>
