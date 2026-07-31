<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Плитки каталога на /catalog/ в новом дизайне.
 *
 * Размеры из макета Figma («Чистовик», фрейм «Каталог страница» 21349:57069):
 * сетка 1336 в три колонки по 440 с зазором 8, карточка — заливка
 * rgba(82,82,100,.1), радиус 4, внутренние поля 24, шаг между шапкой и списком
 * 12; иконка 64×64, название 22/26 700, ссылки подразделов 16/24 400 с шагом 2.
 *
 * Данные готовит result_modifier.php: там дерево разделов и подбор иконки.
 * Стили — в css/newdesign.css (шаблон компонента свой style.css не заводит:
 * блок соседствует с брендами, и правила лежат рядом с ними).
 *
 * «Перголы» — псевдораздел: отдельной ветки в каталоге нет, ссылка ведёт на
 * посадочную в /materials/. Перенесён из старого шаблона
 * 2025_1_catalog_sections_list_desktop, где он тоже прописан руками.
 */
$this->setFrameMode(true);

if (empty($arResult['ND_SECTIONS'])) {
	return;
}

$ndPergolaUrl = '/materials/umnaya-pergola-3kh3-s-mebelyu-i-led-podsvetkoy-gotovyy-komplekt-dlya-idealnogo-otdykha/';
$ndPergolaIcon = SITE_TEMPLATE_PATH.'/images/newdesign/catalog/pergola.png';
?>
<div class="nd-catalog">
	<? foreach ($arResult['ND_SECTIONS'] as $arSection): ?>
		<? $this->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection['IBLOCK_ID'], 'SECTION_EDIT')); ?>
		<div class="nd-catalog__item" id="<?= $this->GetEditAreaId($arSection['ID']) ?>">
			<a class="nd-catalog__head" href="<?= $arSection['SECTION_PAGE_URL'] ?>">
				<? if ($arSection['ND_ICON']): ?>
					<img class="nd-catalog__icon<?= $arSection['ND_ICON_IS_PHOTO'] ? ' nd-catalog__icon--photo' : '' ?>" src="<?= $arSection['ND_ICON'] ?>" alt="<?= $arSection['NAME'] ?>" width="64" height="64" loading="lazy">
				<? else: ?>
					<span class="nd-catalog__icon nd-catalog__icon--empty" aria-hidden="true"></span>
				<? endif; ?>
				<span class="nd-catalog__name"><?= $arSection['NAME'] ?></span>
			</a>

			<? if ($arSection['CHILDS'] || $arSection['UF_MENULINK_TOP']): ?>
				<div class="nd-catalog__subs">
					<? foreach ($arSection['CHILDS'] as $arChild): ?>
						<div class="nd-catalog__sub">
							<a href="<?= $arChild['SECTION_PAGE_URL'] ?>"><?= $arChild['NAME'] ?></a>
						</div>
					<? endforeach; ?>

					<?/* Ссылки, которые контент-менеджер дописывает разделу вручную (UF_MENULINK_TOP).
					     Это готовая разметка ссылки из админки — выводим как есть, как в старом шаблоне. */?>
					<? foreach ((array) $arSection['UF_MENULINK_TOP'] as $ndLink): ?>
						<? if (trim((string) $ndLink) === '') continue; ?>
						<div class="nd-catalog__sub"><?= html_entity_decode($ndLink) ?></div>
					<? endforeach; ?>
				</div>
			<? endif; ?>
		</div>
	<? endforeach; ?>

	<div class="nd-catalog__item">
		<a class="nd-catalog__head" href="<?= $ndPergolaUrl ?>">
			<img class="nd-catalog__icon" src="<?= $ndPergolaIcon ?>" alt="Перголы" width="64" height="64" loading="lazy">
			<span class="nd-catalog__name">Перголы</span>
		</a>
	</div>
</div>
