<?//КМ Верхние теги (DEFAULT)?>
<div class="section_tag_top">
                    		<?if ($section['UF_EDITOR3_TOP_DEFAULT']):?>
      
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section-top-tag", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_TOP_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
                        <? else: ?>

                        <? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section-top-tag", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_TOP_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>

</div>


			   <? $APPLICATION->IncludeComponent(
                    "bitrix:news.list",
                    isMobile() ? "landings_list_mobile" : "landings_list",
                    array(
                        "IBLOCK_TYPE" => "aspro_next_catalog",
                        "IBLOCK_ID" => CNextCache::$arIBlocks[SITE_ID]["aspro_next_catalog"]["aspro_next_catalog_info"][0],
                        "NEWS_COUNT" => "999",
                        "SHOW_COUNT" => $arParams["LANDING_SECTION_COUNT"],
                        "COMPARE_FIELD" => "FILTER_URL",
                        "COMPARE_PROP" => "Y",
                        "SORT_BY1" => "SORT",
                        "SORT_ORDER1" => "ASC",
                        "SORT_BY2" => "ID",
                        "SORT_ORDER2" => "DESC",
                        "FILTER_NAME" => "arLandingSections",
                        "FIELD_CODE" => array(
                            0 => "SORT",
                            1 => "NAME",
                        ),
                        "PROPERTY_CODE" => array(
                            0 => "LINK",
                            1 => "",
                        ),
                        "CHECK_DATES" => "Y",
                        "DETAIL_URL" => "",
                        "AJAX_MODE" => "N",
                        "AJAX_OPTION_JUMP" => "N",
                        "AJAX_OPTION_STYLE" => "Y",
                        "AJAX_OPTION_HISTORY" => "N",
                        "CACHE_TYPE" => "A",
                        "CACHE_TIME" => "172800",
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
                        "COMPONENT_TEMPLATE" => "next",
                        "SET_BROWSER_TITLE" => "N",
                        "SET_META_KEYWORDS" => "N",
                        "SET_META_DESCRIPTION" => "N",
                        "SET_LAST_MODIFIED" => "N",
                        "PAGER_BASE_LINK_ENABLE" => "N",
                        "TITLE_BLOCK" => $arParams["LANDING_TITLE"],
                        "SHOW_404" => "N",
                        "MESSAGE_404" => ""
                    ),
                    false, array("HIDE_ICONS" => "Y")
                ); ?>
                
                
<?//КМ Верхние теги (DEFAULT)?>