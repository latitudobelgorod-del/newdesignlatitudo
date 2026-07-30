<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?$this->setFrameMode(true);?>
<?
/**
 * Выпадающий каталог шапки нового дизайна — две панели.
 *
 * Переписан с боевого шаблона aspro_next/components/bitrix/menu/top_new2
 * (там это ветка `elseif ($arItem['LINK'] == '/catalog/')`, блок megamenu-*).
 * Источник данных и фильтрация — как на боевом, чтобы список совпадал:
 * тип меню `top_content_multilevel`, дети раздела /catalog/ приходят из
 * `catalog/.top_menu_new.menu_ext.php` (CCustomNext::getSectionChilds), дерево
 * строит result_modifier.php через CNext::getChilds. Показываем только разделы
 * с галкой UF_SECTION_IN_MENU — ровно как боевой.
 *
 * Размеры из макета Figma «Чистовик», фрейм «Каталог» (узел 21349:56357):
 * контейнер 1440 с полями 40/52 и зазором 52 между колонками; слева чипы
 * 377×64 с шагом 12 (картинка 64, подпись 18px/25.2, шеврон 24);
 * справа заголовок 36px/39.6 (800) + кнопка «Смотреть все», ниже сетка
 * по 4 плитки 220×108 с зазором 8 (картинка 56, подпись 12px/14.4).
 *
 * Стили — css/newdesign-header.css (префикс .nd-cat), переключение групп —
 * js/newdesign-header.js.
 */

// Находим пункт каталога: его дети — разделы, которые и рисуем.
$arCatalog = null;
foreach($arResult as $arItem)
{
	if($arItem['LINK'] === SITE_DIR.'catalog/' || $arItem['LINK'] === '/catalog/')
	{
		$arCatalog = $arItem;
		break;
	}
}

if(!$arCatalog || empty($arCatalog['CHILD']))
	return;

// Оставляем разделы, помеченные к показу в меню (как в боевом шаблоне).
$arSections = array();
foreach($arCatalog['CHILD'] as $arSection)
{
	if(empty($arSection['PARAMS']['SECTION_IN_MENU']))
		continue;
	$arSections[] = $arSection;
}

if(!$arSections)
	return;

/**
 * Картинка раздела: в левой колонке 64×64, в плитке 56×56.
 * PICTURE приходит из menu_ext идентификатором файла.
 */
$ndSectionImg = function($arItem, $size) {
	$id = isset($arItem['PARAMS']['PICTURE']) ? (int)$arItem['PARAMS']['PICTURE'] : 0;
	if($id <= 0)
		return null;
	$img = CFile::ResizeImageGet($id, array('width' => $size, 'height' => $size), BX_RESIZE_IMAGE_PROPORTIONAL, true);
	return (is_array($img) && $img['src']) ? $img['src'] : null;
};
?>
<div class="nd-cat">

	<!-- Слева: разделы -->
	<div class="nd-cat__aside" role="tablist">
		<?foreach($arSections as $i => $arSection):?>
			<?$img = $ndSectionImg($arSection, 64);?>
			<a class="nd-cat__chip<?=($i === 0 ? ' is-active' : '')?>"
			   href="<?=htmlspecialcharsbx($arSection['LINK'])?>"
			   data-nd-cat-target="<?=htmlspecialcharsbx($arSection['LINK'])?>"
			   role="tab">
				<span class="nd-cat__chip-img">
					<?if($img):?>
						<img src="<?=htmlspecialcharsbx($img)?>" alt="" width="64" height="64" loading="lazy">
					<?endif;?>
				</span>
				<span class="nd-cat__chip-label"><?=htmlspecialcharsbx($arSection['TEXT'])?></span>
				<?// Шеврон инлайном, а не картинкой: цвет у активного пункта другой,
				// а currentColor позволяет задать оба состояния из CSS.?>
				<svg class="nd-cat__chip-arrow" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M8.86321 5.36327C9.21469 5.0118 9.78518 5.0118 10.1367 5.36327L16.1367 11.3633C16.488 11.7148 16.4881 12.2853 16.1367 12.6367L10.1367 18.6367C9.7852 18.9882 9.21469 18.9881 8.86321 18.6367C8.51174 18.2852 8.51174 17.7147 8.86321 17.3633L14.2265 12L8.86321 6.63671C8.51174 6.28524 8.51174 5.71474 8.86321 5.36327Z" fill="currentColor"/>
				</svg>
			</a>
		<?endforeach;?>
	</div>

	<!-- Справа: подразделы выбранного раздела -->
	<div class="nd-cat__panels">
		<?foreach($arSections as $i => $arSection):?>
			<div class="nd-cat__panel<?=($i === 0 ? ' is-active' : '')?>"
			     data-nd-cat-panel="<?=htmlspecialcharsbx($arSection['LINK'])?>">

				<div class="nd-cat__panel-head">
					<div class="nd-cat__panel-title"><?=htmlspecialcharsbx($arSection['TEXT'])?></div>
					<a class="nd-cat__all" href="<?=htmlspecialcharsbx($arSection['LINK'])?>">Смотреть все</a>
				</div>

				<?
				// В сетку идут только подразделы, помеченные к показу.
				$arChilds = array();
				if(!empty($arSection['CHILD']))
				{
					foreach($arSection['CHILD'] as $arChild)
					{
						if(empty($arChild['PARAMS']['SECTION_IN_MENU']))
							continue;
						$arChilds[] = $arChild;
					}
				}
				?>
				<?if($arChilds):?>
					<div class="nd-cat__grid">
						<?foreach($arChilds as $arChild):?>
							<?$childImg = $ndSectionImg($arChild, 56);?>
							<a class="nd-cat__card<?=(!empty($arChild['SELECTED']) ? ' is-active' : '')?>" href="<?=htmlspecialcharsbx($arChild['LINK'])?>">
								<span class="nd-cat__card-img">
									<?if($childImg):?>
										<img src="<?=htmlspecialcharsbx($childImg)?>" alt="" width="56" height="56" loading="lazy">
									<?endif;?>
								</span>
								<span class="nd-cat__card-label"><?=htmlspecialcharsbx($arChild['TEXT'])?></span>
							</a>
						<?endforeach;?>
					</div>
				<?endif;?>
			</div>
		<?endforeach;?>
	</div>

</div>
