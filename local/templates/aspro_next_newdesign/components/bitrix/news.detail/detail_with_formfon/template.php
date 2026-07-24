<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>

<?$this->setFrameMode(true);?>	
<?use \Bitrix\Main\Localization\Loc;?>



<?// shot top banners start?>
<?$bShowTopBanner = (isset($arResult['SECTION_BNR_CONTENT'] ) && $arResult['SECTION_BNR_CONTENT'] == true);?>
<?if($bShowTopBanner):?>
	<?$this->SetViewTarget("section_bnr_content");?>
		<?CNext::ShowTopDetailBanner($arResult, $arParams);?>
	<?$this->EndViewTarget();?>
<?endif;?>
<?// shot top banners end?>

<?if($arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"])
{$goy=$arResult["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"];}
else {$goy=$arResult['NAME'];}
?>
<?$bgimage =  CFile::ResizeImageGet($arResult['DISPLAY_PROPERTIES']['BNR_TOP_FORM']['VALUE'], array('width'=>842, 'height'=>619), BX_RESIZE_IMAGE_EXACT, true);?>
<?if($arResult["DISPLAY_PROPERTIES"]["YANDEX_HINT"]["VALUE"]):?>
<div id="hint_popup" title="<?=$arResult["DISPLAY_PROPERTIES"]["YANDEX_HINT"]["VALUE"];?>"></div>
<?endif;?>

<?if ($bgimage):?>
			<div class="service_formfon">
			<div class="row">
			<div class="col-md-9" style="background:url(<?=$bgimage['src']?>);background-size: cover;min-height: 500px;color: #fff;">
			<div class="text_form_services" >
					<h1 id="pagetitle"><?=$goy?></h1>

			
					<?if(strlen($arResult['FIELDS']['DETAIL_TEXT'])):?>
				<div class="content">
					<?// element detail text?>
					<?if(strlen($arResult['FIELDS']['DETAIL_TEXT'])):?>
						<?if($arResult['DETAIL_TEXT_TYPE'] == 'text'):?>
							<p><?=$arResult['FIELDS']['DETAIL_TEXT'];?></p>
						<?else:?>
							<?=$arResult['FIELDS']['DETAIL_TEXT'];?>
						<?endif;?>
					<?endif;?>
				</div>
			<?endif;?>

					<?if($arResult["DISPLAY_PROPERTIES"]["LIST_SERVICES"]):?>
						<?foreach($arResult["DISPLAY_PROPERTIES"]["LIST_SERVICES"]["VALUE"] as $arProperty):?>
						<div class="list"><i class="fa fa-chevron-circle-right" style=" padding-right: 20px;position: relative;top: 2px;"></i><?=$arProperty;?></div>
						<?endforeach;?>
					<?endif;?>
			</div>
	</div>
			<div class="col-md-3" style="padding:0;">
			<div class="form_inline_services" >
			<div class="head" >Закажите бесплатно консультацию, замер или расчет</div>
				 <?$APPLICATION->IncludeComponent(
			"bitrix:form.result.new", 
			"form_inline_services", 
			array(
				"CACHE_TIME" => "3600",
				"CACHE_TYPE" => "N",
				"CHAIN_ITEM_LINK" => "",
				"CHAIN_ITEM_TEXT" => "",
				"EDIT_URL" => "",
				"IGNORE_CUSTOM_TEMPLATE" => "N",
				"LIST_URL" => "",
				"SEF_MODE" => "N",
				"SUCCESS_URL" => "",
				"USE_EXTENDED_ERRORS" => "Y",
				"WEB_FORM_ID" => "19",
				"AJAX_MODE" => "Y",
				"AJAX_OPTION_SHADOW" => "N",
				"AJAX_OPTION_JUMP" => "Y",
				"AJAX_OPTION_STYLE" => "Y",
				"AJAX_OPTION_ADDITIONAL" => "random",
				"COMPONENT_TEMPLATE" => "form_inline_services",
				"SEF_FOLDER" => "",
				"VARIABLE_ALIASES" => array(
					"WEB_FORM_ID" => "",
					"RESULT_ID" => "",
				)
			),
			false
		);?>
			</div>
			</div>
			</div>

	</div>
	
	<?else:?>
	<h1 id="pagetitle"><?=$goy?></h1>
		<?if(strlen($arResult['FIELDS']['DETAIL_TEXT'])):?>
				<div class="content">
					<?// element detail text?>
					<?if(strlen($arResult['FIELDS']['DETAIL_TEXT'])):?>
						<?if($arResult['DETAIL_TEXT_TYPE'] == 'text'):?>
							<p><?=$arResult['FIELDS']['DETAIL_TEXT'];?></p>
						<?else:?>
							<?=$arResult['FIELDS']['DETAIL_TEXT'];?>
						<?endif;?>
					<?endif;?>
				</div>
			<?endif;?>
				<?if($arResult["DISPLAY_PROPERTIES"]["LIST_SERVICES"]):?>
						<?foreach($arResult["DISPLAY_PROPERTIES"]["LIST_SERVICES"]["VALUE"] as $arProperty):?>
						<div class="list"><i class="fa fa-chevron-circle-right"></i><?=$arProperty;?></div>
						<?endforeach;?>
					<?endif;?>
	<?endif;?>

<div class="editor">
        <?$APPLICATION->IncludeComponent(
            "sprint.editor:blocks",
            ".default",
            Array(
                "ELEMENT_ID" => $arResult["ID"],
                "IBLOCK_ID" => $arResult["IBLOCK_ID"],
                "PROPERTY_CODE" => "EDITOR1",
                "USE_JQUERY" => "N",
               
            ),
            $component,
            Array(
                "HIDE_ICONS" => "Y"
            )
        );?>
    </div>

<div class="editor">
    <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            "ELEMENT_ID" => $arResult["ID"],
            "IBLOCK_ID" => $arResult["IBLOCK_ID"],
            "PROPERTY_CODE" => "EDITOR2",
            "USE_JQUERY" => "N",
            
        ),
        $component,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
</div>

		
					
					
					     
						 