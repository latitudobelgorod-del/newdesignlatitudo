<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>
<h1 id="pagetitle"><?$APPLICATION->ShowTitle(false)?></h1>
<?if($arResult["SECTIONS"]){?>
<div class="sections_wrapper sect_wr_cat">
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
							<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb"><img src="<?=SITE_TEMPLATE_PATH?>/images/no_photo_medium.png" alt="<?=$arSection["NAME"]?>" title="<?=$arSection["NAME"]?>" height="90" /></a>
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
</div>
<div class="center">
<!--noindex--><a class="btn white" href="https://latitudo.org" rel="nofollow"> Перейти на отдельный сайт по фасадным материалам</a><!--/noindex-->
</div>

<?}?>