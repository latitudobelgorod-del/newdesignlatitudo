<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Блок «Акции» на главной нового дизайна.
 *
 * По макету: контейнер 1440 с полями 52, заголовок 52/57 800, справа кнопка
 * «Смотреть все», под ними три карточки по 429 с зазором 24 — картинка
 * со скруглением 4 и подпись «Акция до ДД.ММ.ГГГГ» из даты окончания активности.
 *
 * На мобильном показываем две акции, кнопка уходит под них во всю ширину —
 * так же, как в блоках отзывов и статей.
 */
$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
	return;
}

$ndTitle = trim((string) $arParams['TITLE_BLOCK']) ?: 'Акции';
$ndAllUrl = trim((string) $arParams['ALL_URL']) ?: SITE_DIR.'sale/';

/* Даты окончания. news.list не всегда кладёт ACTIVE_TO в элемент, поэтому
   добираем их одним запросом по показанным ID — выборка внутри кэша компонента. */
$ndTill = [];
$ndIds = array_map(static fn($i) => (int) $i['ID'], $arResult['ITEMS']);
if ($ndIds && CModule::IncludeModule('iblock')) {
	$rs = CIBlockElement::GetList([], ['IBLOCK_ID' => (int) $arParams['IBLOCK_ID'], 'ID' => $ndIds], false, false, ['ID', 'ACTIVE_TO']);
	while ($row = $rs->Fetch()) {
		if (!empty($row['ACTIVE_TO'])) {
			$ndTill[(int) $row['ID']] = MakeTimeStamp($row['ACTIVE_TO']);
		}
	}
}
?>
<section class="nd-sales">
	<?/* Плоская разметка — раскладку задаёт grid: на десктопе кнопка стоит справа
	   от заголовка, на мобильном уходит под список во всю ширину */?>
	<h2 class="nd-sales__title"><?= htmlspecialcharsbx($ndTitle) ?></h2>

	<a class="nd-sales__all" href="<?= $ndAllUrl ?>">Смотреть все</a>

	<div class="nd-sales__list">
		<? foreach ($arResult['ITEMS'] as $arItem): ?>
			<?
			$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

			$pic = is_array($arItem['PREVIEW_PICTURE']) ? $arItem['PREVIEW_PICTURE'] : $arItem['DETAIL_PICTURE'];
			$src = '';
			if (is_array($pic) && $pic['ID']) {
				$img = CFile::ResizeImageGet($pic['ID'], ['width' => 858, 'height' => 574], BX_RESIZE_IMAGE_EXACT, true);
				$src = $img['src'] ?? $pic['SRC'];
			}

			// дата окончания акции — из срока активности элемента
			$ts = $ndTill[(int) $arItem['ID']] ?? (!empty($arItem['ACTIVE_TO']) ? MakeTimeStamp($arItem['ACTIVE_TO']) : 0);
			?>
			<a class="nd-sales__item" href="<?= $arItem['DETAIL_PAGE_URL'] ?>" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
				<span class="nd-sales__pic">
					<? if ($src): ?>
						<img src="<?= $src ?>" alt="<?= htmlspecialcharsbx($arItem['NAME']) ?>" loading="lazy">
					<? endif; ?>
				</span>
				<? if ($ts): ?>
					<span class="nd-sales__till">Акция до <?= FormatDate('d.m.Y', $ts) ?></span>
				<? else: ?>
					<span class="nd-sales__till"><?= htmlspecialcharsbx($arItem['NAME']) ?></span>
				<? endif; ?>
			</a>
		<? endforeach; ?>
	</div>
</section>
