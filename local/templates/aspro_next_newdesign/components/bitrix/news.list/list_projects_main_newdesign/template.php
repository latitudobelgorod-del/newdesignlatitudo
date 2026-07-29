<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Блок «Вдохновитесь нашими проектами» на главной нового дизайна.
 *
 * Размеры и цвета из макета (Figma «Чистовик», фрейм 20463:368267):
 * тёмная подложка градиентом #31313c → #101014 во всю ширину экрана,
 * контент 1336 с полями 80/52, заголовок белый 52/57 800, справа «Смотреть все»,
 * под ними чипы разделов (#525264, радиус 4, высота 36), затем шесть карточек
 * в два ряда по три с зазором 24.
 *
 * Карточка общая со страницей /projects/ (include/parts/project_card.php),
 * оттуда же берём её стили: подключаем файл соседнего шаблона явно,
 * чтобы не держать две копии вёрстки.
 */
$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
	return;
}

require_once $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/parts/project_card.php';

/* Стили карточки живут в шаблоне страницы портфолио. Подключаем тегом здесь,
   а не через SetAdditionalCSS: блок выводится из footer.php, когда <head>
   уже отдан браузеру. Метка времени файла — против кэша браузера. */
if (!defined('ND_PROJECT_CARD_CSS')) {
	define('ND_PROJECT_CARD_CSS', true);
	$ndCardCss = SITE_TEMPLATE_PATH.'/components/bitrix/news.list/list_projects_newdesign/style.css';
	$ndCardCssAbs = $_SERVER['DOCUMENT_ROOT'].$ndCardCss;
	?><link rel="stylesheet" href="<?= $ndCardCss ?><?= is_file($ndCardCssAbs) ? '?'.filemtime($ndCardCssAbs) : '' ?>"><?
}

$ndTitle = trim((string) $arParams['TITLE_BLOCK']) ?: 'Вдохновитесь нашими проектами';
$ndAllUrl = trim((string) $arParams['ALL_URL']) ?: SITE_DIR.'projects/';

/* Чипы — разделы проектов. В макете их пять, поэтому берём первые
   по сортировке; сколько именно — задаётся параметром. */
$ndSections = [];
$ndSectionCount = (int) $arParams['SECTION_COUNT'] ?: 5;
if ((int) $arParams['IBLOCK_ID'] && CModule::IncludeModule('iblock')) {
	$rs = CIBlockSection::GetList(
		['SORT' => 'ASC', 'NAME' => 'ASC'],
		['IBLOCK_ID' => (int) $arParams['IBLOCK_ID'], 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'DEPTH_LEVEL' => 1],
		false,
		['ID', 'NAME', 'SECTION_PAGE_URL']
	);
	while (($sect = $rs->GetNext()) && count($ndSections) < $ndSectionCount) {
		$ndSections[] = $sect;
	}
}
?>
<section class="nd-mprojects">
	<div class="nd-mprojects__inner">
		<div class="nd-mprojects__head">
			<h2 class="nd-mprojects__title"><?= htmlspecialcharsbx($ndTitle) ?></h2>
			<a class="nd-mprojects__all" href="<?= $ndAllUrl ?>">Смотреть все</a>
		</div>

		<? if ($ndSections): ?>
			<div class="nd-mprojects__chips">
				<? foreach ($ndSections as $sect): ?>
					<a class="nd-mprojects__chip" href="<?= $sect['SECTION_PAGE_URL'] ?>"><?= htmlspecialcharsbx($sect['NAME']) ?></a>
				<? endforeach; ?>
			</div>
		<? endif; ?>

		<div class="nd-projects__list nd-mprojects__list">
			<? foreach ($arResult['ITEMS'] as $arItem): ?>
				<?
				$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

				ndProjectCard($arItem, $this->GetEditAreaId($arItem['ID']));
				?>
			<? endforeach; ?>
		</div>
	</div>
</section>
