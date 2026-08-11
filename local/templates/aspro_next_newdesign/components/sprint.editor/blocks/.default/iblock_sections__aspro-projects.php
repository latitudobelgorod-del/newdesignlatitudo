<? /** @var $block array */ ?><?
/**
 * Блок редактора «Разделы инфоблока», указывающий на портфолио.
 *
 * Вид — как плитки разделов на общей странице /projects/ (Ирина, 2026-08-11):
 * три в ряд, картинка 660×420 EXACT, подпись под ней. Разметку обоим даёт
 * ndSectionTile() из include/parts/section_tile.php, поэтому сетка не
 * разъедется; шаблон вывода — catalog.section.list/list_projects_sections_newdesign
 * (прежний subsections_list_3el_kont_manager рисовал карточки старой темы
 * с кнопкой «Смотреть»).
 *
 * PICTURE и DETAIL_PICTURE перечислены в SECTION_FIELDS явно: без них
 * компонент не отдаёт картинку и плитки выходят пустыми.
 */

global $APPLICATION;

global $sprintSearchFilter;
$sprintSearchFilter = [
	'=ID' => $block['section_ids'],
	'GLOBAL_ACTIVE' => 'Y',
];

$APPLICATION->IncludeComponent(
	'bitrix:catalog.section.list',
	'list_projects_sections_newdesign',
	[
		'IBLOCK_TYPE' => 'aspro_next_content',
		'IBLOCK_ID' => $block['iblock_id'],
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => '172800',
		'CACHE_FILTER' => 'Y',
		'CACHE_GROUPS' => 'N',
		'COUNT_ELEMENTS' => 'N',
		'FILTER_NAME' => 'sprintSearchFilter',
		'TOP_DEPTH' => '',
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
		'SECTION_ID' => $_REQUEST['SECTION_ID'],
		'SECTION_CODE' => '',
		'SECTION_FIELDS' => [
			'PICTURE',
			'DETAIL_PICTURE',
		],
		'SECTION_USER_FIELDS' => [
			'UF_LINK_REGION',
		],
		'COMPOSITE_FRAME_MODE' => 'A',
		'COMPOSITE_FRAME_TYPE' => 'AUTO',
	],
	false
);
?>
