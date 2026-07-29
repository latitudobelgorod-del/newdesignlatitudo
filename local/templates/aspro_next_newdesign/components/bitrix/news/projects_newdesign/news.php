<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Страница «Портфолио» нового дизайна — список проектов.
 *
 * Копия асprovского шаблона `projects`: оригинал не трогаем, он остаётся
 * старому дизайну. Страница /projects/index.php выбирает имя шаблона
 * по SITE_TEMPLATE_ID, поэтому старый дизайн продолжает брать `projects`.
 *
 * Вся разметка — в шаблоне news.list `list_projects_newdesign`,
 * навигация — общая с отзывами (`pagination_newdesign`).
 */
$this->setFrameMode(true);

$arItemFilter = CNext::GetIBlockAllElementsFilter($arParams);
if ($arParams['CACHE_GROUPS'] == 'Y') {
	$arItemFilter['CHECK_PERMISSIONS'] = 'Y';
	$arItemFilter['GROUPS'] = $GLOBALS['USER']->GetGroups();
}

$itemsCnt = CNextCache::CIblockElement_GetList(
	['CACHE' => ['TAG' => CNextCache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]],
	$arItemFilter,
	[]
);

if (!$itemsCnt) {
	?><div class="alert alert-warning"><?= GetMessage('SECTION_EMPTY') ?></div><?
	return;
}

if ($arParams['USE_RSS'] !== 'N') {
	CNext::ShowRSSIcon($arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss']);
}

include __DIR__.'/page_blocks/list_elements_newdesign.php';
