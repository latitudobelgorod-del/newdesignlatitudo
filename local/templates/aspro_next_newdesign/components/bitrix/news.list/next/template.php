<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>
<div class="tizers_block">
	<div class="row">
		<?foreach($arResult["ITEMS"] as $arItem){
			$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
			$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
			$name=strip_tags($arItem["~NAME"], "<br><br/>");
			$desc=strip_tags($arItem["~PREVIEW_TEXT"], "<br><br/>");
			?>
			<div class="col-md-3 col-sm-12 col-xs-12">
			<div id="<?=$this->GetEditAreaId($arItem['ID']);?>" class="item">

					<?if ($desc):?>
					<div class="tizers_block_desc">
					
							<?if($arItem["PROPERTIES"]["LINK"]["VALUE"]):?>
								<a class="name" href="<?=$arItem["PROPERTIES"]["LINK"]["VALUE"]?>">
							<?endif;?>
							<img src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" alt="<?=$name;?>" title="<?=$name;?>"/>
							<?if($arItem["PROPERTIES"]["LINK"]["VALUE"]):?>
								</a>
							<?endif;?>
						
						<div class="title">
						<?if($arItem["PROPERTIES"]["LINK"]["VALUE"]):?>
							<a class="name" href="<?=$arItem["PROPERTIES"]["LINK"]["VALUE"]?>">
						<?endif;?>
							<div style=""><?=$name;?></div>
								
						<?if($arItem["PROPERTIES"]["LINK"]["VALUE"]):?>
							</a>
						<?endif;?>
					</div>
					<div class="title"><div><?=$desc;?></div></div>
					</div>
					
					<?else:?>

							<?if($arItem["PREVIEW_PICTURE"]["SRC"]){?>
							<div class="img">
								<?if($arItem["PROPERTIES"]["LINK"]["VALUE"]):?>
									<a class="name" href="<?=$arItem["PROPERTIES"]["LINK"]["VALUE"]?>">
								<?endif;?>
								<img src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>" alt="<?=$name;?>" title="<?=$name;?>"/>
								<?if($arItem["PROPERTIES"]["LINK"]["VALUE"]):?>
									</a>
								<?endif;?>
							</div>
							<?}?>
					
						<div class="title">
						<?if($arItem["PROPERTIES"]["LINK"]["VALUE"]):?>
						<a class="name" href="<?=$arItem["PROPERTIES"]["LINK"]["VALUE"]?>"><?endif;?>
						<div><?=$name;?></div>
						<?if($arItem["PROPERTIES"]["LINK"]["VALUE"]):?>	
						</a>
						<?endif;?>
						</div>
					<?endif;?>
				</div>
				
			</div>
	
		<?}?>
	</div>
</div>