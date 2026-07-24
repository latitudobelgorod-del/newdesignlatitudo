<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!defined('ERROR_404')){
	$arResult['URL'] = $arResult['SECTION_CODE'];

	$url = $APPLICATION->GetCurDir();

	//$APPLICATION->AddHeadString('<link rel="canonical" href="https://latitudo.ru'.$url.'">');
	$APPLICATION->SetPageProperty("robots", "noindex, follow");
	if (isset($arResult['NAV_NUM'], $arResult['NAV_PAGE_NOMER'], $arResult['NAV_PAGE_COUNT'], $arResult['URL'])){
		if ($arResult['NAV_PAGE_COUNT'] > $arResult['NAV_PAGE_NOMER']) { // rel next
			$next = $arResult['NAV_PAGE_NOMER'] + 1;
			$urlNextRel = $arResult['URL']."?PAGEN_1=".$next;       
		} 
		if ($arResult['NAV_PAGE_NOMER'] > 1) { // rel prev
			$prev = $arResult['NAV_PAGE_NOMER'] - 1;
			If($prev > 1){
				$urlPrevRel = $arResult['URL']."?PAGEN_1=".$prev; 
			}
			else{
				$urlPrevRel = $arResult['URL'];
			}
		} 
		if (isset($urlNextRel)) {
			//$APPLICATION->SetPageProperty('next', 'https://' . $_SERVER["HTTP_HOST"] . $urlNextRel);
			$APPLICATION->AddHeadString('<link rel="next" href="https://' .$_SERVER["HTTP_HOST"].$urlNextRel . '">');
		} 
		if (isset($urlPrevRel)) {
			//$APPLICATION->SetPageProperty('prev', 'https://' . $_SERVER["HTTP_HOST"] . $urlPrevRel);
			$APPLICATION->AddHeadString('<link rel="prev" href="https://' .$_SERVER["HTTP_HOST"].$urlPrevRel . '">');
		} 
	}
}