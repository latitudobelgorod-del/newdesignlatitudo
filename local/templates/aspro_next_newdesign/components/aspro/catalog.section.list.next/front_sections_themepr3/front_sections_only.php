<div class="list items">
	
		<?foreach(array_chunk($arResult['SECTIONS'], 3) as $arSect):
			
			$this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_EDIT"));
			$this->AddDeleteAction($arSection['ID'], $arSection['DELETE_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));?>
			<div class="row margin0 flexbox">
				  <?foreach ($arSect as $arSection):?>
			<div class="col-md-4 col-sm-4 col-xs-12 front-sect">
			
				<div class="item" id="<?=$this->GetEditAreaId($arSection['ID']);?>">
					<div class="img shine">
						<?if($arSection["PICTURE"]["SRC"]):?>
							<?$img = CFile::ResizeImageGet($arSection["PICTURE"]["ID"], array( "width" => 600, "height" => 350 ), BX_RESIZE_IMAGE_EXACT, true );?>
							<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb"><img  src="<?=$img["src"]?>" alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" /></a>
						<?elseif($arSection["~PICTURE"]):?>
							<?$img = CFile::ResizeImageGet($arSection["~PICTURE"], array( "width" => 600, "height" => 350 ), BX_RESIZE_IMAGE_EXACT, true );?>
							<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb"><img  src="<?=$img["src"]?>" alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" /></a>
						<?else:?>
							<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb"><img class="lazy" data-src="<?=SITE_TEMPLATE_PATH?>/images/no_photo_medium.png" alt="<?=$arSection["NAME"]?>" title="<?=$arSection["NAME"]?>" height="90" /></a>
						<?endif;?>
					</div>
<div class="body-info" >
	<div class="title" ><a href="<?=$arSection["SECTION_PAGE_URL"]?>"><?=$arSection['NAME'];?></a></div>
</div>
				</div>
					
			</div>
			<?endforeach;?>
			</div>
		<?endforeach;?>
	
</div>