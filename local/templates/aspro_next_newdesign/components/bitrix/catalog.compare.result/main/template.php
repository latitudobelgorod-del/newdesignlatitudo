<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$isAjax = ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST["ajax_action"]) && $_POST["ajax_action"] === "Y");
$bOrderViewBasket = $arParams['ORDER_VIEW'];
?>
<div class="bx_compare" id="bx_catalog_compare_block">
	<?
	if ($isAjax) {
		$APPLICATION->RestartBuffer();
	}

	$bUseGroups = $arParams["USE_COMPARE_GROUP"] === "Y";
	$bDelCookie = false;
	if ($bUseGroups) {
		$activeTabCookie = isset($_COOKIE['compare_section']) && $_COOKIE['compare_section'] > 0 ? $_COOKIE['compare_section'] : '0';
		$arSectionsIds = array_column($arResult['SECTIONS'], 'ID');

		if (!in_array($activeTabCookie, $arSectionsIds) && $activeTabCookie) {
			$bDelCookie = true;
		}

		$activeTabId = in_array($activeTabCookie, $arSectionsIds) ? $activeTabCookie : reset($arResult['SECTIONS'])['ID'];
	}
	else {
		$activeTabId = '0';
	}
	?>
	<div class="catalog-compare swipeignore"<?=$bDelCookie ? ' data-delcookie' : '' ?>>
		<?if ($bUseGroups && count($arResult['SECTIONS']) > 1):?>
			<div class="tabs arrow_scroll tabs--in-section compare-sections__tabs">
				<ul class="nav nav-tabs">
					<?foreach ($arResult['SECTIONS'] as $arSection):?>
						<li class="compare-sections__tab-item <?=$arSection['ID'] === $activeTabId ? 'active' : '' ?>">
							<span data-section-id="<?=$arSection['ID'];?>">
								<?=$arSection['NAME'];?>
								<span class="muted compare-sections__tab-count"><?=count($arSection["ITEMS"]);?></span>
							</span>
						</li>
					<?endforeach;?>
				</ul>
			</div>
		<?endif;?>
		<div class="catalog-compare__top flexbox flexbox--row justify-content-between align-items-normal">
			<!-- noindex -->
			<ul class="tabs-head nav nav-tabs hidden">
				<li <?=(!$arResult["DIFFERENT"] ? 'class="active"' : '');?>>
					<a rel="nofollow" class="sortbutton<?=(!$arResult["DIFFERENT"] ? ' active' : '');?>" data-href="?DIFFERENT=N" rel="nofollow"><?=GetMessage("CATALOG_ALL_CHARACTERISTICS");?></a>
				</li>
				<li <?=($arResult["DIFFERENT"] ? 'class="active"' : '');?>>
					<a rel="nofollow" class="sortbutton diff <?=($arResult["DIFFERENT"] ? ' active' : '');?>" data-href="?DIFFERENT=Y" rel="nofollow"><?=GetMessage("CATALOG_ONLY_DIFFERENT");?></a>
				</li>
			</ul>
			<div class="catalog-compare__switch form__check form__check--switch form__check--switch--secondary">
				<div class="onoff filter sm">
					<input type="checkbox" id="compare_diff" <?=($arResult['DIFFERENT'] ? 'checked' : '');?>>
					<label for="compare_diff" class="muted">
						<?=GetMessage("CATALOG_ONLY_DIFFERENT");?>
					</label>
				</div>
			</div>
			<!-- /noindex -->
			<?
			$arStr = $arCompareIDs = array();
			if ($arResult["ITEMS"]) {
				foreach ($arResult["ITEMS"] as $arItem) {
					$arCompareIDs[] = $arItem["ID"];
				}
			}
			$arStr = implode("&ID[]=", $arCompareIDs);
			?>
			<span class="catalog-compare__clear color-theme-hover muted" onclick="CatalogCompareObj.MakeAjaxAction('<?=$GLOBALS['arTheme']['COMPARE_PAGE_URL']['VALUE'];?>?action=DELETE_FROM_COMPARE_RESULT&ID[]=<?=$arStr ?>', 'Y');">
				<?=GetMessage("CLEAR_ALL_COMPARE");?>
			</span>
		</div>
		<div class="catalog-compare__inner loading">
			<div class="catalog-compare loader_circle"></div>
			<div class="table_compare catalog-block">
				<?foreach ($arResult['SECTIONS'] as $arSection):?>
					<div class="compare-sections__item <?=$arSection['ID'] === $activeTabId ? 'active' : '' ?>" data-section-id="<?=$arSection['ID'];?>">
						<?if ($arResult["SHOW_FIELDS"]):?>
							<div class="catalog-compare__items catalog_block ajax_load items block flexbox flexbox--row owl-carousel owl-bg-nav visible-nav owl-carousel--light owl-carousel--outer-dots owl-carousel--button-wide owl-carousel--button-offset-half owl-carousel--after-offset-1" data-plugin-options='{"nav": true, "autoplay" : false, "dots": false, "autoplayTimeout" : "3000", "smartSpeed":500, "responsiveClass": true, "withSlide": "catalog-compare__props-slider", "rewind": true, "margin": -1, "responsive":{"0":{"items": 2},"768":{"items": 3},"992":{"items": 4},"1200":{"items": 5}}}'>
								<?foreach ($arSection["ITEMS"] as &$arElement):?>	
									<?$arElement['ARTICLE'] = $arElement['OFFER_DISPLAY_PROPERTIES']['ARTICLE'] ?? ($arElement['DISPLAY_PROPERTIES']['CML2_ARTICLE'] ?? []);?>							
									<div class="catalog-block__wrapper height-100">
										<div class="catalog-block__item catalog_item bordered bg-theme-parent-hover border-theme-parent-hover js-notice-block" id="<?=$this->GetEditAreaId($arElement['ID']);?>">
											<div class="catalog-block__inner flexbox height-100">
												<span onclick="CatalogCompareObj.MakeAjaxAction('<?=CUtil::JSEscape($arElement['~DELETE_URL']);?>', 'Y');" 
													  class="remove colored_theme_hover_text stroke-use-grey stroke-theme-use-svg-hover" 
													  title="<?=GetMessage("CATALOG_REMOVE_PRODUCT");?>"
												>
													<i></i>
												</span>
												<?
												$name = $arElement["OFFER_FIELDS"]["NAME"] ?? $arElement["NAME"];
												$arElement['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] = $name;
												
												if ($arParams['SKU_DETAIL_ID'] && isset($arElement["OFFER_FIELDS"]["ID"])) {
													$arElement["DETAIL_PAGE_URL"] .= '?oid=' . $arElement["OFFER_FIELDS"]["ID"];
												}

												$bOrderButton = ($arElement["PROPERTIES"]["FORM_ORDER"]["VALUE_XML_ID"] == "YES");
												if ($arElement['FIELDS']['PREVIEW_PICTURE']) {
													$arElement['PREVIEW_PICTURE'] = $arElement['FIELDS']['PREVIEW_PICTURE'];
												}

												// image from offer
												if (isset($arElement["OFFER_FIELDS"]['PREVIEW_PICTURE']) && $arElement["OFFER_FIELDS"]['PREVIEW_PICTURE']) {
													$arElement['PREVIEW_PICTURE'] = $arElement["OFFER_FIELDS"]['PREVIEW_PICTURE'];
												} else if (isset($arElement["OFFER_FIELDS"]['DETAIL_PICTURE']) && $arElement["OFFER_FIELDS"]['DETAIL_PICTURE']) {
													$arElement['PREVIEW_PICTURE'] = $arElement["OFFER_FIELDS"]['DETAIL_PICTURE'];
												}

												// quantity from offer
												if (isset($arElement["OFFER_FIELDS"]['QUANTITY'])) {
													$arElement['CATALOG_QUANTITY'] = $arElement["OFFER_FIELDS"]['QUANTITY'];
													$arElement['~CATALOG_QUANTITY'] = $arElement["OFFER_FIELDS"]['QUANTITY'];
												}


												// basket button
												$arItemIDs = CNext::GetItemsIDs($arElement);
												$totalCount = CNext::GetTotalCount($arElement, $arParams);
												$arAddToBasketData = CNext::GetAddToBasketArray(
													$arElement,
													$totalCount,
													$arParams["DEFAULT_COUNT"],
													$arParams["BASKET_URL"],
													false,
													$arItemIDs["ALL_ITEM_IDS"],
													'small',
													$arParams
												);
												$bShowCounterBlock = $arAddToBasketData["OPTIONS"]["USE_PRODUCT_QUANTITY_LIST"]
													&& $arAddToBasketData["ACTION"] === "ADD"
													&& $arAddToBasketData["CAN_BUY"];
												$bWideButton = $arAddToBasketData["ACTION"] === "ORDER"
													|| !$arAddToBasketData["CAN_BUY"]
													|| !$arAddToBasketData["OPTIONS"]["USE_PRODUCT_QUANTITY_LIST"]
													|| $arAddToBasketData["ACTION"] === "SUBSCRIBE";

												$arImage = [];
												?>
												<div class="image_wrapper_block js-notice-block__image">
													<?
													if ($arElement["OFFER_FIELDS"]["PREVIEW_PICTURE"]) {
														$arImage = is_array($arElement["OFFER_FIELDS"]["PREVIEW_PICTURE"])
															? $arElement["OFFER_FIELDS"]["PREVIEW_PICTURE"]
															: CFile::GetFileArray($arElement["OFFER_FIELDS"]["PREVIEW_PICTURE"]);

														$arElement['THUMB'] = CFile::ResizeImageGet($arElement["OFFER_FIELDS"]["PREVIEW_PICTURE"]['ID'], ['WIDTH' => 60, 'HEIGHT' => 60], BX_RESIZE_IMAGE_PROPORTIONAL_ALT, true);
													} 
													elseif ($arElement["FIELDS"]["PREVIEW_PICTURE"] && is_array($arElement["FIELDS"]["PREVIEW_PICTURE"])) {
														$arImage = [
															'SRC' => $arElement["FIELDS"]["PREVIEW_PICTURE"]["SRC"],
															'ALT' => $arElement["FIELDS"]["PREVIEW_PICTURE"]["ALT"],
															'TITLE' => $arElement["FIELDS"]["PREVIEW_PICTURE"]["TITLE"],
														];
														$arElement['THUMB'] = CFile::ResizeImageGet($arElement["PREVIEW_PICTURE"]['ID'], ['WIDTH' => 60, 'HEIGHT' => 60], BX_RESIZE_IMAGE_PROPORTIONAL_ALT, true);
													} 
													elseif ($arElement["FIELDS"]["DETAIL_PICTURE"] && is_array($arElement["FIELDS"]["DETAIL_PICTURE"])) {
														$arImage = [
															'SRC' => $arElement["FIELDS"]["DETAIL_PICTURE"]["SRC"],
															'ALT' => $arElement["FIELDS"]["DETAIL_PICTURE"]["ALT"],
															'TITLE' => $arElement["FIELDS"]["DETAIL_PICTURE"]["TITLE"],
														];
														$arElement['THUMB'] = CFile::ResizeImageGet($arElement["DETAIL_PICTURE"]['ID'], ['WIDTH' => 60, 'HEIGHT' => 60], BX_RESIZE_IMAGE_PROPORTIONAL_ALT, true);
													} 
													else {
														$arImage = [
															'SRC' => SITE_TEMPLATE_PATH . '/images/no_photo_medium.png',
															'ALT' => $arElement["NAME"],
															'TITLE' => $arElement["NAME"],
														];
													}
													?>

													<?if (!empty($arImage)):?>
														<a href="<?=$arElement["DETAIL_PAGE_URL"];?>">
															<img src="<?=$arImage['SRC'];?>" alt="<?=$arImage["ALT"];?>" title="<?=$arImage["TITLE"];?>" />
														</a>
													<?endif;?>
												</div>
												<?$dataItem = CNext::getDataItem($arElement);?>
												<div class="catalog-block__info flex-1 flexbox justify-content-between" data-id="<?=$arElement['ID'];?>" data-item="<?=$dataItem;?>">
													<div class="catalog-block__info-top">
														<div class="catalog-block__info-inner">
															<div class="catalog-block__info-inner">
																<?// element title?>
																<div class="catalog-block__info-title item-title lineclamp-4 height-auto-t600">
																	<a href="<?=$arElement["DETAIL_PAGE_URL"];?>" class="dark_link switcher-title js-notice-block__title""><span><?=$name;?></span></a>
																</div>
															</div>
														</div>
													</div>

													<div class="catalog-block__info-bottom">
														<div class="line-block line-block--20 flexbox--wrap justify-content-between">
															<?// element price?>
															<div class="line-block__item catalog-block__info-bottom--margined js-popup-price catalog-block__info-price cost prices">
																<?
																$frame = $this->createFrame()->begin('');
																$frame->setBrowserStorage(true);
																\Aspro\Functions\CAsproItem::showItemPrices($arParams, $arElement['PRICES'], '', $price_id, ($arParams["SHOW_DISCOUNT_PERCENT_NUMBER"] == "Y" ? "N" : "Y"));
																$frame->end();
																?>
															</div>
															<div class="line-block__item catalog-block__info-bottom--margined">
																	<div class="article_block" >
																	<?if(isset($arElement['ARTICLE']) && $arElement['ARTICLE']['VALUE']){?>
																		<div> <?=$arElement['ARTICLE']['NAME']?> : <?=$arElement['ARTICLE']['VALUE'];?> </div>
																		<?}?>
																	</div>
															</div>	
															<?// element buttons?>
															<div class="line-block__item catalog-block__info-bottom--margined catalog-block__info-btn">
																<div class="counter_wrapp">
																	<?if ($bShowCounterBlock):?>
																		<div class="counter_block" data-offers="N" data-item="<?=$arElement['ID'];?>">
																			<span class="minus" id="<?=$arItemIDs['ALL_ITEM_IDS']['QUANTITY_DOWN'];?>">-</span>
																			<input type="text" class="text" id="<?=$arItemIDs['ALL_ITEM_IDS']['QUANTITY'];?>" name="<?=$arParams['PRODUCT_QUANTITY_VARIABLE'];?>" value="<?=$arAddToBasketData['MIN_QUANTITY_BUY'];?>" />
																			<span class="plus" id="<?=$arItemIDs['ALL_ITEM_IDS']['QUANTITY_UP'];?>" <?=$arAddToBasketData['MAX_QUANTITY_BUY'] ? ' data-max="' . $arAddToBasketData['MAX_QUANTITY_BUY'] . '"' : '';?>>+</span>
																		</div>
																	<?endif;?>
																	<div class="button_block<?=$bWideButton ?  ' wide' : '';?>" id="<?=$arItemIDs["ALL_ITEM_IDS"]['BASKET_ACTIONS'];?>">
																		<!--noindex-->
																		<?=$arAddToBasketData["HTML"] ?>
																		<!--/noindex-->
																	</div>								
																</div>
															</div>	
														</div>
													</div>

												</div>
											</div>
										</div>
									</div>
									<?unset($arElement);?>
								<?endforeach;?>
							</div>
						<?endif;?>

						<?if ($arResult["ALL_FIELDS"] || $arResult["ALL_PROPERTIES"] || $arResult["ALL_OFFER_FIELDS"] || $arResult["ALL_OFFER_PROPERTIES"]):?>
							<?$bShowDeletedProps = false;
							if (!empty($arResult["ALL_FIELDS"])) {
								foreach ($arResult["ALL_FIELDS"] as $propCode => $arProp) {
									if (!isset($arResult['FIELDS_REQUIRED'][$propCode])) {
										if ($arProp["IS_DELETED"] != "N") {
											$bShowDeletedProps = true;
											break;
										}
									}
								}
							}
							if (!$bShowDeletedProps) {
								if (!empty($arResult["ALL_OFFER_FIELDS"])) {
									foreach ($arResult["ALL_OFFER_FIELDS"] as $propCode => $arProp) {
										if ($arProp["IS_DELETED"] != "N") {
											$bShowDeletedProps = true;
											break;
										}
									}
								}
							}
							if (!$bShowDeletedProps) {
								if (!empty($arResult["ALL_PROPERTIES"])) {
									foreach ($arResult["ALL_PROPERTIES"] as $propCode => $arProp) {
										if ($arProp["IS_DELETED"] != "N") {
											$bShowDeletedProps = true;
											break;
										}
									}
								}
							}
							if (!$bShowDeletedProps) {
								if (!empty($arResult["ALL_OFFER_PROPERTIES"])) {
									foreach ($arResult["ALL_OFFER_PROPERTIES"] as $propCode => $arProp) {
										if ($arProp["IS_DELETED"] != "N") {
											$bShowDeletedProps = true;
											break;
										}
									}
								}
							}
							?>
							<?if ($bShowDeletedProps):?>
								<div class="swipeignore compare_wr_inner">
									<div class="bx_filtren_container ">
										<ul>
											<?if (!empty($arResult["ALL_FIELDS"])) {
												foreach ($arResult["ALL_FIELDS"] as $propCode => $arProp) {
													if (!isset($arResult['FIELDS_REQUIRED'][$propCode])){?>
														<li class="btn btn-transparent <?=($arProp["IS_DELETED"] != "N" ? 'visible' : '');?> btn-xs">
															<span onclick="CatalogCompareObj.MakeAjaxAction('<?=CUtil::JSEscape($arProp["ACTION_LINK"]);?>')">+ <?=GetMessage("IBLOCK_FIELD_" . $propCode);?></span>
														</li>
													<?}
												}
											}
											if (!empty($arResult["ALL_OFFER_FIELDS"])) {
												foreach ($arResult["ALL_OFFER_FIELDS"] as $propCode => $arProp){?>
													<li class="btn btn-transparent <?=($arProp["IS_DELETED"] != "N" ? 'visible' : '');?> btn-xs">
														<span onclick="CatalogCompareObj.MakeAjaxAction('<?=CUtil::JSEscape($arProp["ACTION_LINK"]);?>')">+ <?=GetMessage("IBLOCK_FIELD_" . $propCode);?></span>
													</li>
												<?}
											}
											if (!empty($arResult["ALL_PROPERTIES"])) {
												foreach ($arResult["ALL_PROPERTIES"] as $propCode => $arProp){?>
													<li class="btn btn-transparent <?=($arProp["IS_DELETED"] != "N" ? 'visible' : '');?> btn-xs">
														<span onclick="CatalogCompareObj.MakeAjaxAction('<?=CUtil::JSEscape($arProp["ACTION_LINK"]);?>')">+ <?=$arProp["NAME"];?></span>
													</li>
												<?}
											}
											if (!empty($arResult["ALL_OFFER_PROPERTIES"])) {
												foreach ($arResult["ALL_OFFER_PROPERTIES"] as $propCode => $arProp){?>
													<li class="btn btn-transparent <?=($arProp["IS_DELETED"] != "N" ? 'visible' : '');?> btn-xs">
														<span onclick="CatalogCompareObj.MakeAjaxAction('<?=CUtil::JSEscape($arProp["ACTION_LINK"]);?>')">+ <?=$arProp["NAME"];?></span>
													</li>
												<?}
											}?>
										</ul>
									</div>
								</div>
							<?endif;?>
						<?endif;?>

						<?$arUnvisible = array("NAME", "PREVIEW_PICTURE", "DETAIL_PICTURE");?>

						<?//make conditions array
						?>
						<?$arShowFileds = $arShowOfferFileds = $arShowProps = $arShowOfferProps = array();?>

						<?
						if ($arResult["SHOW_FIELDS"]) {
							foreach ($arResult["SHOW_FIELDS"] as $code => $arProp) {
								if (!in_array($code, $arUnvisible)) {
									$showRow = true;
									if (!isset($arResult['FIELDS_REQUIRED'][$code]) || $arResult['DIFFERENT']) {
										$arCompare = array();
										foreach ($arSection["ITEMS"] as &$arElement) {
											$arPropertyValue = $arElement["FIELDS"][$code];
											if (is_array($arPropertyValue)) {
												sort($arPropertyValue);
												$arPropertyValue = implode(" , ", $arPropertyValue);
											}
											$arCompare[] = $arPropertyValue;
										}
										unset($arElement);
										$showRow = (count(array_unique($arCompare)) > 1);
									}
									if ($showRow)
										$arShowFileds[$code] = $arProp;
								}
							}
						}
						if ($arResult["SHOW_OFFER_FIELDS"]) {
							foreach ($arResult["SHOW_OFFER_FIELDS"] as $code => $arProp) {
								$showRow = true;
								if ($arResult['DIFFERENT']) {
									$arCompare = array();
									foreach ($arSection["ITEMS"] as &$arElement) {
										$Value = $arElement["OFFER_FIELDS"][$code];
										if (is_array($Value)) {
											sort($Value);
											$Value = implode(" , ", $Value);
										}
										$arCompare[] = $Value;
									}
									unset($arElement);
									$showRow = (count(array_unique($arCompare)) > 1);
								}
								if ($showRow)
									$arShowOfferFileds[$code] = $arProp;
							}
						}
						if ($arResult["SHOW_PROPERTIES"]) {
							foreach ($arResult["SHOW_PROPERTIES"] as $code => $arProperty) {
								$showRow = true;
								if ($arResult['DIFFERENT']) {
									$arCompare = array();
									foreach ($arSection["ITEMS"] as &$arElement) {
										$arPropertyValue = $arElement["DISPLAY_PROPERTIES"][$code]["VALUE"];
										if (is_array($arPropertyValue)) {
											sort($arPropertyValue);
											$arPropertyValue = implode(" , ", $arPropertyValue);
										}
										$arCompare[] = $arPropertyValue;
									}
									unset($arElement);
									$showRow = (count(array_unique($arCompare)) > 1);
								} else {
									$bNotEmptyProp = false;
									foreach ($arSection["ITEMS"] as &$arElement) {
										if ($arElement["DISPLAY_PROPERTIES"][$code]["VALUE"] !== NULL) {
											$bNotEmptyProp = true;
											break;
										}
									}
									unset($arElement);
									$showRow = $bNotEmptyProp;
								}
								if ($showRow)
									$arShowProps[$code] = $arProperty;
							}
						}
						if ($arResult["SHOW_OFFER_PROPERTIES"]) {
							foreach ($arResult["SHOW_OFFER_PROPERTIES"] as $code => $arProperty) {
								$showRow = true;
								if ($arResult['DIFFERENT']) {
									$arCompare = array();
									foreach ($arSection["ITEMS"] as &$arElement) {
										$arPropertyValue = $arElement["OFFER_DISPLAY_PROPERTIES"][$code]["VALUE"];
										if (is_array($arPropertyValue)) {
											sort($arPropertyValue);
											$arPropertyValue = implode(" , ", $arPropertyValue);
										}
										$arCompare[] = $arPropertyValue;
									}
									unset($arElement);
									$showRow = (count(array_unique($arCompare)) > 1);
								} else {
									$bNotEmptyProp = false;
									foreach ($arSection["ITEMS"] as &$arElement) {
										if ($arElement["OFFER_DISPLAY_PROPERTIES"][$code]["VALUE"] !== NULL) {
											$bNotEmptyProp = true;
											break;
										}
									}
									unset($arElement);
									$showRow = $bNotEmptyProp;
								}
								if ($showRow)
									$arShowOfferProps[$code] = $arProperty;
							}
						}
						?>

						<?if ($arShowFileds || $arShowOfferFileds || $arShowProps || $arShowOfferProps):?>
							<div class="catalog-compare__props-slider owl-carousel owl-theme" data-plugin-options='{"nav": false, "dots": false, "autoplay" : false, "autoplayTimeout" : "3000", "smartSpeed":500, "responsiveClass": true, "withSlide1": "catalog-compare__items", "rewind": true, "margin": -1, "responsive":{"0":{"items": 2},"768":{"items": 3},"992":{"items": 4},"1200":{"items": 5}}}'>
								<?foreach ($arSection["ITEMS"] as $arElement){?>
									<div class="catalog-compare__item-props" data-id="<?=$arElement["ID"];?>">
										<?if ($arShowFileds):?>
											<?foreach ($arShowFileds as $code => $arProp):?>
												<div class="catalog-compare__prop-line font_xs">
													<span class="catalog-compare__prop-name muted"><?=GetMessage("IBLOCK_FIELD_" . $code);?></span>
													<?if ($arResult["ALL_FIELDS"][$code]){?>
														<span onclick="CatalogCompareObj.MakeAjaxAction('<?=CUtil::JSEscape($arResult['ALL_FIELDS'][$code]['ACTION_LINK']);?>')" 
															  class="remove colored_theme_hover_text stroke-use-grey stroke-theme-use-svg-hover"
														>
															<i></i>
														</span>
													<?}?>
													<?=$arElement["FIELDS"][$code];?>
												</div>
											<?endforeach;?>
										<?endif;?>

										<?if ($arShowOfferFileds):?>
											<?foreach ($arShowOfferFileds as $code => $arProp):?>
												<div class="catalog-compare__prop-line font_xs">
													<span class="catalog-compare__prop-name muted"><?=GetMessage("IBLOCK_OFFER_FIELD_" . $code);?></span>
													<?if ($arResult["ALL_OFFER_FIELDS"][$code]){?>
														<span onclick="CatalogCompareObj.MakeAjaxAction('<?=CUtil::JSEscape($arResult['ALL_OFFER_FIELDS'][$code]['ACTION_LINK']);?>')" 
															  class="remove colored_theme_hover_text stroke-use-grey stroke-theme-use-svg-hover"
														>
															<i></i>
														</span>
													<?}?>
													<?=(is_array($arElement["OFFER_FIELDS"][$code]) ? implode(", ", $arElement["OFFER_FIELDS"][$code]) : $arElement["OFFER_FIELDS"][$code]);?>
												</div>
											<?endforeach;?>
										<?endif;?>

										<?if ($arShowProps):?>
											<?foreach ($arShowProps as $code => $arProp):?>
												<div class="catalog-compare__prop-line font_xs">
													<span class="catalog-compare__prop-name muted"><?=$arProp["NAME"];?></span>
													<?if ($arResult["ALL_PROPERTIES"][$code]):?>
														<span onclick="CatalogCompareObj.MakeAjaxAction('<?=CUtil::JSEscape($arResult['ALL_PROPERTIES'][$code]['ACTION_LINK']);?>')" 
															  class="remove colored_theme_hover_text stroke-use-grey stroke-theme-use-svg-hover"
														>
															<i></i>
														</span>
													<?endif;?>

													<?$currency = $arElement["PROPERTIES"]["PRICE_CURRENCY"]["VALUE"];?>
													<?if ($arElement["DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"]):?>
														<?=(is_array($arElement["DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"]) ? implode(", ", $arElement["DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"]) : str_replace("#CURRENCY#", $currency, $arElement["DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"]));?>
													<?else:?>
														<span class="muted">&mdash;</span>
													<?endif;?>
												</div>
											<?endforeach;?>
										<?endif;?>

										<?if ($arShowOfferProps):?>
											<?foreach ($arShowOfferProps as $code => $arProp):?>
												<div class="catalog-compare__prop-line font_xs">
													<span class="catalog-compare__prop-name muted"><?=$arProp["NAME"];?></span>
													<?if ($arResult["ALL_OFFER_PROPERTIES"][$code]):?>
														<span onclick="CatalogCompareObj.MakeAjaxAction('<?=CUtil::JSEscape($arResult['ALL_OFFER_PROPERTIES'][$code]['ACTION_LINK']);?>')" 
															  class="remove colored_theme_hover_text stroke-use-grey stroke-theme-use-svg-hover"
														>
															<i></i>
														</span>
													<?endif;?>
													<?=(is_array($arElement["OFFER_DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"]) ? implode(", ", $arElement["OFFER_DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"]) : $arElement["OFFER_DISPLAY_PROPERTIES"][$code]["DISPLAY_VALUE"]);?>
												</div>
											<?endforeach;?>
										<?endif;?>

										<div class="catalog-small__item bordered flexbox flexbox--row height-100">
											<span onclick="CatalogCompareObj.MakeAjaxAction('<?=CUtil::JSEscape($arElement['~DELETE_URL']);?>', 'Y');" 
												  class="catalog-small__remove remove colored_theme_hover_text stroke-use-grey stroke-theme-use-svg-hover" title="<?=GetMessage("CATALOG_REMOVE_PRODUCT");?>"
											>
												<i></i>
											</span>

											<div class="catalog-small__img-wrap">
												<a href="<?=$arElement['DETAIL_PAGE_URL'];?>">
													<img class="img-responsive" src="<?=$arElement['THUMB']['src'] ?? SITE_TEMPLATE_PATH . '/images/no_image_small.png';?>" alt="" title="">
												</a>
											</div>

											<div class="catalog-small__name">
												<?
												$name = $arElement["OFFER_FIELDS"]["NAME"] ?? $arElement["NAME"];
												// if ($arParams['SKU_DETAIL_ID'] && isset($arElement["OFFER_FIELDS"]["ID"])) {
												// 	$arElement["DETAIL_PAGE_URL"] .= '?oid=' . $arElement["OFFER_FIELDS"]["ID"];
												// }
												?>
												<a href="<?=$arElement["DETAIL_PAGE_URL"];?>" class="dark_link switcher-title font_xs"><span class="lineclamp-3"><?=$name;?></span></a>
											</div>
										</div>
									</div>
								<?}
								unset($arElement);?>
							</div>
						<?endif;?>
					</div>
				<?endforeach;?>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		(function() {
			$(document).ready(function() {
				InitOwlSlider();

				var allSection = $('.compare-sections__item');
				allSection.each(function(index) {
					var section = $(this);
					var $sliderProducts = section.find('.catalog-compare__items'),
						$sliderProps = section.find('.catalog-compare__props-slider'),
						$propsLines = $sliderProps.find(".catalog-compare__prop-line"),
						$sliderProductsItems = $sliderProducts.find(".owl-item"),
						$sliderPropsItems = $sliderProps.find(".owl-item");

					//change products slider
					$sliderProducts.on('change.owl.carousel', function(event) {
						if (event.namespace && event.property.name === 'position') {
							var target = event.relatedTarget.relative(event.property.value, true);

							$sliderProductsItems.removeClass("sync");
							$sliderProductsItems.eq(target).addClass("sync");

							if (target != $sliderProps.find(".owl-item.sync").index())
								$sliderProps.owlCarousel('to', target, 500, true);
						}
					});

					//change props slider
					$sliderProps.on('change.owl.carousel', function(event) {
						if (event.namespace && event.property.name === 'position') {
							var target = event.relatedTarget.relative(event.property.value, true);

							//show props title
							$sliderPropsItems.removeClass("active-title sync");
							$sliderProps.find(".owl-item:nth-child(" + (target + 1) + ")").addClass("active-title");
							$sliderPropsItems.eq(target).addClass("sync");

							if ($sliderProducts.find(".owl-item.sync").index() != target)
								$sliderProducts.owlCarousel('to', target, 500, true);
						}
					});


					tableEqualHeight($sliderProps, $sliderPropsItems);
					$(window).on('resize', function() {
						tableEqualHeight($sliderProps, $sliderPropsItems);
					});

					$propsLines.hover(
						function() {
							var owlItemsActive = $sliderProps.find(".owl-item.active"),
								index = $(this).index();

							$sliderPropsItems.each(function(i, element) {
								$(this).find(".catalog-compare__prop-line").eq(index).addClass("hover-prop");
							});

							owlItemsActive.each(function(i, element) {
								// set border-left
								if (i === 0) {
									$(this).find(".catalog-compare__prop-line").eq(index).addClass("border-left");
								}
								// set border-right
								if (i === owlItemsActive.length - 1) {
									$(this).find(".catalog-compare__prop-line").eq(index).addClass("border-right");
								}
							});
						},
						function() {
							$propsLines.removeClass("hover-prop border-left border-right");
						}
					);
				});

				stickyCompareItems();
				setBasketStatusBtn();

				if ($(".catalog-compare[data-delcookie]").length) {
					$.removeCookie("compare_section");
				}
			})
		})()
	</script>
	<?
	if ($isAjax) {
		die();
	}
	?>
</div>
<script type="text/javascript">
	var CatalogCompareObj = new BX.Iblock.Catalog.CompareClass("bx_catalog_compare_block");
</script>