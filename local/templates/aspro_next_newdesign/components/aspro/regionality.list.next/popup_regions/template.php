<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
/* @var CBitrixComponentTemplate $this */
/* @var array $arParams */
/* @var array $arResult */
/* @global CDatabase $DB */

$this->setFrameMode(true);

use Bitrix\Main\Localization\Loc;
?>
<?
//if(isset($_SESSION['UTM']) && !empty($_SESSION['UTM'])){

foreach (array('utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term') as $val) {
if($_SESSION['UTM'][$val]) $v=$_SESSION['UTM'][$val]; else $v='empty';
if ($val=='utm_medium')
	$utm_medium =$v;
}
?>

<style>
.popup_regions .h-search {padding:15px 36px 0;}
.popup_regions .items.ext_view .cities {
    width: 100% !important;
    padding: 0px;
    font-size: 0px;
}
div.city_chooser_frame {
    min-width: 900px;
    max-width: 1300px;
    width: auto;
}
.popup_regions .h-search .wrapper .ui-menu li:before {
    display: none;
    padding-left: 10px;
}

.popup_regions .h-search .favorits {
    font-size: 13px;
    padding: 6px 0 0;
    margin: 0 0px -11px;
}

.popup_regions .h-search .favorits .title {
    float: left;
    width: 80px;
    padding: 5px 0 0;
}

.popup_regions .h-search .favorits .cities {
    padding-left: 80px;
}

.popup_regions .h-search .favorits .cities .item {
    display: inline-block;
    padding: 5px 8px 5px 0;
}

.popup_regions .h-search .favorits .cities .item a {
    border-bottom: 1px dotted;
}

.popup_regions .items.ext_view {
    padding: 20px 25px 40px;
    font-size: 0;
    border-top: 1px solid #f2f2f2;
    background: #f8f8f8;
}
@media (max-width: 991px) {
    div.city_chooser_frame {
        overflow: auto;
        min-width: 1px;
    }
    .favorits_3city .col-md-3{
        width: 100%;
    }

}
@media (min-width: 992px){
    div.city_chooser_frame div.row.flexbox .col-md-3 {
        width: 20%;
    }
    div.city_chooser_frame{
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: 100%;
    }
}
</style>
<?if (!$arResult['POPUP']):?>
    <?if ($arResult['CURRENT_REGION']):?>
        <?global $arTheme;?>
        <div class="region_wrapper">
            <div class="city_title">
                <?=Loc::getMessage('CITY_TITLE');?>
            </div>
            <div class="js_city_chooser colored" data-event="jqm" data-name="city_chooser" data-param-url="<?=$arResult['URI'];?>" data-param-form_id="city_chooser">
                <span><?=$arResult['CURRENT_REGION']['NAME'];?></span><span style="
    font-size: 10px;
    padding: 0 0px 0 5px;
"><i class="fa fa-caret-down"></i></span><span class="arrow"><i></i></span>
            </div>
            <?if ($arResult['SHOW_REGION_CONFIRM']):?>
                <div class="confirm_region popup show">
                    <span class="close" data-id="<?=$arResult['CURRENT_REGION']['ID'];?>"><i></i></span>
                    <?php
                    $href = 'data-href="'.urldecode($arResult['REGIONS'][$arResult['REAL_REGION']['ID']]['URL']).'"';
                    if (
                        $arTheme['USE_REGIONALITY']['DEPENDENT_PARAMS']['REGIONALITY_TYPE']['VALUE'] == 'SUBDOMAIN'
                        && ($arResult['REGIONS'][$arResult['REAL_REGION']['ID']]['URL'] == $arResult['HOST'].$_SERVER['HTTP_HOST'].$arResult['URI'])
                    ) {
                        $href = '';
                    }
                    ?>
                    <div class="title"><?=Loc::getMessage('CITY_TITLE');?> <?=$arResult['REAL_REGION']['NAME'];?> ?</div>
                    <div class="buttons">
                        <span class="btn btn-default aprove" data-id="<?=$arResult['REAL_REGION']['ID'];?>" <?=$href;?>><?=Loc::getMessage('CITY_YES');?></span>
                        <span class="btn btn-default white js_city_change"><?=Loc::getMessage('CITY_CHANGE');?></span>
                    </div>
                </div>
            <?endif;?>
        </div>
    <?endif;?>
<?else:?>
    <div class="popup_regions">
	  <div class="favorits_3city h-search">
                <? if ($arResult['FAVORITS']): ?>
                    <? $count = 0; ?>
                    <div class="row flexbox">
                        <? foreach ($arResult['FAVORITS'] as $arItem):
                            if (++$count > 5) {
                                break;
                            }
							 $url = urldecode($arItem['URL']);
                            $bCurrentFavourites = ($arResult['CURRENT_REGION']['ID'] == $arItem['ID']); ?>

                            <div class="col-md-3 shadow ">

                                <div class="item fav_links" onclick='window.location.href="<?=$url;?>"'>
								
                                    <div class="name"><span><?= $arItem['NAME']; ?></span><span
                                            class="<?= ($bCurrentFavourites ? 'current_shownf' : ''); ?>"></span>
                                    </div>

										<?if ($utm_medium == "cpc") :?>
										    <?if ($arItem['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']):?>
                                                <div class="phone"><?=$arItem['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']; ?></div>
                                            <?else:?>
                                                <div class="phone"><?=$arItem['PROPERTY_REGION_TAG_PHONE_VALUE'];?></div>
                                            <?endif;?>
                                        <?else:?>
                                              <div class="phone"><?= $arItem['PROPERTY_REGION_TAG_PHONE_VALUE']; ?></div>
                                        <?endif;?>
			
			
									
                                    <div
                                        class="address"><?= $arItem['PROPERTY_REGION_TAG_ADDRESSMY_VALUE']; ?></div>
                                    <div class="row flexbox">
                                        <ul>
                                            <li><i class="fa fa-check "></i>Есть шоу-рум</li>
                                            <li><i class="fa fa-check "></i>Выезд на замер</li>
                                            <li><i class="fa fa-check "></i>Монтаж под ключ</li>
                                        </ul>
                                    </div>

                                </div>
                            </div>
                        <? endforeach; ?>
                    </div>
                <? endif; ?>
                <div class="dostavka">Доставка в города</div>
            </div>
        <div class="h-search autocomplete-block" id="title-search-city">
            <div class="wrapper">
                <input id="search" class="autocomplete text" type="text" placeholder="<?=Loc::getMessage('CITY_PLACEHOLDER');?>" style="display:none;">
                <?/*<div class="search_btn"></div>*/?>
            </div>
            <?if ($arResult['FAVORITS']):?>
                <div class="favorits">
                    <?/*<span class="title"><?=GetMessage('EXAMPLE_CITY');?></span>
                    <div class="cities">
                        <?foreach ($arResult['FAVORITS'] as $arItem):?>
                            <div class="item">
                                <a href="<?=$arItem['URL'];?>" data-id="<?=$arItem['ID'];?>" class="name"><?=$arItem['NAME'];?></a>
                            </div>
                        <?endforeach;?>
                    </div>*/?>
                </div>
            <?endif;?>
        </div>
        <?if (Bitrix\Main\Config\Option::get('aspro.next', 'REGIONALITY_SEARCH_ROW', 'N') != 'Y'):?>
            <div class="items ext_view">
                <?if ($arResult['SECTION_LEVEL1']):?>
                    <div class="block regions">
                        <div class="title"><?= $arResult['SECTION_LEVEL2'] ? Loc::getMessage('OKRUG') : Loc::getMessage('REGION');?></div>
                        <div class="items_block">
                            <?foreach ($arResult['SECTION_LEVEL1'] as $key => $arSection):?>
                                <div class="item dark_link" data-id="<?=$key;?>"><span><?=$arSection['NAME'];?></span></div>
                            <?endforeach;?>
                        </div>
                    </div>
                <?endif;?>

                <?if ($arResult['SECTION_LEVEL2']):?>
                    <div class="block regions">
                        <div class="title"><?=Loc::getMessage('REGION');?></div>
                        <div class="items_block">
                            <?foreach ($arResult['SECTION_LEVEL2'] as $key => $arSections):?>
                                <div class="parent_block" data-id="<?=$key;?>">
                                    <?foreach ($arSections as $key2 => $arSection):?>
                                        <div class="item dark_link" data-id="<?=$key2;?>"><span><?=$arSection['NAME'];?></span></div>
                                    <?endforeach;?>
                                </div>
                            <?endforeach;?>
                        </div>
                    </div>
                <?endif;?>

                <?if ($arResult['REGIONS']):?>
                   <div class="block cities">
                       <? foreach ($arResult['REGIONS'] as $k => $v) {
                        $subArr[$k] = $v["NAME"];
                    }
                    natsort($subArr);
                    $subArrTmp = $arResult['REGIONS'];
                    unset($arResult['REGIONS']);
                    foreach ($subArr as $k => $v) {
                        $arResult['REGIONS'][$k] = $subArrTmp[$k];
                    }
                    ?>
							
                        <div class="items_block">
						  <? $ignoredCityIds = [10039, 9278, 9277, 9568, 22018, 27599, 27600, 27601, 27602, 27603, 27604];
							$newregions = [27599, 27600, 27601, 27602, 27603, 27604];?>
						<?  foreach ($arResult['REGIONS'] as $key => $arItem):
                                if (in_array($arItem['ID'], $ignoredCityIds)) {
                                    continue;
                                }
                                $url = urldecode($arItem['URL']);
								$bCurrent = ($arResult['CURRENT_REGION']['ID'] == $arItem['ID']); ?>	
								
								
                                <div class="item <?= $bCurrent ? 'current shown' : '';?> <?= (!$arResult['SECTION_LEVEL1'] && !$arResult['SECTION_LEVEL2']) ? 'shown' : '';?>" data-id="<?= (isset($arItem['IBLOCK_SECTION_ID']) && $arItem['IBLOCK_SECTION_ID']) ? $arItem['IBLOCK_SECTION_ID'] : 0;?>">
                                    <?if ($bCurrent):?>
                                        <span class="name"><?=$arItem['NAME'];?></span>
                                    <?else:?>
                                        <a href="<?=$url;?>" data-id="<?=$arItem['ID'];?>" class="name"><?=$arItem['NAME'];?></a>
                                    <?endif;?>
                                </div>
                            <?endforeach;?>
							
							
							<?/*Новые регионы*/?>
							
							 <?  foreach ($arResult['REGIONS'] as $key => $arItem):
                                if (!in_array($arItem['ID'], $newregions)) {
                                    continue;
                                }
                                $url = urldecode($arItem['URL']);
								$bCurrent = ($arResult['CURRENT_REGION']['ID'] == $arItem['ID']);?>
					 
								<div class="item <?= $bCurrent ? 'current shown' : '';?> <?= (!$arResult['SECTION_LEVEL1'] && !$arResult['SECTION_LEVEL2']) ? 'shown' : '';?>" data-id="<?= (isset($arItem['IBLOCK_SECTION_ID']) && $arItem['IBLOCK_SECTION_ID']) ? $arItem['IBLOCK_SECTION_ID'] : 0;?>">
                                    <?if ($bCurrent):?>
                                        <span class="name"><?=$arItem['NAME'];?></span>
                                    <?else:?>
                                        <a href="<?=$url;?>" data-id="<?=$arItem['ID'];?>" class="name"><?=$arItem['NAME'];?></a>
                                    <?endif;?>
                                </div>
                            <? endforeach; ?>
							<?/*Новые регионы*/?>
                        </div>
                    </div>
                <?endif;?>
            </div>
            <script>
                var arRegions = <?=CUtil::PhpToJsObject($arResult['JS_REGIONS']);?>
            </script>
        <?else:?>
            <script>
                var arRegions = [];
            </script>
        <?endif;?>
    </div>
<?endif;?>

<script>
    $('.js-close-popup').click(function () {
        $('.confirm_region').hide();
    });
    $('.current_shownf').text('выбран сейчас');
</script>