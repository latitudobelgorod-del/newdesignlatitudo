<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
} ?>
<?php $this->setFrameMode(true); ?>
<?php // intro text?>
    <h1 id="pagetitle"><?php $APPLICATION->ShowTitle(false) ?></h1>
    <div class="text_before_items">
        <?php $APPLICATION->IncludeComponent(
            "bitrix:main.include",
            "",
            [
                "AREA_FILE_SHOW" => "page",
                "AREA_FILE_SUFFIX" => "inc",
                "EDIT_TEMPLATE" => "",
            ]
        ); ?>
    </div>
<?php
$arItemFilter = CNext::GetIBlockAllElementsFilter($arParams);

if ($arParams['CACHE_GROUPS'] == 'Y') {
    $arItemFilter['CHECK_PERMISSIONS'] = 'Y';
    $arItemFilter['GROUPS'] = $GLOBALS["USER"]->GetGroups();
}

$itemsCnt = CNextCache::CIblockElement_GetList(
    ["CACHE" => ["TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"])]],
    $arItemFilter,
    []
); ?>

<?php if (!$itemsCnt): ?>
    <div class="alert alert-warning"><?= GetMessage("SECTION_EMPTY") ?></div>
<?php else: ?>
    <?php // rss
    if ($arParams['USE_RSS'] !== 'N') {
        CNext::ShowRSSIcon($arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss']);
    }
    ?>

    <?php $arItems = CNextCache::CIBLockElement_GetList(
        ['SORT' => 'ASC', 'NAME' => 'ASC', 'CACHE' => ['TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]],
        $arItemFilter,
        false,
        false,
        ['ID', 'NAME', 'ACTIVE_FROM', 'ACTIVE_TO']
    );
    $arYears = [];
    if ($arItems) {
        foreach ($arItems as $arItem) {
            if ($arItem['ACTIVE_FROM']) {
                if ($arDateTime = ParseDateTime($arItem['ACTIVE_FROM'], FORMAT_DATETIME)) {
                    $arYears[$arDateTime['YYYY']] = $arDateTime['YYYY'];
                }
            }
            if ($arItem['ACTIVE_TO']) {
                if ($arDateTime = ParseDateTime($arItem['ACTIVE_TO'], FORMAT_DATETIME)) {
                    $arYears[$arDateTime['YYYY']] = $arDateTime['YYYY'];
                }
            }
        }
        if ($arYears) {
            if ($arParams['USE_FILTER'] != 'N') {
                rsort($arYears);
                $bHasYear = (isset($_GET['year']) && (int)$_GET['year']);
                $year = ($bHasYear ? (int)$_GET['year'] : 0); ?>
                <div class="">
                  
                    <?php foreach ($arYears as $value):
                        $bSelected = ($bHasYear && $value == $year); ?>
                 
                    <?php endforeach;
                    if ($arResult['FOLDER'] === '/sale/'):
                       $bHasActivity = true;
                        $activity = isset($_GET['activity']) ? intval($_GET['activity']) : 1; ?>
                        
                        <span style="display: inline-block;"></span>
                        <?php foreach ([1 => 'активные', 0 => 'завершенные'] as $index => $value):
                        $bSelected = ($bHasActivity && $index === $activity); ?>
                    
                    <?php endforeach; ?>
                        <?php
                        if ($bHasActivity) {
                            switch ($activity) {
                                case 1:
                                    $GLOBALS[$arParams["FILTER_NAME"]][] = [
                                        ">=DATE_ACTIVE_TO" => ConvertDateTime(date('d.m.Y'), "DD.MM.YYYY"),
                                        "<=DATE_ACTIVE_FROM" => array(false, ConvertTimeStamp(false, "FULL")),
										
                                    ];
                                    break;
                                case 0:
                                    $GLOBALS[$arParams["FILTER_NAME"]][] = [
                                        "<DATE_ACTIVE_TO" => ConvertDateTime(date('d.m.Y'), "DD.MM.YYYY"),
                                    ];
                                    break;
                            }
                        }
                    endif; ?>
                </div>
                <?php
                if ($bHasYear) {
                    $GLOBALS[$arParams["FILTER_NAME"]][] = [
                        ">=DATE_ACTIVE_FROM" => ConvertDateTime("01.01.".$year, "DD.MM.YYYY"),
                        "<DATE_ACTIVE_FROM" => ConvertDateTime("01.01.".($year + 1), "DD.MM.YYYY"),
                        ">=DATE_ACTIVE_TO" => ConvertDateTime("01.01.".$year, "DD.MM.YYYY"),
                        "<DATE_ACTIVE_TO" => ConvertDateTime("01.01.".($year + 1), "DD.MM.YYYY"),
                    ];
                }
                ?>
                <?php
            }
        }
    } ?>

    <?php global $arTheme, $isMenu; ?>

    <?php if (!$isMenu): ?>
        <div class="sub_container fixed_wrapper">
        <div>
        <div>
    <?php endif; ?>
    <?php if ((isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower(
                $_SERVER['HTTP_X_REQUESTED_WITH']
            ) == "xmlhttprequest") || (strtolower($_REQUEST['ajax']) == 'y')) {
        $APPLICATION->RestartBuffer();
    } ?>
    <?php // section elements?>
    <?php $sViewElementsTemplate = ($arParams["SECTION_ELEMENTS_TYPE_VIEW"] == "FROM_MODULE" ? $arTheme["NEWS_PAGE"]["VALUE"] : $arParams["SECTION_ELEMENTS_TYPE_VIEW"]); ?>
    <?php @include_once('page_blocks/'.$sViewElementsTemplate.'.php'); ?>
    <?php if ((isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower(
                $_SERVER['HTTP_X_REQUESTED_WITH']
            ) == "xmlhttprequest") || (strtolower($_REQUEST['ajax']) == 'y')) {
        die();
    } ?>
    <?php // ask block?>
    <?php ob_start(); ?>
    <div class="ask_a_question">
        <div class="inner">
            <div class="text-block">
                <?php $APPLICATION->IncludeComponent(
                    'bitrix:main.include',
                    '',
                    [
                        'AREA_FILE_SHOW' => 'page',
                        'AREA_FILE_SUFFIX' => 'ask',
                        'EDIT_TEMPLATE' => '',
                    ]
                ); ?>
            </div>
        </div>
        <div class="outer">
            <span><span class="btn btn-default btn-lg white animate-load" data-event="jqm" data-param-form_id="ASK"
                        data-name="question"><span><?= (strlen(
                            $arParams['S_ASK_QUESTION']
                        ) ? $arParams['S_ASK_QUESTION'] : GetMessage('S_ASK_QUESTION')) ?></span></span></span>
        </div>
    </div>
    <?php $html = ob_get_contents(); ?>
    <?php ob_end_clean(); ?>

    <?php if (!$isMenu): ?>
        </div>
        <!--div class="col-md-3  with-padding-left hidden-xs hidden-sm">
            <div class="fixed_block_fix"></div>
            <div class="ask_a_question_wrapper">
                <//?=$html;?>
            </div>
        </div-->

        </div>
        </div>
<hr class="long">
                <div class="head-block top years">
                    <div class="bottom_border"></div>
               
                    <?php foreach ($arYears as $value):
                        $bSelected = ($bHasYear && $value == $year); ?>
                 
                    <?php endforeach;
                    if ($arResult['FOLDER'] === '/sale/'):
                       $bHasActivity = true;
                        $activity = isset($_GET['activity']) ? intval($_GET['activity']) : 1; ?>
                        
                        <span style="display: inline-block;"></span>
                        <?php foreach ([1 => 'активные', 0 => 'завершенные'] as $index => $value):
                        $bSelected = ($bHasActivity && $index === $activity); ?>
                        <div class="item-link <?= ($bSelected ? 'active' : ''); ?>">
                            <div class="title btn-inline black">
                                <?php if ($bSelected): ?>
                                    <span class="btn-inline black"><?= $value; ?></span>
                                <?php else: ?>
                                    <a class="btn-inline black"
                                       href="<?= $APPLICATION->GetCurPageParam('activity='.$index, ['activity']
                                       ); ?>"><?= $value; ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                        <?php
                        if ($bHasActivity) {
                            switch ($activity) {
                                case 1:
                                    $GLOBALS[$arParams["FILTER_NAME"]][] = [
                                        ">=DATE_ACTIVE_TO" => ConvertDateTime(date('d.m.Y'), "DD.MM.YYYY"),
                                        "<=DATE_ACTIVE_FROM" => array(false, ConvertTimeStamp(false, "FULL")),
										
                                    ];
                                    break;
                                case 0:
                                    $GLOBALS[$arParams["FILTER_NAME"]][] = [
                                        "<DATE_ACTIVE_TO" => ConvertDateTime(date('d.m.Y'), "DD.MM.YYYY"),
                                    ];
                                    break;
                            }
                        }
                    endif; ?>
                </div>
		
    <?php else: ?>
        <?php $this->SetViewTarget('under_sidebar_content'); ?>
        <?= $html; ?>
        <?php $this->EndViewTarget(); ?>
    <?php endif; ?>
<?php endif; ?>