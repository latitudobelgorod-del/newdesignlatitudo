<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>
<ul class="bottom-menu">
	<?if (is_array($arResult) && !empty($arResult)):?>
<?$key=count($arResult);
$i = 0;?>
<?echo 'key -'.$key;?>
<?while($i < 4) {?>
	
<?foreach( $arResult as $arItem ){?>
		<li class="<?=($arItem["SELECTED"]?" selected":"");?>"><a href="<?=$arItem["LINK"]?>" class="dark_link"><?=$arItem["TEXT"]?></a></li>
		<?if (is_array($arResult["ITEMS"]) && !empty($arResult["ITEMS"])):?>
			<?foreach( $arItem["ITEMS"] as $arSubItem ){?>
				<li class="menu_subitem<?=($arItem["SELECTED"]?" selected":"");?>"><a href="<?=$arSubItem["LINK"]?>"><?=$arSubItem["TEXT"]?></a></li>
			<?}?>
		<?endif;?>
	<?}?>
<?$i = $i++;?>
<?}?>


	
	<?endif;?>
</ul>