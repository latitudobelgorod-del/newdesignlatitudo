<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>

<?global $arRegion;
  $regionID = ($arRegion ? $arRegion['ID'] : '');?>

	
<style>


.catalog-detail__sku {
	margin: 25px 0;
}
.catalog-detail-sku__title {
	margin-bottom: 1em;
	font-size: 15px;
	line-height: 18px;
	font-weight: 500;
}
.sku-list {
	display: flex;
	flex-wrap: wrap;
	margin: -2px -2px -2px -8px;
	padding: 2px;
	max-height: 114px;
	overflow: hidden;
}
.sku-list.active {
	height: auto;
	max-height: none;
}
.sku-list-more {
	display: none;
	margin-top: 16px;
	color: #165c7d;
	font-size: 14px;
	font-weight: 500;
	line-height: 16px;
	cursor: pointer;
	-webkit-tap-highlight-color: transparent;
}
.sku-list__item {
	position:relative;
	margin: 0 0 12px 8px;
}
.sku-list__item img {
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;
	border-radius: 5px;
	width:45px;
	height:45px;
}
.sku-list-item {
	position: relative;
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;
	border-radius: 5px;
	/*overflow: hidden;*/
}

.sku-list-item:hover:after {
	content: "";
	position: absolute;
	z-index: -1;
	top: -2px;
	right: -2px;
	bottom: -2px;
	left: -2px;
	border: 1px solid #bababa;
	-webkit-border-radius: 7px;
	-moz-border-radius: 7px;
	border-radius: 7px;
}

.sku-list-item.active:after {
	content: "";
	position: absolute;
	z-index: -1;
	top: -2px;
	right: -2px;
	bottom: -2px;
	left: -2px;
	border: 1px solid #000;
	-webkit-border-radius: 7px;
	-moz-border-radius: 7px;
	border-radius: 7px;
}
</style>

<?
$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/bitrix/components/maxyss/measure_unit/templates/aspro_list_tp/style.css');
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/bitrix/components/maxyss/measure_unit/templates/aspro_list_tp/script.js'); 
?>
<?if( count( $arResult["ITEMS"] ) >= 1 ){?>
<div class="catalog-detail-sku__title">Вариации:</div>
<div  class="catalog-detail-sku__list">
		<div class="sku-list">
	  <?foreach($arResult["ITEMS"] as $i => $arItem){?>
			
				<div class="sku-list__item sku-list-item">
	
				
					<?
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_EDIT"));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')));			
					$elementName = ((isset($arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) && $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE']) ? $arItem['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] : $arItem['NAME']);					
					?>
					
	
					<div class="sku-list-item__inner">
					
					
				
						
						
						<a href="<?=$arItem["DETAIL_PAGE_URL"]?>" title="<?=$elementName;?>" class="thumb shine" id="<? echo $arItemIDs["ALL_ITEM_IDS"]['PICT']; ?>">
						
								<link href="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" />
						
                     
                            <?if( !empty($arItem["PREVIEW_PICTURE"]) ):?>
                             <img  src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>"  alt="<?=$elementName;?>" title="<?=$elementName;?>"  />
                            <?elseif( !empty($arItem["DETAIL_PICTURE"])):?>
                                <?$img = CFile::ResizeImageGet($arItem["DETAIL_PICTURE"], array( "width" => 170, "height" => 170 ), BX_RESIZE_IMAGE_PROPORTIONAL,true );?>
                                <img   src="<?=$img["src"]?>" alt="<?=$elementName;?>" title="<?=$elementName;?>"  />
                            <?else:?>
                                <img  src="/images/no_photo_medium.png" alt="<?=$elementName;?>" title="<?=$elementName;?>" />
                            <?endif;?>
						</a>						
                   
                        	  
					
							
						
</div>

					
				</div><?/*catalog_item_wrapp*/?> 
				
	
		
		<?}?>		
		</div><?/*item_block*/?>
</div>
		<?}?>	
	
<div class="clear"></div>