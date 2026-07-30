<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<? $this->setFrameMode( true ); ?>
<?if($arResult["ITEMS"]):?>
	<hr />
	<?if(strlen($arParams['TITLE'])):?>
		<h5><?=$arParams['TITLE'];?></h5>
	<?endif;?>
	<div class="tab_slider_wrapp specials best_block clearfix">
		<ul class="tabs_content">
			<li class="tab cur tabs_slider">
				<div class="top_wrapper">
					<div class="catalog_block items row margin0">
						<?foreach($arResult["ITEMS"] as $key => $arItem):?>
							<?
							$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_EDIT"));
							$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')));
							$totalCount = CNext::GetTotalCount($arItem, $arParams);
							$arQuantityData = CNext::GetQuantityArray($totalCount);
							$arItem["FRONT_CATALOG"]="Y";

							$strMeasure='';
							if($arItem["OFFERS"]){
								$strMeasure=$arItem["MIN_PRICE"]["CATALOG_MEASURE_NAME"];
							}else{
								if (($arParams["SHOW_MEASURE"]=="Y")&&($arItem["CATALOG_MEASURE"])){
									$arMeasure = CCatalogMeasure::getList(array(), array("ID"=>$arItem["CATALOG_MEASURE"]), false, false, array())->GetNext();
									$strMeasure=$arMeasure["SYMBOL_RUS"];
								}
							}

							$elementName = ((isset($arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) && $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) ? $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] : $arItem['NAME']);
							switch ($arParams["LINE_ELEMENT_COUNT"]){
								case '1':
								case '2':
									$col=2;
									break;
								case '3':
									$col=3;
									break;
								case '5':
									$col=5;
									break;
								default:
									$col=4;
									break;
							}
							if($arParams["LINE_ELEMENT_COUNT"] > 5)
								$col = 5;
								
							// stickers
							$arParams["STIKERS_PROP"] = $arParams["STIKERS_PROP"] ?: 'HIT';
							$bShowHitStickers = $arParams["STIKERS_PROP"] && isset($arItem['DISPLAY_PROPERTIES'][$arParams["STIKERS_PROP"]]) && $arItem["DISPLAY_PROPERTIES"][$arParams["STIKERS_PROP"]]["VALUE"];
							$bShowSaleStickers = $arParams["SALE_STIKER"] && isset($arItem['DISPLAY_PROPERTIES'][$arParams["SALE_STIKER"]]) && $arItem['DISPLAY_PROPERTIES'][$arParams["SALE_STIKER"]]["VALUE"];
							?>
							<?$arAddToBasketData = CNext::GetAddToBasketArray($arItem, $totalCount, $arParams["DEFAULT_COUNT"], $arParams["BASKET_URL"], true);?>
							<div class="catalog_item_wrapp item col-<?=$col;?> col-md-<?=ceil(12/$col);?> col-sm-<?=ceil(12/round($col / 2))?> col-xs-6 js-notice-block">
								<div class="catalog_item item_wrap" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
									<div class="inner_wrap">
										<div class="image_wrapper_block js-notice-block__image">
											<? if ($bShowHitStickers || $bShowSaleStickers): ?>
												<div class="stickers">
													<? if($bShowHitStickers): ?>
														<? foreach(CNext::GetItemStickers($arItem["DISPLAY_PROPERTIES"][$arParams["STIKERS_PROP"]]) as $arSticker): ?>
															<div><div class="<?=$arSticker['CLASS']?>"><?=$arSticker['VALUE']?></div></div>
														<? endforeach; ?>
													<? endif; ?>
													<? if($bShowSaleStickers): ?>
														<div><div class="sticker_sale_text"><?= $arItem["DISPLAY_PROPERTIES"][$arParams["SALE_STIKER"]]["VALUE"]; ?></div></div>
													<? endif; ?>
												</div>
											<? endif; ?>
											<?if( ($arParams["DISPLAY_WISH_BUTTONS"] != "N" || $arParams["DISPLAY_COMPARE"] == "Y" || $arParams['GALLERY_ITEM_SHOW'] == 'Y')):?>
												<div class="like_icons">
													<?if($arAddToBasketData["CAN_BUY"] && empty($arItem["OFFERS"]) && $arParams["DISPLAY_WISH_BUTTONS"] != "N"):?>
														<div class="wish_item_button" <?=($arAddToBasketData['CAN_BUY'] ? '' : 'style="display:none"');?>>
															<span title="<?=GetMessage('CATALOG_WISH')?>" class="wish_item to" data-item="<?=$arItem["ID"]?>"><i></i></span>
															<span title="<?=GetMessage('CATALOG_WISH_OUT')?>" class="wish_item in added" style="display: none;" data-item="<?=$arItem["ID"]?>"><i></i></span>
														</div>
													<?endif;?>
													<?if($arParams["DISPLAY_COMPARE"] == "Y"):?>
														<div class="compare_item_button">
															<span title="<?=GetMessage('CATALOG_COMPARE')?>" class="compare_item to" data-iblock="<?=$arParams["IBLOCK_ID"]?>" data-item="<?=$arItem["ID"]?>" ><i></i></span>
															<span title="<?=GetMessage('CATALOG_COMPARE_OUT')?>" class="compare_item in added" style="display: none;" data-iblock="<?=$arParams["IBLOCK_ID"]?>" data-item="<?=$arItem["ID"]?>"><i></i></span>
														</div>
													<?endif;?>
													<?if($arParams['GALLERY_ITEM_SHOW'] == 'Y'):?>
														<div class="fast_view_wrapper">
															<span>
																<?if($fast_view_text_tmp = CNext::GetFrontParametrValue('EXPRESSION_FOR_FAST_VIEW'))
																	$fast_view_text = $fast_view_text_tmp;
																else
																	$fast_view_text = GetMessage('FAST_VIEW');?>
																<i class="fast_view_block" data-event="jqm" data-param-form_id="fast_view" data-param-iblock_id="<?=$arParams["IBLOCK_ID"];?>" data-param-id="<?=$arItem["ID"];?>" data-param-fid="<?=$arItemIDs["strMainID"];?>" data-param-item_href="<?=urlencode($arItem["DETAIL_PAGE_URL"]);?>" title="<?=$fast_view_text;?>" data-name="fast_view">
																</i>
															</span>
														</div>
													<?endif;?>
												</div>
											<?endif;?>
											<?$arParams['EVENT_TYPE'] = 'catalog_top_block_view'?>
											<?if($arParams['GALLERY_ITEM_SHOW'] == 'Y'):?>
												<?\Aspro\Functions\CAsproNext::showSectionGallery( array('ITEM' => $arItem, 'RESIZE' => $arResult['CUSTOM_RESIZE_OPTIONS']) );?>
											<?else:?>
												<?\Aspro\Functions\CAsproNext::showImg($arParams, $arItem);?>
											<?endif;?>
										</div>
										<div class="item_info">
											<div class="item-title">
												<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="dark_link js-notice-block__title"><span><?=$elementName?></span></a>
											</div>
											<?if($arItem["DISPLAY_PROPERTIES"]["SUB_TITLE"]["VALUE"]):?>
												<div class="item_info--preview_text muted999">
													<?if(!is_array($arItem["DISPLAY_PROPERTIES"]["SUB_TITLE"]["~VALUE"])){
														echo $arItem["DISPLAY_PROPERTIES"]["SUB_TITLE"]["VALUE"];
													} else {
														echo $arItem["DISPLAY_PROPERTIES"]["SUB_TITLE"]["~VALUE"]["TEXT"];
													}?>
												</div>
											<?endif;?>
											<?if($arParams["SHOW_RATING"] == "Y"):?>
												<div class="rating">
													<?//$frame = $this->createFrame('dv_'.$arItem["ID"])->begin('');?>
													<?if( $arParams['REVIEWS_VIEW'] ):?>
														<div class="blog-info__rating--top-info EXTENDED">
															<div class="votes_block nstar with-text">
																<div class="ratings">
																	<?$message = $arItem['PROPERTIES']['EXTENDED_REVIEWS_COUNT']['VALUE'] ? GetMessage('VOTES_RESULT', array('#VALUE#' => $arItem['PROPERTIES']['EXTENDED_REVIEWS_RAITING']['VALUE'])) : GetMessage('VOTES_RESULT_NONE')?>
																	<div class="inner_rating" title="<?=$message?>">
																		<?for($i=1;$i<=5;$i++):?>
																			<div class="item-rating <?=$i<=$arItem['PROPERTIES']['EXTENDED_REVIEWS_RAITING']['VALUE'] ? 'filled' : ''?>"></div>
																		<?endfor;?>
																	</div>
																</div>
															</div>
														</div>
													<?else:?>
														<?$APPLICATION->IncludeComponent(
														"bitrix:iblock.vote",
														"element_rating_front",
														Array(
															"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
															"IBLOCK_ID" => $arItem["IBLOCK_ID"],
															"ELEMENT_ID" =>$arItem["ID"],
															"MAX_VOTE" => 5,
															"VOTE_NAMES" => array(),
															"CACHE_TYPE" => $arParams["CACHE_TYPE"],
															"CACHE_TIME" => $arParams["CACHE_TIME"],
															"DISPLAY_AS_RATING" => 'vote_avg'
														),
														$component, array("HIDE_ICONS" =>"Y")
														);?>
													<?endif;?>
													<?//$frame->end();?>
												</div>
											<?endif;?>
											<div class="sa_block">
												<?=$arQuantityData["HTML"];?>
											</div>
											<div class="cost prices clearfix">
												<?if($arItem["OFFERS"]):?>
													<?\Aspro\Functions\CAsproSku::showItemPrices($arParams, $arItem, $item_id, $min_price_id, array(), ($arParams["SHOW_DISCOUNT_PERCENT_NUMBER"] == "Y" ? "N" : "Y"));?>
												<?else:?>
													<?
													if(isset($arItem['PRICE_MATRIX']) && $arItem['PRICE_MATRIX']) // USE_PRICE_COUNT
													{?>
														<?if($arItem['ITEM_PRICE_MODE'] == 'Q' && count($arItem['PRICE_MATRIX']['ROWS']) > 1):?>
															<?=CNext::showPriceRangeTop($arItem, $arParams, GetMessage("CATALOG_ECONOMY"));?>
														<?endif;?>
														<?=CNext::showPriceMatrix($arItem, $arParams, $strMeasure, $arAddToBasketData);?>
														<?$arMatrixKey = array_keys($arItem['PRICE_MATRIX']['MATRIX']);
														$min_price_id=current($arMatrixKey);?>
													<?
													}
													elseif($arItem["PRICES"])
													{?>
														<?\Aspro\Functions\CAsproItem::showItemPrices($arParams, $arItem["PRICES"], $strMeasure, $min_price_id, ($arParams["SHOW_DISCOUNT_PERCENT_NUMBER"] == "Y" ? "N" : "Y"));?>
													<?}?>
												<?endif;?>
											</div>
										</div>
										<div class="footer_button">
											<?=$arAddToBasketData["HTML"]?>
										</div>
									</div>
								</div>
							</div>
						<?endforeach;?>
					</div>
				</div>
			</li>
		</ul>
	</div>
<?else:?>
	<?$this->setFrameMode(true);?>
	<script type="text/javascript">
	$(document).ready(function(){
		$(".news_detail_wrapp .similar_products_wrapp").remove();
	}); /* dirty hack, remove this code */
	</script>
<?endif;?>