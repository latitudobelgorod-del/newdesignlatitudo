<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?// вывод текущей даты и перевод ее в UNIX-формат?>
<?$objDateTime = new DateTime();
 $time_now = $objDateTime->getTimestamp();
  ?>


<?$this->setFrameMode(true);?>	
<?use \Bitrix\Main\Localization\Loc;?>

<?if($arParams["DISPLAY_PICTURE"] != "N"){
	$picture = ($arResult["FIELDS"]["DETAIL_PICTURE"] ? "DETAIL_PICTURE" : "PREVIEW_PICTURE");
	CNext::getFieldImageData($arResult, array($picture));
	$arPhoto = $arResult[$picture];
	if($arPhoto){
		$arImgs[] = array(
			'DETAIL' => $arPhoto,
			'PREVIEW' => CFile::ResizeImageGet($arPhoto["ID"], array('width' => 300, 'height' => 300), BX_RESIZE_IMAGE_PROPORTIONAL_ALT, true),
			'TITLE' => (strlen($arPhoto['DESCRIPTION']) ? $arPhoto['DESCRIPTION'] : (strlen($arPhoto['TITLE']) ? $arPhoto['TITLE'] : $arResult['NAME'])),
			'ALT' => (strlen($arPhoto['DESCRIPTION']) ? $arPhoto['DESCRIPTION'] : (strlen($arPhoto['ALT']) ? $arPhoto['ALT'] : $arResult['NAME'])),
		);
	}
}
?>

<div class="detail partners<?/*=($templateName = $component->{"__parent"}->{"__template"}->{"__name"})*/?>">
<?if($arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"])
{
$goy=$arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"];}
else {
$goy=$arResult['NAME'];
}
?>

<div itemprop="brand" itemscope itemtype="https://schema.org/Brand">
<meta itemprop="name" content="<?=$arResult['NAME']?>" />
<link itemprop="url" href="<?=$arResult["DETAIL_PAGE_URL"]?>" />
<link itemprop="logo" src="<?=$arResult["PREVIEW_PICTURE"]["SRC"]?>" alt="<?=$arResult['NAME']?>" />
</div>
	
	


<? if ($value['PROPERTIES']['SET']['VALUE']): ?>
	<div class="<?=$value['PROPERTIES']['SET']['VALUE_XML_ID']?>">	<img class="img-responsive" src="<?=$value["PREVIEW_PICTURE"]["SRC"]?>" alt="<?=$value["NAME"]?>"></div>
<? else: ?>
<img class="img-responsive" src="<?=$value["PREVIEW_PICTURE"]["SRC"]?>" alt="<?=$value["NAME"]?>">
<?endif;?>


<div class="row">
<div class="col-md-8 col-xs-12">	<h1 id="pagetitle"><?=$goy?></h1>
	<?/*<h1><?$APPLICATION->ShowTitle(false)?></h1>*/?>
	
	
<?/*Вывод анкоров на разделы, в зависимости от шаблоны детальной*/?>
<?foreach($arResult["DISPLAY_PROPERTIES"] as $pid=>$arProperty):?>
<?if($pid =="TEMPLATE"): ?>
<?/*
<li><span>Шаблон страницы:</span> <?echo $arProperty["DISPLAY_VALUE"];?>
*/?>
<?/*print_r($arProperty);*/?>
</li>
	<? if ($arProperty["VALUE_XML_ID"]=="temp_numb_1"): ?>
	<?/* Якоря считает include/brand_anchors.php шаблона, а не компонент
	   catalog.section из include/news.detail.ankor_section.php: тот ради десятка
	   ссылок вычитывал сотню товаров с ценами и съедал почти всё время страницы
	   (EasyDecking 16.9 → 1.4 секунды). Старый дизайн остался на компоненте. */?>
	<?include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/brand_anchors.php';?>
	<? else: ?>
	
	<?endif;?>

<?endif?>
<?endforeach;?> 
<?/*Вывод анкоров на разделы, в зависимости от шаблоны детальной*/?>
	

	</div>
<div class="col-md-4 col-xs-12">
	<?// images?>
		<?if($arImgs):?>
			<div class="detailimage">
				<?if($arImgs):?>
					<div class="img-partner">
						<img src="<?=$arImgs[0]["DETAIL"]["SRC"]?>" title="<?=$arImgs[0]["TITLE"]?>" alt="<?=$arImgs[0]["ALT"]?>" class="img-responsive" />
						<?if($arResult["DISPLAY_PROPERTIES"]["SITE"]['VALUE']):?>
							<div style="text-align:center;">
							<h2>Официальный сайт <?=$arResult["NAME"]?></h2>
							<a href="<?=(strpos($arResult["DISPLAY_PROPERTIES"]["SITE"]['VALUE'], 'http') === false ? 'http://' : '').$arResult["DISPLAY_PROPERTIES"]["SITE"]['VALUE'];?>"  target="_blank">
								<?=$arResult["DISPLAY_PROPERTIES"]["SITE"]['VALUE'];?>
							</a>
							</div>	
						<?endif;?>					
					</div>
				<?endif;?>
			</div>
		<?endif;?>
</div>
</div>

			

		<div class="post-content">
			<?if($arParams["DISPLAY_NAME"] != "N" && strlen($arResult["NAME"])):?>
				<h2><?=$arResult["NAME"]?></h2>
			<?endif;?>
			<div class="content">
				<?// text?>
				<?if(strlen($arResult["FIELDS"]["PREVIEW_TEXT"].$arResult["FIELDS"]["DETAIL_TEXT"])):?>
					<div class="text">
						<?if($arResult["DETAIL_TEXT_TYPE"] == "text"):?>
							<p><?=$arResult["FIELDS"]["DETAIL_TEXT"];?></p>
						<?else:?>
							<?=$arResult["FIELDS"]["DETAIL_TEXT"];?>
						<?endif;?>
					</div>
				<?endif;?>
				
				<?// display properties?>
				
			</div>
		</div>
	
</div>



<div class="editor torgmarks" >
	<?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        "images_columns",
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
<?if($arResult['GALLERY']):?>
	<div class="wraps with-padding galerys-block">
		<hr />
		<h5><?=$arParams['T_GALLERY'];?></h5>
		<div class="small-gallery-block">
			<div class="flexslider unstyled front border custom_flex top_right color-controls" data-plugin-options='{"animation": "slide", "directionNav": true, "controlNav" :true, "animationLoop": true, "slideshow": false, "counts": [4, 3, 2, 1]}'>
				<ul class="slides items">
					<?foreach($arResult['GALLERY'] as $i => $arPhoto):?>
						<li class="col-md-3 item">
							<div>
								<img src="/assets/lazyload/loading.gif" data-original="<?=$arPhoto['PREVIEW']['src']?>" class="lazy img-responsive inline" title="<?=$arPhoto['TITLE']?>" alt="<?=$arPhoto['ALT']?>" />
							</div>
							<a href="<?=$arPhoto['DETAIL']['SRC']?>" data-fancybox="group" class="fancy dark_block_animate" rel="gallery" target="_blank" title="<?=$arPhoto['TITLE']?>"></a>
						</li>
					<?endforeach;?>
				</ul>
			</div>
		</div>
	</div>
<?endif;?>

<?// docs files?>
<?if($arResult['DOCUMENTS']):?>
	<div class="wraps docs-block">
		<hr/>
		<h5><?=(strlen($arParams['T_DOCS']) ? $arParams['T_DOCS'] : Loc::getMessage('T_DOCS'))?></h5>
		<div class="files_block">
			<div class="row">
				<?foreach($arResult['DOCUMENTS']['VALUE'] as $arItem):?>
					<div class="col-md-3 col-sm-6">
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




<?// form question?>
<?global $isHideLeftBlock;?>
<?$bShowFormQuestion = ($arResult['DISPLAY_PROPERTIES']['FORM_QUESTION']['VALUE_XML_ID'] == 'YES');?>
<?if($bShowFormQuestion):?>
	<?ob_start();?>

	<?$sFormQuestion = ob_get_contents();
	ob_end_clean();?>
	<?if(!$isHideLeftBlock):?>
		<?$this->SetViewTarget('under_sidebar_content');?>
			<?=$sFormQuestion;?>
		<?$this->EndViewTarget();?>
	<?else:?>
		<div class="row">
			<div class="col-md-9">
	<?endif;?>
<?endif;?>



<?// form question?>
<?if($bShowFormQuestion && $isHideLeftBlock):?>
	</div>
	<div class="col-md-3 hidden-xs hidden-sm">
		<div class="fixed_block_fix"></div>
			<div class="ask_a_question_wrapper">
				<?=$sFormQuestion;?>
			</div>
		</div>
	</div>
<?endif;?>
