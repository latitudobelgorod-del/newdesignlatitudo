<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>
<?global $arRegion;
$regionID = ($arRegion ? $arRegion['ID'] : '');?>

<div class="news-list">

	<h1 id="pagetitle"><?=$arResult["PAGE_TITLE"]?></h1>
<?foreach($arResult["ITEMS"] as $arItem):?>
	<?
	$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
	$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
	?>


	<div class="news-item" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
	
<div class="editor">


          <? switch ($regionID) {
                     	case 9278:?>

                            
                            <? if (!empty($arItem['PROPERTIES']['EDITOR1_VRN']['VALUE'])): ?>
                             <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_VRN",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
                        <? else: ?>
						 <? if (!empty($arItem['PROPERTIES']['EDITOR1_DEFAULT']['VALUE'])): ?>
                            <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_DEFAULT",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>  <? endif; ?>
                        <? endif; ?>

                            <? break;

                        case 9277:?>
	

                            <? if (!empty($arItem['PROPERTIES']['EDITOR1_BEL']['VALUE'])): ?>
                            <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_BEL",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
                        <? else: ?>
						<? if (!empty($arItem['PROPERTIES']['EDITOR1_DEFAULT']['VALUE'])): ?>
                            <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_DEFAULT",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
                         <? endif; ?>
						 <? endif; ?>
                            <? break;
                        case 22016:
                            ?>
							
                             <? if (!empty($arItem['PROPERTIES']['EDITOR1_SPB']['VALUE'])): ?>
                            <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_SPB",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
                        <? else: ?>
						 <? if (!empty($arItem['PROPERTIES']['EDITOR1_DEFAULT']['VALUE'])): ?>
                            <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_DEFAULT",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?> <? endif; ?>
                        <? endif; ?>
                            <? break;

  case 9568:
                            ?>
							
                             <? if (!empty($arItem['PROPERTIES']['EDITOR1_KRD']['VALUE'])): ?>
                            <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_KRD",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
                        <? else: ?>
						 <? if (!empty($arItem['PROPERTIES']['EDITOR1_DEFAULT']['VALUE'])): ?>
                            <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_DEFAULT",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?> <? endif; ?>
                        <? endif; ?>
                            <? break;

 case 22018:
                            ?>
							
                             <? if (!empty($arItem['PROPERTIES']['EDITOR1_ROSTOV']['VALUE'])): ?>
                            <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_ROSTOV",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
                        <? else: ?>
						 <? if (!empty($arItem['PROPERTIES']['EDITOR1_DEFAULT']['VALUE'])): ?>
                            <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_DEFAULT",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?> <? endif; ?>
                        <? endif; ?>
                            <? break;



                        case 10039:
                            ?>
							

                             <? if (!empty($arItem['PROPERTIES']['EDITOR1']['VALUE'])): ?>
                            <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
                        <? else: ?>
 <? if (!empty($arItem['PROPERTIES']['EDITOR1_DEFAULT']['VALUE'])): ?>						
 <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_DEFAULT",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
                        <? endif; ?> <? endif; ?>

                            <? break;
                        default:
                            ?>
                              <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arItem["ID"],
                "IBLOCK_ID" => $arItem["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1_DEFAULT",
                "USE_JQUERY" => "N",
                "USE_FANCYBOX" => "N",
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>

                            <? break;

                    }

                    ?>
    </div>
	</div>
<?endforeach;?>

</div>

<?/* отладочный дамп, отключён */ //file_put_contents($_SERVER['DOCUMENT_ROOT'].'/f5f5f5f.txt', print_r($arItem, 1));?>

