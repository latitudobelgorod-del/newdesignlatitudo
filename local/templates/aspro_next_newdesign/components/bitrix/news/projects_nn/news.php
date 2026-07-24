<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>

<?// intro text?>

<div class="text_before_items">
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
<?
$arItemFilter = CNext::GetIBlockAllElementsFilter($arParams);

if($arParams['CACHE_GROUPS'] == 'Y')
{
	$arItemFilter['CHECK_PERMISSIONS'] = 'Y';
	$arItemFilter['GROUPS'] = $GLOBALS["USER"]->GetGroups();
}

$itemsCnt = CNextCache::CIblockElement_GetList(array("CACHE" => array("TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), $arItemFilter, array());?>

<?if(!$itemsCnt):?>
	<div class="alert alert-warning"><?=GetMessage("SECTION_EMPTY")?></div>
<?else:?>

	<?// rss
	if($arParams['USE_RSS'] !== 'N')
		CNext::ShowRSSIcon($arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss']);
	?>
	<?global $arTheme;?>

<?
CModule::IncludeModule("fileman");

CMedialib::Init();

// получим все элементы коллекции с идентификатором 1
$arCollections = CMedialibCollection::GetList(array('arOrder'=>Array('NAME'=>'ASC'),'arFilter' => array('ACTIVE' => 'Y')));

$arItemsMedia = CMedialibItem::GetList(array('SORT' => array('NAME' => 'ASC'), 'arCollections' => array("0" => 11)));
 
//Заполним массив путей к картинкам
$arImgPath= array();
foreach ($arItemsMedia as $arItem){
 $imgPath= $arItem['PATH'];
 $arImgPath[]= $imgPath;
 $imgName= $arItem['NAME'];
  $arImgName[]= $imgName;
        };
?> 


	

<div class="row">
		<div class="col-md-2 col-sm-2 hidden-xs"></div>
			
				<div class="col-md-8 col-sm-12 col-xs-12">
				
				
							<div class="gallery-block-otz">
				<div class="gallery-wrapper">
					<div class="inner">
								<div class="flexslider dark bigs big_slider color-controls" id="slider" data-plugin-options='{"animation": "slide", "useCSS": true, "directionNav": true, "controlNav" :true, "animationLoop": true, "slideshow": false, "sync": ".gallery-wrapper .small-gallery", "counts": [1, 1, 1]}'>
							<ul class="slides items">
								<?foreach($arItemsMedia as $i => $arPhoto):?>
									<li class="col-md-12 item">
										
											<img src="<?=$arPhoto['PATH']?>" class="img-responsive inline" title="<?=$arPhoto['TITLE']?>" alt="<?=$arPhoto['ALT']?>" />									
										
										
									</li>
									
								<?endforeach;?>
							</ul>
						</div>
					</div>
				</div>
			</div>
			</div>

<div class="col-md-2 col-sm-2 hidden-xs"></div>
</div>

		
			
<div>
	Посмотрите на фотографии наших проектов. Выберите то, что понравилось - так нам будет проще понять друг друга.</div>

	<?@include_once('page_blocks/'.$arParams["SECTIONS_TYPE_VIEW"].'.php');?>
<?endif;?>