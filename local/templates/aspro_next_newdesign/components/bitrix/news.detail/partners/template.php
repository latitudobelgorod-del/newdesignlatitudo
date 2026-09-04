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

<?// Разметка марки — граф JSON-LD (LatitudoSchema, local/php_interface/include).
   //
   // Прежде здесь стоял блок микроданных с itemprop="brand", но без внешнего
   // itemscope: свойство висело в пустоте и ни к чему не относилось, а
   // валидатор Яндекса такой itemprop просто выбрасывает. Заодно у логотипа
   // стоял <link src>, хотя у link адрес задаётся атрибутом href.
   //
   // Марку описываем как Brand, а саму страницу — как CollectionPage: на ней
   // перечислены товары этой марки. Организация в графе одна на весь сайт —
   // «Латитудо», продавец; производитель марки ей не равен.?>
<?
$ndBrandName = $arResult['NAME'];
$ndBrandLogo = ($arPhoto ?? null) ?: (($arResult['PREVIEW_PICTURE'] ?? null) ?: ($arResult['DETAIL_PICTURE'] ?? null));

LatitudoSchema::printGraph(LatitudoSchema::brandGraph(array(
	'URL' => $arResult['DETAIL_PAGE_URL'],
	'NAME' => $ndBrandName,
	'DESCRIPTION' => ($arResult['IPROPERTY_VALUES']['ELEMENT_META_DESCRIPTION'] ?? '')
		?: ($arResult['PREVIEW_TEXT'] ?? ''),
	'LOGO' => $ndBrandLogo,
)));
?>
	
	

<?/* Шапка бренда по макету Figma «Категория производителя»: имя слева,
   логотип справа на той же строке, под ними описание и только потом якоря.

   Прежде здесь была бутстраповская пара колонок: в левой имя и якоря, в правой
   логотип, а описание печаталось ниже — из-за этого якоря оказывались выше
   описания. Заодно убран <img> с пустым src: он печатался из переменной
   $value, которой в этом шаблоне никто не присваивает значение. */?>
<div class="nd-brandhead">
	<h1 class="nd-brandhead__title" id="pagetitle"><?=$goy?></h1>
	<?if($arImgs):?>
		<div class="nd-brandhead__logo">
			<img src="<?=$arImgs[0]["DETAIL"]["SRC"]?>" title="<?=$arImgs[0]["TITLE"]?>" alt="<?=$arImgs[0]["ALT"]?>" />
		</div>
	<?endif;?>
</div>

<?/* Описание. У большинства марок оно лежит в свойстве EDITOR1 (sprint.editor),
   у части — по-старому в детальном тексте, поэтому печатаем оба. */?>
<div class="nd-brandhead__text editor torgmarks">
	<?if(strlen($arResult["FIELDS"]["PREVIEW_TEXT"].$arResult["FIELDS"]["DETAIL_TEXT"])):?>
		<div class="text">
			<?if($arResult["DETAIL_TEXT_TYPE"] == "text"):?>
				<p><?=$arResult["FIELDS"]["DETAIL_TEXT"];?></p>
			<?else:?>
				<?=$arResult["FIELDS"]["DETAIL_TEXT"];?>
			<?endif;?>
		</div>
	<?endif;?>
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
	<?/* Ссылки на официальный сайт марки здесь нет намеренно: в макете
	   «Категория производителя» её не предусмотрено. Свойство SITE у части
	   брендов заполнено — если понадобится, вернуть недолго. */?>
</div>

<?/*Вывод анкоров на разделы, в зависимости от шаблона детальной*/?>
<?foreach($arResult["DISPLAY_PROPERTIES"] as $pid=>$arProperty):?>
	<?if($pid == "TEMPLATE" && $arProperty["VALUE_XML_ID"] == "temp_numb_1"):?>
		<?/* Якоря считает include/brand_anchors.php шаблона, а не компонент
		   catalog.section из include/news.detail.ankor_section.php: тот ради десятка
		   ссылок вычитывал сотню товаров с ценами и съедал почти всё время страницы
		   (EasyDecking 16.9 → 1.4 секунды). Старый дизайн остался на компоненте. */?>
		<?include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/brand_anchors.php';?>
	<?endif;?>
<?endforeach;?>

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
