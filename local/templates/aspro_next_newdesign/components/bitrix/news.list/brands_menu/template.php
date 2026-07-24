<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?use \Bitrix\Main\Localization\Loc;?>



<div style="clear:both;">
	

	<div class="group-content partners">
		<?$arParams['COUNT_IN_LINE'] = ($arParams['COUNT_IN_LINE'] <=0 ? 3 : $arParams['COUNT_IN_LINE']);
		$line_element_count = ($arParams['COUNT_IN_LINE'] >= 6 ? 4 : $arParams['COUNT_IN_LINE']);
		?>
		<?// group elements by sections?>
		<?foreach($arResult['SECTIONS'] as $SID => $arSection):?>
			<?
			// edit/add/delete buttons for edit mode
			$arSectionButtons = CIBlock::GetPanelButtons($arSection['IBLOCK_ID'], 0, $arSection['ID'], array('SESSID' => false, 'CATALOG' => true));
			$this->AddEditAction($arSection['ID'], $arSectionButtons['edit']['edit_section']['ACTION_URL'], CIBlock::GetArrayByID($arSection['IBLOCK_ID'], 'SECTION_EDIT'));
			$this->AddDeleteAction($arSection['ID'], $arSectionButtons['edit']['delete_section']['ACTION_URL'], CIBlock::GetArrayByID($arSection['IBLOCK_ID'], 'SECTION_DELETE'), array('CONFIRM' => Loc::getMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
			?>
			<div id="<?=$this->GetEditAreaId($arSection['ID'])?>" class="tab-pane <?=(!$si++ || !$arSection['ID'] ? 'active' : '')?>">

		

				<div class="row items">
					<?foreach($arSection['ITEMS'] as $i => $arItem):?>
						<?
						// edit/add/delete buttons for edit mode
						$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
						$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
						// use detail link?
						$bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);
						// show preview picture?
						$bImage = strlen($arItem['FIELDS']['PREVIEW_PICTURE']['SRC']);
						$imageSrc = ($bImage ? $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] : false);
						$imageDetailSrc = ($bImage ? $arItem['FIELDS']['DETAIL_PICTURE']['SRC'] : false);
						?>
						
						<div class="col-md-<?=floor(12 / $line_element_count)?> col-sm-<?=floor(12 / round($line_element_count / 2))?> col-xs-6">
								<?if($bDetailLink):?>
										<a href="<?=$arItem['DETAIL_PAGE_URL']?>" title="<?=$arItem['NAME']?>">
									<?endif;?><div class=" clearfix" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
								
									<div class="image">
								
									<?if($imageSrc):?>
										<img   src="<?=$imageSrc?>" alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" class="lazy img-responsive" />
											<?else:?>
											<?=$arItem['NAME']?>
										<?endif;?>
									
								</div>
								
							</div><?if($bDetailLink):?>
										</a>
									<?endif;?>
						</div>
					<?endforeach;?>
				</div>
			</div>
		<?endforeach;?>
	</div>


</div>