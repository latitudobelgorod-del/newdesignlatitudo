<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>

<? global $arSectionDopRazdel; ?>


<?if($arResult["SECTIONS"]){?>
<div class="sections_wrapper sect_wr_cat" style="margin-top:30px;">
<div class="list items">
		<?foreach(array_chunk($arResult['SECTIONS'], 3) as $arSect):
			//$this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_EDIT"));
			//$this->AddDeleteAction($arSection['ID'], $arSection['DELETE_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));?>
			<div class="row margin0 flexbox">
			<?foreach ($arSect as $arSection):?>
			<div class="col-md-4 col-sm-4 col-xs-12 front-sect">
<? $imgSect = (empty($arSection["PICTURE"]["SRC"]) ? 'noimng' : '');?>
				<div class="item shine <?=(empty($arSection["PICTURE"]["SRC"]) ? 'noimng' : '')?>" id="<?=$this->GetEditAreaId($arSection['ID']);?>">
						<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb">
						<?if($arSection["PICTURE"]["SRC"]):?>
						<div class="img">
						<?if($arSection["PICTURE"]["SRC"]):?>
							<?$img = CFile::ResizeImageGet($arSection["PICTURE"]["ID"], array( "width" => 600, "height" => 350 ), BX_RESIZE_IMAGE_EXACT, true );?>
							<img  src="<?=$img["src"]?>" alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" 
							title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" />
						<?elseif($arSection["~PICTURE"]):?>
							<?$img = CFile::ResizeImageGet($arSection["~PICTURE"], array( "width" => 600, "height" => 350 ), BX_RESIZE_IMAGE_EXACT, true );?>
						<img  src="<?=$img["src"]?>" alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" 
						title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" />
						<?else:?>
							<img src="<?=SITE_TEMPLATE_PATH?>/images/no_photo_medium.png" alt="<?=$arSection["NAME"]?>" title="<?=$arSection["NAME"]?>" height="90" />
						<?endif;?>
					</div>
					<?endif;?>
<div class="<?=(empty($arSection["PICTURE"]["SRC"]) ? '' : 'name')?> " ><div class="blk"><div class="left col-md-12 col-sm-12"><div class="<?=(empty($arSection["PICTURE"]["SRC"]) ? 'dark_link' : 'white_link')?> " ><?=$arSection['NAME'];?></div></div></div>				
</div>
						</a>
				</div>
			</div>
			<?endforeach;?>
			</div>
		<?endforeach;?>
</div>
</div>
<?}?>
