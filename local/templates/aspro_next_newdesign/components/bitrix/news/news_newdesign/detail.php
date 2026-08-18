<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?
// get element
$arItemFilter = CNext::GetCurrentElementFilter($arResult['VARIABLES'], $arParams);

global $APPLICATION, $arRegion;
$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/animation/animate.min.css');

if($arParams['CACHE_GROUPS'] == 'Y')
{
	$arItemFilter['CHECK_PERMISSIONS'] = 'Y';
	$arItemFilter['GROUPS'] = $GLOBALS["USER"]->GetGroups();
}

$arElement = CNextCache::CIblockElement_GetList(array('CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'N')), $arItemFilter, false, false, array('ID', 'PREVIEW_TEXT', 'IBLOCK_SECTION_ID', 'PREVIEW_PICTURE', 'ACTIVE_FROM', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL', 'LIST_PAGE_URL', 'PROPERTY_LINK_PROJECTS', 'PROPERTY_LINK_GOODS', 'PROPERTY_LINK_REVIEWS', 'PROPERTY_LINK_STAFF', 'PROPERTY_LINK_SERVICES', 'PROPERTY_FORM_QUESTION', 'PROPERTY_LINK_REGION'));

if($arParams["SHOW_NEXT_ELEMENT"] == "Y")
{
	$arSort=array($arParams["SORT_BY1"] => $arParams["SORT_ORDER1"], $arParams["SORT_BY2"] => $arParams["SORT_ORDER2"]);
	$arElementNext = array();

	$arAllElements = CNextCache::CIblockElement_GetList(array($arParams["SORT_BY1"] => $arParams["SORT_ORDER1"], $arParams["SORT_BY2"] => $arParams["SORT_ORDER2"], 'CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'Y')), array("IBLOCK_ID" => $arParams["IBLOCK_ID"], "ACTIVE" => "Y", "SECTION_ID" => $arElement["IBLOCK_SECTION_ID"]/*, ">ID" => $arElement["ID"]*/ ), false, false, array('ID', 'DETAIL_PAGE_URL', 'IBLOCK_ID', 'SORT'));
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
<?if($arParams['USE_FILTER'] == 'Y'){
	if(isset($arResult['VARIABLES']['YEAR']))
	{
		if($arElement['ACTIVE_FROM'])
		{
			if($arDateTime = ParseDateTime($arElement['ACTIVE_FROM'], FORMAT_DATETIME))
    		{
		        if($arDateTime['YYYY'] != (int)$arResult['VARIABLES']['YEAR'])
		        {
		        	/*echo GetMessage('ELEMENT_NOTFOUND');
					CHTTP::SetStatus(404);*/
					\Bitrix\Iblock\Component\Tools::process404(
						trim($arParams["MESSAGE_404"]) ?: GetMessage("ELEMENT_NOTFOUND")
						,true
						,true
						,$arParams["SHOW_404"] === "Y"
						,$arParams["FILE_404"]
					);
					return;
		        }
    		}
		}
	}
}?>

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
	<div class="detail <?=($templateName = $component->{'__template'}->{'__name'})?> fixed_wrapper">

		<?//element?>
		<?$sViewElementTemplate = ($arParams["ELEMENT_TYPE_VIEW"] == "FROM_MODULE" ? $arTheme["NEWS_PAGE_DETAIL"]["VALUE"] : $arParams["ELEMENT_TYPE_VIEW"]);?>
		<?@include_once('page_blocks/'.$sViewElementTemplate.'.php');?>

	</div>

	<?if(in_array('FORM_QUESTION', $arParams['DETAIL_PROPERTY_CODE']) && $arElement['PROPERTY_FORM_QUESTION_VALUE']):?>
		<div class="row">
			<div class="col-md-9">
	<?endif;?>

	<div style="clear:both"></div>

	<?// Кнопки «Назад к списку» / «Следующая акция» — кнопка дизайн-системы
	   // (.nd-artnav, стили в css/newdesign.css), та же, что под карточкой бренда
	   // и на детальной портфолио. Синие рамки темы (.url-block) и черту над
	   // ними в новом дизайне не используем.?>
	<nav class="nd-artnav nd-artnav--solo">
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
	<?if(in_array('FORM_QUESTION', $arParams['DETAIL_PROPERTY_CODE']) && $arElement['PROPERTY_FORM_QUESTION_VALUE']):?>
		</div></div>
	<?endif;?>
<?endif;?>