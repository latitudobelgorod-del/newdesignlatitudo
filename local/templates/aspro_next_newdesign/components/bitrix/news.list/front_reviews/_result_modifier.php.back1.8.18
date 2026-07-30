<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?
if($arParams["NOT_SLIDER"] == "Y" && $arResult['ITEMS']){
	$arStaffsID = $arResult['STAFF'] = array();
	$nStaffIblockID = 0;
	foreach($arResult['ITEMS'] as $key => $arItem){
		if(isset($arItem['DISPLAY_PROPERTIES']['STAFF'])){
			$arStaffsID[] = $arItem['DISPLAY_PROPERTIES']['STAFF']['VALUE'];
			$nStaffIblockID = $arItem['DISPLAY_PROPERTIES']['STAFF']['LINK_IBLOCK_ID'];
		}
		
		if($arStaffsID && $nStaffIblockID){
			$arResult['STAFF'] = CNextCache::CIblockElement_GetList(array('CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($nStaffIblockID), 'MULTI' => 'N', 'GROUP' => 'ID')), array('IBLOCK_ID' => $nStaffIblockID, 'ID' => $arStaffsID), false, false, array('ID', 'NAME', 'PROPERTY_POST', 'PREVIEW_PICTURE'));
		}
	}
	
}

if($arResult['ITEMS']){
	foreach($arResult['ITEMS'] as $itemKey => $arItem){
	    if($arItem['PROPERTIES'])
	    {
	        foreach($arItem['PROPERTIES'] as $key2 => $arProp)
	        {
	            if(($key2 == 'EMAIL' || $key2 == 'PHONE') && $arProp['VALUE'])
	                $arItem['MIDDLE_PROPS'][] = $arProp;
	            if(strpos($key2, 'SOCIAL') !== false && $arProp['VALUE']){
	                switch($key2){
	                    case('SOCIAL_VK'):
	                        $arProp['FILE'] = SITE_TEMPLATE_PATH.'/images/svg/social/social_vk.svg';
	                        break;
	                    case('SOCIAL_ODN'):
	                        $arProp['FILE'] = SITE_TEMPLATE_PATH.'/images/svg/social/social_odnoklassniki.svg';
	                        break;
	                    case('SOCIAL_FB'):
	                        $arProp['FILE'] = SITE_TEMPLATE_PATH.'/images/svg/social/social_facebook.svg';
	                        break;
	                    case('SOCIAL_MAIL'):
	                        $arProp['FILE'] = SITE_TEMPLATE_PATH.'/images/svg/social/social_mail.svg';
	                        break;
	                    case('SOCIAL_TW'):
	                        $arProp['FILE'] = SITE_TEMPLATE_PATH.'/images/svg/social/social_twitter.svg';
	                        break;
	                    case('SOCIAL_INST'):
	                        $arProp['FILE'] = SITE_TEMPLATE_PATH.'/images/svg/social/social_instagram.svg';
	                        break;
	                    case('SOCIAL_GOOGLE'):
	                        $arProp['FILE'] = SITE_TEMPLATE_PATH.'/images/svg/social/social_google.svg';
	                        break;
	                    case('SOCIAL_SKYPE'):
	                        $arProp['FILE'] = SITE_TEMPLATE_PATH.'/images/svg/social/social_skype.svg';
	                        break;
	                    case('SOCIAL_BITRIX'):
	                        $arProp['FILE'] = SITE_TEMPLATE_PATH.'/images/svg/social/social_bitrix24.svg';
	                        break;
	                }
	
	                $arItem['SOCIAL_PROPS'][] = $arProp;
	            }
	        }
	    }
	    $arResult['ITEMS'][$itemKey] = $arItem;
	}
}

?>