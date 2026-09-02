<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
global $USER;
?>
<?php
/* Списки инфоблоков ($arIBlocks, $arIBlockList) и пустой $arOffers, что стояли
   здесь раньше, нужны были только штатному bitrix:search.page — какие
   инфоблоки ему обходить. Своему подбору они не нужны: инфоблоки каталога и
   предложений он знает сам. */
/* Свой подбор товаров вместо штатного bitrix:search.page (Ирина, 2026-08-17).

   Штатный компонент искал по индексу словоформ модуля «Поиск» и умел только
   целые слова: «террас» не находил «Террасную», а артикул торгового
   предложения не находился вовсе. LatitudoQuickSearch ищет по неполным словам
   — по названию, артикулу и производителю товара и по названиям и артикулам
   его предложений, несколько слов в любом порядке.

   Тот же класс зовут подсказки в шапке (search.title/newdesign), поэтому
   выдача по ссылке «Все товары» совпадает с тем, что показала подсказка.

   Возвращаются ID товаров: предложения подбор сводит к родительскому товару
   сам. Поэтому здесь больше нет прежней надстройки с SubQuery по свойству
   привязки предложений — она добавляла к выборке товары, чьи предложения
   попали в результаты поиска, а это теперь сделано на шаг раньше. */
require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/latitudo_quick_search.php';
/* Приоритет своих марок на выдаче — сортировка ниже по файлу. */
require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/latitudo_brand_weight.php';

$arElements = LatitudoQuickSearch::findProducts(isset($_REQUEST['q']) ? $_REQUEST['q'] : '');

if (is_array($arElements) && !empty($arElements)) {
	global $searchFilter, $arTheme, $arRegion;

	$searchFilter = array(
		"=ID" => $arElements,
	);


	/* Умный фильтр слева — как в разделе каталога (Ирина, 2026-08-12).
	   Пункты и значения считаются только по найденным товарам.

	   Как это работает:
	   - `$searchFilter` (список ID из поиска) кладём в отдельную глобалку и
	     передаём её имя параметром PREFILTER_NAME. Компонент подмешивает
	     префильтр в запрос, которым собирает значения свойств, — и в фильтр
	     попадают только те пункты, что есть у найденных товаров.
	   - FILTER_NAME тот же `searchFilter`: выбранные пункты компонент
	     дописывает в эту же глобалку, не затирая ID-условие, а ниже её
	     забирает catalog.section. Поэтому фильтр обязан отработать **до**
	     списка.
	   - SECTION_ID пуст, поэтому нужен SHOW_ALL_WO_SECTION=Y: иначе компонент
	     положил бы в запрос SECTION_ID=0 и остался бы без значений.
	   - Кеш выключен: содержимое зависит от поискового запроса, а он в ключ
	     кеша компонента не входит.
	   - SEF_MODE=N — у поиска нет ЧПУ-адресов /filter/. Форма уходит методом
	     GET на текущий адрес, а `q` компонент сам добавляет скрытым полем
	     (в HIDDEN попадают все параметры запроса, которые не его).

	   Разметку отдаём в отложенную область `left_menu` — её печатает левая
	   колонка (page_blocks/left_block_newdesign.php из footer.php). Тот же
	   приём у раздела: catalog/main/page_blocks/list_elements_1.php. */
	if ($arParams["USE_FILTER"] === "Y") {
		$GLOBALS['ndSearchPreFilter'] = $searchFilter;

		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:catalog.smart.filter",
			"main_newdesign",
			array(
				"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"SECTION_ID" => "",
				"SHOW_ALL_WO_SECTION" => "Y",
				"FILTER_NAME" => "searchFilter",
				"PREFILTER_NAME" => "ndSearchPreFilter",
				"PRICE_CODE" => $arParams["PRICE_CODE"],
				"CACHE_TYPE" => "N",
				"CACHE_TIME" => "0",
				"CACHE_NOTES" => "",
				"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
				"SAVE_IN_SESSION" => "N",
				"XML_EXPORT" => "N",
				"SECTION_TITLE" => "NAME",
				"SECTION_DESCRIPTION" => "DESCRIPTION",
				"SHOW_HINTS" => $arParams["SHOW_HINTS"],
				"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
				"CURRENCY_ID" => $arParams["CURRENCY_ID"],
				"INSTANT_RELOAD" => "Y",
				"VIEW_MODE" => "vertical",
				"SEF_MODE" => "N",
				"SEF_RULE" => "",
				"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
			),
			$component,
			array("HIDE_ICONS" => "Y")
		);
		$APPLICATION->AddViewContent('left_menu', ob_get_clean());
	}

	/* «Найдено N» — число по всем страницам и уже с учётом фильтра. Считать
	   его своим запросом нельзя: после умного фильтра в $searchFilter лежат
	   и условия по торговым предложениям (ключ OFFERS), которые понимает
	   только catalog.section. Поэтому цифру печатает сам список — отдаёт её в
	   отложенную область `nd_search_count`, а заглушку под неё выводим здесь
	   (ShowViewContent отложенный, порядок в документе роли не играет). */
	$GLOBALS['ND_SEARCH_COUNT'] = true;
	?>
	<div class="catalog">
		<?/* Панель над списком — та же, что на странице раздела (Ирина,
		   2026-08-12): выпадающий список сортировки вместо прежних ссылок
		   «По возрастанию цены / По убыванию цены».

		   Подключаем сам sort_newdesign.php, а не копируем разметку: он
		   самодостаточен — читает sort/order из запроса, умеет подменять адрес
		   на ЧПУ-двойник sotbit.seometa и сам отдаёт наружу $display, $template,
		   $sort и $sort_order, которые ниже уходят в catalog.section. Чипы
		   посадочных страниц он печатает из ND_CATALOG_TAGS_HTML — на поиске
		   этой переменной нет, блок остаётся пустым. */?>
		<? include(__DIR__ . "/../../catalog/main/sort_newdesign.php"); ?>

		<? /* Свои марки первыми — тот же порядок, что в подсказках шапки:
		      EasyDecking, следом LATITUDO, потом остальные (Ирина, 2 сентября
		      2026). Сортируем по служебному свойству-весу: марка у товара —
		      привязка к справочнику, и база умеет упорядочить её только по ID
		      бренда, а нужного порядка из номеров не выходит. Вес проставляет
		      обработчик из local/init.php, разовая заливка —
		      local/tools/nd_brand_weight_fill.php.

		      Вес — первый ключ только для сортировки по умолчанию. Выбрал
		      человек «сначала дешёвые» — его выбор главнее, иначе список
		      разъехался бы на три ценовых лесенки подряд; марка тогда решает
		      только равенство цен. */ ?>
		<?
		$ndBrandSort  = 'PROPERTY_'.LatitudoBrandWeight::CODE;
		$ndSortField  = ($sort === 'sort') ? $ndBrandSort : $sort;
		$ndSortOrder  = ($sort === 'sort') ? 'asc'        : $sort_order;
		$ndSortField2 = ($sort === 'sort') ? 'sort'       : $ndBrandSort;
		$ndSortOrder2 = ($sort === 'sort') ? $sort_order  : 'asc';
		?>

		<div class="ajax_load <?=$display;?>">
		<div class="catalog <?=$display;?> search">
			<?
            $APPLICATION->IncludeComponent(
				"bitrix:catalog.section",
				$template,
				array(
					"USE_REGION" => ($arRegion ? "Y" : "N"),
					"STORES" => $arParams['STORES'],
					"AJAX_REQUEST" => "N",
					"TYPE_SKU" => $arTheme["TYPE_SKU"]["VALUE"],
					"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
					"IBLOCK_ID" => $arParams["IBLOCK_ID"],
					"ELEMENT_SORT_FIELD" => $ndSortField,
					"ELEMENT_SORT_ORDER" => $ndSortOrder,
					"ELEMENT_SORT_FIELD2" => $ndSortField2,
					"ELEMENT_SORT_ORDER2" => $ndSortOrder2,
					"PAGE_ELEMENT_COUNT" => $arParams["PAGE_ELEMENT_COUNT"],
					"LINE_ELEMENT_COUNT" => $arParams["LINE_ELEMENT_COUNT"],
					"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
					"PROPERTY_CODE" => $arParams["PROPERTY_CODE"],

					"SHOW_ARTICLE_SKU" => $arParams["SHOW_ARTICLE_SKU"],
					"SHOW_MEASURE_WITH_RATIO" => $arParams["SHOW_MEASURE_WITH_RATIO"],

					"OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
					"OFFERS_FIELD_CODE" => $arParams["OFFERS_FIELD_CODE"],
					"OFFERS_PROPERTY_CODE" => $arParams["OFFERS_PROPERTY_CODE"],
					"OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
					"OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
					"OFFERS_LIMIT" => $arParams["OFFERS_LIMIT"],
					"OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
					"OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
					'OFFER_TREE_PROPS' => $arParams['OFFER_TREE_PROPS'],
					"SHOW_COUNTER_LIST" => $arParams["SHOW_COUNTER_LIST"],

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
					"DISPLAY_COMPARE" => $arParams["USE_COMPARE"],
					"PRICE_CODE" => $arParams["PRICE_CODE"],
					"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
					"SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
					"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
					"PRODUCT_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],
					"USE_PRODUCT_QUANTITY" => $arParams["USE_PRODUCT_QUANTITY"],
					"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
					"CURRENCY_ID" => $arParams["CURRENCY_ID"],
					"DISPLAY_TOP_PAGER" => "N",
					/* Постраничка как в списке раздела (Ирина, 2026-08-12): номера
					   слева, «Показать ещё» справа. Оба даёт связка
					   pagination_newdesign + обёртка .nd-catlist__nav шаблона
					   карточки — кнопку по этой обёртке дорисовывает
					   js/newdesign-ui.js. Раньше сюда приезжал PAGER_TEMPLATE из
					   параметров комплексного компонента, и номера шли по центру. */
					"DISPLAY_BOTTOM_PAGER" => "Y",
					"PAGER_TITLE" => "",
					"PAGER_SHOW_ALWAYS" => "N",
					"PAGER_TEMPLATE" => "pagination_newdesign",
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
					"SET_STATUS_404" => "N",
					"CACHE_FILTER" => "Y",
					"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
					"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
					"CURRENCY_ID" => $arParams["CURRENCY_ID"],
					"DISPLAY_SHOW_NUMBER" => "N",
					"DEFAULT_COUNT" => $arParams["DEFAULT_COUNT"],
					"SHOW_DISCOUNT_PERCENT" => $arParams["SHOW_DISCOUNT_PERCENT"],
					"SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
					"SALE_STIKER" => $arParams["SALE_STIKER"],
					"STIKERS_PROP" => $arParams["STIKERS_PROP"],
					"SHOW_RATING" => $arParams["SHOW_RATING"],
					"SHOW_DISCOUNT_TIME" => $arParams["SHOW_DISCOUNT_TIME"],
					"ADD_PROPERTIES_TO_BASKET" => (isset($arParams["ADD_PROPERTIES_TO_BASKET"]) ? $arParams["ADD_PROPERTIES_TO_BASKET"] : ''),
					"PARTIAL_PRODUCT_PROPERTIES" => (isset($arParams["PARTIAL_PRODUCT_PROPERTIES"]) ? $arParams["PARTIAL_PRODUCT_PROPERTIES"] : ''),
					"USE_MAIN_ELEMENT_SECTION" => $arParams["USE_MAIN_ELEMENT_SECTION"],
					"OFFER_HIDE_NAME_PROPS" => $arParams["OFFER_HIDE_NAME_PROPS"],
					"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
					"HIDE_NOT_AVAILABLE_OFFERS" => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],
					"COMPATIBLE_MODE" => 'Y',

			),
	$arResult["THEME_COMPONENT"]);
?>
		</div>
		</div>
	</div>
<?}else{
	/* При пустом запросе ничего не пишем: об этом говорит сам заголовок
	   страницы — «Введите поисковый запрос» (см. catalog/main/search.php).
	   Раньше здесь было ещё и сообщение «Введите поисковый запрос и нажмите
	   кнопку "Искать"» — получалось дважды об одном (Ирина, 2026-08-13). */
	if($_GET["q"] != '')
		echo '<div class="nd-search-empty">'.GetMessage("CT_BCSE_NOT_FOUND").'</div>';
}
?>