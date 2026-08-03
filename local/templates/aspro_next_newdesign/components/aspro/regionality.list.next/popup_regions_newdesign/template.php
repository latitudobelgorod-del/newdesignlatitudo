<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
/* @var CBitrixComponentTemplate $this */
/* @var array $arParams */
/* @var array $arResult */

/**
 * Окно «Выберите город» нового дизайна.
 *
 * Копия штатного шаблона popup_regions с вёрсткой по макету Figma
 * «ЛАТИТУДО FINAL _ 2026», страница «Чистовик»: десктоп — узел 20517:154683
 * (1200×574), мобильный — 20517:155668 (360). Логика и состав данных те же,
 * переписана только разметка попапа; триггер в шапке (ветка !POPUP)
 * оставлен байт в байт — на него завязаны скрипт темы, скрипт этого
 * шаблона и мобильная шапка нового дизайна.
 *
 * Фото офисов добирает result_modifier.php (PREVIEW_PICTURE региона).
 * Стили — css/newdesign-cities.css шаблона.
 */

$this->setFrameMode(true);

use Bitrix\Main\Localization\Loc;

// Подмена телефона для платного трафика — как в исходном шаблоне.
$utm_medium = 'empty';
if (!empty($_SESSION['UTM']['utm_medium'])) {
    $utm_medium = $_SESSION['UTM']['utm_medium'];
}

$imgPath = SITE_TEMPLATE_PATH.'/images/newdesign/mobile';
?>
<?if (!$arResult['POPUP']):?>
    <?// Триггер в шапке. Разметку не трогаем: по .js_city_chooser открывают
    // окно и тема, и мобильная шапка нового дизайна, а .confirm_region
    // ищет скрипт этого же шаблона.?>
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
    <?// Окно приезжает аяксом, когда <head> уже отдан, — стили подключаем
    // тегом прямо в разметке (та же грабля, что у блоков главной).?>
    <link rel="stylesheet" href="<?=SITE_TEMPLATE_PATH?>/css/newdesign-cities.css?<?=@filemtime($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/css/newdesign-cities.css')?>">
    <div class="popup_regions nd-cities">

        <div class="nd-cities__head">
            <div class="nd-cities__title">Выберите город</div>
        </div>

        <?if ($arResult['FAVORITS']):?>
            <div class="nd-cities__cards">
                <?
                $count = 0;
                foreach ($arResult['FAVORITS'] as $arItem):
                    if (++$count > 5) {
                        break;
                    }
                    $url = urldecode($arItem['URL']);
                    $bCurrent = ($arResult['CURRENT_REGION']['ID'] == $arItem['ID']);

                    // Тот же выбор номера, что в исходном шаблоне.
                    $phone = $arItem['PROPERTY_REGION_TAG_PHONE_VALUE'];
                    if ($utm_medium == 'cpc' && $arItem['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE']) {
                        $phone = $arItem['PROPERTY_REGION_TAG_PHONE_PODMENA_VALUE'];
                    }
                    ?>
                    <a class="nd-citycard<?=($bCurrent ? ' is-current' : '');?>" href="<?=$url;?>" data-id="<?=$arItem['ID'];?>">
                        <span class="nd-citycard__media">
                            <?if ($arItem['ND_PHOTO']):?>
                                <img class="nd-citycard__img" src="<?=$arItem['ND_PHOTO'];?>" alt="<?=htmlspecialcharsbx($arItem['NAME']);?>" loading="lazy">
                            <?endif;?>
                            <span class="nd-citycard__badge">
                                <img src="<?=$imgPath;?>/star.svg" alt="" width="18" height="18">
                                <span>Шоурум</span>
                            </span>
                        </span>
                        <span class="nd-citycard__body">
                            <span class="nd-citycard__name"><?=$arItem['NAME'];?></span>
                            <?if ($phone):?>
                                <span class="nd-citycard__row">
                                    <i class="nd-ico nd-citycard__ico" style="-webkit-mask-image:url('<?=$imgPath;?>/phone24.svg');mask-image:url('<?=$imgPath;?>/phone24.svg')"></i>
                                    <span class="nd-citycard__phone"><?=$phone;?></span>
                                </span>
                            <?endif;?>
                            <?if ($arItem['PROPERTY_REGION_TAG_ADDRESSMY_VALUE']):?>
                                <span class="nd-citycard__row">
                                    <i class="nd-ico nd-citycard__ico" style="-webkit-mask-image:url('<?=$imgPath;?>/pin24.svg');mask-image:url('<?=$imgPath;?>/pin24.svg')"></i>
                                    <span class="nd-citycard__addr"><?=$arItem['PROPERTY_REGION_TAG_ADDRESSMY_VALUE'];?></span>
                                </span>
                            <?endif;?>
                            <?if ($bCurrent):?>
                                <span class="nd-citycard__current">выбран сейчас</span>
                            <?endif;?>
                        </span>
                    </a>
                <?endforeach;?>
            </div>
        <?endif;?>

        <?// Поле поиска по городам в макете не показано, но разметку оставляем:
        // на #title-search-city завязан автокомплит темы. Скрыто в CSS.?>
        <div class="h-search autocomplete-block" id="title-search-city">
            <div class="wrapper">
                <input id="search" class="autocomplete text" type="text" placeholder="<?=Loc::getMessage('CITY_PLACEHOLDER');?>" style="display:none;">
            </div>
        </div>

        <?if ($arResult['REGIONS']):?>
            <?
            // Сортировка по алфавиту — как в исходном шаблоне.
            $subArr = array();
            foreach ($arResult['REGIONS'] as $k => $v) {
                $subArr[$k] = $v['NAME'];
            }
            natsort($subArr);
            $subArrTmp = $arResult['REGIONS'];
            unset($arResult['REGIONS']);
            foreach ($subArr as $k => $v) {
                $arResult['REGIONS'][$k] = $subArrTmp[$k];
            }

            // Города с офисом уже показаны карточками выше, в список доставки
            // они не идут. Списки ID перенесены из исходного шаблона.
            $ignoredCityIds = array(10039, 9278, 9277, 9568, 22018, 27599, 27600, 27601, 27602, 27603, 27604);
            $newregions = array(27599, 27600, 27601, 27602, 27603, 27604);
            ?>
            <div class="nd-cities__delivery">
                <div class="nd-cities__dtitle">Доставка в города</div>
                <div class="nd-cities__dlist">
                    <?foreach ($arResult['REGIONS'] as $arItem):?>
                        <?if (in_array($arItem['ID'], $ignoredCityIds)) continue;?>
                        <?$bCurrent = ($arResult['CURRENT_REGION']['ID'] == $arItem['ID']);?>
                        <?if ($bCurrent):?>
                            <span class="nd-cities__dlink is-current"><?=$arItem['NAME'];?></span>
                        <?else:?>
                            <a class="nd-cities__dlink" href="<?=urldecode($arItem['URL']);?>" data-id="<?=$arItem['ID'];?>"><?=$arItem['NAME'];?></a>
                        <?endif;?>
                    <?endforeach;?>

                    <?// Новые регионы идут отдельным проходом в конце — так же, как в исходном шаблоне.?>
                    <?foreach ($arResult['REGIONS'] as $arItem):?>
                        <?if (!in_array($arItem['ID'], $newregions)) continue;?>
                        <?$bCurrent = ($arResult['CURRENT_REGION']['ID'] == $arItem['ID']);?>
                        <?if ($bCurrent):?>
                            <span class="nd-cities__dlink is-current"><?=$arItem['NAME'];?></span>
                        <?else:?>
                            <a class="nd-cities__dlink" href="<?=urldecode($arItem['URL']);?>" data-id="<?=$arItem['ID'];?>"><?=$arItem['NAME'];?></a>
                        <?endif;?>
                    <?endforeach;?>
                </div>
            </div>
        <?endif;?>

        <?// Переменную ждёт скрипт темы, даже когда фильтра по регионам нет.?>
        <script>
            var arRegions = <?=($arResult['JS_REGIONS'] ? CUtil::PhpToJsObject($arResult['JS_REGIONS']) : '[]');?>;
        </script>
    </div>
<?endif;?>

<script>
    $('.js-close-popup').click(function () {
        $('.confirm_region').hide();
    });
</script>
<?if ($arResult['POPUP']):?>
    <script>
        // Штатный скрипт окна (ajax/city_chooser.php) вешает установку куки
        // на .cities .item a — разметки с такими классами у нас нет, поэтому
        // делаем то же самое для своих ссылок. Без куки регион переключается
        // только у городов с поддоменом, у остальных выбор молча теряется.
        $(document).on('click', '.nd-cities a[data-id]', function (e) {
            e.preventDefault();
            var _this = $(this);
            $.removeCookie('current_region');
            $.cookie('current_region', _this.data('id'), {
                path: '/',
                domain: arNextOptions['SITE_ADDRESS']
            });
            location.href = _this.attr('href');
        });
    </script>
<?endif;?>
