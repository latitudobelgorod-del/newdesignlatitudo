<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Блок «Что говорят о нас» на главной нового дизайна.
 *
 * Размеры из макета (Figma «Чистовик», фрейм 20464:369553): контейнер 1440
 * с полями 52, отступ шапки до карточек 36, заголовок 52/57 800, подзаголовок
 * 36/40 500 серым, кнопка «Смотреть все» справа по низу шапки,
 * три карточки по 429 с зазором 24.
 *
 * Карточка и попап — общие со страницей /company/reviews/
 * (include/parts/review_card.php), оттуда же берём стили и скрипт: подключаем
 * файлы соседнего шаблона явно, чтобы не держать две копии вёрстки.
 */
$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
	return;
}

require_once $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/parts/review_card.php';

/* Стили и скрипт карточки берём у шаблона страницы отзывов. Подключаем тегами
   прямо здесь, а не через SetAdditionalCSS/AddHeadScript: блок выводится из
   footer.php, когда <head> уже отдан браузеру, и в шапку они не попадут.
   К адресам добавляем метку времени файла, иначе браузер отдаст старую копию. */
$ndReviewsTpl = SITE_TEMPLATE_PATH.'/components/bitrix/news.list/list_reviews_newdesign';
$ndAsset = function ($rel) use ($ndReviewsTpl) {
	$path = $ndReviewsTpl.'/'.$rel;
	$abs = $_SERVER['DOCUMENT_ROOT'].$path;
	return $path.(is_file($abs) ? '?'.filemtime($abs) : '');
};
?>
<link rel="stylesheet" href="<?= $ndAsset('style.css') ?>">
<script src="<?= $ndAsset('script.js') ?>"></script>
<?

// названия городов для меток — одним запросом по тем регионам, что есть в выборке
$ndCityIds = [];
foreach ($arResult['ITEMS'] as $arItem) {
	$id = (int) ($arItem['PROPERTIES']['CITY_REVIEW']['VALUE'] ?? 0);
	if ($id) {
		$ndCityIds[$id] = true;
	}
}
$ndCityNames = [];
if ($ndCityIds && CModule::IncludeModule('iblock')) {
	$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => 7, 'ID' => array_keys($ndCityIds)], false, false, ['ID', 'NAME']);
	while ($r = $rs->Fetch()) {
		$ndCityNames[(int) $r['ID']] = $r['NAME'];
	}
}

$ndTitle = trim((string) $arParams['TITLE_BLOCK']) ?: 'Что говорят о нас';
$ndSubtitle = trim((string) $arParams['SUBTITLE_BLOCK']);
$ndAllUrl = trim((string) $arParams['ALL_URL']) ?: SITE_DIR.'company/reviews/';
?>
<section class="nd-mreviews">
	<?/* Плоская разметка — раскладку задаёт grid: на десктопе кнопка стоит справа
	   от заголовков, на мобильном уходит под список во всю ширину */?>
	<div class="nd-mreviews__titles">
		<h2 class="nd-mreviews__title"><?= htmlspecialcharsbx($ndTitle) ?></h2>
		<? if ($ndSubtitle): ?>
			<div class="nd-mreviews__subtitle"><?= htmlspecialcharsbx($ndSubtitle) ?></div>
		<? endif; ?>
	</div>

	<a class="nd-mreviews__all" href="<?= $ndAllUrl ?>">Смотреть все</a>

	<div class="nd-mreviews__list">
		<? foreach ($arResult['ITEMS'] as $arItem): ?>
			<?
			$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

			ndReviewCard($arItem, $ndCityNames, $this->GetEditAreaId($arItem['ID']));
			?>
		<? endforeach; ?>
	</div>
</section>
<? ndReviewModal(); ?>
