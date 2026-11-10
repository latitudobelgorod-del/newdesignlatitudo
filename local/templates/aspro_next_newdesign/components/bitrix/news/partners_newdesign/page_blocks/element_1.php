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

	   Порция кратна ряду сетки, а в ней пять карточек: по разделам — 5 (один
	   ряд; в макете «Категория производителя» два, сократили ради скорости),
	   сплошным списком — 25 (пять рядов). Ширина сетки задана в
	   css/newdesign-catalog.css. */?>
	<?/* 28.10.2026 (Ирина: «страница бренда грузится долго»): по разделам — один
	   ряд (5) и сразу только два раздела, остальные догружаются при прокрутке
	   (EAGER_SECTIONS). У EasyDecking было 97 карточек и 2.5 МБ разметки. */?>
	<?$ldPerPortion = ($ldBySections ? 5 : 25);?>
	<?$ldBrand = array(
		'MODE' => ($ldBySections ? 'sections' : 'flat'),
		'FILTER' => $GLOBALS['arrProductsFilter'],
		'PER_SECTION' => $ldPerPortion,
		'EAGER_SECTIONS' => 2,
		/* «Товары … в нашем магазине» печатаем только у сплошного списка. При
		   выводе по разделам заголовок не нужен: под ним сразу идёт название
		   первого раздела, и две надписи подряд читаются как ошибка. */
		'TITLE' => ($ldBySections
			? ''
			: str_replace('#BRAND_NAME#', $arElement['NAME'], (strlen($arParams['T_GOODS']) ? $arParams['T_GOODS'] : GetMessage('T_GOODS')))),
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
    <?/* «Документация» — документы инфоблока «Лицензии и сертификаты» (13), у которых
       в свойстве «Привязка к производителю» (BRAND) выбрана эта марка. До 10.11.2026 блок
       собирали руками в EDITOR2 выше; привязки и перенос — tools/brand-docs/nd_brand_docs.php.
       Порядок — сортировка элемента, затем название. */?>
    <?// Просроченные документы (DATE раньше сегодня) не показываем; дата в ключе кеша — обновляется сама.
    $GLOBALS['arrBrandDocsFilter'] = array(
        'PROPERTY_BRAND' => (int)$arElement['ID'],
        array('LOGIC' => 'OR', array('PROPERTY_DATE' => false), array('>=PROPERTY_DATE' => date('Y-m-d'))),
    );?>
    <?$APPLICATION->IncludeComponent(
        "bitrix:news.list",
        "brand-docs_newdesign",
        array(
            "IBLOCK_TYPE" => "aspro_next_content",
            "IBLOCK_ID" => "13",
            "NEWS_COUNT" => "200",
            "SORT_BY1" => "SORT",
            "SORT_ORDER1" => "ASC",
            "SORT_BY2" => "NAME",
            "SORT_ORDER2" => "ASC",
            "FILTER_NAME" => "arrBrandDocsFilter",
            "FIELD_CODE" => array("NAME", ""),
            "PROPERTY_CODE" => array("INSTRUCTIONS", ""),
            "CHECK_DATES" => "Y",
            "ACTIVE_DATE_FORMAT" => "d.m.Y",
            "SET_TITLE" => "N",
            "SET_BROWSER_TITLE" => "N",
            "SET_META_KEYWORDS" => "N",
            "SET_META_DESCRIPTION" => "N",
            "SET_LAST_MODIFIED" => "N",
            "SET_STATUS_404" => "N",
            "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
            "ADD_SECTIONS_CHAIN" => "N",
            "HIDE_LINK_WHEN_NO_DETAIL" => "Y",
            "PARENT_SECTION" => "",
            "INCLUDE_SUBSECTIONS" => "Y",
            "DISPLAY_TOP_PAGER" => "N",
            "DISPLAY_BOTTOM_PAGER" => "N",
            "PAGER_SHOW_ALWAYS" => "N",
            "CACHE_TYPE" => "A",
            "CACHE_TIME" => "36000000",
            "CACHE_FILTER" => "Y",
            "CACHE_GROUPS" => "N",
            "ND_TITLE" => "Документация",
        ),
        $component,
        array("HIDE_ICONS" => "Y")
    );?>
</div>

<?/* «Видео о продукции» — в самом низу, под «Документацией» (она в блоках
   редактора EDITOR2 выше; Ирина, 28.10.2026: сначала стоял до товаров). Роликов у марки нет — блока нет.
   Ролики — инфоблок «Видео», см. local/php_interface/include/latitudo_videos.php. */?>
<?$APPLICATION->IncludeComponent(
	"latitudo:video.rail",
	"",
	array(
		"BRAND_ID" => $arElement["ID"],
		// заголовок с названием марки — Ирина, 28.10.2026
		"TITLE" => "Видео ".$arElement["NAME"],
		"SUBTITLE" => "Фактура, цвета и монтаж — смотрите, прежде чем покупать",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => 3600,
	),
	$component,
	array("HIDE_ICONS" => "Y")
);?>
