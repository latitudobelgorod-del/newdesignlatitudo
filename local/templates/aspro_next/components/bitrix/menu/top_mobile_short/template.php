<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);
global $arTheme, $arRegion, $arRegionLinkCat;
$regionID = ($arRegion ? $arRegion['ID'] : '');
$iVisibleItemsMenu = ($arTheme['MAX_VISIBLE_ITEMS_MENU']['VALUE'] ? $arTheme['MAX_VISIBLE_ITEMS_MENU']['VALUE'] : 15);?>

<?if($arResult):?>
	<div class="menu top">
		<ul class="top">
			<?foreach($arResult as $arItem):?>
				<?$bShowChilds = $arParams['MAX_LEVEL'] > 1;?>
				<?$bParent = $arItem['CHILD'] && $bShowChilds;?>
				<li <?=($arItem['SELECTED'] ? ' class="selected"' : '')?>>
					<a class="dark-color<?=($bParent ? ' parent' : '')?> <?=(($regionID == 9277) ? "".$arSubItem["PARAMS"]["dif_class"]."" : '')?> <?=(($regionID == 9278) ? "".$arSubItem["PARAMS"]["dif_class"]."" : '')?>	<?=(($regionID == 10039) ? "".$arSubItem["PARAMS"]["dif_class"]."" : '')?>	" href="<?=$arItem["LINK"]?>" title="<?=$arItem["TEXT"]?>">
						<span><?=$arItem['TEXT']?></span>
						<?if($bParent):?>
							<span class="arrow"><i class="svg svg_triangle_right"></i></span>
						<?endif;?>
					</a>
				
				</li>
			<?endforeach;?>
		</ul>
	</div>
<?endif;?>