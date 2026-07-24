<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);
?>

<?if($arResult['SECTIONS']):?>

		<div class="sections_wrapper sect_wr_cat portfolio projects-blocks" >
		<div class="list items">
	<div class="row margin0 flexbox">
	
			<?foreach($arResult['SECTIONS'] as $arItem):?>
				<?
				// edit/add/delete buttons for edit mode
				$arSectionButtons = CIBlock::GetPanelButtons($arItem['IBLOCK_ID'], 0, $arItem['ID'], array('SESSID' => false, 'CATALOG' => true));
				$this->AddEditAction($arItem['ID'], $arSectionButtons['edit']['edit_section']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'SECTION_EDIT'));
				$this->AddDeleteAction($arItem['ID'], $arSectionButtons['edit']['delete_section']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'SECTION_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
				// preview picture
				if($bShowSectionImage = in_array('PREVIEW_PICTURE', $arParams['FIELD_CODE'])){
					$bImage = strlen($arItem['~PICTURE']);
					$arSectionImage = ($bImage ? CFile::ResizeImageGet($arItem['~PICTURE'], array('width' => 650, 'height' => 350), BX_RESIZE_IMAGE_EXACT, false) : array());
					$imageSectionSrc = ($bImage ? $arSectionImage['src'] : SITE_TEMPLATE_PATH.'/images/no_photo_medium.png');
				}
				?>
				<div class="col-md-6 col-sm-6 col-xs-12">
					<div class="item" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
					
<a class="centered_name" href="<?=$arItem['SECTION_PAGE_URL']?>">
<?if($bShowSectionImage):?>

<div class="image">
<img  src="/assets/lazyload/loading.gif" data-original="<?=$imageSectionSrc?>" alt="<?=( $arItem['PICTURE']['ALT'] ? $arItem['PICTURE']['ALT'] : $arItem['NAME']);?>" title="<?=( $arItem['PICTURE']['TITLE'] ? $arItem['PICTURE']['TITLE'] : $arItem['NAME']);?>" class="lazy img-responsive" />
</div>
<?endif;?>
<div class="name " >
<?=$arItem['NAME']?>
</div>
		</a>					
							
					</div>
				</div>
			<?endforeach;?>
		</div>
	</div>
	</div>
<?endif;?>