<?//КМ Нижние теги (DEFAULT)?>
<div class="section_tag_bottom">
                    		<?if ($section['UF_EDITOR3_BOTTOM_DEFAULT']):?>
      
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section-top-tag", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_BOTTOM_DEFAULT',
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
                                'PROPERTY_CODE' => 'UF_EDITOR3_BOTTOM_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>

</div>
<?//КМ Нижние теги (DEFAULT)?>
