<?if( !defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true ) die();?>
<?$this->setFrameMode(true);?>
<?
use \Bitrix\Main\Localization\Loc;
// get element
global $APPLICATION, $arRegion;
$arItemFilter = CNext::GetCurrentElementFilter($arResult["VARIABLES"], $arParams);
$arElement = CNextCache::CIblockElement_GetList(array("CACHE" => array("TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]), "MULTI" => "N")), $arItemFilter, false, false, array("ID", 'NAME', 'PREVIEW_TEXT', "IBLOCK_SECTION_ID", 'DETAIL_PICTURE', 'DETAIL_PAGE_URL', 'PROPERTY_LINK_PROJECTS', 'PROPERTY_LINK_REVIEWS', 'PROPERTY_DOCUMENTS'));
?>
<?if ($_SERVER['REQUEST_URI'] !== $arElement['DETAIL_PAGE_URL']):?>
 <? $APPLICATION->SetPageProperty("robots", "noindex, nofollow"); ?>
<?endif;?>

<?if(!$arElement && $arParams['SET_STATUS_404'] !== 'Y'):?>
	<div class="alert alert-warning"><?=GetMessage("ELEMENT_NOTFOUND")?></div>
<?elseif(!$arElement && $arParams['SET_STATUS_404'] === 'Y'):?>
	<?CNext::goto404Page();?>
<?else:?>
	<?// rss
	if($arParams['USE_RSS'] !== 'N'){
		CNext::ShowRSSIcon($arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss']);
	}?>
	<?CNext::AddMeta(
		array(
			'og:description' => $arElement['PREVIEW_TEXT'],
			'og:image' => (($arElement['PREVIEW_PICTURE'] || $arElement['DETAIL_PICTURE']) ? CFile::GetPath(($arElement['PREVIEW_PICTURE'] ? $arElement['PREVIEW_PICTURE'] : $arElement['DETAIL_PICTURE'])) : false),
		)
	);?>

	<?global $arTheme;?>
	<?//element?>
	<?$sViewElementTemplate = ($arParams["ELEMENT_TYPE_VIEW"] == "FROM_MODULE" ? $arTheme["PARTNERS_PAGE_DETAIL"]["VALUE"] : $arParams["ELEMENT_TYPE_VIEW"]);?>
	<?@include_once('page_blocks/'.$sViewElementTemplate.'.php');?>
<?endif;?>



<div style="clear:both"></div>
<hr class="bottoms" />

<?/*Кнопка назад с детальной к списку марок*/?>		
<div class="course-content-footer" style="position: relative;margin-top:20px;">
	<div class="course-content-footer-nav" >
		<div class="course-content-footer-item-container" style="margin-right:11px;">
			<a  href="<?=$arResult['FOLDER'].$arResult['URL_TEMPLATES']['news']?>" class="course-content-footer-button course-content-footer-button-previous">
				<i class="fa fa-angle-left"></i><span><?=GetMessage('BACK_LINK')?></span>
			</a>
		</div>
	</div>
</div>
<?/*Кнопка назад с детальной к списку марок*/?>	