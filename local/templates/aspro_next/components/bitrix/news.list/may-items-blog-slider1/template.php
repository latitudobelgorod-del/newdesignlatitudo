<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);?>
<?if($arResult['ITEMS']):?>
	<div id="portfolio_loader"  class="projects item-views table with-comments">
		<?if($arParams['TITLE_BLOCK']):?>
			<div class="title-block-big"><?=$arParams['TITLE_BLOCK'];?></div>
		<?endif;?>
		<div class="outer_wrap flexslider shadow items border custom_flex top_right" 
		data-plugin-options='{"animation": "slide", "directionNav": true, "itemMargin":10, "controlNav" :false, "animationLoop": true, "slideshow": false, "counts": [4,3,2,1]}'>
			<ul class="rows_block slides row">
				<?foreach($arResult["ITEMS"] as $arItem):?>
					<?
					// edit/add/delete buttons for edit mode
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

					$bImage = isset($arItem['FIELDS']['PREVIEW_PICTURE']) && strlen($arItem['PREVIEW_PICTURE']['SRC']);
					// show active date period
					$bActiveDate = strlen($arItem['DISPLAY_PROPERTIES']['PERIOD']['VALUE']) || ($arItem['DISPLAY_ACTIVE_FROM'] && in_array('DATE_ACTIVE_FROM', (array)$arParams['FIELD_CODE']));
					$bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);
				$bImage = $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'];
				$imageSrc = ($bImage ? $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] : SITE_TEMPLATE_PATH.'/images/noimage.png');
					$res_images = CFile::ResizeImageGet($arItem["PREVIEW_PICTURE"], Array("width" => 355, "height" => 200), BX_RESIZE_IMAGE_EXACT, false);
					?>
					<li class="col-md-4 col-sm-4 col-xs-12">
						<div class="item" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
							<a href="<?=$arItem['DETAIL_PAGE_URL']?>">
								<?// preview picture?>
								<? if ($bImage): ?>
									<div class="image shine <?=($bImage ? "w-picture" : "wo-picture");?>">
										<img src="<?= $arItem['PREVIEW_PICTURE']['SRC']; ?>" alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" class="img-responsive" />
									</div>
								<? endif; ?>
								<div class="info">
									<?// element name?>
									<div class="title dark-color">
										<span><?=$arItem['NAME']?></span>
									</div>
									<div class="comments-wrapper">
										<?// date active period?>
										<?if($bActiveDate):?>
											<div class="period">
												<?if(strlen($arItem['DISPLAY_PROPERTIES']['PERIOD']['VALUE'])):?>
													<?=$arItem['DISPLAY_PROPERTIES']['PERIOD']['VALUE']?>
												<?else:?>
													<?=$arItem['DISPLAY_ACTIVE_FROM']?>
												<?endif;?>
											</div>
										<?endif;?>
										<div class="comments"></div>
									</div>
								</div>
							</a>
						</div>
					</li>
				<?endforeach;?>
			</ul>
		</div>
	</div>
<?endif;?>