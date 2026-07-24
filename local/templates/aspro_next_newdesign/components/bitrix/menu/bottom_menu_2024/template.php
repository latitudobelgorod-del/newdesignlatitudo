<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>
<div class="bottom-menu">
<div class="items">

	<?if (is_array($arResult) && !empty($arResult)):?>
<?$key=count($arResult);
$i = 0;
$count = 5;?>
<?//echo 'key -'.$key;?>


<?foreach( $arResult as $arItem ){?>
<?if ($i < $count):?>
<div class="item-link">
					<div class="item<?=($arItem["SELECTED"] ? " active" : "")?>"><div class="title"><a href="<?=$arItem['LINK']?>" class="dark_link"><?=$arItem['TEXT']?></a></div></div>
				</div>
  <?$i = $i+1;?>
  <?endif?>
	<?}?>

	<?endif;?>
</div>
</div>
<?if ($key > $count):?>
<div><a href="/services/">Смотреть все</div>
<?endif;?>