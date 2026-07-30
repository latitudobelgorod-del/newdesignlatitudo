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
   Состояние держим в GET: review=y, video=y, color[] и fence[] (XML_ID
   справочников), brand[] (ID варианта списка). Собранный фильтр кладём в
   глобальную переменную с именем из FILTER_NAME — её читает news.list. */
$ndIb = (int) $arParams['IBLOCK_ID'];
$ndFilterName = $arParams['FILTER_NAME'] ?: 'arProjectFilter';

$ndReview = ($_GET['review'] ?? '') === 'y';
$ndVideo = ($_GET['video'] ?? '') === 'y';
$ndColor = array_values(array_filter(array_map('strval', (array) ($_GET['color'] ?? [])), 'strlen'));
$ndFence = array_values(array_filter(array_map('strval', (array) ($_GET['fence'] ?? [])), 'strlen'));
$ndBrand = array_values(array_filter(array_map('intval', (array) ($_GET['brand'] ?? []))));

if (!function_exists('ndDirectoryOptions')) {
	/**
	 * Значения свойства типа «справочник» (highload-блок): имя таблицы лежит
	 * в настройках самого свойства.
	 *
	 * @param int    $iblockId
	 * @param string $code       код свойства
	 * @param bool   $withFile   тянуть ли UF_FILE (у справочника с картинками)
	 * @return array XML_ID => ['NAME' => ..., 'FILE' => id файла|0]
	 */
	function ndDirectoryOptions($iblockId, $code, $withFile = false)
	{
		$options = [];

		$rsProp = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code]);
		$prop = $rsProp->Fetch();
		if (!$prop) {
			return $options;
		}

		$settings = $prop['USER_TYPE_SETTINGS'];
		if (is_string($settings)) {
			$settings = unserialize($settings, ['allowed_classes' => false]);
		}
		$table = $settings['TABLE_NAME'] ?? '';
		if (!$table || !preg_match('~^[a-z0-9_]+$~i', $table)) {
			return $options;
		}

		global $DB;
		$rs = $DB->Query('SELECT UF_XML_ID, UF_NAME'.($withFile ? ', UF_FILE' : '').' FROM '.$table.' ORDER BY UF_SORT, ID');
		while ($row = $rs->Fetch()) {
			if ($row['UF_XML_ID'] !== '' && $row['UF_XML_ID'] !== null) {
				$options[$row['UF_XML_ID']] = [
					'NAME' => $row['UF_NAME'],
					'FILE' => $withFile ? (int) $row['UF_FILE'] : 0,
				];
			}
		}

		return $options;
	}
}

if (!function_exists('ndElementIdsByProperty')) {
	/**
	 * ID элементов, у которых множественное свойство содержит одно из значений.
	 * Фильтровать по такому свойству напрямую нельзя — выборка размножит элементы.
	 *
	 * @return int[]
	 */
	function ndElementIdsByProperty($iblockId, $code, array $values)
	{
		$ids = [];
		$rs = CIBlockElement::GetList(
			[],
			['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'PROPERTY_'.$code => $values],
			false,
			false,
			['ID']
		);
		while ($r = $rs->Fetch()) {
			$ids[(int) $r['ID']] = true;
		}

		return array_keys($ids);
	}
}

/* --- списки для выпадающих меню --- */
$ndColorOptions = [];
$ndFenceOptions = [];
$ndBrandOptions = [];

if ($ndIb) {
	$ndColorOptions = ndDirectoryOptions($ndIb, 'COLOR_IN_FILTER');
	$ndFenceOptions = ndDirectoryOptions($ndIb, 'VIDW_OGRAJDENIY', true);

	// картинки справочника видов ограждений — сразу в нужном размере
	foreach ($ndFenceOptions as $xmlId => $opt) {
		$ndFenceOptions[$xmlId]['SRC'] = '';
		if ($opt['FILE'] > 0) {
			$img = CFile::ResizeImageGet($opt['FILE'], ['width' => 64, 'height' => 64], BX_RESIZE_IMAGE_PROPORTIONAL, true);
			$ndFenceOptions[$xmlId]['SRC'] = $img['src'] ?? '';
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

/* Множественные справочники отбираем через ID; если выбраны оба фильтра,
   берём пересечение — элемент должен подходить и по цвету, и по виду. */
$ndIdSets = [];
if ($ndColor) {
	$ndIdSets[] = ndElementIdsByProperty($ndIb, 'COLOR_IN_FILTER', $ndColor);
}
if ($ndFence) {
	$ndIdSets[] = ndElementIdsByProperty($ndIb, 'VIDW_OGRAJDENIY', $ndFence);
}
if ($ndIdSets) {
	$ids = array_shift($ndIdSets);
	foreach ($ndIdSets as $set) {
		$ids = array_intersect($ids, $set);
	}
	$ndFilter['ID'] = $ids ? array_values($ids) : [-1];
}

$GLOBALS[$ndFilterName] = array_merge(
	is_array($GLOBALS[$ndFilterName] ?? null) ? $GLOBALS[$ndFilterName] : [],
	$ndFilter
);

$ndActive = (bool) ($ndReview || $ndVideo || $ndColor || $ndFence || $ndBrand);

/* ======================= разметка панели фильтров =======================
   Рисуем здесь, а не в шаблоне news.list: тот кешируется по составу фильтра,
   и отмеченные пункты могли бы приехать из чужого кеша. Оформление общее с
   фильтром отзывов (классы .nd-filter*, стили в css/newdesign.css).
   Раскрытие списков — на <details>, чтобы работало и без JS; скрипт лишь
   отправляет форму сразу после выбора. */
$ndSelColor = array_flip($ndColor);
$ndSelFence = array_flip($ndFence);
$ndSelBrand = array_flip($ndBrand);
$ndResetUrl = $APPLICATION->GetCurPage(false);

/** Кружки у названий цветов — из макета, по XML_ID справочника. */
$ndColorDots = [
	'i3EfDm7T' => '#ff9500', // Яркий
	'x020vRDl' => '#8b5a2b', // Коричневый
	'S67Rr1nK' => '#9a9a9a', // Серый
	'fYCKV9cP' => '#1a1a1a', // Черный
	'56F41bIv' => '#e8dcc8', // Светлый
	'sm1J8msp' => '#ffffff', // Белый
];

$ndChevron = '<svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
	.'<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>';

/* Общий скрипт фильтра и кнопки «Показать ещё». Подключаем тегом здесь:
   блок выводится, когда <head> уже отдан, и AddHeadScript туда не попадёт. */
if (!defined('ND_UI_JS')) {
	define('ND_UI_JS', true);
	$ndUi = SITE_TEMPLATE_PATH.'/js/newdesign-ui.js';
	$ndUiAbs = $_SERVER['DOCUMENT_ROOT'].$ndUi;
	?><script src="<?= $ndUi ?><?= is_file($ndUiAbs) ? '?'.filemtime($ndUiAbs) : '' ?>"></script><?
}
?>
<form class="nd-filter" method="get" action="<?= $ndResetUrl ?>">
	<label class="nd-filter__toggle">
		<input type="checkbox" name="review" value="y"<?= $ndReview ? ' checked' : '' ?>>
		<span class="nd-filter__switch" aria-hidden="true"></span>
		<span class="nd-filter__toggle-text">Есть отзыв</span>
	</label>

	<label class="nd-filter__toggle">
		<input type="checkbox" name="video" value="y"<?= $ndVideo ? ' checked' : '' ?>>
		<span class="nd-filter__switch" aria-hidden="true"></span>
		<span class="nd-filter__toggle-text">Есть видео</span>
	</label>

	<? if ($ndColorOptions): ?>
		<details class="nd-filter__drop">
			<summary class="nd-filter__head">
				<span>Цвет<?= $ndSelColor ? ' ('.count($ndSelColor).')' : '' ?></span><?= $ndChevron ?>
			</summary>
			<div class="nd-filter__panel">
				<? foreach ($ndColorOptions as $xmlId => $opt): ?>
					<label class="nd-filter__opt">
						<input type="checkbox" name="color[]" value="<?= htmlspecialcharsbx($xmlId) ?>"<?= isset($ndSelColor[$xmlId]) ? ' checked' : '' ?>>
						<span class="nd-filter__box" aria-hidden="true"></span>
						<? if (isset($ndColorDots[$xmlId])): ?>
							<span class="nd-filter__dot" style="background: <?= $ndColorDots[$xmlId] ?>" aria-hidden="true"></span>
						<? endif; ?>
						<span class="nd-filter__opt-name"><?= htmlspecialcharsbx($opt['NAME']) ?></span>
					</label>
				<? endforeach; ?>
				<button type="submit" class="nd-filter__apply">Применить</button>
			</div>
		</details>
	<? endif; ?>

	<? /* виды ограждений — справочник с картинками: у пункта миниатюра слева */ ?>
	<? if ($ndFenceOptions): ?>
		<details class="nd-filter__drop">
			<summary class="nd-filter__head">
				<span>Виды ограждений<?= $ndSelFence ? ' ('.count($ndSelFence).')' : '' ?></span><?= $ndChevron ?>
			</summary>
			<div class="nd-filter__panel nd-filter__panel--pics">
				<? foreach ($ndFenceOptions as $xmlId => $opt): ?>
					<label class="nd-filter__opt">
						<input type="checkbox" name="fence[]" value="<?= htmlspecialcharsbx($xmlId) ?>"<?= isset($ndSelFence[$xmlId]) ? ' checked' : '' ?>>
						<span class="nd-filter__box" aria-hidden="true"></span>
						<? if ($opt['SRC']): ?>
							<img class="nd-filter__pic" src="<?= htmlspecialcharsbx($opt['SRC']) ?>" alt="" width="32" height="32" loading="lazy">
						<? endif; ?>
						<span class="nd-filter__opt-name"><?= htmlspecialcharsbx($opt['NAME']) ?></span>
					</label>
				<? endforeach; ?>
				<button type="submit" class="nd-filter__apply">Применить</button>
			</div>
		</details>
	<? endif; ?>

	<? if ($ndBrandOptions): ?>
		<details class="nd-filter__drop">
			<summary class="nd-filter__head">
				<span>Бренд<?= $ndSelBrand ? ' ('.count($ndSelBrand).')' : '' ?></span><?= $ndChevron ?>
			</summary>
			<div class="nd-filter__panel">
				<? foreach ($ndBrandOptions as $enumId => $name): ?>
					<label class="nd-filter__opt">
						<input type="checkbox" name="brand[]" value="<?= (int) $enumId ?>"<?= isset($ndSelBrand[$enumId]) ? ' checked' : '' ?>>
						<span class="nd-filter__box" aria-hidden="true"></span>
						<span class="nd-filter__opt-name"><?= htmlspecialcharsbx($name) ?></span>
					</label>
				<? endforeach; ?>
				<button type="submit" class="nd-filter__apply">Применить</button>
			</div>
		</details>
	<? endif; ?>

	<? if ($ndActive): ?>
		<a class="nd-filter__reset" href="<?= $ndResetUrl ?>">Сбросить фильтры</a>
	<? endif; ?>
</form>
<?

$APPLICATION->IncludeComponent(
	'bitrix:news.list',
	'list_projects_newdesign',
	[
		'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
		'IBLOCK_ID' => $arParams['IBLOCK_ID'],
		'NEWS_COUNT' => $arParams['NEWS_COUNT'],
		// шаблону нужен только признак «фильтр включён» — для текста пустого списка
		'ND_FILTER' => ['active' => $ndActive],
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
