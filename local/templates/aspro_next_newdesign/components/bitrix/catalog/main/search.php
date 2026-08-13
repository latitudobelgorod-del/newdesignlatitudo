<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/* Заголовок страницы поиска по макету (Ирина, 2026-08-12):
   «Результаты поиска: «запрос»» вместо прежнего «Поиск». Пустой запрос
   приглашает ввести его — так же, как нарисовано в макете; прежнее «Поиск»
   ничего не подсказывало, а под заголовком стояло ещё и сообщение компонента
   «Введите поисковый запрос и нажмите кнопку "Искать"» (Ирина, 2026-08-13).

   H1 отдаём в отложенную область `nd_page_head`: по макету он идёт над
   колонками, а этот файл рисуется уже внутри контента. Заглушку под область
   печатает page_blocks/page_title_newdesign.php. Класс nd-search-head рядом с
   nd-cat-head отменяет черту-разделитель под заголовком: на поиске под ним
   идёт строка поиска, а черта — уже под ней. */
$ndSearchQuery = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : '';
$ndSearchTitle = ($ndSearchQuery !== '')
	? 'Результаты поиска: «'.htmlspecialcharsbx($ndSearchQuery).'»'
	: 'Введите поисковый запрос';

/* В <title> оставляем короткое «Поиск» из языкового файла: приглашение
   уместно на странице, а во вкладке браузера и выдаче — нет. */
$APPLICATION->SetTitle(($ndSearchQuery !== '') ? $ndSearchTitle : GetMessage("CMP_TITLE"));

/* Стили и скрипт каталога. Их печатает шаблон списка товаров, но при пустом
   запросе списка нет — и строка поиска оставалась без оформления: поле серой
   полосой, кнопка отдельным квадратом под ним. Подключаем здесь, под тем же
   признаком ND_CATALOG_ASSETS, что и в списке, чтобы на странице с
   результатами теги не задвоились. Скрипт берём заодно: признак общий, и без
   него на выдаче перестали бы работать фильтр и «Показать ещё». */
$ndSearchAssets = '';
if (!defined('ND_CATALOG_ASSETS')) {
	define('ND_CATALOG_ASSETS', true);
	$ndSearchCss = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/css/newdesign-catalog.css';
	$ndSearchJs  = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/js/newdesign-catalog.js';
	$ndSearchAssets =
		'<link href="'.SITE_TEMPLATE_PATH.'/css/newdesign-catalog.css?'.(file_exists($ndSearchCss) ? filemtime($ndSearchCss) : '').'" rel="stylesheet" />'
		.'<script src="'.SITE_TEMPLATE_PATH.'/js/newdesign-catalog.js?'.(file_exists($ndSearchJs) ? filemtime($ndSearchJs) : '').'"></script>';
}

/* Шапка поиска: H1, строка поиска и черта-разделитель. Всё это по макету
   идёт во всю ширину, над обеими колонками, — поэтому собираем здесь и
   отдаём в `nd_page_head`, а не печатаем в шаблоне компонента (он рисуется
   внутри правой колонки, слева умный фильтр).

   Строку поиска раньше печатал шаблон search.page — там она оказалась бы
   зажата колонкой. Своя разметка, никаких отложенных функций внутри: строку
   собираем конкатенацией, чтобы не заводить ob_start (ShowTitle и подобные
   в буфере рвут его — грабля из шаблона услуг). */
$ndSearchIcon = SITE_TEMPLATE_PATH.'/images/newdesign/header/search.svg';
$APPLICATION->AddViewContent('nd_page_head',
	$ndSearchAssets
	.'<div class="nd-cat-head nd-search-head"><h1 id="pagetitle">'.$ndSearchTitle.'</h1></div>'
	.'<div class="nd-searchpage">'
		.'<form class="nd-searchpage__form" action="'.htmlspecialcharsbx($APPLICATION->GetCurPage(false)).'" method="get">'
			.'<input class="nd-searchpage__input" type="text" name="q" value="'.htmlspecialcharsbx($ndSearchQuery).'"'
				.' placeholder="Найти товар" maxlength="50" autocomplete="off" />'
			.'<button class="nd-searchpage__submit" type="submit" aria-label="Искать">'
				.'<img src="'.$ndSearchIcon.'" alt="" width="24" height="24">'
			.'</button>'
		.'</form>'
	.'</div>'
	.'<div class="nd-searchpage__sep"></div>'
);

/* Крошки по макету — «Главная / Поиск». Страница поиска лежит по адресу
   /catalog/?q=…, поэтому штатная цепочка даёт «Главная — Каталог ДПК»
   (название из $sSectionName в /catalog/.section.php). Подменяем хвост через
   ND_CRUMBS_REPLACE — его разбирает шаблон крошек breadcrumb/newdesign,
   он же chain_template, и выполняется он в самом конце страницы. */
$GLOBALS['ND_CRUMBS_REPLACE'] = array(
	array('TITLE' => 'Поиск', 'LINK' => ''),
);

/* Левая колонка с фильтром (Ирина, 2026-08-12). Её печатает footer.php по
   свойству страницы HIDE_LEFT_BLOCK, а /catalog/index.php ставит ему «Y» для
   всех адресов короче двух слэшей — под это правило попадает и /catalog/?q=.
   Возвращаем «N» здесь: footer.php читает свойство уже после нас, а обёртку
   .right_block header.php к этому моменту вывел (там значение приходит из
   /catalog/.section.php, и оно как раз «N»). Сам фильтр собирает шаблон
   catalog.search/main — только он знает, что нашлось по запросу. */
$APPLICATION->SetPageProperty('HIDE_LEFT_BLOCK', 'N');
?>


<?$APPLICATION->IncludeComponent(
	"bitrix:catalog.search",
	"main",
	Array(
			
			"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
			"IBLOCK_ID" => $arParams["IBLOCK_ID"],
			// Умный фильтр на поиске рисует шаблон catalog.search/main —
			// признак «фильтр включён» берём тот же, что и раздел каталога.
			"USE_FILTER" => $arParams["USE_FILTER"],
			"SORT_BUTTONS" => $arParams["SORT_BUTTONS"],
			"SORT_PRICES" => $arParams["SORT_PRICES"],
			"ELEMENT_SORT_FIELD" => $arParams["ELEMENT_SORT_FIELD"],
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
			'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '172800',
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
			"SET_STATUS_404" => "N",
			"CACHE_FILTER" => "Y",
			"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
			"RESTART" => ($arParams["RESTART"] == "Y" ? "Y" : "N"),
			"NO_WORD_LOGIC" => ($arParams["NO_WORD_LOGIC"] != "N" ? "Y" : "N"),
			"USE_LANGUAGE_GUESS" => ($arParams["USE_LANGUAGE_GUESS"] != "N" ? "Y" : "N"),
			"CHECK_DATES" => "Y",
			"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
			"CURRENCY_ID" => $arParams["CURRENCY_ID"],
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
?>