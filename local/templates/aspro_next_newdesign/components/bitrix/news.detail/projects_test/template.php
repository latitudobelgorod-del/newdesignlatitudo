<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?// вывод текущей даты и перевод ее в UNIX-формат?>
<?$objDateTime = new DateTime();
 $time_now = $objDateTime->getTimestamp();
  ?>
 <?if ($_SERVER['REQUEST_URI'] !== $arResult['DETAIL_PAGE_URL']):?>
 <? $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
<?endif;?>
<?$this->setFrameMode(true);?>	
<?use \Bitrix\Main\Localization\Loc;?>
<?$bShowCalculateBlock = ($arResult['DISPLAY_PROPERTIES']['FORM_CALCULATE']['VALUE_XML_ID'] == 'YES');?>

<?// shot top banners start?>
<?$bShowTopBanner = (isset($arResult['SECTION_BNR_CONTENT'] ) && $arResult['SECTION_BNR_CONTENT'] == true);?>
<?if($bShowTopBanner):?>
	<?$this->SetViewTarget("section_bnr_content");?>
		<?CNext::ShowTopDetailBanner($arResult, $arParams);?>
	<?$this->EndViewTarget();?>
<?endif;?>
<?// shot top banners end?>

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

<?// element name?>


			
	
<div class="item projects-blocks">

		<div class="row">
		
		
				<div class="col-md-8 col-md-offset-2">
					<div class="inner">

<?if($arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"])
{
$goy=$arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"];}
else {
$goy=$arResult['NAME'];		
}
?>
<h1 id="pagetitle"><?=$goy?></h1>
<?if($arResult['GALLERY']):?>

						<div class="flexslider color-controls dark show-nav-controls bigs top_slider" data-slice="Y" data-plugin-options='{"animation": "slide", "directionNav": true, "controlNav" :true, "animationLoop": true, "slideshow": false, "counts": [1, 1, 1]}'>
							<ul class="slides items">
								<?$countAll = count($arResult['GALLERY']);?>
								<?foreach($arResult['GALLERY'] as $i => $arPhoto):?>
							
									<li class="item" data-slice-block="Y" data-slice-params='{"lineheight": -3}'>
										<!--<a href="<?=$arPhoto['DETAIL']['SRC']?>" target="_blank" title="<?=$arPhoto['TITLE']?>" class="fancy" data-fancybox-group="gallery">-->
											<img src="<?=$arPhoto['PREVIEW']['src']?>" style="width:100%;" class="img-responsive inline" title="<?=$arPhoto['TITLE']?>" alt="<?=$arPhoto['ALT']?>" itemprop="image" />
											<!--<span class="zoom"></span>-->
										<!--</a>-->
									</li>
									
							
								<?endforeach;?>
									
							</ul>
						</div>
					

							<?endif;?>	
</div>
				
<div class="playvideo">  
	<a  title="Подпишитесь на наш канал" target="_blank" class="personal-link dark-color"href="https://www.youtube.com/channel/UCRgn9WlVgrp3W2hRxEw6AwQ?sub_confirmation=1" >
	<div class="text">Подпишитесь на наш канал</div>
	<img src="/images/yt_video.png">
	</a>
</div>
				
<div class="info" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
					<?if(isset($arResult['PROPERTIES']['TASK_PROJECT']) && $arResult['PROPERTIES']['TASK_PROJECT']['~VALUE']['TEXT']):?>
					
						<div class="hh">
							<div class="title_grey_small" style=" font-weight: bold; margin-bottom: 10px;  margin-top: 20px;"><?=$arResult['PROPERTIES']['TASK_PROJECT']['NAME'];?></div>
							<div class="text" ><?=htmlspecialcharsBack($arResult['PROPERTIES']['TASK_PROJECT']['~VALUE']['TEXT']);?></div>
						</div>
					<?endif;?>
					
						<?if(isset($arResult['PROPERTIES']['REVIEW']) && $arResult['PROPERTIES']['REVIEW']['~VALUE']['TEXT']):?>
						<div class="review">
							<div class="h2">Отзыв заказчика</div>
							<div class="text" ><?=$arResult['PROPERTIES']['REVIEW']['~VALUE']['TEXT'];?></div>
						</div>
					<?endif;?>
</div>
	  
<div class="editor">
            	<?$APPLICATION->IncludeComponent(
                    "sprint.editor:blocks",
                    ".default",
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

 <div class="content">
		<?if($arResult['PREVIEW_TEXT_TYPE'] == 'text'):?>
			<p><?=$arResult['FIELDS']['PREVIEW_TEXT'];?></p>
		<?else:?>
			<?=$arResult['FIELDS']['PREVIEW_TEXT'];?>
		<?endif;?>		
</div>
			
<?if(strlen($arResult['FIELDS']['DETAIL_TEXT'])):?>
	<div class="content">
		<?// element detail text?>
		<?if(strlen($arResult['FIELDS']['DETAIL_TEXT'])):?>
			<?if($arResult['DETAIL_TEXT_TYPE'] == 'text'):?>
				<p><?=$arResult['FIELDS']['DETAIL_TEXT'];?></p>
			<?else:?>
				<?=$arResult['FIELDS']['DETAIL_TEXT'];?>
			<?endif;?>
			<br>
		<?endif;?>
	</div>
<?endif;?>
	
<div class="block" style="margin: 0 auto;text-align: center;">
<span style="padding-left:40px;padding-right:40px;" class="btn btn-default btn-lg  animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="question"><span>Узнать стоимость</span></span>
</div>
</div>				
</div><?/*row*/?>
</div>
		
		

		
	<?// gallery?>
<?if($arResult['GALLERY_BIG']):?>
	<div class="wraps galerys-block">
		<hr />	
		<?if($arParams['GALLERY_TYPE'] == 'small'):?>
			<div class="small-gallery-block">
				<div class="flexslider row front bigs dark small_slider color-controls" data-plugin-options='{"animation": "slide", "directionNav": false, "useCSS": true, "controlNav" :true, "animationLoop": true, "slideshow": false, "counts": [4, 3, 2, 1]}'>
					<ul class="slides items">
						<?foreach($arResult['GALLERY_BIG'] as $i => $arPhoto):?>
							<li class="col-md-3 item visible">
								<div>
									<img src="<?=$arPhoto['PREVIEW']['src']?>" class="img-responsive inline" title="<?=$arPhoto['TITLE']?>" alt="<?=$arPhoto['ALT']?>" />
								</div>
								<a href="<?=$arPhoto['DETAIL']['SRC']?>" class="fancy dark_block_animate" rel="gallery" target="_blank" title="<?=$arPhoto['TITLE']?>"></a>
							</li>
						<?endforeach;?>
					</ul>
				</div>
			</div>
		<?else:?>
		
	  
			<div class="gallery-block">
				<div class="gallery-wrapper">
					<div class="inner">
						<?if(count($arResult["GALLERY_BIG"]) > 1):?>
							<div class="small-gallery-wrapper">
								<div style="padding-bottom:40px;" class="thmb1 flexslider unstyled small-gallery center-nav" data-plugin-options='{"slideshow": "false", "useCSS": true, "animation": "slide", "animationLoop": true, "itemWidth": 60, "itemMargin": 20, "minItems": 1, "maxItems": 9, "slide_counts": 1, "asNavFor": ".gallery-wrapper .bigs"}' id="carousel1">
									<ul class="slides items">	
										<?foreach($arResult["GALLERY_BIG"] as $arPhoto):?>
											<li class="item">
												<img class="img-responsive inline" border="0" src="<?=$arPhoto["THUMB"]["src"]?>" title="<?=$arPhoto['TITLE']?>" alt="<?=$arPhoto['ALT']?>" />
											</li>
										<?endforeach;?>
									</ul>
									
								</div>
							</div>
						<?endif;?>
						<div class="flexslider dark bigs big_slider color-controls" id="slider" data-plugin-options='{"animation": "slide", "useCSS": true, "directionNav": true, "controlNav" :true, "animationLoop": true, "slideshow": false, "sync": ".gallery-wrapper .small-gallery", "counts": [1, 1, 1]}'>
							<ul class="slides items">
								<?foreach($arResult['GALLERY_BIG'] as $i => $arPhoto):?>
									<li class="col-md-12 item">
										<a href="<?=$arPhoto['DETAIL']['SRC']?>" class="fancy" data-fancybox="gallery" rel="gallery" target="_blank" title="<?=$arPhoto['TITLE']?>">
											<img src="<?=$arPhoto['PREVIEW']['src']?>" class="img-responsive inline" title="<?=$arPhoto['TITLE']?>" alt="<?=$arPhoto['ALT']?>" />
											<p><?=$arPhoto['TITLE']?></p>
											<span class="zoom"></span>
										</a>
										
									</li>
									
								<?endforeach;?>
							</ul>
						</div>
					</div>
				</div>
			</div>
		<?endif;?>
		
		
	</div>
<?endif;?>


<div class="item projects-blocks">

		<div class="row">

		<div class="col-md-8 col-md-offset-2">
		
		<div class="infochat hidden-xs">
		<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
			array(
				"COMPONENT_TEMPLATE" => ".default",
				"PATH" => SITE_DIR."include/infochat_projects.php",
				"AREA_FILE_SHOW" => "file",
				"AREA_FILE_SUFFIX" => "",
				"AREA_FILE_RECURSIVE" => "Y",
				"EDIT_TEMPLATE" => "standard.php"
			),
			false
		);?>
		</div>
			  <div class="editor">
            	<?$APPLICATION->IncludeComponent(
                    "sprint.editor:blocks",
                    ".default",
                    Array(
                        "ELEMENT_ID" => $arResult["ID"],
                        "IBLOCK_ID" => $arResult["IBLOCK_ID"],
                        "PROPERTY_CODE" => "EDITOR2",
                        "USE_JQUERY" => "N",
                        "USE_FANCYBOX" => "N",
                    ),
                    $component,
                    Array(
                        "HIDE_ICONS" => "Y"
                    )
                );?>
        </div>

			</div>		
		
		<div class="col-md-12">
			<div class="infochat visible-xs">
			<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
			array(
				"COMPONENT_TEMPLATE" => ".default",
				"PATH" => SITE_DIR."include/infochat.php",
				"AREA_FILE_SHOW" => "file",
				"AREA_FILE_SUFFIX" => "",
				"AREA_FILE_RECURSIVE" => "Y",
				"EDIT_TEMPLATE" => "standard.php"
			),
			false
		);?>
		</div>
		</div>
		</div>
		
		
		</div>

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

