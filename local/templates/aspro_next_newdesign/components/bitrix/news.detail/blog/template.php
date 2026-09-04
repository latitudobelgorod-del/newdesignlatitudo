<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?use \Bitrix\Main\Localization\Loc;?>

<?// shot top banners start?>
<?/*$bShowTopBanner = (isset($arResult['SECTION_BNR_CONTENT'] ) && $arResult['SECTION_BNR_CONTENT'] == true);?>
<?if($bShowTopBanner):?>
	<?$this->SetViewTarget("section_bnr_content");?>
		<?CNext::ShowTopDetailBanner($arResult, $arParams);?>
	<?$this->EndViewTarget();?>
<?endif;*/?>
<?// shot top banners end?>

<?// Разметка статьи — граф JSON-LD (LatitudoSchema, local/php_interface/include).
   //
   // У страниц «Материалов» разметки не было вовсе: поисковик видел только
   // хлебные крошки и набор ImageObject от редактора блоков, не связанных ни с
   // текстом, ни между собой (проверка микроразметки, Ирина, сентябрь 2026).
   //
   // Фотографии берём детальную и все снимки из редактора блоков (EDITOR1) —
   // тем самым они привязаны к статье, а не висят сами по себе.?>
<?
$ndArtName = $arResult['NAME'];
$ndArtUrl = $arResult['DETAIL_PAGE_URL'];
$ndArtImages = LatitudoSchema::mergeImages(
	(($arResult['DETAIL_PICTURE']['ID'] ?? 0) ? array(LatitudoSchema::image($arResult['DETAIL_PICTURE'], $ndArtName, $ndArtUrl)) : array()),
	LatitudoSchema::imagesFromEditor($arResult['IBLOCK_ID'], $arResult['ID'], array('EDITOR1'), $ndArtName, $ndArtUrl)
);

LatitudoSchema::printGraph(LatitudoSchema::articleGraph(array(
	'URL' => $ndArtUrl,
	'NAME' => $ndArtName,
	'HEADLINE' => ($arResult['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] ?? '') ?: $ndArtName,
	'DESCRIPTION' => ($arResult['IPROPERTY_VALUES']['ELEMENT_META_DESCRIPTION'] ?? '')
		?: ($arResult['PREVIEW_TEXT'] ?? ''),
	'IMAGES' => $ndArtImages,
	'DATE_PUBLISHED' => ($arResult['ACTIVE_FROM'] ?? '') ?: ($arResult['DATE_CREATE'] ?? ''),
	'DATE_MODIFIED' => $arResult['TIMESTAMP_X'] ?? '',
	'SECTION' => $arResult['SECTION']['NAME'] ?? '',
	'IBLOCK_ID' => $arResult['IBLOCK_ID'],
	'ID' => $arResult['ID'],
)));
?>

<?// element name?>
<?if($arParams['DISPLAY_NAME'] != 'N' && strlen($arResult['NAME'])):?>
	<h2><?=$arResult['NAME']?></h2>
<?endif;?>



<?/*Дополнительные файлы*/?>
						<?
						$arFiles = array();
						if($arResult["PROPERTIES"]["INSTRUCTIONS"]["VALUE"]){
							$arFiles = $arResult["PROPERTIES"]["INSTRUCTIONS"]["VALUE"];
						}
						else{
							//$arFiles = $arResult["SECTION_FULL"]["UF_FILES"];
						}
						if(is_array($arFiles)){
							foreach($arFiles as $key => $value){
								if(!intval($value)){
									unset($arFiles[$key]);
								}
							}
						}
						?>
<?if($arFiles):?>								
								<div class="files_block">
									<div>
									
										<?foreach($arFiles as $arResult1):?>
											<div>
												<?$arFile=CNext::GetFileInfo($arResult1);?>
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
<?endif;?>




<?// date active from or dates period active?>
<?if(strlen($arResult['DISPLAY_PROPERTIES']['PERIOD']['VALUE']) || ($arResult['DISPLAY_ACTIVE_FROM'] && in_array('DATE_ACTIVE_FROM', $arParams['FIELD_CODE']))):?>
	<div class="period-wrapper">
	
		<?if($arResult['SECTIONS']):
			$arResult['SECTIONS']= current($arResult['SECTIONS']);?>
			<span class="section_name">
				//&nbsp;<?=$arResult['SECTIONS']['NAME']?>
			</span>
		<?endif;?>
	</div>
<?endif;?>

<?// single detail image?>
<?if($arResult['FIELDS']['DETAIL_PICTURE']):?>
	<?
	$atrTitle = (strlen($arResult['DETAIL_PICTURE']['DESCRIPTION']) ? $arResult['DETAIL_PICTURE']['DESCRIPTION'] : (strlen($arResult['DETAIL_PICTURE']['TITLE']) ? $arResult['DETAIL_PICTURE']['TITLE'] : $arResult['NAME']));
	$atrAlt = (strlen($arResult['DETAIL_PICTURE']['DESCRIPTION']) ? $arResult['DETAIL_PICTURE']['DESCRIPTION'] : (strlen($arResult['DETAIL_PICTURE']['ALT']) ? $arResult['DETAIL_PICTURE']['ALT'] : $arResult['NAME']));
	?>
	<?if($arResult['PROPERTIES']['PHOTOPOS']['VALUE_XML_ID'] == 'LEFT'):?>
		<div class="detailimage image-left col-md-4 col-sm-4 col-xs-12"><a href="<?=$arResult['DETAIL_PICTURE']['SRC']?>" class="fancybox" title="<?=$atrTitle?>"><img src="<?=$arResult['DETAIL_PICTURE']['SRC']?>" class="img-responsive" title="<?=$atrTitle?>" alt="<?=$atrAlt?>" /></a></div>
	<?elseif($arResult['PROPERTIES']['PHOTOPOS']['VALUE_XML_ID'] == 'RIGHT'):?>
		<div class="detailimage image-right col-md-4 col-sm-4 col-xs-12"><a href="<?=$arResult['DETAIL_PICTURE']['SRC']?>" class="fancybox" title="<?=$atrTitle?>"><img src="<?=$arResult['DETAIL_PICTURE']['SRC']?>" class="img-responsive" title="<?=$atrTitle?>" alt="<?=$atrAlt?>" /></a></div>
	<?elseif($arResult['PROPERTIES']['PHOTOPOS']['VALUE_XML_ID'] == 'TOP'):?>
		<script type="text/javascript">
		$(document).ready(function() {
			$('section.page-top').remove();
			$('<div class="row"><div class="col-md-12"><div class="detailimage image-head"><img src="<?=$arResult['DETAIL_PICTURE']['SRC']?>" class="img-responsive" title="<?=$atrTitle?>" alt="<?=$atrAlt?>"/></div></div></div>').insertBefore('.body > .main > .container > .row');
		});
		</script>
	<?else:?>
		<div class="detailimage image-wide"><a href="<?=$arResult['DETAIL_PICTURE']['SRC']?>" class="fancybox" title="<?=$atrTitle?>"><img src="<?=$arResult['DETAIL_PICTURE']['SRC']?>" class="img-responsive" title="<?=$atrTitle?>" alt="<?=$atrAlt?>" /></a></div>
	<?endif;?>
<?endif;?>

<?// ask question?>

<?if(strlen($arResult['FIELDS']['PREVIEW_TEXT'])):?>
	<div class="preview-text-detail">
		<?if($arResult['PREVIEW_TEXT_TYPE'] == 'text'):?>
			<p><?=$arResult['FIELDS']['PREVIEW_TEXT'];?></p>
		<?else:?>
			<?=$arResult['FIELDS']['PREVIEW_TEXT'];?>
		<?endif;?>
		<hr class="colored_line">
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
    <div class="landing_detail">
    </div>


<?if($arResult['PROPERTIES']['LINKS']['VALUE']):?>
    <div class="wraps links">
        <?foreach($arResult['PROPERTIES']['LINKS']['VALUE'] as $i => $arLink):?>
            <div class="link"><i class="fa fa-caret-right"></i>
                <a href="<?=$arResult['PROPERTIES']['LINKS']['DESCRIPTION'][$i]?>" target="_blank"> <?=$arLink?></a>
            </div>
        <?endforeach;?>
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

<?
$frame = $this->createFrame('video')->begin('');
$frame->setAnimation(true);
?>
<?// video?>
<?if($arResult['VIDEO']):?>
	<div class="wraps">
		<hr />
		<h5><?=(strlen($arParams['T_VIDEO']) ? $arParams['T_VIDEO'] : Loc::getMessage('T_VIDEO'))?></h5>
		<div class="row video">
			<?foreach($arResult['VIDEO'] as $i => $arVideo):?>
				<div class="col-md-6 item">
					<div class="video_body">
						<video id="js-video_<?=$i?>" width="350" height="217"  class="video-js" controls="controls" preload="metadata" data-setup="{}">
							<source src="<?=$arVideo["path"]?>" type='video/mp4' />
							<p class="vjs-no-js">
								To view this video please enable JavaScript, and consider upgrading to a web browser that supports HTML5 video
							</p>
						</video>
					</div>
					<div class="title"><?=(strlen($arVideo["title"]) ? $arVideo["title"] : $i)?></div>
				</div>
			<?endforeach;?>
		</div>
	</div>
<?endif;?>
<?$frame->end();?>
<?if($arResult['TAGS']):?>
	<?$this->SetViewTarget('tags_content');?>
		<div class="search-tags-cloud">
			<div class="title-block-middle"><?=Loc::getMessage('TAGS');?></div>
			<div class="tags">
				<?$arTags = explode(",", $arResult['TAGS']);?>
				<?foreach($arTags as $text):?>
					<a href="<?=SITE_DIR;?>search/index.php?tags=<?=htmlspecialcharsex($text);?>" rel="nofollow"><?=$text;?></a>
				<?endforeach;?>
			</div>
		</div>
	<?$this->EndViewTarget();?>
<?endif;?>
