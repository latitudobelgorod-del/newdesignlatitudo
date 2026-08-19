<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Блок «Полезно знать» на главной нового дизайна — статьи инфоблока «Материалы».
 *
 * Отбор идёт по свойству SHOW_ON_MAINPAGE («да» / «нет», по умолчанию «нет») —
 * фильтр собирается в include/mainpage/articles_mainpage.php шаблона.
 * Если отмеченных статей нет, блок не выводится совсем — вместе с заголовком
 * и кнопкой, чтобы на главной не оставалась пустая шапка.
 *
 * Стили лежат рядом (style.css) — Битрикс подключает их сам.
 */
$this->setFrameMode(true);

if (!$arResult['ITEMS']) {
    return;
}

$allUrl = trim($arParams['ALL_URL'] ?? '') ?: SITE_DIR.'materials/';
$badge = trim($arParams['BADGE_TEXT'] ?? '') ?: 'Статья';
?>
<section class="nd-articles">
	<?/* Плоская разметка — раскладку задаёт grid: на десктопе кнопка стоит справа
	   от заголовка, на мобильном уходит под список во всю ширину */?>
	<h2 class="nd-articles__title"><?= $arParams['TITLE_BLOCK'] ?: 'Полезно знать' ?></h2>

	<a class="nd-articles__all" href="<?= $allUrl ?>">Смотреть все</a>

	<div class="nd-articles__list">
		<? foreach ($arResult['ITEMS'] as $arItem): ?>
			<?
			$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));

			/* Раньше в разметку уходил оригинал: 800×507 по 150–200 КБ при плитке
			   424×293. Отдаём ресайз в полтора размера (Ирина, 19 августа 2026). */
			$pic = is_array($arItem['PREVIEW_PICTURE']) ? $arItem['PREVIEW_PICTURE'] : $arItem['DETAIL_PICTURE'];
			$src = '';
			if (is_array($pic) && $pic['ID']) {
				$img = CFile::ResizeImageGet($pic['ID'], ['width' => 636, 'height' => 440], BX_RESIZE_IMAGE_EXACT, true);
				$src = $img['src'] ?? $pic['SRC'];
			}
			$link = $arItem['DETAIL_PAGE_URL'];
			$tag = $link ? 'a' : 'div';
			?>
			<<?= $tag ?> class="nd-articles__item"<?= $link ? ' href="'.$link.'"' : '' ?> id="<?= $this->GetEditAreaId($arItem['ID']) ?>">
				<div class="nd-articles__pic">
					<? if ($src): ?>
						<img class="nd-articles__img" src="<?= $src ?>" alt="<?= $arItem['NAME'] ?>" loading="lazy">
					<? endif; ?>
					<span class="nd-articles__badge"><?= $badge ?></span>
				</div>
				<div class="nd-articles__name"><?= $arItem['NAME'] ?></div>
			</<?= $tag ?>>
		<? endforeach; ?>
	</div>
</section>
