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

<?//КМ Верхние теги (DEFAULT) — только текстовый блок; чипы посадочных
// страниц вынесены в landings_tags_newdesign.php: в новом дизайне они стоят
// в одной строке с сортировкой.?>
