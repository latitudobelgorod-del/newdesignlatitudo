<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>
<?global $arRegion;
  $regionID = ($arRegion ? $arRegion['ID'] : '');?>

	<?if( count( $arResult["ITEMS"] ) >= 1 ){?>
	<?if(($arParams["AJAX_REQUEST"]=="N") || !isset($arParams["AJAX_REQUEST"])){?>
	
		     <div style="border:1px solid #F2F2F2;margin-top:60px;" class="top_slider_wrapp editslide maxwidth-banner catalog_block items block_list" id="portfolio_loader" >
        <div class="flexslider ">
		
		<ul class="  slides">	 
	
	<?}?>

		<?
		$currencyList = '';
		if (!empty($arResult['CURRENCIES'])){
			$templateLibrary[] = 'currency';
			$currencyList = CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true);
		}
		$templateData = array(
			'TEMPLATE_LIBRARY' => $templateLibrary,
			'CURRENCIES' => $currencyList
		);
		unset($currencyList, $templateLibrary);
		$arParams["BASKET_ITEMS"]=($arParams["BASKET_ITEMS"] ? $arParams["BASKET_ITEMS"] : array());
		$arOfferProps = implode(';', $arParams['OFFERS_CART_PROPERTIES']);
		?>
<?
$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/bitrix/components/maxyss/measure_unit/templates/aspro_list_tp/style.css');
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/bitrix/components/maxyss/measure_unit/templates/aspro_list_tp/script.js'); 
?>
	  <? $arr_count_article = array(); ?>
	  <?foreach($arResult["ITEMS"] as $i => $arItem){?>
	
		 <li class="box" >
			<div  class="item_block">
				<div class="catalog_item_wrapp item">
				<?/*basket_props_block*/?>
					<div class="basket_props_block" id="bx_basket_div_<?=$arItem["ID"];?>" style="display: none;">
						<?if (!empty($arItem['PRODUCT_PROPERTIES_FILL'])){
							foreach ($arItem['PRODUCT_PROPERTIES_FILL'] as $propID => $propInfo){?>
								<input type="hidden" name="<? echo $arParams['PRODUCT_PROPS_VARIABLE']; ?>[<? echo $propID; ?>]" value="<? echo htmlspecialcharsbx($propInfo['ID']); ?>">
								<?if (isset($arItem['PRODUCT_PROPERTIES'][$propID]))
									unset($arItem['PRODUCT_PROPERTIES'][$propID]);
							}
						}
						$arItem["EMPTY_PROPS_JS"]="Y";
						$emptyProductProperties = empty($arItem['PRODUCT_PROPERTIES']);
						if (!$emptyProductProperties){
							$arItem["EMPTY_PROPS_JS"]="N";?>
							<div class="wrapper">
								<table>
									<?foreach ($arItem['PRODUCT_PROPERTIES'] as $propID => $propInfo){?>
										<tr>
											<td><? echo $arItem['PROPERTIES'][$propID]['NAME']; ?></td>
											<td>
												<?if('L' == $arItem['PROPERTIES'][$propID]['PROPERTY_TYPE']	&& 'C' == $arItem['PROPERTIES'][$propID]['LIST_TYPE']){
													foreach($propInfo['VALUES'] as $valueID => $value){?>
														<label>
															<input type="radio" name="<? echo $arParams['PRODUCT_PROPS_VARIABLE']; ?>[<? echo $propID; ?>]" value="<? echo $valueID; ?>" <? echo ($valueID == $propInfo['SELECTED'] ? '"checked"' : ''); ?>><? echo $value; ?>
														</label>
													<?}
												}else{?>
													<select name="<? echo $arParams['PRODUCT_PROPS_VARIABLE']; ?>[<? echo $propID; ?>]"><?
														foreach($propInfo['VALUES'] as $valueID => $value){?>
															<option value="<? echo $valueID; ?>" <? echo ($valueID == $propInfo['SELECTED'] ? '"selected"' : ''); ?>><? echo $value; ?></option>
														<?}?>
													</select>
												<?}?>
											</td>
										</tr>
									<?}?>
								</table>
							</div>
							<?}?>
					</div>
				<?/*basket_props_block*/?>
				
					<?
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_EDIT"));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')));
					
					$arItem["strMainID"] = $this->GetEditAreaId($arItem['ID']);
					$arItemIDs=CNext::GetItemsIDs($arItem);
					
					$totalCount = CNext::GetTotalCount($arItem, $arParams);
					$arQuantityData = CNext::GetQuantityArray($totalCount, $arItemIDs["ALL_ITEM_IDS"]);

					$item_id = $arItem["ID"];
					$strMeasure = '';
					$arAddToBasketData;
					if(!$arItem["OFFERS"] || $arParams['TYPE_SKU'] !== 'TYPE_1'){
						if($arParams["SHOW_MEASURE"] == "Y" && $arItem["CATALOG_MEASURE"]){
							$arMeasure = CCatalogMeasure::getList(array(), array("ID" => $arItem["CATALOG_MEASURE"]), false, false, array())->GetNext();
							$strMeasure = $arMeasure["SYMBOL_RUS"];
						}
						$arAddToBasketData = CNext::GetAddToBasketArray($arItem, $totalCount, $arParams["DEFAULT_COUNT"], $arParams["BASKET_URL"], false, $arItemIDs["ALL_ITEM_IDS"], 'small', $arParams);
					}
					elseif($arItem["OFFERS"]){
						$strMeasure = $arItem["MIN_PRICE"]["CATALOG_MEASURE_NAME"];
						/*дописала*/$arParams['OFFERS_CUSTOM'] = true;
					}
					$elementName = ((isset($arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) && $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) ? $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] : $arItem['NAME']);

					//Вывод артикула элемента
					$isArticle=$arItem["PROPERTIES"]["CML2_ARTICLE"]["VALUE"];
					//Показать или спрятать размеры элемента
					//$elementDlina = $arItem["PROPERTIES"]["PROP_2065"]["VALUE"];
					//$elementShirina = $arItem["PROPERTIES"]["IT_8"]["VALUE"];
					//$elementTolshina = $arItem["PROPERTIES"]["IT_6"]["VALUE"];
					//Показать или спрятать размеры элемента
					?>
					
	
					
					<div class="catalog_item main_item_wrapper item_wrap <?=(($_GET['q'])) ? 's' : ''?>" id="<?=$arItemIDs["strMainID"];?>">
					<div class="inner_wrap">
						<div class="stickers">
						<?$prop = ($arParams["STIKERS_PROP"] ? $arParams["STIKERS_PROP"] : "HIT");?>
							<?foreach(CNext::GetItemStickers($arItem["PROPERTIES"][$prop]) as $arSticker):?>
								<div class="<?=$arSticker['CLASS']?>"></div>
							<?endforeach;?>
							<?if($arParams["SALE_STIKER"] && $arItem["PROPERTIES"][$arParams["SALE_STIKER"]]["VALUE"]):?>
								<div>
								<?foreach($arItem["PROPERTIES"][$arParams["SALE_STIKER"]]["VALUE"] as $val):?>
								<div class="sticker_sale_text"><?=$val;?></div>
								<?endforeach;?>
								</div>
							<?endif;?>
						</div>	
						
						<div class="image_wrapper_block">
						<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="thumb shine" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['PICT']; ?>">
															<?
															$a_alt = ($arItem["PREVIEW_PICTURE"] && strlen($arItem["PREVIEW_PICTURE"]['DESCRIPTION']) ? $arItem["PREVIEW_PICTURE"]['DESCRIPTION'] : ($arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_ALT"] ? $arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_ALT"] : $arItem["NAME"] ));
															$a_title = ($arItem["PREVIEW_PICTURE"] && strlen($arItem["PREVIEW_PICTURE"]['DESCRIPTION']) ? $arItem["PREVIEW_PICTURE"]['DESCRIPTION'] : ($arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_TITLE"] ? $arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_TITLE"] : $arItem["NAME"] ));
															?>
															<?if( !empty($arItem["PREVIEW_PICTURE"]) ):?>
																<? if ($arItem['PROPERTIES']['SET']['VALUE']): ?>
																	<div class="<?=$arItem['PROPERTIES']['SET']['VALUE_XML_ID']?>"></div>
																<?endif;?>
																<img  src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>"  alt="<?=$a_alt;?>" title="<?=$a_title;?>" />
															<?elseif( !empty($arItem["DETAIL_PICTURE"])):?>
																<?$img = CFile::ResizeImageGet($arItem["DETAIL_PICTURE"], array( "width" => 170, "height" => 170 ), BX_RESIZE_IMAGE_PROPORTIONAL,true );?>
																<img   src="<?=$img["src"]?>" alt="<?=$a_alt;?>" title="<?=$a_title;?>" />
															<?else:?>
																<img  src="<?=SITE_TEMPLATE_PATH?>/images/no_photo_medium.png" alt="<?=$a_alt;?>" title="<?=$a_title;?>" />
															<?endif;?>
						</a>						
						<?if($arItem["OFFERS"]):?>
							<div class="colproduct_block1 " ></div>
						<?else:?>
							<?if (($arItem["PRODUCT"]["QUANTITY"]) == 0):?>
							<div class="colproduct_block notavailability"></div>
							<?else:?>
							<div class="colproduct_block availability"></div>
							<?endif;?>
						<?endif;?>
						</div>

							<?/*item_info*/?>
							<div class="item_info <?=$arParams["TYPE_SKU"]?>">
							<div class="item-title"><a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="dark_link"><span><?=$elementName;?><br></span></a></div>
							<?/*Смотрите также / Вариации*/?>
							<?if($arItem['PROPERTIES']['ASSOCIATED']['VALUE']):?>
											
								<div class="list__catalog_sku">
								<?foreach($arItem["PROPERTIES"]["ASSOCIATED"]["VALUE"] as $assosiated):?>
						
								<?$res = CIBlockElement::GetByID($assosiated);?>
								<?if($ar_res = $res->GetNext()):?>
								<?$img = CFile::ResizeImageGet($ar_res["PREVIEW_PICTURE"], array( "width" => 170, "height" => 170 ), BX_RESIZE_IMAGE_PROPORTIONAL,true );?>
								<div class="list__sku-item <?=(($arItem["ID"] == $ar_res['ID']) ? 'active' : '')?>" >
								
								<a href="<?=$ar_res["DETAIL_PAGE_URL"];?>"  title="<?=$ar_res["NAME"];?>" alt="<?=$ar_res["NAME"];?>">
								<img  src="<?=$img["src"]?>" alt="<?=$ar_res["NAME"];?>" title="<?=$ar_res["NAME"];?>"  />
								</a>
								
								</div>
								
								<?endif;?>
								
								<?endforeach;?>
								
								</div>
							<?endif;?>
							<?/*Смотрите также / Вариации*/?>	
							<?if($arItem["OFFERS"]){?>
								<?if(!empty($arItem['OFFERS_PROP'])){?>
									<div class="sku_props">

										<div class="bx_catalog_item_scu wrapper_sku" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['PROP_DIV']; ?>">
											<?$arSkuTemplate = array();?>
											<?$arSkuTemplate=CNext::GetSKUPropsArray($arItem['OFFERS_PROPS_JS'], $arResult["SKU_IBLOCK_ID"], $arParams["DISPLAY_TYPE"], $arParams["OFFER_HIDE_NAME_PROPS"]); ?>
											<?foreach ($arSkuTemplate as $code => $strTemplate){
												if (!isset($arItem['OFFERS_PROP'][$code]))
													continue;
												echo '<div>', str_replace('#ITEM#_prop_', $arItemIDs["ALL_ITEM_IDS"]['PROP'], $strTemplate), '</div>';
											}?>
										</div>
										<?$arItemJSParams=CNext::GetSKUJSParams($arResult, $arParams, $arItem);?>

										<script type="text/javascript">
											var <? echo $arItemIDs["strObName"]; ?> = new JCCatalogSection(<? echo CUtil::PhpToJSObject($arItemJSParams, false, true); ?>);
										</script>
									</div>
								<?}?>
							<?}?>

				<?if($arItem["OFFERS"]):?>
					<?if(!empty($arItem['OFFERS_PROP'])):?>
					<div class="article_block item-article" ></div>
					<?endif;?>
				<?else:?>
					<?if ($isArticle): ?>
				
		 <? $arr_count_article[$arItem['ID']] = $isArticle; ?>
					<div data-id="<?= $arItem['ID'] ?>" class="article_block_nooffer" ></div>
					
					<?endif;?>
				<?endif;?>

			
				
	
				
				
				
				
				
								<div class="cost prices clearfix">
								
									<?if( $arItem["OFFERS"]){?>
									
																
										<div class="with_matrix <?=($arParams["SHOW_OLD_PRICE"]=="Y" ? 'with_old' : '');?>" style="display:none;">
											<div class="price price_value_block"><span class="values_wrapper"></span></div>
											<?if($arParams["SHOW_OLD_PRICE"]=="Y"):?>
												<div class="price discount"></div>
											<?endif;?>
											<?if($arParams["SHOW_DISCOUNT_PERCENT"]=="Y"){?>
												<div class="sale_block matrix" style="display:none;">
													<div class="sale_wrapper">
													<span class="title"></span>
													<div class="text"><span class="values_wrapper"></span></div>
													<div class="clearfix"></div></div>
												</div>
											<?}?>
										</div>
											
										
                                        <?
                                        foreach ($arItem["OFFERS"] as $off){
                                            if($item_id == $off['ID']) {
//
                                                if(is_array($off['PROPERTIES']['UNIT_KOEF']['VALUE'])) {
                                                    foreach ($off['PROPERTIES']['UNIT_KOEF']['DESCRIPTION'] as $key => $un) {
                                                        ?>
                                                        <span style="display: none"><?= round($off['PRICES']['BASE']['DISCOUNT_VALUE'] * $off['PROPERTIES']['UNIT_KOEF']['VALUE'][$key], 0) ?> руб./<?echo $arResult['MEASURE_ALL'][$un]['SYMBOL_RUS'] ?></span>
                                                        <?
                                                    }
                                                    ?>
                                                    <span  style="display: none"><?=$off['PRICES']['BASE']['DISCOUNT_VALUE']?> руб./<?echo $off['ITEM_MEASURE']['TITLE']?></span>
                                                    <?
                                                }
                                            }
                                        }
                                        ?>
										<?\Aspro\Functions\CAsproSku::showItemPrices($arParams, $arItem, $item_id, $min_price_id, $arItemIDs, 'Y');?>
									<?}else{?>
										<?
										$item_id = $arItem["ID"];
										if(isset($arItem['PRICE_MATRIX']) && $arItem['PRICE_MATRIX']) // USE_PRICE_COUNT
										{?>
											<?if($arItem['ITEM_PRICE_MODE'] == 'Q' && count($arItem['PRICE_MATRIX']['ROWS']) > 1):?>
											
												<?//=CNext::showPriceRangeTop($arItem, $arParams, GetMessage("CATALOG_ECONOMY"));?>
											<?endif;?>
											<?=CNext::showPriceMatrix($arItem, $arParams, $strMeasure, $arAddToBasketData);?>
											<?$arMatrixKey = array_keys($arItem['PRICE_MATRIX']['MATRIX']);
											$min_price_id=current($arMatrixKey);?>
										<?
                                            if (is_array($arItem['PROPERTIES']['UNIT_KOEF']['VALUE'])) {
                                                foreach ($arItem['PROPERTIES']['UNIT_KOEF']['DESCRIPTION'] as $key => $un) {
                                                    ?>
                                                    <span style="display: none"><?= round($arItem['MIN_PRICE']['DISCOUNT_VALUE'] * $arItem['PROPERTIES']['UNIT_KOEF']['VALUE'][$key], 0) ?>
                                                        руб./<?
                                                        echo $arResult['MEASURE_ALL'][$un]['SYMBOL_RUS'] ?></span>
                                                    <?
                                                }
                                                ?>
                                                <span style="display: none"><?= $arItem['MIN_PRICE']['DISCOUNT_VALUE'] ?>
                                                    руб./<?
                                                    echo $arItem['ITEM_MEASURE']['TITLE'] ?></span>
                                                <?
                                            }
										}
										else
										{
											$arCountPricesCanAccess = 0;
											$min_price_id=0;?>
											<?\Aspro\Functions\CAsproItem::showItemPrices($arParams, $arItem["PRICES"], $strMeasure, $min_price_id, 'Y');?>
                                            <?
                                            if (is_array($arItem['PROPERTIES']['UNIT_KOEF']['VALUE'])) {
                                                foreach ($arItem['PROPERTIES']['UNIT_KOEF']['DESCRIPTION'] as $key => $un) {
                                                    ?>
                                                    <span style="display: none"><?= round($arItem['PRICES']['BASE']['DISCOUNT_VALUE'] * $arItem['PROPERTIES']['UNIT_KOEF']['VALUE'][$key], 0) ?>
                                                        руб./<?
                                                        echo $arResult['MEASURE_ALL'][$un]['SYMBOL_RUS'] ?></span>
                                                    <?
                                                }
                                                ?>
                                                <span style="display: none"><?= $arItem['PRICES']['BASE']['DISCOUNT_VALUE'] ?>
                                                    руб./<?
                                                    echo $arItem['ITEM_MEASURE']['TITLE'] ?></span>
                                                <?
                                            }
                                        ?>
										<?}?>

									<?}?>
								</div>
								
								
								
								
									<?if($arParams["SHOW_DISCOUNT_TIME"]=="Y" && $arParams['SHOW_COUNTER_LIST'] != 'N'){?>
									<?$arUserGroups = $USER->GetUserGroupArray();?>
									<?if($arParams['SHOW_DISCOUNT_TIME_EACH_SKU'] != 'Y' || ($arParams['SHOW_DISCOUNT_TIME_EACH_SKU'] == 'Y' && !$arItem['OFFERS'])):?>
										<?$arDiscounts = CCatalogDiscount::GetDiscountByProduct($item_id, $arUserGroups, "N", $min_price_id, SITE_ID);
										$arDiscount=array();
										if($arDiscounts)
											$arDiscount=current($arDiscounts);
										if($arDiscount["ACTIVE_TO"]){?>
											<div class="view_sale_block <?=($arQuantityData["HTML"] ? '' : 'wq');?>"">
												<div class="count_d_block">
													<span class="active_to hidden"><?=$arDiscount["ACTIVE_TO"];?></span>
													<div class="title"><?=GetMessage("UNTIL_AKC");?></div>
													<span class="countdown values"><span class="item"></span><span class="item"></span><span class="item"></span><span class="item"></span></span>
												</div>
												<?if($arQuantityData["HTML"]):?>
													<div class="quantity_block">
														<div class="title"><?=GetMessage("TITLE_QUANTITY_BLOCK");?></div>
														<div class="values">
															<span class="item">
																<span class="value" <?=((count( $arItem["OFFERS"] ) > 0 && $arParams["TYPE_SKU"] == 'TYPE_1' && $arItem["OFFERS_PROP"]) ? 'style="opacity:0;"' : '')?>><?=$totalCount;?></span>
																<span class="text"><?=GetMessage("TITLE_QUANTITY");?></span>
															</span>
														</div>
													</div>
												<?endif;?>
											</div>
											<?/*item_info*/?>
										<?}?>
									<?else:?>
										<?if($arItem['JS_OFFERS'])
										{
											foreach($arItem['JS_OFFERS'] as $keyOffer => $arTmpOffer2)
											{
												$active_to = '';
												$arDiscounts = CCatalogDiscount::GetDiscountByProduct( $arTmpOffer2['ID'], $arUserGroups, "N", array(), SITE_ID );
												if($arDiscounts)
												{
													foreach($arDiscounts as $arDiscountOffer)
													{
														if($arDiscountOffer['ACTIVE_TO'])
														{
															$active_to = $arDiscountOffer['ACTIVE_TO'];
															break;
														}
													}
												}
												$arItem['JS_OFFERS'][$keyOffer]['DISCOUNT_ACTIVE'] = $active_to;
											}
										}?>
										<div class="view_sale_block" style="display:none;">
											<div class="count_d_block">
													<span class="active_to_<?=$arItem["ID"]?> hidden"><?=$arDiscount["ACTIVE_TO"];?></span>
													<div class="title"><?=GetMessage("UNTIL_AKC");?></div>
													<span class="countdown countdown_<?=$arItem["ID"]?> values"></span>
											</div>
											<?if($arQuantityData["HTML"]):?>
												<div class="quantity_block">
													<div class="title"><?=GetMessage("TITLE_QUANTITY_BLOCK");?></div>
													<div class="values">
														<span class="item">
															<span class="value"><?=$totalCount;?></span>
															<span class="text"><?=GetMessage("TITLE_QUANTITY");?></span>
														</span>
													</div>
												</div>
											<?endif;?>
										</div>
									<?endif;?>
								<?}?>
							</div>
						<?/*item_info*/?>
						
						
										<?if(!$arItem["OFFERS"] || $arParams['TYPE_SKU'] !== 'TYPE_1'):?>
									<div class="counter_wrapp <?=($arItem["OFFERS"] && $arParams["TYPE_SKU"] == "TYPE_1" ? 'woffers' : '')?>">
										<?if(($arAddToBasketData["OPTIONS"]["USE_PRODUCT_QUANTITY_LIST"] && $arAddToBasketData["ACTION"] == "ADD") && $arAddToBasketData["CAN_BUY"]):?>
											<div class="counter_block" data-offers="<?=($arItem["OFFERS"] ? "Y" : "N");?>" data-item="<?=$arItem["ID"];?>">
												<span class="minus" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['QUANTITY_DOWN']; ?>">-</span>
												<input type="text" class="text" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['QUANTITY']; ?>" name="<? echo $arParams["PRODUCT_QUANTITY_VARIABLE"]; ?>" value="<?=$arAddToBasketData["MIN_QUANTITY_BUY"]?>" />
												<span class="plus" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['QUANTITY_UP']; ?>" <?=($arAddToBasketData["MAX_QUANTITY_BUY"] ? "data-max='".$arAddToBasketData["MAX_QUANTITY_BUY"]."'" : "")?>>+</span>
											</div>
										<?endif;?>
										<div id="<?=$arItemIDs["ALL_ITEM_IDS"]['BASKET_ACTIONS']; ?>" class="button_block button_list <?=(($arAddToBasketData["ACTION"] == "ORDER"/*&& !$arItem["CAN_BUY"]*/)  || !$arAddToBasketData["CAN_BUY"] || !$arAddToBasketData["OPTIONS"]["USE_PRODUCT_QUANTITY_LIST"] || $arAddToBasketData["ACTION"] == "SUBSCRIBE" ? "wide" : "");?>">
											<!--noindex-->
												<?=$arAddToBasketData["HTML"]?>
											<!--/noindex-->
										</div>
									</div>
									<?
									if(isset($arItem['PRICE_MATRIX']) && $arItem['PRICE_MATRIX']) // USE_PRICE_COUNT
									{?>
										<?if($arItem['ITEM_PRICE_MODE'] == 'Q' && count($arItem['PRICE_MATRIX']['ROWS']) > 1):?>
											<?$arOnlyItemJSParams = array(
												"ITEM_PRICES" => $arItem["ITEM_PRICES"],
												"ITEM_PRICE_MODE" => $arItem["ITEM_PRICE_MODE"],
												"ITEM_QUANTITY_RANGES" => $arItem["ITEM_QUANTITY_RANGES"],
												"MIN_QUANTITY_BUY" => $arAddToBasketData["MIN_QUANTITY_BUY"],
												"ID" => $arItemIDs["strMainID"],
											)?>
											<script type="text/javascript">
												var <? echo $arItemIDs["strObName"]; ?>el = new JCCatalogSectionOnlyElement(<? echo CUtil::PhpToJSObject($arOnlyItemJSParams, false, true); ?>);
											</script>
										<?endif;?>
									<?}?>
								<?elseif($arItem["OFFERS"]):?>
									<?if(empty($arItem['OFFERS_PROP'])){?>
										<div class="offer_buy_block buys_wrapp woffers">
											<?
											$arItem["OFFERS_MORE"] = "Y";
											$arAddToBasketData = CNext::GetAddToBasketArray($arItem, $totalCount, $arParams["DEFAULT_COUNT"], $arParams["BASKET_URL"], false, $arItemIDs["ALL_ITEM_IDS"], 'small read_more1', $arParams);?>
											<!--noindex-->
											<?=$arAddToBasketData["HTML"]?>
											<!--/noindex-->
										</div>
									<?}else{?>
                                            <?
                                                $buttonHTML = '<span class="small read_more1 to-order btn btn-default grey transition_bg transparent animate-load" data-event="jqm" data-param-form_id="TOORDER" data-name="toorder" data-autoload-product_name="" data-autoload-product_id="'.$arItem["ID"].'"><i></i><span>'.(empty($arItem["PROPERTIES"]['IN_STOCK']['PROPERTY_VALUE_ID'])?'Заказать':'В корзину').'</span></span>';

                                                ?>
										<div class="offer_buy_block buys_wrapp woffers">
											<div class="counter_wrapp">
                                                <div class="button_block wide">
                                                    <?=$buttonHTML?>
                                                </div>
                                            </div>
										</div>
									<?}?>
								<?endif;?>


							
						</div><?/*inner_wrap*/?>  


					</div><?/*catalog_item*/?> 
				</div><?/*catalog_item_wrapp*/?> 
				
			</div><?/*item_block*/?>
				</li>  
			
        <?if(isset($arItem['OFFERS']) && count($arItem['OFFERS'])>0){
            $offer_unit_yes = false;
            foreach ($arItem['OFFERS'] as $key=>$offer_u){
				if (isset($_GET['test'])){
					if (empty($offer_u['PRODUCT']['QUANTITY']))
						continue;
					//print_r($offer_u['PRODUCT']);
				}
                if(!empty( $offer_u["PROPERTIES"]["UNIT_KOEF"]["VALUE"]))
                    $offer_unit_yes = true;
                $arOfferUnit[$offer_u["ID"]]["ID"] = $offer_u["ID"];
                $arOfferUnit[$offer_u["ID"]]["UNITS"] = $offer_u["PROPERTIES"]["UNIT_KOEF"];
                $arOfferUnit[$offer_u["ID"]]["BASE_OFFER_MEASURE"] = $offer_u['ITEM_MEASURE'];
                $arOfferUnit[$offer_u["ID"]]["PRICES"] = $offer_u["PRICES"];
                $arOfferUnit[$offer_u["ID"]]["MIN_PRICE"] = $offer_u["MIN_PRICE"];
                $arOfferUnit[$offer_u["ID"]]["PRICE_MATRIX"] = $offer_u["PRICE_MATRIX"];
                $arOfferUnit[$offer_u["ID"]]["ITEM_PRICES"] = $offer_u["ITEM_PRICES"];
                $arOfferUnit[$offer_u["ID"]]["BASE_KOEFF_UNIT"] = $offer_u['PROPERTIES']['BASE_KOEF']['DESCRIPTION'];
                foreach ($offer_u["PROPERTIES"] as $key_sku => $sku_property){
    //                        echo '<pre>', print_r($sku_property[VALUE]), '</pre>' ;
                    if(array_key_exists($key_sku, $arResult['SKU_PROPS']) && !empty($sku_property['VALUE'])){

                        if(isset($arResult['SKU_PROPS'][$key_sku]['XML_MAP']))
                            $arOfferUnit[$offer_u["ID"]]["SKU_PROPS"][] = $sku_property['ID'].'_'.$arResult['SKU_PROPS'][$key_sku]['XML_MAP'][$sku_property['~VALUE']];
                        else
                            $arOfferUnit[$offer_u["ID"]]["SKU_PROPS"][] = $sku_property['ID'].'_'.$arResult['SKU_PROPS'][$key_sku]['VALUES'][$sku_property["VALUE_ENUM_ID"]]["ID"];
                    }
                }
            }

            if(!empty($arOfferUnit)) {
                $APPLICATION->IncludeComponent(
                    "maxyss:measure_unit",
                    "aspro_list_tp",
                    array(
                        "BX_BASCKET_OBJ" => $arItemIDs["strObName"],
                        "PRODUCT_ID" => $arItem["ID"],
                        "OFFERS_UNIT_YES" => $offer_unit_yes,
                        "OFFERS_UNIT" => $arOfferUnit,
                        "CACHE_TIME" => "172800",
                        "CACHE_TYPE" => "A",
                        "MAIN_MEASURE_UNIT" => $arItem["ITEM_MEASURE"]["TITLE"],
                        "MEASURE_BLOCK_SELECTOR" => $arItemIDs["strMainID"],
                        "MEASURE_INPUT_SELECTOR" => ".counter_block .text",
                        "MEASURE_RESULT" => $arItem["PROPERTIES"]["PROP_UNITS"],
                        "ASPRO_MEASURE" => "Y",
                        "COMPONENT_TEMPLATE" => "aspro_list_tp"
                    ), $component, array("HIDE_ICONS" => "Y")
                );
                ?><?
            }
//            unset($offer_unit_yes);
            unset($arOfferUnit);
        }else{
            $arOfferUnit[$arItem["ID"]]["ID"] = $arItem["ID"];
            $arOfferUnit[$arItem["ID"]]["UNITS"] = $arItem["PROPERTIES"]["UNIT_KOEF"];
            $arOfferUnit[$arItem["ID"]]["BASE_OFFER_MEASURE"] = $arItem['ITEM_MEASURE'];
            $arOfferUnit[$arItem["ID"]]["PRICES"] = $arItem["PRICES"];
            $arOfferUnit[$arItem["ID"]]["MIN_PRICE"] = $arItem["MIN_PRICE"];
            $arOfferUnit[$arItem["ID"]]["PRICE_MATRIX"] = $arItem["PRICE_MATRIX"];
            $arOfferUnit[$arItem["ID"]]["ITEM_PRICES"] = $arItem["ITEM_PRICES"];
            $arOfferUnit[$arItem["ID"]]["BASE_KOEFF_UNIT"] = $arItem['PROPERTIES']['BASE_KOEF']['DESCRIPTION'];

            $APPLICATION->IncludeComponent(
                "maxyss:measure_unit",
                "aspro_list_tp",
                array(
                    "BX_BASCKET_OBJ" => $arItemIDs["strObName"],
                    "PRODUCT_ID" => $arItem["ID"],
                    "OFFERS_UNIT_YES" => $offer_unit_yes,
                    "OFFERS_UNIT" => $arOfferUnit,
                    "CACHE_TYPE" => "N",
                    "MAIN_MEASURE_UNIT" => $arItem["ITEM_MEASURE"]["TITLE"],
                    "MEASURE_BLOCK_SELECTOR" => $arItemIDs["strMainID"],
                    "MEASURE_INPUT_SELECTOR" => ".counter_block .text",
                    "MEASURE_RESULT" => $arItem["PROPERTIES"]["UNIT_KOEF"],
                    "ASPRO_MEASURE" => "Y",
                    "COMPONENT_TEMPLATE" => "aspro_list_tp"
                ), $component, array("HIDE_ICONS" => "Y")
            );
            unset($arOfferUnit);
        }?>
		
		
		<?}?>
		
	
					    <? $json = json_encode($arr_count_article); ?>
    <script type="text/javascript">
        let name = <?= $json ?>;
        for (const i in name) {
            $('.article_block_nooffer[data-id=' + i + ']').text('Артикул: ' + name[i]);
        }
    </script>

			

		
	<?if(($arParams["AJAX_REQUEST"]=="N") || !isset($arParams["AJAX_REQUEST"])){?>
			</ul>
		</div>
		
			
			</div>	
		
	<?}?>
	
	<?if($arParams["AJAX_REQUEST"]=="Y"){?>
		<div class="wrap_nav">
	<?}?>
	
	
	
	<?if($arParams["AJAX_REQUEST"]=="Y"){?>
		</div>
	<?}?>
<?}else{?>
	<script>
		// $(document).ready(function(){
			$('.sort_header').animate({'opacity':'1'}, 500);
		// })
	</script>

<?}?>
	
	
<script>
	BX.message({
		QUANTITY_AVAILIABLE: '<? echo COption::GetOptionString("aspro.next", "EXPRESSION_FOR_EXISTS", GetMessage("EXPRESSION_FOR_EXISTS_DEFAULT"), SITE_ID); ?>',
		QUANTITY_NOT_AVAILIABLE: '<? echo COption::GetOptionString("aspro.next", "EXPRESSION_FOR_NOTEXISTS", GetMessage("EXPRESSION_FOR_NOTEXISTS"), SITE_ID); ?>',
		ADD_ERROR_BASKET: '<? echo GetMessage("ADD_ERROR_BASKET"); ?>',
		//ADD_ERROR_COMPARE: '<? echo GetMessage("ADD_ERROR_COMPARE"); ?>',
	})
</script>