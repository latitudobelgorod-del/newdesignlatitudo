<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Страница «Статьи» (/materials/) нового дизайна — сетка карточек.
 *
 * По макету: контент 1336, четыре карточки в ряд по 316 с зазором 24,
 * картинка со скруглением 4 и плашкой «Статья» в левом нижнем углу,
 * заголовок под ней. Навигация — общий `pagination_newdesign`,
 * компонент отдаёт её в $arResult['NAV_STRING'].
 *
 * Над сеткой — ссылки на разделы инфоблока. У «Материалов» разделов сейчас нет,
 * поэтому ряд не выводится; появятся — покажется сам.
 */
$this->setFrameMode(true);

$ndBadge = trim((string) $arParams['BADGE_TEXT']) ?: 'Статья';

/* Скрипт кнопки «Показать ещё» — тот же, что на отзывах и портфолио.
   Подключаем тегом здесь: компонент выводится, когда <head> уже отдан. */
if (!defined('ND_UI_JS')) {
	define('ND_UI_JS', true);
	$ndUi = SITE_TEMPLATE_PATH.'/js/newdesign-ui.js';
	$ndUiAbs = $_SERVER['DOCUMENT_ROOT'].$ndUi;
	?><script src="<?= $ndUi ?><?= is_file($ndUiAbs) ? '?'.filemtime($ndUiAbs) : '' ?>"></script><?
}

$ndSections = [];
if ((int) $arParams['IBLOCK_ID'] && CModule::IncludeModule('iblock')) {
	$rs = CIBlockSection::GetList(
		['SORT' => 'ASC', 'NAME' => 'ASC'],
		['IBLOCK_ID' => (int) $arParams['IBLOCK_ID'], 'ACTIVE' => 'Y', 'GLOBAL_ACTIVE' => 'Y', 'DEPTH_LEVEL' => 1],
		false,
		['ID', 'NAME', 'SECTION_PAGE_URL']
	);
	while ($sect = $rs->GetNext()) {
		$ndSections[] = $sect;
	}
}
?>
<section class="nd-mat">
	<? if ($ndSections): ?>
		<div class="nd-mat__sections">
			<? foreach ($ndSections as $sect): ?>
				<a class="nd-mat__section" href="<?= $sect['SECTION_PAGE_URL'] ?>"><?= htmlspecialcharsbx($sect['NAME']) ?></a>
			<? endforeach; ?>
		</div>
	<? endif; ?>

	<? if (!$arResult['ITEMS']): ?>
		<p class="nd-mat__empty">Статей пока нет.</p>
	<? else: ?>
		<div class="nd-mat__list">
			<? foreach ($arResult['ITEMS'] as $arItem): ?>
				<?
				$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

				$pic = is_array($arItem['PREVIEW_PICTURE']) ? $arItem['PREVIEW_PICTURE'] : $arItem['DETAIL_PICTURE'];
				$src = '';
				if (is_array($pic) && $pic['ID']) {
					$img = CFile::ResizeImageGet($pic['ID'], ['width' => 632, 'height' => 422], BX_RESIZE_IMAGE_EXACT, true);
					$src = $img['src'] ?? $pic['SRC'];
				}
				?>
				<a class="nd-mat__item" href="<?= $arItem['DETAIL_PAGE_URL'] ?>" id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
					<span class="nd-mat__pic">
						<? if ($src): ?>
							<img class="nd-mat__img" src="<?= $src ?>" alt="<?= htmlspecialcharsbx($arItem['NAME']) ?>" loading="lazy">
						<? endif; ?>
						<span class="nd-mat__badge"><?= htmlspecialcharsbx($ndBadge) ?></span>
					</span>
					<span class="nd-mat__name"><?= htmlspecialcharsbx($arItem['NAME']) ?></span>
				</a>
			<? endforeach; ?>
		</div>
	<? endif; ?>

	<? if ($arResult['NAV_STRING']): ?>
		<div class="nd-mat__nav"><?= $arResult['NAV_STRING'] ?></div>
	<? endif; ?>
</section>
