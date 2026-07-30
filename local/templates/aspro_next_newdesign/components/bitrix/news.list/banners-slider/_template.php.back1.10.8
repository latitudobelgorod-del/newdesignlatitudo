<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);
if (!$arResult['ITEMS']) return;

$gallerySetting = [
	// Disable preloading of all images
	'preloadImages' => false,
	// Enable lazy loading
	'lazy' => [
		'loadPrevNext' => true,
	],
	'init' => false,
	'keyboard' => [
		'enabled' => true,
	],
	'loop' => false,
	'rewind' => true,
	'pagination' => [
		'enabled' => true,
		'el' => '.swiper-pagination',
		'clickable' => true,
	],
	'slidesPerView' => 1,
];
?>
<div class="swiper slider-solution banners-slider outer-rounded-x hidden" data-plugin-options='<?=\Bitrix\Main\Web\Json::encode($gallerySetting);?>'>
	<div class="swiper-wrapper">
		<?foreach ($arResult['ITEMS'] as $i => $arItem):?>
			<?
			// edit/add/delete buttons for edit mode
			$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
			$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
			
			// show preview picture?
			$bImage = (isset($arItem['FIELDS']['PREVIEW_PICTURE']) && $arItem['PREVIEW_PICTURE']['SRC']);
			$imageSrc = ($bImage ? $arItem['PREVIEW_PICTURE']['SRC'] : false);				
			?>
			<div class="swiper-slide banners-slider__item <?=$arItem['PROPERTIES']['SIZING']['VALUE_XML_ID']?> <?=$arParams['POSITION']?> <?=($arItem['PROPERTIES']['HIDDEN_SM']['VALUE_XML_ID']=='Y'?'hidden-sm':'')?> <?=($arItem['PROPERTIES']['HIDDEN_XS']['VALUE_XML_ID']=='Y'?'hidden-xs':'')?>" <?=($arItem['PROPERTIES']['BGCOLOR']['VALUE']?' style=" background:'.$arItem['PROPERTIES']['BGCOLOR']['VALUE'].';"':'')?> id="<?=$this->GetEditAreaId($arItem['ID'])?>">		
				<?if ($arItem['PROPERTIES']['LINK']['VALUE']):?>
					<a href="<?=$arItem['PROPERTIES']['LINK']['VALUE']?>" <?=($arItem['PROPERTIES']['TARGET']['VALUE_XML_ID'] ? 'target="'.$arItem['PROPERTIES']['TARGET']['VALUE_XML_ID'].'"': '');?>>
				<?endif;?>
					<span class="banners-slider__image swiper-lazy" data-lazyload title="<?=$arItem['NAME']?>" data-background="<?=$imageSrc;?>"></span>
				<?if ($arItem['PROPERTIES']['LINK']['VALUE']):?>
					</a>
				<?endif;?>
			</div>
		<?endforeach;?>
	</div>
	<div class="slider-nav stroke-dark-light swiper-button-prev">
		<?=CNext::showSpriteIconSvg(SITE_TEMPLATE_PATH . '/images/svg/arrows.svg#left-7-12', 'slider-nav__icon', [
			'WIDTH' => 7, 
			'HEIGHT' => 12
		]);?>
	</div>

	<div class="slider-nav stroke-dark-light swiper-button-next">
		<?=CNext::showSpriteIconSvg(SITE_TEMPLATE_PATH . '/images/svg/arrows.svg#right-7-12', 'slider-nav__icon', [
			'WIDTH' => 7, 
			'HEIGHT' => 12
		]);?>
	</div>
	<div class="swiper-pagination swiper-pagination--flex swiper-pagination--flex-center"></div>
	<script>showSectionBanners();</script>
</div>