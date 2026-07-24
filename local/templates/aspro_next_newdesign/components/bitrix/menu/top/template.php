<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?
global $arTheme, $noMegaMenu;
$iVisibleItemsMenu = ($arTheme['MAX_VISIBLE_ITEMS_MENU']['VALUE'] ? $arTheme['MAX_VISIBLE_ITEMS_MENU']['VALUE'] : 10);
$bManyItemsMenu = ($arTheme['USE_BIG_MENU']['VALUE'] == 'Y');

if ($noMegaMenu) {
	$bManyItemsMenu = false;
}
?>
<?if($arResult):?>
	<?if (!function_exists('showSubItemss')) {
		function showSubItemss($arParams = [
			'HAS_PICTURE' => false,
			'HAS_ICON' => false,
			'WIDE_MENU' => false,
			'SHOW_CHILDS' => false,
			'VISIBLE_ITEMS_MENU' => 0,
			'MAX_LEVEL' => 0,
			'ITEM' => [],
		]){?>
			<?if($arParams['HAS_PICTURE'] && $arParams['WIDE_MENU']):
				if ($arParams['ITEM']['PARAMS']['UF_CATALOG_ICON']) {
					$arImg=CFile::ResizeImageGet($arParams['ITEM']['PARAMS']['UF_CATALOG_ICON'], Array('width'=>50, 'height'=>50), BX_RESIZE_IMAGE_PROPORTIONAL, true);													
				} elseif($arParams['ITEM']['PARAMS']['PICTURE']) {
					$arImg=CFile::ResizeImageGet($arParams['ITEM']['PARAMS']['PICTURE'], array('width' => 60, 'height' => 60), BX_RESIZE_IMAGE_PROPORTIONAL);													
				}
																
				if(is_array($arImg)):?>
					<a href="<?=$arParams['ITEM']["LINK"]?>" title="<?=$arParams['ITEM']["TEXT"]?>">
						<div class="menu_img"><img src="<?=$arImg["src"]?>" alt="<?=$arParams['ITEM']["TEXT"]?>" title="<?=$arParams['ITEM']["TEXT"]?>" /></div>
					</a>
				<?endif;?>
			<?endif;?>
			<a href="<?=$arParams['ITEM']["LINK"]?>" title="<?=$arParams['ITEM']["TEXT"]?>"><span class="name"><?=$arParams['ITEM']["TEXT"]?></span><?=($arParams['ITEM']["CHILD"] && $arParams['SHOW_CHILDS'] ? '<span class="arrow"><i></i></span>' : '')?></a>
			<?if($arParams['ITEM']["CHILD"] && $arParams['SHOW_CHILDS']):?>
				<?$iCountChilds = count($arParams['ITEM']["CHILD"]);?>
				<?$iVisibleItemsMenu = $arParams['VISIBLE_ITEMS_MENU'];?>
				<ul class="dropdown-menu toggle_menu">
					<?foreach($arParams['ITEM']["CHILD"] as $key => $arSubSubItem):?>
						<?$bShowChilds = $arParams["MAX_LEVEL"] > 3;?>
						<li class="menu-item <?=(++$key > $iVisibleItemsMenu ? 'collapsed' : '');?> <?=($arSubSubItem["CHILD"] && $bShowChilds ? "dropdown-submenu" : "")?> <?=($arSubSubItem["SELECTED"] ? "active" : "")?>">
							<a href="<?=$arSubSubItem["LINK"]?>" title="<?=$arSubSubItem["TEXT"]?>"><span class="name"><?=$arSubSubItem["TEXT"]?></span></a>
							<?if($arSubSubItem["CHILD"] && $bShowChilds):?>
								<ul class="dropdown-menu">
									<?foreach($arSubSubItem["CHILD"] as $arSubSubSubItem):?>
										<li class="menu-item <?=($arSubSubSubItem["SELECTED"] ? "active" : "")?>">
											<a href="<?=$arSubSubSubItem["LINK"]?>" title="<?=$arSubSubSubItem["TEXT"]?>"><span class="name"><?=$arSubSubSubItem["TEXT"]?></span></a>
										</li>
									<?endforeach;?>
								</ul>
								
							<?endif;?>
						</li>
					<?endforeach;?>
					<?if($iCountChilds > $iVisibleItemsMenu && $arParams['WIDE_MENU']):?>
						<li><span class="colored more_items with_dropdown"><?=\Bitrix\Main\Localization\Loc::getMessage("S_MORE_ITEMS");?></span></li>
					<?endif;?>
				</ul>
			<?endif;?>
		<?}?>
	<?}?>
	<div class="table-menu ">
		<table>
			<tr>
				<?foreach($arResult as $arItem):?>					
					<?
					$bShowChilds = $arParams["MAX_LEVEL"] > 1;
					$bWideMenu = (isset($arItem['PARAMS']['CLASS']) && strpos($arItem['PARAMS']['CLASS'], 'wide_menu') !== false);
					$arItem['bManyItemsMenu'] = $bManyItemsMenu;
					if(!$bWideMenu) {
						$arItem['bManyItemsMenu'] = false;
					}
					?>
					<td class="menu-item unvisible <?=($arItem["CHILD"] ? "dropdown" : "")?> <?=(isset($arItem["PARAMS"]["CLASS"]) ? $arItem["PARAMS"]["CLASS"] : "");?>  <?=($arItem["SELECTED"] ? "active" : "")?>">
						<div class="wrap">
							<a class="<?=($arItem["CHILD"] && $bShowChilds ? "dropdown-toggle" : "")?>" href="<?=$arItem["LINK"]?>">
								<div>
									<?if(isset($arItem["PARAMS"]["CLASS"]) && strpos($arItem["PARAMS"]["CLASS"], "sale_icon") !== false):?>
										<?=CNext::showIconSvg('sale', SITE_TEMPLATE_PATH.'/images/svg/Sale.svg', '', '');?>
									<?endif;?>
									<?=$arItem["TEXT"]?>
									<div class="line-wrapper"><span class="line"></span></div>
								</div>
							</a>
							<?if($arItem["CHILD"] && $bShowChilds):?>
								<span class="tail"></span>
								<div class="dropdown-menu <?=$arItem['bManyItemsMenu'] ? 'long-menu-items' : ''?>">
									<?if($arItem['bManyItemsMenu']):?>
										<div class="menu-navigation">
											<div class="menu-navigation__sections-wrapper">
												<div class="customScrollbar scrollbar">
													<div class="menu-navigation__sections">
														<?foreach($arItem["CHILD"] as $arChild):?>
															<div class="menu-navigation__sections-item<?=($arChild['SELECTED'] ? " active" : "");?>">
																<?$bShowImg = ((isset($arChild['PARAMS']['PICTURE']) && $arChild['PARAMS']['PICTURE'] || (isset($arChild['PARAMS']['UF_CATALOG_ICON']))) && $arTheme['LEFT_BLOCK_CATALOG_ICONS']['VALUE'] == 'Y');
																$bIcon = (isset($arChild['PARAMS']['UF_CATALOG_ICON'])) && $arChild['PARAMS']['UF_CATALOG_ICON'];?>

																<a
																	href="<?=$arChild['LINK']?>"
																	class="menu-navigation__sections-item-link font_xs menu-navigation__sections-item-link--fa <?=($arChild["SELECTED"] ? "colored_theme_text" : "dark_link")?> <?=($bShowImg ? " menu-navigation__sections-item-link--image" : "");?><?=($arChild['CHILD'] ? " menu-navigation__sections-item-dropdown" : "");?>"
																>
																	<?if($arChild['CHILD']):?>
																		<?=CNext::showIconSvg("right", SITE_TEMPLATE_PATH.'/images/svg/trianglearrow_right.svg', '', '');?>
																	<?endif;?>
																	<span class="menu-navigation__sections-item-link-inner">
																		<?if($bShowImg){?>
																			<span class="image colored_theme_svg ">
																				<?$imageID = ((isset($arChild['PARAMS']['UF_CATALOG_ICON']) && $arChild['PARAMS']['UF_CATALOG_ICON']) ? $arChild['PARAMS']['UF_CATALOG_ICON'] : $arChild['PARAMS']['PICTURE']);?>
																				<?$image = CFile::GetPath($imageID);?>
																				<?if(strpos($image, ".svg1") !== false):?>
																					<?=CNext::showIconSvg("cat_icons light-ignore", $image);?>
																				<?else:?>
																					<img src="<?=$image;?>" alt="<?=$arChild["NAME"];?>" title="<?=$arChild["NAME"]?>" />
																				<?endif;?>
																			</span>
																		<?}?>
																		<span class="name"><?=$arChild['TEXT'];?></span>
																	</span>
																</a>
															</div>
														<?endforeach;?>
													</div>
												</div>
											</div>
											<div class="menu-navigation__content">
									<?endif;?>

										<div class="customScrollbar scrollbar">
											<ul class="menu-wrapper " >
												<?foreach($arItem["CHILD"] as $arSubItem):?>
													<?
													$bShowChilds = $arParams["MAX_LEVEL"] > 2;
													$bHasSvgIcon = (isset($arSubItem['PARAMS']['UF_CATALOG_ICON']) && $arSubItem['PARAMS']['UF_CATALOG_ICON']);
													$bHasImg = (isset($arSubItem['PARAMS']['PICTURE']) && $arSubItem['PARAMS']['PICTURE']);
													$bHasPicture = (($bHasSvgIcon || $bHasImg) && $arTheme['SHOW_CATALOG_SECTIONS_ICONS']['VALUE'] == 'Y');
													$bShowAsBlock = !$bHasPicture && $arSubItem["SUB_ITEMS_IS"];
													?>
													<?//var_dump($arSubItem["SUB_ITEMS_IS"]);?>
													<li class="<?=($arSubItem["CHILD"] && $bShowChilds ? "dropdown-submenu" : "")?> <?=($bShowAsBlock ? "show_as_block" : "")?> <?=($arSubItem["SELECTED"] ? "active" : "")?> <?=($bHasPicture ? "has_img" : "")?> parent-items">
														<?if($arItem['bManyItemsMenu']):?>
															<div class="subitems-wrapper">
																<ul class="menu-wrapper" >
																	<?foreach($arSubItem["CHILD"] as $arSubItem2):?>
																		<?
																		$bHasPicture = ( (isset($arSubItem2['PARAMS']['PICTURE']) && $arSubItem2['PARAMS']['PICTURE'] || (isset($arSubItem2['PARAMS']['UF_CATALOG_ICON'])) ) && $arTheme['SHOW_CATALOG_SECTIONS_ICONS']['VALUE'] == 'Y');
																		$bIcon = (isset($arSubItem2['PARAMS']['UF_CATALOG_ICON'])) && $arSubItem2['PARAMS']['UF_CATALOG_ICON'];
																		?>
																		<li class="<?=($arSubItem2["CHILD"] && $bShowChilds ? "dropdown-submenu" : "")?> <?=$bIcon ? 'icon' : ''?> <?=($arSubItem2["SELECTED"] ? "active" : "")?> <?=($bHasPicture ? "has_img" : "")?>">
																			<?=showSubItemss([
																				'HAS_PICTURE' => $bHasPicture,
																				'HAS_ICON' => $bIcon,
																				'WIDE_MENU' => true,
																				'SHOW_CHILDS' => $bShowChilds,
																				'VISIBLE_ITEMS_MENU' => $iVisibleItemsMenu,
																				'ITEM' => $arSubItem2,
																				'MAX_LEVEL' => $arParams["MAX_LEVEL"]
																			]);?>
																		</li>
																	<?endforeach;?>
																</ul>
															</div>
														<?else:?>
															<?=showSubItemss([
																'HAS_PICTURE' => $bHasPicture,
																'HAS_ICON' => $bIcon,
																'WIDE_MENU' => $bWideMenu,
																'SHOW_CHILDS' => $bShowChilds,
																'VISIBLE_ITEMS_MENU' => $iVisibleItemsMenu,
																'ITEM' => $arSubItem,
																'MAX_LEVEL' => $arParams["MAX_LEVEL"]
															]);?>
														<?endif;?>
														<?/*
														<?if($bHasPicture && $bWideMenu):
															if($arSubItem['PARAMS']['UF_CATALOG_ICON'])
															{
																$arImg=CFile::ResizeImageGet($arSubItem['PARAMS']['UF_CATALOG_ICON'], Array('width'=>50, 'height'=>50), BX_RESIZE_IMAGE_PROPORTIONAL, true);													
															}
															elseif($arSubItem['PARAMS']['PICTURE']){
																$arImg=CFile::ResizeImageGet($arSubItem['PARAMS']['PICTURE'], array('width' => 60, 'height' => 60), BX_RESIZE_IMAGE_PROPORTIONAL);													
															}
																											
															if(is_array($arImg)):?>
																<div class="menu_img"><img src="<?=$arImg["src"]?>" alt="<?=$arSubItem["TEXT"]?>" title="<?=$arSubItem["TEXT"]?>" /></div>
															<?endif;?>
														<?endif;?>
														<a href="<?=$arSubItem["LINK"]?>" title="<?=$arSubItem["TEXT"]?>"><span class="name"><?=$arSubItem["TEXT"]?></span><?=($arSubItem["CHILD"] && $bShowChilds ? '<span class="arrow"><i></i></span>' : '')?></a>
														<?if($arSubItem["CHILD"] && $bShowChilds):?>
															<?$iCountChilds = count($arSubItem["CHILD"]);?>
															<ul class="dropdown-menu toggle_menu">
																<?foreach($arSubItem["CHILD"] as $key => $arSubSubItem):?>
																	<?$bShowChilds = $arParams["MAX_LEVEL"] > 3;?>
																	<li class="menu-item <?=(++$key > $iVisibleItemsMenu ? 'collapsed' : '');?> <?=($arSubSubItem["CHILD"] && $bShowChilds ? "dropdown-submenu" : "")?> <?=($arSubSubItem["SELECTED"] ? "active" : "")?>">
																		<a href="<?=$arSubSubItem["LINK"]?>" title="<?=$arSubSubItem["TEXT"]?>"><span class="name"><?=$arSubSubItem["TEXT"]?></span></a>
																		<?if($arSubSubItem["CHILD"] && $bShowChilds):?>
																			<ul class="dropdown-menu">
																				<?foreach($arSubSubItem["CHILD"] as $arSubSubSubItem):?>
																					<li class="menu-item <?=($arSubSubSubItem["SELECTED"] ? "active" : "")?>">
																						<a href="<?=$arSubSubSubItem["LINK"]?>" title="<?=$arSubSubSubItem["TEXT"]?>"><span class="name"><?=$arSubSubSubItem["TEXT"]?></span></a>
																					</li>
																				<?endforeach;?>
																			</ul>
																			
																		<?endif;?>
																	</li>
																<?endforeach;?>
																<?if($iCountChilds > $iVisibleItemsMenu && $bWideMenu):?>
																	<li><span class="colored more_items with_dropdown"><?=\Bitrix\Main\Localization\Loc::getMessage("S_MORE_ITEMS");?></span></li>
																<?endif;?>
															</ul>
														<?endif;?>
														*/?>
													</li>
												<?endforeach;?>
											</ul>
										</div>

									<?if($arItem['bManyItemsMenu']):?>
											</div>
										</div>
									<?endif;?>

								</div>
							<?endif;?>
						</div>
					</td>
				<?endforeach;?>

				<td class="menu-item dropdown js-dropdown nosave unvisible">
					<div class="wrap">
						<a class="dropdown-toggle more-items" href="#">
							<span><?=\Bitrix\Main\Localization\Loc::getMessage("S_MORE_ITEMS");?></span>
						</a>
						<span class="tail"></span>
						<ul class="dropdown-menu"></ul>
					</div>
				</td>

			</tr>
		</table>
	</div>
<?endif;?>