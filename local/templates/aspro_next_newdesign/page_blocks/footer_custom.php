<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?
global $arTheme, $arRegion;
$arRegions = CNextRegionality::getRegions();
$regionID = ($arRegion ? $arRegion['ID'] : '');
?>
<?if( isMobilelat() ):?>

    <?$APPLICATION->IncludeComponent(
								"bitrix:main.include",
								"",
								Array(
								"AREA_FILE_SHOW" => "file",
								"AREA_FILE_SUFFIX" => "inc",
								"EDIT_TEMPLATE" => "",
								"PATH" => "/include/footer/f-mobile.php"
								)
								);?> 

<?else:?>

  
			<?$APPLICATION->IncludeComponent(
								"bitrix:main.include",
								"",
								Array(
								"AREA_FILE_SHOW" => "file",
								"AREA_FILE_SUFFIX" => "inc",
								"EDIT_TEMPLATE" => "",
								"PATH" => "/include/footer/f-desk.php"
								)
			);?> 

<?$APPLICATION->IncludeComponent(
								"bitrix:main.include",
								"",
								Array(
								"AREA_FILE_SHOW" => "file",
								"AREA_FILE_SUFFIX" => "inc",
								"EDIT_TEMPLATE" => "",
								"PATH" => "/include/footer/footer_block/footer_6_col.php"
								)
			);?> 

		  <?endif;?>