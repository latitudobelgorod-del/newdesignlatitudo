<? /** @var $block array */ ?><?
/**
 * Блок редактора «Разделы инфоблока», указывающий на каталог.
 *
 * Вид — тот же ряд подразделов, что идёт над списком товаров на странице
 * раздела каталога (Ирина, 2026-08-11, скриншот): карточка на светло-сером
 * фоне, картинка сверху, название мелким текстом снизу; у раздела без
 * картинки название стоит по центру карточки (модификатор
 * .nd-subsec__item--noimg добавляет сам шаблон).
 *
 * Шаблон общий со страницей раздела —
 * catalog.section.list/subsections_list_dop_razd_newdesign, копии нет.
 * Сетка `.nd-subsec` самодостаточна (grid auto-fill от 176px) и живёт в
 * глобальном css/newdesign.css, поэтому обёртка `.nd-subsec-row` со страницы
 * раздела здесь не нужна: она существует только чтобы поставить рядом баннер
 * акции. Прежний шаблон editor_list_catalog_2025 рисовал карточки старой темы.
 *
 * PICTURE и DETAIL_PICTURE перечислены в SECTION_FIELDS явно — без них
 * компонент не отдаёт картинку и плитки выходят без фото.
 *
 * TOP_DEPTH=10, а не 1: со значением 1 компонент режет выборку по глубине и
 * выбранные подразделы (DEPTH_LEVEL 2 и глубже) не выводились вовсе. Но при
 * TOP_DEPTH > 1 result_modifier шаблона начинает собирать дерево и раздел без
 * родителя в выборке превращается в пустую заготовку — отсюда флаг ND_FLAT=Y,
 * он это отключает. Страница раздела каталога передаёт TOP_DEPTH=1 и обоих
 * изменений не замечает.
 */

global $APPLICATION;

global $sprintSearchFilter;
$sprintSearchFilter = [
	'=ID' => $block['section_ids'],
	'GLOBAL_ACTIVE' => 'Y',
];

$APPLICATION->IncludeComponent(
	'bitrix:catalog.section.list',
	'subsections_list_dop_razd_newdesign',
	[
		'IBLOCK_TYPE' => 'aspro_next_catalog',
		'IBLOCK_ID' => $block['iblock_id'],
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => '172800',
		'CACHE_FILTER' => 'Y',
		'CACHE_GROUPS' => 'N',
		'COUNT_ELEMENTS' => 'N',
		'FILTER_NAME' => 'sprintSearchFilter',
		'TOP_DEPTH' => '10',
		// см. result_modifier шаблона: не собирать дерево, выводить как пришло
		'ND_FLAT' => 'Y',
		'SECTION_URL' => '',
		'VIEW_MODE' => '',
		'SHOW_PARENT_NAME' => 'N',
		'HIDE_SECTION_NAME' => 'N',
		'ADD_SECTIONS_CHAIN' => 'N',
		'SHOW_SECTIONS_LIST_PREVIEW' => 'N',
		'SECTIONS_LIST_PREVIEW_PROPERTY' => 'N',
		'SECTIONS_LIST_PREVIEW_DESCRIPTION' => 'N',
		'SHOW_SECTION_LIST_PICTURES' => 'Y',
		'DISPLAY_PANEL' => 'N',
		'SECTION_ID' => '',
		'SECTION_CODE' => '',
		'SECTION_FIELDS' => [
			'PICTURE',
			'DETAIL_PICTURE',
		],
		'SECTION_USER_FIELDS' => [],
		'COMPOSITE_FRAME_MODE' => 'A',
		'COMPOSITE_FRAME_TYPE' => 'AUTO',
	],
	false,
	['HIDE_ICONS' => 'Y']
);
?>
