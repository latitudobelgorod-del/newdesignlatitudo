<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>
<?if($arResult["SECTIONS"]){?>
<div class="sections_wrapper sect_wr_cat">
<div class="list items">
	
		<?foreach(array_chunk($arResult['SECTIONS'], 3) as $arSect):
			
			$this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_EDIT"));
			$this->AddDeleteAction($arSection['ID'], $arSection['DELETE_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));?>
			
			<div class="row margin0 flexbox">
				  <?foreach ($arSect as $arSection):?>
			<div class="col-md-4 col-sm-4 col-xs-12 front-sect">
			
<? $imgSect = (empty($arSection["PICTURE"]["SRC"]) ? 'noimng' : '');?>


				<div class="item <?=(empty($arSection["PICTURE"]["SRC"]) ? 'noimng' : '')?>" id="<?=$this->GetEditAreaId($arSection['ID']);?>">
						<?if($arSection["PICTURE"]["SRC"]):?>
					<div class="img shine">
						<?if($arSection["PICTURE"]["SRC"]):?>
							<?$img = CFile::ResizeImageGet($arSection["PICTURE"]["ID"], array( "width" => 440, "height" => 440 ), BX_RESIZE_IMAGE_EXACT, true );?>
							<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb"><img  src="<?=$img["src"]?>" alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" /></a>
						<?elseif($arSection["~PICTURE"]):?>
							<?$img = CFile::ResizeImageGet($arSection["~PICTURE"], array( "width" => 440, "height" => 440 ), BX_RESIZE_IMAGE_EXACT, true );?>
							<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb"><img  src="<?=$img["src"]?>" alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" /></a>
						<?else:?>
							<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb"><img src="<?=SITE_TEMPLATE_PATH?>/images/no_photo_medium.png" alt="<?=$arSection["NAME"]?>" title="<?=$arSection["NAME"]?>" height="90" /></a>
						<?endif;?>
					</div>
					<?endif;?>
					
					
					<div class="name" >
					<div class="blk"> <div class="left col-md-7 col-sm-12"><a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="white_link" ><?=$arSection['NAME']?></a></div>
<div class="right col-md-5 col-sm-12"><a  class="sect_button animate-load white btn-default btn" href="<?=$arSection["SECTION_PAGE_URL"]?>" class="">Смотреть</a></div>
						</div>
							</div>
							

				</div>
					
			</div>
			<?endforeach;?>
			</div>
		<?endforeach;?>
</div>
</div>
<?}?>