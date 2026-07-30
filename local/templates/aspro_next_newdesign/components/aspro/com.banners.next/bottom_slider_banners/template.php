<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>
<?if($arResult["ITEMS"]):?>
	<?global $arTheme;
	$bHideOnNarrow = $arTheme['BIGBANNER_HIDEONNARROW']['VALUE'] === 'Y';?>
	<div class="top_slider_wrapp maxwidth-banner<?=($bHideOnNarrow ? ' hidden_narrow' : '')?>">
		<?$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.flexslider-min.js',true)?> 
		<div class="flexslider">
			<ul class="slides">
				<?foreach($arResult["ITEMS"] as $arItem):?>
					<?
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
					$background = is_array($arItem["DETAIL_PICTURE"]) ? $arItem["DETAIL_PICTURE"]["SRC"] : $this->GetFolder()."/images/background.jpg";
					$target = $arItem["PROPERTIES"]["TARGETS"]["VALUE_XML_ID"];
					?>
					<li class="box<?=($arItem["PROPERTIES"]["TEXTCOLOR"]["VALUE_XML_ID"] ? " ".$arItem["PROPERTIES"]["TEXTCOLOR"]["VALUE_XML_ID"] : "");?><?=($arItem["PROPERTIES"]["TEXT_POSITION"]["VALUE_XML_ID"] ? " ".$arItem["PROPERTIES"]["TEXT_POSITION"]["VALUE_XML_ID"] : " left");?>" data-nav_color="<?=($arItem["PROPERTIES"]["NAV_COLOR"]["VALUE_XML_ID"] ? $arItem["PROPERTIES"]["NAV_COLOR"]["VALUE_XML_ID"] : "");?>" id="<?=$this->GetEditAreaId($arItem['ID']);?>" style="background-image: url('<?=$background?>') !important;">
						<?if($arItem["PROPERTIES"]["URL_STRING"]["VALUE"]):?>
							<a class="target" href="<?=$arItem["PROPERTIES"]["URL_STRING"]["VALUE"]?>" <?=(strlen($target) ? 'target="'.$target.'"' : '')?>></a>
						<?endif;?>
						<div class="wrapper_inner">	
							<? 
							$position = "0% 100%";
							if($arItem["PROPERTIES"]["TEXT_POSITION"]["VALUE_XML_ID"])
							{
								if($arItem["PROPERTIES"]["TEXT_POSITION"]["VALUE_XML_ID"] == "left")
									$position = "100% 100%";
								elseif($arItem["PROPERTIES"]["TEXT_POSITION"]["VALUE_XML_ID"] == "right")
									$position = "0% 100%";
								else
									$position = "center center";									
							}
							?>
							<table class="table-no-border">
								<tbody><tr>
									<?if($arItem["PROPERTIES"]["TEXT_POSITION"]["VALUE_XML_ID"] != "image"):?>
										<?ob_start();?>
											
										<?$text = ob_get_clean();?>
									<?endif;?>
									<?ob_start();?>
										<td class="img" >
											<?if($arItem["PREVIEW_PICTURE"]):?>
												
												<img class="plaxy" src="<?=$arItem['PREVIEW_PICTURE']['SRC']?>" alt="<?=($arItem['PREVIEW_PICTURE']['ALT'] ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($arItem['PREVIEW_PICTURE']['TITLE'] ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" />
												
											<?endif;?>									
										</td>
									<?$image = ob_get_clean();?>
									
								</tr></tbody>
							</table>
						</div>
					</li>
				<?endforeach;?>
			</ul>
		</div>
	</div>
<?endif;?>