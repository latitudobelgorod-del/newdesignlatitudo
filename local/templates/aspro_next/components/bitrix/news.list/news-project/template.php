<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?php global $arRegion, $APPLICATION;
	$regionID = ($arRegion ? $arRegion['ID'] : '');
?>

<?if($arResult["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"])
{

$goy=$arResult["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"];}
else {
	
		$goy=$arResult['SECTIONS'][$arParams['PARENT_SECTION']]['NAME'];


}
?>
<?
	$get_fields = CIBlockSection::GetList(
		array(),
		array(
			'IBLOCK_ID' => 18,
			'ID' => $arResult['SECTION']['ID']
		),
		false,
		array(
			'UF_EDITOR1',
'UF_EDITOR1_MSK',
'UF_EDITOR1_BEL',
'UF_EDITOR1_VRN',
'UF_EDITOR2',
'UF_EDITOR2_MSK',
'UF_EDITOR2_BEL',
'UF_EDITOR2_VRN'
		)
	);
	
	if($get_fields_item = $get_fields->GetNext()) { 

		//$my_fields_1 = $get_fields_item['UF_EDITOR1_MSK'];
		//	echo 	$my_fields_1;		
	}


	?>
				<h1 id="pagetitle"><?=$goy?></h1>
				
<?if($arResult['ITEMS']):?>

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

	  <div class="editor padd">
           <? switch ($regionID) {
                            case 9278:
                                ?>
								<?if (!empty($get_fields_item['UF_EDITOR1_VRN'])):?>

                               <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR1_VRN",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
                           <? else: ?>
						<?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR1",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
                            <? endif; ?>
                                <? break;

                            case 9277:
                                ?>
								
                   <?if (!empty($get_fields_item['UF_EDITOR1_BEL'])):?>
                              
                               <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR1_BEL",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>

                            <? else: ?>
                                 <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR1",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
                            <? endif; ?>
                                <? break;
                               case 10039:
                                ?>
                                                 <?if (!empty($get_fields_item['UF_EDITOR1_MSK'])):?>
                              
                               <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR1_MSK",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>

                            <? else: ?>
                                <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR1",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
                            <? endif; ?>

                                <? break;
                            default:
                                ?>
                                 <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
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

                        }?>
						
        </div>
	<div class="items row">
       

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
									<img src="<?=$res_images["src"]?>"  alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" class="img-responsive" /
								
				
								
								<?if($bDetailLink):?>
									</a>
								<?endif;?>
								<?if($arParams['SHOW_MORE'] != 'N'):?>
									<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="dark_block_animate">
										<div class="text">
											<div class="cont">
												<div class="titles">
													<div class="text_more"><div class="mores">Смотреть проект</div></div>
												</div>			
											</div>
										</div>
									</a>
								<?endif;?>
							</div>
						<?endif;?>
				<div class="body-info">
						
										<?if(((isset($arItem['PROPERTIES']['REVIEW']) && $arItem['PROPERTIES']['REVIEW']['~VALUE']['TEXT'])) || ($arItem['PROPERTIES']['LINK_YOUTUBE']['VALUE']) || ($arItem['PROPERTIES']['GALLEY_BIG']['VALUE'])):?>
					<div class="stickers">
					
					<?/*if($arItem['PROPERTIES']['LINK_YOUTUBE']['VALUE']):?>
					<div class="sticker_video"><div class="fa-caret" ><i class="fa fa-caret-right"></i></div>Есть видео</div>
					<?endif;*/?>
						<?if($arItem['PROPERTIES']['VIDEO']['VALUE']):?>
					 <div class="sticker_video"><div class="fa-caret" ><i class="fa fa-caret-right"></i></div>Есть видео</div>
					<?endif;?>
					
					
					
					<?if($arItem['PROPERTIES']['GALLEY_BIG']['VALUE']):
					$count_photo = 0;?>
						<?foreach ($arItem['PROPERTIES']['GALLEY_BIG']['VALUE'] as $arPhoto):
					$count_photo++;?>
					<?endforeach;?>
					<div class="sticker_photo" ><?=$count_photo?> фото</div>
					<?endif;?>
					
					<?if((isset($arItem['PROPERTIES']['REVIEW']) && $arItem['PROPERTIES']['REVIEW']['~VALUE']['TEXT'])):?>
					<div class="sticker_review">Есть отзыв</div>
					<?endif;?>
					
					</div>
						<?endif;?>
						
						
						
						
							<?// element name?>
							<?if(strlen($arItem['FIELDS']['NAME'])):?>
								<div class="title">
									<?if($bDetailLink):?>
									<a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="dark-color"><?endif;?>
										<?=$arItem['NAME']?>
									<?if($bDetailLink):?>
																		
									</a><?endif;?>
								</div>
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
	
		</div>
	
<?endif;?>

  <div class="editor padd">
           <? switch ($regionID) {
                            case 9278:
                                ?>
								<?if (!empty($get_fields_item['UF_EDITOR2_VRN'])):?>

                               <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR2_VRN",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
                           <? else: ?>
						<?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR2",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
                            <? endif; ?>
                                <? break;

                            case 9277:
                                ?>
								
                   <?if (!empty($get_fields_item['UF_EDITOR2_BEL'])):?>
                              
                               <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR2_BEL",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>

                            <? else: ?>
                                 <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR2",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
                            <? endif; ?>
                                <? break;
                               case 10039:
                                ?>
                                                 <?if (!empty($get_fields_item['UF_EDITOR2_MSK'])):?>
                              
                               <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR2_MSK",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>

                            <? else: ?>
                                <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR2",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
                            <? endif; ?>

                                <? break;
                            default:
                                ?>
                                 <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $get_fields_item['ID'],
            "PROPERTY_CODE" => "UF_EDITOR2",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>

                                <? break;

                        }?>
						
        </div>