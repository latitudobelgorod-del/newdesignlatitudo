<?if( !defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true ) die();?>
<?$this->setFrameMode(true);?>
<?
if(!function_exists("ShowSubItems")){
	function ShowSubItems($arItem){
		?>
		<?if($arItem["SELECTED"] && $arItem["CHILD"]):?>
			<?$noMoreSubMenuOnThisDepth = false;?>
			<div class="submenu-wrapper">
				<ul class="submenu">
					<?foreach($arItem["CHILD"] as $arSubItem):?>
						<li class="<?=($arSubItem["SELECTED"] ? "active" : "")?><?=($arSubItem["CHILD"] ? " child" : "")?>">
							<a href="<?=$arSubItem["LINK"]?>"><?=$arSubItem["TEXT"]?></a>
							<?if(!$noMoreSubMenuOnThisDepth):?>
								<?ShowSubItems($arSubItem);?>
							<?endif;?>
						</li>
						<?$noMoreSubMenuOnThisDepth |= CNext::isChildsSelected($arSubItem["CHILD"]);?>
					<?endforeach;?>
				</ul>
			</div>
		<?endif;?>
		<?
	}
}
?>
<?if($arResult):?>
	<div class="cabinet-dropdown">
		<div class="dropdown dropdown--relative">
			<?$counter = 1;?>
			<?foreach($arResult as $arItem):?>
				<div class="cabinet-dropdown__item <?=(strlen($arItem["PARAMS"]["class"]) ? $arItem["PARAMS"]["class"] : '')?> <?=($arItem["SELECTED"] ? "active" : "")?> <?=($arItem["CHILD"] ? "child" : "")?> <?=($counter == 1 ? "cabinet-dropdown__item--first" : "")?>">
					<?if( strpos($arItem["LINK"] ,'?logout=yes') !== false ){
						$arItem["LINK"].= '&'.bitrix_sessid_get();
					}?>
					<a class="icons_fa cabinet-dropdown__item--link" href="<?=$arItem["LINK"]?>">
						<?=(isset($arItem["PARAMS"]["BLOCK"]) && $arItem["PARAMS"]["BLOCK"] ? CNext::showIconSvg("cabinet-exit", SITE_TEMPLATE_PATH."/images/svg/exit_icon.svg") : "");?>
						<span class="name"><?=$arItem["TEXT"]?></span>
					</a>
					<?ShowSubItems($arItem);?>
				</div>
				<?$counter++;?>
			<?endforeach;?>
		</div>
	</div>
<?endif;?>