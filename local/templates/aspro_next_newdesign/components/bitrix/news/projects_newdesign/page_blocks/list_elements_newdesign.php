<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Вывод списка проектов для нового дизайна.
 *
 * Рядом лежит асprovский list_elements_1.php — он не тронут, им пользуется
 * старый шаблон `projects`. Здесь свой вызов: шаблон карточек
 * `list_projects_newdesign` и навигация `pagination_newdesign`, та же,
 * что на странице отзывов.
 *
 * К списку свойств принудительно добавлены те, из которых собираются плашки
 * на карточке: ярлык производителя, признак видео, галерея (по её длине
 * считаем «N фото») и отзыв.
 */
$arNdProjectProps = array_values(array_unique(array_filter(array_merge(
	is_array($arParams['LIST_PROPERTY_CODE']) ? $arParams['LIST_PROPERTY_CODE'] : [],
	['SET_BRAND', 'VIDEO', 'GALLEY_BIG', 'REVIEW']
))));

$arNdProjectFields = array_values(array_unique(array_filter(array_merge(
	is_array($arParams['LIST_FIELD_CODE']) ? $arParams['LIST_FIELD_CODE'] : [],
	['ID', 'NAME', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']
))));

/* ======================= фильтр над сеткой =======================
   Состояние держим в GET: review=y, video=y, color[] (XML_ID справочника),
   brand[] (ID варианта списка). Собранный фильтр кладём в глобальную
   переменную с именем из FILTER_NAME — её читает news.list. */
$ndIb = (int) $arParams['IBLOCK_ID'];
$ndFilterName = $arParams['FILTER_NAME'] ?: 'arProjectFilter';

$ndReview = ($_GET['review'] ?? '') === 'y';
$ndVideo = ($_GET['video'] ?? '') === 'y';
$ndColor = array_values(array_filter(array_map('strval', (array) ($_GET['color'] ?? [])), 'strlen'));
$ndBrand = array_values(array_filter(array_map('intval', (array) ($_GET['brand'] ?? []))));

/* --- списки для выпадающих меню --- */
$ndColorOptions = [];
$ndBrandOptions = [];

if ($ndIb) {
	// цвет — свойство типа «справочник»: имя таблицы лежит в настройках свойства
	$rsProp = CIBlockProperty::GetList([], ['IBLOCK_ID' => $ndIb, 'CODE' => 'COLOR_IN_FILTER']);
	if ($prop = $rsProp->Fetch()) {
		$settings = $prop['USER_TYPE_SETTINGS'];
		if (is_string($settings)) {
			$settings = unserialize($settings, ['allowed_classes' => false]);
		}
		$table = $settings['TABLE_NAME'] ?? '';
		if ($table && preg_match('~^[a-z0-9_]+$~i', $table)) {
			global $DB;
			$rs = $DB->Query('SELECT UF_XML_ID, UF_NAME FROM '.$table.' ORDER BY UF_SORT, ID');
			while ($row = $rs->Fetch()) {
				if ($row['UF_XML_ID'] !== '') {
					$ndColorOptions[$row['UF_XML_ID']] = $row['UF_NAME'];
				}
			}
		}
	}

	$rsEnum = CIBlockPropertyEnum::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $ndIb, 'CODE' => 'SET_BRAND']);
	while ($enum = $rsEnum->Fetch()) {
		$ndBrandOptions[(int) $enum['ID']] = $enum['VALUE'];
	}
}

/* --- сам фильтр --- */
$ndFilter = [];
if ($ndReview) {
	$ndFilter['!PROPERTY_REVIEW'] = false;
}
if ($ndVideo) {
	$ndFilter['!PROPERTY_VIDEO'] = false;
}
if ($ndBrand) {
	$ndFilter['PROPERTY_SET_BRAND'] = $ndBrand;
}
if ($ndColor) {
	// COLOR_IN_FILTER множественное: фильтровать по нему напрямую нельзя —
	// выборка размножит элементы. Собираем ID и фильтруем по ним.
	$ids = [];
	$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => $ndIb, 'ACTIVE' => 'Y', 'PROPERTY_COLOR_IN_FILTER' => $ndColor], false, false, ['ID']);
	while ($r = $rs->Fetch()) {
		$ids[(int) $r['ID']] = true;
	}
	$ndFilter['ID'] = $ids ? array_keys($ids) : [-1];
}

$GLOBALS[$ndFilterName] = array_merge(
	is_array($GLOBALS[$ndFilterName] ?? null) ? $GLOBALS[$ndFilterName] : [],
	$ndFilter
);

$ndFilterState = [
	'review' => $ndReview,
	'video' => $ndVideo,
	'color' => $ndColor,
	'brand' => $ndBrand,
	'colorOptions' => $ndColorOptions,
	'brandOptions' => $ndBrandOptions,
	'active' => (bool) ($ndReview || $ndVideo || $ndColor || $ndBrand),
];

$APPLICATION->IncludeComponent(
	'bitrix:news.list',
	'list_projects_newdesign',
	[
		'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
		'IBLOCK_ID' => $arParams['IBLOCK_ID'],
		'NEWS_COUNT' => $arParams['NEWS_COUNT'],
		'ND_FILTER' => $ndFilterState,
		'SORT_BY1' => $arParams['SORT_BY1'],
		'SORT_ORDER1' => $arParams['SORT_ORDER1'],
		'SORT_BY2' => $arParams['SORT_BY2'],
		'SORT_ORDER2' => $arParams['SORT_ORDER2'],
		'FIELD_CODE' => $arNdProjectFields,
		'PROPERTY_CODE' => $arNdProjectProps,
		'FILTER_NAME' => $arParams['FILTER_NAME'],
		'AJAX_MODE' => 'N',
		'CACHE_TYPE' => $arParams['CACHE_TYPE'],
		'CACHE_TIME' => $arParams['CACHE_TIME'],
		'CACHE_FILTER' => 'Y',
		'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
		'PREVIEW_TRUNCATE_LEN' => $arParams['PREVIEW_TRUNCATE_LEN'],
		'ACTIVE_DATE_FORMAT' => $arParams['LIST_ACTIVE_DATE_FORMAT'],
		'SET_TITLE' => 'N',
		'SET_BROWSER_TITLE' => 'N',
		'SET_LAST_MODIFIED' => 'N',
		'SET_STATUS_404' => $arParams['SET_STATUS_404'],
		'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
		'ADD_SECTIONS_CHAIN' => 'N',
		'HIDE_LINK_WHEN_NO_DETAIL' => 'N',
		'CHECK_DATES' => $arParams['CHECK_DATES'],
		'PARENT_SECTION' => $arResult['VARIABLES']['SECTION_ID'],
		'PARENT_SECTION_CODE' => $arResult['VARIABLES']['SECTION_CODE'],
		'INCLUDE_SUBSECTIONS' => 'Y',
		'STRICT_SECTION_CHECK' => $arParams['STRICT_SECTION_CHECK'],
		'DISPLAY_TOP_PAGER' => 'N',
		'DISPLAY_BOTTOM_PAGER' => 'Y',
		'PAGER_TITLE' => $arParams['PAGER_TITLE'],
		'PAGER_TEMPLATE' => 'pagination_newdesign',
		'PAGER_SHOW_ALWAYS' => 'N',
		'PAGER_DESC_NUMBERING' => 'N',
		'PAGER_SHOW_ALL' => 'N',
		'PAGER_BASE_LINK_ENABLE' => 'N',
		'DISPLAY_DATE' => 'N',
		'DISPLAY_NAME' => 'Y',
		'DISPLAY_PICTURE' => 'Y',
		'DISPLAY_PREVIEW_TEXT' => 'N',
		'USE_PERMISSIONS' => $arParams['USE_PERMISSIONS'],
		'GROUP_PERMISSIONS' => $arParams['GROUP_PERMISSIONS'],
		'DETAIL_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['detail'],
		'SECTION_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['section'],
		'IBLOCK_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['news'],
		'COMPONENT_TEMPLATE' => 'list_projects_newdesign',
	],
	$component
);
