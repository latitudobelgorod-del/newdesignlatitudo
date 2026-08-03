<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?// H1 по макету стоит над обеими колонками, во всю ширину — а этот шаблон
   // рисуется уже внутри правой (слева фильтр). Заголовок вместе с рядом
   // подразделов уходит в отложенную область `nd_page_head` в самом конце
   // page_blocks/list_elements_1.php; заглушку под неё выводит
   // page_blocks/page_title_newdesign.php.?>

<?
use Bitrix\Main\Loader,
	Bitrix\Main\ModuleManager;

Loader::includeModule("iblock");

global $arTheme, $NextSectionID, $arRegion, $APPLICATION, $LinkCatalog;
$regionID = ($arRegion ? $arRegion['ID'] : '');

$arPageParams = $arSectionFilter = $arSection = $section = array();
$_SESSION['SMART_FILTER_VAR'] = $arParams['FILTER_NAME'];




// get current section ID
if($arResult["VARIABLES"]["SECTION_ID"] > 0){
	$arSectionFilter = array('GLOBAL_ACTIVE' => 'Y', "ID" => $arResult["VARIABLES"]["SECTION_ID"], "IBLOCK_ID" => $arParams["IBLOCK_ID"]);
	$section=CNextCache::CIBlockSection_GetList(array('CACHE' => array("MULTI" =>"N", "TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))),
	array('GLOBAL_ACTIVE' => 'Y', "ID" => $arResult["VARIABLES"]["SECTION_ID"], "IBLOCK_ID" => $arParams["IBLOCK_ID"]), false, array("ID", "PICTURE", "IBLOCK_ID", "NAME", "DESCRIPTION", "SECTION_PAGE_URL", "UF_SECTION_DESCR", "UF_PLACE_SECT",
	"UF_EDITOR1", "UF_EDITOR2", "UF_EDITOR3", "UF_EDITOR1_BEL", "UF_EDITOR2_BEL", "UF_EDITOR3_BEL", "UF_EDITOR1_SPB", "UF_EDITOR2_SPB", "UF_EDITOR3_SPB", "UF_EDITOR1_VRN", "UF_EDITOR2_VRN", "UF_EDITOR3_VRN", "UF_EDITOR1_DEFAULT", 
	"UF_EDITOR3_DEFAULT", "UF_EDITOR_DEFAULT", "UF_EDITOR1_KRD", "UF_EDITOR2_KRD",  "UF_EDITOR3_KRD", "IPROPERTY_VALUES",
	"UF_EDITOR1_ROSTOV", "UF_EDITOR2_ROSTOV", "UF_EDITOR3_ROSTOV", "UF_EDITOR1_KURSK", "UF_EDITOR2_KURSK", "UF_EDITOR3_KURSK", "UF_EDITOR1_LIPETSK", "UF_EDITOR2_LIPETSK", "UF_EDITOR3_LIPETSK", "UF_EDITOR1_TAMBOV", "UF_EDITOR2_TAMBOV", 
	"UF_EDITOR3_TAMBOV", "UF_EDITOR1_STAVR", "UF_EDITOR2_STAVR",  "UF_EDITOR3_STAVR", "SECTION_META_TITLE",
	"UF_SORT", "UF_LINKSECTION", "UF_ELEMENTS", "UF_SECTIONCONNECT", "UF_LINK_REGION", "UF_OFFERS_TYPE", "UF_COMMENT_PRICE",
	"UF_SHOW_BLOCK_METRIKA", "UF_EDITOR3_TOP_DEFAULT", "UF_EDITOR3_BOTTOM_DEFAULT", "UF_META_PODDOMEN_TITLE", "UF_META_PODDOMEN_DESCRIPTION","UF_YANDEX_HINT","UF_SECTION_IN_MENU","UF_MENULINK_TOP",  $arParams["SECTION_DISPLAY_PROPERTY"], 
	$arParams["SECTION_PLACE_PROPERTY"], $arParams["SECTION_PAGE_URL"],"IBLOCK_SECTION_ID", "DEPTH_LEVEL", "LEFT_MARGIN", "RIGHT_MARGIN"));


}
elseif(strlen(trim($arResult["VARIABLES"]["SECTION_CODE"])) > 0){
	$arSectionFilter = array('GLOBAL_ACTIVE' => 'Y', "=CODE" => $arResult["VARIABLES"]["SECTION_CODE"], "IBLOCK_ID" => $arParams["IBLOCK_ID"]);
	$section=CNextCache::CIBlockSection_GetList(array('CACHE' => array("MULTI" =>"N", "TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))),
	array('GLOBAL_ACTIVE' => 'Y', "=CODE" => $arResult["VARIABLES"]["SECTION_CODE"], "IBLOCK_ID" => $arParams["IBLOCK_ID"]), false, array("ID", "PICTURE", "IBLOCK_ID", "NAME", "DESCRIPTION", "UF_SECTION_DESCR", "UF_PLACE_SECT",
	"UF_EDITOR1", "UF_EDITOR2", "UF_EDITOR3", "UF_EDITOR1_BEL", "UF_EDITOR2_BEL", "UF_EDITOR3_BEL", "UF_EDITOR1_SPB", "UF_EDITOR2_SPB", "UF_EDITOR3_SPB", "UF_EDITOR1_VRN", "UF_EDITOR2_VRN", "UF_EDITOR3_VRN", "UF_EDITOR1_DEFAULT", 
	"UF_EDITOR3_DEFAULT", "UF_EDITOR_DEFAULT", "UF_EDITOR1_KRD", "UF_EDITOR2_KRD",  "UF_EDITOR3_KRD", "IPROPERTY_VALUES",
	"UF_EDITOR1_ROSTOV", "UF_EDITOR2_ROSTOV", "UF_EDITOR3_ROSTOV", "UF_EDITOR1_KURSK", "UF_EDITOR2_KURSK", "UF_EDITOR3_KURSK", "UF_EDITOR1_LIPETSK", "UF_EDITOR2_LIPETSK", "UF_EDITOR3_LIPETSK", "UF_EDITOR1_TAMBOV", "UF_EDITOR2_TAMBOV", 
	"UF_EDITOR3_TAMBOV", "UF_EDITOR1_STAVR", "UF_EDITOR2_STAVR",  "UF_EDITOR3_STAVR", "SECTION_META_TITLE",
	"UF_SORT", "UF_LINKSECTION", "UF_ELEMENTS",  "UF_SECTIONCONNECT", "UF_LINK_REGION", "UF_OFFERS_TYPE", "UF_COMMENT_PRICE",
	"UF_SHOW_BLOCK_METRIKA", "UF_EDITOR3_TOP_DEFAULT", "UF_EDITOR3_BOTTOM_DEFAULT", "UF_META_PODDOMEN_TITLE", "UF_META_PODDOMEN_DESCRIPTION","UF_YANDEX_HINT","UF_SECTION_IN_MENU","UF_MENULINK_TOP", $arParams["SECTION_DISPLAY_PROPERTY"], $arParams["SECTION_PLACE_PROPERTY"], $arParams["SECTION_PAGE_URL"], "IBLOCK_SECTION_ID", "DEPTH_LEVEL", "LEFT_MARGIN", "RIGHT_MARGIN"));


}

//if($arSectionFilter){
//	$section = CNextCache::CIBlockSection_GetList(array('CACHE' => array("MULTI" =>"N", "TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), CNext::makeSectionFilterInRegion($arSectionFilter), false, array("ID", "IBLOCK_ID", "NAME", "DESCRIPTION", "UF_SECTION_DESCR", "UF_OFFERS_TYPE", 'UF_FILTER_VIEW', 'UF_LINKED_BLOG', $arParams["SECTION_DISPLAY_PROPERTY"], "IBLOCK_SECTION_ID", "DEPTH_LEVEL", "LEFT_MARGIN", "RIGHT_MARGIN", "PICTURE", "DETAIL_PICTURE"));
//}

$sectionPicturePatch = $section["PICTURE"] ? CFile::GetPath($section["PICTURE"]) : CFile::GetPath($section["DETAIL_PICTURE"]);
if($sectionPicturePatch) {
	CNext::AddMeta(array('og:image' => $sectionPicturePatch));
}


$typeSKU = '';

$LinkCatalog = $section["ID"];


if($section){
	$arSection["ID"] = $section["ID"];
	$arSection["NAME"] = $section["NAME"];
	$arSection["SECTION_PAGE_URL"] = $section["SECTION_PAGE_URL"];
	$arSection["SECTION_META_TITLE"] = $section["SECTION_META_TITLE"];
	$arSection["IBLOCK_SECTION_ID"] = $section["IBLOCK_SECTION_ID"];
	$arSection["UF_COMMENT_PRICE"] = $section["UF_COMMENT_PRICE"];
	if($section[$arParams["SECTION_DISPLAY_PROPERTY"]]){
		$arDisplayRes = CUserFieldEnum::GetList(array(), array("ID" => $section[$arParams["SECTION_DISPLAY_PROPERTY"]]));
		if($arDisplay = $arDisplayRes->GetNext()){
			$arSection["DISPLAY"] = $arDisplay["XML_ID"];
		}
	}
	if($section[$arParams["SECTION_PLACE_PROPERTY"]]){
		$arPlaceRes = CUserFieldEnum::GetList(array(), array("ID" => $section[$arParams["SECTION_PLACE_PROPERTY"]]));
		if($arPlace = $arPlaceRes->GetNext()){
			$arSection["PLACE"] = $arPlace["XML_ID"];
		}
	}
	
$ipropValues = new \Bitrix\Iblock\InheritedProperty\SectionValues($arParams["IBLOCK_ID"],$arSection["ID"]); 
  $values_seo = $ipropValues->getValues();
//выводим наши мета-данные
//var_dump($values_seo['SECTION_META_DESCRIPTION']);





	
	if(strlen($section["DESCRIPTION"]))
		$arSection["DESCRIPTION"] = $section["DESCRIPTION"];
	if(strlen($section["PICTURE"]))
	$arSection["PICTURE"] = $section["PICTURE"];

	

	if(strlen($section["UF_SECTION_DESCR"]))
		$arSection["UF_SECTION_DESCR"] = $section["UF_SECTION_DESCR"];
	
	if(strlen($section["UF_COMMENT_PRICE"]))
	$arSection["UF_COMMENT_PRICE"] = $section["UF_COMMENT_PRICE"];
	
	if(strlen($section["UF_META_PODDOMEN_TITLE"]))
	$arSection["UF_META_PODDOMEN_TITLE"] = $section["UF_META_PODDOMEN_TITLE"];
	
	if(strlen($section["UF_META_PODDOMEN_DESCRIPTION"]))
	$arSection["UF_META_PODDOMEN_DESCRIPTION"] = $section["UF_META_PODDOMEN_DESCRIPTION"];


	if(strlen($section["UF_SHOW_BLOCK_METRIKA"]))
	$arSection["UF_SHOW_BLOCK_METRIKA"] = $section["UF_SHOW_BLOCK_METRIKA"];

	if(strlen($section["UF_PLACE_SECT"]))
	$arSection["UF_PLACE_SECT"] = $section["UF_PLACE_SECT"];

	if(strlen($section["UF_YANDEX_HINT"]))
	$arSection["UF_YANDEX_HINT"] = $section["UF_YANDEX_HINT"];

	if(strlen($section["UF_EDITOR1"]))
	$arSection["UF_EDITOR1"] = $section["UF_EDITOR1"];

if(strlen($section["UF_ELEMENTS"]))
$arSection["UF_ELEMENTS"] = $section["UF_ELEMENTS"];	
	
	$posSectionDescr = COption::GetOptionString("aspro.next", "SHOW_SECTION_DESCRIPTION", "BOTTOM", SITE_ID);
	
	global $arSubSectionFilter;
	$arSubSectionFilter = array(
		"SECTION_ID" => $arSection["ID"],
		"IBLOCK_ID" => $arParams['IBLOCK_ID'],
		"ACTIVE" => "Y",
		"GLOBAL_ACTIVE" => "Y",
	);
	
	$iSectionsCount = CNextCache::CIBlockSection_GetCount(array('CACHE' => array("TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), CNext::makeSectionFilterInRegion($arSubSectionFilter));

	$catalog_available = $arParams['HIDE_NOT_AVAILABLE'];
	if (!isset($arParams['HIDE_NOT_AVAILABLE']))
		$catalog_available = 'N';
	if ($arParams['HIDE_NOT_AVAILABLE'] != 'Y' && $arParams['HIDE_NOT_AVAILABLE'] != 'L')
		$catalog_available = 'N';
	if($arParams['HIDE_NOT_AVAILABLE'] == 'Y')
		$catalog_available = 'Y';
	$arElementFilter = array("SECTION_ID" => $arSection["ID"], "ACTIVE" => "Y", "INCLUDE_SUBSECTIONS" => $arParams["INCLUDE_SUBSECTIONS"], "IBLOCK_ID" => $arParams["IBLOCK_ID"]);
	if($arParams["INCLUDE_SUBSECTIONS"] == "A")
	{
		$arElementFilter["INCLUDE_SUBSECTIONS"] = "Y";
		$arElementFilter["SECTION_GLOBAL_ACTIVE"] = "Y";
		$arElementFilter["SECTION_ACTIVE "] = "Y";
	}
	if($arParams['HIDE_NOT_AVAILABLE'] == 'Y')
		$arElementFilter["CATALOG_AVAILABLE"] = $catalog_available;

	$itemsCnt = CNextCache::CIBlockElement_GetList(array("CACHE" => array("TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), CNext::makeElementFilterInRegion($arElementFilter, false, $bSetLinkRegionFilter = $arParams['FILTER_NAME'] === 'arRegionLink'), array());

	// set offer type & smartfilter view
	$typeTmpSKU = $viewTmpFilter = 0;
	if($section['UF_OFFERS_TYPE']){
		$typeTmpSKU = $section['UF_OFFERS_TYPE'];
	}
	if($section['UF_FILTER_VIEW']){
		$viewTmpFilter = $section['UF_FILTER_VIEW'];
	}
	if ($section['UF_LINKED_BLOG']) {
        $linkedArticles = $section['UF_LINKED_BLOG'];
    }
	if(!$typeTmpSKU || !$viewTmpFilter){
		if($section['DEPTH_LEVEL'] > 1){
			$sectionParent = CNextCache::CIBlockSection_GetList(array('CACHE' => array("MULTI" =>"N", "TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), array('GLOBAL_ACTIVE' => 'Y', "ID" => $section["IBLOCK_SECTION_ID"], "IBLOCK_ID" => $arParams["IBLOCK_ID"]), false, array("ID", "IBLOCK_ID", "NAME", "UF_OFFERS_TYPE", 'UF_FILTER_VIEW'));
			if($sectionParent['UF_OFFERS_TYPE'] && !$typeTmpSKU){
				$typeTmpSKU = $sectionParent['UF_OFFERS_TYPE'];
			}
			if($sectionParent['UF_FILTER_VIEW'] && !$viewTmpFilter){
				$viewTmpFilter = $sectionParent['UF_FILTER_VIEW'];
			}

			if($section['DEPTH_LEVEL'] > 2){
				if(!$typeTmpSKU || !$viewTmpFilter){
					$sectionRoot = CNextCache::CIBlockSection_GetList(array('CACHE' => array("MULTI" =>"N", "TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), array('GLOBAL_ACTIVE' => 'Y', "<=LEFT_BORDER" => $section["LEFT_MARGIN"], ">=RIGHT_BORDER" => $section["RIGHT_MARGIN"], "DEPTH_LEVEL" => 1, "IBLOCK_ID" => $arParams["IBLOCK_ID"]), false, array("ID", "IBLOCK_ID", "NAME", "UF_OFFERS_TYPE", 'UF_FILTER_VIEW'));
					if($sectionRoot['UF_OFFERS_TYPE'] && !$typeTmpSKU){
						$typeTmpSKU = $sectionRoot['UF_OFFERS_TYPE'];
					}
					if($sectionRoot['UF_FILTER_VIEW'] && !$viewTmpFilter){
						$viewTmpFilter = $sectionRoot['UF_FILTER_VIEW'];
					}
				}
			}
		}
	}
	if($typeTmpSKU){
		$rsTypes = CUserFieldEnum::GetList(array(), array("ID" => $typeTmpSKU));
		if($arType = $rsTypes->Fetch()){
			$typeSKU = $arType['XML_ID'];
			$arTheme['TYPE_SKU']['VALUE'] = $typeSKU;
		}
	}
	if($viewTmpFilter){
		$rsViews = CUserFieldEnum::GetList(array(), array('ID' => $viewTmpFilter));
		if($arView = $rsViews->Fetch()){
			$viewFilter = $arView['XML_ID'];
			$arTheme['FILTER_VIEW']['VALUE'] = strtoupper($viewFilter);
		}
	}
}
else{
	\Bitrix\Iblock\Component\Tools::process404(
		""
		,($arParams["SET_STATUS_404"] === "Y")
		,($arParams["SET_STATUS_404"] === "Y")
		,($arParams["SHOW_404"] === "Y")
		,$arParams["FILE_404"]
	);
}


  
if($arRegion)
{
	if($arRegion['LIST_PRICES'])
	{
		if(reset($arRegion['LIST_PRICES']) != 'component')
			$arParams['PRICE_CODE'] = array_keys($arRegion['LIST_PRICES']);
	}
	if($arRegion['LIST_STORES'])
	{
		if(reset($arRegion['LIST_STORES']) != 'component')
			$arParams['STORES'] = $arRegion['LIST_STORES'];
	}
}

if($arParams['LIST_PRICES'])
{
	foreach($arParams['LIST_PRICES'] as $key => $price)
	{
		if(!$price)
			unset($arParams['LIST_PRICES'][$key]);
	}
}

if($arParams['STORES'])
{
	foreach($arParams['STORES'] as $key => $store)
	{
		if(!$store)
			unset($arParams['STORES'][$key]);
	}
}

$NextSectionID = $arSection["ID"];?>


<?
//seo
$catalogInfoIblockId = CNextCache::$arIBlocks[SITE_ID]["aspro_next_catalog"]["aspro_next_catalog_info"][0];
if($catalogInfoIblockId){

	/*fix*/
	$current_url =  $APPLICATION->GetCurDir();
	$real_url = $current_url;
	$current_url =  str_replace(array('%25', '&quot;', '&#039;'), array('%', '"', "'"), $current_url); // for utf-8 fix some problem
	$encode_current_url = urlencode($current_url);
	$gaps_encode_current_url = str_replace(' ', '%20', $current_url);
	$encode_current_url_slash = str_replace(array('%2F', '+'), array('/', '%20'), $encode_current_url);
	$urldecodedCP = iconv("windows-1251", "utf-8//IGNORE", $current_url);
	$urldecodedCP_slash = str_replace(array('%2F'), array('/'), rawurlencode($urldecodedCP));
	$replacements = array('"' ,'%27', '%20', '%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%3F', '%23', '%5B', '%5D');// for fix some problem  with spec chars in prop
	$entities = array("&quot;", '&#039;', ' ', '!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "?", "#", "[", "]");
	$replacedSpecChar = str_replace($entities, $replacements, $current_url);
	/**/

	$arSeoItems = CNextCache::CIBLockElement_GetList(array('SORT' => 'ASC', 'CACHE' => array("MULTI" => "Y", "TAG" => CNextCache::GetIBlockCacheTag($catalogInfoIblockId))), array("IBLOCK_ID" => $catalogInfoIblockId, "ACTIVE" => "Y", "PROPERTY_FILTER_URL" => array($real_url, $current_url, $gaps_encode_current_url, $urldecodedCP_slash, $encode_current_url_slash, $replacedSpecChar)), false, false, array("ID", "IBLOCK_ID", "PROPERTY_FILTER_URL", "PROPERTY_LINK_REGION"));
	$arSeoItem = $arTmpRegionsLanding = array();
	
	if($arSeoItems)
	{
		$iLandingItemID = 0;
		//$current_url =  $APPLICATION->GetCurDir();
		//$url = urldecode(str_replace(' ', '+', $current_url));
		foreach($arSeoItems as $arItem)
		{
			if(!is_array($arItem['PROPERTY_LINK_REGION_VALUE']))
				$arItem['PROPERTY_LINK_REGION_VALUE'] = (array)$arItem['PROPERTY_LINK_REGION_VALUE'];

			if(!$arSeoItem)
			{
				//$urldecoded = urldecode($arItem["PROPERTY_FILTER_URL_VALUE"]);
				//if($urldecoded == $url || $urldecoded == $current_url)
				//{
					if($arItem['PROPERTY_LINK_REGION_VALUE'])
					{
						if($arRegion && in_array($arRegion['ID'], $arItem['PROPERTY_LINK_REGION_VALUE']))
							$arSeoItem = $arItem;
					}
					else
					{
						$arSeoItem = $arItem;
					}

					if($arSeoItem)
					{
						$iLandingItemID = $arSeoItem['ID'];
						$arSeoItem = CNextCache::CIBLockElement_GetList(array('SORT' => 'ASC', 'CACHE' => array("MULTI" => "N", "TAG" => CNextCache::GetIBlockCacheTag($catalogInfoIblockId))), array("IBLOCK_ID" => $catalogInfoIblockId, "ID" => $iLandingItemID), false, false, array("ID", "IBLOCK_ID", "NAME", "PREVIEW_TEXT", "DETAIL_PICTURE", "PROPERTY_FILTER_URL", "PROPERTY_LINK_REGION", "PROPERTY_FORM_QUESTION", "PROPERTY_SECTION_SERVICES", "PROPERTY_TIZERS", "PROPERTY_SECTION", "DETAIL_TEXT", "PROPERTY_I_ELEMENT_PAGE_TITLE", "PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_ALT", "PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_TITLE", "PROPERTY_I_SKU_PAGE_TITLE", "PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_ALT", "PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_TITLE", "ElementValues"));

						$arIBInheritTemplates = array(
							"ELEMENT_PAGE_TITLE" => $arSeoItem["PROPERTY_I_ELEMENT_PAGE_TITLE_VALUE"],
							"ELEMENT_PREVIEW_PICTURE_FILE_ALT" => $arSeoItem["PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_ALT_VALUE"],
							"ELEMENT_PREVIEW_PICTURE_FILE_TITLE" => $arSeoItem["PROPERTY_I_ELEMENT_PREVIEW_PICTURE_FILE_TITLE_VALUE"],
							"SKU_PAGE_TITLE" => $arSeoItem["PROPERTY_I_SKU_PAGE_TITLE_VALUE"],
							"SKU_PREVIEW_PICTURE_FILE_ALT" => $arSeoItem["PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_ALT_VALUE"],
							"SKU_PREVIEW_PICTURE_FILE_TITLE" => $arSeoItem["PROPERTY_I_SKU_PREVIEW_PICTURE_FILE_TITLE_VALUE"],
						);
					}
				//}
			}

			if($arItem['PROPERTY_LINK_REGION_VALUE'])
			{
				if(!$arRegion || !in_array($arRegion['ID'], $arItem['PROPERTY_LINK_REGION_VALUE']))
					$arTmpRegionsLanding[] = $arItem['ID'];
			}
		}
	}
}

if(!$arParams["FILTER_NAME"]){
	$arParams["FILTER_NAME"] = 'arrProductsFilter';
}

if($arRegion)
{
	if($arRegion["LIST_STORES"] && $arParams["HIDE_NOT_AVAILABLE"] == "Y")
	{
		if($arParams['STORES']){
			if(CNext::checkVersionModule('18.6.200', 'iblock')){
				$arStoresFilter = array(
					'STORE_NUMBER' => $arParams['STORES'],
					'>STORE_AMOUNT' => 0,
				);
			}
			else{
				if(count($arParams['STORES']) > 1){
					$arStoresFilter = array('LOGIC' => 'OR');
					foreach($arParams['STORES'] as $storeID)
					{
						$arStoresFilter[] = array(">CATALOG_STORE_AMOUNT_".$storeID => 0);
					}
				}
				else{
					foreach($arParams['STORES'] as $storeID)
					{
						$arStoresFilter = array(">CATALOG_STORE_AMOUNT_".$storeID => 0);
					}
				}
			}

			$arTmpFilter = array('!TYPE' => array('2', '3'));
			if($arStoresFilter){
				if(!CNext::checkVersionModule('18.6.200', 'iblock') && count($arStoresFilter) > 1){
					$arTmpFilter[] = $arStoresFilter;
				}
				else{
					$arTmpFilter = array_merge($arTmpFilter, $arStoresFilter);
				}

				$GLOBALS[$arParams["FILTER_NAME"]][] = array(
					'LOGIC' => 'OR',
					array('TYPE' => array('2','3')),
					$arTmpFilter,
				);
			}
		}
	}
	$arParams["USE_REGION"] = "Y";

	$GLOBALS[$arParams['FILTER_NAME']]['IBLOCK_ID'] = $arParams['IBLOCK_ID'];
	CNext::makeElementFilterInRegion($GLOBALS[$arParams['FILTER_NAME']]);
}

/* hide compare link from module options */
if(CNext::GetFrontParametrValue('CATALOG_COMPARE') == 'N')
	$arParams["USE_COMPARE"] = 'N';
/**/

if(CNext::GetFrontParametrValue('SHOW_DELAY_BUTTON') == 'N')
	$arParams["DISPLAY_WISH_BUTTONS"] = 'N';
	
?>
<?if(!in_array("DETAIL_PAGE_URL", (array)$arParams["LIST_OFFERS_FIELD_CODE"]))
	$arParams["LIST_OFFERS_FIELD_CODE"][] = "DETAIL_PAGE_URL";?>

<?$arTransferParams = array(
	"SHOW_ABSENT" => $arParams["SHOW_ABSENT"],
	"HIDE_NOT_AVAILABLE_OFFERS" => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],
	"PRICE_CODE" => $arParams["PRICE_CODE"],
	"OFFER_TREE_PROPS" => $arParams["OFFER_TREE_PROPS"],
	"OFFER_SHOW_PREVIEW_PICTURE_PROPS" => $arParams["OFFER_SHOW_PREVIEW_PICTURE_PROPS"],
	"CACHE_TIME" => $arParams["CACHE_TIME"],
	"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
	"CURRENCY_ID" => $arParams["CURRENCY_ID"],
	"OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
	"OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
	"OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
	"OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
	"LIST_OFFERS_LIMIT" => $arParams["LIST_OFFERS_LIMIT"],
	"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
	"LIST_OFFERS_PROPERTY_CODE" => $arParams["LIST_OFFERS_PROPERTY_CODE"],
	"SHOW_DISCOUNT_TIME" => $arParams["SHOW_DISCOUNT_TIME"],
	"SHOW_COUNTER_LIST" => $arParams["SHOW_COUNTER_LIST"],
	"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
	"USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
	"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
	"SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
	"SHOW_DISCOUNT_PERCENT" => $arParams["SHOW_DISCOUNT_PERCENT"],
	"SHOW_DISCOUNT_PERCENT_NUMBER" => $arParams["SHOW_DISCOUNT_PERCENT_NUMBER"],
	"USE_REGION" => $arParams["USE_REGION"],
	"STORES" => $arParams["STORES"],
	"DEFAULT_COUNT" => $arParams["DEFAULT_COUNT"],
	"BASKET_URL" => $arParams["BASKET_URL"],
	"OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
	"PRODUCT_PROPERTIES" => $arParams["PRODUCT_PROPERTIES"],
	"PARTIAL_PRODUCT_PROPERTIES" => $arParams["PARTIAL_PRODUCT_PROPERTIES"],
	"ADD_PROPERTIES_TO_BASKET" => $arParams["ADD_PROPERTIES_TO_BASKET"],
	"SHOW_DISCOUNT_TIME_EACH_SKU" => $arParams["SHOW_DISCOUNT_TIME_EACH_SKU"],
	"SHOW_ARTICLE_SKU" => $arParams["SHOW_ARTICLE_SKU"],
	"OFFER_ADD_PICT_PROP" => $arParams["OFFER_ADD_PICT_PROP"],
	"PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
	"MAIN_IBLOCK_ID" => $arParams["IBLOCK_ID"],
	"IBINHERIT_TEMPLATES" => $arSeoItem ? $arIBInheritTemplates : array(),
	"ADD_PICT_PROP" => $arParams["ADD_PICT_PROP"],
	"OFFER_ADD_PICT_PROP" => $arParams["OFFER_ADD_PICT_PROP"],
	"GALLERY_ITEM_SHOW" => $arTheme["GALLERY_ITEM_SHOW"]["VALUE"],
	"MAX_GALLERY_ITEMS" => $arTheme["GALLERY_ITEM_SHOW"]["DEPENDENT_PARAMS"]["MAX_GALLERY_ITEMS"]["VALUE"],
	"ADD_DETAIL_TO_GALLERY_IN_LIST" => $arTheme["GALLERY_ITEM_SHOW"]["DEPENDENT_PARAMS"]["ADD_DETAIL_TO_GALLERY_IN_LIST"]["VALUE"],
);?>










<?//Вставка блока сео?>
<?
//seo
$arSeoItems = CNextCache::CIBLockElement_GetList(array('CACHE' => array("MULTI" =>"Y", "TAG" => CNextCache::GetIBlockCacheTag(CNextCache::$arIBlocks[SITE_ID]["aspro_next_catalog"]["aspro_next_catalog_info"][0]))), array("IBLOCK_ID" => CNextCache::$arIBlocks[SITE_ID]["aspro_next_catalog"]["aspro_next_catalog_info"][0], "ACTIVE"=>"Y"), false, false, 
array("ID", "IBLOCK_ID", "NAME", "PREVIEW_TEXT", "DETAIL_PICTURE", "PROPERTY_FILTER_URL", "PROPERTY_FORM_QUESTION", "PROPERTY_TIZERS", "PROPERTY_SECTION", "PROPERTY_NOINDEX","DETAIL_TEXT", "PROPERTY_EDITOR1_BEL", "PROPERTY_EDITOR2_BEL", "PROPERTY_EDITOR1_VRN", "PROPERTY_EDITOR2_VRN", "PROPERTY_EDITOR1_MSK", "PROPERTY_EDITOR2_MSK", "PROPERTY_EDITOR1_KRD", "PROPERTY_EDITOR2_KRD", "PROPERTY_EDITOR1_ROSTOV", "PROPERTY_EDITOR2_ROSTOV", "PROPERTY_EDITOR1_KURSK", "PROPERTY_EDITOR2_KURSK","PROPERTY_EDITOR1_LIPETSK", "PROPERTY_EDITOR2_LIPETSK", "PROPERTY_EDITOR1_TAMBOV", "PROPERTY_EDITOR2_TAMBOV",  "PROPERTY_EDITOR1_STAVR", 
"PROPERTY_EDITOR2_STAVR", "PROPERTY_EDITOR1", "PROPERTY_EDITOR2", "PROPERTY_SHOW_BLOCK_METRIKA", "ElementValues"));
$arSeoItem = array();
if($arSeoItems)
{
	$current_url =  $APPLICATION->GetCurDir();
	$url = urldecode(str_replace(' ', '+', $current_url));
	foreach($arSeoItems as $arItem)
	{
		if(urldecode($arItem["PROPERTY_FILTER_URL_VALUE"]) == $url)
		{
			$arSeoItem = $arItem;
			break;
		}
	}
}
//echo printPre($section);
//echo printPre($arSection);
?>
<?//Вставка блока сео?>

<?// section elements?>

<div class="js_wrapper_items <?=($arTheme["LAZYLOAD_BLOCK_CATALOG"]["VALUE"] == "Y" ? ' with-load-block' : '')?> <?=($arParams["AJAX_MODE"] == "Y" ? ' ajax_mode_on' : '')?> <?=$arTheme['MOBILE_FILTER_COMPACT']['VALUE'] == 'Y' ? "has_mobile_filter_compact" : ""; ?>" data-params='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arTransferParams, false))?>'>
	<?@include_once('page_blocks/'.$arParams["SECTION_ELEMENTS_TYPE_VIEW"].'.php');?>
</div>
<?// section elements?>

<?// navigation chain?>
<?CNext::checkBreadcrumbsChain($arParams, $arSection);?>
<?$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.history.js');?>
<?// navigation chain?>

<?// не использовалось на старом сайте?>
<?if(\Bitrix\Main\Loader::includeModule("sotbit.seometa")):?>
	<?
	// unset, because the sotbit:seo.meta component may have already been included
	unset($APPLICATION->__view['sotbit_seometa_h1']);
	unset($APPLICATION->__view['sotbit_seometa_top_desc']);
	unset($APPLICATION->__view['sotbit_seometa_bottom_desc']);
	unset($APPLICATION->__view['sotbit_seometa_add_desc']);
	unset($APPLICATION->__view['sotbit_seometa_file']);
	?>
	<?$APPLICATION->IncludeComponent(
		"sotbit:seo.meta",
		".default",
		array(
			"FILTER_NAME" => $arParams["FILTER_NAME"],
			"SECTION_ID" => $arSection['ID'],
			"CACHE_TYPE" => $arParams["CACHE_TYPE"],
			"CACHE_TIME" => $arParams["CACHE_TIME"],
		)
	);?>
	

	<?
	if($arTheme['PRIORITY_SECTION_DESCRIPTION_SOURCE']['VALUE'] !== 'NOT'){
		$top_desc = trim($APPLICATION->GetViewContent('top_desc'));
		$bottom_desc = trim($APPLICATION->GetViewContent('bottom_desc'));
		$sotbit_top_desc = trim($APPLICATION->GetViewContent('sotbit_seometa_top_desc'));
		$sotbit_bottom_desc = trim($APPLICATION->GetViewContent('sotbit_seometa_bottom_desc'));
		$sotbit_add_desc = trim($APPLICATION->GetViewContent('sotbit_seometa_add_desc'));

		if($arTheme['PRIORITY_SECTION_DESCRIPTION_SOURCE']['VALUE'] !== 'IBLOCK'){
			if(strlen($top_desc) && strlen($sotbit_top_desc)){
				unset($APPLICATION->__view['top_desc']);
			}
			if(strlen($bottom_desc) && strlen($sotbit_bottom_desc.$sotbit_add_desc)){
				unset($APPLICATION->__view['bottom_desc']);
			}
		}
		else{
			if(strlen($top_desc) && strlen($sotbit_top_desc)){
				unset($APPLICATION->__view['sotbit_seometa_top_desc']);
			}
			if(strlen($bottom_desc) && strlen($sotbit_bottom_desc.$sotbit_add_desc)){
				unset($APPLICATION->__view['sotbit_seometa_bottom_desc'], $APPLICATION->__view['sotbit_seometa_add_desc']);
			}
		}
	}
	?>
<?endif;?>

<?// не использовалось на старом сайте?>


