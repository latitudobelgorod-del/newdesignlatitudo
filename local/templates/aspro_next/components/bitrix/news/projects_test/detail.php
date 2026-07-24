<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<style>

.course-content-footer-nav {display:flex; align-items: center;
  justify-content: center;}
 .course-content-footer-button {
                                                display: block;
                                                font-size: 15px;
                                                line-height: 23px;
                                                color: #4056e8;
                                                padding: 8px 35px;
                                                border-radius: 3px;
                                                text-decoration: none;
                                                position: relative;
                                                border: 1px solid #4056e8;
                                            }

.course-content-footer-button-next {padding-left:32px;padding-right:52px;}
	 .course-content-footer-button i {
 position: absolute;
    width: 44px;
    font-size: 20px;
    font-weight: bold;
    left: 0px;
    top: 9px;
    text-align: center;
	}
.course-content-footer-button-previous {
	padding-left: 52px;
}

.course-content-footer-button-next i{left:auto;right:0px;}
.course-content-footer-button-next:before{left:auto;right:44px;}

.course-content-footer-button-previous:before {
	top: 15px;
	left: 15px;
	width: 15px;
	height: 8px;
	background-position: -39px -675px;
}

.course-content-footer-button:hover {
    color: #ffffff !important;
    border-color: #4056e8;
    background: #4056e8;
}

@media screen  and (max-width:1024px) {
.course-content-footer-button span
{display:none;}
.course-content-footer-button 
{
    height: 40px;
    box-sizing: border-box;
    padding: 8px 24px;
}

}
	</style>
<?

$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/animation/animate.min.css');

// get element
$arItemFilter = CNext::GetCurrentElementFilter($arResult['VARIABLES'], $arParams);

if($arParams['CACHE_GROUPS'] == 'Y')
{
	$arItemFilter['CHECK_PERMISSIONS'] = 'Y';
	$arItemFilter['GROUPS'] = $GLOBALS["USER"]->GetGroups();
}

$arElement = CNextCache::CIblockElement_GetList(array('CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'N')), $arItemFilter, false, false, array('ID', 'NAME', 'PREVIEW_TEXT', 'IBLOCK_SECTION_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL', 'LIST_PAGE_URL', 'PROPERTY_LINK_PROJECTS', 'PROPERTY_LINK_YOUTUBE', 'PROPERTY_LINK_GOODS', 'PROPERTY_LINK_REVIEWS', 'PROPERTY_LINK_STAFF', 'PROPERTY_LINK_SERVICES', 'PROPERTY_FORM_QUESTION', 'PROPERTY_FORM_ORDER'));



if($arParams["SHOW_NEXT_ELEMENT"] == "Y")
{
	$arSort=array($arParams["SORT_BY1"] => $arParams["SORT_ORDER1"], $arParams["SORT_BY2"] => $arParams["SORT_ORDER2"]);
	$arElementNext = array();

	$arAllElements = CNextCache::CIblockElement_GetList(array($arParams["SORT_BY1"] => $arParams["SORT_ORDER1"], $arParams["SORT_BY2"] => $arParams["SORT_ORDER2"], 'CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'Y')), array("IBLOCK_ID" => $arParams["IBLOCK_ID"], "ACTIVE" => "Y", "SECTION_ID" => $arElement["IBLOCK_SECTION_ID"]/*, ">ID" => $arElement["ID"]*/), false, false, array('ID', 'DETAIL_PAGE_URL', 'IBLOCK_ID', 'SORT'));
	if($arAllElements)
	{
		$url_page_pr = $APPLICATION->GetCurPage();
		$key_item = 0;
		foreach($arAllElements as $key => $arItemElement)
		{
			if($arItemElement["DETAIL_PAGE_URL"] == $url_page_pr)
			{
				$key_item = $key;
				break;
			}
		}
		if(strlen($key_item))
		{
			$arElementNext = $arAllElements[$key_item+1];
		}
		if($arElementNext)
		{
			if($arElementNext["DETAIL_PAGE_URL"] && is_array($arElementNext["DETAIL_PAGE_URL"])){
				$arElementNext["DETAIL_PAGE_URL"]=current($arElementNext["DETAIL_PAGE_URL"]);
			}
		}
	}
}
?>
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
<div class="detail <?=($templateName = $component->{'__template'}->{'__name'})?> fixed_wrapper">


	
	<?//element?>
		<?$sViewElementTemplate = ($arParams["ELEMENT_TYPE_VIEW"] == "FROM_MODULE" ? $arTheme["NEWS_PAGE_DETAIL"]["VALUE"] : $arParams["ELEMENT_TYPE_VIEW"]);?>
	

<?
$resSection = CIBlockSection::GetNavChain(false, $arElement['IBLOCK_SECTION_ID']);
while ($arSection = $resSection->GetNext()) {
$array_sections = $arSection;
$arSectionLink = $arSection['SECTION_PAGE_URL'];
$arSectionLinkName = $arSection['NAME'];
?>

<?}?>


	<?@include_once('page_blocks/'.$sViewElementTemplate.'.php');?>

	
	<?if($arElement["IBLOCK_SECTION_ID"]):?>
		<?if(CSite::InDir('/projects/zabory/')):?>
			<style>
			#footer {margin-bottom:60px !important;}
			</style>
			<div class="k_det" style="">
			<div class="wrapper_inner">
			<div>
			<div>
			<a href="/zabor-iz-dpk/" alt="Перейти в каталог заборной доски ДПК"  title="Перейти в каталог заборной доски ДПК"><span style="text-indent:0;margin:0px 0;" class="callback-block animate-load twosmallfont colored  white">Перейти в каталог заборной доски ДПК</span>
			</a></div>
			</div>
			</div>
			</div>
		<?endif;?>
	<?endif;?>
	
	
		<?if($arElement["IBLOCK_SECTION_ID"]):?>
		<?if(CSite::InDir('/projects/ulichnye-ograzhdeniya/')):?>
			<style>
			#footer {margin-bottom:60px !important;}
			</style>
			<div class="k_det" style="">
				<div class="wrapper_inner">
					<div>
					<div><span style="text-indent:0;margin:0px 0;" class="callback-block animate-load twosmallfont colored  white" data-event="jqm" data-param-form_id="MAINFORM" data-name="detail_razd_portf">Заказать расчет ограждений</span></div>
					</div>
				</div>
			</div>
		<?endif;?>
	<?endif;?>
		
</div>


	




	
	<?if(in_array('FORM_QUESTION', $arParams['DETAIL_PROPERTY_CODE']) && $arElement['PROPERTY_FORM_QUESTION_VALUE']):?>
		<div class="row">
			<div class="col-md-12">
	<?endif;?>

	


	<?if(in_array('FORM_QUESTION', $arParams['DETAIL_PROPERTY_CODE']) && $arElement['PROPERTY_FORM_QUESTION_VALUE']):?>
		</div></div>
	<?endif;?>
	
	
				<div class="course-content-footer" style="position: relative;margin-top:20px;">
<div class="course-content-footer-nav" >

	<div class="course-content-footer-item-container" style="margin-right:11px;">
		<a  href="<?=$arResult['FOLDER'].$arResult['URL_TEMPLATES']['news']?>" class="course-content-footer-button course-content-footer-button-previous">
                    <i class="fa fa-angle-left"></i><span><?=($arParams["T_PREV_LINK"] ? $arParams["T_PREV_LINK"] : GetMessage('BACK_LINK'));?></span>
        </a>
			
	</div>
		<div class="course-content-footer-item-container" style="margin-right:11px;">
		
		<a href="<?=$arElementNext['DETAIL_PAGE_URL']?>" class="course-content-footer-button course-content-footer-button-next">
                  <i class="fa fa-angle-right"></i> <span><?=($arParams["T_NEXT_LINK"] ? $arParams["T_NEXT_LINK"] : GetMessage('NEXT_LINK'));?></span> 
        </a>
		
	</div>
	
	

</div>
</div>

<?endif;?>

