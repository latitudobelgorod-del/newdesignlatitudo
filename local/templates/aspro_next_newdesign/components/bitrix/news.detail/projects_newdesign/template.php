<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?// Скрипт галереи подключаем сами, а не через script.js шаблона: тот файл
   // целиком попадает в общий бандл, и первая же ошибка в старом коде Аспро
   // (там `$(document).ready` до загрузки jQuery) обрывает выполнение всего
   // файла — наш код до запуска не доходил. Версия по filemtime, как у
   // остальных newdesign-*.js.?>
<?$ndProjJs = $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/js/newdesign-projects.js';?>
<?$APPLICATION->AddHeadString('<script src="'.SITE_TEMPLATE_PATH.'/js/newdesign-projects.js?'.(file_exists($ndProjJs) ? filemtime($ndProjJs) : '').'" defer></script>', true);?>
<?// вывод текущей даты и перевод ее в UNIX-формат?>
<?
$page_url_itemprop = CMain::IsHTTPS() ? 'https://'. $_SERVER['HTTP_HOST']. $arResult['DETAIL_PAGE_URL'] : 'http://' . $_SERVER['HTTP_HOST']. $arResult['DETAIL_PAGE_URL'];
$address_image_itemprop = CMain::IsHTTPS() ? 'https://'. $_SERVER['HTTP_HOST'] : 'http://' . $_SERVER['HTTP_HOST'];
//$page_url_itemprop = CMain::IsHTTPS() ? 'https://'. $arResult["DETAIL_PAGE_URL"] : 'http://' . $arResult["DETAIL_PAGE_URL"];

?>
<?$objDateTime = new DateTime();
 $time_now = $objDateTime->getTimestamp();
  ?>
 <?if ($_SERVER['REQUEST_URI'] !== $arResult['DETAIL_PAGE_URL']):?>
 <?// $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
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

<?$bShowAskBlock = ($arResult['DISPLAY_PROPERTIES']['FORM_QUESTION']['VALUE_XML_ID'] == 'YES');?>
<?$bShowOrderBlock = ($arResult['DISPLAY_PROPERTIES']['FORM_ORDER']['VALUE_XML_ID'] == 'YES');?>
<?$bShowAllChar = (isset($arResult['DISPLAY_PROPERTIES_FORMATTED']) && count((array)$arResult['DISPLAY_PROPERTIES_FORMATTED'])>3);?>
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




<div class="row"><?// row?>
<div class="col-md-9"><?// col-md-9?>
					
<?endif;?>
<?endif;?>







		<?/*ПЕРЕНЕСЕНО*/?>

<?// element name?>

<div class="item projects-blocks">

<div class="row">



<div class="col-md-8 col-md-offset-2" itemscope itemtype="https://schema.org/Article"><?//col-md-8 col-md-offset-2?>
<link itemprop="url" href="<?=$arResult['DETAIL_PAGE_URL']?>" />


<div class="inner">
		<?if($arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"])
		{
		$goy=$arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"];}
		else {
		$goy=$arResult['NAME'];		
		}
		?>

		<h1 id="pagetitle" itemprop="headline"><?=$goy?></h1>
									
			<?if($arResult['GALLERY']):?>
			<div class="flexslider color-controls dark show-nav-controls bigs top_slider" data-slice="Y" data-plugin-options='{"animation": "slide", "directionNav": true, "controlNav" :true, "animationLoop": true, "slideshow": false, "counts": [1, 1, 1]}'>
				<ul class="slides items">
					<?$countAll = count($arResult['GALLERY']);?>
					<?foreach($arResult['GALLERY'] as $i => $arPhoto):?>
						<li itemprop="hasPart" itemscope itemtype="https://schema.org/ImageObject" class="item" data-slice-block="Y" data-slice-params='{"lineheight": -3}'>
							<!--<a href="<?=$arPhoto['DETAIL']['SRC']?>" target="_blank" title="<?=$arPhoto['TITLE']?>" class="fancy" data-fancybox-group="gallery">-->
							<meta itemprop="name" content="<?=$arPhoto['TITLE']?>" />
							<meta itemprop="url" content="<?=$page_url_itemprop?>" />
							<meta itemprop="author" content="Латитудо" />
							<img itemprop="contentUrl" src="<?=$address_image_itemprop?><?=$arPhoto['PREVIEW']['src']?>" style="width:100%;" class="img-responsive inline" title="<?=$arPhoto['TITLE']?>" alt="<?=$arPhoto['ALT']?>" itemprop="image" />
							
							<!--<span class="zoom"></span>-->
							<!--</a>-->
						</li>
					<?endforeach;?>
				</ul>
					<? if ($arResult['PROPERTIES']['SET_BRAND']['VALUE']): ?>
                 <div class="<?=$arResult['PROPERTIES']['SET_BRAND']['VALUE_XML_ID']?>"><?=$arResult['PROPERTIES']['SET_BRAND']['VALUE']?></div>
               <?endif;?>
			</div>
		<?endif;?>												
</div>
		


		
<div class="playvideo">  
	<a  title="Подпишитесь на наш канал" target="_blank" class="personal-link dark-color"href="https://www.youtube.com/channel/UCRgn9WlVgrp3W2hRxEw6AwQ?sub_confirmation=1" >
	<div class="text">Подпишитесь на наш канал</div>
	<img src="/images/yt_video.png">
	</a>
</div>


<div class="info">
					<?if($arResult['PROPERTIES']['TASK_PROJECT']['VALUE']):?>
					
					<div class="hh">
							<div class="title_grey_small" style=" font-weight: bold; margin-bottom: 10px;  margin-top: 20px;"><?=$arResult['PROPERTIES']['TASK_PROJECT']['NAME'];?></div>
							<div class="text" ><?=$arResult['PROPERTIES']['TASK_PROJECT']['VALUE']['TEXT'];?></div>
						</div>
					<?endif;?>
					
					
						
					
					
						<?if($arResult['PROPERTIES']['REVIEW']['VALUE']):?>
						<div class="review">
							<div class="h2">Отзыв заказчика</div>
							<div class="text" ><?=$arResult['PROPERTIES']['REVIEW']['VALUE']['TEXT'];?></div>
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
						"NEWS_NAME" => $arResult["NAME"],
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
</div>	<?//col-md-8 col-md-offset-2?>		
</div><?/*row*/?>
</div><?/*item projects-blocks*/?>
			<?/*ПЕРЕНЕСЕНО*/?>	

	
<?// Галерея проекта — новый дизайн.
   // Макет: Figma «Чистовик», фрейм «Проект» 20524:98253, блок 20545:102807.
   // Лента 600×400 со скруглением 6 и зазором 6 — следующий кадр выглядывает
   // справа; под ней ряд превью 51×51 с зазором 12. Прежний flexslider с
   // синхронной лентой миниатюр сверху так не умеет (там кадр всегда один
   // на всю ширину), поэтому лента своя: горизонтальная прокрутка со
   // scroll-snap, миниатюры и стрелки — на js/script.js этого шаблона.
   // Старый дизайн остался на прежнем шаблоне projects.?>
<?if($arResult['GALLERY_BIG']):?>
	<?// Колонка та же, что у текста проекта (col-md-8 col-md-offset-2 = 878
	   // из макета): в Figma «Галерея» стоит внутри текстового блока, и
	   // ширина ленты рассчитана на неё — кадр 600 плюс краешек следующего.?>
	<div class="row">
	<div class="col-md-8 col-md-offset-2">
	<section class="nd-gal" data-nd-gal>
		<h2 class="nd-gal__title"><?=(strlen($arParams['T_GALLERY']) ? $arParams['T_GALLERY'] : GetMessage('T_GALLERY'))?></h2>

		<div class="nd-gal__box">
			<div class="nd-gal__strip" data-nd-gal-strip itemscope itemtype="https://schema.org/ImageGallery">
				<?foreach($arResult['GALLERY_BIG'] as $i => $arPhoto):?>
					<a class="nd-gal__slide" href="<?=$arPhoto['DETAIL']['SRC']?>" data-fancybox="nd-gallery"
					   data-nd-gal-slide="<?=$i?>" title="<?=htmlspecialcharsbx($arPhoto['TITLE'])?>"
					   itemprop="hasPart" itemscope itemtype="https://schema.org/ImageObject">
						<meta itemprop="name" content="<?=htmlspecialcharsbx($arPhoto['TITLE'])?>" />
						<img itemprop="contentUrl" src="<?=$arPhoto['PREVIEW']['src']?>" loading="lazy"
							 alt="<?=htmlspecialcharsbx($arPhoto['ALT'])?>" title="<?=htmlspecialcharsbx($arPhoto['TITLE'])?>" />
					</a>
				<?endforeach;?>
			</div>

			<?if(count($arResult['GALLERY_BIG']) > 1):?>
				<button type="button" class="nd-gal__arrow nd-gal__arrow--prev" data-nd-gal-prev aria-label="Предыдущее фото">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="m15 6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
				<button type="button" class="nd-gal__arrow nd-gal__arrow--next" data-nd-gal-next aria-label="Следующее фото">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
			<?endif;?>
		</div>

		<?if(count($arResult['GALLERY_BIG']) > 1):?>
			<div class="nd-gal__thumbs" data-nd-gal-thumbs>
				<?foreach($arResult['GALLERY_BIG'] as $i => $arPhoto):?>
					<button type="button" class="nd-gal__thumb<?=($i ? '' : ' is-active')?>" data-nd-gal-thumb="<?=$i?>"
							aria-label="Фото <?=($i + 1)?>">
						<img src="<?=$arPhoto['THUMB']['src']?>" loading="lazy" alt="" />
					</button>
				<?endforeach;?>
			</div>
		<?endif;?>
	</section>
	</div>
	</div>
<?endif;?>
<?// gallery?>



<div class="item projects-blocks">
	<div class="row">
	<div class="col-md-8 col-md-offset-2">
		<div class="infochat nd-infochat-wrap hidden-xs">
		<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
			array(
				"COMPONENT_TEMPLATE" => ".default",
				"PATH" => SITE_DIR."include/infochat_projects_newdesign.php",
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
						"NEWS_NAME" => $arResult["NAME"],
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
			<div class="infochat nd-infochat-wrap visible-xs">
			<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
				array(
					"COMPONENT_TEMPLATE" => ".default",
					"PATH" => SITE_DIR."include/infochat_newdesign.php",
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
	</div><?// col-md-9?>
	<div class="col-md-3 hidden-xs hidden-sm">
		<div class="fixed_block_fix"></div>
		<div class="ask_a_question_wrapper">
				<?=$sFormQuestion;?>
		</div>
	</div>
	</div><?// row?>
	
	<?endif;?>