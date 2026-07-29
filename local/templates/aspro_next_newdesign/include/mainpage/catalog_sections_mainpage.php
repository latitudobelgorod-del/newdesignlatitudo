<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Главная нового дизайна. Блок «Категории товаров» — разделы каталога,
 * у которых пользовательское поле UF_SHOW_ON_MAINPAGE выставлено в «Да».
 *
 * Компонент фильтровать по UF не умеет, поэтому берём все разделы первого
 * уровня, а отбор делает шаблон list_sections_main_newdesign. Разделов
 * немного, выборка кэшируется.
 *
 * Картинка карточки — из UF_IMAGE_SECTION_MAIN; если её не залили,
 * шаблон рисует цветную заглушку.
 */
if (!CModule::IncludeModule('iblock')) {
	return;
}

$APPLICATION->IncludeComponent(
	'bitrix:catalog.section.list',
	'list_sections_main_newdesign',
	[
		'IBLOCK_TYPE' => 'aspro_next_catalog',
		'IBLOCK_ID' => '19',
		'SECTION_ID' => '',
		'SECTION_CODE' => '',
		'COUNT_ELEMENTS' => 'N',
		'TOP_DEPTH' => '1',
		'SECTION_FIELDS' => ['ID', 'NAME', 'SECTION_PAGE_URL', 'PICTURE'],
		'SECTION_USER_FIELDS' => ['UF_SHOW_ON_MAINPAGE', 'UF_IMAGE_SECTION_MAIN'],
		'SECTION_URL' => '',
		'ADD_SECTIONS_CHAIN' => 'N',
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => '36000000',
		'CACHE_GROUPS' => 'N',
		'TITLE_BLOCK' => 'Категории товаров',
		'COMPONENT_TEMPLATE' => 'list_sections_main_newdesign',
	],
	false,
	['HIDE_ICONS' => 'Y']
);
