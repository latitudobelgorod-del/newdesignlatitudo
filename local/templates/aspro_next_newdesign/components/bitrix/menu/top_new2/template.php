<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<? $this->setFrameMode(true); ?>
<?php
global $arRegion, $arTheme, $APPLICATION;
$regionID = ($arRegion ? $arRegion['ID'] : '');
?>

<?
function chunk(array $array, int $column)
{
    $count = count($array);
    return array_chunk($array, ceil($count / ($count / $column)), 1);
}

global $arTheme;
$iVisibleItemsMenu = ($arTheme['MAX_VISIBLE_ITEMS_MENU']['VALUE'] ? $arTheme['MAX_VISIBLE_ITEMS_MENU']['VALUE'] : 10);
?>

<? if ($arResult): ?>
    <div class="table-menu">
        <table>
            <tr>
                <? foreach ($arResult as $arItem): ?>
                    <? 
                    $bShowChilds = ($arParams["MAX_LEVEL"] > 1) && ($ITEM_INDEX <> 2);
                    $bWideMenu = (isset($arItem['PARAMS']['CLASS']) && strpos($arItem['PARAMS']['CLASS'], 'wide_menu') !== false);
                    $bPartneram = (isset($arItem['PARAMS']['CLASS']) && strpos($arItem['PARAMS']['CLASS'], 'partneram') !== false);
                    $bProjectcl = (isset($arItem['PARAMS']['CLASS']) && strpos($arItem['PARAMS']['CLASS'], 'project') !== false);
                    $bServicescl = (isset($arItem['PARAMS']['CLASS']) && strpos($arItem['PARAMS']['CLASS'], 'services') !== false);
                    $bSalel = (isset($arItem['PARAMS']['CLASS']) && strpos($arItem['PARAMS']['CLASS'], 'sale') !== false);
                    if (is_array($arItem["CHILD"])) {
                        $k = array_chunk($arItem["CHILD"], 4);
                    }
                    ?>
                    <td class="menu-item unvisible <?= ($arItem["CHILD"] ? "dropdown" : "") ?> <?= (isset($arItem["PARAMS"]["CLASS"]) ? $arItem["PARAMS"]["CLASS"] : ""); ?>  <?= ($arItem["SELECTED"] ? "active" : "") ?>">
                        <div class="wrap">

                            <? if (!$bPartneram): ?>
                                <a class="<?= ($arItem["CHILD"] && $bShowChilds ? "dropdown-toggle" : "") ?> "
                                   href="<?= $arItem["LINK"] ?>">
                                    <div class="<?= (isset($arItem["PARAMS"]["ICON_FILE"]) ? "s_icon" : "") ?>"><?= $arItem["TEXT"] ?>
                                        <div class="line-wrapper"><span class="line"></span></div>
                                    </div>
                                </a>
                            <? else: ?>
                                <div class="partners_menu <?= (isset($arItem["PARAMS"]["ICON_FILE"]) ? "s_icon" : "") ?>"><?= $arItem["TEXT"] ?>
                                    <div class="line-wrapper"><span class="line"></span></div>
                                </div>
                            <? endif; ?>

                            <? if ($arItem["CHILD"] && $bShowChilds): ?>
                                <span class="tail"></span>
                                
                                <!-- ДЛЯ PARTNERAM - ОСТАВЛЯЕМ БЕЗ ИЗМЕНЕНИЙ (как было раньше) -->
                                <? if ($bPartneram): ?>
                                    <ul class="dropdown-menu partneram-menu">
                                        <? 
                                        $visibleChildren = array();
                                        foreach ($arItem["CHILD"] as $arSubItem) {
                                            $visibleChildren[] = $arSubItem;
                                        }
                                        foreach ($visibleChildren as $arSubItem): 
                                        ?>
                                            <li class="<?= (($arSubItem["CHILD"] || $arSubItem['PARAMS']['MENULINK_TOP']) && $bShowChilds ? "dropdown-submenu" : "") ?> <?= ($arSubItem["SELECTED"] ? "active" : "") ?>">
                                                <a href="<?= $arSubItem["LINK"] ?>" title="<?= $arSubItem["TEXT"] ?>"><span class="name"><?= $arSubItem["TEXT"] ?></span><?= ($arSubItem["CHILD"] && $bShowChilds ? '<span class="arrow"><i></i></span>' : '') ?></a>

                                                <? if (($arSubItem["CHILD"] || $arSubItem['PARAMS']['MENULINK_TOP']) && $bShowChilds): ?>
                                                    <ul class="dropdown-menu">
                                                        <? foreach ($arSubItem["CHILD"] as $arSubSubSubItem): ?>
                                                            <li class="menu-item <?= ($arSubSubSubItem["SELECTED"] ? "active" : "") ?>">
                                                                <a href="<?= $arSubSubSubItem["LINK"] ?>" title="<?= $arSubSubSubItem["TEXT"] ?>"><span class="name"><?= $arSubSubSubItem["TEXT"] ?></span></a>
                                                            </li>
                                                        <? endforeach; ?>

                                                        <?php if (!empty($arSubItem['PARAMS']['MENULINK_TOP']) && is_array($arSubItem['PARAMS']['MENULINK_TOP'])): ?>
                                                            <?php foreach ($arSubItem['PARAMS']['MENULINK_TOP'] as $arSubHTML):?>
                                                                <li class="menu-item">
                                                                    <?=htmlspecialchars_decode($arSubHTML)?>
                                                                </li>
                                                            <?php endforeach;?>
                                                        <?php endif; ?>
                                                    </ul>
                                                <? endif; ?>
                                            </li>
                                        <? endforeach; ?>
                                        <div class="clear"></div>
                                    </ul>
                                    
                                <!-- ДЛЯ ПРОЕКТОВ - ОСТАВЛЯЕМ СТАРУЮ СТРУКТУРУ -->
                                <? elseif ($bProjectcl): ?>
                                    <? $children = chunk($arItem["CHILD"], 4); ?>
                                    <div class="dropdown-menu">
                                        <? foreach ($children as $div): ?>
                                            <div style="width:100%;position:relative;">
                                                <? foreach ($div as $arSubItem): ?>
                                                    <? $bShowChilds = $arParams["MAX_LEVEL"] > 2; ?>
                                                    <? $bHasPicture = (isset($arSubItem['PARAMS']['PICTURE']) && $arSubItem['PARAMS']['PICTURE'] && $arTheme['SHOW_CATALOG_SECTIONS_ICONS']['VALUE'] == 'Y'); ?>
                                                    <div class="project_col">
                                                        <a href="<?= $arSubItem["LINK"] ?>" title="<?= $arSubItem["TEXT"] ?>">
                                                            <? if (isset($arSubItem['PARAMS']['PICTURE'])):
                                                                $arImg = CFile::ResizeImageGet($arSubItem['PARAMS']['PICTURE'], array('width' => 60, 'height' => 60), BX_RESIZE_IMAGE_EXACT);
                                                                if (is_array($arImg)):?>
                                                                    <div class="img"><img src="<?= $arImg["src"] ?>" alt="<?= $arSubItem["TEXT"] ?>" title="<?= $arSubItem["TEXT"] ?><?= $arSubItem["ID"] ?>"/></div>
                                                                <? endif; ?>
                                                            <? endif; ?>
                                                            <div class="title"><span class="name" style=""><?= $arSubItem["TEXT"] ?></span><?= ($arSubItem["CHILD"] && $bShowChilds ? '<span class="arrow"><i></i></span>' : '') ?></div>
                                                        </a>
                                                    </div>
                                                <? endforeach; ?>
                                            </div>
                                        <? endforeach; ?>
                                    </div>
                              <!-- ДЛЯ КАТАЛОГА - НОВАЯ ДВУХКОЛОНОЧНАЯ СТРУКТУРА -->
<? elseif ($arItem['LINK'] == '/catalog/'): ?>
    <ul class="dropdown-menu" style="width: 100%; left: 0; right: 0;">
        <div class="megamenu-fullwidth">
            <!-- ЛЕВАЯ КОЛОНКА - КАТЕГОРИИ -->
            <div class="megamenu-sidebar">
                <ul class="megamenu-sidebar-list">
                    <? 
                    $visibleChildren = array();
                    foreach ($arItem["CHILD"] as $arSubItem) {
                        if (empty($arSubItem['PARAMS']['SECTION_IN_MENU'])) {
                            continue;
                        }
                        $visibleChildren[] = $arSubItem;
                    }
                    
                    foreach ($visibleChildren as $arIndex => $arSubItem): 
                        $picturePath = '';
                        if (isset($arSubItem['PARAMS']['PICTURE']) && $arSubItem['PARAMS']['PICTURE']) {
                            $arFile = CFile::GetFileArray($arSubItem['PARAMS']['PICTURE']);
                            if ($arFile) {
                                $picturePath = $arFile['SRC'];
                            }
                        }
                    ?>
                        <li class="megamenu-sidebar-item <?= ($arIndex === 0 ? 'megamenu-sidebar-active' : '') ?>" data-megamenu-target="<?= $arSubItem["LINK"] ?>">
                            <a href="<?= $arSubItem["LINK"] ?>" class="megamenu-sidebar-link">
                                <span class="submenu__link--wrap">
                                    <span class="submenu__link--svg">
                                        <? if ($picturePath): ?>
                                            <img src="<?= $picturePath ?>" alt="<?= $arSubItem["TEXT"] ?>">
                                        <? else: ?>
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1.5">
                                                <rect x="2" y="2" width="20" height="20" rx="2"/>
                                                <circle cx="8.5" cy="8.5" r="2.5"/>
                                                <path d="M21 15L16 10L5 21"/>
                                            </svg>
                                        <? endif; ?>
                                    </span>
                                    <span class="submenu__link--title"><?= $arSubItem["TEXT"] ?></span>
                                </span>
                            </a>
                        </li>
                    <? endforeach; ?>
                </ul>
            </div>
            
            <!-- ПРАВАЯ КОЛОНКА - ПОДКАТЕГОРИИ -->
            <div class="megamenu-content">
                <? foreach ($visibleChildren as $arSubItem): ?>
                    <?php 
                    $hasChildren = !empty($arSubItem["CHILD"]);
                    $hasMenuLinkTop = !empty($arSubItem['PARAMS']['MENULINK_TOP']) && is_array($arSubItem['PARAMS']['MENULINK_TOP']);
                    $isEmpty = !$hasChildren && !$hasMenuLinkTop;
                    ?>
                    <div class="megamenu-grid-group" data-megamenu-group="<?= $arSubItem["LINK"] ?>" style="<?= ($arSubItem === reset($visibleChildren) ? '' : 'display: none;') ?>">
                        <div class="megamenu-header">
                            <div class="megamenu-header-left">
                                <div class="megamenu-content-title"><?= $arSubItem["TEXT"] ?></div>
                                <a href="<?= $arSubItem["LINK"] ?>" class="megamenu-view-all-link">Смотреть все →</a>
                            </div>
                            <div class="megamenu-header-right">
                                <a href="/materials/umnaya-pergola-3kh3-s-mebelyu-i-led-podsvetkoy-gotovyy-komplekt-dlya-idealnogo-otdykha/" class="megamenu-banner-link">
                                    <div class="megamenu-banner-img-wrapper">
                                        <img src="https://latitudo.ru/upload/resize_cache/sprint.editor/48d/1200_768_1/qns7sbkk6dqd7be6wv8zfoj5p49t7cla.jpg" alt="Перголы. Новинка! Скоро в продаже" class="megamenu-banner-img">
                                        <div class="megamenu-banner-overlay"></div>
                                        <span class="megamenu-banner-text">Перголы<br>Новинка!<br>Скоро в продаже</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                        
                        <div class="megamenu-unified-grid">
                            <? if ($hasChildren): ?>
                                <? foreach ($arSubItem["CHILD"] as $arSubSubItem): ?>
                                    <?php if (empty($arSubSubItem['PARAMS']['SECTION_IN_MENU'])) {
                                        continue;
                                    } 
                                    $bSubHasPicture = (isset($arSubSubItem['PARAMS']['PICTURE']) && $arSubSubItem['PARAMS']['PICTURE']);
                                    ?>
                                    <a href="<?= $arSubSubItem["LINK"] ?>" class="megamenu-unified-card">
                                        <div class="megamenu-card-img">
                                            <? if ($bSubHasPicture): ?>
                                                <? 
                                                $arSubImg = CFile::ResizeImageGet($arSubSubItem['PARAMS']['PICTURE'], array('width' => 80, 'height' => 80), BX_RESIZE_IMAGE_EXACT);
                                                if (is_array($arSubImg)):?>
                                                    <img src="<?= $arSubImg["src"] ?>" alt="<?= $arSubSubItem["TEXT"] ?>">
                                                <? endif; ?>
                                            <? else: ?>
                                                <div class="megamenu-card-img-placeholder">
                                                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1">
                                                        <rect x="2" y="2" width="20" height="20" rx="2"/>
                                                        <circle cx="8.5" cy="8.5" r="2.5"/>
                                                        <path d="M21 15L16 10L5 21"/>
                                                    </svg>
                                                </div>
                                            <? endif; ?>
                                        </div>
                                        <div class="megamenu-card-name"><?= $arSubSubItem["TEXT"] ?></div>
                                    </a>
                                <? endforeach; ?>
                            <? endif; ?>
                            
                         <? if ($hasMenuLinkTop): ?>
    <?php foreach ($arSubItem['PARAMS']['MENULINK_TOP'] as $arSubHTML):?>
        <?php 
        $linkHtml = htmlspecialchars_decode($arSubHTML);
        
        // Ищем тег a и его содержимое
        if (preg_match('/<a\s+([^>]*?)>(.*?)<\/a>/is', $linkHtml, $matches)) {
            $linkAttributes = $matches[1];
            $linkText = strip_tags($matches[2]);
            
            // Сохраняем оригинальный класс, если есть
            if (strpos($linkAttributes, 'class=') === false) {
                $linkAttributes .= ' class="megamenu-unified-card"';
            } else {
                $linkAttributes = preg_replace('/class=["\']([^"\']*)["\']/', 'class="$1 megamenu-unified-card"', $linkAttributes);
            }
            
            // Создаем новую ссылку с теми же атрибутами, но с новой структурой внутри
            // Такая ссылка ведёт на посадочную страницу каталога (ИБ 21 «Посадочные в каталоге»),
            // а не на раздел, поэтому картинки раздела у неё нет — берём анонсную картинку посадочной.
            // Сопоставляем по свойству CPY_FILTER_TAG (в нём тот же URL), запасной вариант — по названию.
            if (!isset($GLOBALS['MEGAMENU_LANDING_PICS'])) {
                $GLOBALS['MEGAMENU_LANDING_PICS'] = array('URL' => array(), 'NAME' => array());
                $rsLandings = CIBlockElement::GetList(
                    array(),
                    array('IBLOCK_ID' => 21, 'ACTIVE' => 'Y'),
                    false,
                    false,
                    array('ID', 'NAME', 'PREVIEW_PICTURE', 'PROPERTY_CPY_FILTER_TAG')
                );
                while ($arLanding = $rsLandings->Fetch()) {
                    if (!$arLanding['PREVIEW_PICTURE']) {
                        continue;
                    }
                    $sLandingUrl = trim((string)$arLanding['PROPERTY_CPY_FILTER_TAG_VALUE']);
                    if ($sLandingUrl !== '') {
                        $GLOBALS['MEGAMENU_LANDING_PICS']['URL'][rtrim($sLandingUrl, '/') . '/'] = $arLanding['PREVIEW_PICTURE'];
                    }
                    $GLOBALS['MEGAMENU_LANDING_PICS']['NAME'][mb_strtolower(trim($arLanding['NAME']))] = $arLanding['PREVIEW_PICTURE'];
                }
            }

            $landingPictureID = 0;
            if (preg_match('/href\s*=\s*["\']([^"\']+)["\']/i', $linkAttributes, $arHrefMatch)) {
                $sMenuHref = rtrim(trim($arHrefMatch[1]), '/') . '/';
                if (isset($GLOBALS['MEGAMENU_LANDING_PICS']['URL'][$sMenuHref])) {
                    $landingPictureID = $GLOBALS['MEGAMENU_LANDING_PICS']['URL'][$sMenuHref];
                }
            }
            if (!$landingPictureID) {
                $sMenuText = mb_strtolower(trim($linkText));
                if (isset($GLOBALS['MEGAMENU_LANDING_PICS']['NAME'][$sMenuText])) {
                    $landingPictureID = $GLOBALS['MEGAMENU_LANDING_PICS']['NAME'][$sMenuText];
                }
            }
            $arLandingImg = ($landingPictureID ? CFile::ResizeImageGet($landingPictureID, array('width' => 80, 'height' => 80), BX_RESIZE_IMAGE_EXACT) : false);

            echo '<a ' . $linkAttributes . '>';
            echo '<div class="megamenu-card-img">';
            if (is_array($arLandingImg) && $arLandingImg['src']) {
                echo '<img src="' . $arLandingImg['src'] . '" alt="' . htmlspecialchars($linkText) . '">';
            } else {
                echo '<div class="megamenu-card-img-placeholder">';
                echo '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1">';
                echo '<rect x="2" y="2" width="20" height="20" rx="2"/>';
                echo '<circle cx="8.5" cy="8.5" r="2.5"/>';
                echo '<path d="M21 15L16 10L5 21"/>';
                echo '</svg>';
                echo '</div>';
            }
            echo '</div>';
            echo '<div class="megamenu-card-name">' . htmlspecialchars($linkText) . '</div>';
            echo '</a>';
        } else {
            // Если ссылки нет, выводим как обычный блок
            echo '<div class="megamenu-unified-card">';
            echo '<div class="megamenu-card-img">';
            echo '<div class="megamenu-card-img-placeholder">';
            echo '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="1">';
            echo '<rect x="2" y="2" width="20" height="20" rx="2"/>';
            echo '<circle cx="8.5" cy="8.5" r="2.5"/>';
            echo '<path d="M21 15L16 10L5 21"/>';
            echo '</svg>';
            echo '</div>';
            echo '</div>';
            echo $linkHtml;
            echo '</div>';
        }
        ?>
    <?php endforeach;?>
<? endif; ?>
                        </div>
                        
                        <? if ($isEmpty): ?>
                            <div class="megamenu-empty">
                                <p>Нет подкатегорий</p>
                            </div>
                        <? endif; ?>
                    </div>
                <? endforeach; ?>
            </div>
        </div>
        
        <!-- ============ БЛОК БРЕНДОВ ПОД ВСЕМ МЕНЮ ============ -->
        <div class="megamenu-brands-wrapper">
            <div class="megamenu-brands-container">
               
                <? $APPLICATION->IncludeComponent(
                    "bitrix:news.list",
                    "2025_brands_list_menu",
                    [
                        "SORT_BY_FILTER_ID" => "Y",
                        "IBLOCK_TYPE" => "aspro_next_content",
                        "IBLOCK_ID" => "12",
                        "NEWS_COUNT" => "20",
                        "FILTER_NAME" => "",
                        "FIELD_CODE" => [
                            0 => "PREVIEW_PICTURE",
                            1 => "",
                        ],
                        "PROPERTY_CODE" => [
                            0 => "",
                            1 => "LINK",
                            2 => "",
                        ],
                        "CHECK_DATES" => "Y",
                        "DETAIL_URL" => "",
                        "AJAX_MODE" => "N",
                        "AJAX_OPTION_JUMP" => "N",
                        "AJAX_OPTION_STYLE" => "Y",
                        "AJAX_OPTION_HISTORY" => "N",
                        "CACHE_TYPE" => "A",
                        "CACHE_TIME" => "36000000",
                        "CACHE_FILTER" => "Y",
                        "CACHE_GROUPS" => "N",
                        "PREVIEW_TRUNCATE_LEN" => "",
                        "ACTIVE_DATE_FORMAT" => "j F Y",
                        "SET_TITLE" => "N",
                        "SET_STATUS_404" => "N",
                        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
                        "ADD_SECTIONS_CHAIN" => "N",
                        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
                        "PARENT_SECTION" => "",
                        "PARENT_SECTION_CODE" => "",
                        "INCLUDE_SUBSECTIONS" => "Y",
                        "PAGER_TEMPLATE" => "",
                        "DISPLAY_TOP_PAGER" => "N",
                        "DISPLAY_BOTTOM_PAGER" => "N",
                        "PAGER_TITLE" => "",
                        "PAGER_SHOW_ALWAYS" => "N",
                        "PAGER_DESC_NUMBERING" => "N",
                        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
                        "PAGER_SHOW_ALL" => "N",
                        "AJAX_OPTION_ADDITIONAL" => "",
                        "COMPONENT_TEMPLATE" => "2025_brands_list_menu",
                        "SET_BROWSER_TITLE" => "N",
                        "SET_META_KEYWORDS" => "N",
                        "SET_META_DESCRIPTION" => "Y",
                        "SET_LAST_MODIFIED" => "N",
                        "PAGER_BASE_LINK_ENABLE" => "N",
                        "SHOW_404" => "N",
                        "MESSAGE_404" => "",
                        "SORT_BY1" => "SORT",
                        "SORT_ORDER1" => "ASC",
                        "SORT_BY2" => "SORT",
                        "SORT_ORDER2" => "ASC",
                        "STRICT_SECTION_CHECK" => "N",
                        "SHOW_DETAIL_LINK" => "Y",
                        "TITLE_BLOCK" => "Бренды",
                        "TITLE_BLOCK_ALL" => "Бренды",
                        "ALL_URL" => "/brands/"
                    ],
                    false
                ); ?>
            </div>
        </div>
        <!-- ================================================ -->
        
    </ul>
                                    
                                <!-- ДЛЯ ВСЕХ ОСТАЛЬНЫХ РАЗДЕЛОВ (services, sale и т.д.) - СТАРАЯ СТРУКТУРА -->
                                <? else: ?>
                                    <ul class="dropdown-menu">
                                        <? 
                                        $visibleChildren = array();
                                        foreach ($arItem["CHILD"] as $arSubItem) {
                                            $visibleChildren[] = $arSubItem;
                                        }
                                        $totalVisible = count($visibleChildren);
                                        $currentIndex = 0;
                                        foreach ($visibleChildren as $arSubItem): 
                                            $currentIndex++;
                                        ?>
                                            <? $bShowChilds = $arParams["MAX_LEVEL"] > 2; ?>
                                            <? $bHasPicture = (isset($arSubItem['PARAMS']['PICTURE']) && $arSubItem['PARAMS']['PICTURE'] && $arTheme['SHOW_CATALOG_SECTIONS_ICONS']['VALUE'] == 'Y'); ?>

                                            <li class="<?= (($arSubItem["CHILD"] || $arSubItem['PARAMS']['MENULINK_TOP']) && $bShowChilds ? "dropdown-submenu" : "") ?> <?= ($arSubItem["SELECTED"] ? "active" : "") ?> <?= ($bHasPicture ? "has_img" : "") ?>">
                                                <a href="<?= $arSubItem["LINK"] ?>" title="<?= $arSubItem["TEXT"] ?>"><span class="name"><?= $arSubItem["TEXT"] ?></span><?= ($arSubItem["CHILD"] && $bShowChilds ? '<span class="arrow"><i></i></span>' : '') ?></a>

                                                <? if (($arSubItem["CHILD"] || $arSubItem['PARAMS']['MENULINK_TOP']) && $bShowChilds): ?>
                                                    <ul class="dropdown-menu">
                                                        <? foreach ($arSubItem["CHILD"] as $arSubSubSubItem): ?>
                                                            <li class="menu-item <?= ($arSubSubSubItem["SELECTED"] ? "active" : "") ?>">
                                                                <a href="<?= $arSubSubSubItem["LINK"] ?>" title="<?= $arSubSubSubItem["TEXT"] ?>"><span class="name"><?= $arSubSubSubItem["TEXT"] ?></span></a>
                                                            </li>
                                                        <? endforeach; ?>

                                                        <?php if (!empty($arSubItem['PARAMS']['MENULINK_TOP']) && is_array($arSubItem['PARAMS']['MENULINK_TOP'])): ?>
                                                            <?php foreach ($arSubItem['PARAMS']['MENULINK_TOP'] as $arSubHTML):?>
                                                                <li class="menu-item">
                                                                    <?=htmlspecialchars_decode($arSubHTML)?>
                                                                </li>
                                                            <?php endforeach;?>
                                                        <?php endif; ?>
                                                    </ul>
                                                <? endif; ?>
                                            </li>
                                        <? endforeach; ?>
                                        <div class="clear"></div>
                                        <!-- Блок брендов для старых разделов, если есть wide_menu -->
                                   
                                    </ul>
                                <? endif; ?>
                            <? endif; ?>
                        </div>
                    </td>
                <? endforeach; ?>
            </tr>
        </table>
    </div>
<? endif; ?>

<script>
$(document).ready(function() {
    // Функция для выравнивания высоты dropdown-menu
    function alignDropdownHeight() {
        $('.menu-item.dropdown').each(function() {
            var $this = $(this);
            var $dropdown = $this.find('.dropdown-menu');
            var $sidebar = $dropdown.find('.megamenu-sidebar');
            var $fullwidth = $dropdown.find('.megamenu-fullwidth');
            
            if ($sidebar.length && $fullwidth.length) {
                $fullwidth.css('height', 'auto');
                $dropdown.css('height', 'auto');
                $sidebar.css('height', 'auto');
                
                var sidebarHeight = $sidebar.outerHeight(true);
                
                if (sidebarHeight > 0) {
                    $fullwidth.css('height', sidebarHeight + 'px');
                    $dropdown.css('height', sidebarHeight + 'px');
                    
                    var $content = $dropdown.find('.megamenu-content');
                    if ($content.length) {
                        $content.css('min-height', sidebarHeight + 'px');
                        $content.css('height', 'auto');
                    }
                }
            }
        });
    }
    
    setTimeout(alignDropdownHeight, 100);
    $(window).on('resize', function() {
        setTimeout(alignDropdownHeight, 100);
    });
    
    $('.menu-item.dropdown').on('mouseenter', function() {
        var $this = $(this);
        var $dropdown = $this.find('.dropdown-menu');
        
        setTimeout(function() {
            var $sidebar = $dropdown.find('.megamenu-sidebar');
            var $fullwidth = $dropdown.find('.megamenu-fullwidth');
            
            if ($sidebar.length && $fullwidth.length) {
                $fullwidth.css('height', 'auto');
                $dropdown.css('height', 'auto');
                
                var sidebarHeight = $sidebar.outerHeight(true);
                
                if (sidebarHeight > 0) {
                    $fullwidth.css('height', sidebarHeight + 'px');
                    $dropdown.css('height', sidebarHeight + 'px');
                    
                    var $content = $dropdown.find('.megamenu-content');
                    if ($content.length) {
                        $content.css('min-height', sidebarHeight + 'px');
                    }
                }
            }
        }, 50);
    });
    
    // Обработка наведения на боковое меню (только для каталога)
    $('.megamenu-sidebar-item').on('mouseenter', function() {
        var $this = $(this);
        var $menu = $this.closest('.dropdown-menu');
        var targetLink = $this.data('megamenu-target');
        
        $menu.find('.megamenu-sidebar-item').removeClass('megamenu-sidebar-active');
        $this.addClass('megamenu-sidebar-active');
        $menu.find('.megamenu-grid-group').hide();
        $menu.find('.megamenu-grid-group[data-megamenu-group="' + targetLink + '"]').show();
    });
    
    // Hover для выпадающих меню
    $('.menu-item.dropdown').hover(
        function() {
            var $this = $(this);
            var $dropdown = $this.find('.dropdown-menu');
            $dropdown.stop(true, true).fadeIn(200);
            $this.addClass('hovered');
            
            // Только для каталога активируем первый раздел
            if ($this.find('.megamenu-sidebar-item').length) {
                var $firstSidebarItem = $dropdown.find('.megamenu-sidebar-item:first');
                if ($firstSidebarItem.length && !$firstSidebarItem.hasClass('megamenu-sidebar-active')) {
                    $dropdown.find('.megamenu-sidebar-item').removeClass('megamenu-sidebar-active');
                    $firstSidebarItem.addClass('megamenu-sidebar-active');
                    var targetLink = $firstSidebarItem.data('megamenu-target');
                    $dropdown.find('.megamenu-grid-group').hide();
                    $dropdown.find('.megamenu-grid-group[data-megamenu-group="' + targetLink + '"]').show();
                }
            }
        },
        function() {
            var $this = $(this);
            var $dropdown = $this.find('.dropdown-menu');
            $dropdown.stop(true, true).fadeOut(150);
            $this.removeClass('hovered');
        }
    );
});
</script>

<style>
/* Стили для ссылок в выпадающем меню */
.mega-menu table .dropdown-menu li a {
    padding: 5px 29px 5px 19px !important;
}

/* Стили для partneram - увеличенные отступы */
.mega-menu table .dropdown-menu.partneram-menu li a {
    padding: 14px 29px 14px 19px !important;
}

/* Выпадающее меню на всю ширину - общая тень для всего меню включая бренды */
.dropdown-menu {
    position: absolute !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    padding: 0 !important;
    margin-top: 0 !important;
    border-radius: 0 0 8px 8px !important;
    box-shadow: 0 20px 35px -10px rgba(0,0,0,0.25), 0 10px 15px -5px rgba(0,0,0,0.1) !important;
    border: none !important;
    border-top: 1px solid #e9ecef !important;
    top: 100% !important;
    overflow: visible !important;
    background: #fff !important;
    z-index: 1000;
}

/* Для меню каталога - убираем закругления снизу, но тень остается на весь блок */
.menu-item.dropdown .dropdown-menu:has(.megamenu-fullwidth) {
    border-radius: 0 !important;
    box-shadow: 0 20px 35px -10px rgba(0,0,0,0.25), 0 10px 15px -5px rgba(0,0,0,0.1) !important;
    border-bottom: none !important;
}

/* Полноширинный контейнер */
.megamenu-fullwidth {
    display: flex;
    background: #fff;
    min-height: auto;
    height: auto;
    border-radius: 0;
    overflow: visible;
    position: relative;
}

/* Убираем внутреннюю тень */
.megamenu-fullwidth::after {
    display: none;
}

.menu-item.dropdown:hover .dropdown-menu {
    box-shadow: 0 25px 40px -12px rgba(0,0,0,0.3), 0 15px 20px -8px rgba(0,0,0,0.15) !important;
    transition: box-shadow 0.25s ease;
}

/* Для каталога при наведении - усиливаем тень на весь блок */
.menu-item.dropdown:has(.megamenu-fullwidth):hover .dropdown-menu {
    box-shadow: 0 25px 40px -12px rgba(0,0,0,0.3), 0 15px 20px -8px rgba(0,0,0,0.15) !important;
}

/* Левая боковая панель */
.megamenu-sidebar {
    width: 280px;
    background: #f8f9fa;
    border-right: 1px solid #e9ecef;
    flex-shrink: 0;
    overflow-y: auto;
    height: auto;
    min-height: 100%;
}

.megamenu-sidebar::-webkit-scrollbar {
    width: 4px;
}

.megamenu-sidebar::-webkit-scrollbar-track {
    background: #e9ecef;
}

.megamenu-sidebar::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.megamenu-sidebar::-webkit-scrollbar-thumb:hover {
    background: #b41818;
}

.megamenu-sidebar-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.megamenu-sidebar-item {
    margin: 0;
    border-bottom: 1px solid #e9ecef;
    position: relative;
    cursor: pointer;
}

.megamenu-sidebar-link {
    display: block;
    padding: 5px 16px;
    text-decoration: none;
}

.submenu__link--wrap {
    display: flex;
    align-items: center;
    padding-right: 0.5rem;
}

.submenu__link--svg {
    width: 36px;
    display: flex;
    align-items: center;
    flex-shrink: 0;
}

.submenu__link--svg img,
.submenu__link--svg svg {
    width: 35px;
    height: 35px;
    object-fit: contain;
}

.submenu__link--title {
    padding-left: 10px;
    font-size: 13px;
    color: #444;
    line-height: 1.2;
    font-weight: 400;
}

.megamenu-sidebar-link:hover .submenu__link--title {
    color: #b41818;
}

.megamenu-sidebar-item::after {
    content: '→';
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #bbb;
    font-size: 11px;
    transition: all 0.2s ease;
}

.megamenu-sidebar-item:hover::after {
    color: #b41818;
    transform: translateY(-50%) translateX(3px);
}

.megamenu-sidebar-item.megamenu-sidebar-active {
    background: #fff;
    box-shadow: inset 3px 0 0 #b41818;
}

.megamenu-sidebar-item.megamenu-sidebar-active .submenu__link--title {
    color: #b41818;
    font-weight: 500;
}

.megamenu-sidebar-item.megamenu-sidebar-active::after {
    color: #b41818;
}

/* Правая область контента */
.megamenu-content {
    flex: 1;
    padding: 20px 24px;
    background: #fff;
    overflow-y: auto;
    height: auto;
    min-height: 100%;
}

.megamenu-content::-webkit-scrollbar {
    width: 4px;
}

.megamenu-content::-webkit-scrollbar-track {
    background: #e9ecef;
}

.megamenu-content::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.megamenu-content::-webkit-scrollbar-thumb:hover {
    background: #b41818;
}

/* Хедер */
.megamenu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid #b41818;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 1;
}

.megamenu-header-left {
    display: flex;
    align-items: baseline;
    gap: 15px;
    flex-wrap: wrap;
}

.megamenu-content-title {
    font-size: 22px;
    font-weight: 600;
    color: #333;
    margin: 0;
    padding: 0;
}

.megamenu-view-all-link {
    font-size: 12px;
    color: #888;
    text-decoration: none;
    white-space: nowrap;
}

.megamenu-view-all-link:hover {
    color: #b41818;
}

.megamenu-header-right {
    flex-shrink: 0;
}

/* Баннер */
.megamenu-banner-link {
    text-decoration: none;
    display: block;
}

.megamenu-banner-img-wrapper {
    position: relative;
    width: 200px;
    height: 70px;
    border-radius: 4px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.megamenu-banner-img-wrapper:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.megamenu-banner-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.megamenu-banner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    transition: background 0.2s ease;
}

.megamenu-banner-img-wrapper:hover .megamenu-banner-overlay {
    background: rgba(0, 0, 0, 0.5);
}

.megamenu-banner-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 1px;
    text-align: center;
    white-space: nowrap;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    z-index: 1;
}

/* Сетка */
.megamenu-unified-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

/* Стили для карточки как ссылки */
.megamenu-unified-card {
    background: #f5f5f5;
    border-radius: 10px;
    transition: all 0.2s ease;
    overflow: hidden;
    display: block;
    text-decoration: none;
    cursor: pointer;
}

.megamenu-unified-card:hover {
    background: #e8e8e8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}
.megamenu-unified-card a {font-size:13px;text-align:center;color:#333;}
.megamenu-unified-card:hover a {color:#b41818;}
.megamenu-unified-card:hover .megamenu-card-name {
    color: #b41818;
}

.megamenu-card-img {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 16px auto 10px auto;
}

.megamenu-card-img img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.megamenu-card-img-placeholder {
    width: 60px;
    height: 60px;
    background: #e0e0e0;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.megamenu-card-name {
    font-size: 12px;
    font-weight: 500;
    color: #333;
    line-height: 1.3;
    text-align: center;
    padding: 0 10px 16px 10px;
}

.megamenu-empty {
    text-align: center;
    padding: 30px 20px;
    color: #999;
}

.megamenu-fullwidth {
    max-height: calc(100vh - 150px);
    overflow-y: auto;
}

.megamenu-fullwidth::-webkit-scrollbar {
    width: 6px;
}

.megamenu-fullwidth::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.megamenu-fullwidth::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.megamenu-fullwidth::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* ============ СТИЛИ ДЛЯ БЛОКА БРЕНДОВ ПОД МЕНЮ ============ */
.megamenu-brands-wrapper {
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
    margin-top: 0;
    padding: 16px 24px;
    clear: both;
    width: 100%;
    position: relative;
    z-index: 1;
}

/* Убираем двойную границу, если блоков брендов несколько */
.megamenu-brands-wrapper + .megamenu-brands-wrapper {
    border-top: none;
    padding-top: 0;
    margin-top: 0;
}

/* Убираем лишние отступы у основного контейнера меню каталога */
.dropdown-menu:has(.megamenu-brands-wrapper) {
    padding-bottom: 0 !important;
}

.megamenu-brands-container {
    max-width: 100%;
    margin: 0 auto;
}

/* Стили для списка брендов, которые выводит компонент */
.megamenu-brands-wrapper .brands-list,
.megamenu-brands-wrapper .news-list {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 12px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.megamenu-brands-wrapper .brand-item,
.megamenu-brands-wrapper .news-item {
    background: #fff;
    border-radius: 8px;
    padding: 8px;
    transition: all 0.2s ease;
    text-align: center;
    border: 1px solid #e9ecef;
}

.megamenu-brands-wrapper .brand-item:hover,
.megamenu-brands-wrapper .news-item:hover {
    background: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-color: #b41818;
}

.megamenu-brands-wrapper .brand-item a,
.megamenu-brands-wrapper .news-item a {
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    min-height: 60px;
}

.megamenu-brands-wrapper img {
    max-width: 100%;
    height: auto;
    max-height: 50px;
    object-fit: contain;
}

/* ============ АДАПТИВ ============ */
@media (max-width: 1200px) {
    .megamenu-unified-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
    .megamenu-banner-img-wrapper {
        width: 160px;
        height: 60px;
    }
    .megamenu-banner-text {
        font-size: 14px;
        white-space: nowrap;
    }
    .megamenu-brands-wrapper .brands-list,
    .megamenu-brands-wrapper .news-list {
        grid-template-columns: repeat(5, 1fr);
    }
}

@media (max-width: 992px) {
    .megamenu-unified-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .megamenu-sidebar {
        width: 240px;
    }
    .megamenu-banner-img-wrapper {
        width: 140px;
        height: 50px;
    }
    .megamenu-banner-text {
        font-size: 12px;
    }
    .megamenu-brands-wrapper .brands-list,
    .megamenu-brands-wrapper .news-list {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 768px) {
    .megamenu-fullwidth {
        flex-direction: column;
    }
    .megamenu-sidebar {
        width: 100%;
    }
    .megamenu-unified-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .megamenu-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    .megamenu-banner-img-wrapper {
        width: 100%;
        max-width: 200px;
    }
    .megamenu-brands-wrapper {
        padding: 12px 16px;
    }
    .megamenu-brands-wrapper .brands-list,
    .megamenu-brands-wrapper .news-list {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 480px) {
    .megamenu-unified-grid {
        grid-template-columns: repeat(1, 1fr);
    }
    .megamenu-brands-wrapper .brands-list,
    .megamenu-brands-wrapper .news-list {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>