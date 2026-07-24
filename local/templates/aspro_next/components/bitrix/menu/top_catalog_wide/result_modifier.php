<?
$arResult = CNext::getChilds($arResult);
global $arRegion, $arTheme;

if($arResult){
	if($bUseMegaMenu = $arTheme['USE_MEGA_MENU']['VALUE'] === 'Y'){
		$arMenuIblocks = array();

        foreach($arResult as $i => $arItem){
            if(
            	$arItem['PARAMS']['FROM_IBLOCK'] &&
                $arItem['PARAMS']['IBLOCK_ID']
            ){
            	$iblockId = $arItem['PARAMS']['IBLOCK_ID'];

            	if(!isset($arMenuIblocks[$iblockId])){
            		$arMenuIblocks[$iblockId] = false;

                	if($arCatalogIblock = CNextCache::$arIBlocksInfo[$iblockId]){
                        if($catalogPageUrl = str_replace('#'.'SITE_DIR'.'#', SITE_DIR, $arCatalogIblock['LIST_PAGE_URL'])){
                            $menuIblockId = CNextCache::$arIBlocks[SITE_ID]['aspro_next_catalog']['aspro_next_megamenu'][0];
                            if($menuIblockId){
                                $menuRootCatalogSectionId = CNextCache::CIblockSection_GetList(array('SORT' => 'ASC', 'CACHE' => array('TAG' => CNextCache::GetIBlockCacheTag($menuIblockId), 'RESULT' => array('ID'), 'MULTI' => 'N')), array('ACTIVE' => 'Y', 'IBLOCK_ID' => $menuIblockId, 'DEPTH_LEVEL' => 1, 'UF_MENU_LINK' => $catalogPageUrl), false, array('ID'), array('nTopCount' => 1));
								if($menuRootCatalogSectionId){
									$arMenuIblocks[$iblockId] = true;

									$arResult[$i] = array(
                                        'LINK' => $catalogPageUrl,
                                        'PARAMS' => array(
                                            'FROM_IBLOCK' => 1,
                                            'DEPTH_LEVEL' => 1,
                                            'MEGA_MENU_CHILDS' => 1
                                        ),
                                    );
								}
							}
						}
					}
            	}
            	elseif($arMenuIblocks[$iblockId]){
        			unset($arResult[$i]);
            	}
            }
        }

        CNext::replaceMenuChilds($arResult, $arParams);
	}

	foreach($arResult as $key => $arItem)
	{
		if(isset($arItem["PARAMS"]["ONLY_MOBILE"]) && $arItem["PARAMS"]["ONLY_MOBILE"]=="Y") {
		    unset($arResult[$key]);
		    continue;
		}

		if(isset($arItem['CHILD']))
		{
			foreach($arItem['CHILD'] as $key2=>$arItemChild)
			{
				if(isset($arItemChild['PARAMS']) && $arRegion && $arTheme['USE_REGIONALITY']['VALUE'] === 'Y' && $arTheme['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_FILTER_ITEM']['VALUE'] === 'Y')
				{
					// filter items by region
					if(isset($arItemChild['PARAMS']['LINK_REGION']))
					{
						if($arItemChild['PARAMS']['LINK_REGION'])
						{
							if(!in_array($arRegion['ID'], $arItemChild['PARAMS']['LINK_REGION']))
								unset($arResult[$key]['CHILD'][$key2]);
						}
						else
							unset($arResult[$key]['CHILD'][$key2]);
					}
				}
			}
		}
	}
}
?>