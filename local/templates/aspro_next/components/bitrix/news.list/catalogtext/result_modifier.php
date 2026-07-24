<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;

// result_modifier шаблона компонента news.list
// задача - "SEOшник со стажем" сказал сделать description вида "[Название элементов через запятую] скачать бесплатно без регистрации без смс"
$arNames = Array(); // сюда собираем названия элементов
foreach($arResult["ITEMS"] as $arItem){
    $arNames[] = $arItem["NAME"];
$arResult["DESCRIPTION"] = $arItem["IPROPERTY_VALUES"]["ELEMENT_META_DESCRIPTION"];
//$arResult["TITLE"] = $arItem["IPROPERTY_VALUES"]["ELEMENT_META_TITLE"];
//$arResult["PAGE_TITLE"] = $arItem["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"];
}

$cp = $this->__component; // объект компонента
if (is_object($cp)) 
   $cp->SetResultCacheKeys(array('DESCRIPTION')); 
 if (is_object($cp)) 
	 $cp->SetResultCacheKeys(array('TITLE'));
 if (is_object($cp)) 
	 $cp->SetResultCacheKeys(array('PAGE_TITLE'));


