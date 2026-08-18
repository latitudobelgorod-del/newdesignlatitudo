<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?

global $APPLICATION;
$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/animation/animate.min.css');

// get element
$arItemFilter = CNext::GetCurrentElementFilter($arResult['VARIABLES'], $arParams);

if($arParams['CACHE_GROUPS'] == 'Y')
{
	$arItemFilter['CHECK_PERMISSIONS'] = 'Y';
	$arItemFilter['GROUPS'] = $GLOBALS["USER"]->GetGroups();
}

$sectionCode = preg_split('#/#', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), -1, PREG_SPLIT_NO_EMPTY);
$arElement = CNextCache::CIblockElement_GetList(array('CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'N')), $arItemFilter, false, false, array('ID', 'NAME', 'PREVIEW_TEXT', 'IBLOCK_SECTION_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL', 'LIST_PAGE_URL', 'PROPERTY_LINK_PROJECTS', 'PROPERTY_LINK_GOODS', 'PROPERTY_LINK_REVIEWS', 'PROPERTY_LINK_STAFF', 'PROPERTY_LINK_SERVICES', 'PROPERTY_FORM_QUESTION', 'PROPERTY_FORM_ORDER'));

if($arParams["SHOW_NEXT_ELEMENT"] == "Y")
{
	$arSort=array($arParams["SORT_BY1"] => $arParams["SORT_ORDER1"], $arParams["SORT_BY2"] => $arParams["SORT_ORDER2"]);
	$arElementNext = array();

	$arAllElements = CNextCache::CIblockElement_GetList(array($arParams["SORT_BY1"] => $arParams["SORT_ORDER1"], $arParams["SORT_BY2"] => $arParams["SORT_ORDER2"], 'CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'Y')), array("IBLOCK_ID" => $arParams["IBLOCK_ID"], "ACTIVE" => "Y", "SECTION_CODE" => $sectionCode[1]/*, ">ID" => $arElement["ID"]*/ ), false, false, array('ID', 'NAME', 'DETAIL_PAGE_URL', 'IBLOCK_SECTION_ID', 'IBLOCK_ID', 'SORT'));
	if($arAllElements)
	{
		$url_page = $APPLICATION->GetCurPage();

		$key_item = 0;
		foreach($arAllElements as $key => $arItemElement)
		{
			if($arItemElement["DETAIL_PAGE_URL"] == $url_page)
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

<?//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/xxx50888.txt', print_r($arAllElements, 1));?>


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
		<?$sViewElementTemplate = ($arParams["ELEMENT_TYPE_VIEW"] == "FROM_MODULE" ? $arTheme["PROJECTS_PAGE_DETAIL"]["VALUE"] : $arParams["ELEMENT_TYPE_VIEW"]);?>
		
		<?//для исправления цепочки хлебных крошек была исправлена строка $resSection = CIBlockSection::GetNavChain(false, $arElement['IBLOCK_SECTION_ID_SELECTED'] ?: $arElement['IBLOCK_SECTION_ID']);?>

		<?
$resSection = CIBlockSection::GetNavChain(false, $arElement['IBLOCK_SECTION_ID_SELECTED'] ?: $arElement['IBLOCK_SECTION_ID']);
while ($arSection = $resSection->GetNext()) {
$array_sections = $arSection;
$arSectionLink = $arSection['SECTION_PAGE_URL'];
$arSectionLinkName = $arSection['NAME'];
?>

<?}?>

		<?
	if(is_array($arElement['IBLOCK_SECTION_ID']) && count($arElement['IBLOCK_SECTION_ID']) > 1){
		
//echo count($arElement['IBLOCK_SECTION_ID']);

//CNext::CheckAdditionalChainInMultiLevel($arResult, $arParams, $arElement);
	}
	?>
	
		
		<?$APPLICATION->AddChainItem($arSectionLinkName, $arSectionLink);?>

		<?@include_once('page_blocks/'.$sViewElementTemplate.'.php');?>
	

	</div>
	
	<?if(in_array('FORM_QUESTION', $arParams['DETAIL_PROPERTY_CODE']) && $arElement['PROPERTY_FORM_QUESTION_VALUE']):?>
		<div class="row">
			<div class="col-md-12">
	<?endif;?>
	<div style="clear:both"></div>
	<?// Кнопки «Назад к списку» / «Следующий проект» — кнопка дизайн-системы
	   // (.nd-artnav, стили в css/newdesign.css): Figma, компонент 132:2431
	   // «size=l, variant=secondary», на фрейме «Проект» 20524:98253 пара
	   // кнопок делит ряд 878 пополам. Синие рамки темы (.url-block) и черту
	   // над ними в новом дизайне не используем — в макете их нет.?>
	<nav class="nd-artnav nd-artnav--center">
		<a class="nd-artnav__btn" href="<?=$arResult['FOLDER'].$arResult['URL_TEMPLATES']['news']?>">
			<svg class="nd-artnav__ico" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="m15 6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span><?=($arParams["T_PREV_LINK"] ? $arParams["T_PREV_LINK"] : GetMessage('BACK_LINK'));?></span>
		</a>
		<?if($arParams["SHOW_NEXT_ELEMENT"] == "Y" && $arElementNext):?>
			<a class="nd-artnav__btn" href="<?=$arElementNext['DETAIL_PAGE_URL']?>">
				<span><?=($arParams["T_NEXT_LINK"] ? $arParams["T_NEXT_LINK"] : GetMessage('NEXT_LINK'));?></span>
				<svg class="nd-artnav__ico" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</a>
		<?endif;?>
	</nav>
	<?if($arParams["SHOW_NEXT_ELEMENT"] != "Y" && $arParams["USE_SHARE"] == "Y" && $arElement):?>
		<div class="line_block">
			<?$APPLICATION->IncludeFile(SITE_DIR."include/share_buttons.php", Array(), Array("MODE" => "html", "NAME" => GetMessage('CT_BCE_CATALOG_SOC_BUTTON')));?>
		</div>
	<?endif;?>
	
	

	
	

	 
	<?if(in_array('FORM_QUESTION', $arParams['DETAIL_PROPERTY_CODE']) && $arElement['PROPERTY_FORM_QUESTION_VALUE']):?>
		</div></div>
	<?endif;?>
<?endif;?>

