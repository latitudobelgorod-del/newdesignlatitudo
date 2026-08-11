<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>
<?
// geting section items count and section [ID, NAME]
$arItemFilter = CNext::GetCurrentSectionElementFilter($arResult["VARIABLES"], $arParams);
$arSectionFilter = CNext::GetCurrentSectionFilter($arResult["VARIABLES"], $arParams);

if($arParams['CACHE_GROUPS'] == 'Y')
{
	$arSectionFilter['CHECK_PERMISSIONS'] = 'Y';
	$arSectionFilter['GROUPS'] = $GLOBALS["USER"]->GetGroups();
}

// SECTION_PAGE_URL нужен новому дизайну: по нему отличаем канонический адрес раздела
$arSection = CNextCache::CIblockSection_GetList(array("CACHE" => array("TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]), "MULTI" => "N")), $arSectionFilter, false, array('ID', 'NAME', 'DESCRIPTION', 'PICTURE', 'DETAIL_PICTURE', 'SECTION_PAGE_URL'), true);
CNext::AddMeta(
	array(
		'og:description' => $arSection['DESCRIPTION'],
		'og:image' => (($arSection['PICTURE'] || $arSection['DETAIL_PICTURE']) ? CFile::GetPath(($arSection['PICTURE'] ? $arSection['PICTURE'] : $arSection['DETAIL_PICTURE'])) : false),
	)
);

$bFoundSection = false;
$arYears = array();

if($arSection)
{
	$bFoundSection = true;
	$itemsCnt = CNextCache::CIblockElement_GetList(array("CACHE" => array("TAG" => CNextCache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), $arItemFilter, array());
}

global $arTheme;
if($arTheme['PROJECTS_PAGE']['VALUE'] == 'list_elements_3' || $arParams["SECTION_ELEMENTS_TYPE_VIEW"] == 'list_elements_3')
{
	$arYears = CNext::GetItemsYear($arParams);
	if($arYears)
	{
		$current_year = current($arResult['VARIABLES']);
		if($current_year && $arYears[$current_year])
		{
			$bFoundSection = true;
			$GLOBALS[$arParams["FILTER_NAME"]] = array(
				">DATE_ACTIVE_FROM" => ConvertDateTime("01.01.".$current_year, "DD.MM.YYYY"),
				"<=DATE_ACTIVE_FROM" => ConvertDateTime("01.01.".(intval($current_year)+1), "DD.MM.YYYY"),
			);
			$title_news = GetMessage('CURRENT_PROJECTS', array('#YEAR#' => $current_year));
		}
		$itemsCnt = 1;
	}
}?>

<?if(!$bFoundSection && $arParams['SET_STATUS_404'] !== 'Y'):?>
	<div class="alert alert-warning"><?=GetMessage("SECTION_NOTFOUND")?></div>
<?elseif(!$bFoundSection && $arParams['SET_STATUS_404'] === 'Y'):?>
	<?CNext::goto404Page();?>
<?else:?>

	<?// rss
	if($arParams['USE_RSS'] !== 'N'){
		CNext::ShowRSSIcon(CComponentEngine::makePathFromTemplate($arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss_section'], array_map('urlencode', $arResult['VARIABLES'])));
	}?>
	<?if(!$itemsCnt):?>
		<div class="alert alert-warning"><?=GetMessage("SECTION_EMPTY")?></div>
	<?endif;?>

	<?
	/* Новый дизайн рисует раздел тем же блоком, что и общую страницу портфолио:
	   фильтры, плашки разделов, SEO-текст, сетка карточек. Параметр
	   SECTION_ELEMENTS_TYPE_VIEW (list_elements_1) остаётся старому дизайну —
	   у него свой шаблон `projects`.

	   Заголовок раздела в новом дизайне никто не выводит: header.php печатает
	   <h1> только для блога. Берём SEO-заголовок раздела, как это делал старый
	   шаблон news-project-catalog1, и ставим его сами. */
	$ndIb = (int) $arParams['IBLOCK_ID'];
	$ndSectionId = (int) $arSection['ID'];

	$ndSectionTitle = '';
	if ($ndSectionId) {
		$ndIprop = new Bitrix\Iblock\InheritedProperty\SectionValues($ndIb, $ndSectionId);
		$ndIprop = $ndIprop->getValues();

		$ndSectionTitle = trim((string) ($ndIprop['SECTION_PAGE_TITLE'] ?? ''));

		/* Мету раздела в старом дизайне ставил сам компонент (SET_TITLE=Y).
		   Здесь news.list вызывается с SET_TITLE=N, поэтому проставляем сами —
		   иначе на разделе останутся title и description общей страницы. */
		if ($ndSectionTitle) {
			$APPLICATION->SetTitle($ndSectionTitle);
		}
		foreach (['SECTION_META_TITLE' => 'title', 'SECTION_META_DESCRIPTION' => 'description', 'SECTION_META_KEYWORDS' => 'keywords'] as $ndIpropKey => $ndProp) {
			$ndValue = trim((string) ($ndIprop[$ndIpropKey] ?? ''));
			if ($ndValue) {
				$APPLICATION->SetPageProperty($ndProp, $ndValue);
			}
		}
	}
	if (!$ndSectionTitle) {
		$ndSectionTitle = (string) $APPLICATION->GetTitle(false);
	}

	/* Страницы пагинации и «чужие» адреса раздела из индекса убираем —
	   логика перенесена из шаблона news-project-catalog1. */
	$ndCurPage = (int) ($_REQUEST['PAGEN_1'] ?? 0);
	if ($ndCurPage > 1) {
		$APPLICATION->AddHeadString('<meta name="yandex" content="noindex, follow" />', true);
	} elseif ($arSection && $_SERVER['REQUEST_URI'] !== $arSection['SECTION_PAGE_URL']) {
		$APPLICATION->SetPageProperty('robots', 'noindex, nofollow');
	}
	?>
	<? if ($ndSectionTitle): ?>
		<h1 id="pagetitle" class="nd-projects__h1"><?= $ndSectionTitle ?></h1>
	<? endif; ?>
	<? include __DIR__.'/page_blocks/list_elements_newdesign.php'; ?>
<?endif;?>
<?if($arYears && $bFoundSection)
{			
	$APPLICATION->SetTitle($title_news);
	$APPLICATION->AddChainItem($title_news);
	
	
}?>





<?/* Прибитая снизу синяя полоса `.k_det` с кнопкой (была у /projects/zabory/ и
	   /projects/ulichnye-ograzhdeniya/) в новом дизайне убрана целиком — Ирина,
	   2026-08-11. Вместе с ней ушёл и её костыль `#footer{margin-bottom:60px}`.
	   Оформление самой полосы осталось в css/custom.css: она ещё рисуется
	   старым дизайном из своих шаблонов, их не трогаем.
	   На /projects/ulichnye-ograzhdeniya/ полоса дублировала кнопку блока
	   редактора «Заказать расчет ограждений», а на /projects/zabory/ вела в
	   каталог заборной доски — этой ссылки на странице теперь нет вовсе
	   (кнопка редактора там другая, «Рассчитать забор»). */?>
		