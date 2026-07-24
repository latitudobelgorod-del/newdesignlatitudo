<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?use \Bitrix\Main\Localization\Loc;?>

<?if($arResult['ITEMS']):?>
	<div class="wraps video-block">
		
		<div class="item-views list-type-block <?=($arParams['IMAGE_POSITION'] ? 'image_'.$arParams['IMAGE_POSITION'] : '')?> <?=$templateName;?>">
			<div class="row margin0">
				<?foreach($arResult['ITEMS'] as $i => $arItem):?>
					<?
					// edit/add/delete buttons for edit mode
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
					// use detail link?
					$bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);
					// show preview picture?
					$bImage = strlen($arItem['FIELDS']['PREVIEW_PICTURE']['SRC']);
					$vidos = $arItem['PROPERTIES']['LINK_VIDEO']['VALUE'];
					$imageSrc = ($bImage ? $arItem['FIELDS']['PREVIEW_PICTURE']['SRC'] : false);
					$imageDetailSrc = ($bImage ? $arItem['FIELDS']['DETAIL_PICTURE']['SRC'] : false);
					?>
					
					<div class="col-md-12 nopadding">
					
							<div class="body-info">
								<?// element name?>
								<?if($arItem['PROPERTIES']['LINK_VIDEO']['VALUE']):?>

									<?
									$url = $arItem['PROPERTIES']['LINK_VIDEO']['VALUE'];
if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
    $video_id = $match[1];
}
?>

<iframe  src="https://www.youtube.com/embed/<?=$video_id?>" width="1280" height="720" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>

								<?endif;?>						
								
								
				
								
								
								
								
								<?// element preview text?>
									
							
						</div>
					</div>
				<?endforeach;?>
			</div>
		</div>
	</div>
<?endif;?>