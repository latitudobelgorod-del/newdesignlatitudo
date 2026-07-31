<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Колонка меню в подвале нового дизайна.
 * Плоский список пунктов первого уровня — вложенность в макете подвала не нужна.
 */
$this->setFrameMode(true);
if(!$arResult) return;
?>
<ul class="nd-fmenu__list">
	<?foreach($arResult as $arItem):?>
		<?if($arItem["PERMISSION"] == "D") continue;?>
		<li class="nd-fmenu__item">
			<?if(strlen($arItem["LINK"])):?>
				<a class="nd-fmenu__link<?=($arItem["SELECTED"] ? " is-active" : "")?>" href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?></a>
			<?else:?>
				<span class="nd-fmenu__link"><?=$arItem["TEXT"]?></span>
			<?endif;?>
		</li>
	<?endforeach;?>
</ul>
