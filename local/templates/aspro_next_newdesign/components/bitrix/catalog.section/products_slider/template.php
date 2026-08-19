<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<?$this->setFrameMode(true);?>


<style>
/* Скрываем список складов, пока внутри него нет триггера (т.е. до выполнения JS) */
.product-item-stores .stores-list:not(:has(.stores-trigger)) {
    display: none;
}

.catalog_item.has-stores-block {
    padding-top: 35px;
}

/* Скрываем стандартный блок остатков */
.catalog_item .item-stock {
    display: none !important;
}

/* Контейнер блока складов – привязан к левому верхнему углу карточки */
.product-item-stores {
    position: absolute;
    top: 4px;
    left: 0;
    z-index: 10;
    font-size: 9px;
    margin: 0;
}

/* Общие стили для триггера (без фона и цвета текста – цвета задаются дополнительными классами) */
.stores-trigger {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    font-weight: 500;
    font-size: 12px;
    padding: 0px 4px;
    border-radius: 0 4px 4px 0;
    transition: background 0.2s;
    white-space: nowrap;
}

/* Зелёный – В НАЛИЧИИ */
.stores-trigger-green {
    background: #ebfaef;
    color: #2ca94c;
}
.stores-trigger-green:hover {
    background: #d4f0dc;
}

/* Серый – ПОД ЗАКАЗ */
.stores-trigger-gray {
    background: #eeeef0;
    color: #999;
}
.stores-trigger-gray:hover {
    background: #e2e2e6;
}
.stores-trigger-orange {
    background: #fff5e6;
    color: #ff9500;
}
.stores-trigger-orange:hover {
    background: #ffe8cc;
}

/* Стрелочка */
.stores-arrow {
    font-size: 8px;
    transition: transform 0.2s;
}

/* Выпадающий список */
.stores-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 20;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 10px 10px;
    margin-top: 4px;
    min-width: 125px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    gap: 6px;
}

/* Бейджи складов */
.store-badge {
    display: block;
    padding: 0px 4px;
    border-radius: 4px;
    font-size: 9px;
    white-space: nowrap;
}

.store-badge-green {
    background: #ebfaef;
    color: #2ca94c;
}

.store-badge-orange {
    background: #fff5e6;
    color: #ff9500;
}

.store-badge-gray {
    background: #eeeef0;
    color: #999;
}

/* Сообщение "Нет данных о складах" */
.stores-empty {
    color: #999;
    font-style: italic;
    font-size: 9px;
}

</style>
<?
global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');?>
	<?	
$sliderID  = "specials_slider_wrapp_".$this->randString();
$notifyOption = COption::GetOptionString("sale", "subscribe_prod", "");
$arNotify = unserialize($notifyOption, ['allowed_classes' => false]);
$data = [];

array_map(function($arr) use(&$data){
	$data[$arr['IBLOCK_SECTION_ID']][] = $arr;
}, $arResult["ITEMS"]);
if (isset($_GET['test'])){
	var_dump(count($arResult["ITEMS"]));
	exit;
}
$ids = implode(', ', array_filter(array_map('intval', array_keys($data))));

global $DB;

$sections = [];

$entitiesExist = !empty($ids);
if ($entitiesExist) {
      $result = $DB->query("SELECT id, name, code FROM b_iblock_section WHERE id IN($ids) ORDER BY sort");
  if($result)
while($row = $result->fetch())
$sections[$row['id']] = $row;
} else {
   
}



/*$result = $DB->query("SELECT id, name, code FROM b_iblock_section WHERE id IN($ids) ORDER BY sort");
if($result)
while($row = $result->fetch())
$sections[$row['id']] = $row;*/

?>

	
	<?if( count( $arResult["ITEMS"] ) >= 1 ){?>
	<?if($arParams["AJAX_REQUEST"]=="N" ){?>
			
		
		
		<div class="top_wrapper row margin0 <?=($arParams["SHOW_UNABLE_SKU_PROPS"] != "N" ? "show_un_props" : "unshow_un_props");?>">
		<div id="portfolio_loader 111 333" >



				
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
			$col = 5;?>


			
<?foreach($sections as $id => $section): ?>
			<h2 class="jumptarget" id="<?=$section['code']?>"><?=$section['name']?></h2>
					
					
					<?
	
	$get_fields = CIBlockSection::GetList(
		array(),
		array(
			'IBLOCK_ID' => 19,
			'ID' => $section['id']
		),
		false,
		array(
			'UF_BUTTON_OT'
		)
	);
	
	if($get_fields_item = $get_fields->GetNext()) { 

		$my_fields_1 = $get_fields_item['UF_BUTTON_OT'];
		
	}
						

?>
	
	

<div class="catalog_block items block_list">

	<?foreach($data[$id] as $key => $arItem){?>
			<?$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_EDIT"));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')));

					$arItem["strMainID"] = $this->GetEditAreaId($arItem['ID']);
					$arItemIDs=CNext::GetItemsIDs($arItem);
$renderImg = CFile::ResizeImageGet($arItem['PREVIEW_PICTURE'], Array("width" => 100, "height" => 100), BX_RESIZE_IMAGE_PROPORTIONAL, false);
					$totalCount = CNext::GetTotalCount($arItem, $arParams);
								

					//$arQuantityData = CNext::GetQuantityArray($totalCount, $arItemIDs["ALL_ITEM_IDS"]);

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

					}
					$elementName = ((isset($arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) && $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) ? $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] : $arItem['NAME']);
	
					//Вывод артикула элемента
					$isArticle=$arItem["PROPERTIES"]["CML2_ARTICLE"]["VALUE"];

					?>
					
				<div  class="item_block col-<?=$col;?> col-md-<?=ceil(12/$col);?> col-sm-<?=ceil(12/round($col / 2))?> col-xs-6 ">
				<div class="catalog_item_wrapp item" itemscope itemtype="https://schema.org/Product" >
				<meta itemprop="name" content="<?=$arItem['NAME']?>" />
		<meta itemprop="category" content="<?=$arItem['CATEGORY_PATH']?>" />
		<meta itemprop="description" content="<?=(strlen(strip_tags($arItem['PREVIEW_TEXT'])) ? strip_tags($arItem['PREVIEW_TEXT']) : (strlen(strip_tags($arItem['DETAIL_TEXT'])) ? strip_tags($arItem['DETAIL_TEXT']) : $name))?>" />
		<meta itemprop="sku" content="<?=$arItem['ID'];?>" />
		<link itemprop="url" href="<?=$arItem["DETAIL_PAGE_URL"]?>" />
			
		<link itemprop="image" href="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" />
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
							<?
						}?>
					</div>
				
					


					<div  class="catalog_item main_item_wrapper item_wrap <?=(($_GET['q'])) ? 's' : ''?> <?=(($my_fields_1==1) ? 'add_ot' : '')?>" id="<?=$arItemIDs["strMainID"];?>">
						<div class="inner_wrap">
						
<?/*остатки на складах*/?>
		<div class="product-item-stores" 
     data-product-id="<?=$arItem['ID']?>"
     data-current-offer="<?=$arItem['STORES_DATA'] ? $arItem['CURRENT_OFFER_ID'] ?? $arItem['ID'] : ''?>"
     data-offers-stores='<?=htmlspecialchars(json_encode($arItem['OFFERS_STORES_DATA'] ?? []), ENT_QUOTES)?>'>
   
    <div class="stores-list" id="stores-list-<?=$arItem['ID']?>">
        <?php if (!empty($arItem['STORES_DATA'])): ?>
            <?php foreach ($arItem['STORES_DATA'] as $store): 
                $amount = (int)$store['AMOUNT'];
                $class = $amount >= 30 ? 'green' : ($amount > 0 ? 'orange' : 'gray');
                $text = $amount > 0 ? $amount . ' ' . GetMessage("PIECES") : GetMessage("ON_ORDER");
            ?>
                <span class="store-badge store-badge-<?=$class?>">
                    <?=htmlspecialcharsbx($store['NAME'])?>: <?=$text?>
                </span>
            <?php endforeach; ?>
        <?php else: ?>
            <span class="stores-empty"><?=GetMessage("NO_STORES")?></span>
        <?php endif; ?>
    </div>
</div>

<?/*остатки на складах*/?>
						
							<div class="image_wrapper_block">
		
								<div class="stickers">
			<?$prop = ($arParams["STIKERS_PROP"] ? $arParams["STIKERS_PROP"] : "HIT");?>
			<?foreach(CNext::GetItemStickers($arItem["PROPERTIES"][$prop]) as $arSticker):?>
				<div><div class="<?=$arSticker['CLASS']?>"><?=$arSticker['VALUE']?></div></div>
			<?endforeach;?>
			
			
		
				<?if($arParams["SALE_STIKER"] && $arItem["PROPERTIES"][$arParams["SALE_STIKER"]]["VALUE"]):?>
							
<div>							<?foreach($arItem["PROPERTIES"][$arParams["SALE_STIKER"]]["VALUE"] as $val):?>
								<div class="sticker_sale_text"><?=$val;?></div>

								<?endforeach;?>
								</div>
								<?endif;?>
			
		</div>
							
							
							

								<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="thumb shine" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['PICT']; ?>">
									<?
									$a_alt = ($arItem["PREVIEW_PICTURE"] && strlen($arItem["PREVIEW_PICTURE"]['DESCRIPTION']) ? $arItem["PREVIEW_PICTURE"]['DESCRIPTION'] : ($arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_ALT"] ? $arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_ALT"] : $arItem["NAME"] ));
									$a_title = ($arItem["PREVIEW_PICTURE"] && strlen($arItem["PREVIEW_PICTURE"]['DESCRIPTION']) ? $arItem["PREVIEW_PICTURE"]['DESCRIPTION'] : ($arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_TITLE"] ? $arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_TITLE"] : $arItem["NAME"] ));
									?>
									<?if( !empty($arItem["PREVIEW_PICTURE"]) ):?>
											
											<? if ($arItem['PROPERTIES']['SET']['VALUE']): ?>
							    	<div class="<?=$arItem['PROPERTIES']['SET']['VALUE_XML_ID']?>"></div>
								    
					<?endif;?>
										<?/* Раньше в data-original уходил оригинал (обычно 500×500 по
										     150–200 КБ) при плитке 231×231. Отдаём ресайз 348×348 с качеством 82:
										     в настройках модуля стоит 100 (Ирина, 19 августа 2026). */?>
										<?
										$ndPicId = (int) $arItem["PREVIEW_PICTURE"]["ID"];
										$ndPicSrc = $arItem["PREVIEW_PICTURE"]["SRC"];
										if ($ndPicId > 0) {
											$ndPic = CFile::ResizeImageGet($ndPicId, array("width" => 348, "height" => 348), BX_RESIZE_IMAGE_PROPORTIONAL, true, false, false, 82);
											if (!empty($ndPic["src"])) $ndPicSrc = $ndPic["src"];
										}
										?>
										<img class="lazy img-responsive" src="/assets/lazyload/loading.gif" data-original="<?=$ndPicSrc?>"  alt="<?=$a_alt;?>" title="<?=$a_title;?>" />								
										
									<?elseif( !empty($arItem["DETAIL_PICTURE"])):?>
										<?$img = CFile::ResizeImageGet($arItem["DETAIL_PICTURE"], array( "width" => 170, "height" => 170 ), BX_RESIZE_IMAGE_PROPORTIONAL,true );?>
										<img  class="lazy img-responsive" src="/assets/lazyload/loading.gif" data-original="<?=$img["src"]?>" alt="<?=$a_alt;?>" title="<?=$a_title;?>" />
									<?else:?>
										<img class="lazy img-responsive" src="/assets/lazyload/loading.gif" data-original="<?=SITE_TEMPLATE_PATH?>/images/no_photo_medium.png" alt="<?=$a_alt;?>" title="<?=$a_title;?>" />
									<?endif;?>
									<?if($fast_view_text_tmp = CNext::GetFrontParametrValue('EXPRESSION_FOR_FAST_VIEW'))
										$fast_view_text = $fast_view_text_tmp;
									else
										$fast_view_text = GetMessage('FAST_VIEW');?>
								</a>
														
	   <?if($arItem['HAS_VIDEO']){?>
                          <a data-fancybox href="" class="colproduct_video">Смотрите видео</a>
                       
                                <?}?>
                             
</div>
							
							
							
							
							
							<div class="item_info <?=$arParams["TYPE_SKU"]?>">
							
							
							
							
								<div class="item-title">
									<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="dark_link"><span><?=$elementName;?><br></span></a>
									
								</div>
					
					<div class="list__catalog_sku" data-product-id="<?=$arItem['ID']?>" style="min-height: 30px;"></div>
							
							
							
										<?if($arItem['OFFERS']):?>
										
										
										<?/*schema.org*/?>	
			<span itemprop="offers" itemscope itemtype="http://schema.org/AggregateOffer" style="display:none;">
			<meta itemprop="offerCount" content="<?=count($arItem['OFFERS'])?>" />
			<meta itemprop="lowPrice" content="<?=$arItem["PROPERTIES"]['MINIMUM_PRICE']['VALUE']?>" />
			<meta itemprop="price" content="<?=$arItem["PROPERTIES"]['MINIMUM_PRICE']['VALUE']?>" />
			<meta itemprop="priceCurrency" content="RUB" />
			<?foreach($arItem['OFFERS'] as $arOffer):?>
				<?$currentOffersList = array();?>
				<?foreach($arOffer['TREE'] as $propName => $skuId):?>
					<?$propId = (int)substr($propName, 5);?>
					<?foreach($arResult['SKU_PROPS'] as $prop):?>
						<?if($prop['ID'] == $propId):?>
							<?foreach($prop['VALUES'] as $propId => $propValue):?>
								<?if($propId == $skuId):?>
									<?$currentOffersList[] = $propValue['NAME'];?>
									<?break;?>
								<?endif;?>
							<?endforeach;?>
						<?endif;?>
					<?endforeach;?>
				<?endforeach;?>
				<span itemprop="offers" itemscope itemtype="http://schema.org/Offer">
					<meta itemprop="sku" content="<?=implode('/', $currentOffersList)?>" />
					<meta itemprop="name" content="<?=$arOffer["NAME"] ?>" />
					<link itemprop="url" href="<?=$arOffer['DETAIL_PAGE_URL']?>?pid=<?=$arOffer['ID']?>" />
					<meta itemprop="lowPrice" content="<?=($arOffer['MIN_PRICE']['DISCOUNT_VALUE']) ? $arOffer['MIN_PRICE']['DISCOUNT_VALUE'] : $arOffer['MIN_PRICE']['VALUE']?>" />
					<meta itemprop="price" content="<?=($arOffer['MIN_PRICE']['DISCOUNT_VALUE']) ? $arOffer['MIN_PRICE']['DISCOUNT_VALUE'] : $arOffer['MIN_PRICE']['VALUE']?>" />
					<meta itemprop="priceCurrency" content="<?=$arOffer['MIN_PRICE']['CURRENCY']?>" />
					<link itemprop="availability" href="http://schema.org/<?=($arOffer['CAN_BUY'] ? 'InStock' : 'OutOfStock')?>" />
					<?
					if($arDiscount["ACTIVE_TO"]){?>
						<meta itemprop="priceValidUntil" content="<?=date("Y-m-d", MakeTimeStamp($arDiscount["ACTIVE_TO"]))?>" />
					<?}?>

				</span>
			<?endforeach;?>
		</span>
					<?/*schema.org*/?>		
										
										
										
										
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
								
								
								
								<?else:?>	
				<span itemprop="offers" itemscope itemtype="http://schema.org/Offer">
				<meta itemprop="price" content="<?=($arItem['MIN_PRICE']['DISCOUNT_VALUE'] ? $arItem['MIN_PRICE']['DISCOUNT_VALUE'] : $arItem['MIN_PRICE']['VALUE'])?>" />
				<meta itemprop="priceCurrency" content="<?=$arItem['MIN_PRICE']['CURRENCY']?>" />
				<link itemprop="availability" href="http://schema.org/<?=($arItem['MIN_PRICE']['CAN_BUY'] ? 'InStock' : 'OutOfStock')?>" />
				<?
				if($arDiscount["ACTIVE_TO"]){?>
					<meta itemprop="priceValidUntil" content="<?=date("Y-m-d", MakeTimeStamp($arDiscount["ACTIVE_TO"]))?>" />
				<?}?>
				<link itemprop="url" href="<?=$arItem["DETAIL_PAGE_URL"]?>" />
			</span>


			
							<?endif;?>
				
				<?if($arItem["OFFERS"]):?>
								<?if(!empty($arItem['OFFERS_PROP'])):?>
	
	<div class="article_block item-article" style="color: #d0d0d0;font-size: 8px;position: absolute;top: 0;right: 5px;" >
										<?if(isset($arItem['ARTICLE']) && $arItem['ARTICLE']['VALUE']){?>
											<?=$arItem['ARTICLE']['NAME'];?>: <?=$arItem['ARTICLE']['VALUE'];?>
										<?}?>
									</div>
	
			<?endif;?>
			<?else:?>
			<?if ($isArticle): ?><div style="color: #d0d0d0;font-size: 8px;position: absolute;top: 0;right: 5px;">Артикул: <?=$isArticle?>	</div><?endif;?>
			<?endif;?>
								

								<div class="sa_block">
									<?=$arQuantityData["HTML"];?>
							
								</div>
										<div class="cost prices clearfix">
								
									<?if( $arItem["OFFERS"])
									{?>
									
																
										<div class="with_matrix <?=($arParams["SHOW_OLD_PRICE"]=="Y" ? 'with_old' : '');?>" style="display:none;">
											<div class="price price_value_block"><span class="values_wrapper"></span></div>
											<?if($arParams["SHOW_OLD_PRICE"]=="Y"):?>
												<div class="price discount"></div>
											<?endif;?>
												<?if($arParams["SHOW_DISCOUNT_PERCENT"]=="Y"){?>
												<div class="111 5 sale_block matrix" style="display:none;">
													<div class="sale_wrapper">
													
													<div class="text"> <span class="title"><?=GetMessage("CATALOG_ECONOMY");?></span><span class="values_wrapper"></span></div>
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
											<div class="view_sale_block <?=($arQuantityData["HTML"] ? '' : 'wq');?>">
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
																<span class="value" <?=((count( $arItem["OFFERS"] ) > 0 && $arParams["TYPE_SKU"] == 'TYPE_1' && $arItem["OFFERS_PROP"]) ? 'style="opacity:1;"' : '')?>><?=$totalCount;?></span>
																<span class="text"><?=GetMessage("TITLE_QUANTITY");?></span>
															</span>
														</div>
													</div>
												<?endif;?>
											</div>
											
											
											
											
											
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
										<div class="view_sale_block" >
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
									<?}else{?>
                                        <?$arOnlyItemJSParams = array(
                                            "ITEM_PRICES" => $arItem["ITEM_PRICES"],
                                            "ITEM_PRICE_MODE" => $arItem["ITEM_PRICE_MODE"],
//                                            "ITEM_QUANTITY_RANGES" => $arItem["ITEM_QUANTITY_RANGES"],
                                            "MIN_QUANTITY_BUY" => $arAddToBasketData["MIN_QUANTITY_BUY"],
                                            "ID" => $arItemIDs["strMainID"],
                                        )?>
                                            <script type="text/javascript">
                                                var <? echo $arItemIDs["strObName"]; ?>el = new JCCatalogSectionOnlyElement(<? echo CUtil::PhpToJSObject($arOnlyItemJSParams, false, true); ?>);
                                            </script>
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
                                                <div class="button_block button_list wide">
                                                    <?=$buttonHTML?>
                                                </div>
                                            </div>
										</div>
									<?}?>
								<?endif;?>
								
								
						
							<?/*<div class="footer_button">
							
							</div>*/?>
						</div>
						
												
					</div>
			
			</div></div>
        <?if(isset($arItem['OFFERS']) && count($arItem['OFFERS'])>0){
            $offer_unit_yes = false;
            foreach ($arItem['OFFERS'] as $key=>$offer_u){
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
                    //if(array_key_exists($key_sku, $arResult['SKU_PROPS']) && !empty($sku_property['VALUE'])){
// Стало:
if(isset($arResult['SKU_PROPS']) && is_array($arResult['SKU_PROPS']) && array_key_exists($key_sku, $arResult['SKU_PROPS']) && !empty($sku_property['VALUE'])){
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
                        "CACHE_TIME" => $arParams["CACHE_TIME"],
                        "CACHE_TYPE" => "N",
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
                    "CACHE_TIME" => $arParams["CACHE_TIME"],
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

		</div>

		<? endforeach; ?>	
	

	<?if(($arParams["AJAX_REQUEST"]=="N") || !isset($arParams["AJAX_REQUEST"])){?>
			</div>
		</div>
	<?}?>
	<?if($arParams["AJAX_REQUEST"]=="Y"){?>
		<div class="wrap_nav">
	<?}?>
	<div class="bottom_nav <?=$arParams["DISPLAY_TYPE"];?>" <?=($arParams["AJAX_REQUEST"]=="Y" ? "style='display: none; '" : "");?>>
		<?if( $arParams["DISPLAY_BOTTOM_PAGER"] == "Y" ){?><?=$arResult["NAV_STRING"]?><?}?>
	</div>
	<?if($arParams["AJAX_REQUEST"]=="Y"){?>
		</div>
	<?}?>
<?}else{?>
	<script>
		// $(document).ready(function(){
			$('.sort_header').animate({'opacity':'1'}, 500);
		// })
	</script>
	<div class="no_goods catalog_block_view">
		<div class="no_products">
			<div class="wrap_text_empty">
				<?if($_REQUEST["set_filter"]){?>
					<?$APPLICATION->IncludeFile(SITE_DIR."include/section_no_products_filter.php", Array(), Array("MODE" => "html",  "NAME" => GetMessage('EMPTY_CATALOG_DESCR')));?>
				<?}else{?>
					<?$APPLICATION->IncludeFile(SITE_DIR."include/section_no_products.php", Array(), Array("MODE" => "html",  "NAME" => GetMessage('EMPTY_CATALOG_DESCR')));?>
				<?}?>
			</div>
		</div>
		<?if($_REQUEST["set_filter"]){?>
			<span class="button wide"><?=GetMessage('RESET_FILTERS');?></span>
		<?}?>
	</div>
<?}?>


	
<script>
$(document).ready(function() {
$( ".catalog_block .add_ot .cost.prices .price" ).prepend( "от " );
							});
</script>
	
<script>
	BX.message({
		QUANTITY_AVAILIABLE: '<? echo COption::GetOptionString("aspro.next", "EXPRESSION_FOR_EXISTS", GetMessage("EXPRESSION_FOR_EXISTS_DEFAULT"), SITE_ID); ?>',
		QUANTITY_NOT_AVAILIABLE: '<? echo COption::GetOptionString("aspro.next", "EXPRESSION_FOR_NOTEXISTS", GetMessage("EXPRESSION_FOR_NOTEXISTS"), SITE_ID); ?>',
		ADD_ERROR_BASKET: '<? echo GetMessage("ADD_ERROR_BASKET"); ?>',
		ADD_ERROR_COMPARE: '<? echo GetMessage("ADD_ERROR_COMPARE"); ?>',
	})
</script>


<?// Кружки цветов грузит js/newdesign-sku-colors.js — одной пачкой на всю
   // страницу. Здесь был свой $.ajax на каждую плитку: 34 запроса с главной,
   // по 1,3 с каждый (Ирина, 19 августа 2026).?>

<?//Обновление остатков?>

<script>
window.STORE_MESSAGES = {
    PIECES: '<?=GetMessageJS("PIECES")?>',
    ON_ORDER: '<?=GetMessageJS("ON_ORDER")?>',
    NO_STORES: '<?=GetMessageJS("NO_STORES")?>'
};
</script>

<script>

BX.ready(function() {
    // Функция обновления остатков
    function updateStores(productId, offerId) {
        var storesBlock = document.querySelector('.product-item-stores[data-product-id="' + productId + '"]');
        if (!storesBlock) return;
        
        var offersStoresJson = storesBlock.getAttribute('data-offers-stores');
        if (!offersStoresJson) return;
        
        try {
            var offersStores = JSON.parse(offersStoresJson);
            var storesData = offersStores[offerId];
            if (!storesData) return;
            
            var container = document.getElementById('stores-list-' + productId);
            if (!container) return;
            
            var html = '';
            for (var i = 0; i < storesData.length; i++) {
                var store = storesData[i];
                var amount = parseInt(store.AMOUNT);
                var className = amount >= 30 ? 'green' : (amount > 0 ? 'orange' : 'gray');
                var amountText = amount > 0 ? amount + ' ' + (window.STORE_MESSAGES ? window.STORE_MESSAGES.PIECES : 'шт.') : (window.STORE_MESSAGES ? window.STORE_MESSAGES.ON_ORDER : 'под заказ');
                html += '<span class="store-badge store-badge-' + className + '">' + 
                        BX.util.htmlspecialchars(store.NAME) + ': ' + amountText + 
                        '</span>';
            }
            container.innerHTML = html;
        } catch(e) {
            console.error('Error updating stores:', e);
        }
    }
    
    // Перехватываем клики по элементам выбора SKU (длин)
    // В Аспро обычно используются такие селекторы - адаптируйте под свою вёрстку
    var skuSelectors = [
        '[data-sku-id]',
        '[data-offer-id]',
        '.sku-item',
        '.offer-item',
        '.catalog-sku-item',
        '.product-sku-item',
        '.aspro-sku-item',
        '.sku-item-link',
        '.item-sku'
    ];
    
    document.addEventListener('click', function(e) {
        // Ищем элемент SKU по одному из селекторов
        var skuElement = null;
        for (var i = 0; i < skuSelectors.length; i++) {
            skuElement = e.target.closest(skuSelectors[i]);
            if (skuElement) break;
        }
        if (!skuElement) return;
        
        // Извлекаем ID предложения
        var offerId = skuElement.dataset.skuId || skuElement.dataset.offerId || skuElement.dataset.id || skuElement.getAttribute('data-value');
        if (!offerId) return;
        
        // Находим карточку товара (родительский блок)
        var productItem = skuElement.closest('.product-item, .catalog-section-item, .item-block, .catalog-item, .product-card');
        if (!productItem) return;
        
        var storesBlock = productItem.querySelector('.product-item-stores');
        if (!storesBlock) return;
        
        var productId = storesBlock.dataset.productId;
        if (productId && offerId) {
            updateStores(productId, offerId);
        }
    });
});

</script>
<?//Обновление остатков?>