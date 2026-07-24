<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<? $this->setFrameMode( true ); ?>
<?if( !empty( $arResult ) ){?>
	<ul class="menu topest">
		<?foreach( $arResult as $key => $arItem ){?>
			<a href="<?=$arItem["LINK"]?>">
<li class=<?=($arItem["SELECTED"] ? " current" : "")?> >
			<span><?=$arItem["TEXT"]?></span>
			</li></a>
		<?}?>
		<li class="more hidden">
			<span>...</span>
			<ul class="dropdown"></ul>
		</li>
	</ul>
<?}?>