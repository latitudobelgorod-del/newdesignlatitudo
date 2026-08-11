<? /** @var $block array */ ?><?
/**
 * Блок редактора «Элементы инфоблока», указывающий на портфолио.
 *
 * Вид — как в списке проектов на /projects/ (Ирина, 2026-08-11): та же сетка
 * три в ряд, картинка со скруглением, плашки «N фото» / «Видео» / «Отзыв» и
 * подпись под карточкой. Поэтому здесь не свой шаблон, а прямо
 * news.list/list_projects_newdesign: копия развела бы вёрстку карточки и её
 * стили с портфолио при первой же правке макета, а карточку обоим рисует одна
 * функция ndProjectCard() из include/parts/project_card.php.
 *
 * Постраничной навигации и кнопки «Показать ещё» тут нет и не появится:
 * пейджер шаблон печатает только при непустом $arResult['NAV_STRING'], а его
 * не будет при DISPLAY_BOTTOM_PAGER=N; кнопку «Показать ещё» js/newdesign-ui.js
 * вешает на обёртку навигации — без неё ей не за что зацепиться.
 *
 * SET_BRAND и VIDEO дописаны в PROPERTY_CODE: без них карточка не покажет
 * ярлык производителя и плашку «Видео». Прежний набор свойств от шаблонов
 * news-project-*-editor оставлен как был — он ничему не мешает.
 *
 * Настройка блока param1=style1 больше ни на что не влияет: она выбирала
 * шаблон news-project-service-editor вместо news-project-catalog-editor, а
 * теперь оформление одно. Обёртка .text-block-style1 убрана — стилей у неё
 * в проекте нет.
 */

global $APPLICATION;

$GLOBALS['sprintSearchFilter'] = [
	'ID' => $block['element_ids'],
];

$APPLICATION->IncludeComponent(
	'bitrix:news.list',
	'list_projects_newdesign',
	[
		'IBLOCK_TYPE' => 'aspro_next_content',
		'IBLOCK_ID' => $block['iblock_id'],
		'NEWS_COUNT' => '500',
		'FILTER_NAME' => 'sprintSearchFilter',
		'FIELD_CODE' => [
			'ID',
			'NAME',
			'PREVIEW_TEXT',
			'PREVIEW_PICTURE',
			'IBLOCK_ID',
		],
		'PROPERTY_CODE' => [
			'SET_BRAND',
			'VIDEO',
			'GALLEY_BIG',
			'REVIEW',
			'LINK_YOUTUBE',
			'FORM_CALCULATE',
			'PODPIS_OBWECT',
			'EDITOR1',
			'EDITOR2',
			'UF_EDITOR2',
			'UF_EDITOR1_BEL',
			'UF_EDITOR2_BEL',
		],
		'CHECK_DATES' => 'Y',
		'DETAIL_URL' => '',
		'AJAX_MODE' => 'N',
		'CACHE_TYPE' => 'N',
		'CACHE_TIME' => '36000000',
		'CACHE_FILTER' => 'Y',
		'CACHE_GROUPS' => 'N',
		'PREVIEW_TRUNCATE_LEN' => '',
		'ACTIVE_DATE_FORMAT' => 'j F Y',
		'SET_TITLE' => 'N',
		'SET_STATUS_404' => 'N',
		'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
		'ADD_SECTIONS_CHAIN' => 'N',
		'HIDE_LINK_WHEN_NO_DETAIL' => 'N',
		'PARENT_SECTION' => '',
		'PARENT_SECTION_CODE' => '',
		'INCLUDE_SUBSECTIONS' => 'Y',
		/* Своего пейджера у блока быть не должно (Ирина, 2026-08-11) —
		   параметрами компонента это не выключается, флаг понимает шаблон. */
		'ND_NO_PAGER' => 'Y',
		'PAGER_TEMPLATE' => '',
		'DISPLAY_TOP_PAGER' => 'N',
		'DISPLAY_BOTTOM_PAGER' => 'N',
		'PAGER_TITLE' => '',
		'PAGER_SHOW_ALWAYS' => 'N',
		'PAGER_DESC_NUMBERING' => 'N',
		'PAGER_SHOW_ALL' => 'N',
		'SET_BROWSER_TITLE' => 'N',
		'SET_META_KEYWORDS' => 'N',
		'SET_META_DESCRIPTION' => 'Y',
		'SET_LAST_MODIFIED' => 'N',
		'PAGER_BASE_LINK_ENABLE' => 'N',
		'SHOW_404' => 'N',
		'MESSAGE_404' => '',
	],
	false
);
?>
