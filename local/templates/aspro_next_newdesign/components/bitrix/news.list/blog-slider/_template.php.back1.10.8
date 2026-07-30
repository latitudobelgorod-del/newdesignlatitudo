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
	'breakpoints' => [
		'768' => [
			'slidesPerView' => 2,
		],
	],
];
?>


<div class="swiper  slider-solution blog-slider" data-plugin-options='<?=\Bitrix\Main\Web\Json::encode($gallerySetting);?>'>
	<div class="swiper-wrapper">
		<?foreach ($arResult['ITEMS'] as $i => $arItem):?>
			<?// show preview picture?
			$bImage = (isset($arItem['FIELDS']['PREVIEW_PICTURE']) && $arItem['PREVIEW_PICTURE']['SRC']);
			$imageSrc = ($bImage ? $arItem['PREVIEW_PICTURE']['SRC'] : false);	?>
			<div class='swiper-slide'>
				<div class='blog-slider__item'>
					<div class='line-block line-block--align-flex-start line-block--gap line-block--column-450'>
						<div class='blog-slider__img '>
							<a href="<?=$arItem['DETAIL_PAGE_URL']?>">
								<img src="<?=$imageSrc?>" alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" class="img-responsive" />		
							</a>
						</div>
						<div class='blog-slider__info'>
							<div class='line-block line-block--align-flex-start line-block--column line-block--gap'>
								<div class='blog-slider__section'><?=$arResult['SECTIONS'][$arItem['IBLOCK_SECTION_ID']]['NAME']?></div>
								<div class='blog-slider__title'><a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="dark-color"><?=$arItem['NAME']?></a></div>
								<div class='blog-slider__data' >
									<?if(array_key_exists('PERIOD', $arItem['DISPLAY_PROPERTIES']) && strlen($arItem['DISPLAY_PROPERTIES']['PERIOD']['VALUE'])):?>
										<span class="date-block"><?=$arItem['DISPLAY_PROPERTIES']['PERIOD']['VALUE']?></span>
									<?else:?>
										<span class="date-block"><?=$arItem['DISPLAY_ACTIVE_FROM']?></span>
									<?endif;?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?endforeach?>
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
	<script>moveSectionBlock(".blog-slider");</script>
</div>
