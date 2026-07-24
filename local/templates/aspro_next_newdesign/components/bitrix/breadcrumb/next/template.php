<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$strReturn = '';
if($arResult){
	\Bitrix\Main\Loader::includeModule("iblock");
	global $NextSectionID, $APPLICATION, $noAddElementToChain;
	$cnt = count($arResult);
	$lastindex = $cnt - 1;
	$visibleMobile = 0;
	if (\Bitrix\Main\Loader::includeModule('aspro.next')) {
		global $arTheme;
		$bShowCatalogSubsections = ($arTheme["SHOW_BREADCRUMBS_CATALOG_SUBSECTIONS"]["VALUE"] == "Y");
		$bMobileBreadcrumbs = ($arTheme["MOBILE_CATALOG_BREADCRUMBS"]["VALUE"] == "Y" && $NextSectionID);
	}
	if ($bMobileBreadcrumbs) {
		if ($noAddElementToChain) {
			$visibleMobile = $lastindex;
		} else {
			$visibleMobile = $lastindex - 1;
		}
	}
	for($index = 0; $index < $cnt; ++$index){
		$arSubSections = array();
		$bShowMobileArrow = false;
		$arItem = $arResult[$index];
		$title = htmlspecialcharsex($arItem["TITLE"]);
		$bLast = $index == $lastindex;
		if ($NextSectionID) {
			if ($bMobileBreadcrumbs && $visibleMobile == $index) {
				$bShowMobileArrow = true;
			}
			if ($bShowCatalogSubsections) {
				$arSubSections = CNext::getChainNeighbors($NextSectionID, $arItem['LINK']);
			}
		}
		if($index){
			$strReturn .= '<span class="separator">-</span>';
		}
		if($arItem["LINK"] <> "" && $arItem['LINK'] != GetPagePath() && $arItem['LINK']."index.php" != GetPagePath() || $arSubSections){
			$strReturn .= '<div class="bx-breadcrumb-item'.($bMobileBreadcrumbs ? ' bx-breadcrumb-item--mobile' : '').($bShowMobileArrow ? ' bx-breadcrumb-item--visible-mobile' : '').($arSubSections ? ' drop' : '').($bLast ? ' cat_last' : '').'" id="bx_breadcrumb_'.$index.'" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">';
			if($arSubSections){
				if($index == ($cnt-1) && GetPagePath() === $arItem["LINK"]):
					$strReturn .= '<link href="'.GetPagePath().'" itemprop="item" /><span class="number">';
				else:
					$strReturn .= '<a class="number" href="'.$arItem["LINK"].'" itemprop="item">';
				endif;
				if ($bShowMobileArrow) {
					$strReturn .= CNext::showIconSvg('colored_theme_hover_bg-el-svg', SITE_TEMPLATE_PATH.'/images/svg/catalog/arrow_breadcrumbs.svg');
				}
				$strReturn .=($arSubSections ? '<span itemprop="name">'.$title.'</span><b class="space"></b><span class="separator'.($bLast ? ' cat_last' : '').'"></span>' : '<span>'.$title.'</span>');
				$strReturn .= '<meta itemprop="position" content="'.($index + 1).'">';
				if($index == ($cnt-1) && GetPagePath() === $arItem["LINK"]):
					$strReturn .= '</span>';
				else:
					$strReturn .= '</a>';
				endif;
				$strReturn .= '<div class="dropdown_wrapp"><div class="dropdown">';
					foreach($arSubSections as $arSubSection){
						$strReturn .= '<a class="dark_link" href="'.$arSubSection["LINK"].'">'.$arSubSection["NAME"].'</a>';
					}
				$strReturn .= '</div></div>';
			}
			else{
				$strReturn .= '<a href="'.$arItem["LINK"].'" title="'.$title.'" itemprop="item">';
				if ($bShowMobileArrow) {
					$strReturn .= CNext::showIconSvg('colored_theme_hover_bg-el-svg', SITE_TEMPLATE_PATH.'/images/svg/catalog/arrow_breadcrumbs.svg');
				}
				$strReturn .= '<span itemprop="name">'.$title.'</span><meta itemprop="position" content="'.($index + 1).'"></a>';
			}
			$strReturn .= '</div>';
		}
		else{
			$strReturn .= '<span class="'.($bMobileBreadcrumbs ? ' bx-breadcrumb-item--mobile' : '').'" itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem"><link href="'.GetPagePath().'" itemprop="item" /><span><span itemprop="name">'.$title.'</span><meta itemprop="position" content="'.($index + 1).'"></span></span>';
		}
	}

	return '<div class="breadcrumbs" itemscope="" itemtype="http://schema.org/BreadcrumbList">'.$strReturn.'</div>';
}
else{
	return $strReturn;
}
?>