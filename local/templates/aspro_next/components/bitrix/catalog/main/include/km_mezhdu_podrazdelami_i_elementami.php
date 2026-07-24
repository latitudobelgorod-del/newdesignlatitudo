<?//КМ между подразделами и элементами?>
<div class="group_description_block top editor">
                    <? switch ($regionID) 
					{
                        case 9278: //ВОРОНЕЖ
                        ?> 
						<?if ($section['UF_EDITOR3_VRN']):?>
      
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_VRN',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
                        <? else: ?>

                        <? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>

                        <? break;

                        case 9277: // БЕЛГОРОД
                        ?>
					
						<?if ($section['UF_EDITOR3_BEL']):?>
      
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_BEL',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
                        <? else: ?>

                        <? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>

                        <? break;
						
						case 9568: // КРАСНОДАР
                        ?> 
						<?if ($section['UF_EDITOR3_KRD']):?>
      
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_KRD',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
                        <? else: ?>

                        <? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>
					 
		
                        <? break;
						case 22018: // РОСТОВ
                        ?>

						<?if ($section['UF_EDITOR3_ROSTOV']):?> 
      
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_ROSTOV',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
                        <? else: ?>

                        <? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>
					 
						<? break;
							
						case 10102: // КУРСК
                        ?>

						<?if ($section['UF_EDITOR3_KURSK']):?>
      
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_KURSK',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
                        <? else: ?>

                        <? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>
						
						<? break;
						
						case 22002: // ЛИПЕЦК
                        ?>

						<?if ($section['UF_EDITOR3_LIPETSK']):?>
      
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_LIPETSK',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
                        <? else: ?>

                        <? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>
					 
						<? break;
							
						case 22017: // ТАМБОВ
                        ?>

						<?if ($section['UF_EDITOR3_TAMBOV']):?> 
      
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_TAMBOV',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
                        <? else: ?>

                        <? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>
					 
					    <? break;
							
						case 22029: // СТАВРОПОЛЬ
                        ?>

						<?if ($section['UF_EDITOR3_STAVR']):?>
      
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_STAVR',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
                        <? else: ?>

                        <? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>
					 
						<? break;
			
                        case 22016: // САНКТ-ПЕТЕРБУРГ
                        ?>	  
						
						<?if ($section['UF_EDITOR3_SPB']):?>
      					<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_SPB',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
                        <? else: ?>

                        <? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>
					 
						<? break;
                        case 10039: // МОСКВА
                        ?>

						<?if ($section['UF_EDITOR3']):?>
      
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
                        <? else: ?>

                        <? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>
						<? endif; ?>
					 
				
                        <? break;
                        default:
                            ?>
						<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR3_DEFAULT',
                                "NEWS_NAME" => $arSection["NAME"],
                                'USE_JQUERY' => 'N',
                                'USE_FANCYBOX' => 'N',
                            ), $component, array(
                                'HIDE_ICONS' => 'Y',
                            )) ?>

                        <? break;

                    }
                    ?>
</div>

<?//КМ между подразделами и элементами?>