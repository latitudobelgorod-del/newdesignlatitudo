<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Колонка «Услуги» в подвале нового дизайна.
 * Меню `footer_services_newdesign` состоит из одного корневого пункта «Услуги»,
 * а сами услуги приезжают его детьми из /services/.left.menu_ext.php
 * (там же они фильтруются по региону). Поэтому выводим не корни, а их детей.
 */
$this->setFrameMode(true);
if(!$arResult) return;

$arItems = array();
foreach($arResult as $arItem)
{
	if($arItem["PERMISSION"] == "D") continue;
	if($arItem["CHILD"])
	{
		foreach($arItem["CHILD"] as $arChild)
		{
			if($arChild["PERMISSION"] == "D") continue;
			$arItems[] = $arChild;
		}
	}
	elseif($arItem["DEPTH_LEVEL"] > 1)
	{
		$arItems[] = $arItem;
	}
}
if(!$arItems) return;
?>
<ul class="nd-fmenu__list">
	<?foreach($arItems as $arItem):?>
		<li class="nd-fmenu__item">
			<a class="nd-fmenu__link<?=($arItem["SELECTED"] ? " is-active" : "")?>" href="<?=$arItem["LINK"]?>"><?=$arItem["TEXT"]?></a>
		</li>
	<?endforeach;?>
</ul>
