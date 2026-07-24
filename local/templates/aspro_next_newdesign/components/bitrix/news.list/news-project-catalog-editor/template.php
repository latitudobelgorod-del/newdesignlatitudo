<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>




<?$this->setFrameMode(true);?>
<?global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');?>

<?if($arResult['ITEMS']):?>
<?$isAjax = (isset($_GET["AJAX_REQUEST"]) && $_GET["AJAX_REQUEST"] == "Y");?>
<?if(!$isAjax):?>

<div class="item-views table-type-block table-elements <?=$templateName;?>">


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

			
				
				
				
				
				

		
		<div class="items row flexbox portfolio_list">
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
				<div class="col-md-4 col-sm-4 col-xs-12 <?=$class;?>" data-ref="mixitup-target">
					<div class="item shine shadow slice-item noborder<?=($bImage ? '' : ' wti')?><?=($bActiveDate ? ' wdate' : '')?>" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
						<?if($imageSrc):?>
							<div class="image">
								<?if($bDetailLink):?>
									<a href="<?=$arItem['DETAIL_PAGE_URL']?>">
								<?endif;?>
									<img class="lazy img-responsive" src="/assets/lazyload/loading.gif" data-original="<?=$res_images["src"]?>"  alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" >
									<?if($bDetailLink):?>
									</a>
								<?endif;?>
								
								
							</div>
						<?endif;?>
						<div class="body-info">

						
						
						
						
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
	<?if(!$isAjax):?>
		</div>
	<?endif;?>
<?endif;?>
