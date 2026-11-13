<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * Кнопки групп в «Распродаже»: Все · Террасная доска · Ступени · … (Ирина, 13.11.2026).
 * Подключает page_blocks/list_elements_1.php в разделе без фильтра ($ndNoFilter).
 *
 * Группа – свойство ND_SALE_GROUP (список, XML_ID = код в адресе ?group=). Ставят сверка с фидом
 * и tools/sale-feed/nd_sale_groups.php по названию; руками можно поправить в карточке товара.
 *
 * SEO: основная страница не меняется. Страница с ?group= – noindex, follow, canonical на раздел
 * без параметров ставит общий код. Ссылки кнопок – rel="nofollow", чтобы робот не ходил по ним.
 * Выбранная группа – тёмный чип (стили .tag_ank a.active), пустые группы не показываем.
 *
 * Вход: $arParams, $arResult (VARIABLES), $GLOBALS[$arParams['FILTER_NAME']].
 * Выход: чипы в $GLOBALS['ND_CATALOG_TAGS_HTML'] (печатает sort_newdesign.php) и фильтр по группе.
 */
\Bitrix\Main\Loader::includeModule('iblock');

$ndSgIblock = (int)$arParams['IBLOCK_ID'];
$ndSgSection = (int)($arResult['VARIABLES']['SECTION_ID'] ?? 0);
if (!$ndSgSection && !empty($arResult['VARIABLES']['SECTION_CODE'])) {
	$ndSgRow = CIBlockSection::GetList([], ['IBLOCK_ID' => $ndSgIblock, '=CODE' => $arResult['VARIABLES']['SECTION_CODE']], false, ['ID', 'SECTION_PAGE_URL'])->Fetch();
	$ndSgSection = (int)($ndSgRow['ID'] ?? 0);
}
$ndSgFilterName = (string)$arParams['FILTER_NAME'];
$ndSgBase = (array)($GLOBALS[$ndSgFilterName] ?? []);

// значения списка и число активных товаров в каждой группе; кеш 10 мин + тег ИБ (сброс при правке товаров)
$ndSgData = null;
$ndSgCache = \Bitrix\Main\Data\Cache::createInstance();
$ndSgCacheId = 'nd_sale_groups_' . $ndSgSection . '_' . md5(serialize($ndSgBase));
if ($ndSgCache->initCache(600, $ndSgCacheId, '/nd_sale_groups/')) {
	$ndSgData = $ndSgCache->getVars();
} elseif ($ndSgSection && $ndSgCache->startDataCache()) {
	$ndSgData = ['enums' => [], 'count' => [], 'all' => 0];
	$rs = CIBlockPropertyEnum::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], ['IBLOCK_ID' => $ndSgIblock, 'CODE' => 'ND_SALE_GROUP']);
	while ($e = $rs->Fetch()) {
		$ndSgData['enums'][$e['XML_ID']] = ['id' => (int)$e['ID'], 'name' => $e['VALUE']];
	}
	$ndSgFilter = array_merge($ndSgBase, ['IBLOCK_ID' => $ndSgIblock, 'SECTION_ID' => $ndSgSection,
		'INCLUDE_SUBSECTIONS' => 'Y', 'ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y']);
	if ($ndSgData['enums']) {
		$rs = CIBlockElement::GetList([], $ndSgFilter, ['PROPERTY_ND_SALE_GROUP']);
		while ($r = $rs->Fetch()) {
			$ndSgData['count'][(int)$r['PROPERTY_ND_SALE_GROUP_ENUM_ID']] = (int)$r['CNT'];
			$ndSgData['all'] += (int)$r['CNT'];
		}
	}
	$GLOBALS['CACHE_MANAGER']->StartTagCache('/nd_sale_groups/');
	$GLOBALS['CACHE_MANAGER']->RegisterTag('iblock_id_' . $ndSgIblock);
	$GLOBALS['CACHE_MANAGER']->EndTagCache();
	$ndSgCache->endDataCache($ndSgData);
}

if ($ndSgData && $ndSgData['enums']) {
	$ndSgCur = (string)($_GET['group'] ?? '');
	if (!isset($ndSgData['enums'][$ndSgCur])) {
		$ndSgCur = '';
	}
	$ndSgUrl = $APPLICATION->GetCurPage(false);
	$ndSgHtml = '<div class="section_tag_top nd-sale-groups nd-tags-open">';
	$ndSgChip = function ($href, $title, $cnt, $active) {
		return '<span class="tag_ank"><a href="' . htmlspecialcharsbx($href) . '"' . ($active ? ' class="active" aria-current="page"' : '')
			. ($href !== '' && strpos($href, '?') !== false ? ' rel="nofollow"' : '') . '>'
			. htmlspecialcharsbx($title) . ' <span class="nd-sale-groups__cnt">' . (int)$cnt . '</span></a></span>';
	};
	$ndSgHtml .= $ndSgChip($ndSgUrl, 'Все', $ndSgData['all'], $ndSgCur === '');
	foreach ($ndSgData['enums'] as $code => $e) {
		$cnt = (int)($ndSgData['count'][$e['id']] ?? 0);
		if ($cnt > 0 || $code === $ndSgCur) {
			$ndSgHtml .= $ndSgChip($ndSgUrl . '?group=' . rawurlencode($code), $e['name'], $cnt, $code === $ndSgCur);
		}
	}
	$ndSgHtml .= '</div>';
	// число в чипе – приглушённое; стили здесь: newdesign-catalog.css на проде отдаётся урезанной копией
	// nd-tags-open: групп мало, в один ряд с «…» (collapseTags в newdesign-catalog.js) не сворачиваем
	$ndSgHtml .= '<style>.nd-sale-groups .nd-sale-groups__cnt{opacity:.5;margin-left:2px}</style>';
	$GLOBALS['ND_CATALOG_TAGS_HTML'] = $ndSgHtml;

	if ($ndSgCur !== '') {
		$GLOBALS[$ndSgFilterName]['=PROPERTY_ND_SALE_GROUP'] = $ndSgData['enums'][$ndSgCur]['id'];
		$APPLICATION->AddHeadString('<meta name="robots" content="noindex, follow" />', true);
	}
}
