<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>

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
        <div class="editor padd">
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
        </div>
		<div class="items flexbox">
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
				
		
				
				
				<?if($imageSrc):?>
				
				<div class="row">
		
				<div class="col-md-12" data-ref="mixitup-target">
					<div class="item shadow slice-item noborder<?=($bImage ? '' : ' wti')?><?=($bActiveDate ? ' wdate' : '')?>" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
							<div class="size_video" >
							
				<?			$url = $arItem['PROPERTIES']['LINK_VIDEO']['VALUE'];
		
		preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
		$video_id = $match[1];
    	?>
							
							
							
						<a href='https://www.youtube.com/embed/<?=$video_id?>'  class="gallery" rel="group">
       	   
		   <div class="preview_pic"><img src="<?=$arItem['PREVIEW_PICTURE']['SRC']?>"  class="img-responsive" /></div>
								   	   		   
		   
        </a>
   
					</div>
					
			
						
						
					
						
						
						
						
						
					</div>
				</div>
				
				</div>
					<?endif;?>
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
