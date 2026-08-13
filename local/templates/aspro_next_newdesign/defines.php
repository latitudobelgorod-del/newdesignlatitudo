<?
global $is404, $isIndex, $isForm, $isWidePage, $isBlog, $isInfo, $isHideLeftBlock, $isShowTopAdvBottomBanner, $isShowFloatBanner, $isShowTizers, $isShowSale,
$isShowBottomBanner, $isShowCompany, $isShowBrands, $isShowCatalogSections, $isShowCatalogElements, $isShowIndexLeftBlock, $isShowMiddleAdvBottomBanner, $isShowBlog,
$bInstagrammIndex, $bVKIndex, $bShowHeaderSimple, $isShowReviews;
$is404 = (defined("ERROR_404") && ERROR_404 === "Y");
$isIndex = CNext::IsMainPage();
$isForm = CNext::IsFormPage();
$isBlog = (CSite::inDir(SITE_DIR.'materials/') || $APPLICATION->GetProperty("BLOG_PAGE") == "Y");
$isProject = (CSite::inDir(SITE_DIR.'projects/'));
$isInfo = (CSite::inDir(SITE_DIR.'info/'));
$isCatalogPage = (CSite::inDir(SITE_DIR.'catalog/'));
$isWidePage = ($APPLICATION->GetProperty("WIDE_PAGE") == "Y");
// Новый дизайн, страница отзывов: левого блока «Уточните наличие» здесь нет —
// его место занимает колонка с общим рейтингом из шаблона list_reviews_newdesign.
// Свойство ставим до его чтения ниже; сама страница /company/reviews/index.php
// лежит вне git и общая со старым дизайном, поэтому правим только здесь.
if (CSite::inDir(SITE_DIR.'company/reviews/')) {
	$APPLICATION->SetPageProperty("HIDE_LEFT_BLOCK", "Y");
}
// Новый дизайн: у страниц раздела /info/ появилась галочка «Скрыть левое меню
// (страница во всю ширину)» — свойство HIDE_LEFT_BLOCK инфоблока «Информация».
//
// Страницы раздела — это элементы инфоблока с ЧПУ, физического файла под них
// нет, поэтому выставить свойство страницы прямо в файле нельзя. Компонент
// news.detail тоже не подходит: он отрабатывает уже после header.php, где
// решается, рисовать левую колонку или нет.
//
// Поэтому коды «широких» страниц читаем здесь одним запросом и держим в кэше
// с привязкой к тегу инфоблока — после снятия или установки галочки в админке
// кэш сбросится сам.
if ($isInfo && $APPLICATION->GetProperty("HIDE_LEFT_BLOCK") != "Y") {
	$ndInfoPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$arNdInfoSeg = array_values(array_filter(explode('/', (string)substr($ndInfoPath, strlen(SITE_DIR)))));
	// Интересует только детальная страница раздела вида /info/<код>/.
	$ndInfoCode = (count($arNdInfoSeg) == 2 && $arNdInfoSeg[0] == 'info' ? $arNdInfoSeg[1] : '');

	if (strlen($ndInfoCode)) {
		$arNdWideInfo = array();
		$obNdCache = \Bitrix\Main\Data\Cache::createInstance();

		if ($obNdCache->initCache(28800, 'nd_wide_info_pages', '/nd/wide_info')) {
			$arNdWideInfo = $obNdCache->getVars();
		}
		elseif ($obNdCache->startDataCache()) {
			if (CModule::IncludeModule('iblock')) {
				$arNdInfoIblock = CIBlock::GetList(array(), array('CODE' => 'aspro_next_info', 'TYPE' => 'aspro_next_content'))->Fetch();
				if ($arNdInfoIblock) {
					// Тег регистрируем до выборки: иначе, когда галочки не стоит
					// ни у одной страницы, кэш остался бы без привязки к инфоблоку
					// и первая же поставленная галочка не сработала бы до сброса.
					$obNdTagged = \Bitrix\Main\Application::getInstance()->getTaggedCache();
					$obNdTagged->startTagCache('/nd/wide_info');
					$obNdTagged->registerTag('iblock_id_'.$arNdInfoIblock['ID']);
					$obNdTagged->endTagCache();

					// «Свойство не пустое» вместо сравнения со значением: у флажка
					// один вариант, и заполненность и есть поставленная галочка.
					$rsNdInfo = CIBlockElement::GetList(
						array(),
						array('IBLOCK_ID' => $arNdInfoIblock['ID'], 'ACTIVE' => 'Y', '!PROPERTY_HIDE_LEFT_BLOCK' => false),
						false,
						false,
						array('ID', 'IBLOCK_ID', 'CODE')
					);
					while ($arNdItem = $rsNdInfo->Fetch()) {
						$arNdWideInfo[] = $arNdItem['CODE'];
					}
				}
			}
			$obNdCache->endDataCache($arNdWideInfo);
		}

		if (is_array($arNdWideInfo) && in_array($ndInfoCode, $arNdWideInfo)) {
			$APPLICATION->SetPageProperty("HIDE_LEFT_BLOCK", "Y");
		}
	}
}
$isHideLeftBlock = ($APPLICATION->GetProperty("HIDE_LEFT_BLOCK") == "Y");
$indexType = $arTheme["INDEX_TYPE"]["VALUE"];
$isShowIndexLeftBlock = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["WITH_LEFT_BLOCK"]["VALUE"] == "Y");
$isShowCatalogSections = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["CATALOG_SECTIONS"]["VALUE"] != "N");


$indexType = $arTheme["INDEX_TYPE"]["VALUE"];
$isShowIndexLeftBlock = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["WITH_LEFT_BLOCK"]["VALUE"] == "Y");
$isShowTopAdvBottomBanner = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["TOP_ADV_BOTTOM_BANNER"]["VALUE"] != "N");
$isShowMiddleAdvBottomBanner = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["MIDDLE_ADV"]["VALUE"] != "N");
$isShowFloatBanner = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["FLOAT_BANNER"]["VALUE"] != "N");
$isShowTizers = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["TIZERS"]["VALUE"] != "N");
$isShowSale = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["SALE"]["VALUE"] != "N");
$isShowBottomBanner = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["BOTTOM_BANNERS"]["VALUE"] != "N");
$isShowCompany = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["COMPANY_TEXT"]["VALUE"] != "N");
$isShowBrands = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["BRANDS"]["VALUE"] != "N");
$isShowCatalogSections = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["CATALOG_SECTIONS"]["VALUE"] != "N");
$isShowCatalogElements = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["CATALOG_TAB"]["VALUE"] != "N");
$isShowBlog = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["BLOG"]["VALUE"] != "N");
$isShowReviews = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["REVIEWS"]["VALUE"] != "N");
$bInstagrammIndex = (isset($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["INSTAGRAMM"]["VALUE"]) ? $arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["INSTAGRAMM"]["VALUE"] != "N" : true);
$bVKIndex = (isset($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["VK"]["VALUE"]) ? $arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["VK"]["VALUE"] !== "N" : true);
$isShowMap = ($arTheme["INDEX_TYPE"]["SUB_PARAMS"][$indexType]["MAP"]["VALUE"] != "N");


$bShowHeaderSimple = $bShowFooterSimple = ( CSite::InDir($arTheme['BASKET_PAGE_URL']['VALUE']) || CSite::InDir($arTheme['ORDER_PAGE_URL']['VALUE']) ) && $arTheme['SIMPLE_BASKET']['VALUE'] == 'Y';

global $arRegion;
if($isIndex)
{
	$GLOBALS['arRegionLinkFront'] = array('PROPERTY_SHOW_ON_INDEX_PAGE_VALUE' => 'Y');
}

if($arRegion && $arTheme['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] == 'Y')
{
	$GLOBALS['arRegionLink'] = array('PROPERTY_LINK_REGION' => $arRegion['ID']);
	if($isIndex)
	{
		$GLOBALS['arRegionLinkFront']['PROPERTY_LINK_REGION'] = $arRegion['ID'];
	}
}
?>