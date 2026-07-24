<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);
global $arTheme, $arRegion, $arRegionLinkCat;
$regionID = ($arRegion ? $arRegion['ID'] : '');
?>

<div class="bottom-menu">
<div class="items">
			<?foreach($arResult as $arItem):?>
				
				
					
					<?$key=count($arItem['CHILD']);
					$i = 0;
					$count = 5;?>
					
						
							<?foreach($arItem['CHILD'] as $arSubItem):?>
							
												
							<?if ($i < $count):?>	
											<div class="item-link">
												<div class="item<?=($arSubItem["SELECTED"] ? " active" : "")?>"><div class="title"><a href="<?=$arSubItem['LINK']?>"><?=$arSubItem['TEXT']?></a></div></div>
											</div>	
							<?$i = $i+1;?>
							<?endif?>		
							<?endforeach;?>
					
			<?endforeach;?>
	
<br>
<?if ($key > $count):?>
<div><a href="/services/">Смотреть все</a></div>
<?endif;?>
</div>
</div>

