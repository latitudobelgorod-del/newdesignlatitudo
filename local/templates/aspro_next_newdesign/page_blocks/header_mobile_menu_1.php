<?
$parsedUrl = $_SERVER['SERVER_NAME'];
$url_page = $_SERVER['REQUEST_URI'];
$parsedUrl = str_replace('ru', '', $parsedUrl);
global $arTheme, $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');
$REGION_TAG_RAITING = "#REGION_TAG_RAITING#";
?>
<div class="mobilemenu-v1 scroller">
	<div class="wrap">
		<?$APPLICATION->IncludeComponent(
			"bitrix:menu",
			"top_mobile",
			Array(
				"COMPONENT_TEMPLATE" => "top_mobile",
				"MENU_CACHE_TIME" => "",
				"MENU_CACHE_TYPE" => "N",
				"MENU_CACHE_USE_GROUPS" => "N",
				"MENU_CACHE_GET_VARS" => array(
				),
				"DELAY" => "N",
				"MAX_LEVEL" => "4",
				"ALLOW_MULTI_SELECT" => "Y",
				"ROOT_MENU_TYPE" => "top_content_multilevel",
			/*	"CHILD_MENU_TYPE" => "left",*/
				"CHILD_MENU_TYPE" => "top_menu_new",
				"CACHE_SELECTED_ITEMS" => "N",
				"ALLOW_MULTI_SELECT" => "Y",
				"USE_EXT" => "Y"
			)
		);?>
<div class="mobile_search search-block mobile_dop_block">
							<?$APPLICATION->IncludeComponent(
								"bitrix:main.include",
								"",
								Array(
									"AREA_FILE_SHOW" => "file",
									"PATH" => SITE_DIR."include/top_page/search.title.catalog.php",
									"EDIT_TEMPLATE" => "include_area.php"
								)
							);?>
						</div>							
                				
		<?
		// show regions
		CNext::ShowMobileRegions();
		// show cabinet item
		CNext::ShowMobileMenuCabinet();
		// show basket item
		CNext::ShowMobileMenuBasket();
		?>
		<div style="padding:15px 20px 10px 20px;"><?=$REGION_TAG_RAITING?></div>	
		<?$APPLICATION->IncludeComponent(
			"aspro:social.info.next",
			"mobile",array(
				"CACHE_TYPE" => "A",
				"CACHE_TIME" => "3600000",
				"CACHE_GROUPS" => "N",
				"COMPONENT_TEMPLATE" => ".default"
			),
			false);?>
			
		<div class="mobile_dop_block next_site"><a class="text" href="https://<?=$parsedUrl?>org" rel="nofollow"><!--noindex-->Перейти на отдельный сайт по фасадным материалам<!--/noindex--></a></div>
	</div>
</div>