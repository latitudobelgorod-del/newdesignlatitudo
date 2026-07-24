<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?$objDateTime = new DateTime();
 $time_now = $objDateTime->getTimestamp();
  ?>
<?if ($_SERVER['REQUEST_URI'] !== '/sale/'):?>
 <? $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
<?endif;?>
<?$isAjax = (isset($_GET["AJAX_REQUEST"]) && $_GET["AJAX_REQUEST"] == "Y");?>
<?if($arResult['ITEMS']):?>
	<?if(!$isAjax):?>
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
				$bImage = isset($arItem['FIELDS']['PREVIEW_PICTURE']) && strlen($arItem['PREVIEW_PICTURE']['SRC']);
				$imageSrc = ($bImage ? $arItem['PREVIEW_PICTURE']['SRC'] : SITE_TEMPLATE_PATH.'/images/noimage.png');
				$imageDetailSrc = ($bImage ? $arItem['DETAIL_PICTURE']['SRC'] : false);
				// show active date period
				$bActiveDate = strlen($arItem['DISPLAY_PROPERTIES']['PERIOD']['VALUE']) || ($arItem['DISPLAY_ACTIVE_FROM'] && in_array('DATE_ACTIVE_FROM', (array)$arParams['FIELD_CODE']));
				$line_element_count = ($arParams['LINE_ELEMENT_COUNT_LIST'] >= 6 ? 4 : $arParams['LINE_ELEMENT_COUNT_LIST']);
				?>
				<div class="col-md-6">
					<div class="news-list__item-wrap" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
					   <a  href="<?=$arItem['DETAIL_PAGE_URL']?>" class="news-list__item">
					   
					   <div class="">
						<?if($imageSrc):?>
                            <picture class="loaded">		   
							   <img  src="<?=$imageSrc?>" alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" style="  display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;" />
                            </picture>
								<?else:?>
							<div   background: url(/images/no_photo_medium.png);    background-size: contain;    background-repeat: no-repeat;    background-position: center;"></div>
						<?endif;?>
							
                        </div>
										
							<span class="news-list__item-date">		
							<?$convers1 = ConvertDateTime($arItem["DATE_ACTIVE_TO"], "DD.MM.YYYY", "ru");
							$date_activeto1 = MakeTimeStamp($arItem["DATE_ACTIVE_TO"], "DD.MM.YYYY HH:MI:SS");
							?>
							<?
$convers = ConvertDateTime($arItem["DATE_ACTIVE_TO"], "YYYY-MM-DD HH:MI:SS", "ru");
$date_activeto = MakeTimeStamp($arItem["DATE_ACTIVE_TO"], "DD.MM.YYYY HH:MI:SS");
?>
							<?// date active period?>
								<?if($bActiveDate):?>
									
											<?//Бирка - акция завершена / акция?>
			<?if ($arItem["DATE_ACTIVE_TO"]):?>
				<?if (($date_activeto) > ($time_now)):?>		
					
					<div class="akciya_metka" >
					<div>Акция до <?=$convers1?></div>
				</div>
					
				
				<?else:?>
					<div class="akciya_metka" >
					<div>Акция завершена</div>
				</div>
					
				<?endif;?>							
			<?endif;?>
			<?//Бирка - акция завершена / акция?>
									
								<?endif;?>

									
							<?// date active period?>
							</span>
                       
                       
                   
					

		
		
					</a>
					</div>
				</div>
			<?endforeach;?>
	<?if(!$isAjax):?>
			</div>
			<?if($bHasSection):?>
				</div>
			<?endif;?>
	<?endif;?>
		<?// bottom pagination?>
		<?if($arParams['DISPLAY_BOTTOM_PAGER']):?>
			<div class="bottom_nav" <?=($isAjax ? "style='display: none; '" : "");?>>
			<?=$arResult['NAV_STRING']?>
			</div>
		<?endif;?>
	<?if(!$isAjax):?>
	</div>
<?endif;?>
<?endif;?>

