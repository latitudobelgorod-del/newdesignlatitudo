<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
	
<?if($arResult["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"])
{

$goy=$arResult["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"];}
else {
	
		$goy=$arResult['SECTIONS'][$arParams['PARENT_SECTION']]['NAME'];

		
}
?>
	<h1 id="pagetitle">Видео YouTube</h1>

		<?if (substr_count($_SERVER['REQUEST_URI'], '/') <=2 ):?>

		<div class="text_before_items">
	<?$APPLICATION->IncludeComponent(
		"bitrix:main.include",
		"",
		Array(
			"AREA_FILE_SHOW" => "page",
			"AREA_FILE_SUFFIX" => "inc",
			"EDIT_TEMPLATE" => ""
		)
	);?>
</div>
	

		<?endif;?>
		
		
	



<?if($arResult['ITEMS']):?>
<?$isAjax = (isset($_GET["AJAX_REQUEST"]) && $_GET["AJAX_REQUEST"] == "Y");?>
<?if(!$isAjax):?>
	<?if($arParams["TITLE"]):?>
		<hr/>
		<h4><?=$arParams["TITLE"];?></h4>
	<?endif;?>
<div class="item-views table-type-block table-elements <?=$templateName;?>">

	<?// top pagination?>
	<?if($arParams['DISPLAY_TOP_PAGER']):?>
		<?=$arResult['NAV_STRING']?>
	<?endif;?>

		<?
		$bHasSection = false;
		if($arParams['PARENT_SECTION'] && (isset($arResult['SECTIONS']) && $arResult['SECTIONS']))
		{
			if(isset($arResult['SECTIONS'][$arParams['PARENT_SECTION']]) && $arResult['SECTIONS'][$arParams['PARENT_SECTION']])
				$bHasSection = true;
		}
		if($bHasSection)
		{
			// edit/add/delete buttons for edit mode
			$arSectionButtons = CIBlock::GetPanelButtons($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['IBLOCK_ID'], 0, $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'], array('SESSID' => false, 'CATALOG' => true));
			$this->AddEditAction($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'], $arSectionButtons['edit']['edit_section']['ACTION_URL'], CIBlock::GetArrayByID($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['IBLOCK_ID'], 'SECTION_EDIT'));
			$this->AddDeleteAction($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'], $arSectionButtons['edit']['delete_section']['ACTION_URL'], CIBlock::GetArrayByID($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['IBLOCK_ID'], 'SECTION_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
			?>
			<div class="section" id="<?=$this->GetEditAreaId($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'])?>">
			<?
		}?>
        
    			<?switch ($regionID) {				
		case 9278:?>
						

<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR1_VRN",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR1_VRN",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR1",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>


		<? break;
		
		case 9277:?>
	
<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR1_BEL",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR1_BEL",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR1",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>


		<? break;
			
		case 10039:?>
				
<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR1_MSK",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR1_MSK",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR1",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>


		<? break;
		default:?>
    
    <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
            "PROPERTY_CODE" => "UF_EDITOR1",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
							
		<? break;						
		}					
		?>

		
		<div class="items row flexbox">
		
<?endif;?>

			<?$arParams['LINE_ELEMENT_COUNT_LIST'] = ($arParams['LINE_ELEMENT_COUNT_LIST'] <=0 ? 3 : $arParams['LINE_ELEMENT_COUNT_LIST']);?>
			<?foreach($arResult['ITEMS'] as $i => $arItem):?>
				<?
				// edit/add/delete buttons for edit mode
				$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
				$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
				// use detail link?
				$bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);
				$bImage = $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'];
				$imageSrc = ($bImage ? $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] : SITE_TEMPLATE_PATH.'/images/noimage.png');
					$res_images = CFile::ResizeImageGet($arItem["PREVIEW_PICTURE"], Array("width" => 355, "height" => 200), BX_RESIZE_IMAGE_EXACT, false);
    
								
				
				$imageDetailSrc = ($bImage ? $arItem['FIELDS']['DETAIL_PICTURE']['SRC'] : false);
				// show active date period
				$bActiveDate = strlen($arItem['DISPLAY_PROPERTIES']['PERIOD']['VALUE']) || ($arItem['DISPLAY_ACTIVE_FROM'] && in_array('DATE_ACTIVE_FROM', $arParams['FIELD_CODE']));

				$class = '';
				if(isset($arItem['SECTIONS']) && $arItem['SECTIONS'])
				{
					foreach($arItem['SECTIONS'] as $id => $name)
					{
						$class .= ' s-'.$id;
					}
				}
				?>
				<div class="col-md-<?=floor(12 / $arParams['LINE_ELEMENT_COUNT_LIST'])?> col-sm-<?=floor(12 / round($arParams['LINE_ELEMENT_COUNT_LIST'] / 2))?> <?=$class;?>" data-ref="mixitup-target">
					<div class="item shadow slice-item noborder<?=($bImage ? '' : ' wti')?><?=($bActiveDate ? ' wdate' : '')?>" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
						<?if($imageSrc):?>
							<div class="image">
								<?if($bDetailLink):?>
									<a href="<?=$arItem['DETAIL_PAGE_URL']?>">
								<?endif;?>
								<? if ($res_images["src"]): ?>
													
												<img src="<?=$res_images["src"]?>"  alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" 
									title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" class="img-responsive" />
												<?else:?>
												<img class="img-responsive" src="/bitrix/templates/aspro_next/images/no_photo_video.jpg" alt="<?=$arItem["NAME"]?>">
										<?endif;?>
										
										
										
								
									
								
				
								
								<?if($bDetailLink):?>
									</a>
								<?endif;?>
								<?if($arParams['SHOW_MORE'] != 'N'):?>
									<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="dark_block_animate">
										<div class="text">
											<div class="cont">
												<div class="titles">
													<div class="text_more"><div class="mores">Смотреть видео</div></div>
												</div>			
											</div>
										</div>
									</a>
								<?endif;?>
							</div>
						<?endif;?>
						<div class="body-info">
							<?// element name?>
							<?if(strlen($arItem['FIELDS']['NAME'])):?>
								<div class="title">
									<?if($bDetailLink):?><a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="dark-color"><?endif;?>
										<?=$arItem['NAME']?>
									<?if($bDetailLink):?>
									<?if(isset($arItem['PROPERTIES']['PRICE_PROJECTS']) && $arItem['PROPERTIES']['PRICE_PROJECTS']['~VALUE']['TEXT']):?>
							<p class="price">≈ <? echo $arItem['PROPERTIES']['PRICE_PROJECTS']['~VALUE'];?></p>
					<?endif;?>
									
									</a><?endif;?>
								</div>
							<?endif;?>

							<?// element preview text?>
							<?/*if(strlen($arItem['FIELDS']['PREVIEW_TEXT'])):?>
								<div class="previewtext">
									<?if($arItem['PREVIEW_TEXT_TYPE'] == 'text'):?>
										<p><?=$arItem['FIELDS']['PREVIEW_TEXT']?></p>
									<?else:?>
										<?=$arItem['FIELDS']['PREVIEW_TEXT']?>
									<?endif;?>
								</div>
							<?endif;*/?>
						
							<?if(isset($arItem['PROPERTIES']['SALE_STIKER']) && $arItem['PROPERTIES']['SALE_STIKER']['~VALUE']['TEXT']):?>
							<div class="stickers_sale_project"><? echo $arItem['PROPERTIES']['SALE_STIKER']['~VALUE'];?></div>
					<?endif;?>
						
						</div>
						
						
					
						
						
						
						
						
					</div>
				</div>
			<?endforeach;?>
		</div>
<?if(!$isAjax):?>
		<?if($bHasSection):?>
			</div>
		<?endif;?>
<?endif;?>

	<?// bottom pagination?>
	<?if($arParams['DISPLAY_BOTTOM_PAGER']):?>
		<div class="bottom_nav" <?=($isAjax ? "style='display: none; '" : "");?>><?=$arResult['NAV_STRING']?></div>
	<?endif;?>
	<?if(!$isAjax):?>
		</div>
	<?endif;?>
<?endif;?>

<div class="editor padd">
    <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
            "PROPERTY_CODE" => "UF_EDITOR2",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
</div>
