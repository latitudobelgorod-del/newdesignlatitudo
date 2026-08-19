<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Блок «Категории товаров» на главной нового дизайна.
 *
 * Размеры из макета (Figma «Чистовик», фрейм 20463:355525): контейнер 1440
 * с полями 52, отступ заголовка до сетки 36, заголовок 52/57 800,
 * четыре карточки по 316 с зазором 24, картинка 316×316 радиус 4,
 * подпись под ней 22/26 700.
 *
 * Отбор — по пользовательскому полю раздела UF_SHOW_ON_MAINPAGE («Да»).
 * Компонент фильтровать по UF не умеет, поэтому он отдаёт все разделы
 * первого уровня, а отбор делаем здесь: разделов немного, выборка кэшируется.
 *
 * Картинка берётся из UF_IMAGE_SECTION_MAIN. Если её не залили — вместо неё
 * цветная заглушка из палитры макета, чтобы сетка не разъезжалась.
 */
$this->setFrameMode(true);

$ndItems = [];
foreach ($arResult['SECTIONS'] as $arSection) {
	if (empty($arSection['UF_SHOW_ON_MAINPAGE'])) {
		continue;
	}
	$ndItems[] = $arSection;
}

if (!$ndItems) {
	return;
}

/** Цвета заглушек — из макета, идут по кругу по порядку карточек. */
$ndStubColors = ['#5856d6', '#003cff', '#af52de', '#ff9500'];
?>
<section class="nd-cats">
	<h2 class="nd-cats__title"><?= htmlspecialcharsbx(trim((string) $arParams['TITLE_BLOCK']) ?: 'Категории товаров') ?></h2>

	<div class="nd-cats__list">
		<? foreach ($ndItems as $i => $arSection): ?>
			<?
			$this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection['IBLOCK_ID'], 'SECTION_EDIT'));

			$src = '';
			$fileId = (int) ($arSection['UF_IMAGE_SECTION_MAIN'] ?? 0);
			if ($fileId) {
				/* 470×470 — полтора размера плитки (312×312). 632×632 было больше
				   исходников, ресайз их не трогал и отдавал оригинал. Качество 82
				   седьмым параметром: в настройках модуля стоит 100. */
				$img = CFile::ResizeImageGet($fileId, ['width' => 470, 'height' => 470], BX_RESIZE_IMAGE_EXACT, true, false, false, 82);
				$src = $img['src'] ?? CFile::GetPath($fileId);
			}
			$stub = $ndStubColors[$i % count($ndStubColors)];
			?>
			<a class="nd-cats__item" href="<?= $arSection['SECTION_PAGE_URL'] ?>" id="<?= $this->GetEditAreaId($arSection['ID']) ?>">
				<span class="nd-cats__pic"<?= $src ? '' : ' style="background: '.$stub.'"' ?>>
					<? if ($src): ?>
						<img src="<?= $src ?>" alt="<?= htmlspecialcharsbx($arSection['NAME']) ?>" loading="lazy">
					<? endif; ?>
				</span>
				<span class="nd-cats__name"><?= htmlspecialcharsbx($arSection['NAME']) ?></span>
			</a>
		<? endforeach; ?>
	</div>
</section>
