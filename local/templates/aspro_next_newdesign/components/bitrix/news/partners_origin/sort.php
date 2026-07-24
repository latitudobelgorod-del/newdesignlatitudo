<?
$arDisplays = array("block", "list", "table");
if(array_key_exists("display", $_REQUEST) || (array_key_exists("display", $_SESSION)) || $arParams["DEFAULT_LIST_TEMPLATE"]){
	if($_REQUEST["display"] && (in_array(trim($_REQUEST["display"]), $arDisplays))){
		$display = trim($_REQUEST["display"]);
		$_SESSION["display"]=trim($_REQUEST["display"]);
	}
	elseif($_SESSION["display"] && (in_array(trim($_SESSION["display"]), $arDisplays))){
		$display = $_SESSION["display"];
	}
	elseif($arSection["DISPLAY"]){
		$display = $arSection["DISPLAY"];
	}
	else{
		$display = $arParams["DEFAULT_LIST_TEMPLATE"];
	}
}
else{
	$display = "block";
}
$template = "catalog_".$display;
?>

<div class="sort_header float-right-mobile view_<?=$display?>">
	<!--noindex-->
		<div class="sort_filter1 pull-left <?=($arTheme['MOBILE_FILTER_COMPACT']['VALUE'] === 'Y' ? 'mobile_filter_compact' : '')?>">
			<?
			$arAvailableSort = array();
			if(!$arParams["SORT_BUTTONS"])
			{
				$arParams["SORT_BUTTONS"] = array(
					"POPULARITY" => "POPULARITY",
					"NAME" => "NAME",
					"PRICE" => "PRICE",
				);
			}
			$arSorts = $arParams["SORT_BUTTONS"];
			if(in_array("POPULARITY", $arSorts)){
				$arAvailableSort["SHOWS"] = array("SHOWS", "desc");
			}
			if(in_array("NAME", $arSorts)){
				$arAvailableSort["NAME"] = array("NAME", "asc");
			}
			if(in_array("PRICE", $arSorts)){
				$arSortPrices = $arParams["SORT_PRICES"];
				if($arSortPrices == "MINIMUM_PRICE" || $arSortPrices == "MAXIMUM_PRICE"){
					$arAvailableSort["PRICE"] = array("PROPERTY_".$arSortPrices, "desc");
				}
				else{
					if($arSortPrices == "REGION_PRICE")
					{
						global $arRegion;
						if($arRegion)
						{
							if(!$arRegion["PROPERTY_SORT_REGION_PRICE_VALUE"] || $arRegion["PROPERTY_SORT_REGION_PRICE_VALUE"] == "component")
							{
								$price = CCatalogGroup::GetList(array(), array("NAME" => $arParams["SORT_REGION_PRICE"]), false, false, array("ID", "NAME"))->GetNext();
								$arAvailableSort["PRICE"] = array("CATALOG_PRICE_".$price["ID"], "desc");
							}
							else
							{
								$arAvailableSort["PRICE"] = array("CATALOG_PRICE_".$arRegion["PROPERTY_SORT_REGION_PRICE_VALUE"], "desc");
							}
						}
						else
						{
							$price_name = ($arParams["SORT_REGION_PRICE"] ? $arParams["SORT_REGION_PRICE"] : "BASE");
							$price = CCatalogGroup::GetList(array(), array("NAME" => $price_name), false, false, array("ID", "NAME"))->GetNext();
							$arAvailableSort["PRICE"] = array("CATALOG_PRICE_".$price["ID"], "desc");
						}
					}
					else
					{
						$price = CCatalogGroup::GetList(array(), array("NAME" => $arParams["SORT_PRICES"]), false, false, array("ID", "NAME"))->GetNext();
						$arAvailableSort["PRICE"] = array("CATALOG_PRICE_".$price["ID"], "desc");
					}
				}
			}
			if(in_array("QUANTITY", $arSorts)){
				$arAvailableSort["CATALOG_AVAILABLE"] = array("QUANTITY", "desc");
			}

			$defaulSortButtons = array("SORT","POPULARITY", "NAME", "PRICE", "QUANTITY", "CUSTOM");
			$propsInSortName = array();

			foreach($arSorts as $sort_prop){
				if(!in_array($sort_prop, $defaulSortButtons)){
					$arAvailableSort['PROPERTY_'.$sort_prop] = array('PROPERTY_'.$sort_prop, "desc");
					$propsInSort[] = $sort_prop;
				}
			}

			if(is_array($propsInSort) && count($propsInSort)>0 ){
				foreach($propsInSort as $propSortCode){
					$dbRes = CIBlockProperty::GetList(array(), array('ACTIVE' => 'Y', 'IBLOCK_ID' => $arParams['IBLOCK_ID'], 'CODE' => $propSortCode));
					while($arPropperty = $dbRes->Fetch()){
						$propsInSortName['PROPERTY_'.$arPropperty['CODE']] = $arPropperty['NAME'];
					}
				}
			}

			$sortElementField = ToUpper($arParams["ELEMENT_SORT_FIELD"]);
			if(in_array("CUSTOM", $arSorts) && !array_key_exists($sortElementField, $arAvailableSort) ){
				$arAvailableSort[$sortElementField] = array("CUSTOM", ToLower($arParams["ELEMENT_SORT_ORDER"]));
			}

			$sort = "SHOWS";
			$customSort = false;
			if((array_key_exists("sort", $_REQUEST) && array_key_exists(ToUpper($_REQUEST["sort"]), $arAvailableSort)) || (array_key_exists("sort", $_SESSION) && array_key_exists(ToUpper($_SESSION["sort"]), $arAvailableSort)) || $arParams["ELEMENT_SORT_FIELD_LANDING"]){
				if($_REQUEST["sort"]){
					$sort = ToUpper($_REQUEST["sort"]);
					$_SESSION["sort"] = ToUpper($_REQUEST["sort"]);
				}
				elseif($_SESSION["sort"]){
					$sort = ToUpper($_SESSION["sort"]);
				}
				else{
					$sort = ToUpper($arParams["ELEMENT_SORT_FIELD_LANDING"]);
				}
			}
			if( $sort === $sortElementField ){
				if(!array_key_exists($sortElementField, $arAvailableSort) || $arAvailableSort[$sortElementField][0] === 'CUSTOM'  ){
					$customSort = true;
				}				
			}

			$arDelUrlParams = array('sort', 'order', 'control_ajax', 'ajax_get_filter', 'linerow', 'display');
			$sort_order=$arAvailableSort[$sort][1];
			if((array_key_exists("order", $_REQUEST) && in_array(ToLower($_REQUEST["order"]), Array("asc", "desc"))) || (array_key_exists("order", $_REQUEST) && in_array(ToLower($_REQUEST["order"]), Array("asc", "desc")) ) || $arParams["LINKED_ELEMENT_TAB_SORT_ORDER"]){
				if($_REQUEST["order"]){
					$sort_order = $_REQUEST["order"];
					$_SESSION["order"] = $_REQUEST["order"];
				}
				elseif($_SESSION["order"]){
					$sort_order = $_SESSION["order"];
				}
				else{
					$sort_order = ToLower($arParams["LINKED_ELEMENT_TAB_SORT_ORDER"]);
				}
			}
			?>
			<?if($arAvailableSort):?>
			<div class="dropdown-select">
				<div class="dropdown-select__title">
						<span>
							<?if($sort_order && $sort):?>
								<?if( in_array($sort, array_keys($propsInSortName)) ):?>
									<?=\Bitrix\Main\Localization\Loc::getMessage('SORT_TITLE_PROPETY', array('#CODE#' => $propsInSortName[$sort])).\Bitrix\Main\Localization\Loc::getMessage('SECT_ORDER_'.$sort_order)?>
								<?else:?>
									<?=\Bitrix\Main\Localization\Loc::getMessage('SECT_SORT_'.($customSort ? 'CUSTOM' : $sort)).\Bitrix\Main\Localization\Loc::getMessage('SECT_ORDER_'.$sort_order)?>
								<?endif;?>
							<?else:?>
								<?=\Bitrix\Main\Localization\Loc::getMessage('NOTHING_SELECTED');?>
							<?endif;?>
						</span>
						<?=CNext::showIconSvg("down", SITE_TEMPLATE_PATH.'/images/svg/trianglearrow_down.svg', '', '', true, false);?>
				</div>
				<div class="dropdown-select__drawer dropdown-select__drawer--mobile-right dropdown-select__drawer--to-top" role="menu">
						<div class="menu-list scrollbar">
							<?$arOrder = ['desc', 'asc']?>
							<?foreach($arAvailableSort as $key => $arVals):?>
								<?foreach($arOrder as $value):?>
									<div class="menu-list__item">
										<?$newSort = $sort_order == 'desc' ? 'asc' : 'desc';
										$current_url = $APPLICATION->GetCurPageParam('sort='.$key.'&order='.$value, $arDelUrlParams);
										$url = str_replace('+', '%2B', $current_url);?>
										<?if($bCurrentLink = ($sort == $key && $sort_order == $value)):?>
											<span class="menu-list__link menu-list__link--current">
										<?else:?>
											<a href="<?=$url;?>" class="menu-list__link <?=$value?> <?=$key?> darken <?=($arParams['AJAX_CONTROLS'] == 'Y' ? ' js-load-link' : '');?>" data-url="<?=$url;?>" rel="nofollow">
										<?endif;?>
											<?if( in_array($key, array_keys($propsInSortName)) ):?>
												<span><?=\Bitrix\Main\Localization\Loc::getMessage('SORT_TITLE_PROPETY', array('#CODE#' => $propsInSortName[$key])).\Bitrix\Main\Localization\Loc::getMessage('SECT_ORDER_'.$value)?></span>
											<?else:?>
												<span><?=\Bitrix\Main\Localization\Loc::getMessage('SECT_SORT_'.($arAvailableSort[$key][0] === 'CUSTOM' ? 'CUSTOM' : $key)).\Bitrix\Main\Localization\Loc::getMessage('SECT_ORDER_'.$value)?></span>
											<?endif;?>
										<?if($bCurrentLink):?>
											</span>
										<?else:?>
											</a>
										<?endif;?>
									</div>
								<?endforeach?>
							<?endforeach;?>
						</div>
					</div>
			</div>
				<?\Aspro\Next\Functions\Extensions::init('dropdown-select');?>
			<?endif;?>

			<?
			if($sort == "PRICE"){
				$sort = $arAvailableSort["PRICE"][0];
			}
			if($sort == "CATALOG_AVAILABLE"){
				$sort = "CATALOG_QUANTITY";
			}
			?>
		</div>
		<div class="sort_display">
			<?foreach($arDisplays as $displayType):?>
				<?
				$current_url = '';
				$current_url = $APPLICATION->GetCurPageParam('display='.$displayType, 	array('display', 'ajax_get', 'ajax_get_filter'));
				$url = str_replace('+', '%2B', $current_url);
				?>
				<a rel="nofollow" href="<?=$url;?>" class="sort_btn <?=$displayType?> <?=($display == $displayType ? 'current' : '')?>"><i title="<?=GetMessage("SECT_DISPLAY_".strtoupper($displayType))?>"></i></a>
			<?endforeach;?>
		</div>
		<div class="clearfix"></div>
	<!--/noindex-->
</div>