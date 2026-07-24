<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>

<?/*<h1 id="pagetitle">Каталог</h1>*/?>
<div class="text_before_items" style="margin-bottom:20px;">
	<?$APPLICATION->IncludeComponent(
		"bitrix:main.include",
		"",
		Array(
			"AREA_FILE_SHOW" => "page",
			"AREA_FILE_SUFFIX" => "inc",
			"EDIT_TEMPLATE" => ""
		)
	);?>
</div>
<h1 id="pagetitle">Каталог ДПК</h1>
<?if($arResult["SECTIONS"]){?>
<div class="sec_list">
<div class="sec_list-wrap">

	<?foreach( $arResult["SECTIONS"] as $arItems ){
		$this->AddEditAction($arItems['ID'], $arItems['EDIT_LINK'], CIBlock::GetArrayByID($arItems["IBLOCK_ID"], "SECTION_EDIT"));
		$this->AddDeleteAction($arItems['ID'], $arItems['DELETE_LINK'], CIBlock::GetArrayByID($arItems["IBLOCK_ID"], "SECTION_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_SECTION_DELETE_CONFIRM')));
	?>
		
			<div class="sec_list-item" id="<?=$this->GetEditAreaId($arItems['ID']);?>">
			<a  href="<?=$arItems["SECTION_PAGE_URL"]?>" class="sec_list-item-link">
			<span class="icon">
                    <picture class="loaded">
					
					
						<?if($arItems["PICTURE"]["SRC"]):?>
									<?$img = CFile::ResizeImageGet($arItems["PICTURE"]["ID"], array( "width" => 80, "height" => 80 ), BX_RESIZE_IMAGE_EXACT, true );?>
									
									<img width="80" height="80" src="<?=$img["src"]?>" alt="<?=($arItems["PICTURE"]["ALT"] ? $arItems["PICTURE"]["ALT"] : $arItems["NAME"])?>" title="<?=($arItems["PICTURE"]["TITLE"] ? $arItems["PICTURE"]["TITLE"] : $arItems["NAME"])?>" />
									
								<?elseif($arItems["~PICTURE"]):?>
									<?$img = CFile::ResizeImageGet($arItems["~PICTURE"], array( "width" => 80, "height" => 80 ), BX_RESIZE_IMAGE_EXACT, true );?>
									
									<img width="80" height="80" src="<?=$img["src"]?>" alt="<?=($arItems["PICTURE"]["ALT"] ? $arItems["PICTURE"]["ALT"] : $arItems["NAME"])?>"	title="<?=($arItems["PICTURE"]["TITLE"] ? $arItems["PICTURE"]["TITLE"] : $arItems["NAME"])?>" />
												<?endif;?>
					
                    </picture>
                </span>
				
				<span class="text"><?=$arItems["NAME"]?></span>
					
	
				</a>
			</div>
		
	<?}?>
</div></div>
<?}?>


<? if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) == '/catalog/'): ?>


<div class="k_det">
<div class="wrapper_inner">
<div>
<div>
<span style="text-indent:0;margin:0px 0;" class="callback-block animate-load twosmallfont colored  white" 
data-event="jqm" data-param-form_id="MAINFORM" data-name="detail_catalog">Консультация по материалам</span></div>
</div>
</div>

</div>


<?endif;?>
