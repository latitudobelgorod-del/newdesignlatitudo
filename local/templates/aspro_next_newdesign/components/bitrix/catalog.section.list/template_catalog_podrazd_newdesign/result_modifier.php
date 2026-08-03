<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
/**
 * Собираем плоский список разделов в дерево — копия result_modifier.php
 * шаблонов template_catalog_podrazd* без изменений.
 */
if($arParams["TOP_DEPTH"] > 1)
{
	$arSections = array();
	$arSectionsDepth3 = array();

	foreach($arResult["SECTIONS"] as $arItem)
	{
		if($arItem["DEPTH_LEVEL"] == 1)
			$arSections[$arItem["ID"]] = $arItem;
		elseif($arItem["DEPTH_LEVEL"] == 2)
			$arSections[$arItem["IBLOCK_SECTION_ID"]]["SECTIONS"][$arItem["ID"]] = $arItem;
		elseif($arItem["DEPTH_LEVEL"] == 3)
			$arSectionsDepth3[] = $arItem;
	}

	if($arSectionsDepth3)
	{
		foreach($arSectionsDepth3 as $arItem)
		{
			foreach($arSections as $key => $arSection)
			{
				if(is_array($arSection["SECTIONS"][$arItem["IBLOCK_SECTION_ID"]]) && !empty($arSection["SECTIONS"][$arItem["IBLOCK_SECTION_ID"]]))
					$arSections[$key]["SECTIONS"][$arItem["IBLOCK_SECTION_ID"]]["SECTIONS"][$arItem["ID"]] = $arItem;
			}
		}
	}

	$arResult["SECTIONS"] = $arSections;
}
