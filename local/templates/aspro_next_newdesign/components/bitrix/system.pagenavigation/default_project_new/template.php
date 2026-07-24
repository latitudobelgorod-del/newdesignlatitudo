<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?><?$this->setFrameMode(true);?>
<?use Bitrix\Main\Page\Asset;
$asset = Asset::getInstance();?>
<?
if(!function_exists('PaiGetSiteInfo')){
	function PaiGetSiteInfo($siteId = SITE_ID)
	{
		if (empty($siteId))
		{
			return "";
		}
		$arSite = false;
		$obCache = new \CPHPCache();
		if ($obCache->InitCache(36000, 'site_' . $siteId, '/'))
		{
			$arSite = $obCache->GetVars();
		} elseif ($obCache->StartDataCache())
		{
			$arSite = \CSite::GetByID($siteId)->Fetch();
			$obCache->EndDataCache($arSite);
		}
		return $arSite;
	}
}
?>
<?
$context = \Bitrix\Main\Application::getInstance()->getContext();
$server = $context->getServer();
$curPage = $server->getRequestUri();
$arSiteInfo = PaiGetSiteInfo();
$arResult['PROTOCOL'] = CMain::IsHTTPS() ? "https://" : "http://";
$arResult['SERVER_NAME'] = $arSiteInfo['SERVER_NAME'];
$arResult['NEXT_NUM'] = $arResult['NavPageNomer'] + 1;
$arResult['PREV_NUM'] = $arResult['NavPageNomer'] - 1;
$arResult['NEXT_PAGE'] = $arResult['PROTOCOL'] . $arResult['SERVER_NAME'] . $arResult['sUrlPath'] .
	'?' .$strNavQueryString.'PAGEN_'.$arResult["NavNum"].'=' . $arResult['NEXT_NUM'];
$arResult['PREV_PAGE'] = $arResult['PROTOCOL'] . $arResult['SERVER_NAME'] . $arResult['sUrlPath'] .
	'?' .$strNavQueryString.'PAGEN_'.$arResult["NavNum"].'=' . $arResult['PREV_NUM'];
	
	?>
	
	<?if(intval($arResult['NavPageCount']) > 1){
	if($arResult['PREV_NUM']>=1){
		$asset->addString('<link rel="prev" href="'.$arResult['PREV_PAGE'].'" />');
	}

	if($arResult['NEXT_NUM']<=$arResult['NavPageCount']){
		$asset->addString('<link rel="next" href="'.$arResult['NEXT_PAGE'].'" />');
	}

	if($arResult['NavPageNomer']>1){
		$APPLICATION->SetDirProperty('robots','noindex,follow');
	
		// если не выводится, можете попробовать так: 
		// asset->addString('<meta name="robots" content="noindex, follow" />');
	}
}


?>

	<?
	$count_item = 2;
	$arResult["nStartPage"] = $arResult["NavPageNomer"] - $count_item;
	$arResult["nStartPage"] = $arResult["nStartPage"] <= 0 ? 1 : $arResult["nStartPage"];
	$arResult["nEndPage"] = $arResult["NavPageNomer"] + $count_item;
	$arResult["nEndPage"] = $arResult["nEndPage"] > $arResult["NavPageCount"] ? $arResult["NavPageCount"] : $arResult["nEndPage"];
	$strNavQueryString = ($arResult["NavQueryString"] != "" ? $arResult["NavQueryString"]."&amp;" : "");
	$strNavQueryStringFull = ($arResult["NavQueryString"] != "" ? "?".$arResult["NavQueryString"] : "");
	if($arResult["NavPageNomer"] == 1){
		$bPrevDisabled = true;
	}
	elseif($arResult["NavPageNomer"] < $arResult["NavPageCount"]){
		$bPrevDisabled = false;
	}
	if($arResult["NavPageNomer"] == $arResult["NavPageCount"]){
		$bNextDisabled = true;
	}
	else{
		$bNextDisabled = false;
	}
	?>
	<div class="module-pagination">
		<div class="nums">
			<ul class="flex-direction-nav">
				<?if(!$bPrevDisabled):?>
					<?$page = ( $bHasPage ? ($arResult["NavPageNomer"]-1 == 1 ? '' : $arResult["NavPageNomer"]-1) : '' );
					$url = ($page ? '?'.$strNavQueryString.'PAGEN_'.$arResult["NavNum"].'='.$page : '');?>
					<?$APPLICATION->AddHeadString('<link rel="prev" href="'.$arResult["sUrlPath"].$url.'"  />', true);?>
					<li class="flex-nav-prev "><a href="<?=$arResult["sUrlPath"]?><?=$url?>" class="flex-prev"></a></li>
					<? $APPLICATION->SetPageProperty("robots", "noindex, follow"); ?>
				<?endif;?>
				<?if(!$bNextDisabled):?>
					<?$APPLICATION->AddHeadString('<link rel="next" href="'.$arResult["sUrlPath"].'?'.$strNavQueryString.'PAGEN_'.$arResult["NavNum"].'='.($arResult["NavPageNomer"]+1).'"  />', true);?>
					<li class="flex-nav-next "><a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=($arResult["NavPageNomer"]+1)?>" class="flex-next"></a></li>
				<?endif;?>
			</ul>
			<?if($arResult["nStartPage"] > 1):?>
				<a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=1" class="dark_link">1</a>
				<span class='point_sep'></span>
			<?endif;?>
			<?while($arResult["nStartPage"] <= $arResult["nEndPage"]):?>
				<?if($arResult["nStartPage"] == $arResult["NavPageNomer"]):?>
					<span class="cur"><?=$arResult["nStartPage"]?></span>
				<?elseif($arResult["nStartPage"] == 1 && $arResult["bSavePage"] == false):?>
					<a href="<?=$arResult["sUrlPath"]?><?=$strNavQueryStringFull?>" class="dark_link"><?=$arResult["nStartPage"]?></a>
				<?else:?>
					<a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["nStartPage"]?>" class="dark_link"><?=$arResult["nStartPage"]?></a>
				<?endif;?>
				<?$arResult["nStartPage"]++;?>
			<?endwhile;?>
			<?if($arResult["nEndPage"] < $arResult["NavPageCount"]):?>
				<span class='point_sep'></span>
				<a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>PAGEN_<?=$arResult["NavNum"]?>=<?=$arResult["NavPageCount"]?>" class="dark_link"><?=$arResult["NavPageCount"]?></a>
			<?endif;?>
			<?if ($arResult["bShowAll"]):?>
			<noindex>
				<div class="all_block_nav">
					<?if ($arResult["NavShowAll"]):?>
						<a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>SHOWALL_<?=$arResult["NavNum"]?>=0" class="link" rel="nofollow"><?=GetMessage("nav_paged")?></a>
					<?else:?>
						<a href="<?=$arResult["sUrlPath"]?>?<?=$strNavQueryString?>SHOWALL_<?=$arResult["NavNum"]?>=1" class="link" rel="nofollow"><?=GetMessage("nav_all")?></a>
					<?endif?>
				</div>
			</noindex>
			<?endif?>
		</div>
	</div>
