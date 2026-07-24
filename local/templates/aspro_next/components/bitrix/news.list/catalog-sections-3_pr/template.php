<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);




?>
<?if ($_SERVER['REQUEST_URI'] !== '/projects/'):?>
 <? //$APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
<?endif;?>


<?if($arResult['SECTIONS']):?>
		<div class="sections_wrapper sect_wr_cat" style="margin-top:30px;">
		<div class="list items">
			
	<div class="row margin0 flexbox">
			<?foreach($arResult['SECTIONS'] as $arItem):?>
				<?
				// edit/add/delete buttons for edit mode
				$arSectionButtons = CIBlock::GetPanelButtons($arItem['IBLOCK_ID'], 0, $arItem['ID'], array('SESSID' => false, 'CATALOG' => true));
				//$this->AddEditAction($arItem['ID'], $arSectionButtons['edit']['edit_section']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'SECTION_EDIT'));
				//$this->AddDeleteAction($arItem['ID'], $arSectionButtons['edit']['delete_section']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'SECTION_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
				// preview picture
				if($bShowSectionImage = in_array('PREVIEW_PICTURE', $arParams['FIELD_CODE'])){
					$bImage = strlen($arItem['~PICTURE']);
					$arSectionImage = ($bImage ? CFile::ResizeImageGet($arItem['~PICTURE'], array('width' => 440, 'height' => 440), BX_RESIZE_IMAGE_EXACT, false) : array());
					$imageSectionSrc = ($bImage ? $arSectionImage['src'] : SITE_TEMPLATE_PATH.'/images/no_photo_medium.png');
				}
				?>
				<div class="col-md-4 col-sm-4 col-xs-12">
					<div class="item" id="<?=$this->GetEditAreaId($arItem['ID'])?>">
						<?// icon or preview picture?>
						<?if($bShowSectionImage):?>
							<div class="image shine">
								<a href="<?=$arItem['SECTION_PAGE_URL']?>">
									<img src="/assets/lazyload/loading.gif" data-original="<?=$imageSectionSrc?>" alt="<?=( $arItem['PICTURE']['ALT'] ? $arItem['PICTURE']['ALT'] : $arItem['NAME']);?>" 
									title="<?=( $arItem['PICTURE']['TITLE'] ? $arItem['PICTURE']['TITLE'] : $arItem['NAME']);?>" class="lazy img-responsive" />
								</a>
							</div>
						<?endif;?>
						<div class="name" >
					<div class="blk"><div class="left col-md-6 col-sm-12"><a href="<?=$arItem['SECTION_PAGE_URL'];?>" class="white_link" ><?=$arItem['NAME']?></a></div>
<div class="right col-md-6 col-sm-12"><a  href="<?=$arItem['SECTION_PAGE_URL'];?>" class="sect_button animate-load white btn-default btn">Смотреть</a></div></div></div></div>
				</div>
			<?endforeach;?>
		</div>
		
	</div></div>
<?endif;?>


<? if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) == '/projects/'): ?>


<div class="k_det">
<div class="wrapper_inner">
<div>
<div>
<span style="text-indent:0;margin:0px 0;" class="callback-block animate-load twosmallfont colored  white" 
data-event="jqm" data-param-form_id="MAINFORM" data-name="detail_project">Обсудить проект</span></div>
</div>
</div>

</div>


<?endif;?>
