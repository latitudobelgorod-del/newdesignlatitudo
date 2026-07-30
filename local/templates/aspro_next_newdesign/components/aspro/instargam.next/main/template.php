<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */

$this->setFrameMode(true);?>
<?if($arResult['IS_AJAX'] === 'Y'):?>
	<?if($arResult['ITEMS']):?>
		<?$obParser = new CTextParser;?>
		<div class="item-views front blocks">
			<div class="top_block">
				<h3 class="title_block"><?=$arResult['TITLE']?></h3>
				<a href="https://www.instagram.com/<?=$arResult['USER']['username']?>/" target="_blank"><?=$arResult['ALL_TITLE']?></a>
			</div>
			<div class="instagram clearfix">
				<div class="items row1 flexbox1 flexslider" data-plugin-options='{"animation": "slide", "move": 0, "directionNav": true, "itemMargin":0, "controlNav" :false, "animationLoop": true, "slideshow": false, "slideshowSpeed": 5000, "animationSpeed": 900, "counts": [<?=$arResult['ITEMS_VISIBLE']?>,4,3,2,1]}'>
					<ul class="slides row flexbox">
						<?foreach($arResult['ITEMS'] as $arItem):?>
							<?$arItem['IMAGE'] = $arItem['thumbnail_url'] ? $arItem['thumbnail_url'] : $arItem['media_url'];?>
							<li class="item col-<?=$arResult['ITEMS_VISIBLE']?>">
								<div class="image" style="background:url(<?=$arItem['IMAGE'];?>) center center/cover no-repeat;"><a href="<?=$arItem['permalink']?>" target="_blank" class="scroll-title"><div class="title"><div class="date font_upper_md muted"><span><?=FormatDate('d F', strtotime($arItem['timestamp']), 'SHORT');?></div><div><?=($obParser->html_cut($arItem['caption'], $arResult['TEXT_LENGTH']))?></div></div></a></div>
							</li>
						<?endforeach;?>
					</ul>
				</div>
			</div>
		</div>
	<?endif;?>
<?else:?>
	<div class="instagram_wrapper wide_<?=$arResult['WIDE_BLOCK']?>">
		<div class="maxwidth-theme">
			<div class="instagram_ajax loader_circle"></div>
		</div>
	</div>
<?endif;?>