<?
$arResult = CNext::getChilds($arResult);
global $arRegion, $arTheme;


if(isset($arTheme['HEADER_MOBILE_MENU_CATALOG_EXPANDED']['VALUE']) && $arTheme['HEADER_MOBILE_MENU_CATALOG_EXPANDED']['VALUE'] === 'Y') {
    $arParams["CATALOG_MENU_EXPANDED"] = "Y";
}

if($arResult){
    if($bUseMegaMenu = $arTheme['USE_MEGA_MENU']['VALUE'] === 'Y'){
        CNext::replaceMenuChilds($arResult, $arParams);
    }

    foreach($arResult as $key=>$arItem)
    {
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
<?
if(!function_exists('show_top_mobile_li')){
    function show_top_mobile_li($arItem, $arParams, $bParent, $style = array()){
        if ($arItem["LINK"] == '/info/') {
            $hideLink = true;
        }
        ?>
        <?
$liClass = '';
if ($arItem['SELECTED']) $liClass = 'selected';
if ($arItem['LINK'] == '/sale/') $liClass = trim($liClass . ' sale_menu');
?>
<li<?=($liClass ? ' class="'.$liClass.'"' : '')?>>
            <a class="<?=isset($style["a"])?$style["a"]:""?> dark-color<?=($bParent ? ' parent' : '')?>"<?php if (!$hideLink):?> href="<?=$arItem["LINK"]?>"<?php endif;?> title="<?=$arItem["TEXT"]?>">
                <span><?=$arItem['TEXT']?></span>
                <?if($bParent):?>
                    <span class="arrow"><i class="svg svg_triangle_right"></i></span>
                <?endif;?>
            </a>
            <?if($bParent):?>
                <ul class="dropdown">
                    <li class="menu_back"><a href="" class="dark-color" rel="nofollow"><i class="svg svg-arrow-right"></i><?=GetMessage('NEXT_T_MENU_BACK')?></a></li>
                    <li class="menu_title">
                        <?php if ($arItem['LINK'] == '/info/'):?>
                            <span><?=$arItem['TEXT']?></span>
                        <?php else:?>
                            <a href="<?=$arItem['LINK']?>"><?=$arItem['TEXT']?></a>
                        <?php endif;?>
                    </li>
                    <?foreach($arItem['CHILD'] as $arSubItem):?>
                        <?$bShowChilds = $arParams['MAX_LEVEL'] > $arSubItem['DEPTH_LEVEL'];?>
                        <?$bParent = $arSubItem['CHILD'] && $bShowChilds;?>
                        <li<?=($arSubItem['SELECTED'] ? ' class="selected"' : '')?>>
                            <a class="dark-color<?=($bParent ? ' parent' : '')?>" href="<?=$arSubItem["LINK"]?>" title="<?=$arSubItem["TEXT"]?>">
                                <span><?=$arSubItem['TEXT']?></span>
                                <?if($bParent):?>
                                    <span class="arrow"><i class="svg svg_triangle_right"></i></span>
                                <?endif;?>
                            </a>
                            <?if($bParent):?>
                                <ul class="dropdown">
                                    <li class="menu_back"><a href="" class="dark-color" rel="nofollow"><i class="svg svg-arrow-right"></i><?=GetMessage('NEXT_T_MENU_BACK')?></a></li>
                                    <li class="menu_title"><a href="<?=$arSubItem['LINK'];?>"><?=$arSubItem['TEXT']?></a></li>
                                    <?foreach($arSubItem["CHILD"] as $arSubSubItem):?>
                                        <?php if ($arItem['LINK'] == '/catalog/' && empty($arSubSubItem['PARAMS']['SECTION_IN_MENU'])) {
                                            continue;
                                        }?>
                                        <?$bShowChilds = $arParams['MAX_LEVEL'] > $arSubSubItem['DEPTH_LEVEL'];?>
                                        <?$bParent = $arSubSubItem['CHILD'] && $bShowChilds;?>

                                        <li<?=($arSubSubItem['SELECTED'] ? ' class="selected"' : '')?>>
                                            <a class="dark-color<?=($bParent ? ' parent' : '')?>" href="<?=$arSubSubItem["LINK"]?>" title="<?=$arSubSubItem["TEXT"]?>">
                                                <span><?=$arSubSubItem['TEXT']?></span>
                                                <?if($bParent):?>
                                                    <span class="arrow"><i class="svg svg_triangle_right"></i></span>
                                                <?endif;?>
                                            </a>
                                            <?if($bParent):?>
                                                <ul class="dropdown">
                                                    <li class="menu_back"><a href="" class="dark-color" rel="nofollow"><i class="svg svg-arrow-right"></i><?=GetMessage('NEXT_T_MENU_BACK')?></a></li>
                                                    <li class="menu_title"><a href="<?=$arSubSubItem['LINK'];?>"><?=$arSubSubItem['TEXT']?></a></li>
                                                    <?foreach($arSubSubItem["CHILD"] as $arSubSubSubItem):?>
                                                        <?php if ($arItem['LINK'] == '/catalog/' && empty($arSubSubSubItem['PARAMS']['SECTION_IN_MENU'])) {
                                                            continue;
                                                        }?>
                                                        <li<?=($arSubSubSubItem['SELECTED'] ? ' class="selected"' : '')?>>
                                                            <a class="dark-color" href="<?=$arSubSubSubItem["LINK"]?>" title="<?=$arSubSubSubItem["TEXT"]?>">
                                                                <span><?=$arSubSubSubItem['TEXT']?></span>
                                                            </a>
                                                        </li>
                                                    <?endforeach;?>
                                                </ul>
                                            <?endif;?>
                                        </li>
                                    <?endforeach;?>
                                </ul>
                            <?endif;?>
                        </li>
                    <?endforeach;?>
                </ul>
            <?endif;?>
        </li>
        <?
    }
}
?>