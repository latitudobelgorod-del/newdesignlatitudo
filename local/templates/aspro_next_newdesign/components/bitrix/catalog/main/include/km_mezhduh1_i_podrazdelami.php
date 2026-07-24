<div class="group_description_block top editor 1-1">
            <? switch ($regionID) {
                case 9278: // ВОРОНЕЖ
                ?> 
			<?if ($section['UF_EDITOR1_VRN']):?>
      
			<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR1_VRN',
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
                                'PROPERTY_CODE' => 'UF_EDITOR1_DEFAULT',
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
					
			<?if ($section['UF_EDITOR1_BEL']):?>
      
			<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR1_BEL',
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
                                'PROPERTY_CODE' => 'UF_EDITOR1_DEFAULT',
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
			<?if ($section['UF_EDITOR1_KRD']):?>
      
			<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR1_KRD',
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
                                'PROPERTY_CODE' => 'UF_EDITOR1_DEFAULT',
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

			<?if ($section['UF_EDITOR1_ROSTOV']):?>
      
			<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR1_ROSTOV',
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
                                'PROPERTY_CODE' => 'UF_EDITOR1_DEFAULT',
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

			<?if ($section['UF_EDITOR1_KURSK']):?>
      
			<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR1_KURSK',
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
                                'PROPERTY_CODE' => 'UF_EDITOR1_DEFAULT',
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

			<?if ($section['UF_EDITOR1_LIPETSK']):?>
      
			<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR1_LIPETSK',
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
                                'PROPERTY_CODE' => 'UF_EDITOR1_DEFAULT',
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

			<?if ($section['UF_EDITOR1_TAMBOV']):?>
      
			<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR1_TAMBOV',
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
                                'PROPERTY_CODE' => 'UF_EDITOR1_DEFAULT',
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

			<?if ($section['UF_EDITOR1_STAVR']):?>
      
			<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR1_STAVR',
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
                                'PROPERTY_CODE' => 'UF_EDITOR1_DEFAULT',
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
			<?if ($section['UF_EDITOR1_SPB']):?>
      
			<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR1_SPB',
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
                                'PROPERTY_CODE' => 'UF_EDITOR1_DEFAULT',
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

			<?if ($section['UF_EDITOR1']):?>
			<? $APPLICATION->IncludeComponent("sprint.editor:blocks", "aspro-catalog-section", array(
                                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                                'SECTION_ID' => $arSection['ID'],
                                'PROPERTY_CODE' => 'UF_EDITOR1',
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
                                'PROPERTY_CODE' => 'UF_EDITOR1_DEFAULT',
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
                            'PROPERTY_CODE' => 'UF_EDITOR1_DEFAULT',
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