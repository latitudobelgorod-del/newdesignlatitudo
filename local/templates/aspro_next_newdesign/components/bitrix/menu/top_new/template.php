<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?php
	global $arRegion,$arTheme, $APPLICATION;
	$regionID = ($arRegion ? $arRegion['ID'] : '');
?>

<?
function chunk(array $array, int $column)
{
	$count = count($array);
	return array_chunk($array, ceil($count/($count/$column)), 1);
}
global $arTheme;
$iVisibleItemsMenu = ($arTheme['MAX_VISIBLE_ITEMS_MENU']['VALUE'] ? $arTheme['MAX_VISIBLE_ITEMS_MENU']['VALUE'] : 10);
?>
<?//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/xxx500.txt', print_r($arResult, 1));?>
<?if($arResult):?>
<div class="table-menu">
		<table>
			<tr>
				<?foreach($arResult as $arItem):?>					
					<?$bShowChilds = ($arParams["MAX_LEVEL"] > 1) && ($ITEM_INDEX <> 2) ;
					$bWideMenu = (isset($arItem['PARAMS']['CLASS']) && strpos($arItem['PARAMS']['CLASS'], 'wide_menu') !== false);
					$bPartneram = (isset($arItem['PARAMS']['CLASS']) && strpos($arItem['PARAMS']['CLASS'], 'partneram') !== false);
					$bProjectcl = (isset($arItem['PARAMS']['CLASS']) && strpos($arItem['PARAMS']['CLASS'], 'project') !== false);
					$bServicescl = (isset($arItem['PARAMS']['CLASS']) && strpos($arItem['PARAMS']['CLASS'], 'services') !== false);
					$bSalel = (isset($arItem['PARAMS']['CLASS']) && strpos($arItem['PARAMS']['CLASS'], 'sale') !== false);
					if(is_array($arItem["CHILD"])){
                        $k = array_chunk($arItem["CHILD"], 4);
                    }
                    ?>
					<td class="menu-item unvisible <?=($arItem["CHILD"] ? "dropdown" : "")?> <?=(isset($arItem["PARAMS"]["CLASS"]) ? $arItem["PARAMS"]["CLASS"] : "");?>  <?=($arItem["SELECTED"] ? "active" : "")?>">
						<div class="wrap">
					
					<?if(!$bPartneram):?>
					<a class="<?=($arItem["CHILD"] && $bShowChilds ? "dropdown-toggle" : "")?> " href="<?=$arItem["LINK"]?>">
					<div class="<?=(isset($arItem["PARAMS"]["ICON_FILE"]) ? "s_icon" : "")?>"><?=$arItem["TEXT"]?><div class="line-wrapper"><span class="line"></span></div></div>
					</a>
					<?else:?>
					<div class="partners_menu <?=(isset($arItem["PARAMS"]["ICON_FILE"]) ? "s_icon" : "")?>"><?=$arItem["TEXT"]?><div class="line-wrapper"><span class="line"></span></div></div>

					<?endif;?>	
					
					
							<?if($arItem["CHILD"] && $bShowChilds):?>
								<span class="tail"></span>
									<?if((!$bProjectcl) && (!$bServicescl)):?>
									<ul class="dropdown-menu"  >	
				
									<?foreach($arItem["CHILD"] as $arSubItem):?>
										<?$bShowChilds = $arParams["MAX_LEVEL"] > 2;?>
										<?$bHasPicture = (isset($arSubItem['PARAMS']['PICTURE']) && $arSubItem['PARAMS']['PICTURE'] && $arTheme['SHOW_CATALOG_SECTIONS_ICONS']['VALUE'] == 'Y');?>
														
										<li class="<?=(($bProjectcl) ? 'project_col' : '')?> <?=($arSubItem["CHILD"] && $bShowChilds ? "dropdown-submenu" : "")?> <?=($arSubItem["SELECTED"] ? "active" : "")?> <?=($bHasPicture ? "has_img" : "")?>">
											<??>
											<a href="<?=$arSubItem["LINK"]?>" title="<?=$arSubItem["TEXT"]?>"><span class="name"><?=$arSubItem["TEXT"]?></span><?=($arSubItem["CHILD"] && $bShowChilds ? '<span class="arrow"><i></i></span>' : '')?></a>
											
											<?if($arSubItem["CHILD"] && $bShowChilds):?>
													<ul class="dropdown-menu">
														<?foreach($arSubItem["CHILD"] as $arSubSubSubItem):?>
														
															<li class="menu-item <?=($arSubSubSubItem["SELECTED"] ? "active" : "")?>">
																<a href="<?=$arSubSubSubItem["LINK"]?>" title="<?=$arSubSubSubItem["TEXT"]?>"><span class="name"><?=$arSubSubSubItem["TEXT"]?></span></a>
															</li>
													
														<?endforeach;?>
													</ul>
										<?endif;?>			
							
							
										</li>
							
								<?endforeach; ?>	
								<div class="clear"></div>
								<?if($bWideMenu):?>
									<? $APPLICATION->IncludeComponent(
									"bitrix:news.list", 
									"2025_brands_list_menu", 
									[
										"SORT_BY_FILTER_ID" => "Y",
										"IBLOCK_TYPE" => "aspro_next_content",
										"IBLOCK_ID" => "12",
										"NEWS_COUNT" => "20",
										"FILTER_NAME" => "",
										"FIELD_CODE" => [
											0 => "PREVIEW_PICTURE",
											1 => "",
										],
										"PROPERTY_CODE" => [
											0 => "",
											1 => "LINK",
											2 => "",
										],
										"CHECK_DATES" => "Y",
										"DETAIL_URL" => "",
										"AJAX_MODE" => "N",
										"AJAX_OPTION_JUMP" => "N",
										"AJAX_OPTION_STYLE" => "Y",
										"AJAX_OPTION_HISTORY" => "N",
										"CACHE_TYPE" => "A",
										"CACHE_TIME" => "36000000",
										"CACHE_FILTER" => "Y",
										"CACHE_GROUPS" => "N",
										"PREVIEW_TRUNCATE_LEN" => "",
										"ACTIVE_DATE_FORMAT" => "j F Y",
										"SET_TITLE" => "N",
										"SET_STATUS_404" => "N",
										"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
										"ADD_SECTIONS_CHAIN" => "N",
										"HIDE_LINK_WHEN_NO_DETAIL" => "N",
										"PARENT_SECTION" => "",
										"PARENT_SECTION_CODE" => "",
										"INCLUDE_SUBSECTIONS" => "Y",
										"PAGER_TEMPLATE" => "",
										"DISPLAY_TOP_PAGER" => "N",
										"DISPLAY_BOTTOM_PAGER" => "N",
										"PAGER_TITLE" => "",
										"PAGER_SHOW_ALWAYS" => "N",
										"PAGER_DESC_NUMBERING" => "N",
										"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
										"PAGER_SHOW_ALL" => "N",
										"AJAX_OPTION_ADDITIONAL" => "",
										"COMPONENT_TEMPLATE" => "2025_brands_list_menu",
										"SET_BROWSER_TITLE" => "N",
										"SET_META_KEYWORDS" => "N",
										"SET_META_DESCRIPTION" => "Y",
										"SET_LAST_MODIFIED" => "N",
										"PAGER_BASE_LINK_ENABLE" => "N",
										"SHOW_404" => "N",
										"MESSAGE_404" => "",
										"SORT_BY1" => "SORT",
										"SORT_ORDER1" => "ASC",
										"SORT_BY2" => "SORT",
										"SORT_ORDER2" => "ASC",
										"STRICT_SECTION_CHECK" => "N",
										"SHOW_DETAIL_LINK" => "Y",
										"TITLE_BLOCK" => "Бренды",
										"TITLE_BLOCK_ALL" => "Бренды",
										"ALL_URL" => "/brands/"
									],
									false
								); ?>
							<?endif;?>
	
								</ul>
									
											
						<?else:?>	
						
						<?if ($bProjectcl):?>
						<?$children = chunk($arItem["CHILD"], 4);?>
						<?else:?>
						<?$children = chunk($arItem["CHILD"], 6);?>
						<?endif;?>
                      
					
												
						
							<div class="dropdown-menu">
										<? foreach($children as $div): ?>
										<div style="width:100%;position:relative;">
													<?foreach($div as $arSubItem):?>
													<?$bShowChilds = $arParams["MAX_LEVEL"] > 2;?>
													<?$bHasPicture = (isset($arSubItem['PARAMS']['PICTURE']) && $arSubItem['PARAMS']['PICTURE'] && $arTheme['SHOW_CATALOG_SECTIONS_ICONS']['VALUE'] == 'Y');
													?>
													<div class="project_col">	
													<a href="<?=$arSubItem["LINK"]?>" title="<?=$arSubItem["TEXT"]?>">
														<?if(isset($arSubItem['PARAMS']['PICTURE'])):
																		$arImg = CFile::ResizeImageGet($arSubItem['PARAMS']['PICTURE'], array('width' => 60, 'height' => 60), BX_RESIZE_IMAGE_EXACT);
																		if(is_array($arImg)):?>
																			<div class="img"><img src="<?=$arImg["src"]?>" alt="<?=$arSubItem["TEXT"]?>" title="<?=$arSubItem["TEXT"]?><?=$arSubItem["ID"]?>" /></div>
																		<?endif;?>
														<?endif;?>
													<div class="title"><span class="name" style=""><?=$arSubItem["TEXT"]?></span><?=($arSubItem["CHILD"] && $bShowChilds ? '<span class="arrow"><i></i></span>' : '')?></div></a>
													</div>
													<?endforeach;?>
										</div>
							<? endforeach;?>
							</div>
							<?endif;?>
							<?endif;?>
						</div>
					</td>
				<?endforeach;?>
					</tr>
		</table>
	</div>
<?endif;?>