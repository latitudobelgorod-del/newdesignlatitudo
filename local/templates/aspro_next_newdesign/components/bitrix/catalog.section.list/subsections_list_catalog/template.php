<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>
<?if ($_SERVER['REQUEST_URI'] !== '/catalog/'):?>
 <? $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
<?endif;?>
<?/*<h1 id="pagetitle">Каталог</h1>*/?>
<div class="text_before_items" style="margin-bottom:20px;">
	<?/*$APPLICATION->IncludeComponent(
		"bitrix:main.include",
		"",
		Array(
			"AREA_FILE_SHOW" => "page",
			"AREA_FILE_SUFFIX" => "inc",
			"EDIT_TEMPLATE" => ""
		)
	);*/?>
</div>
<?global $arRegion;?>
<?if($arResult['SECTIONS']):?>
	<div class="sections_wrapper sect_wr_cat">
<div class="list items">
	<div class="row margin0 flexbox">
		<?foreach($arResult['SECTIONS'] as $arSection):
			$this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_EDIT"));
			$this->AddDeleteAction($arSection['ID'], $arSection['DELETE_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));?>
			
		

			<div class="col-md-4 col-sm-4 col-xs-12 col">
			
				<div class="item shine" id="<?=$this->GetEditAreaId($arSection['ID']);?>">
				<a href="<?=$arSection["SECTION_PAGE_URL"]?>" class="thumb">
					<div class="img ">
						<?if($arSection["PICTURE"]["SRC"]):?>
							<?$img = CFile::ResizeImageGet($arSection["PICTURE"]["ID"], array( "width" => 600, "height" => 350 ), BX_RESIZE_IMAGE_EXACT, true );?>
							<img class="lazy img-responsive" src="/assets/lazyload/loading.gif" data-original="<?=$img["src"]?>" 
							alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" 
							title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" />
						<?elseif($arSection["~PICTURE"]):?>
							<?$img = CFile::ResizeImageGet($arSection["~PICTURE"], array( "width" => 600, "height" => 350 ), BX_RESIZE_IMAGE_EXACT, true );?>
							<img class="lazy img-responsive" src="/assets/lazyload/loading.gif" data-original="<?=$img["src"]?>" 
							alt="<?=($arSection["PICTURE"]["ALT"] ? $arSection["PICTURE"]["ALT"] : $arSection["NAME"])?>" 
							title="<?=($arSection["PICTURE"]["TITLE"] ? $arSection["PICTURE"]["TITLE"] : $arSection["NAME"])?>" />
						<?else:?>
							<img class="lazy img-responsive" src="/assets/lazyload/loading.gif" data-original="<?=SITE_TEMPLATE_PATH?>/images/no_photo_medium.png" 
							alt="<?=$arSection["NAME"]?>" title="<?=$arSection["NAME"]?>" height="90" />
						<?endif;?>
					</div>

<div class="name" >
<div class="blk"> 
<div class="left col-md-5 col-sm-12">
<div class="white_link" ><?=$arSection['NAME'];?></div></div>
<div class="right col-md-7 col-sm-12">
<div  class="sect_button animate-load white btn-default btn">Смотреть фото и цены</div></div>
						</div>
							</div>
</a>
				</div>
				
			</div>
		<?endforeach;?>
	
	</div>
</div>




				
				
	</div>	
<?endif;?>


<? if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) == '/catalog/'): ?>


<style>

#footer {margin-bottom:60px !important;}

</style>
<div class="k_det" style="">
<div class="wrapper_inner">
<div>
<div>
<span style="text-indent:0;margin:0px 0;" class="callback-block animate-load twosmallfont colored  white" data-event="jqm" data-param-form_id="MAINFORM" data-name="detail_catalog">Консультация по материалам</span></div>
</div>
</div>

</div>


<?endif;?>