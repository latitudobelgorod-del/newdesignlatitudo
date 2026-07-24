<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die()?>
<?// вывод текущей даты и перевод ее в UNIX-формат?>
<?$objDateTime = new DateTime();
 $time_now = $objDateTime->getTimestamp();
 global $arFilterActiya;
  ?>

<?if ($_SERVER['REQUEST_URI'] !== '/sale/'):?>
 <? $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
<?endif;?>

<?$this->setFrameMode(true);?>
<?if($arResult['ITEMS']):?>
	<?$isAjax = (isset($_GET["AJAX_REQUEST"]) && $_GET["AJAX_REQUEST"] == "Y");?>
	<?$isWideImg = (isset($arParams['IMAGE_WIDE']) && $arParams['IMAGE_WIDE'] == 'Y');?>
	<?if(!$isAjax):?>
	<div class="section__wrap item-views list list-type-block wide_img image_left news item-views  <?=($arParams['IMAGE_POSITION'] ? 'image_'.$arParams['IMAGE_POSITION'] : '')?> <?=($templateName = $component->{'__parent'}->{'__template'}->{'__name'})?>">
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
			//$this->AddEditAction($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'], $arSectionButtons['edit']['edit_section']['ACTION_URL'], CIBlock::GetArrayByID($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['IBLOCK_ID'], 'SECTION_EDIT'));
			//$this->AddDeleteAction($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'], $arSectionButtons['edit']['delete_section']['ACTION_URL'], CIBlock::GetArrayByID($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['IBLOCK_ID'], 'SECTION_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
			?>
			<div class="section" id="<?=$this->GetEditAreaId($arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'])?>">
			<?
		}?>
		<div class="news-list items row">
			<?// show section items?>
	<?endif;?>
			<?
				$count=count($arResult['ITEMS']);
				$current=0;
			?>
			<?foreach($arResult['ITEMS'] as $i => $arItem):?>
				<?
				$current++;
				// edit/add/delete buttons for edit mode
//$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
//$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
				// use detail link?
				$bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);
				$bImage = strlen($arItem['FIELDS']['PREVIEW_PICTURE']['SRC']);
				$imageSrc = ($bImage ? $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] : false);

	

				$imageDetailSrc = ($bImage ? $arItem['FIELDS']['DETAIL_PICTURE']['SRC'] : false);
				// show active date period
				$bActiveDate = strlen($arItem['DISPLAY_PROPERTIES']['PERIOD']['VALUE']) || ($arItem['DISPLAY_ACTIVE_FROM'] && in_array('DATE_ACTIVE_FROM', $arParams['FIELD_CODE']));
				$date_active_to = $arItem["DISPLAY_ACTIVE_TO"]; 
				
?>

<div class="col-md-3">
			
<div class="news-list__item-wrap" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
<a  href="<?=$arItem['DETAIL_PAGE_URL']?>" class="news-list__item">
                        <span class="news-list__item-img">
						<?if($imageSrc):?>
                            <picture class="loaded">		   
							   <img width="280" height="183" src="<?=$imageSrc?>" alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>"  />
                            </picture>
								<?else:?>
							<div style="width: 280px;    height: 183px;    background: url(/images/no_photo_medium.png);    background-size: contain;    background-repeat: no-repeat;    background-position: center;"></div>
							<?endif;?>
							
                        </span>
						<div class="news-list__item-cont">
							<span class="news-list__item-date">		
							<?$convers1 = ConvertDateTime($arItem["DATE_ACTIVE_TO"], "DD.MM.YYYY", "ru");
							$date_activeto1 = MakeTimeStamp($arItem["DATE_ACTIVE_TO"], "DD.MM.YYYY HH:MI:SS");
							?>
							<?// date active period?>
								<?if($bActiveDate):?>
									
										<?if(strlen($arItem["DATE_ACTIVE_TO"])):?>
											Акция действительна до <?=$convers1?>
										<?else:?>
											<?=$arItem['DISPLAY_ACTIVE_FROM']?>
										<?endif;?>
									
								<?endif;?>

									
							<?// date active period?>
							</span>
                        <div class="news-list__item-name"><?=$arItem['NAME']?></div>
                        <p class="news-list__item-text">
                            <span style="color: #000000;">
							
									<?if(strlen($arItem['FIELDS']['PREVIEW_TEXT'])):?>
								
									<?if($arItem['PREVIEW_TEXT_TYPE'] == 'text'):?>
										<?=$arItem['FIELDS']['PREVIEW_TEXT']?>
									<?else:?>
										<?=$arItem['FIELDS']['PREVIEW_TEXT']?>
									<?endif;?>
								
							<?endif;?>
							</span>
						</p>
                    </div>
					
<?
$convers = ConvertDateTime($arItem["DATE_ACTIVE_TO"], "YYYY-MM-DD HH:MI:SS", "ru");
$date_activeto = MakeTimeStamp($arItem["DATE_ACTIVE_TO"], "DD.MM.YYYY HH:MI:SS");
?>
			<?//Бирка - акция завершена / акция?>
			<?if ($arItem["DATE_ACTIVE_TO"]):?>
				<?if (($date_activeto) > ($time_now)):?>		
				<div class="akciya_begin1" >
					<div>Акция!</div>
				</div>
				<?else:?>
					<div class="akciya_end1" >
					<div style="">Акция завершена</div></div>
				<?endif;?>							
			<?endif;?>
			<?//Бирка - акция завершена / акция?>
		
                </a>
				
</div>
			</div>
		
					<?//if($current<$count):?>
					<?//endif;?>
				
			<?endforeach;?>
		<?if(!$isAjax):?>
		</div>
		<?if($bHasSection):?>
			</div>
		<?endif;?>
			<?// bottom pagination?>
		
		<?endif;?>

	
	<?if(!$isAjax):?>
	<?if($arParams['DISPLAY_BOTTOM_PAGER']):?>
			<div class="bottom_nav" <?=($isAjax ? "style='display: none; '" : "");?>><?=$arResult['NAV_STRING']?></div>
		<?endif;?></div>
	<?endif;?>
<?endif;?>
