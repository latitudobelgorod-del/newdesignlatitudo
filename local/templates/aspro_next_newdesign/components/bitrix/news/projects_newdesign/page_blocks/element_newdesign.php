<?
// Блок «Также вас может заинтересовать» — это область редактора EDITOR2.
// По макету (Figma «Проект» 20524:98253, фрейм 20545:102225) он стоит
// ниже «Оказанных услуг» и во всю ширину контейнера (1336, три
// карточки по 429), а не в текстовой колонке 878, где его рисует
// шаблон news.detail. Поэтому печатаем его отсюда, после услуг.
//
// Переносим только тогда, когда в EDITOR2 лежит именно список проектов
// (блоки iblock_elements плюс заголовок htag). У девяти старых проектов
// в той же области лежат текст и фотогалерея — это часть статьи,
// её место остаётся в колонке.
$ndEditor2Below = false;
// Берём «сырое» значение (~PROPERTY_…): в отображаемом кавычки уже
// превращены в &quot; и json_decode на нём падает.
$ndEd2Raw = $arElement['~PROPERTY_EDITOR2_VALUE'];
if(strlen($ndEd2Raw))
{
	$ndEd2 = json_decode($ndEd2Raw, true);
	$ndEd2Names = array();
	if(is_array($ndEd2) && isset($ndEd2['blocks']) && is_array($ndEd2['blocks']))
	{
		foreach($ndEd2['blocks'] as $ndEd2Block)
		{
			if(is_array($ndEd2Block) && isset($ndEd2Block['name']))
				$ndEd2Names[$ndEd2Block['name']] = true;
		}
	}
	$ndEditor2Below = isset($ndEd2Names['iblock_elements'])
		&& !array_diff(array_keys($ndEd2Names), array('iblock_elements', 'htag'));
}
?>
	<?$APPLICATION->IncludeComponent(
	"bitrix:news.detail",
	"projects_newdesign",
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
		"SET_STATUS_404" => "Y",
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
		"IBLOCK_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["news"],
		"USE_SHARE" 			=> $arParams["USE_SHARE"],
		"SHARE_HIDE" 			=> $arParams["SHARE_HIDE"],
		"SHARE_TEMPLATE" 		=> $arParams["SHARE_TEMPLATE"],
		"SHARE_HANDLERS" 		=> $arParams["SHARE_HANDLERS"],
		"SHARE_SHORTEN_URL_LOGIN"	=> $arParams["SHARE_SHORTEN_URL_LOGIN"],
		"SHARE_SHORTEN_URL_KEY" => $arParams["SHARE_SHORTEN_URL_KEY"],
		"GALLERY_TYPE" => $arParams["GALLERY_TYPE"],
		// Сам блок EDITOR2 печатаем ниже, шаблону его рисовать не надо.
		"ND_EDITOR2_BELOW" => ($ndEditor2Below ? "Y" : "N"),
	),
	$component
);?>

<?if(in_array('FORM_QUESTION', $arParams['DETAIL_PROPERTY_CODE']) && $arElement['PROPERTY_FORM_QUESTION_VALUE']):?>
	<div class="row">
		<div class="col-md-12">
<?endif;?>

<?$list_view = ($arParams['LIST_VIEW'] ? $arParams['LIST_VIEW'] : 'slider');?>
<?// Материалы проекта — товары, привязанные свойством LINK_GOODS.
   // Вид тот же, что у товаров акции (news.detail/news_newdesign): серая черта,
   // заголовок и карточки нового дизайна. Общий include/brand_products.php,
   // режим сплошного списка. Прежний вызов include/news.detail.products_block.php
   // рисовал карточки старого дизайна и остался у шаблона projects.
   //
   // Блок печатаем, только когда привязанные товары есть и активны: иначе
   // каталог рисовал заглушку «Раздел сейчас наполняется» под заголовком.
   // Число товаров считаем сами — оно же задаёт размер порции, чтобы список
   // вышел целиком: кнопку «Показать ещё» здесь нечем обслуживать (у акции
   // для неё свой обработчик /local/ajax/promo_products.php), а молча
   // обрезать список нельзя.?>
<?if(in_array('LINK_GOODS', $arParams['DETAIL_PROPERTY_CODE']) && $arElement['PROPERTY_LINK_GOODS_VALUE']):?>
	<?
	$ndGoodsFilter = array('ID' => $arElement['PROPERTY_LINK_GOODS_VALUE'], 'ACTIVE' => 'Y');
	$ndGoodsIblock = (int)\Bitrix\Main\Config\Option::get('aspro.next', 'CATALOG_IBLOCK_ID', 19);
	$ndGoodsCount = (int)CIBlockElement::GetList(array(), array_merge($ndGoodsFilter, array('IBLOCK_ID' => $ndGoodsIblock)), array());
	?>
	<?// Блок оформлен так же, как «Оказанные услуги» ниже: во всю ширину, с
	   // крупным заголовком и первым после текста проекта, под чертой (Ирина,
	   // 10 сентября 2026). Заголовок печатаем сами — у шаблона каталога он
	   // мелкий, с <hr> над ним (h5 из параметра TITLE). Стили — .nd-projgoods
	   // в news.detail/projects_newdesign/style.css.?>
	<?if($ndGoodsCount):?>
		<div class="wraps goods-block with-padding nd-projgoods">
			<h2 class="nd-projgoods__title">Материалы</h2>
			<?
			$ldBrand = array(
				'MODE' => 'flat',
				'FILTER' => $ndGoodsFilter,
				'PER_SECTION' => $ndGoodsCount,
				'TITLE' => '',
			);
			include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/brand_products.php';
			?>
		</div>
	<?endif;?>
<?endif;?>


<?global $arRegion;
	$regionID = ($arRegion ? $arRegion['ID'] : '');?>
<?// services links?>
<?if(in_array('LINK_SERVICES', $arParams['DETAIL_PROPERTY_CODE']) && $arElement['PROPERTY_LINK_SERVICES_VALUE']):?>
	<div class="wraps">
		<?global $arrrFilter; $arrrFilter = array("ID" => $arElement["PROPERTY_LINK_SERVICES_VALUE"], array("LOGIC" => "OR", array("PROPERTY_LINK_REGION" => false), array("PROPERTY_LINK_REGION" => $regionID)));?>
		
		
				
		<?$APPLICATION->IncludeComponent("bitrix:news.list", "items-services_newdesign", array(
			"IBLOCK_TYPE" => "aspro_next_content",
			"IBLOCK_ID" => CNextCache::$arIBlocks[SITE_ID]["aspro_next_content"]["aspro_next_services"][0],
			"NEWS_COUNT" => "20",
			"SORT_BY1" => "ACTIVE_FROM",
			"SORT_ORDER1" => "DESC",
			"SORT_BY2" => "SORT",
			"SORT_ORDER2" => "ASC",
			"FILTER_NAME" => "arrrFilter",
			// В макете заголовок блока — «Оказанные услуги» (Figma, фрейм
			// «Проект» 20524:98253). Прежний GetMessage('T_SERVICES') дал бы
			// «Услуги»: это сообщение общее для всего шаблона, менять его нельзя.
			"TITLE" => (strlen($arParams['T_SERVICES']) ? $arParams['T_SERVICES'] : 'Оказанные услуги'),
			"FIELD_CODE" => array(
				0 => "NAME",
				1 => "PREVIEW_TEXT",
				2 => "PREVIEW_PICTURE",
				3 => "",
			),
			"PROPERTY_CODE" => array(
				0 => "",
				1 => "",
			),
			"CHECK_DATES" => "Y",
			"DETAIL_URL" => "",
			"AJAX_MODE" => "N",
			"AJAX_OPTION_JUMP" => "N",
			"AJAX_OPTION_STYLE" => "Y",
			"AJAX_OPTION_HISTORY" => "N",
			"CACHE_TYPE" => "N",
			"CACHE_TIME" => "36000000",
			"CACHE_FILTER" => "N",
			"CACHE_GROUPS" => "N",
			"PREVIEW_TRUNCATE_LEN" => "",
			"ACTIVE_DATE_FORMAT" => "d.m.Y",
			"SET_TITLE" => "N",
			"SET_STATUS_404" => "Y",
			"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
			"ADD_SECTIONS_CHAIN" => "N",
			"HIDE_LINK_WHEN_NO_DETAIL" => "N",
			"PARENT_SECTION" => "",
			"PARENT_SECTION_CODE" => "",
			"INCLUDE_SUBSECTIONS" => "Y",
			"PAGER_TEMPLATE" => ".default",
			"DISPLAY_TOP_PAGER" => "N",
			"DISPLAY_BOTTOM_PAGER" => "Y",
			"PAGER_TITLE" => "Новости",
			"PAGER_SHOW_ALWAYS" => "N",
			"PAGER_DESC_NUMBERING" => "N",
			"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
			"PAGER_SHOW_ALL" => "N",
			"VIEW_TYPE" => "list",
			"SHOW_TABS" => "N",
			"SHOW_IMAGE" => "Y",
			"SHOW_NAME" => "Y",
			"SHOW_DETAIL" => "Y",
			"IMAGE_POSITION" => "top",
			"COUNT_IN_LINE" => "3",
			"GALLERY_TYPE" => $arParams["GALLERY_TYPE"],
			"AJAX_OPTION_ADDITIONAL" => ""
			),
		false, array("HIDE_ICONS" => "Y")
		);?>
	</div>
<?endif;?>

<?// «Также вас может заинтересовать» — область редактора EDITOR2, поднятая
   // сюда из шаблона news.detail: по макету блок идёт после услуг и во всю
   // ширину контейнера. Сетка карточек (news.list/list_projects_newdesign)
   // сама раскладывается по три в ряд, как в макете.?>
<?if($ndEditor2Below):?>
	<div class="nd-projinterest">
		<div class="editor">
		<?$APPLICATION->IncludeComponent(
			"sprint.editor:blocks",
			".default",
			array(
				"ELEMENT_ID" => $arElement["ID"],
				"IBLOCK_ID" => $arParams["IBLOCK_ID"],
				"PROPERTY_CODE" => "EDITOR2",
				"NEWS_NAME" => $arElement["NAME"],
				"USE_JQUERY" => "N",
				"USE_FANCYBOX" => "N",
			),
			false,
			array("HIDE_ICONS" => "Y")
		);?>
		</div>
	</div>
<?endif;?>

<?if(in_array('FORM_QUESTION', $arParams['DETAIL_PROPERTY_CODE']) && $arElement['PROPERTY_FORM_QUESTION_VALUE']):?>
	</div>
<?endif;?>