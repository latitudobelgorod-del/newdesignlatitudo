<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Блок «Акции» — три акции из инфоблока 17.
 *
 * Подпись под картинкой собирается из даты окончания активности элемента,
 * поэтому в поля обязательно входит DATE_ACTIVE_TO.
 *
 * Отбор — просто первые по сортировке. У инфоблока есть свойство
 * SHOW_ON_INDEX_PAGE «Показывать на главной», но отмечена им сейчас одна акция,
 * поэтому фильтр по нему не включаем — иначе на главной будет одна карточка.
 *
 * Разметка и стили — в шаблоне компонента list_sales_main_newdesign.
 */
if (!CModule::IncludeModule('iblock')) {
	return;
}

/* Акции привязаны к регионам свойством LINK_REGION «Показывать акцию только
   в этих регионах». Показываем те, что относятся к текущему региону, плюс
   акции без привязки — они общие для всех. Если регион не определился,
   фильтр не ставим, иначе главная останется без блока. */
$GLOBALS['arNdSalesFilter'] = [];
if (class_exists('CNextRegionality')) {
	$ndRegion = CNextRegionality::getCurrentRegion();
	$ndRegionId = is_array($ndRegion) ? (int) ($ndRegion['ID'] ?? 0) : 0;
	if ($ndRegionId) {
		// ИЛИ обязательно вкладывать подгруппой: на верхнем уровне оно
		// распространится на весь фильтр компонента, включая IBLOCK_ID,
		// и в выборку полезут элементы других инфоблоков
		$GLOBALS['arNdSalesFilter'] = [
			[
				'LOGIC' => 'OR',
				['PROPERTY_LINK_REGION' => $ndRegionId],
				['PROPERTY_LINK_REGION' => false],
			],
		];
	}
}

$APPLICATION->IncludeComponent(
	'bitrix:news.list',
	'list_sales_main_newdesign',
	[
		'IBLOCK_TYPE' => 'aspro_next_content',
		'IBLOCK_ID' => '17',
		'NEWS_COUNT' => '3',
		'SORT_BY1' => 'SORT',
		'SORT_ORDER1' => 'ASC',
		'SORT_BY2' => 'ID',
		'SORT_ORDER2' => 'DESC',
		'FIELD_CODE' => ['ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'DATE_ACTIVE_TO'],
		'PROPERTY_CODE' => [],
		'FILTER_NAME' => 'arNdSalesFilter',
		'CHECK_DATES' => 'Y',
		// адрес детальной берём как в настройках инфоблока, иначе ссылка пустая
		'DETAIL_URL' => SITE_DIR.'sale/#ELEMENT_CODE#/',
		'AJAX_MODE' => 'N',
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => '36000000',
		// фильтр по региону обязан попадать в ключ кэша, иначе все регионы
		// получат выборку того, кто зашёл первым
		'CACHE_FILTER' => 'Y',
		'CACHE_GROUPS' => 'N',
		'PREVIEW_TRUNCATE_LEN' => '',
		'ACTIVE_DATE_FORMAT' => 'd.m.Y',
		'SET_TITLE' => 'N',
		'SET_BROWSER_TITLE' => 'N',
		'SET_META_KEYWORDS' => 'N',
		'SET_META_DESCRIPTION' => 'N',
		'SET_LAST_MODIFIED' => 'N',
		'SET_STATUS_404' => 'N',
		'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
		'ADD_SECTIONS_CHAIN' => 'N',
		'HIDE_LINK_WHEN_NO_DETAIL' => 'N',
		'PARENT_SECTION' => '',
		'PARENT_SECTION_CODE' => '',
		'INCLUDE_SUBSECTIONS' => 'Y',
		'DISPLAY_TOP_PAGER' => 'N',
		'DISPLAY_BOTTOM_PAGER' => 'N',
		'PAGER_TEMPLATE' => '',
		'PAGER_SHOW_ALWAYS' => 'N',
		'PAGER_DESC_NUMBERING' => 'N',
		'PAGER_SHOW_ALL' => 'N',
		'DISPLAY_DATE' => 'N',
		'DISPLAY_NAME' => 'Y',
		'DISPLAY_PICTURE' => 'Y',
		'DISPLAY_PREVIEW_TEXT' => 'N',
		'TITLE_BLOCK' => 'Акции',
		'ALL_URL' => SITE_DIR.'sale/',
		'COMPONENT_TEMPLATE' => 'list_sales_main_newdesign',
	],
	false,
	['HIDE_ICONS' => 'Y']
);
