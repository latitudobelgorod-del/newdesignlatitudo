<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);
?>
<style>
 .sec_list{margin-bottom:25px}
.sec_list-wrap{display:-webkit-box;display:-webkit-flex;display:-moz-box;display:-ms-flexbox;display:flex;-webkit-flex-wrap:wrap;-ms-flex-wrap:wrap;flex-wrap:wrap;margin-left:-5px;margin-right:-5px;-webkit-box-align:stretch;-webkit-align-items:stretch;-moz-box-align:stretch;-ms-flex-align:stretch;align-items:stretch;margin-top:4px;}
.sec_list-item{padding:5px;-webkit-box-flex:1;-webkit-flex:1 1 33.3%;-moz-box-flex:1;-ms-flex:1 1 33.3%;flex:1 1 33.3%;max-width:33.3%;text-align:center;}
.sec_list-item-link{color:#000;font-size:1.2rem;line-height:1.4rem;display:block;background-color:#fff;padding:10px;padding-bottom:5px;height:100%;background:#f9f9f9;border:1px solid #f2f2f2 !important;}
.sec_list-item-link:hover{color:#A5000E;border-color:#A5000E}
.sec_list-item-link .icon{display:block;margin-left:auto;margin-right:auto;width:47px;height:47px;position:relative;margin-bottom:7px}
.sec_list-item-link .icon img{display:block;width:100%;height:100%;-o-object-fit:contain;object-fit:contain}
</style>

<?if ($_SERVER['REQUEST_URI'] !== '/projects/'):?>
 <?// $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
<?endif;?>
<?if($arResult['SECTIONS']):?>

		
		
	<div class="sec_list" style="margin-top:30px;">
<div class="sec_list-wrap">

			<?foreach($arResult['SECTIONS'] as $arItem):?>
				<?
				// edit/add/delete buttons for edit mode
				$arSectionButtons = CIBlock::GetPanelButtons($arItem['IBLOCK_ID'], 0, $arItem['ID'], array('SESSID' => false, 'CATALOG' => true));
				//$this->AddEditAction($arItem['ID'], $arSectionButtons['edit']['edit_section']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'SECTION_EDIT'));
				//$this->AddDeleteAction($arItem['ID'], $arSectionButtons['edit']['delete_section']['ACTION_URL'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'SECTION_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
				// preview picture
				if($bShowSectionImage = in_array('PREVIEW_PICTURE', $arParams['FIELD_CODE'])){
					$bImage = strlen($arItem['~PICTURE']);
					$arSectionImage = ($bImage ? CFile::ResizeImageGet($arItem['~PICTURE'], array('width' => 80, 'height' => 80), BX_RESIZE_IMAGE_EXACT, false) : array());
					$imageSectionSrc = ($bImage ? $arSectionImage['src'] : SITE_TEMPLATE_PATH.'/images/no_photo_medium.png');
				}
				?>
				
				
				
							<div class="sec_list-item" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
			<a  href="<?=$arItem['SECTION_PAGE_URL']?>" class="sec_list-item-link">
			<span class="icon">
                    <picture class="loaded">
					
					
				
								
									<img width="80" height="80" src="/assets/lazyload/loading.gif" data-original="<?=$imageSectionSrc?>" alt="<?=( $arItem['PICTURE']['ALT'] ? $arItem['PICTURE']['ALT'] : $arItem['NAME']);?>" 
									title="<?=( $arItem['PICTURE']['TITLE'] ? $arItem['PICTURE']['TITLE'] : $arItem['NAME']);?>" class="lazy img-responsive" />
									
									
					
                    </picture>
                </span>
				
				<span class="text"><?=$arItem["NAME"]?></span>
					
	
				</a>
			</div>
				
				
				

			<?endforeach;?>
		</div>
	</div>
<?endif;?>