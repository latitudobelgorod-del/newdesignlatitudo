<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);
?>
<?if($arResult['SECTIONS']):?>
	<div class="sections_wrapper sect_wr_cat" style="margin-top:20px;">
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
					$arSectionImage = ($bImage ? CFile::ResizeImageGet($arItem['~PICTURE'], array('width' => 500, 'height' => 500), BX_RESIZE_IMAGE_EXACT, false) : array());

					
					
					
					
					$imageSectionSrc = ($bImage ? $arSectionImage['src'] : SITE_TEMPLATE_PATH.'/images/no_photo_medium.png');
				}
				?>
				<div class="col-md-6 col-sm-6 col-xs-6 col">
			<div class="item shine" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
			<a href="<?=$arItem['SECTION_PAGE_URL']?>"><div class="img shine">
			<?if($bShowSectionImage):?>
			<img src="<?=$imageSectionSrc?>" alt="<?=( $arItem['PICTURE']['ALT'] ? $arItem['PICTURE']['ALT'] : $arItem['NAME']);?>" title="<?=( $arItem['PICTURE']['TITLE'] ? $arItem['PICTURE']['TITLE'] : $arItem['NAME']);?>" class="img-responsive" />
			<?endif;?>	
			</div>
<div class="name_pr"><?if(in_array('NAME', $arParams['FIELD_CODE'])):?><?=$arItem['NAME']?><?endif;?></div>						
</a>
					
					
					
				</div>	
					
				</div>
			<?endforeach;?>
		</div>
	</div>
<?endif;?>

<? if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) == '/projects/'): ?>


<div class="k_det" style="">
<div class="wrapper_inner">
<div>
<div>
<span style="text-indent:0;margin:0px 0;" class="callback-block animate-load twosmallfont colored  white" data-event="jqm" data-param-form_id="MAINFORM" data-name="detail_catalog">Обсудить проект</span></div>
</div>
</div>

</div>

<?endif;?>
