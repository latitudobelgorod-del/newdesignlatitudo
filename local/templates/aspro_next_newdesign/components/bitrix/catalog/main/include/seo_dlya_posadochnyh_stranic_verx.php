<?/*SEO текст для посадочных страниц каталога по регионам*/?>
<div class="group_description_block top editor pos_catalog_top">
              
			<? switch ($regionID) {
                case 9278: // ВОРОНЕЖ
                ?>
				<?if ($arSeoItem['PROPERTY_EDITOR1_VRN_VALUE']):?>
      
                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1_VRN",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>
                <? else: ?>
				<? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>
                <? endif; ?>
                <? break;

                case 9277: // БЕЛГОРОД
                ?>
								
                <?if ($arSeoItem['PROPERTY_EDITOR1_BEL_VALUE']):?>
                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1_BEL",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                ); ?>

                <? else: ?>
                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>
                <? endif; ?>
                <? break;
				
				case 9568: // КРАСНОДАР
                ?>
                
				<?if ($arSeoItem['PROPERTY_EDITOR1_KRD_VALUE']):?>
                                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1_KRD",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>

                <? else: ?>
                
				<? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>
                <? endif; ?>

                <? break;
                case 10039: // МОСКВА
                ?>
                <?if ($arSeoItem['PROPERTY_EDITOR1_MSK_VALUE']):?>
                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1_MSK",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>

                <? else: ?>
                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>
                <? endif; ?>

                <? break;
								
				case 22018: // РОСТОВ
                                ?>
                                <?if ($arSeoItem['PROPERTY_EDITOR1_ROSTOV_VALUE']):?>
                                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1_ROSTOV",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                ); ?>

                <? else: ?>
                
				<? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>
                <? endif; ?>

                <? break;
								
								
				case 10102: // КУРСК
                                ?>
                                <?if ($arSeoItem['PROPERTY_EDITOR1_KURSK_VALUE']):?>
                                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1_KURSK",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); 
				?>

                <? else: ?>
                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>
                <? endif; ?>

                <? break;	
								
								
				case 22002: // ЛИПЕЦК
                                ?>
                                <?if ($arSeoItem['PROPERTY_EDITOR1_LIPETSK_VALUE']):?>
                                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1_LIPETSK",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>

                <? else: ?>
                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>
                <? endif; ?>

                <? break;		
								
				case 22017: // ТАМБОВ
                ?>
                <?if ($arSeoItem['PROPERTY_EDITOR1_TAMBOV_VALUE']):?>
                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1_TAMBOV",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>

                <? else: ?>
                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>
                <? endif; ?>

                <? break;			
								
				case 22029: // СТАВРОПОЛЬ
                ?>
                <?if ($arSeoItem['PROPERTY_EDITOR1_STAVR_VALUE']):?>
                                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1_STAVR",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>

                <? else: ?>
                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>
                <? endif; ?>

                <? break;			
				
								
                default:
                ?>
                <? $APPLICATION->IncludeComponent(
                            "sprint.editor:blocks",
                            "aspro-catalog-section",
                            array(
                                "ELEMENT_ID" => $arSeoItem["ID"],
                                "IBLOCK_ID" => $arSeoItem["IBLOCK_ID"],
                                "PROPERTY_CODE" => "EDITOR1",
                                "NEWS_NAME" => $arSeoItem["NAME"],
                                "USE_JQUERY" => "N",
                                "USE_FANCYBOX" => "Y",
                            ),
                            $component,
                            array(
                                "HIDE_ICONS" => "Y"
                            )
                        ); ?>

                <? break;

                }?>
						
							
              
</div>