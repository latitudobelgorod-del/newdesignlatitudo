<div class="editor padd">
    			<?switch ($regionID) {				
		case 9278:?>
<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_VRN",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_VRN",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>


		<? break;
		
		case 9277:?>
	
<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_BEL",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_BEL",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>


		<? break;
		
		
		
		
case 10102:?>
	
<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_KURSK",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_KURSK",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>


		<? break;
		
		
		
				
case 22002:?>
	
<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_LIPETSK",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_LIPETSK",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>


		<? break;
		
		
	case 22017:?>
	
<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_TAMBOV",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_TAMBOV",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>


		<? break;	
		
		
		
		case 22029:?>
	
<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_STAVR",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_STAVR",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>


		<? break;	
			case 9568:?>
	
<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_KRD",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_KRD",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>


		<? break;
			
		case 10039:?>
				
<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_MSK",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_MSK",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>


		<? break;
		
		
			case 22018:?>
				
<? ob_start();
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_ROSTOV",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
		$buffer = trim(ob_get_clean());
			preg_match('#<div[^>]*>(.*?)</div>#uis', $buffer, $matches);
			if ($matches){
				if (trim($matches[1])){
					echo $buffer;
				}
				else{
					// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2_ROSTOV",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
				}
			}
			else{
				// Твой компонент
$APPLICATION->IncludeComponent(
                "sprint.editor:blocks",
                ".default",
                Array(
                    'IBLOCK_ID' => 18,
                    'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
                    "PROPERTY_CODE" => "UF_EDITOR2",
                    "USE_JQUERY" => "N",
                    "USE_FANCYBOX" => "N",
                ),
                false,
                Array(
                    "HIDE_ICONS" => "Y"
                )
            );
			}


?>

		<? break;
		default:?>
    
    <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            'IBLOCK_ID' => 18,
            'SECTION_ID' => $arResult['SECTIONS'][$arParams['PARENT_SECTION']]['ID'],
            "PROPERTY_CODE" => "UF_EDITOR2",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        false,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
							
		<? break;						
		}					
		?>
</div>
		