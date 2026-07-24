<?
	if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
	//echo ShowError($arResult["ERROR_MESSAGE"]);
	$bDelayColumn  = false;
	$bDeleteColumn = false;
	$bWeightColumn = false;
	$bPropsColumn  = false;
	$rowCols = 0;
	if ($normalCount > 0):
	global $arBasketItems;
?>

<div class="module-cart">
	<div class="goods" style="overflow-x: hidden !important;">
		<?if(isset($arResult["ITEMS_IBLOCK_ID"])){?>
			<div class="iblockid" data-iblockid="<?=$arResult["ITEMS_IBLOCK_ID"];?>"></div>
		<?}?>
		<table class="colored" width="100%" id="basket_items">
			<thead>
				<tr>
					<?
						foreach ($arResult["GRID"]["HEADERS"] as $id => $arHeader)
						{
							if ($arHeader["id"] == "DELETE"){$bDeleteColumn = true;}
							if ($arHeader["id"] == "TYPE"){$bTypeColumn = true;}
							if ($arHeader["id"] == "QUANTITY"){$bQuantityColumn = true;}
							if ($arHeader["id"] == "DISCOUNT"){$bDiscountColumn = true;}
						}
					?>
					<?foreach ($arResult["GRID"]["HEADERS"] as $id => $arHeader):
						if (in_array($arHeader["id"], array("TYPE", "DISCOUNT"))) {continue;} // some header columns are shown differently
						elseif ($arHeader["id"] == "PROPS"){$bPropsColumn = true; continue;}
						elseif ($arHeader["id"] == "DELAY"){$bDelayColumn = true; continue;}
					//elseif ($arHeader["id"] == "WEIGHT"){ $bWeightColumn = true;}
						elseif ($arHeader["id"] == "DELETE"){ continue;}
						if ($arHeader["id"] == "NAME"):?>
							<td class="thumb-cell"></td><td class="name-th">
						<?else:?><td class="<?=strToLower($arHeader["id"])?>-th"><?endif;?><?=getColumnName($arHeader)?></td>
					<?endforeach;?>
					<?/*if ($bDelayColumn):?><td class="delay-cell"></td><?endif;*/?>
					<?if ($bDeleteColumn):?><td class="remove-cell"></td><?endif;?>
				</tr>
			</thead>

			<tbody>
				<?foreach ($arResult["GRID"]["ROWS"] as $k => $arItem):
					$currency = $arItem["CURRENCY"];
					if ($arItem["DELAY"] == "N" && $arItem["CAN_BUY"] == "Y"):
					$arBasketItems[]=$arItem["PRODUCT_ID"];?>
					<tr data-id="<?=$arItem["ID"]?>" product-id="<?=$arItem["PRODUCT_ID"]?>" data-iblockid="<?=$arItem["IBLOCK_ID"]?>"  <?if($arItem["QUANTITY"]>$arItem["AVAILABLE_QUANTITY"]):?>data-error="no_amounth"<?endif;?>>
						<?foreach ($arResult["GRID"]["HEADERS"] as $id => $arHeader):
							if (in_array($arHeader["id"], array("PROPS", "DELAY", "DELETE", "TYPE", "DISCOUNT"))) continue; // some values are not shown in columns in this template
							if ($arHeader["id"] == "NAME"):
								$bPreviewPicture = ($arItem["PREVIEW_PICTURE"]["SRC"] ?? '') !== '';
								$bDetailPicture = ($arItem["DETAIL_PICTURE"]["SRC"] ?? '') !== '';
								$bShowDetailURL = ($arItem["DETAIL_PAGE_URL"] ?? '') !== '';
							?>
								<td class="thumb-cell">
									<?if ($bPreviewPicture):?>
										<?if ($bShowDetailURL):?><a href="<?=$arItem["DETAIL_PAGE_URL"];?>" class="thumb"><?endif;?>
											<img src="<?=$arItem["PREVIEW_PICTURE"]["SRC"];?>" alt="<?=(is_array($arItem["PREVIEW_PICTURE"]["ALT"])?$arItem["PREVIEW_PICTURE"]["ALT"]:$arItem["NAME"]);?>" title="<?=(is_array($arItem["PREVIEW_PICTURE"]["TITLE"])?$arItem["PREVIEW_PICTURE"]["TITLE"]:$arItem["NAME"]);?>" />
										<?if ($bShowDetailURL):?></a><?endif;?>
									<?elseif ($bDetailPicture):?>
										<?if ($bShowDetailURL):?><a href="<?=$arItem["DETAIL_PAGE_URL"];?>" class="thumb"><?endif;?>
											<img src="<?=$arItem["DETAIL_PICTURE"]["SRC"];?>" alt="<?=(is_array($arItem["DETAIL_PICTURE"]["ALT"])?$arItem["DETAIL_PICTURE"]["ALT"]:$arItem["NAME"]);?>" title="<?=(is_array($arItem["DETAIL_PICTURE"]["TITLE"])?$arItem["DETAIL_PICTURE"]["TITLE"]:$arItem["NAME"]);?>" />
										<?if ($bShowDetailURL):?></a><?endif;?>
									<?else:?>
										<?if ($bShowDetailURL):?><a href="<?=$arItem["DETAIL_PAGE_URL"];?>" class="thumb"><?endif;?>
											<img src="<?=SITE_TEMPLATE_PATH?>/images/no_photo_medium.png" alt="<?=$arItem["NAME"];?>" title="<?=$arItem["NAME"];?>" width="70" height="70" />
										<?if ($bShowDetailURL):?></a><?endif;?>
									<?endif;?>
									<?if (!empty($arItem["BRAND"])):?><div class="ordercart_brand"><img src="<?=$arItem["BRAND"];?>" /></div><?endif;?>
								</td>
								<td class="name-cell" style="padding-left:0; padding-right:0;">
									<?if ($bShowDetailURL):?><a href="<?=$arItem["DETAIL_PAGE_URL"]?>"><?endif;?><?=$arItem["NAME"]?><?if ($bShowDetailURL):?></a><?endif;?><br />
									<?if ($bPropsColumn):?>
										<div class="item_props">
											<? /*foreach ($arItem["PROPS"] as $val) {
													if (is_array($arItem["SKU_DATA"])) {
														$bSkip = false;
														foreach ($arItem["SKU_DATA"] as $propId => $arProp) { if ($arProp["CODE"] == $val["CODE"]) { $bSkip = true; break; } }
														if ($bSkip) continue;
													} echo '<span class="item_prop"><span class="name">'.$val["NAME"].':&nbsp;</span><span class="property_value">'.$val["VALUE"].'</span></span>';
												}*/?>
										</div>
									<?endif;?>
									<?/*if (is_array($arItem["SKU_DATA"]) && $arItem["PROPS"]):
										foreach ($arItem["SKU_DATA"] as $propId => $arProp):
											$isImgProperty = false; // is image property
											foreach ($arProp["VALUES"] as $id => $arVal) { if (isset($arVal["PICT"]) && !empty($arVal["PICT"])) { $isImgProperty = true; break; } }
											$full = (count($arProp["VALUES"]) > 5) ? "full" : "";
											if ($isImgProperty): // iblock element relation property
											?>
												<div class="bx_item_detail_scu_small_noadaptive <?=$full?>">
													<span class="titles"><?=$arProp["NAME"]?>:</span>
													<div class="bx_scu_scroller_container">
														<div class="bx_scu values">
															<ul id="prop_<?=$arProp["CODE"]?>_<?=$arItem["ID"]?>">
															<?foreach ($arProp["VALUES"] as $valueId => $arSkuValue){
																$selected = "";
																foreach ($arItem["PROPS"] as $arItemProp) {
																	if ($arItemProp["CODE"] == $arItem["SKU_DATA"][$propId]["CODE"])
																		{ if ($arItemProp["VALUE"] == $arSkuValue["NAME"] || $arItemProp["VALUE"] == $arSkuValue["XML_ID"]) $selected = "class=\"bx_active\""; }
																};?>
																<li <?=$selected?>>
																	<span><?=$arSkuValue["NAME"]?></span>
																</li>
															<?}?>
															</ul>
														</div>
													</div>
												</div>
											<?else:?>
												<div class="bx_item_detail_size_small_noadaptive <?=$full?>">
													<span class="titles">
														<?=$arProp["NAME"]?>:
													</span>

													<div class="bx_size_scroller_container">
														<div class="bx_size values">
															<ul id="prop_<?=$arProp["CODE"]?>_<?=$arItem["ID"]?>">
																<?foreach ($arProp["VALUES"] as $valueId => $arSkuValue) {
																	$selected = "";
																	foreach ($arItem["PROPS"] as $arItemProp) {
																		if ($arItemProp["CODE"] == $arItem["SKU_DATA"][$propId]["CODE"])
																		{ if ($arItemProp["VALUE"] == $arSkuValue["NAME"]) $selected = "class=\"bx_active\""; }
																	}?>
																	<li <?=$selected?>><span><?=$arSkuValue["NAME"]?></span></li>
																<?}?>
															</ul>
														</div>
													</div>
												</div>
											<?endif;
										endforeach;
									endif;
									*/?>
									
									
									
								</td>
							<?elseif ($arHeader["id"] == "QUANTITY"):?>
								<td class="count-cell" style="vertical-align: top !important; padding-left: 2px; padding-right: 2px;">
									<div class="counter_block basket">
										<?
											$ratio = isset($arItem["MEASURE_RATIO"]) ? $arItem["MEASURE_RATIO"] : 1;
											$tmp_ratio=0;
											$tmp_ratio+=$ratio;
											$float_ratio=is_double($tmp_ratio);

											$max = isset($arItem["AVAILABLE_QUANTITY"]) ? "max=\"".$arItem["AVAILABLE_QUANTITY"]."\"" : "";
											if (!isset($arItem["MEASURE_RATIO"])){
												$arItem["MEASURE_RATIO"] = 1;
											}
										?>
										<?if (isset($arItem["AVAILABLE_QUANTITY"]) /*&& floatval($arItem["AVAILABLE_QUANTITY"]) != 0*/ /*&& !CSaleBasketHelper::isSetParent($arItem)*/):?><span onclick="setQuantityFly('<?=$arItem["ID"]?>', '<?=$arItem["MEASURE_RATIO"]?>', 'down')" class="minus">-</span><?endif;?>
										<input
											type="text"
											class="text"
											id="QUANTITY_INPUT_<?=$arItem["ID"]?>"
											name="QUANTITY_INPUT_<?=$arItem["ID"]?>"
											size="2"
											data-id="<?=$arItem["ID"];?>"
											data-float_ratio="<?=$float_ratio;?>"
											maxlength="18"
											min="0"
											<?=$max?>
											step="<?=$ratio?>"
											value="<?=$arItem["QUANTITY"]?>"
											onchange="updateQuantityFly('QUANTITY_INPUT_<?=$arItem["ID"]?>', '<?=$arItem["ID"]?>', '<?=$ratio?>')"
										>
										<?if (isset($arItem["AVAILABLE_QUANTITY"]) /*&& floatval($arItem["AVAILABLE_QUANTITY"]) != 0*/ /*&& !CSaleBasketHelper::isSetParent($arItem)*/):?><span onclick="setQuantityFly('<?=$arItem["ID"]?>', '<?=$arItem["MEASURE_RATIO"]?>', 'up')" class="plus">+</span><?endif;?>
									</div>
									<input type="hidden" id="QUANTITY_<?=$arItem['ID']?>" name="QUANTITY_<?=$arItem['ID']?>" value="<?=$arItem["QUANTITY"]?>" />
									<?/*if($arItem["QUANTITY"]>$arItem["AVAILABLE_QUANTITY"]):?><div class="error"><?=GetMessage("NO_NEED_AMMOUNT")?></div><?endif;*/?>
								</td>
							<?elseif ($arHeader["id"] == "SUMM"):?>
								<td class="summ-cell"><div class="cost prices"><div class="price"><?=$arItem["SUMM_FORMATED"];?></div></div></td>
							<?elseif ($arHeader["id"] == "PRICE"):?>
								<td class="cost-cell <?=( $bTypeColumn ? 'notes' : '' );?>">
									<div class="cost prices clearfix">
										<?/*if (strlen($arItem["NOTES"]) > 0 && $bTypeColumn):?>
											<div class="price_name"><?=$arItem["NOTES"]?> за 1 <?=$arItem["MEASURE_NAME"]?></div>
										<?endif;*/?>
										<?if( doubleval($arItem["DISCOUNT_PRICE_PERCENT"]) > 0 && $bDiscountColumn ){?>
											<div class="price"><?=$arItem["PRICE_FORMATED"]?></div>
											<div class="price discount"><strike><?=$arItem["FULL_PRICE_FORMATED"]?></strike></div>
											<input type="hidden" name="item_price_<?=$arItem["ID"]?>" value="<?=$arItem["PRICE"]?>" />
											<input type="hidden" name="item_price_discount_<?=$arItem["ID"]?>" value="<?=$arItem["FULL_PRICE"]?>" />
											<div class="sale_block">
												<span class="title"><?=GetMessage("ECONOMY")?></span>
												<div class="text"><?=SaleFormatCurrency(round($arItem["DISCOUNT_PRICE"]), $arItem["CURRENCY"]);?></div>
												<div class="clearfix"></div>
											</div>
										<?}else{?>
											<div class="price"><?=$arItem["PRICE_FORMATED"];?></div>
											<input type="hidden" name="item_price_<?=$arItem["ID"]?>" value="<?=$arItem["PRICE"]?>" />
										<?}?>
										<input type="hidden" name="item_summ_<?=$arItem["ID"]?>" value="<?=$arItem["PRICE"]*$arItem["QUANTITY"]?>" />
									</div>
								</td>
							<?elseif ($arHeader["id"] == "WEIGHT"):?>
								<td class="weight-cell"><?=$arItem["WEIGHT_FORMATED"]?></td>
							<?else:?>
								<td class="cell"><?=$arItem[$arHeader["id"]]?></td>
							<?endif;?>
						<?endforeach;?>

							<?/*if ($bDelayColumn ):?>
							<td class="delay-cell delay">
								<a class="wish_item" href="<?=str_replace("#ID#", $arItem["ID"], $arUrls["delay"])?>">
									<span class="icon" title="<?=GetMessage("SALE_DELAY");?>"><i></i></span>
								</a>
							</td>
						<?endif;*/?>
						<?if ($bDeleteColumn):?>
							<td class="remove-cell"><a class="remove" href="<?=str_replace("#ID#", $arItem["ID"], $arUrls["delete"])?>" title="<?=GetMessage("SALE_DELETE")?>"><i></i></a></td>
						<?endif;?>
					</tr>
					<?
					endif;
				endforeach;
				?>
				<?
					$arTotal = array();
					if ($bWeightColumn) { $arTotal["WEIGHT"]["NAME"] = GetMessage("SALE_TOTAL_WEIGHT"); $arTotal["WEIGHT"]["VALUE"] = $arResult["allWeight_FORMATED"];}
					if ($arParams["PRICE_VAT_SHOW_VALUE"] == "Y")
					{
						$arTotal["VAT_EXCLUDED"]["NAME"] = GetMessage("SALE_VAT_EXCLUDED"); $arTotal["VAT_EXCLUDED"]["VALUE"] = $arResult["allSum_wVAT_FORMATED"];
						$arTotal["VAT_INCLUDED"]["NAME"] = GetMessage("SALE_VAT_INCLUDED"); $arTotal["VAT_INCLUDED"]["VALUE"] = $arResult["allVATSum_FORMATED"];
					}
					if (doubleval($arResult["DISCOUNT_PRICE_ALL"]) > 0)
					{
						$arTotal["PRICE"]["NAME"] = GetMessage("SALE_TOTAL");
						$arTotal["PRICE"]["VALUES"]["ALL"] = $arResult["allSum_FORMATED"];
						$arTotal["PRICE"]["VALUES"]["WITHOUT_DISCOUNT"] = $arResult["PRICE_WITHOUT_DISCOUNT"];
					}
					else
					{
						$arTotal["PRICE"]["NAME"] = GetMessage("SALE_TOTAL");
						$arTotal["PRICE"]["VALUES"]["ALL"] = $arResult["allSum_FORMATED"];
					}
				?>
			</tbody>
		</table>
	</div>
	<?$arError = CNext::checkAllowDelivery($arResult["allSum"],$currency);?>
<div class="itog" style="padding:0 35px;">
		<div class="buttons clearfix" style="/*padding:50px 50px;*/0 0 20px 0;">
		<div class="colored fixed" height="100%" width="100%">
		<div data-id="total_row" style="
    text-align: right;
    padding: 20px 0;
">
					<div class="row_titles" style="display:inline-block;">
					
						<?foreach($arTotal as $key => $value):?>
							<?if ($value["VALUES"] && $value["NAME"]):?><div class="item_title" style=" display: inline-block;
    color: #333;
    padding: 0 35px 0 0;
    /* font-size: 22px; */
    vertical-align: top;
    font-size: 18px;
    font-weight:
    bold;line-height:15px;
"><?=$value["NAME"]?></div><?endif;?>
						<?endforeach;?>
					</div>
					<div class="row_values" style="
    display: inline-block;
    vertical-align: top;
">
						<div class="wrap_prices">
							<?foreach($arTotal as $key => $value):?>
								<?if ($value["VALUES"] && $value["NAME"]):?>
									<?if ($key=="PRICE"):?>
										<?if ($arResult["DISCOUNT_PRICE_ALL"]):?>
											<div data-type="price_discount">
												<div class="price"><?=$value["VALUES"]["ALL"];?></div>
												<div class="price discount"><strike><?=$value["VALUES"]["WITHOUT_DISCOUNT"];?></strike></div>
											</div>
										<?else:?>
											<div  data-type="price_normal"><div class="price"><?=$arResult["allSum_FORMATED"];?></div></div>
										<?endif;?>
									<?elseif ($value["VALUE"]):?>
										<div data-type="<?=strToLower($key)?>"><div class="price"><?=$value["VALUE"]?></div></div>
									<?endif;?>
								<?endif;?>
							<?endforeach;?>
						</div>
					</div>
					
					<div class="total_row">
						<?if($arError["ERROR"]){?>
							<div class="icon_error_block"><?=$arError["TEXT"];?></div>
						<?}?>
						
						</div>
						
				</div>
			
			
		</div>
		
		
		
				<div class="row">
					<div class="col-md-3 col-sm-3">
						
					<?/*Оформить заказ*/?>
						
							<?if (\Bitrix\Main\Config\Option::get("aspro.next", "SHOW_ONECLICKBUY_ON_BASKET_PAGE", "Y") == "Y"):?>
								<div class="basket_fast_order clearfix">
									<a onclick="oneClickBuyBasket()" class="<?php echo ($arError["ERROR"]) ? "disabled" : ""; ?> width100 btn btn-default fast_order"><span><?=GetMessage("SALE_FAST_ORDER")?></span></a>
																
									
									
									<?/*<div class="description"><?=GetMessage("SALE_FAST_ORDER_DESCRIPTION");?></div>*/?>
								</div>
							<?endif;?>
						
					<?/*Оформить заказ*/?>
					</div>
					
						<?/*Перейти в корзину*/?>
						<div class="col-md-3 col-sm-3">
							<div class="basket_back">
							<div class="wrap_button">
							<a href="<?=$arParams["PATH_TO_BASKET"]?>" class="btn btn-default white width100"><span><?=GetMessage("GO_TO_BASKET")?></span></a>
							</div>
							</div>
						</div>
						<?/*Перейти в корзину*/?>
					
					<?/*Очистить*/?>
					<div class="col-md-3 col-sm-3">
						<div class="basket_sort fly-1">
			
					<span class="wrap_remove_button">
						<?if($normalCount){?>
							<span class="btn btn-default white white-bg grey remove_all_basket AnDelCanBuy cur width100" data-type="basket"><?=GetMessage('CLEAR_BASKET')?></span>
						<?}?>
						<?if($delayCount){?>
							<span class="btn btn-default white white-bg grey remove_all_basket DelDelCanBuy width100" data-type="delay"><?=GetMessage('CLEAR_BASKET')?></span>
						<?}?>
						<?if($naCount){?>
							<span class="btn btn-default white white-bg grey remove_all_basket nAnCanBuy width100" data-type="na"><?=GetMessage('CLEAR_BASKET')?></span>
						<?}?>
					</span>
					</div></div>
					<?/*Очистить*/?>
			
			
			<?/*Свернуть*/?>
			<div class="col-md-3 col-sm-3" >
				<div class="basket_close">
				<span class="btn btn-default white grey close width100"><span><?=GetMessage("SALE_BACK")?></span></span>
				</div>
			</div>
			<?/*Свернуть*/?>
					
					</div>

						
		</div>
	</div>
</div>
<?else:?>
	<div class="cart_empty">
		<table cellspacing="0" cellpadding="0" width="100%" border="0"><tr><td class="img_wrapp">
			<div class="img"></div>
		</td><td>
			<div class="text">
				<?$APPLICATION->IncludeFile(SITE_DIR."include/empty_fly_cart.php", Array(), Array("MODE"      => "html", "NAME"      => GetMessage("SALE_BASKET_EMPTY"),));?>
			</div>
		</td></tr></table>
		<div class="clearboth"></div>
	</div>
<?endif;?>
<div class="one_click_buy_basket_frame"></div>