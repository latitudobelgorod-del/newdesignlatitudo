<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? global $APPLICATION;?>
<?if( !empty( $arResult ) ){?>
<ul class="menu topest">
<?foreach( $arResult as $key => $arItem ){?>
<li class=<?=($arItem["SELECTED"] ? " current" : "em")?>><a href="<?=$arItem["LINK"]?>"><span><?=$arItem["TEXT"]?></span></a></li><?}?>
</ul>
<?}?>