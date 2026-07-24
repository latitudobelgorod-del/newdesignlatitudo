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


<div class="swiper  slider-solution blog-slider slider-in-section" data-plugin-options='<?=\Bitrix\Main\Web\Json::encode($gallerySetting);?>'>
	<div class="swiper-wrapper">
		<?foreach ($arResult['ITEMS'] as $i => $arItem):?>
			<?// show preview picture?
			$bImage = (isset($arItem['FIELDS']['PREVIEW_PICTURE']) && $arItem['PREVIEW_PICTURE']['SRC']);
			$imageSrc = ($bImage ? $arItem['PREVIEW_PICTURE']['SRC'] : false);	
			$bActiveDate = strlen($arItem['DISPLAY_PROPERTIES']['PERIOD']['VALUE']) || ($arItem['DISPLAY_ACTIVE_FROM'] && in_array('DATE_ACTIVE_FROM', $arParams['FIELD_CODE']));
			$date_active_to = $arItem["DISPLAY_ACTIVE_TO"]; 
				?>
			<div class='swiper-slide'>
				<div class='blog-slider__item'>
					<div class='line-block line-block--align-flex-start line-block--gap line-block--column-450'>
						<div class='blog-slider__img '>
							<a href="<?=$arItem['DETAIL_PAGE_URL']?>">
								<img src="<?=$imageSrc?>" alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" class="img-responsive" />		
							</a>
						</div>
									
						<div class="news-list__item-date">		
							<?$convers1 = ConvertDateTime($arItem["DATE_ACTIVE_TO"], "DD.MM.YYYY", "ru");
							$date_activeto1 = MakeTimeStamp($arItem["DATE_ACTIVE_TO"], "DD.MM.YYYY HH:MI:SS");
							?>
							<?
							$convers = ConvertDateTime($arItem["DATE_ACTIVE_TO"], "YYYY-MM-DD HH:MI:SS", "ru");
							$date_activeto = MakeTimeStamp($arItem["DATE_ACTIVE_TO"], "DD.MM.YYYY HH:MI:SS");
							?>
							
							
							
									<?/*$startTimeStamp = $time_now;
									$endTimeStamp = $date_activeto;
									$timeDiff = abs($endTimeStamp - $startTimeStamp);
									$numberDays = $timeDiff/86400; 
									
									$numberDays = intval($numberDays);*/
									  
									?>
					
					
					
								<?//Бирка?>
								<?if($bActiveDate):?>						
									<?if ($arItem["DATE_ACTIVE_TO"]):?>
									<?if (($date_activeto) > ($time_now)):?>		
									<div class="akciya_metka" >
									<?/*<div>Осталось дней: <?=$numberDays?></div>*/?>
										
										<div>Акция до <?=$convers1?></div>
										
									</div>
									<?else:?>
										<div class="akciya_metka" >
										<div>Акция завершена</div>
									</div>
									<?endif;?>							
								<?endif;?>
								<?//Бирка?>
								<?endif;?>
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
