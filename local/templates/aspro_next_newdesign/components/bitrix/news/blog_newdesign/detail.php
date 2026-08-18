<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$this->setFrameMode(true);?>
<?
// get element
$arItemFilter = CNext::GetCurrentElementFilter($arResult['VARIABLES'], $arParams);

global $APPLICATION;
$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH.'/css/animation/animate.min.css');

$arElement = CNextCache::CIblockElement_GetList(array('CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'N')), $arItemFilter, false, false, array('ID', 'PREVIEW_TEXT', 'IBLOCK_SECTION_ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DETAIL_PAGE_URL', 'LIST_PAGE_URL', 'PROPERTY_LINK_PROJECTS', 'PROPERTY_LINK_GOODS', 'PROPERTY_LINK_REVIEWS', 'PROPERTY_LINK_STAFF', 'PROPERTY_LINK_SERVICES'));

/* Кнопка «Следующая статья» в макете есть всегда, а страница /materials/
   вызывает компонент с SHOW_NEXT_ELEMENT=N — поэтому соседа ищем сами,
   в том же порядке, что и список статей (SORT_BY1/SORT_BY2 страницы),
   и по всему инфоблоку: на /materials/ разделов нет, статьи лежат в корне. */
$ndNextElement = array();
$ndAllElements = CNextCache::CIblockElement_GetList(
	array(
		$arParams["SORT_BY1"] => $arParams["SORT_ORDER1"],
		$arParams["SORT_BY2"] => $arParams["SORT_ORDER2"],
		'CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID']), 'MULTI' => 'Y'),
	),
	array("IBLOCK_ID" => $arParams["IBLOCK_ID"], "ACTIVE" => "Y"),
	false,
	false,
	array('ID', 'DETAIL_PAGE_URL', 'IBLOCK_ID', 'SORT')
);
if($ndAllElements && $arElement['ID'])
{
	$ndKeys = array_keys($ndAllElements);
	foreach($ndKeys as $ndPos => $ndKey)
	{
		if((int)$ndAllElements[$ndKey]['ID'] !== (int)$arElement['ID'])
			continue;

		// С последней статьи уводим на первую — кнопка в макете есть всегда,
		// а «Монтажные схемы и инструкции» как раз идут последними.
		$ndNextKey = isset($ndKeys[$ndPos + 1]) ? $ndKeys[$ndPos + 1] : $ndKeys[0];
		if($ndNextKey !== $ndKey)
			$ndNextElement = $ndAllElements[$ndNextKey];
		break;
	}
	if($ndNextElement && is_array($ndNextElement["DETAIL_PAGE_URL"]))
		$ndNextElement["DETAIL_PAGE_URL"] = current($ndNextElement["DETAIL_PAGE_URL"]);
}

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
	<div class="detail <?=($templateName = $component->{'__template'}->{'__name'})?>">
	

		<?//element?>
		<?@include_once('page_blocks/'.$arParams["ELEMENT_TYPE_VIEW"].'.php');?>

	</div>
	<?/*
	if(is_array($arElement['IBLOCK_SECTION_ID']) && count($arElement['IBLOCK_SECTION_ID']) > 1){
		CNext::CheckAdditionalChainInMultiLevel($arResult, $arParams, $arElement);
	}*/
	?>
<?endif;?>
<div style="clear:both"></div>
<div class="projects-blocks">
<?// Широкий блок «Уточните наличие и условия доставки» под статьёй убран
   // (Ирина, 2026-08-03): тот же блок стоит в левой колонке, получался дубль.
   // Колонка скрыта на xs и sm — там блок остаётся, но уже узкий.?>
<div class="infochat nd-infochat-wrap visible-xs visible-sm">
											<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default",
			array(
				"COMPONENT_TEMPLATE" => ".default",
				"PATH" => SITE_DIR."include/infochat_newdesign.php",
				"AREA_FILE_SHOW" => "file",
				"AREA_FILE_SUFFIX" => "",
				"AREA_FILE_RECURSIVE" => "Y",
				"EDIT_TEMPLATE" => "standard.php"
			),
			false
		);?>
		</div>
</div>
<div style="clear:both"></div>

<?// Кнопки под статьёй по макету: серые плашки со стрелками — «Назад к списку»
   // и «Следующая статья». Разметка своя (.nd-artnav), синие рамки темы
   // (.url-block) в новом дизайне не используем. Стили — в css/newdesign.css.?>
<nav class="nd-artnav">
	<a class="nd-artnav__btn" href="<?=$arResult['FOLDER'].$arResult['URL_TEMPLATES']['news']?>">
		<svg class="nd-artnav__ico" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="m15 6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		<span><?=($arParams["T_PREV_LINK"] ? $arParams["T_PREV_LINK"] : GetMessage('BACK_LINK'));?></span>
	</a>
	<?if($ndNextElement['DETAIL_PAGE_URL']):?>
		<a class="nd-artnav__btn" href="<?=$ndNextElement['DETAIL_PAGE_URL']?>">
			<span><?=($arParams["T_NEXT_LINK"] ? $arParams["T_NEXT_LINK"] : 'Следующая статья');?></span>
			<svg class="nd-artnav__ico" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</a>
	<?endif;?>
</nav>