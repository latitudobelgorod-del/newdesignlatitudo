<?global $arTheme;
global $APPLICATION;?>
<?$GLOBALS['arrProductsFilter2'] = array( "PROPERTY_".$arParams["LINKED_PRODUCTS_PROPERTY"] => $arElement["ID"] )?>
<?$APPLICATION->IncludeComponent(
	"bitrix:news.detail",
	"partners",
	Array(
		"S_ASK_QUESTION" => $arParams["S_ASK_QUESTION"],
		"S_ORDER_SERVISE" => $arParams["S_ORDER_SERVISE"],
		"T_GALLERY" => $arParams["T_GALLERY"],
		"T_DOCS" => $arParams["T_DOCS"],
		"T_GOODS" => $arParams["T_GOODS"],
		"T_SERVICES" => $arParams["T_SERVICES"],
		"T_PROJECTS" => $arParams["T_PROJECTS"],
		"T_REVIEWS" => $arParams["T_REVIEWS"],
		"T_STAFF" => $arParams["T_STAFF"],
		"T_VIDEO" => $arParams["T_VIDEO"],
		"FORM_ID_ORDER_SERVISE" => ($arParams["FORM_ID_ORDER_SERVISE"] ? $arParams["FORM_ID_ORDER_SERVISE"] : 'SERVICES'),
		"DISPLAY_DATE" => $arParams["DISPLAY_DATE"],
		"DISPLAY_NAME" => $arParams["DISPLAY_NAME"],
		"DISPLAY_PICTURE" => $arParams["DISPLAY_PICTURE"],
		"DISPLAY_PREVIEW_TEXT" => $arParams["DISPLAY_PREVIEW_TEXT"],
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"FIELD_CODE" => $arParams["DETAIL_FIELD_CODE"],
		"PROPERTY_CODE" => $arParams["DETAIL_PROPERTY_CODE"],
		"DETAIL_URL"	=>	$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["detail"],
		"SECTION_URL"	=>	$arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
		"META_KEYWORDS" => $arParams["META_KEYWORDS"],
		"META_DESCRIPTION" => $arParams["META_DESCRIPTION"],
		"BROWSER_TITLE" => $arParams["BROWSER_TITLE"],
		"DISPLAY_PANEL" => $arParams["DISPLAY_PANEL"],
		"SET_CANONICAL_URL" => $arParams["DETAIL_SET_CANONICAL_URL"],
		"SET_TITLE" => $arParams["SET_TITLE"],
		"SET_STATUS_404" => $arParams["SET_STATUS_404"],
		"INCLUDE_IBLOCK_INTO_CHAIN" => $arParams["INCLUDE_IBLOCK_INTO_CHAIN"],
		"ADD_SECTIONS_CHAIN" => $arParams["ADD_SECTIONS_CHAIN"],
		"ADD_ELEMENT_CHAIN" => $arParams["ADD_ELEMENT_CHAIN"],
		"ACTIVE_DATE_FORMAT" => $arParams["DETAIL_ACTIVE_DATE_FORMAT"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
		"USE_PERMISSIONS" => $arParams["USE_PERMISSIONS"],
		"GROUP_PERMISSIONS" => $arParams["GROUP_PERMISSIONS"],
		"DISPLAY_TOP_PAGER" => $arParams["DETAIL_DISPLAY_TOP_PAGER"],
		"DISPLAY_BOTTOM_PAGER" => $arParams["DETAIL_DISPLAY_BOTTOM_PAGER"],
		"PAGER_TITLE" => $arParams["DETAIL_PAGER_TITLE"],
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => $arParams["DETAIL_PAGER_TEMPLATE"],
		"PAGER_SHOW_ALL" => $arParams["DETAIL_PAGER_SHOW_ALL"],
		"CHECK_DATES" => $arParams["CHECK_DATES"],
		"ELEMENT_ID" => $arResult["VARIABLES"]["ELEMENT_ID"],
		"ELEMENT_CODE" => $arResult["VARIABLES"]["ELEMENT_CODE"],
		"IBLOCK_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["partners"],
		"GALLERY_TYPE" => $arParams["GALLERY_TYPE"],
	),
	$component
);?>


<? // товары каталога?>
<?if($arParams["SHOW_LINKED_PRODUCTS"] == "Y" && strlen($arParams["LINKED_PRODUCTS_PROPERTY"])):?>
	<div class="wraps goods-block with-padding">
	<?$GLOBALS['arrProductsFilter'] = array( "PROPERTY_".$arParams["LINKED_PRODUCTS_PROPERTY"] => $arElement["ID"], "ACTIVE"=>"Y" )?>
	<?/* Свойство бренда «Шаблон»: temp_numb_1 — «вывод по разделам», temp_numb_2 —
	   «вывод списком». На то же значение смотрит news.detail/partners, когда решает,
	   печатать ли якоря на разделы, — иначе якоря были бы без разделов или разделы
	   без якорей. Второй шаблон выбран в админке осознанно (Legro, Террасвет,
	   Bruggan, 4SiS) и остаётся сплошным списком, как был. */?>
	<?
	$ldBySections = false;
	$rsBrandTemplate = CIBlockElement::GetProperty($arParams['IBLOCK_ID'], $arElement['ID'], array(), array('CODE' => 'TEMPLATE'));
	if($arBrandTemplate = $rsBrandTemplate->Fetch())
		$ldBySections = ($arBrandTemplate['VALUE_XML_ID'] === 'temp_numb_1');
	?>
	<?/* Оба вывода делает include/brand_products.php шаблона — тот же файл зовёт
	   /local/ajax/brand_products.php, когда список догружает следующую порцию,
	   и параметры каталога у страницы и у догрузки обязаны совпадать.

	   Порция кратна ряду сетки, а в ней пять карточек: по разделам — 10 (два
	   ряда, как в макете «Категория производителя»), сплошным списком — 25
	   (пять рядов). Ширина сетки задана в css/newdesign-catalog.css. */?>
	<?$ldPerPortion = ($ldBySections ? 10 : 25);?>
	<?$ldBrand = array(
		'MODE' => ($ldBySections ? 'sections' : 'flat'),
		'FILTER' => $GLOBALS['arrProductsFilter'],
		'PER_SECTION' => $ldPerPortion,
		'TITLE' => str_replace('#BRAND_NAME#', $arElement['NAME'], (strlen($arParams['T_GOODS']) ? $arParams['T_GOODS'] : GetMessage('T_GOODS'))),
		'PRICE_CODE' => $arParams['PRICE_CODE'],
		'STORES' => $arParams['STORES'],
		'AJAX_QUERY' => http_build_query(array(
			'brand' => (int)$arElement['ID'],
			'prop' => $arParams['LINKED_PRODUCTS_PROPERTY'],
			'per' => $ldPerPortion,
		)),
	);?>
	<?include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/brand_products.php';?>
	</div>
<?endif;?>

<div class="editor" style="margin-top:40px;">
    <?$APPLICATION->IncludeComponent(
        "sprint.editor:blocks",
        ".default",
        Array(
            "ELEMENT_ID" => $arElement["ID"],
            "IBLOCK_ID" => $arElement["IBLOCK_ID"],
            "PROPERTY_CODE" => "EDITOR2",
            "USE_JQUERY" => "N",
            "USE_FANCYBOX" => "N",
        ),
        $component,
        Array(
            "HIDE_ICONS" => "Y"
        )
    );?>
</div>