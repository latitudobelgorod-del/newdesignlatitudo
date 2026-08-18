<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?// вывод текущей даты и перевод ее в UNIX-формат?>
<?$objDateTime = new DateTime();
 $time_now = $objDateTime->getTimestamp();
  ?>


<?$this->setFrameMode(true);?>	
<?use \Bitrix\Main\Localization\Loc;?>

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
		<div class="ask_a_question">
			<div class="inner">
				<div class="text-block">
					<?$APPLICATION->IncludeComponent(
						 'bitrix:main.include',
						 '',
						 Array(
							  'AREA_FILE_SHOW' => 'page',
							  'AREA_FILE_SUFFIX' => 'ask',
							  'EDIT_TEMPLATE' => ''
						 )
					);?>
				</div>
			</div>
			<div class="outer">
				<span><span class="btn btn-default btn-lg white animate-load" data-event="jqm" data-param-form_id="ASK" data-name="question"><span><?=(strlen($arParams['S_ASK_QUESTION']) ? $arParams['S_ASK_QUESTION'] : Loc::getMessage('S_ASK_QUESTION'))?></span></span></span>
			</div>
		</div>
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
<?if($arParams['DISPLAY_NAME'] != 'N' && strlen($arResult['NAME'])):?>
	<h2><?=$arResult['NAME']?></h2>
<?endif;?>

<?// Картинка акции переехала вниз, в полосу с описанием (.nd-sale-lead):
   // по макету она стоит справа от текста, а не над заголовком.?>



<?
$convers = ConvertDateTime($arResult["DATE_ACTIVE_TO"], "YYYY-MM-DD HH:MI:SS", "ru");
$date_activeto = MakeTimeStamp($arResult["DATE_ACTIVE_TO"], "DD.MM.YYYY HH:MI:SS");
?>

<?// Зелёная метка «Акция!» над заголовком убрана: она ничего не сообщает,
   // раздел и так называется «Акции и скидки». Состояние «Акция завершена»
   // оставляем — оно информативное.?>
<?if($arResult["DATE_ACTIVE_TO"] && $date_activeto < $time_now):?>
	<div class="akciya_end">Акция завершена</div>
<?endif;?>
<?if($arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"])
{

$goy=$arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"];}
else {
	
		$goy=$arResult['NAME'];

		
}
?>
	<h1 id="pagetitle"><?=$goy?></h1>


<?
$convers1 = ConvertDateTime($arResult["DATE_ACTIVE_TO"], "DD.MM.YYYY", "ru");
?>
				


<?// Срок акции — плашкой под заголовком: «До конца акции 4 дня» вместо
   // прежней строчки «Акция действительна до 31.08.2026». Считаем остаток
   // в днях, склонение подбираем по числу. Завершённые акции сюда не
   // попадают — у них выше стоит «Акция завершена».?>
<?
$ndDaysLeft = null;
if($date_activeto && $date_activeto >= $time_now)
	$ndDaysLeft = (int)ceil(($date_activeto - $time_now) / 86400);

$ndDaysWord = function($n){
	$last = $n % 10;
	$two = $n % 100;
	if($last === 1 && $two !== 11) return 'день';
	if($last >= 2 && $last <= 4 && ($two < 12 || $two > 14)) return 'дня';
	return 'дней';
};
?>
<?if($ndDaysLeft !== null):?>
	<div class="nd-sale-term"><?=($ndDaysLeft > 0 ? 'До конца акции '.$ndDaysLeft.' '.$ndDaysWord($ndDaysLeft) : 'Акция заканчивается сегодня')?></div>
<?elseif($arResult["DATE_ACTIVE_TO"] && !$date_activeto && $arResult['DISPLAY_ACTIVE_FROM']):?>
	<div class="nd-sale-term"><?=$arResult['DISPLAY_ACTIVE_FROM']?></div>
<?endif;?>


<?if(!$bShowTopBanner && strlen($arResult['FIELDS']['PREVIEW_TEXT'])):?>
	<div class="introtext_wrapper">
		<div class="introtext">
			<?if($arResult['PREVIEW_TEXT_TYPE'] == 'text'):?>
				<p><?=$arResult['FIELDS']['PREVIEW_TEXT'];?></p>
			<?else:?>
				<?=$arResult['FIELDS']['PREVIEW_TEXT'];?>
			<?endif;?>
		</div>
	</div>
<?endif;?>


<?// Полоса под вводным текстом: слева описание акции и блоки редактора
   // контент-менеджера, справа — картинка акции. Раньше описание и редактор
   // стояли под списком товаров, а картинка печаталась над заголовком.
   // Позиция картинки из свойства PHOTOPOS больше не разбирается: в новом
   // дизайне место у неё одно.?>
<?
$ndSalePic = $arResult['FIELDS']['DETAIL_PICTURE'] ? $arResult['DETAIL_PICTURE'] : null;
if($ndSalePic){
	$ndPicTitle = strlen($ndSalePic['DESCRIPTION']) ? $ndSalePic['DESCRIPTION'] : (strlen($ndSalePic['TITLE']) ? $ndSalePic['TITLE'] : $arResult['NAME']);
	$ndPicAlt = strlen($ndSalePic['DESCRIPTION']) ? $ndSalePic['DESCRIPTION'] : (strlen($ndSalePic['ALT']) ? $ndSalePic['ALT'] : $arResult['NAME']);
}
?>
<div class="nd-sale-lead<?=($ndSalePic ? ' nd-sale-lead--pic' : '')?>">
	<div class="nd-sale-lead__text">
		<?if(strlen($arResult['FIELDS']['DETAIL_TEXT'])):?>
			<div class="content">
				<?if($arResult['DETAIL_TEXT_TYPE'] == 'text'):?>
					<p><?=$arResult['FIELDS']['DETAIL_TEXT'];?></p>
				<?else:?>
					<?=$arResult['FIELDS']['DETAIL_TEXT'];?>
				<?endif;?>
			</div>
		<?endif;?>

		<div class="editor">
			<?$APPLICATION->IncludeComponent(
				"sprint.editor:blocks",
				".default",
				Array(
					"ELEMENT_ID" => $arResult["ID"],
					"IBLOCK_ID" => $arResult["IBLOCK_ID"],
					"PROPERTY_CODE" => "EDITOR1",
					"USE_JQUERY" => "N",
				),
				$component,
				Array(
					"HIDE_ICONS" => "Y"
				)
			);?>
		</div>
	</div>

	<?if($ndSalePic):?>
		<div class="nd-sale-lead__pic">
			<a href="<?=$ndSalePic['SRC']?>" class="fancy" data-fancybox="nd-sale" title="<?=htmlspecialcharsbx($ndPicTitle)?>">
				<img src="<?=$ndSalePic['SRC']?>" loading="lazy" title="<?=htmlspecialcharsbx($ndPicTitle)?>" alt="<?=htmlspecialcharsbx($ndPicAlt)?>" />
			</a>
		</div>
	<?endif;?>
</div>

<?$list_view = ($arParams['LIST_VIEW'] ? $arParams['LIST_VIEW'] : 'slider');?>
<?// goods links?>

<?if($arResult['PROPERTIES']['LINK_GOODS']['VALUE']):?>
    <div class="wraps goods-block with-padding">
		<?/* Товары акции — тот же список карточек, что на странице бренда
		   («Шаблон №2, вывод списком»): пять в ряд, первые PER_SECTION сразу,
		   остальное по кнопке «Показать ещё». Разметку и параметры каталога
		   даёт include/brand_products.php, догрузку — /local/ajax/promo_products.php.
		   Старый вызов include/news.detail.products_block.php рисовал карточку
		   прежнего дизайна и остался у шаблона news (старый дизайн). */?>
		<?
		$ldBrand = array(
			'MODE' => 'flat',
			'FILTER' => array(
				'ID' => $arResult['PROPERTIES']['LINK_GOODS']['VALUE'],
				'ACTIVE' => 'Y',
			),
			'PER_SECTION' => 20,
			'TITLE' => (strlen($arParams['T_GOODS']) ? $arParams['T_GOODS'] : GetMessage('T_GOODS')),
			'AJAX_URL' => '/local/ajax/promo_products.php',
			'AJAX_QUERY' => 'promo='.(int)$arResult['ID'],
		);
		include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/brand_products.php';
		?>
	</div>
<?endif;?>




<?if($arResult['COMPANY']):?>
	<div class="wraps barnd-block">
		<div class="item-views list list-type-block image_left">
			<?if($arResult['COMPANY']['PROPERTY_SLOGAN_VALUE']):?>
				<div class="slogan"><?=$arResult['COMPANY']['PROPERTY_SLOGAN_VALUE'];?></div>
			<?endif;?>
			<div class="items row">
				<div class="col-md-12">
					<div class="item noborder clearfix">
						<?if($arResult['COMPANY']['IMAGE-BIG']):?>
							<div class="image">
								<a href="<?=$arResult['COMPANY']['DETAIL_PAGE_URL'];?>">
									<img src="<?=$arResult['COMPANY']['IMAGE-BIG']['src'];?>" alt="<?=$arResult['COMPANY']['NAME'];?>" title="<?=$arResult['COMPANY']['NAME'];?>" class="img-responsive">
								</a>
							</div>
						<?endif;?>
						<div class="body-info">
							<?if($arResult['COMPANY']['DETAIL_TEXT']):?>
								<div class="previewtext">
									<?=$arResult['COMPANY']['DETAIL_TEXT'];?>
								</div>
							<?endif;?>
							<?if($arResult['COMPANY']['PROPERTY_SITE_VALUE']):?>
								<div class="properties">
									<div class="inner-wrapper">
										<!-- noindex -->
										<a class="property icon-block site" href="<?=$arResult['COMPANY']['PROPERTY_SITE_VALUE'];?>" target="_blank" rel="nofollow">
											<?=$arResult['COMPANY']['PROPERTY_SITE_VALUE'];?>
										</a>
										<!-- /noindex -->
									</div>
								</div>
							<?endif;?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<hr>
	</div>
<?endif;?>

<?// date active from or dates period active?>
<?if(strlen($arResult['DISPLAY_PROPERTIES']['PERIOD']['VALUE']) || ($arResult['DISPLAY_ACTIVE_FROM'] && in_array('DATE_ACTIVE_FROM', $arParams['FIELD_CODE']))):?>
	<div class="period">
		<?if(strlen($arResult['DISPLAY_PROPERTIES']['PERIOD']['VALUE'])):?>
			<span class="date"><?=$arResult['DISPLAY_PROPERTIES']['PERIOD']['VALUE']?></span>
		<?else:?>
			<span class="date"><?=$arResult['DISPLAY_ACTIVE_FROM']?></span>
		<?endif;?>
	</div>
<?endif;?>

<?// Описание и блоки редактора переехали выше — в полосу .nd-sale-lead
   // сразу под вводным текстом, до списка товаров.?>

	
<?// show link sale?>
<?//$bShowSales =  (count($arResult['DISPLAY_PROPERTIES']['LINK_SALE']['VALUE'])>0);?>
<?/*if($bShowSales):?>
	<?$GLOBALS['arrSaleFilter'] = array('ID' => $arResult['DISPLAY_PROPERTIES']['LINK_SALE']['VALUE']); ?>
	<div class="stockblock">
	<?$APPLICATION->IncludeComponent(
		"bitrix:news.list",
		"news1",
		array(
			"IBLOCK_TYPE" => "aspro_next_content",
			"IBLOCK_ID" => CNextCache::$arIBlocks[SITE_ID]["aspro_next_content"]["aspro_next_news"][0],
			"NEWS_COUNT" => "20",
			"SORT_BY1" => "SORT",
			"SORT_ORDER1" => "ASC",
			"SORT_BY2" => "ID",
			"SORT_ORDER2" => "DESC",
			"FILTER_NAME" => "arrSaleFilter",
			"FIELD_CODE" => array(
				0 => "NAME",
				1 => "PREVIEW_TEXT",			
				3 => "DATE_ACTIVE_FROM",
				4 => "",
			),
			"PROPERTY_CODE" => array(
				0 => "PERIOD",
				1 => "REDIRECT",
				2 => "",
			),
			"CHECK_DATES" => "Y",
			"DETAIL_URL" => "",
			"AJAX_MODE" => "N",
			"AJAX_OPTION_JUMP" => "N",
			"AJAX_OPTION_STYLE" => "Y",
			"AJAX_OPTION_HISTORY" => "N",
			"CACHE_TYPE" => "A",
			"CACHE_TIME" => "36000000",
			"CACHE_FILTER" => "Y",
			"CACHE_GROUPS" => "N",
			"PREVIEW_TRUNCATE_LEN" => "",
			"ACTIVE_DATE_FORMAT" => "d.m.Y",
			"SET_TITLE" => "N",
			"SET_STATUS_404" => "N",
			"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
			"ADD_SECTIONS_CHAIN" => "N",
			"HIDE_LINK_WHEN_NO_DETAIL" => "N",
			"PARENT_SECTION" => "",
			"PARENT_SECTION_CODE" => "",
			"INCLUDE_SUBSECTIONS" => "Y",
			"PAGER_TEMPLATE" => ".default",
			"DISPLAY_TOP_PAGER" => "N",
			"DISPLAY_BOTTOM_PAGER" => "Y",
			"PAGER_TITLE" => "�������",
			"PAGER_SHOW_ALWAYS" => "N",
			"PAGER_DESC_NUMBERING" => "N",
			"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
			"PAGER_SHOW_ALL" => "N",
			"VIEW_TYPE" => "table",
			"BIG_BLOCK" => "Y",
			"IMAGE_POSITION" => "left",
			"COUNT_IN_LINE" => "2",
		),
		false, array("HIDE_ICONS" => "Y")
	);?>
	</div>
<?endif;*/?>

<?// order block?>
<?if($arResult['DISPLAY_PROPERTIES']['FORM_ORDER']['VALUE_XML_ID'] == 'YES'):?>
	<table class="order-block">
		<tr>
			<td class="col-md-9 col-sm-8 col-xs-7 valign">
				<div class="text">
					<?$APPLICATION->IncludeComponent(
						'bitrix:main.include',
						'',
						Array(
							'AREA_FILE_SHOW' => 'page',
							'AREA_FILE_SUFFIX' => 'services',
							'EDIT_TEMPLATE' => ''
						)
					);?>
				</div>
			</td>
			<td class="col-md-3 col-sm-4 col-xs-5 valign">
				<div class="btns">
					<span class="btn btn-default btn-lg animate-load" data-event="jqm" data-param-form_id="CALCULATE" data-name="question" >
					<span>Заказать расчет</span>
					</span>
				</div>
			</td>
		</tr>
	</table>
<?endif;?>





<?// display properties?>
<?if($arResult['DISPLAY_PROPERTIES_FORMATTED']):?>
	<div class="wraps">
		<hr/>	
		<h5><?=(strlen($arParams['T_CHARACTERISTICS']) ? $arParams['T_CHARACTERISTICS'] : Loc::getMessage('T_CHARACTERISTICS'))?></h5>
		<div class="chars">
			<div class="char-wrapp">
				<table class="props_table">
					<?foreach($arResult['DISPLAY_PROPERTIES_FORMATTED'] as $PCODE => $arProp):?>
						<tr class="char">
							<td class="char_name">
								<?if($arProp['HINT']):?>
									<div class="hint">
										<span class="icons" data-toggle="tooltip" data-placement="top" title="<?=$arProp['HINT']?>"></span>
									</div>
								<?endif;?>
								<span><?=$arProp['NAME']?></span>
							</td>
							<td class="char_value">
								<span>
									<?if(is_array($arProp['DISPLAY_VALUE'])):?>
										<?foreach($arProp['DISPLAY_VALUE'] as $key => $value):?>
											<?if($arProp['DISPLAY_VALUE'][$key + 1]):?>
												<?=$value.'&nbsp;/ '?>
											<?else:?>
												<?=$value?>
											<?endif;?>
										<?endforeach;?>
									<?else:?>
										<?=$arProp['DISPLAY_VALUE']?>
									<?endif;?>
								</span>
							</td>
						</tr>
					<?endforeach;?>
				</table>
			</div>
		</div>
	</div>
<?endif;?>

<?// gallery?>
<?if($arResult['GALLERY']):?>
	<div class="wraps galerys-block with-padding">
		<hr/>		
		<h5><?=(strlen($arParams['T_GALLERY']) ? $arParams['T_GALLERY'] : Loc::getMessage('T_GALLERY'))?></h5>
		<?if($arParams['GALLERY_TYPE'] == 'small'):?>
			<div class="small-gallery-block">
				<div class="flexslider unstyled front border small_slider custom_flex top_right color-controls" data-plugin-options='{"animation": "slide", "useCSS": true, "directionNav": true, "controlNav" :true, "animationLoop": true, "slideshow": false, "counts": [4, 3, 2, 1]}'>
					<ul class="slides items">
						<?foreach($arResult['GALLERY'] as $i => $arPhoto):?>
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
						<?if(count($arResult["GALLERY"]) > 1):?>
							<div class="small-gallery-wrapper">
								<div class="flexslider unstyled small-gallery center-nav" data-plugin-options='{"slideshow": false, "useCSS": true, "animation": "slide", "animationLoop": true, "itemWidth": 60, "itemMargin": 20, "minItems": 1, "maxItems": 9, "slide_counts": 1, "asNavFor": ".gallery-wrapper .bigs"}' id="carousel1">
									<ul class="slides items">	
										<?foreach($arResult["GALLERY"] as $arPhoto):?>
											<li class="item">
												<img class="img-responsive inline" border="0" src="<?=$arPhoto["THUMB"]["src"]?>" title="<?=$arPhoto['TITLE']?>" alt="<?=$arPhoto['ALT']?>" />
											</li>
										<?endforeach;?>
									</ul>
								</div>
							</div>
						<?endif;?>
						<div class="flexslider big_slider dark bigs color-controls" id="slider" data-plugin-options='{"animation": "slide", "useCSS": true, "directionNav": true, "controlNav" :true, "animationLoop": true, "slideshow": false, "sync": ".gallery-wrapper .small-gallery", "counts": [1, 1, 1]}'>
							<ul class="slides items">
								<?foreach($arResult['GALLERY'] as $i => $arPhoto):?>
									<li class="col-md-12 item">
										<a href="<?=$arPhoto['DETAIL']['SRC']?>" class="fancy" rel="gallery" target="_blank" title="<?=$arPhoto['TITLE']?>">
											<img src="<?=$arPhoto['PREVIEW']['src']?>" class="img-responsive inline" title="<?=$arPhoto['TITLE']?>" alt="<?=$arPhoto['ALT']?>" />
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

<?// docs files?>
<?if($arResult['DISPLAY_PROPERTIES']['DOCUMENTS']['VALUE']):?>
	<div class="wraps docs-block">
		<hr/>
		<h5><?=(strlen($arParams['T_DOCS']) ? $arParams['T_DOCS'] : Loc::getMessage('T_DOCS'))?></h5>
		<div class="files_block">
			<div class="row">
				<?foreach($arResult['PROPERTIES']['DOCUMENTS']['VALUE'] as $arItem):?>
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
<div class="editor">
    <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            "ELEMENT_ID" => $arResult["ID"],
            "IBLOCK_ID" => $arResult["IBLOCK_ID"],
            "PROPERTY_CODE" => "EDITOR2",
            "USE_JQUERY" => "N",
            
        ),
        $component,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>

	
	
	
	
	
	</div>
