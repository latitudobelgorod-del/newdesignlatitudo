<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?php
$arSKU = CCatalogSKU::GetInfoByProductIBlock($arParams["IBLOCK_ID"]);
if (is_array($arSKU)) {
  $arResult["ORLND"]["SKU_IBLOCK_ID"] = $arSKU["IBLOCK_ID"];
  
  $rsIBlock = CIBlock::GetByID($arSKU["IBLOCK_ID"]);
  if ($arIBlock = $rsIBlock->GetNext())
    $arResult["ORLND"]["SKU_IBLOCK_TYPE"] = $arIBlock["IBLOCK_TYPE_ID"];
  
  $rsProperty = CIBlockProperty::GetByID($arSKU["SKU_PROPERTY_ID"], $arSKU["IBLOCK_ID"]);
  if ($arProperty = $rsProperty->GetNext())
    $arResult["ORLND"]["SKU_PROPERTY_SID"] = $arProperty["CODE"];
}