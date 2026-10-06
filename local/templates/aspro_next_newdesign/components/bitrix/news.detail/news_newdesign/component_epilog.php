<?
 global $APPLICATION;

if(is_array($arResult["SECTION"]))

  foreach($arResult["SECTION"]["PATH"] as $arPath)

  $APPLICATION->AddChainItem($arPath["NAME"], $arPath["SECTION_PAGE_URL"]);


$APPLICATION->AddChainItem($arResult["NAME"], "");

/* Скрипты карточек «Товаров по акции». Список товаров (catalog.section +
   maxyss:measure_unit) вызывается внутри template.php, а страница акции
   кешируется. Когда она отдаётся из кеша, вложенные компоненты не
   выполняются и свои script.js не подключают — карточки оставались без
   длин, единиц и счётчика (JCCatalogSection / MeasureUnitSwitcher is not
   defined). Эпилог выполняется всегда, поэтому подключаем отсюда.
   Повторного подключения нет: Asset сам отбрасывает дубли.
   Туда же — сворачивание описания («Показать все», js/newdesign-sale.js) и
   кнопка «Показать ещё» под товарами (пейджер only-pokaz-ewe): из кеша они
   пропадали по той же причине. */
$ndSaleAsset = \Bitrix\Main\Page\Asset::getInstance();
$ndSaleAsset->addJs(SITE_TEMPLATE_PATH.'/js/newdesign-sale.js');
$ndSaleAsset->addJs(SITE_TEMPLATE_PATH.'/components/bitrix/system.pagenavigation/only-pokaz-ewe/script.js');
$ndSaleAsset->addJs(SITE_TEMPLATE_PATH.'/components/bitrix/catalog.section/catalog_blockcolors_newdesign/script.js');
$ndSaleAsset->addCss('/bitrix/components/maxyss/measure_unit/templates/aspro_list_tp/style.css');
$ndSaleAsset->addJs('/bitrix/components/maxyss/measure_unit/templates/aspro_list_tp/script.js');

?>


