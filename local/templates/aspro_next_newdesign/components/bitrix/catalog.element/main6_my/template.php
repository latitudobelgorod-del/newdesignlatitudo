<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>
<?
global $arTheme;
global $APPLICATION;
?>
<?global $arRegion;
	$regionID = ($arRegion ? $arRegion['ID'] : '');?>
	
<?if($arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"])
{
$goy=$arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"];}
else {
$goy=$arResult['NAME'];
}
?>
<?if ($_SERVER['REQUEST_URI'] !== $arResult['DETAIL_PAGE_URL']):?>
 <?$APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
<?endif;?>
<h1 id="pagetitle"><?=$goy?></h1>
<div class="basket_props_block" id="bx_basket_div_<?=$arResult["ID"];?>" style="display: none;">
	<?if (!empty($arResult['PRODUCT_PROPERTIES_FILL'])){
		foreach ($arResult['PRODUCT_PROPERTIES_FILL'] as $propID => $propInfo){?>
			<input type="hidden" name="<? echo $arParams['PRODUCT_PROPS_VARIABLE']; ?>[<? echo $propID; ?>]" value="<? echo htmlspecialcharsbx($propInfo['ID']); ?>">
			<?if (isset($arResult['PRODUCT_PROPERTIES'][$propID]))
				unset($arResult['PRODUCT_PROPERTIES'][$propID]);
		}
	}
	$arResult["EMPTY_PROPS_JS"]="Y";
	$emptyProductProperties = empty($arResult['PRODUCT_PROPERTIES']);
	if (!$emptyProductProperties){
		$arResult["EMPTY_PROPS_JS"]="N";?>
		<div class="wrapper">
			<table>
				<?foreach ($arResult['PRODUCT_PROPERTIES'] as $propID => $propInfo){?>
					<tr>
						<td><? echo $arResult['PROPERTIES'][$propID]['NAME']; ?></td>
						<td>
							<?if('L' == $arResult['PROPERTIES'][$propID]['PROPERTY_TYPE'] && 'C' == $arResult['PROPERTIES'][$propID]['LIST_TYPE']){
								foreach($propInfo['VALUES'] as $valueID => $value){?>
									<label>
										<input type="radio" name="<? echo $arParams['PRODUCT_PROPS_VARIABLE']; ?>[<? echo $propID; ?>]" <? echo ($valueID == $propInfo['SELECTED'] ? '"checked"' : ''); ?> value="<? echo $valueID; ?>" ><? echo $value; ?>
									</label>
								<?}
							}else{?>
								<select name="<? echo $arParams['PRODUCT_PROPS_VARIABLE']; ?>[<? echo $propID; ?>]">
									<?foreach($propInfo['VALUES'] as $valueID => $value){?>
										<option <? echo ($valueID == $propInfo['SELECTED'] ? '"selected"' : ''); ?> value="<? echo $valueID; ?>" ><? echo $value; ?></option>
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

<?
$this->setFrameMode(true);
$currencyList = '';
if (!empty($arResult['CURRENCIES'])){
	$templateLibrary[] = 'currency';
	$currencyList = CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true);
}
$templateData = array(
	'TEMPLATE_LIBRARY' => $templateLibrary,
	'CURRENCIES' => $currencyList,
	'BRAND_ITEM' => $arResult['BRAND_ITEM'],
	'ASSOCIATED' => $arResult['PROPERTIES']['ASSOCIATED']['VALUE'],
	'EXPANDABLES' => $arResult['PROPERTIES']['EXPANDABLES']['VALUE'],
	'SERVICES' => $arResult['PROPERTIES']['SERVICES']['VALUE'],
	'PROJECTS' => $arResult['PROPERTIES']['LINK_PORTFOLIO']['VALUE'],
	'STORES' => array(
	"USE_STORE_PHONE" => $arParams["USE_STORE_PHONE"],
	"SCHEDULE" => $arParams["SCHEDULE"],
	"USE_MIN_AMOUNT" => $arParams["USE_MIN_AMOUNT"],
	"MIN_AMOUNT" => $arParams["MIN_AMOUNT"],
	"ELEMENT_ID" => $arResult["ID"],
	"STORE_PATH"  =>  $arParams["STORE_PATH"],
	"MAIN_TITLE"  =>  $arParams["MAIN_TITLE"],
	"MAX_AMOUNT"=>$arParams["MAX_AMOUNT"],
	"USE_ONLY_MAX_AMOUNT" => $arParams["USE_ONLY_MAX_AMOUNT"],
	"SHOW_EMPTY_STORE" => $arParams['SHOW_EMPTY_STORE'],
	"SHOW_GENERAL_STORE_INFORMATION" => $arParams['SHOW_GENERAL_STORE_INFORMATION'],
	"USE_ONLY_MAX_AMOUNT" => $arParams["USE_ONLY_MAX_AMOUNT"],
	"USER_FIELDS" => $arParams['USER_FIELDS'],
	"FIELDS" => $arParams['FIELDS'],
	"STORES_FILTER_ORDER" => $arParams['STORES_FILTER_ORDER'],
	"STORES_FILTER" => $arParams['STORES_FILTER'],
	"STORES" => $arParams['STORES'] = array_diff($arParams['STORES'], array('')),
	)
);
unset($currencyList, $templateLibrary);
$arSkuTemplate = array();
if (!empty($arResult['SKU_PROPS'])){
	$arSkuTemplate=CNext::GetSKUPropsArray($arResult['SKU_PROPS'], $arResult["SKU_IBLOCK_ID"], "list", $arParams["OFFER_HIDE_NAME_PROPS"]);
}
$strMainID = $this->GetEditAreaId($arResult['ID']);
$strObName = 'ob'.preg_replace("/[^a-zA-Z0-9_]/", "x", $strMainID);
$arResult["strMainID"] = $this->GetEditAreaId($arResult['ID']);
$arItemIDs=CNext::GetItemsIDs($arResult, "Y");
$totalCount = CNext::GetTotalCount($arResult, $arParams);
$arQuantityData = CNext::GetQuantityArray($totalCount, $arItemIDs["ALL_ITEM_IDS"], "Y");
$arParams["BASKET_ITEMS"]=($arParams["BASKET_ITEMS"] ? $arParams["BASKET_ITEMS"] : array());
$useStores = $arParams["USE_STORE"] == "Y" && $arResult["STORES_COUNT"] && $arQuantityData["RIGHTS"]["SHOW_QUANTITY"];
$showCustomOffer=(($arResult['OFFERS'] && $arParams["TYPE_SKU"] !="N") ? true : false);
if($showCustomOffer){
	$templateData['JS_OBJ'] = $strObName;
}
$strMeasure='';
$arAddToBasketData = array();
if($arResult["OFFERS"]){
	$strMeasure=$arResult["MIN_PRICE"]["CATALOG_MEASURE_NAME"];
	$templateData["STORES"]["OFFERS"]="Y";
	foreach($arResult["OFFERS"] as $arOffer){
		$templateData["STORES"]["OFFERS_ID"][]=$arOffer["ID"];
	}
}else{
	if (($arParams["SHOW_MEASURE"]=="Y")&&($arResult["CATALOG_MEASURE"])){
		$arMeasure = CCatalogMeasure::getList(array(), array("ID"=>$arResult["CATALOG_MEASURE"]), false, false, array())->GetNext();
		$strMeasure=$arMeasure["SYMBOL_RUS"];
	}
	$arAddToBasketData = CNext::GetAddToBasketArray($arResult, $totalCount, $arParams["DEFAULT_COUNT"], $arParams["BASKET_URL"], false, $arItemIDs["ALL_ITEM_IDS"], 'btn-lg w_icons', $arParams);
}
$arOfferProps = implode(';', $arParams['OFFERS_CART_PROPERTIES']);
// save item viewed
$arFirstPhoto = reset($arResult['MORE_PHOTO']);
$arItemPrices = $arResult['MIN_PRICE'];
if(isset($arResult['PRICE_MATRIX']) && $arResult['PRICE_MATRIX'])
{
	$rangSelected = $arResult['ITEM_QUANTITY_RANGE_SELECTED'];
	$priceSelected = $arResult['ITEM_PRICE_SELECTED'];
	if(isset($arResult['FIX_PRICE_MATRIX']) && $arResult['FIX_PRICE_MATRIX'])
	{
		$rangSelected = $arResult['FIX_PRICE_MATRIX']['RANGE_SELECT'];
		$priceSelected = $arResult['FIX_PRICE_MATRIX']['PRICE_SELECT'];
	}
	$arItemPrices = $arResult['ITEM_PRICES'][$priceSelected];
	$arItemPrices['VALUE'] = $arItemPrices['BASE_PRICE'];
	$arItemPrices['PRINT_VALUE'] = \Aspro\Functions\CAsproItem::getCurrentPrice('BASE_PRICE', $arItemPrices);
	$arItemPrices['DISCOUNT_VALUE'] = $arItemPrices['PRICE'];
	$arItemPrices['PRINT_DISCOUNT_VALUE'] = \Aspro\Functions\CAsproItem::getCurrentPrice('PRICE', $arItemPrices);
}
$arViewedData = array(
	'PRODUCT_ID' => $arResult['ID'],
	'IBLOCK_ID' => $arResult['IBLOCK_ID'],
	'NAME' => $arResult['NAME'],
	'DETAIL_PAGE_URL' => $arResult['DETAIL_PAGE_URL'],
	'PICTURE_ID' => $arResult['PREVIEW_PICTURE'] ? $arResult['PREVIEW_PICTURE']['ID'] : ($arFirstPhoto ? $arFirstPhoto['ID'] : false),
	'CATALOG_MEASURE_NAME' => $arResult['CATALOG_MEASURE_NAME'],
	'MIN_PRICE' => $arItemPrices,
	'CAN_BUY' => $arResult['CAN_BUY'] ? 'Y' : 'N',
	'IS_OFFER' => 'N',
	'WITH_OFFERS' => $arResult['OFFERS'] ? 'Y' : 'N',
);
$actualItem = $arResult["OFFERS"] ? (isset($arResult['OFFERS'][$arResult['OFFERS_SELECTED']]) ? $arResult['OFFERS'][$arResult['OFFERS_SELECTED']] : reset($arResult['OFFERS'])) : $arResult;

?>

<script type="text/javascript">
setViewedProduct(<?=$arResult['ID']?>, <?=CUtil::PhpToJSObject($arViewedData, false)?>);
</script>
<meta itemprop="name" content="<?=$name = strip_tags(!empty($arResult['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) ? $arResult['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] : $arResult['NAME'])?>" />
<meta itemprop="category" content="<?=$arResult['CATEGORY_PATH']?>" />
<meta itemprop="description" content="<?=(strlen(strip_tags($arResult['PREVIEW_TEXT'])) ? strip_tags($arResult['PREVIEW_TEXT']) : (strlen(strip_tags($arResult['DETAIL_TEXT'])) ? strip_tags($arResult['DETAIL_TEXT']) : $name))?>" />


<div class="item_main_info type_clothes <?=(!$showCustomOffer ? "noffer" : "");?> <?=($arParams["SHOW_UNABLE_SKU_PROPS"] != "N" ? "show_un_props" : "unshow_un_props");?>" id="<?=$arItemIDs["strMainID"];?>">

<?/*картинка на детальной*/?>
<div class="img_wrapper">
<div class="stickers">
<?$prop = ($arParams["STIKERS_PROP"] ? $arParams["STIKERS_PROP"] : "HIT");?>
			<?foreach(CNext::GetItemStickers($arResult["PROPERTIES"][$prop]) as $arSticker):?>
				<div><div class="<?=$arSticker['CLASS']?>"><?=$arSticker['VALUE']?></div></div>
			<?endforeach;?>
			
<?if($arParams["SALE_STIKER"] && $arResult["PROPERTIES"][$arParams["SALE_STIKER"]]["VALUE"]):?>						
<div>							
<?foreach($arResult["PROPERTIES"][$arParams["SALE_STIKER"]]["VALUE"] as $val):?>
<div class="sticker_sale_text"><?=$val;?></div>
<?endforeach;?>
</div>
<?endif;?>
</div>

<?$countThumb = count($arResult["MORE_PHOTO"]);?>

<div class="item_slider">
			<?if(($arParams["DISPLAY_WISH_BUTTONS"] != "N" || $arParams["DISPLAY_COMPARE"] == "Y") || (strlen($arResult["DISPLAY_PROPERTIES"]["CML2_ARTICLE"]["VALUE"]) || ($arResult['SHOW_OFFERS_PROPS'] && $showCustomOffer))):?>
				<div class="like_wrapper">
					<?if($arParams["DISPLAY_WISH_BUTTONS"] != "N" || $arParams["DISPLAY_COMPARE"] == "Y"):?>
						<div class="like_icons iblock">
							<?if($arParams["DISPLAY_WISH_BUTTONS"] != "N"):?>
								<?if(!$arResult["OFFERS"]):?>
									<div class="wish_item text" <?=($arAddToBasketData['CAN_BUY'] ? '' : 'style="display:none"');?> data-item="<?=$arResult["ID"]?>" data-iblock="<?=$arResult["IBLOCK_ID"]?>">
										<span class="value" title="<?=GetMessage('CT_BCE_CATALOG_IZB')?>" ><i></i></span>
										<span class="value added" title="<?=GetMessage('CT_BCE_CATALOG_IZB_ADDED')?>"><i></i></span>
									</div>
								<?elseif($arResult["OFFERS"] && $arParams["TYPE_SKU"] === 'TYPE_1' && !empty($arResult['OFFERS_PROP'])):?>
									<div class="wish_item text " <?=($arAddToBasketData['CAN_BUY'] ? '' : 'style="display:none"');?> data-item="" data-iblock="<?=$arResult["IBLOCK_ID"]?>" <?=(!empty($arResult['OFFERS_PROP']) ? 'data-offers="Y"' : '');?> data-props="<?=$arOfferProps?>">
										<span class="value <?=$arParams["TYPE_SKU"];?>" title="<?=GetMessage('CT_BCE_CATALOG_IZB')?>"><i></i></span>
										<span class="value added <?=$arParams["TYPE_SKU"];?>" title="<?=GetMessage('CT_BCE_CATALOG_IZB_ADDED')?>"><i></i></span>
									</div>
								<?endif;?>
							<?endif;?>
							<?if($arParams["DISPLAY_COMPARE"] == "Y"):?>
								<?if(!$arResult["OFFERS"] || ($arResult["OFFERS"] && $arParams["TYPE_SKU"] === 'TYPE_1' && !$arResult["OFFERS_PROP"])):?>
									<div data-item="<?=$arResult["ID"]?>" data-iblock="<?=$arResult["IBLOCK_ID"]?>" data-href="<?=$arResult["COMPARE_URL"]?>" class="compare_item text <?=($arResult["OFFERS"] ? $arParams["TYPE_SKU"] : "");?>" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['COMPARE_LINK']; ?>">
										<span class="value" title="<?=GetMessage('CT_BCE_CATALOG_COMPARE')?>"><i></i></span>
										<span class="value added" title="<?=GetMessage('CT_BCE_CATALOG_COMPARE_ADDED')?>"><i></i></span>
									</div>
								<?elseif($arResult["OFFERS"] && $arParams["TYPE_SKU"] === 'TYPE_1'):?>
									<div data-item="" data-iblock="<?=$arResult["IBLOCK_ID"]?>" data-href="<?=$arResult["COMPARE_URL"]?>" class="compare_item text <?=$arParams["TYPE_SKU"];?>">
										<span class="value" title="<?=GetMessage('CT_BCE_CATALOG_COMPARE')?>"><i></i></span>
										<span class="value added" title="<?=GetMessage('CT_BCE_CATALOG_COMPARE_ADDED')?>"><i></i></span>
									</div>
								<?endif;?>
							<?endif;?>
						</div>
					<?endif;?>
				</div>
			<?endif;?>

			<?reset($arResult['MORE_PHOTO']);
			$arFirstPhoto = current($arResult['MORE_PHOTO']);
			$viewImgType=$arParams["DETAIL_PICTURE_MODE"];?>
			<?/*слайды*/?>
				<div class="slides">
				<? if ($arResult['PROPERTIES']['SET']['VALUE']): ?>
				<div class="<?=$arResult['PROPERTIES']['SET']['VALUE_XML_ID']?>"></div>
				<?endif;?>
				<?if($showCustomOffer && !empty($arResult['OFFERS_PROP'])){?>
					<div class="offers_img wof">
						<?$alt=$arFirstPhoto["ALT"];
						$title=$arFirstPhoto["TITLE"];?>
						<?if($arFirstPhoto["BIG"]["src"]){?>
							<?/*a href="<?=($viewImgType=="POPUP" ? $arFirstPhoto["BIG"]["src"] : "javascript:void(0)");?>" class="<?=($viewImgType=="POPUP" ? "popup_link" : "line_link");?>" title="<?=$title;?>">*/?>
								<img class="lazy" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['PICT']; ?>" src="<?=$arFirstPhoto['SMALL']['src']; ?>"  alt="<?=$alt;?>" title="<?=$title;?>" itemprop="image">

							<?/*</a>*/?>
						<?}else{?>
							<?/*a href="javascript:void(0)" class="" title="<?=$title;?>"*/?>
								<img class="lazy" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['PICT']; ?>" src="<?=$arFirstPhoto['SRC']; ?>" alt="<?=$alt;?>" title="<?=$title;?>" itemprop="image">

							<?/*</a>*/?>
						<?}?>
					</div>
				<?}else{
					if($arResult["MORE_PHOTO"]){
						$bMagnifier = ($viewImgType=="MAGNIFIER");?>
						<ul>
							<?foreach($arResult["MORE_PHOTO"] as $i => $arImage){
								if($i && $bMagnifier):?>
									<?continue;?>
								<?endif;?>
								<?$isEmpty=($arImage["SMALL"]["src"] ? false : true );?>
								<?
								$alt=$arImage["ALT"];
								$title=$arImage["TITLE"];
								?>
								<li <?=(!$i ? 'class="current"' : 'style="display: none;"')?> id="photo-<?=$i?>" >
									<?if(!$isEmpty){?>
										<?/*<a href="<?=($viewImgType=="POPUP" ? $arImage["BIG"]["src"] : "javascript:void(0)");?>" <?=($bIsOneImage ? '' : 'data-fancybox-group="item_slider"')?> class="<?=($viewImgType=="POPUP" ? "popup_link fancy" : "line_link");?>" title="<?=$title;?>">*/?>
											<img  src="<?=$arImage["SMALL"]["src"]?>"  alt="<?=$alt;?>" title="<?=$title;?>"<?=(!$i ? ' itemprop="image"' : '')?>/>

										<?/*</a>*/?>
									<?}else{?>
										<img  src="<?=$arImage["SRC"]?>" alt="<?=$alt;?>" title="<?=$title;?>" />
									<?}?>
								</li>
							<?}?>
						</ul>
					<?}
				}?>

			<?if($arResult["OFFERS"]):?>
			<div class="colproduct_block1 " ></div>
			<?else:?>
			<?if (($arResult["PRODUCT"]["QUANTITY"]) == 0):?>
			<div class="colproduct_block  notavailability">Под заказ</div>
			<?else:?>
			<div class="colproduct_block  availability">В наличии</div>
			<?endif;?>
			<?endif;?>

			</div>
			<?/*слайды*/?>
			
			<?/*thumbs*/?>
			<?if(!$showCustomOffer || empty($arResult['OFFERS_PROP'])){
				if(count($arResult["MORE_PHOTO"]) > 1):?>
						<div class="wrapp_thumbs xzoom-thumbs">
						<div class="thumbs flexslider " data-plugin-options='{"animation": "slide", "selector": ".slides_block > li", "directionNav": true, "itemMargin":10, "itemWidth": 94, "controlsContainer": ".thumbs_navigation", "controlNav" :false, "animationLoop": true, "slideshow": false}' style="max-width:<?=ceil(((count($arResult['MORE_PHOTO']) <= 5 ? count($arResult['MORE_PHOTO']) : 5) * 104) - 10)?>px;">
							<ul class="slides_block" id="thumbs">
								<?foreach($arResult["MORE_PHOTO"]as $i => $arImage):?>
									<li <?=(!$i ? 'class="current"' : '')?> data-big_img="<?=$arImage["BIG"]["src"]?>" data-small_img="<?=$arImage["SMALL"]["src"]?>">
										<span><img class="xzoom-gallery" width="50" xpreview="<?=$arImage["THUMB"]["src"];?>" src="<?=$arImage["THUMB"]["src"]?>" alt="<?=$arImage["ALT"];?>" title="<?=$arImage["TITLE"];?>" /></span>
									</li>
								<?endforeach;?>
							</ul>
							<span class="thumbs_navigation custom_flex"></span>
						</div>
					</div>
					<script>
						$(document).ready(function(){
							$('.item_slider .thumbs li').first().addClass('current');
							$('.item_slider .thumbs .slides_block').delegate('li:not(.current)', 'click', function(){
								var slider_wrapper = $(this).parents('.item_slider'),
									index = $(this).index();
								$(this).addClass('current').siblings().removeClass('current')//.parents('.item_slider').find('.slides li').fadeOut(333);
								if(arNextOptions['THEME']['DETAIL_PICTURE_MODE'] == 'MAGNIFIER')
								{
									var li = $(this).parents('.item_slider').find('.slides li');
									li.find('img').attr('src', $(this).data('small_img'));
									li.find('img').attr('xoriginal', $(this).data('big_img'));
								}
								else
								{
									slider_wrapper.find('.slides li').removeClass('current').hide();
									slider_wrapper.find('.slides li:eq('+index+')').addClass('current').show();
								}
							});
								$('.bxSlider.thumbs .slides_block').bxSlider({
								mode: 'vertical',
								// infiniteLoop: false,
								minSlides: 5,
								maxSlides: 5,
								slideMargin: 10,
								pager: false,
								adaptiveHeight: false,
								responsive: false,
								touchEnabled: false,
								nextSelector: '.bx-controls-direction .slide-next',
								prevSelector: '.bx-controls-direction .slide-prev',
								oneToOneTouch: false,
								moveSlides: <?=($countThumb > 5 ? 1 : 0);?>,
								preventDefaultSwipeY: true,
								onSliderLoad: function(index)
								{
									<?if($countThumb > 5):?>
										$(this).closest('.bx-viewport').addClass('long');
										$(this).closest('.bxSlider').find('.bx-controls-direction a').addClass('opacityv');
									<?endif;?>
									$('.top-small-wrapper li[data-slide_key="0"]').addClass('flex-active-slide');
								}
							})
						})
					</script>
				<?endif;?>
			<?}else{?>
				<div class="wrapp_thumbs">
					<div class="sliders">
						<div class="thumbs" style="">
						</div>
					</div>
				</div>
			<?}?>
		</div>

<?/*картинка на детальной*/?>

<?/*mobile*/?>
		<?if(!$showCustomOffer || empty($arResult['OFFERS_PROP'])){?>
			<div class="item_slider flex flexslider color-controls" data-plugin-options='{"animation": "slide", "directionNav": false, "controlNav": true, "animationLoop": false, "slideshow": false, "slideshowSpeed": 10000, "animationSpeed": 600}'>
				<ul class="slides">
					<?if($arResult["MORE_PHOTO"]){
						foreach($arResult["MORE_PHOTO"] as $i => $arImage){?>
							<?$isEmpty=($arImage["SMALL"]["src"] ? false : true );?>
							<li <?=(!$i ? 'class="current"' : 'style="display: none;"')?> id="mphoto-<?=$i?>" >
								<?
								$alt=$arImage["ALT"];
								$title=$arImage["TITLE"];
								?>
								<?if(!$isEmpty){?>
										
										<img src="<?=$arImage["SMALL"]["src"]?>" alt="<?=$alt;?>" title="<?=$title;?>" />
										
										
								<?}else{?>
									<img  src="<?=$arImage["SRC"];?>" alt="<?=$alt;?>" title="<?=$title;?>" />
								<?}?>
							</li>
						<?}
					}?>
				</ul>
			</div>
		<?}else{?>
			<div class="item_slider flex color-controls"></div>
		<?}?>
	</div>

<?/*картинка на детальной*/?>

<div class="right_info">
<div class="info_item">

<?
	$get_fields = CIBlockSection::GetList(
		array(),
		array(
			'IBLOCK_ID' => 19,
			'ID' => $arResult['SECTION']['ID']
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
<?if ($my_fields_1==1):?>
<script>
$(document).ready(function() {
$( ".info_item .middle_info .prices .price" ).prepend( "от " );
							});
</script>
<?endif;?>	
	
<?$isArticle=(strlen($arResult["DISPLAY_PROPERTIES"]["CML2_ARTICLE"]["VALUE"]) || ($arResult['SHOW_OFFERS_PROPS'] && $showCustomOffer));?>

<?if ($arResult["OFFERS"]) :?>
	<h2 class="product-title-edit" style="display:inline;margin-right:20px;"></h2>	<span class="product-article"></span>
	<?else:?>
				
<?endif;?>
			
<?if($arResult["OFFERS"]):?>
<div class="colproduct_block1 visible-xs" ></div>
<?endif;?>

<?if($arResult["OFFERS"]):?>
	<?if (!$arResult["OFFERS"]) :?>
		<div><span>арт. </span> <span class="value" itemprop="value"><?=$arResult["DISPLAY_PROPERTIES"]["CML2_ARTICLE"]["VALUE"]?></span></div>
	<?endif;?>
<?endif;?>	
	
		
<?if (empty($arResult['OFFERS_PROP'])) :?>
	<div><span>арт. </span> <span class="value" itemprop="value"><?=$arResult["DISPLAY_PROPERTIES"]["CML2_ARTICLE"]["VALUE"]?></span></div>
<?endif;?>

<?if($arResult['UF_COMMENT_PRICE']):?>
<div class="uf_comment_price_container"><div class="uf_comment_price"><?= $arResult['UF_COMMENT_PRICE'] ?></div></div>
<?endif;?>


<?if (!$arResult["OFFERS"]) :?>
<?if (($arResult["PRODUCT"]["QUANTITY"]) == 0):?>
<div class="colproduct_block  notavailability visible-xs">Под заказ</div>
<?else:?>
<div class="colproduct_block  availability visible-xs">В наличии</div>
<?endif;?>
<?endif;?>	
		
		
<div class="middle_info main_item_wrapper">
				<?
				$showProps = false;
				$iCountProps = count($arResult["VISIBLE_PROPS"]);
				if($arResult["VISIBLE_PROPS"]){
					foreach($arResult["VISIBLE_PROPS"] as $arProp){
						if(!is_array($arProp["DISPLAY_VALUE"]))
							$arProp["DISPLAY_VALUE"] = array($arProp["DISPLAY_VALUE"]);

						if(is_array($arProp["DISPLAY_VALUE"])){
							foreach($arProp["DISPLAY_VALUE"] as $value){
								if(strlen($value)){
									$showProps = true;
									break 2;
								}
							}
						}
					}
				}
				?>




				<?$b2block = (($arResult["OFFERS"] && $showCustomOffer) || $showProps);?>
				<?if($b2block):?>
					<div class="row">
						<div class="col-md-6">
							<?if($arResult["OFFERS"] && $showCustomOffer){?>
								<div class="sku_props">
									<?if (!empty($arResult['OFFERS_PROP'])){?>
										<div class="bx_catalog_item_scu wrapper_sku" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['PROP_DIV']; ?>">
											<?foreach ($arSkuTemplate as $code => $strTemplate){
												if (!isset($arResult['OFFERS_PROP'][$code]))
													continue;
												echo str_replace('#ITEM#_prop_', $arItemIDs["ALL_ITEM_IDS"]['PROP'], $strTemplate);
											}?>
										</div>
									<?}?>
									<?$arItemJSParams=CNext::GetSKUJSParams($arResult, $arParams, $arResult, "Y");?>
									<script type="text/javascript">
										var <? echo $arItemIDs["strObName"]; ?> = new JCCatalogElement(<? echo CUtil::PhpToJSObject($arItemJSParams, false, true); ?>);
									</script>
								</div>
							<?}?>
							<?if($showProps):?>
								<div class="top_props">

									<div class="props props_list">
									<?if($iCountProps > $arParams['VISIBLE_PROP_COUNT']):?>
											<div class=""><span class="choise colored" data-block=".all_charakter"><?=GetMessage('ALL_CHARS');?></span></div>
										<?endif;?>
									</div>
								</div>
							<?endif;?>
							
							<?/*Примеры применения материала*/?>
							<?if($arResult["PROPERTIES"]["LINK_PORTFOLIO"]["VALUE"]):?>
								<div class="top_props">

									<div class="props props_list">
									
											<div class=""><span class="choise colored" data-block=".examples">Примеры применения материала</span></div>
									
									</div>
								</div>
							<?endif;?>
							
							
							
						</div>



				<div class="col-md-6 measur">
			<div class="r1"></div>
				<?endif;?>
				<div class="prices_block">
				<div class="cost prices clearfix">
						<?if( count( $arResult["OFFERS"] ) > 0 ){?>
							<div class="with_matrix" style="display:none;">
								<div class="price price_value_block"><span class="title"><?=GetMessage("CATALOG_ECONOMY");?></span> 
								<span class="values_wrapper"></span></div>
								
								<?if($arParams["SHOW_OLD_PRICE"]=="Y"):?>
									<div class="price discount"></div>
									
								<?endif;?>
								<?if($arParams["SHOW_DISCOUNT_PERCENT"]=="Y"){?>
									<div class="sale_block matrix" style="display:none;">
										<span class="title"><?=GetMessage("CATALOG_ECONOMY");?></span>
										<div class="text"><span class="values_wrapper"></span></div>
										<div class="clearfix"></div>
									</div>
								<?}?>
							</div>
							<?\Aspro\Functions\CAsproSku::showItemPrices($arParams, $arResult, $item_id, $min_price_id, $arItemIDs, 'Y');

							?>
                            <?
                                foreach ($arResult["OFFERS"] as $off){
                                    if($_REQUEST['pid'] == $off['ID'])
                                    {
                                            if(is_array($off['PROPERTIES']['UNIT_KOEF']['VALUE'])) {
                                            foreach ($off['PROPERTIES']['UNIT_KOEF']['DESCRIPTION'] as $key => $un) {
                                                ?>
                                                <span style="display: none"><?= round($off['PRICES']['BASE']['DISCOUNT_VALUE'] * $off['PROPERTIES']['UNIT_KOEF']['VALUE'][$key], 0).' руб./'.$arResult['MEASURE_ALL'][$un]['SYMBOL_RUS'] ?></span>
                                                <?
                                            }
                                            ?>
                                            <span  style="display: none"><?=$off['PRICES']['BASE']['DISCOUNT_VALUE']?> руб./<?echo $off['ITEM_MEASURE']['TITLE']?></span>
                                            <?
                                        }
                                    }

                                }
                            ?>
						<?}else{?>
		

							<?
							$item_id = $arResult["ID"];
							if(isset($arResult['PRICE_MATRIX']) && $arResult['PRICE_MATRIX']) // USE_PRICE_COUNT
							{
								if($arResult['PRICE_MATRIX']['COLS'])
								{
									$arCurPriceType = current($arResult['PRICE_MATRIX']['COLS']);
									$arCurPrice = current($arResult['PRICE_MATRIX']['MATRIX'][$arCurPriceType['ID']]);
									$min_price_id = $arCurPriceType['ID'];?>
									<div class="" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
										<meta itemprop="price" content="<?=($arResult['MIN_PRICE']['DISCOUNT_VALUE'] ? $arResult['MIN_PRICE']['DISCOUNT_VALUE'] : $arResult['MIN_PRICE']['VALUE'])?>" />
										<meta itemprop="priceCurrency" content="<?=$arResult['MIN_PRICE']['CURRENCY']?>" />
										<link itemprop="availability" href="http://schema.org/<?=($arResult['PRICE_MATRIX']['AVAILABLE'] == 'Y' ? 'InStock' : 'OutOfStock')?>" />
									</div>
								<?}?>
								<?if($arResult['ITEM_PRICE_MODE'] == 'Q' && count($arResult['PRICE_MATRIX']['ROWS']) > 1):?>
									<?=CNext::showPriceRangeTop($arResult, $arParams, GetMessage("CATALOG_ECONOMY"));?>
								<?endif;?>
								<?=CNext::showPriceMatrix($arResult, $arParams, $strMeasure, $arAddToBasketData);?>
                                <?
                                    if (is_array($arResult['PROPERTIES']['UNIT_KOEF']['VALUE'])) {
                                        foreach ($arResult['PROPERTIES']['UNIT_KOEF']['DESCRIPTION'] as $key => $un) {
                                            ?>
                                            <span style="display: none"><?= round($arResult['MIN_PRICE']['DISCOUNT_VALUE'] * $arResult['PROPERTIES']['UNIT_KOEF']['VALUE'][$key], 0) ?>
                                                руб./<?
                                                echo $arResult['MEASURE_ALL'][$un]['SYMBOL_RUS'] ?></span>
                                            <?
                                        }
                                        ?>
                                        <span style="display: none"><?= $arResult['MIN_PRICE']['DISCOUNT_VALUE'] ?>
                                            руб./<?
                                            echo $arResult['ITEM_MEASURE']['TITLE'] ?></span>
                                        <?
                                    }
                                ?>
							<?
							}
							else
							{?>
						<?\Aspro\Functions\CAsproItem::showItemPrices($arParams, $arResult["PRICES"], $strMeasure, $min_price_id, 'Y');?>
                                <?
                                    if (is_array($arResult['PROPERTIES']['UNIT_KOEF']['VALUE'])) {
                                        foreach ($arResult['PROPERTIES']['UNIT_KOEF']['DESCRIPTION'] as $key => $un) {
                                            ?>
                                            <span style="display: none"><?= round($arResult['PRICES']['BASE']['DISCOUNT_VALUE'] * $arResult['PROPERTIES']['UNIT_KOEF']['VALUE'][$key], 0) ?>
                                                руб./<?
                                                echo $arResult['MEASURE_ALL'][$un]['SYMBOL_RUS'] ?></span>
                                            <?
                                        }
                                        ?>
                                        <span style="display: none"><?= $arResult['PRICES']['BASE']['DISCOUNT_VALUE'] ?>
                                            руб./<?
                                            echo $arResult['ITEM_MEASURE']['TITLE'] ?></span>
                                        <?
                                    }

                                ?>
							<?}?>
						<?}?>
					</div>


					<?if($arParams["SHOW_DISCOUNT_TIME"]=="Y"){?>
						<?$arUserGroups = $USER->GetUserGroupArray();?>
						<?if($arParams['SHOW_DISCOUNT_TIME_EACH_SKU'] != 'Y' || ($arParams['SHOW_DISCOUNT_TIME_EACH_SKU'] == 'Y' && !$arResult['OFFERS'])):?>
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
													<span  <?=((count( $arResult["OFFERS"] ) > 0 && $arParams["TYPE_SKU"] == 'TYPE_1' && $arResult["OFFERS_PROP"]) ? 'style="opacity:0;"' : '')?> class="value"><?=$totalCount;?></span>
													<span class="text"><?=GetMessage("TITLE_QUANTITY");?></span>
												</span>
											</div>
										</div>
									<?endif;?>
								</div>
							<?}?>
						<?else:?>
							<?if($arResult['JS_OFFERS'])
							{
								foreach($arResult['JS_OFFERS'] as $keyOffer => $arTmpOffer2)
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
									$arResult['JS_OFFERS'][$keyOffer]['DISCOUNT_ACTIVE'] = $active_to;
								}
							}?>
							<div class="view_sale_block" style="display:none;">
								<div class="count_d_block">
										<span class="active_to_<?=$arResult["ID"]?> hidden"><?=$arDiscount["ACTIVE_TO"];?></span>
										<div class="title"><?=GetMessage("UNTIL_AKC");?></div>
										<span class="countdown countdown_<?=$arResult["ID"]?> values"></span>
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

			
				
				<div class="buy_block">
					<?if(!$arResult["OFFERS"]):?>
						<script>
							$(document).ready(function() {
								$('.catalog_detail input[data-sid="PRODUCT_NAME"]').attr('value', $('h1').text());
							});
						</script>

						<div class="counter_wrapp">
							<?if(($arAddToBasketData["OPTIONS"]["USE_PRODUCT_QUANTITY_DETAIL"] && $arAddToBasketData["ACTION"] == "ADD") && $arAddToBasketData["CAN_BUY"]):?>
								<div  data-item="<?=$arResult["ID"];?>" <?=(($arResult["OFFERS"] && $arParams["TYPE_SKU"]=="N") ? "style='display: none;'" : "");?> class="counter_block big_basket" data-offers="<?=($arResult["OFFERS"] ? "Y" : "N");?>">
									<span class="minus" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['QUANTITY_DOWN']; ?>">-</span>
									<input type="text" class="text" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['QUANTITY']; ?>" name="<? echo $arParams["PRODUCT_QUANTITY_VARIABLE"]; ?>" value="<?=$arAddToBasketData["MIN_QUANTITY_BUY"]?>" />
									<span class="plus" <?=($arAddToBasketData["MAX_QUANTITY_BUY"] ? "data-max='".$arAddToBasketData["MAX_QUANTITY_BUY"]."'" : "")?> id="<? echo $arItemIDs["ALL_ITEM_IDS"]['QUANTITY_UP']; ?>" >+</span>
								</div>
                            <?endif;?>
							<div id="<? echo $arItemIDs["ALL_ITEM_IDS"]['BASKET_ACTIONS']; ?>" style="" class="button_block <?=(($arAddToBasketData["ACTION"] == "ORDER" /*&& !$arResult["CAN_BUY"]*/) || !$arAddToBasketData["CAN_BUY"] || !$arAddToBasketData["OPTIONS"]["USE_PRODUCT_QUANTITY_DETAIL"] || ($arAddToBasketData["ACTION"] == "SUBSCRIBE" && $arResult["CATALOG_SUBSCRIBE"] == "Y")  ? "wide" : "");?>">
								<!--noindex-->
									<?=$arAddToBasketData["HTML"]?>
								<!--/noindex-->
							</div>
							<?if(isset($arResult['PRICE_MATRIX']) && $arResult['PRICE_MATRIX']) // USE_PRICE_COUNT
							{?>
								<?if($arResult['ITEM_PRICE_MODE'] == 'Q' && count($arResult['PRICE_MATRIX']['ROWS']) > 1):?>
									<?$arOnlyItemJSParams = array(
										"ITEM_PRICES" => $arResult["ITEM_PRICES"],
										"ITEM_PRICE_MODE" => $arResult["ITEM_PRICE_MODE"],
										"ITEM_QUANTITY_RANGES" => $arResult["ITEM_QUANTITY_RANGES"],
										"MIN_QUANTITY_BUY" => $arAddToBasketData["MIN_QUANTITY_BUY"],
										"ID" => $arItemIDs["strMainID"],
									)?>
									<script type="text/javascript">
										var <? echo $arItemIDs["strObName"]; ?>el = new JCCatalogOnlyElement(<? echo CUtil::PhpToJSObject($arOnlyItemJSParams, false, true); ?>);
									</script>
								<?endif;?>
							<?}?>

						</div>
						
						
				
						
					<?elseif($arResult["OFFERS"] && $arParams['TYPE_SKU'] == 'TYPE_1'):?>
						<div class="offer_buy_block buys_wrapp" style="display:none;">
							<div class="counter_wrapp">
                                <div class="counter_block big_basket" data-item="" style="display: none;">
                                    <span class="minus">-</span>
                                    <input type="text" class="text focus" name="quantity" value="1">
                                    <span class="plus">+</span>
                                </div>
								
								
								
							
                            </div>
						
						
					
				
				
				
						</div>
					<?elseif($arResult["OFFERS"] && $arParams['TYPE_SKU'] != 'TYPE_1'):?>
						<span class="btn btn-default btn-lg slide_offer transition_bg type_block"><i></i><span><?=GetMessage("MORE_TEXT_BOTTOM");?></span></span>
					
					<?endif;?>
					<?if($b2block):?>
	
						</div>

				

					<?endif;?>
					
					
<?
$arFilter = Array('IBLOCK_ID'=>19, 'GLOBAL_ACTIVE'=>'Y');
$db_list = CIBlockSection::GetList(Array("timestamp_x"=>"DESC"), $arFilter, false, Array("UF_DELBUTTON"));
  if($uf_value = $db_list->GetNext()):
     $value=$uf_value["UF_DELBUTTON"];
     
  endif;
  ?>
<?//Проверить условие кнопки?>
    <?if ($value==0):?>
	<div class="">
					<span class="btn btn-default btn-lg  type_block transition_bg one_click" data-item="<?=$arResult["ID"]?>" data-event="jqm" data-iblockID="<?=$arParams["IBLOCK_ID"]?>" data-param-form_id="MAINFORM" data-name="question">
					<!--<span><?=GetMessage('S_CALCULATE_PROJECT')?></span>-->
					<span>Заказать расчет</span>
					</span>
					</div>
	<?endif;?>
	<?//Проверить условие кнопки?>		
	
	
			

		<?//Вывод в правой колонке логистических параметров?>		
		<?if($arResult['OFFERS']):?>
	<div class="logistic col-md-12" style="display:none;" >
						<div class="logistic_title" style="display:none;"></div>
						<div class="product-dlina" style="display:none;"></div>
						<div class="product-shirina" style="display:none;"></div>
						<div class="product-tolwina" style="display:none;"></div>
						<div class="product-weight" style="display:none;"></div>
						</div>
						<?else:?>
						<?if((isset($arResult["CATALOG_LENGTH"])) || (isset($arResult["CATALOG_WIDTH"])) || (isset($arResult["CATALOG_HEIGHT"])) || 
						(isset($arResult["CATALOG_WEIGHT"])) && ($arResult["CATALOG_WEIGHT"] !=="0") ):?>
						<div class="logistic col-md-12">
						
						<div class="logistic_title">Логистические параметры единицы товара</div>	
						
						<?if(($arResult["CATALOG_LENGTH"] !=="0") && (isset($arResult["CATALOG_LENGTH"]))):?>
						<div class="product-dlina" ><span>Длина:</span> <?=$arResult["CATALOG_LENGTH"]?> мм</div>
						<?endif;?>
						
						<?if(($arResult["CATALOG_WIDTH"] !=="0") && (isset($arResult["CATALOG_WIDTH"]))):?>
						<div class="product-shirina" ><span>Ширина:</span> <?=$arResult["CATALOG_WIDTH"]?> мм</div>
						<?endif;?>	
						
						<?if(($arResult["CATALOG_HEIGHT"] !=="0") && (isset($arResult["CATALOG_HEIGHT"]))):?>
						<div class="product-tolwina" ><span>Толщина:</span> <?=$arResult["CATALOG_HEIGHT"]?> мм</div>
						<?endif;?>	
						
						<?if(($arResult["CATALOG_WEIGHT"] !=="0") && (isset($arResult["CATALOG_WEIGHT"]))):?>
						<?$weight_prod = $arResult["CATALOG_WEIGHT"]/1000;
						$weight_prod=str_replace(",",".",$weight_prod);?>
						<div class="product-weight" ><span>Вес:</span> <?=$weight_prod;?> кг</div>
						<?endif;?>

						
						
						</div>	
						<?endif;?>						
	<?endif;?>
		<?//Вывод в правой колонке логистических параметров?>		
	
	
	
	</div>

			

				<?if(is_array($arResult["STOCK"]) && $arResult["STOCK"]):?>
				<div class="stock_wrapper">
					<?foreach($arResult["STOCK"] as $key => $arStockItem):?>
						<div class="stock_board <?=($arStockItem["PREVIEW_TEXT"] ? '' : 'nt');?>">
							<div class="title"><a class="dark_link" href="<?=$arStockItem["DETAIL_PAGE_URL"]?>"><?=$arStockItem["NAME"];?></a></div>
							<div class="txt"><?=$arStockItem["PREVIEW_TEXT"]?></div>
						</div>
					<?endforeach;?>
				</div>
				<?endif;?>
	



	<?//Логистические параметры единицы товара?>	
	<?/*if($arResult['OFFERS']):?>
	<div class="logistic col-md-6" style="display:none;" >
						<div class="logistic_title" style="display:none;"></div>
						<div class="product-dlina" style="display:none;"></div>
						<div class="product-shirina" style="display:none;"></div>
						<div class="product-tolwina" style="display:none;"></div>
						<div class="product-weight" style="display:none;"></div>
						</div>
						<?else:?>
						<?if((isset($arResult["CATALOG_LENGTH"])) || (isset($arResult["CATALOG_WIDTH"])) || (isset($arResult["CATALOG_HEIGHT"])) || 
						(isset($arResult["CATALOG_WEIGHT"])) && ($arResult["CATALOG_WEIGHT"] !=="0") ):?>
						<div class="logistic col-md-6">
						
						<div class="logistic_title">Логистические параметры единицы товара</div>	
						
						<?if(($arResult["CATALOG_LENGTH"] !=="0") && (isset($arResult["CATALOG_LENGTH"]))):?>
						<div class="product-dlina" ><span>Длина:</span> <?=$arResult["CATALOG_LENGTH"]?> мм</div>
						<?endif;?>
						
						<?if(($arResult["CATALOG_WIDTH"] !=="0") && (isset($arResult["CATALOG_WIDTH"]))):?>
						<div class="product-shirina" ><span>Ширина:</span> <?=$arResult["CATALOG_WIDTH"]?> мм</div>
						<?endif;?>	
						
						<?if(($arResult["CATALOG_HEIGHT"] !=="0") && (isset($arResult["CATALOG_HEIGHT"]))):?>
						<div class="product-tolwina" ><span>Толщина:</span> <?=$arResult["CATALOG_HEIGHT"]?> мм</div>
						<?endif;?>	
						
						<?if(($arResult["CATALOG_WEIGHT"] !=="0") && (isset($arResult["CATALOG_WEIGHT"]))):?>
						<?$weight_prod = $arResult["CATALOG_WEIGHT"]/1000;
						$weight_prod=str_replace(",",".",$weight_prod);?>
						<div class="product-weight" ><span>Вес:</span> <?=$weight_prod;?> кг</div>
						<?endif;?>

						
						
						</div>	
						<?endif;?>						
	<?endif;*/?>
	
				<?/*div*/?>
				
				<div class="stock_wrapper" style="display:none;"></div>
				
	</div>

			
	</div>
	</div>
		<script>
			ym(62259859,'reachGoal','DETAIL_PR');
			</script>	
			
	</div>
<?/*item_main_info*/?>



	<?$bPriceCount = ($arParams['USE_PRICE_COUNT'] == 'Y');?>
	<?if($arResult['OFFERS']):?>
		<span itemprop="offers" itemscope itemtype="http://schema.org/AggregateOffer" style="display:none;">
			<meta itemprop="offerCount" content="<?=count($arResult['OFFERS'])?>" />
			<meta itemprop="lowPrice" content="<?=($arResult['MIN_PRICE']['DISCOUNT_VALUE'] ? $arResult['MIN_PRICE']['DISCOUNT_VALUE'] : $arResult['MIN_PRICE']['VALUE'] )?>" />
			<meta itemprop="priceCurrency" content="<?=$arResult['MIN_PRICE']['CURRENCY']?>" />
			<?foreach($arResult['OFFERS'] as $arOffer):?>
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
					<noindex><a  rel="nofollow" href="<?=$arOffer['DETAIL_PAGE_URL']?>" itemprop="url"></a></noindex>
					<meta itemprop="price" content="<?=($arOffer['MIN_PRICE']['DISCOUNT_VALUE']) ? $arOffer['MIN_PRICE']['DISCOUNT_VALUE'] : $arOffer['MIN_PRICE']['VALUE']?>" />
					<meta itemprop="priceCurrency" content="<?=$arOffer['MIN_PRICE']['CURRENCY']?>" />
					<link itemprop="availability" href="http://schema.org/<?=($arOffer['CAN_BUY'] ? 'InStock' : 'OutOfStock')?>" />
				</span>
			<?endforeach;?>
		</span>
		<?unset($arOffer, $currentOffersList);?>
	<?else:?>
		<?if(!$bPriceCount):?>
			<span itemprop="offers" itemscope itemtype="http://schema.org/Offer">
				<meta itemprop="price" content="<?=($arResult['MIN_PRICE']['DISCOUNT_VALUE'] ? $arResult['MIN_PRICE']['DISCOUNT_VALUE'] : $arResult['MIN_PRICE']['VALUE'])?>" />
				<meta itemprop="priceCurrency" content="<?=$arResult['MIN_PRICE']['CURRENCY']?>" />
				<link itemprop="availability" href="http://schema.org/<?=($arResult['MIN_PRICE']['CAN_BUY'] ? 'InStock' : 'OutOfStock')?>" />
			</span>
		<?endif;?>
	<?endif;?>
	<div class="clearleft"></div>



	<?if($arResult["TIZERS_ITEMS"]){?>
		<div class="tizers_block_detail tizers_block">
			<div class="row">
				<?$count_t_items=count($arResult["TIZERS_ITEMS"]);?>
				<?foreach($arResult["TIZERS_ITEMS"] as $arItem){?>
					<div class="col-md-3 col-sm-3 col-xs-6">
						<div class="inner_wrapper item">
							<?if($arItem["UF_FILE"]){?>
								<div class="img">
									<?if($arItem["UF_LINK"]){?>
										<a <?=(strpos($arItem["UF_LINK"], "http") !== false ? "target='_blank' rel='nofollow'" : '')?> href="<?=$arItem["UF_LINK"];?>" >
									<?}?>
									<img src="<?=$arItem["PREVIEW_PICTURE"]["src"];?>" alt="<?=$arItem["UF_NAME"];?>" title="<?=$arItem["UF_NAME"];?>">
									<?if($arItem["UF_LINK"]){?>
										</a>
									<?}?>
								</div>
							<?}?>
							<div class="title">
								<?if($arItem["UF_LINK"]){?>
									<a <?=(strpos($arItem["UF_LINK"], "http") !== false ? "target='_blank' rel='nofollow'" : '')?> href="<?=$arItem["UF_LINK"];?>" >
								<?}?>
								<?=$arItem["UF_NAME"];?>
								<?if($arItem["UF_LINK"]){?>
									</a>
								<?}?>
							</div>
						</div>
					</div>
				<?}?>
			</div>
		</div>
	<?}?>


	<?if($arParams["SHOW_KIT_PARTS"] == "Y" && $arResult["SET_ITEMS"]):?>
		<div class="set_wrapp set_block">
			<div class="title"><?=GetMessage("GROUP_PARTS_TITLE")?></div>
			<ul>
				<?foreach($arResult["SET_ITEMS"] as $iii => $arSetItem):?>
					<li class="item">
						<div class="item_inner">
							<div class="image">
								<a href="<?=$arSetItem["DETAIL_PAGE_URL"]?>">
									<?if($arSetItem["PREVIEW_PICTURE"]):?>
										<?$img = CFile::ResizeImageGet($arSetItem["PREVIEW_PICTURE"], array("width" => 140, "height" => 140), BX_RESIZE_IMAGE_PROPORTIONAL, true);?>
										<img  src="<?=$img["src"]?>" alt="<?=$arSetItem["NAME"];?>" title="<?=$arSetItem["NAME"];?>" />
									<?elseif($arSetItem["DETAIL_PICTURE"]):?>
										<?$img = CFile::ResizeImageGet($arSetItem["DETAIL_PICTURE"], array("width" => 140, "height" => 140), BX_RESIZE_IMAGE_PROPORTIONAL, true);?>
										<img  src="<?=$img["src"]?>" alt="<?=$arSetItem["NAME"];?>" title="<?=$arSetItem["NAME"];?>" />
									<?else:?>
										<img  src="<?=SITE_TEMPLATE_PATH?>/images/no_photo_small.png" alt="<?=$arSetItem["NAME"];?>" title="<?=$arSetItem["NAME"];?>" />
									<?endif;?>
								</a>
								<?if($arResult["SET_ITEMS_QUANTITY"]):?>
									<div class="quantity">x<?=$arSetItem["QUANTITY"];?></div>
								<?endif;?>
							</div>
							<div class="item_info">
								<div class="item-title">
									<a href="<?=$arSetItem["DETAIL_PAGE_URL"]?>"><span><?=$arSetItem["NAME"]?></span></a>
								</div>
								<?if($arParams["SHOW_KIT_PARTS_PRICES"] == "Y"):?>
									<div class="cost prices clearfix">
										<?
										$arCountPricesCanAccess = 0;
										foreach($arSetItem["PRICES"] as $key => $arPrice){
											if($arPrice["CAN_ACCESS"]){
												$arCountPricesCanAccess++;
											}
										}?>
										<?foreach($arSetItem["PRICES"] as $key => $arPrice):?>
											<?if($arPrice["CAN_ACCESS"]):?>
												<?$price = CPrice::GetByID($arPrice["ID"]);?>
												<?if($arCountPricesCanAccess > 1):?>
													<div class="price_name"><?=$price["CATALOG_GROUP_NAME"];?></div>
												<?endif;?>
												<?if($arPrice["VALUE"] > $arPrice["DISCOUNT_VALUE"]  && $arParams["SHOW_OLD_PRICE"]=="Y"):?>
													<div class="price">
														<?=$arPrice["PRINT_DISCOUNT_VALUE"];?><?if(($arParams["SHOW_MEASURE"] == "Y") && $strMeasure):?><small>/<?=$strMeasure?></small><?endif;?>
													</div>
													<div class="price discount">
														<span><?=$arPrice["PRINT_VALUE"]?></span>
													</div>
												<?else:?>
													<div class="price">
														<?=$arPrice["PRINT_VALUE"];?><?if(($arParams["SHOW_MEASURE"] == "Y") && $strMeasure):?><small>/<?=$strMeasure?></small><?endif;?>
													</div>
												<?endif;?>
											<?endif;?>
										<?endforeach;?>
									</div>
								<?endif;?>
							</div>
						</div>
					</li>
					<?if($arResult["SET_ITEMS"][$iii + 1]):?>
						<li class="separator"></li>
					<?endif;?>
				<?endforeach;?>
			</ul>
		</div>
	<?endif;?>


	<?if($arResult['OFFERS']):?>
		<?if($arResult['OFFER_GROUP']):?>
			<?foreach($arResult['OFFERS'] as $arOffer):?>
				<?if(!$arOffer['OFFER_GROUP']) continue;?>
				<span id="<?=$arItemIDs['ALL_ITEM_IDS']['OFFER_GROUP'].$arOffer['ID']?>" style="display: none;">
					<?$APPLICATION->IncludeComponent("bitrix:catalog.set.constructor", "",
						array(
							"IBLOCK_ID" => $arResult["OFFERS_IBLOCK"],
							"ELEMENT_ID" => $arOffer['ID'],
							"PRICE_CODE" => $arParams["PRICE_CODE"],
							"BASKET_URL" => $arParams["BASKET_URL"],
							"OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
							"CACHE_TYPE" => $arParams["CACHE_TYPE"],
							"CACHE_TIME" => $arParams["CACHE_TIME"],
							"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
							"SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
							"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
							"SHOW_DISCOUNT_PERCENT" => $arParams["SHOW_DISCOUNT_PERCENT"],
							"CONVERT_CURRENCY" => $arParams['CONVERT_CURRENCY'],
							"CURRENCY_ID" => $arParams["CURRENCY_ID"]
						), $component, array("HIDE_ICONS" => "Y")
					);?>
				</span>
			<?endforeach;?>
		<?endif;?>
	<?else:?>
		<?$APPLICATION->IncludeComponent("bitrix:catalog.set.constructor", "",
			array(
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"ELEMENT_ID" => $arResult["ID"],
				"PRICE_CODE" => $arParams["PRICE_CODE"],
				"BASKET_URL" => $arParams["BASKET_URL"],
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
				"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
				"SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
				"SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
				"SHOW_DISCOUNT_PERCENT" => $arParams["SHOW_DISCOUNT_PERCENT"],
				"CONVERT_CURRENCY" => $arParams['CONVERT_CURRENCY'],
				"CURRENCY_ID" => $arParams["CURRENCY_ID"]
			), $component, array("HIDE_ICONS" => "Y")
		);?>
	<?endif;?>
</div>


<?if($templateData['BRAND_ITEM'] || (\Bitrix\Main\ModuleManager::isModuleInstalled("sale") && (!isset($arParams['USE_BIG_DATA']) || $arParams['USE_BIG_DATA'] != 'N'))):?>
<div class="row">
<div class="col-md-12">
<?endif;?>
<div class=" type_more">
	<?
	$arVideo = array();
	if(strlen($arResult["DISPLAY_PROPERTIES"]["VIDEO"]["VALUE"])){
		$arVideo[] = $arResult["DISPLAY_PROPERTIES"]["VIDEO"]["~VALUE"];
	}
	if(isset($arResult["DISPLAY_PROPERTIES"]["VIDEO_RUTUBE"]["VALUE"])){
		if(is_array($arResult["DISPLAY_PROPERTIES"]["VIDEO_RUTUBE"]["VALUE"])){
			$arVideo = $arVideo + $arResult["DISPLAY_PROPERTIES"]["VIDEO_RUTUBE"]["~VALUE"];
		}
		elseif(strlen($arResult["DISPLAY_PROPERTIES"]["VIDEO_RUTUBE"]["VALUE"])){
			$arVideo[] = $arResult["DISPLAY_PROPERTIES"]["VIDEO_RUTUBE"]["~VALUE"];
		}
	}
	if(strlen($arResult["SECTION_FULL"]["UF_VIDEO"])){
		$arVideo[] = $arResult["SECTION_FULL"]["~UF_VIDEO"];
	}
	if(strlen($arResult["SECTION_FULL"]["UF_VIDEO_YOUTUBE"])){
		$arVideo[] = $arResult["SECTION_FULL"]["~UF_VIDEO_YOUTUBE"];
	}
	?>
	<?$instr_prop = ($arParams["DETAIL_DOCS_PROP"] ? $arParams["DETAIL_DOCS_PROP"] : "INSTRUCTIONS");?>
	
			<div>
			
			<?
			$showSkUName = ((in_array('NAME', $arParams['OFFERS_FIELD_CODE'])));
			$showSkUImages = false;
			if(((in_array('PREVIEW_PICTURE', $arParams['OFFERS_FIELD_CODE']) || in_array('DETAIL_PICTURE', $arParams['OFFERS_FIELD_CODE'])))){
				foreach ($arResult["OFFERS"] as $key => $arSKU){
					if($arSKU['PREVIEW_PICTURE'] || $arSKU['DETAIL_PICTURE']){
						$showSkUImages = true;
						break;
					}
				}
			}?>
			<?if($arResult["OFFERS"] && $arParams["TYPE_SKU"] !== "TYPE_1"):?>
				<script>
					$(document).ready(function() {
						$('.catalog_detail .tabs_section .tabs_content .form.inline input[data-sid="PRODUCT_NAME"]').attr('value', $('h1').text());
					});
				</script>
			<?endif;?>
			

			<?if($arResult["DETAIL_TEXT"] || ($arResult["PREVIEW_TEXT"]) || ($arResult["PROPERTIES"]["EDITOR1"]) || ((count($arResult["PROPERTIES"][$instr_prop]["VALUE"]) && is_array($arResult["PROPERTIES"][$instr_prop]["VALUE"])) || count($arResult["SECTION_FULL"]["UF_FILES"]))):?>
			<div>


						<?if(strlen($arResult["PREVIEW_TEXT"])):?>
							<div class="detail_text"><?=$arResult["PREVIEW_TEXT"]?></div>
						<?endif;?>
						<?if(strlen($arResult["DETAIL_TEXT"])):?>
							<div class="detail_text"><?=$arResult["DETAIL_TEXT"]?></div>
						<?endif;?>
																<?if ($arResult["OFFERS"]) :?>

<div class="product-descr"></div>
<?endif;?>
			<div class="editor default_detail">
                <?$APPLICATION->IncludeComponent(
                    "sprint.editor:blocks",
                    ".default",
                    Array(
                        "ELEMENT_ID" => $arResult["ID"],
                        "IBLOCK_ID" => $arResult["IBLOCK_ID"],
                        "PROPERTY_CODE" => "EDITOR1",
                        "USE_JQUERY" => "N",
                        "USE_FANCYBOX" => "N",
                    ),
                    $component,
                    Array(
                        "HIDE_ICONS" => "Y"
                    )
                );?>
            </div>

<?if(!$showProps && $arResult['OFFERS']){
			foreach($arResult['OFFERS'] as $arOffer){
				foreach($arOffer['DISPLAY_PROPERTIES'] as $arProp){
					if(!$arResult["TMP_OFFERS_PROP"][$arProp['CODE']])
					{
						if(!is_array($arProp["DISPLAY_VALUE"]))
							$arProp["DISPLAY_VALUE"] = array($arProp["DISPLAY_VALUE"]);

						foreach($arProp["DISPLAY_VALUE"] as $value){
							if(strlen($value)){
								$showProps = true;
								break 3;
							}
						}
					}
				}
			}
		}?>
												
<div class="row desc_tab">
<div class="all_charakter"></div>
<hr>

<?/*Все характеристики*/?>


<?/*характеристики доски*/?>
				
				<?if ($arResult['SECTION']['ID'] == 98):?>
	<div class="col-md-6">				
				<h4>Характеристики</h4>
			<div class="char_block">
			
				
				<?if($arResult["DISPLAY_PROPERTIES"]["PROFIL_DOSKA_DPK"]["VALUE"]):?>
						<table class="props_list nbg">
						<tr>
						<td class="char_name"><div class="props_item"><span><?=$arResult["DISPLAY_PROPERTIES"]["PROFIL_DOSKA_DPK"]["NAME"]?></span></div></td>
						<td class="char_value"><?=$arResult["DISPLAY_PROPERTIES"]["PROFIL_DOSKA_DPK"]["VALUE"]?></td>
						</tr>
						</table>
						
				<?endif;?>		
				
									<table class="props_list nbg">
										<?foreach($arResult["DISPLAY_PROPERTIES"] as $arProp):?>
											<?if(in_array($arProp["CODE"], array("IT_8","TOLWINA_DOSKA_DPK","DLINA_DOSKA_DPK", "IT_11_DOSKA_DPK", "VES_DOSKA_DPK", "KL_DOSKA_DPK",
											"VWS_NOJKI_DOSKA_DPK", "VARIANT_COLORS_DOSKA_DPK", "SURFACE_DOSKA_DPK", "MATERIAL_DOSKA_DPK","GARANTY"))):?>
												<?if((!is_array($arProp["DISPLAY_VALUE"]) && strlen($arProp["DISPLAY_VALUE"])) || (is_array($arProp["DISPLAY_VALUE"]) && implode('', $arProp["DISPLAY_VALUE"]))):?>
													<tr itemprop="additionalProperty" itemscope itemtype="http://schema.org/PropertyValue">
														<td class="char_name">
														<div class="props_item"><span><?=$arProp["NAME"]?></span></div>
														</td>
														<td class="char_value">
															
																<?if(is_array($arProp["DISPLAY_VALUE"]) && count($arProp["DISPLAY_VALUE"]) > 1):?>
																	<?=implode(', ', $arProp["DISPLAY_VALUE"]);?>
																<?else:?>
																	<?=$arProp["DISPLAY_VALUE"];?>
																<?endif;?>
															
														</td>
													</tr>
													
												<?endif;?>
											<?endif;?>
										<?endforeach;?>
									</table>
									<?/*<table class="props_list nbg" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['DISPLAY_PROP_DIV']; ?>"></table>*/?>
								
							
											<table class="props_list nbg">
											<tr>	
											<td class="char_name">
											<div class="props_item">Применение</div>
											</td>
											<td class="char_value">
										
											<?if (CModule::IncludeModule("iblock")):?>
											<?$usage_doska_dpk = [];/*массив всех значений свойства Применение, которые занесены в настройки инфоблока */
											$property_enums = CIBlockPropertyEnum::GetList(Array("ID"=>"ASC", "SORT"=>"ASC"), Array("IBLOCK_ID"=>19, "CODE"=>"USAGE_DOSKA_DPK"));?>
											<?while ($enum_fields = $property_enums->GetNext()) :?>
											   <?	$usage_doska_dpk[] = $enum_fields["VALUE"];?>
												<?endwhile;?>
												
												<?endif?>
											<? /*echo "<pre>"; print_r($usage_doska_dpk); echo "</pre>";*/?>

													<?if($arResult['DISPLAY_PROPERTIES']['USAGE_DOSKA_DPK']['VALUE']):?>
													
												<?
											$usag = []; /*массив значений свойства Применение */
											foreach($arResult["DISPLAY_PROPERTIES"] as $idProp=>$arProperty):?>
											<?if($idProp =="USAGE_DOSKA_DPK"): ?>
											<?$usag[] = $arProperty["DISPLAY_VALUE"];?>
											<? /*echo "<pre>"; print_r($usag[0]); echo "</pre>";*/?>

											<?endif?>
											<?endforeach;?>
												<?endif;?>

											<?foreach($usage_doska_dpk as $mew ):?>
											  <div> 
                                               <?if (is_array($usag) && in_array($mew, $usag[0])):?>
                                                  <i class="fa fa-check " style="color:#45840f;"></i>
                                                  <?else:?>
												<i class="fa fa-close" style="color:#9e1414;"></i>
                                                <?endif?>
												<?=$mew;?>
                                                </div>
											<?endforeach;?>
										
											</td>
											</tr>
																						
											</table>
</div>
</div>
	<?else:?>
	
				<?/*характеристики для всех остальных разделов*/?>
				<div class="char_block">
				<?if($arResult["DISPLAY_PROPERTIES"]["CML2_ATTRIBUTES"]["VALUE"]):?>
					<div class="col-md-6">
						<h4>Характеристики:</h4>
						<table class="props_list colored_char">
						<?foreach($arResult["DISPLAY_PROPERTIES"]["CML2_ATTRIBUTES"]["VALUE"] as $k=>$value):?>
						<tr>
						<td class="char_name"><?=$arResult["DISPLAY_PROPERTIES"]["CML2_ATTRIBUTES"]["DESCRIPTION"][$k]?></td>
						<td class="char_value"><?=$value?></td>
						</tr>
						<?endforeach?> 
						</table>
						</div>
				<?endif;?>		
				</div>
				<?/*характеристики для всех остальных разделов*/?>


<?endif;?>
<?/*характеристики доски*/?>
		
<?/*Все характеристики*/?>
		


		
		
		
		
		
		
	<div    class="<?=((isset($arResult["DISPLAY_PROPERTIES"]["CML2_ATTRIBUTES"]["VALUE"]) && (!$arResult['SECTION']['ID'] == 98)) ? 'col-md-12' : 'col-md-6')?>"  >
<?/*Дополнительные файлы*/?>
						<?
						$arFiles = array();
						if($arResult["PROPERTIES"][$instr_prop]["VALUE"]){
							$arFiles = $arResult["PROPERTIES"][$instr_prop]["VALUE"];
						}
						else{
							$arFiles = $arResult["SECTION_FULL"]["UF_FILES"];
						}
						if(is_array($arFiles)){
							foreach($arFiles as $key => $value){
								if(!intval($value)){
									unset($arFiles[$key]);
								}
							}
						}
						?>
						
								<?if (($arResult["PROPERTIES"]["INSTRUCTIONS_FILE"]["VALUE"]) || ($arFiles)):?>
						
								<h4><?=($arParams["BLOCK_DOCS_NAME"] ? $arParams["BLOCK_DOCS_NAME"] : GetMessage("DOCUMENTS_TITLE"))?></h4>
						<?endif;?>
								<?if($arFiles):?>		
								<div class="wraps">		
						
								<div class="files_block">
									<div class="row">
								
										<?foreach($arFiles as $arItem):?>
											<div class="<?=((!isset($arResult["DISPLAY_PROPERTIES"]["CML2_ATTRIBUTES"]["VALUE"]) && (!$arResult['SECTION']['ID'] == 98)) ? 'col-md-12 col-sm-12' : 'col-md-6 col-sm-6')?> ">
												<?$arFile=CNext::GetFileInfo($arItem);?>
												<div class="file_type clearfix <?=$arFile["TYPE"];?>">
													<i class="icon"></i>
													<div class="description">
														<a target="_blank" href="<?=$arFile["SRC"];?>" class="dark_link"><?=$arFile["DESCRIPTION"];?></a>
														<span class="size">
															<?=$arFile["FILE_SIZE_FORMAT"];?>
														</span>
													</div>
												</div>
											</div>
										<?endforeach;?>
									</div>
								</div>

					</div>			
					<?endif;?>
					
						<?/*Вывод привязанных документов*/?>

		<?if($arResult["PROPERTIES"]["INSTRUCTIONS_FILE"]["VALUE"]):?>
<?$GLOBALS['arrFilterInstructions'] = array("ID" => $arResult["PROPERTIES"]["INSTRUCTIONS_FILE"]["VALUE"]);?>
		
			
<div class="">

<?$APPLICATION->IncludeComponent(
			"bitrix:news.list",
			"news-documents",
			array(
				"IBLOCK_TYPE" => "aspro_next_content",
				"IBLOCK_ID" => $arResult["PROPERTIES"]["INSTRUCTIONS_FILE"]["LINK_IBLOCK_ID"],
				"NEWS_COUNT" => "20",
				"SORT_BY1" => "SORT",
				"SORT_ORDER1" => "ASC",
				"SORT_BY2" => "ID",
				"SORT_ORDER2" => "DESC",
				"FILTER_NAME" => "arrFilterInstructions",
				"FIELD_CODE" => array(
					0 => "NAME",
					1 => "PREVIEW_TEXT",
					3 => "PREVIEW_PICTURE",
					4 => "",
				),
				"PROPERTY_CODE" => array(
					0 => "PERIOD",
					1 => "REDIRECT",
					2 => "",
				),
				"CHECK_DATES" => "Y",
				"DETAIL_URL" => "",
				"AJAX_MODE" => "N",
				"AJAX_OPTION_JUMP" => "N",
				"AJAX_OPTION_STYLE" => "Y",
				"AJAX_OPTION_HISTORY" => "N",
				'CACHE_TYPE' => 'N',
				'CACHE_TIME' => '',
				"CACHE_GROUPS" => "N",
				"PREVIEW_TRUNCATE_LEN" => "",
				"ACTIVE_DATE_FORMAT" => "d.m.Y",
				"SET_TITLE" => "N",
				"SET_STATUS_404" => "N",
				"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
				"ADD_SECTIONS_CHAIN" => "N",
				"HIDE_LINK_WHEN_NO_DETAIL" => "N",
				"PARENT_SECTION" => "",
				"PARENT_SECTION_CODE" => "",
				"INCLUDE_SUBSECTIONS" => "Y",
				"PAGER_TEMPLATE" => ".default",
				"DISPLAY_TOP_PAGER" => "N",
				"DISPLAY_BOTTOM_PAGER" => "Y",
				"PAGER_TITLE" => "Новости",
				"PAGER_SHOW_ALWAYS" => "N",
				"PAGER_DESC_NUMBERING" => "N",
				"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
				"PAGER_SHOW_ALL" => "N",
				"VIEW_TYPE" => "list",
				"BIG_BLOCK" => "Y",
				"IMAGE_POSITION" => "left",
				"COUNT_IN_LINE" => "2",
				"TITLE" => ($arParams["BLOCK_SERVICES_NAME"] ? $arParams["BLOCK_SERVICES_NAME"] : GetMessage("SERVICES_TITLE")),
			),
			$component, array("HIDE_ICONS" => "Y")
		);?>

</div>	
		
		
		
		<?endif;?>	
		
						
<?/*Дополнительные файлы*/?>
					
					<?/*Производитель*/?>
<?if($templateData['BRAND_ITEM']["DETAIL_PAGE_URL"]):?>
<div class="wraps" style=" <?=(!empty($arFiles) ? 'margin-top:20px;' : '')?>">
<a class="detail_text" style="    border-bottom: 1px solid;color:#555;" href="<?=$templateData['BRAND_ITEM']["DETAIL_PAGE_URL"];?>" >Информация о производителе</a>
</div>


<?/*Производитель*/?>
							
							<?endif;?>
												


</div>
</div>

		
<?/*Вывод привязанных элементов портфолио*/?>


<div class="examples"></div>
<?if($arResult["PROPERTIES"]["LINK_PORTFOLIO"]["VALUE"]):?>
<?$GLOBALS['arrFilterProjects'] = array("ID" => $arResult["PROPERTIES"]["LINK_PORTFOLIO"]["VALUE"]);?>
			

<hr>
		<h4>Примеры применения материала:</h4>
		
<div class="item-views table-type-block table-elements news-project">

<?$APPLICATION->IncludeComponent(
			"bitrix:news.list",
			"news-project-catalog-pr",
			array(
				"IBLOCK_TYPE" => "aspro_next_content",
				"IBLOCK_ID" => $arResult["PROPERTIES"]["LINK_PORTFOLIO"]["LINK_IBLOCK_ID"],
				"NEWS_COUNT" => "20",
				"SORT_BY1" => "SORT",
				"SORT_ORDER1" => "ASC",
				"SORT_BY2" => "ID",
				"SORT_ORDER2" => "DESC",
				"FILTER_NAME" => "arrFilterProjects",
				"FIELD_CODE" => array(
					0 => "NAME",
					1 => "PREVIEW_TEXT",
					3 => "PREVIEW_PICTURE",
					4 => "",
				),
				"PROPERTY_CODE" => array(
					0 => "PERIOD",
					1 => "REDIRECT",
					2 => "",
				),
				"CHECK_DATES" => "Y",
				"DETAIL_URL" => "",
				"AJAX_MODE" => "N",
				"AJAX_OPTION_JUMP" => "N",
				"AJAX_OPTION_STYLE" => "Y",
				"AJAX_OPTION_HISTORY" => "N",
				'CACHE_TYPE' => 'A',
				'CACHE_TIME' => '172800',
				"CACHE_FILTER" => "Y",
				"CACHE_GROUPS" => "N",
				"PREVIEW_TRUNCATE_LEN" => "",
				"ACTIVE_DATE_FORMAT" => "d.m.Y",
				"SET_TITLE" => "N",
				"SET_STATUS_404" => "N",
				"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
				"ADD_SECTIONS_CHAIN" => "N",
				"HIDE_LINK_WHEN_NO_DETAIL" => "N",
				"PARENT_SECTION" => "",
				"PARENT_SECTION_CODE" => "",
				"INCLUDE_SUBSECTIONS" => "Y",
				"PAGER_TEMPLATE" => ".default",
				"DISPLAY_TOP_PAGER" => "N",
				"DISPLAY_BOTTOM_PAGER" => "N",
				"PAGER_TITLE" => "Новости",
				"PAGER_SHOW_ALWAYS" => "N",
				"PAGER_DESC_NUMBERING" => "N",
				"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
				"PAGER_SHOW_ALL" => "N",
				"VIEW_TYPE" => "list",
				"BIG_BLOCK" => "Y",
				"IMAGE_POSITION" => "left",
				"COUNT_IN_LINE" => "2",
				"TITLE" => ($arParams["BLOCK_SERVICES_NAME"] ? $arParams["BLOCK_SERVICES_NAME"] : GetMessage("SERVICES_TITLE")),
			),
			$component, array("HIDE_ICONS" => "Y")
		);?>
	<?endif;?>	
</div>	

<?/*Вывод привязанных элементов портфолио*/?>
	
	
	
<?/*Видео на детальной странице*/?>
<?if($arVideo):?>
<div class="wraps hidden_print">
			<hr>
			<h4>
				<?=($arParams["TAB_VIDEO_NAME"] ? $arParams["TAB_VIDEO_NAME"] : GetMessage("VIDEO_TAB"));?>
				<?if(count($arVideo) > 1):?>
					<span class="count empty">&nbsp;(<?=count($arVideo)?>)</span>
				<?endif;?>
			</h4>
			<div class="video_block">
				<?if(count($arVideo) > 1):?>
					<table class="video_table">
						<tbody>
							<?foreach($arVideo as $v => $value):?>
								<?if(($v + 1) % 2):?>
									<tr>
								<?endif;?>
								<td width="50%"><?=str_replace('src=', 'width="458" height="257" src=', str_replace(array('width', 'height'), array('data-width', 'data-height'), $value));?></td>
								<?if(!(($v + 1) % 2)):?>
									</tr>
								<?endif;?>
							<?endforeach;?>
							<?if(($v + 1) % 2):?>
								</tr>
							<?endif;?>
						</tbody>
					</table>
				<?else:?>
					<?=$arVideo[0]?>
				<?endif;?>
			</div>
</div>
<?endif;?>
<?/*Видео на детальной странице*/?>

<?/*Галерея фото*/?>
<?if($arResult['MORE_PHOTO_1']):?>
<div class="wraps galerys-block">
<hr>
<h4><?=($arParams["BLOCK_ADDITIONAL_GALLERY_NAME"] ? $arParams["BLOCK_ADDITIONAL_GALLERY_NAME"] : GetMessage("ADDITIONAL_GALLERY_TITLE"))?></h4>
							<div class="gallery-block">
							<div class="gallery-wrapper">
								<div class="inner">
									<?if(count($arResult["MORE_PHOTO_1"]) > 1):?>
										<div class="small-gallery-wrapper">
											<div style="padding-bottom:40px;" class="thmb1 flexslider unstyled small-gallery center-nav" data-plugin-options='{"slideshow": "false", "useCSS": true, "animation": "slide", "animationLoop": true, "itemWidth": 60, "itemMargin": 20, "minItems": 1, "maxItems": 9, "slide_counts": 1, "asNavFor": ".gallery-wrapper .bigs"}' id="carousel1">
												<ul class="slides items">
													<?foreach($arResult["MORE_PHOTO_1"] as $arPhoto):?>
														<li class="item">
															<img class="img-responsive inline lazy" border="0" src="<?=$arPhoto["THUMB"]["src"]?>" title="<?=$arPhoto['TITLE']?>" alt="<?=$arPhoto['ALT']?>" />
														</li>
													<?endforeach;?>
												</ul>

											</div>
										</div>
									<?endif;?>
									<div class="flexslider dark bigs big_slider color-controls" id="slider" data-plugin-options='{"animation": "slide", "useCSS": true, "directionNav": true, "controlNav" :true, "animationLoop": true, "slideshow": false, "sync": ".gallery-wrapper .small-gallery", "counts": [1, 1, 1]}'>
										<ul class="slides items">
											<?foreach($arResult['MORE_PHOTO_1'] as $i => $arPhoto):?>
												<li class="col-md-12 item">
												
														<img src="<?=$arPhoto['PREVIEW']['src']?>" class="img-responsive inline lazy" title="<?=$arPhoto['TITLE']?>" alt="<?=$arPhoto['ALT']?>" />
														<p><?=$arPhoto['TITLE']?></p>
														
												

												</li>

											<?endforeach;?>
										</ul>
									</div>
								</div>
							</div>
							</div>



</div>
<?endif;?>
<?/*Галерея фото*/?>

						
						
					
						
					</div>
				
			<?endif;?>
	

	</div>
    




<?/*Смотрите также*/?>
<?if($arResult["ASSOCIATED"]):?>
			<h4><?=($arParams["DETAIL_ASSOCIATED_TITLE"] ? $arParams["DETAIL_ASSOCIATED_TITLE"] : GetMessage("DETAIL_ASSOCIATED_TITLE"))?></h4>
			<?$GLOBALS['arrFilterAccess1'] = array("ID" => $arResult["PROPERTIES"]["ASSOCIATED"]["VALUE"],array("!ID" => $arResult["ID"]));?>
			
			
		 <? $APPLICATION->IncludeComponent(
            "bitrix:catalog.section",
            isMobilelat() ? "catalog_blockcolors_mobile111022" : "catalog_blockcolors",
            Array(
                "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
                "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                "ELEMENT_SORT_FIELD" => "SORT",
                "ELEMENT_SORT_ORDER" => "asc",
                "FILTER_NAME" => "arrFilterAccess1",
                "SHOW_ALL_WO_SECTION" => "Y",
                "SECTION_ID" => '',
                "SECTION_CODE" => '',
                "USE_REGION" => "N",
                "STORES" => "",
                "SHOW_UNABLE_SKU_PROPS" => "Y",
                "AJAX_REQUEST" => "N",
                "INCLUDE_SUBSECTIONS" => "N",
                "PAGE_ELEMENT_COUNT" => "20",
                "LINE_ELEMENT_COUNT" => "4",
                "TYPE_SKU" => $arTheme["TYPE_SKU"]["VALUE"],
                "PROPERTY_CODE" => array(
                    0 => "BRAND",
                    1 => "PROP_2065",
                    2 => "PROP_159",
                    3 => "PROP_162",
                    4 => "PROP_2052",
                    5 => "PROP_2049",
                    6 => "COLOR_REF2",
                    7 => "PROP_2083",
                    8 => "PROP_2027",
                    9 => "PROP_2026",
                    10 => "PROP_2033",
                    11 => "PROP_2054",
                    12 => "PROP_2055",
                    13 => "PROP_2069",
                    14 => "PROP_2062",
                    15 => "PROP_2061",
                    16 => "CML2_LINK",
                    17 => "",
                ),
                "SHOW_ARTICLE_SKU" => "N",
                "SHOW_MEASURE_WITH_RATIO" => "N",
                "OFFERS_FIELD_CODE" => $arParams['OFFERS_FIELD_CODE'],
                "OFFERS_PROPERTY_CODE" => $arParams['OFFERS_PROPERTY_CODE'],
                "OFFERS_SORT_FIELD" => "sort",
                "OFFERS_SORT_ORDER" => "asc",
                "OFFERS_SORT_FIELD2" => "name",
                "OFFERS_SORT_ORDER2" => "asc",
                "OFFER_TREE_PROPS" => array(
                    0 => "SIZES",
                    1 => "COLOR_REF",
                    2 => "DLINA",
                ),
                "OFFERS_LIMIT" => "300",
                "SECTION_URL" => "",
                "DETAIL_URL" => "",
                "BASKET_URL" => "/basket/",
                "ACTION_VARIABLE" => "action",
                "PRODUCT_ID_VARIABLE" => "id",
                "PRODUCT_QUANTITY_VARIABLE" => "quantity",
                "PRODUCT_PROPS_VARIABLE" => "prop",
                "SECTION_ID_VARIABLE" => "SECTION_ID",
                "SET_LAST_MODIFIED" => "N",
                "AJAX_MODE" => "N",
                "AJAX_OPTION_JUMP" => "N",
                "AJAX_OPTION_STYLE" => "N",
                "AJAX_OPTION_HISTORY" => "N",
                "CACHE_TYPE" => "N",
                "CACHE_TIME" => "3600000",
                "CACHE_GROUPS" => "N",
                "CACHE_FILTER" => "Y",
                "META_KEYWORDS" => "-",
                "META_DESCRIPTION" => "-",
                "BROWSER_TITLE" => "-",
                "ADD_SECTIONS_CHAIN" => "N",
                "HIDE_NOT_AVAILABLE" => "N",
                "HIDE_NOT_AVAILABLE_OFFERS" => "N",
                "DISPLAY_COMPARE" => "N",
                "SET_TITLE" => "N",
                "SET_STATUS_404" => "N",
                "SHOW_404" => "N",
                "MESSAGE_404" => "",
                "PRICE_CODE" => array(
                    0 => "BASE",
                ),
                "USE_PRICE_COUNT" => "Y",
                "SHOW_PRICE_COUNT" => "1",
                "PRICE_VAT_INCLUDE" => "Y",
                "USE_PRODUCT_QUANTITY" => "Y",
                "OFFERS_CART_PROPERTIES" => array(
                    0 => "DLINA",
                ),
                "DISPLAY_TOP_PAGER" => "N",
                "DISPLAY_BOTTOM_PAGER" => "N",
                "PAGER_TITLE" => "Товары",
                "PAGER_SHOW_ALWAYS" => "N",
                "PAGER_TEMPLATE" => "main",
                "PAGER_DESC_NUMBERING" => "N",
                "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
                "PAGER_SHOW_ALL" => "N",
                "AJAX_OPTION_ADDITIONAL" => "",
                "ADD_CHAIN_ITEM" => "N",
                "SHOW_QUANTITY" => "Y",
                "SHOW_QUANTITY_COUNT" => "Y",
                "SHOW_DISCOUNT_PERCENT" => "Y",
                "SHOW_DISCOUNT_TIME" => "N",
                "SHOW_OLD_PRICE" => "Y",
                "CONVERT_CURRENCY" => "Y",
                "CURRENCY_ID" => "RUB",
                "USE_STORE" => "N",
                "MAX_AMOUNT" => "20",
                "MIN_AMOUNT" => "10",
                "USE_MIN_AMOUNT" => "N",
                "USE_ONLY_MAX_AMOUNT" => "Y",
                "DISPLAY_WISH_BUTTONS" => "N",
                "LIST_DISPLAY_POPUP_IMAGE" => "Y",
                "DEFAULT_COUNT" => "1",
                "SHOW_MEASURE" => "Y",
                "SHOW_HINTS" => "Y",
                "OFFER_HIDE_NAME_PROPS" => "N",
                "SECTIONS_LIST_PREVIEW_PROPERTY" => "UF_SECTION_DESCR",
                "SHOW_SECTION_LIST_PICTURES" => "Y",
                "USE_MAIN_ELEMENT_SECTION" => "N",
                "ADD_PROPERTIES_TO_BASKET" => "Y",
                "PARTIAL_PRODUCT_PROPERTIES" => "Y",
                "PRODUCT_PROPERTIES" => array(
                ),
                "SALE_STIKER" => "SALE_TEXT",
                "STIKERS_PROP" => "HIT",
                "SHOW_RATING" => "N",
                "COMPONENT_TEMPLATE" => "catalog_blockcolors_slide_editor",
                "SECTION_USER_FIELDS" => array(
                    0 => "",
                    1 => "",
                ),
                "CUSTOM_FILTER" => "{\"CLASS_ID\":\"CondGroup\",\"DATA\":{\"All\":\"AND\",\"True\":\"True\"},\"CHILDREN\":[]}",
                "ELEMENT_SORT_FIELD2" => "id",
                "ELEMENT_SORT_ORDER2" => "desc",
                "BACKGROUND_IMAGE" => "-",
                "SEF_MODE" => "N",
                "SET_BROWSER_TITLE" => "Y",
                "SET_META_KEYWORDS" => "Y",
                "SET_META_DESCRIPTION" => "Y",
                "PAGER_BASE_LINK_ENABLE" => "N",
                "COMPATIBLE_MODE" => "Y",
                "DISABLE_INIT_JS_IN_COMPONENT" => "N"
        ),
        $component,
        array(
            "HIDE_ICONS" => $isAjax
        )
); ?>
	<?endif;?>
<?/*Смотрите также*/?>






<?/*С этим товаром покупают*/?>
<!--    --><?//echo "<pre>"; print_r('С этим товаром покупают'); echo "</pre>";?>
<?if($arResult["EXPANDABLES"]):?>
<h4><?=($arParams["DETAIL_EXPANDABLES_TITLE"] ? $arParams["DETAIL_EXPANDABLES_TITLE"] : GetMessage("DETAIL_EXPANDABLES_TITLE"))?></h4>
<?$GLOBALS['arrFilterAccess'] = array("ID" => $arResult["PROPERTIES"]["EXPANDABLES"]["VALUE"],array("!ID" => $arResult["ID"]));?>
			
<!--        --><?//echo "<pre>"; print_r($arParams); echo "</pre>";?>
			
		 <? $APPLICATION->IncludeComponent(
                "bitrix:catalog.section",
                isMobilelat() ? "catalog_blockcolors_mobile111022" : "catalog_blockcolors",
                Array(
                    "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
                    "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                    "ELEMENT_SORT_FIELD" => "SORT",
                    "ELEMENT_SORT_ORDER" => "asc",
                    "FILTER_NAME" => "arrFilterAccess",
                    "SHOW_ALL_WO_SECTION" => "Y",
                    "SECTION_ID" => '',
                    "SECTION_CODE" => '',
                    "USE_REGION" => $arParams["USE_REGION"],
                    "STORES" => "",
                    "SHOW_UNABLE_SKU_PROPS" => "Y",
                    "AJAX_REQUEST" => "N",
                    "INCLUDE_SUBSECTIONS" => "N",
                    "PAGE_ELEMENT_COUNT" => "20",
                    "LINE_ELEMENT_COUNT" => "4",
                    "TYPE_SKU" => $arParams["TYPE_SKU"],
                    "PROPERTY_CODE" => $arParams["PROPERTY_CODE"],
                    "SHOW_ARTICLE_SKU" => "Y",
                    "SHOW_MEASURE_WITH_RATIO" => "N",
                    "OFFERS_FIELD_CODE" => $arParams['OFFERS_FIELD_CODE'],
                    "OFFERS_PROPERTY_CODE" => $arParams['OFFERS_PROPERTY_CODE'],
                    "OFFERS_SORT_FIELD" => "sort",
                    "OFFERS_SORT_ORDER" => "asc",
                    "OFFERS_SORT_FIELD2" => "name",
                    "OFFERS_SORT_ORDER2" => "asc",
                    "OFFER_TREE_PROPS" => $arParams['OFFER_TREE_PROPS'],
                    "OFFERS_LIMIT" => "300",
                    "SECTION_URL" => "",
                    "DETAIL_URL" => "",
                    "BASKET_URL" => "/basket/",
                    "ACTION_VARIABLE" => "action",
                    "PRODUCT_ID_VARIABLE" => "id",
                    "PRODUCT_QUANTITY_VARIABLE" => "quantity",
                    "PRODUCT_PROPS_VARIABLE" => "prop",
                    "SECTION_ID_VARIABLE" => "SECTION_ID",
                    "SET_LAST_MODIFIED" => "N",
                    "AJAX_MODE" => "N",
                    "AJAX_OPTION_JUMP" => "N",
                    "AJAX_OPTION_STYLE" => "N",
                    "AJAX_OPTION_HISTORY" => "N",
                    "CACHE_TYPE" => "N",
                    "CACHE_TIME" => "3600000",
                    "CACHE_GROUPS" => "N",
                    "CACHE_FILTER" => "Y",
                    "META_KEYWORDS" => "-",
                    "META_DESCRIPTION" => "-",
                    "BROWSER_TITLE" => "-",
                    "ADD_SECTIONS_CHAIN" => "N",
                    "HIDE_NOT_AVAILABLE" => "N",
                    "HIDE_NOT_AVAILABLE_OFFERS" => "N",
                    "DISPLAY_COMPARE" => "N",
                    "SET_TITLE" => "N",
                    "SET_STATUS_404" => "N",
                    "SHOW_404" => "N",
                    "MESSAGE_404" => "",
                    "PRICE_CODE" => array(
                        0 => "BASE",
                    ),
                    "USE_PRICE_COUNT" => "Y",
                    "SHOW_PRICE_COUNT" => "1",
                    "PRICE_VAT_INCLUDE" => "Y",
                    "USE_PRODUCT_QUANTITY" => "Y",
                    "OFFERS_CART_PROPERTIES" => array(
                        0 => "DLINA",
                    ),
                    "DISPLAY_TOP_PAGER" => "N",
                    "DISPLAY_BOTTOM_PAGER" => "N",
                    "PAGER_TITLE" => "Товары",
                    "PAGER_SHOW_ALWAYS" => "N",
                    "PAGER_TEMPLATE" => "main",
                    "PAGER_DESC_NUMBERING" => "N",
                    "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
                    "PAGER_SHOW_ALL" => "N",
                    "AJAX_OPTION_ADDITIONAL" => "",
                    "ADD_CHAIN_ITEM" => "N",
                    "SHOW_QUANTITY" => "Y",
                    "SHOW_QUANTITY_COUNT" => "Y",
                    "SHOW_DISCOUNT_PERCENT" => "Y",
                    "SHOW_DISCOUNT_TIME" => "N",
                    "SHOW_OLD_PRICE" => "Y",
                    "CONVERT_CURRENCY" => "Y",
                    "CURRENCY_ID" => "RUB",
                    "USE_STORE" => "N",
                    "MAX_AMOUNT" => "20",
                    "MIN_AMOUNT" => "10",
                    "USE_MIN_AMOUNT" => "N",
                    "USE_ONLY_MAX_AMOUNT" => "Y",
                    "DISPLAY_WISH_BUTTONS" => "N",
                    "LIST_DISPLAY_POPUP_IMAGE" => "Y",
                    "DEFAULT_COUNT" => "1",
                    "SHOW_MEASURE" => "Y",
                    "SHOW_HINTS" => "Y",
                    "OFFER_HIDE_NAME_PROPS" => "N",
                    "SECTIONS_LIST_PREVIEW_PROPERTY" => "UF_SECTION_DESCR",
                    "SHOW_SECTION_LIST_PICTURES" => "Y",
                    "USE_MAIN_ELEMENT_SECTION" => "N",
                    "ADD_PROPERTIES_TO_BASKET" => "Y",
                    "PARTIAL_PRODUCT_PROPERTIES" => "Y",
                    "PRODUCT_PROPERTIES" => array(
                    ),
                    "SALE_STIKER" => "SALE_TEXT",
                    "STIKERS_PROP" => "HIT",
                    "SHOW_RATING" => "N",
                    "COMPONENT_TEMPLATE" => "catalog_blockcolors_slide_editor",
                    "SECTION_USER_FIELDS" => array(
                        0 => "",
                        1 => "",
                    ),
                    "CUSTOM_FILTER" => "{\"CLASS_ID\":\"CondGroup\",\"DATA\":{\"All\":\"AND\",\"True\":\"True\"},\"CHILDREN\":[]}",
                    "ELEMENT_SORT_FIELD2" => "id",
                    "ELEMENT_SORT_ORDER2" => "desc",
                    "BACKGROUND_IMAGE" => "-",
                    "SEF_MODE" => "N",
                    "SET_BROWSER_TITLE" => "Y",
                    "SET_META_KEYWORDS" => "Y",
                    "SET_META_DESCRIPTION" => "Y",
                    "PAGER_BASE_LINK_ENABLE" => "N",
                    "COMPATIBLE_MODE" => "Y",
                    "DISABLE_INIT_JS_IN_COMPONENT" => "N"
            ),
            $component,
            array(
                "HIDE_ICONS" => $isAjax
            )
); ?>
	<?endif;?>
<?/*С этим товаром покупают*/?>

<?/*Услуги*/?>
<?if($arResult["SERVICES"]):?>

		<?global $arrSaleFilter; $arrSaleFilter = array("ID" => $arResult["PROPERTIES"]["SERVICES"]["VALUE"], array("LOGIC" => "OR", array("PROPERTY_LINK_REGION" => false), array("PROPERTY_LINK_REGION" => $regionID)));?>
		
		<?$APPLICATION->IncludeComponent(
			"bitrix:news.list",
			"items-services",
			array(
				"IBLOCK_TYPE" => "aspro_next_content",
				"IBLOCK_ID" => $arResult["PROPERTIES"]["SERVICES"]["LINK_IBLOCK_ID"],
				"NEWS_COUNT" => "20",
				"SORT_BY1" => "SORT",
				"SORT_ORDER1" => "ASC",
				"SORT_BY2" => "ID",
				"SORT_ORDER2" => "DESC",
				"FILTER_NAME" => "arrSaleFilter",
				"FIELD_CODE" => array(
					0 => "NAME",
					1 => "PREVIEW_TEXT",
					3 => "PREVIEW_PICTURE",
					4 => "",
				),
				"PROPERTY_CODE" => array(),
				"CHECK_DATES" => "Y",
				"DETAIL_URL" => "",
				"AJAX_MODE" => "N",
				"AJAX_OPTION_JUMP" => "N",
				"AJAX_OPTION_STYLE" => "Y",
				"AJAX_OPTION_HISTORY" => "N",
				'CACHE_TYPE' => 'N',
				'CACHE_TIME' => '172800',
				"CACHE_FILTER" => "Y",
				"CACHE_GROUPS" => "N",
				"PREVIEW_TRUNCATE_LEN" => "",
				"ACTIVE_DATE_FORMAT" => "d.m.Y",
				"SET_TITLE" => "N",
				"SET_STATUS_404" => "N",
				"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
				"ADD_SECTIONS_CHAIN" => "N",
				"HIDE_LINK_WHEN_NO_DETAIL" => "N",
				"PARENT_SECTION" => "",
				"PARENT_SECTION_CODE" => "",
				"INCLUDE_SUBSECTIONS" => "Y",
				"PAGER_TEMPLATE" => ".default",
				"DISPLAY_TOP_PAGER" => "N",
				"DISPLAY_BOTTOM_PAGER" => "Y",
				"PAGER_TITLE" => "Новости",
				"PAGER_SHOW_ALWAYS" => "N",
				"PAGER_DESC_NUMBERING" => "N",
				"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
				"PAGER_SHOW_ALL" => "N",
				"VIEW_TYPE" => "list",
				"BIG_BLOCK" => "Y",
				"IMAGE_POSITION" => "left",
				"COUNT_IN_LINE" => "2",
				"TITLE" => ($arParams["BLOCK_SERVICES_NAME"] ? $arParams["BLOCK_SERVICES_NAME"] : GetMessage("SERVICES_TITLE")),
			),
			$component, array("HIDE_ICONS" => "Y")
		);?>
	<?endif;?>
<?/*Услуги*/?>


</div>


		

<script type="text/javascript">
	BX.message({
		QUANTITY_AVAILIABLE: '<? echo COption::GetOptionString("aspro.next", "EXPRESSION_FOR_EXISTS", GetMessage("EXPRESSION_FOR_EXISTS_DEFAULT"), SITE_ID); ?>',
		QUANTITY_NOT_AVAILIABLE: '<? echo COption::GetOptionString("aspro.next", "EXPRESSION_FOR_NOTEXISTS", GetMessage("EXPRESSION_FOR_NOTEXISTS"), SITE_ID); ?>',
		ADD_ERROR_BASKET: '<? echo GetMessage("ADD_ERROR_BASKET"); ?>',
		ADD_ERROR_COMPARE: '<? echo GetMessage("ADD_ERROR_COMPARE"); ?>',
		ONE_CLICK_BUY: '<? echo GetMessage("ONE_CLICK_BUY"); ?>',
		MORE_TEXT_BOTTOM: '<? echo GetMessage("MORE_TEXT_BOTTOM"); ?>',
		TYPE_SKU: '<? echo $arParams['TYPE_SKU']; ?>',
		HAS_SKU_PROPS: '<? echo ($arResult['OFFERS_PROP'] ? 'Y' : 'N'); ?>',
		SITE_ID: '<? echo SITE_ID; ?>'
	})
</script>

<?if($templateData['BRAND_ITEM'] || (\Bitrix\Main\ModuleManager::isModuleInstalled("sale") && (!isset($arParams['USE_BIG_DATA']) || $arParams['USE_BIG_DATA'] != 'N'))):?>
	</div>
<?endif;?>
        <?
        if(isset($arResult['OFFERS']) && count($arResult['OFFERS'])>0){
            $offer_unit_yes = false;
            foreach ($arResult['OFFERS'] as $key=>$offer_u){
                if(!empty( $offer_u["PROPERTIES"]["UNIT_KOEF"]["VALUE"]))
                    $offer_unit_yes = true;
                $arOfferUnit[$offer_u["ID"]]["ID"] = $offer_u["ID"];
                $arOfferUnit[$offer_u["ID"]]["UNITS"] = $offer_u["PROPERTIES"]["UNIT_KOEF"];
                $arOfferUnit[$offer_u["ID"]]["BASE_OFFER_MEASURE"] = $offer_u['ITEM_MEASURE'];
                $arOfferUnit[$offer_u["ID"]]["PRICES"] = $offer_u["PRICES"];
                $arOfferUnit[$offer_u["ID"]]["MIN_PRICE"] = $offer_u["MIN_PRICE"];
                $arOfferUnit[$offer_u["ID"]]["PRICE_MATRIX"] = $offer_u["PRICE_MATRIX"];
                $arOfferUnit[$offer_u["ID"]]["ITEM_PRICES"] = $offer_u["ITEM_PRICES"];
				 $arOfferUnit[$offer_u["ID"]]["QUANTITY"] = $offer_u["QUANTITY"];
                $arOfferUnit[$offer_u["ID"]]["BASE_KOEFF_UNIT"] = $offer_u['DISPLAY_PROPERTIES']['BASE_KOEF']['DESCRIPTION'];

                foreach ($offer_u["PROPERTIES"] as $key_sku => $sku_property){
                    if(array_key_exists($key_sku, $arResult['SKU_PROPS'])){

                        if(isset($arResult['SKU_PROPS'][$key_sku]['XML_MAP']))
                            $arOfferUnit[$offer_u["ID"]]["SKU_PROPS"][] = $sku_property['ID'].'_'.$arResult['SKU_PROPS'][$key_sku]['XML_MAP'][$sku_property['~VALUE']];
                        else
                            $arOfferUnit[$offer_u["ID"]]["SKU_PROPS"][] = $sku_property['ID'].'_'.$arResult['SKU_PROPS'][$key_sku]['VALUES'][$sku_property["VALUE_ENUM_ID"]]["ID"];
                    }
                }
            }
            $BX_BASCKET_OBJ = $arItemIDs["strObName"];

        }else{
            $offer_unit_yes = false;
            $arOfferUnit[$arResult["ID"]]["ID"] = $arResult["ID"];
            $arOfferUnit[$arResult["ID"]]["UNITS"] = $arResult["PROPERTIES"]["UNIT_KOEF"];
            $arOfferUnit[$arResult["ID"]]["BASE_OFFER_MEASURE"] = $arResult['ITEM_MEASURE'];
            $arOfferUnit[$arResult["ID"]]["PRICES"] = $arResult["PRICES"];
            $arOfferUnit[$arResult["ID"]]["MIN_PRICE"] = $arResult["MIN_PRICE"];
            $arOfferUnit[$arResult["ID"]]["PRICE_MATRIX"] = $arResult["PRICE_MATRIX"];
            $arOfferUnit[$arResult["ID"]]["ITEM_PRICES"] = $arResult["ITEM_PRICES"];
            $arOfferUnit[$arResult["ID"]]["QUANTITY"] = $arResult['PRODUCT']['QUANTITY'];
            $arOfferUnit[$arResult["ID"]]["BASE_KOEFF_UNIT"] = $arResult['DISPLAY_PROPERTIES']['BASE_KOEF']['DESCRIPTION'];
            $BX_BASCKET_OBJ = $arItemIDs;

        }?>
       <?
        if(is_array($arOfferUnit)) {
            $APPLICATION->IncludeComponent("maxyss:measure_unit", "aspro_element_tp", Array(
                "BX_BASCKET_OBJ" => $arItemIDs["strObName"],
                "PRODUCT_ID" => $arResult['ID'],
                "OFFERS_UNIT_YES" => $offer_unit_yes,
                "OFFERS_UNIT" => $arOfferUnit,
                "CACHE_TIME" => $arParams["CACHE_TIME"],
                "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                "MAIN_MEASURE_UNIT" => $actualItem["ITEM_MEASURE"]["TITLE"],
                "MEASURE_BLOCK_SELECTOR" => ".counter_block",
                "MEASURE_INPUT_SELECTOR" => ".counter_block .text",
                "MEASURE_RESULT" => $arResult["PROPERTIES"]["UNIT_KOEF"],
                "ASPRO_MEASURE" => "Y",    // Aspro
            ),
                $component,
                array(
                    "ACTIVE_COMPONENT" => "Y"
                )
            );
        }?>