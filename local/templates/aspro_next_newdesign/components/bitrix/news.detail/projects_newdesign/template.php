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

		<?// Под заголовком — метки проекта и производитель (Figma, фрейм «Проект»
		   // 20524:98253, ряд 20545:101484): слева плашки 28px «Проект / Видео /
		   // N фото / Отзыв», справа ярлык бренда. Набор меток тот же, что на
		   // карточках портфолио, но плашки крупнее — там 20px, здесь 28.?>
		<?
		$ndBrand = $arResult['PROPERTIES']['SET_BRAND'];
		$ndHasVideo = !empty($arResult['PROPERTIES']['VIDEO']['VALUE']);
		$ndPhotoCnt = is_array($arResult['GALLERY_BIG']) ? count($arResult['GALLERY_BIG']) : 0;
		// REVIEW хранится как HTML — значение приходит массивом с ключом TEXT
		$ndReview = $arResult['PROPERTIES']['REVIEW']['~VALUE'] ?: $arResult['PROPERTIES']['REVIEW']['VALUE'];
		$ndHasReview = is_array($ndReview) ? (trim((string)$ndReview['TEXT']) !== '') : (trim((string)$ndReview) !== '');
		?>
		<div class="nd-projhead">
			<div class="nd-projhead__tags">
				<span class="nd-projhead__tag nd-projhead__tag--project">Проект</span>
				<?if($ndHasVideo):?><span class="nd-projhead__tag nd-projhead__tag--video">Видео</span><?endif;?>
				<?if($ndPhotoCnt):?><span class="nd-projhead__tag nd-projhead__tag--photo"><?=$ndPhotoCnt?> фото</span><?endif;?>
				<?if($ndHasReview):?><span class="nd-projhead__tag nd-projhead__tag--review">Отзыв</span><?endif;?>
			</div>
			<?if(!empty($ndBrand['VALUE'])):?>
				<div class="nd-projhead__brand nd-projhead__brand--<?=htmlspecialcharsbx($ndBrand['VALUE_XML_ID'])?>"><?=htmlspecialcharsbx($ndBrand['VALUE'])?></div>
			<?endif;?>
		</div>

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
					<?// Ярлык производителя переехал в ряд под заголовком (.nd-projhead) —
					   // здесь он выводился без стилей, голым текстом под слайдером.?>
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
					
					
						
					
					
<?// Отзыв клиента (Figma, фрейм «Проект» 20524:98253, блок 20545:101545):
					   // заголовок, под ним цитата — вертикальная чёрная полоса 3px,
					   // отступ 12 и текст, ниже подпись автора из свойства AUTHOR.
					   // Вариант с фотографией из макета не делаем: свойства с фото
					   // у проектов нет и заводить его не планируется (решение от
					   // 18 августа 2026).?>
					<?
					$ndRevText = is_array($arResult['PROPERTIES']['REVIEW']['VALUE'])
						? (string)$arResult['PROPERTIES']['REVIEW']['VALUE']['TEXT']
						: (string)$arResult['PROPERTIES']['REVIEW']['VALUE'];
					$ndRevAuthor = trim((string)$arResult['PROPERTIES']['AUTHOR']['VALUE']);
					?>
					<?if(trim(strip_tags($ndRevText)) !== ''):?>
						<section class="nd-review">
							<h2 class="nd-review__title">Отзыв клиента</h2>
							<blockquote class="nd-review__quote">
								<div class="nd-review__text"><?=$ndRevText?></div>
								<?if($ndRevAuthor):?>
									<div class="nd-review__author"><?=htmlspecialcharsbx($ndRevAuthor)?></div>
								<?endif;?>
							</blockquote>
						</section>
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
				<?// Стрелка из макета — не кружок, а полоса затемнения 64px у края ленты
				   // (градиент от #101014 к прозрачному) с длинной белой стрелкой 40×40
				   // по центру. Иконка выгружена из Figma (ico/arrow_right).?>
				<button type="button" class="nd-gal__arrow nd-gal__arrow--prev" data-nd-gal-prev aria-label="Предыдущее фото">
					<svg width="40" height="40" viewBox="0 0 40 40" fill="none" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M21.0615 8.93934C20.4757 8.35355 19.5243 8.35355 18.9385 8.93934L8.93848 18.9393C8.64562 19.2322 8.49902 19.6167 8.49902 20.0009C8.49902 20.0031 8.49902 20.0054 8.49902 20.0077C8.49902 20.4508 8.69336 20.8472 8.99902 21.122L18.9385 31.0624C19.5243 31.6481 20.4757 31.6481 21.0615 31.0624C21.6473 30.4766 21.6473 29.5251 21.0615 28.9393L13.6299 21.5087H30C30.8284 21.5087 31.501 20.8361 31.501 20.0077C31.501 19.1793 30.8284 18.5067 30 18.5067H13.6162L21.0615 11.0624C21.6473 10.4766 21.6473 9.52513 21.0615 8.93934Z" fill="currentColor"/></svg>
				</button>
				<button type="button" class="nd-gal__arrow nd-gal__arrow--next" data-nd-gal-next aria-label="Следующее фото">
					<svg width="40" height="40" viewBox="0 0 40 40" fill="none" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.9385 8.93934C19.5243 8.35355 20.4757 8.35355 21.0615 8.93934L31.0615 18.9393C31.3544 19.2322 31.501 19.6167 31.501 20.0009C31.501 20.0031 31.501 20.0054 31.501 20.0077C31.501 20.4508 31.3066 20.8472 31.001 21.122L21.0615 31.0624C20.4757 31.6481 19.5243 31.6481 18.9385 31.0624C18.3527 30.4766 18.3527 29.5251 18.9385 28.9393L26.3701 21.5087H10C9.17157 21.5087 8.49902 20.8361 8.49902 20.0077C8.49903 19.1793 9.17158 18.5067 10 18.5067H26.3838L18.9385 11.0624C18.3527 10.4766 18.3527 9.52513 18.9385 8.93934Z" fill="currentColor"/></svg>
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