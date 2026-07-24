<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
 global $APPLICATION;?>


<?
if(is_array($arResult["SECTION"]))
foreach($arResult["SECTION"]["PATH"] as $arPath)

$APPLICATION->AddChainItem($arPath["NAME"], $arPath["SECTION_PAGE_URL"]);
?>