<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Плитки акций нового дизайна. Копия асprovского `akciya_two` с новой
 * разметкой: картинка акции и поверх неё, в правом нижнем углу, срок действия.
 *
 * Пропорции картинок у акций разные (от 4:3 до 2:1), и на макете плитки в ряду
 * тоже разной высоты — поэтому картинку не режем, высота у неё своя.
 *
 * @var array $arResult
 * @var array $arParams
 * @var string $templateName
 * @var CBitrixComponent $component
 * @var CMain $APPLICATION
 */
$this->setFrameMode(true);

// noindex на страницах пагинации и прочих адресах — как в старом шаблоне
if ($_SERVER['REQUEST_URI'] !== '/sale/') {
	$APPLICATION->SetPageProperty('robots', 'noindex, nofollow');
}

$ndIsAjax = (isset($_GET['AJAX_REQUEST']) && $_GET['AJAX_REQUEST'] === 'Y');
$ndNow = time();

if (!$arResult['ITEMS']) {
	return;
}

/** Срок действия акции: «до 30 апреля 2026» либо «Акция завершена». */
$ndSaleDate = static function (array $item) use ($ndNow) {
	if (!$item['DATE_ACTIVE_TO']) {
		return '';
	}

	$till = MakeTimeStamp($item['DATE_ACTIVE_TO'], 'DD.MM.YYYY HH:MI:SS');
	if (!$till) {
		return '';
	}
	if ($till <= $ndNow) {
		return 'Акция завершена';
	}

	// FormatDate, а не ConvertDateTime: у второго свой набор токенов
	// (DD.MM.YYYY), и «j F Y» он отдаёт как есть
	return 'до '.FormatDate('j F Y', $till);
};
?>
<? if (!$ndIsAjax): ?><div class="nd-sale__list"><? endif; ?>

	<? foreach ($arResult['ITEMS'] as $arItem): ?>
		<?
		$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
		$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), ['CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')]);

		$bImage = !empty($arItem['PREVIEW_PICTURE']['SRC']);
		$imageSrc = $bImage ? $arItem['PREVIEW_PICTURE']['SRC'] : SITE_TEMPLATE_PATH.'/images/noimage.png';
		$ndAlt = $bImage && $arItem['PREVIEW_PICTURE']['ALT'] ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'];
		$ndTitle = $bImage && $arItem['PREVIEW_PICTURE']['TITLE'] ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'];
		$ndDate = $ndSaleDate($arItem);
		?>
		<a class="nd-sale__item" href="<?= htmlspecialcharsbx($arItem['DETAIL_PAGE_URL']) ?>" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
			<img
				class="nd-sale__pic"
				src="<?= htmlspecialcharsbx($imageSrc) ?>"
				alt="<?= htmlspecialcharsbx($ndAlt) ?>"
				title="<?= htmlspecialcharsbx($ndTitle) ?>"
				loading="lazy"
			>
			<? if ($ndDate): ?>
				<span class="nd-sale__date"><?= htmlspecialcharsbx($ndDate) ?></span>
			<? endif; ?>
		</a>
	<? endforeach; ?>

<? if (!$ndIsAjax): ?></div><? endif; ?>

<? if ($arParams['DISPLAY_BOTTOM_PAGER'] && $arResult['NAV_STRING']): ?>
	<div class="nd-sale__nav"<?= $ndIsAjax ? ' style="display:none"' : '' ?>><?= $arResult['NAV_STRING'] ?></div>
<? endif; ?>
