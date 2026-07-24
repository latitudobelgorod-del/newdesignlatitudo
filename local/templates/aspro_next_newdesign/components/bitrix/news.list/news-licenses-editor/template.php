<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');?>

<?if($arResult['ITEMS']):?>


<div class="item-views table-type-block table-elements <?=$templateName;?>">

			
		<div class="items row flexbox sp-gallery-items">

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
					$res_images = CFile::ResizeImageGet($arItem["PREVIEW_PICTURE"], Array("width" => 450, "height" => 638), BX_RESIZE_IMAGE_EXACT, false);
    
								
				
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
				<div class="col-md-3 col-sm-3 col-xs-12 <?=$class;?>" data-ref="mixitup-target">
				<div class="sp-gallerytext-item">
					<div class="image">
					
					
					<a data-fancybox="gallery" data-caption="<?=$arItem['NAME']?>"  class="sp-gallery-item-img-wrapper fancy fancybox" rel="media-gallery" href="<?=$arItem["PREVIEW_PICTURE"]['SRC']?>">
				<img class="img-responsive" src="<?=$res_images["src"]?>" alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" >
				</a>
				
		
		
		
		
		
							<?// element name?>
							<?if(strlen($arItem['FIELDS']['NAME'])):?>
								<div class="text"><?=$arItem['NAME']?></div>
							<?endif;?>
							
							
					
		</div>
					</div>
				</div>
			<?endforeach;?>
		</div>
		
		

		</div>

<?endif;?>
