<?
global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');?>
<?if($arResult["ITEMS"]):?>
	<div class="brand_wrapper">
		
		<div class="row margin0">
			<div class="brands_list">
				<?foreach( $arResult["ITEMS"] as $arItem ){
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
					?>
					<?$list_brands=array();
						foreach($arItem["PROPERTIES"]["LINK_REGION"]["VALUE"] as $portfolio):?>
								<?$res = CIBlockElement::GetByID($portfolio);?>
								<?if($ar_res = $res->GetNext())?>
								 <?$list_brands[] = $ar_res["ID"];?>
								<?endforeach;?>
								 <?if (in_array($regionID, $list_brands)) :?>
					<div class="col-lg-3 col-md-4 col-sm-4 col-xs-6 item">
						<div id="<?=$this->GetEditAreaId($arItem['ID']);?>">
							<a href="<?=$arItem["DETAIL_PAGE_URL"]?>">
								<?if( is_array($arItem["PREVIEW_PICTURE"]) ){?>
									<img src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" alt="<?=($arItem["PREVIEW_PICTURE"]["ALT"]?$arItem["PREVIEW_PICTURE"]["ALT"]:$arItem["NAME"]);?>" title="<?=($arItem["PREVIEW_PICTURE"]["TITLE"]?$arItem["PREVIEW_PICTURE"]["TITLE"]:$arItem["NAME"]);?>" />
								<?}elseif( is_array($arItem["DETAIL_PICTURE"]) ){?>
									<img src="<?=$arItem["DETAIL_PICTURE"]["SRC"]?>" alt="<?=($arItem["DETAIL_PICTURE"]["ALT"]?$arItem["DETAIL_PICTURE"]["ALT"]:$arItem["NAME"]);?>" title="<?=($arItem["DETAIL_PICTURE"]["TITLE"]?$arItem["DETAIL_PICTURE"]["TITLE"]:$arItem["NAME"]);?>" />
								<?}else{?>
									<span><?=$arItem["NAME"]?></span>
								<?}?>
							</a>
						
						</div>
						
							
						
					</div>
						<?endif;?>
					
				<?}?>
			</div>
		</div>
		<?if($arParams["TITLE_BLOCK"] || $arParams["TITLE_BLOCK_ALL"]):?>
			<div class="top_block">

				<a href="<?=SITE_DIR.$arParams["ALL_URL"];?>"><?=$arParams["TITLE_BLOCK_ALL"] ;?></a>
			</div>
		<?endif;?>
	</div>
<?endif;?>