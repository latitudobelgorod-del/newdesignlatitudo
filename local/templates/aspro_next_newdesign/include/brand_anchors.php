<?php
/* Якоря на разделы бренда — ссылки над списком товаров.

   Раньше их рисовал компонент catalog.section с шаблоном ankor_section
   (include/news.detail.ankor_section.php). Ради десятка ссылок компонент
   вычитывал сотню товаров целиком, с торговыми предложениями и ценами, и это
   стоило странице бренда почти всё её время: EasyDecking 16.9 → 1.4 секунды,
   Millargo 8.7 → 1.0. Здесь тот же список считается двумя запросами — разделы
   товаров бренда и сами разделы.

   Порядок и подписи даёт LdBrandSections, тот же класс зовёт список товаров.
   Порядок обязан совпадать, иначе якорь уводит не в тот блок.

   Разметка повторяет catalog.section/ankor_section — на её классы завязаны
   стили в css/custom.css. Старый дизайн (aspro_next) по-прежнему рисует якоря
   тем компонентом, его мы не трогаем. */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/brand_sections.php';

/* Фильтр товаров бренда кладёт page_blocks/element_1.php — тот самый, по
   которому компонент искал товары. Нет фильтра — нечего и подписывать. */
$ldAnchorFilter = (array)($GLOBALS['arrProductsFilter2'] ?? []);
$ldAnchorSections = $ldAnchorFilter
	? LdBrandSections::fromFilter(
		\Bitrix\Main\Config\Option::get('aspro.next', 'CATALOG_IBLOCK_ID', 19),
		$ldAnchorFilter
	)
	: [];
?>
<?php if ($ldAnchorSections): ?>
	<?php /* id="portfolio_loader" из старой разметки здесь не повторяем: тот же
	   идентификатор носит сетка товаров ниже, и на странице их было два. */ ?>
	<div class="top_wrapper row margin0 unshow_un_props">
		<div class="items ankor_sect">
			<div class="wrap">
				<div class="clearfix">
					<?php foreach ($ldAnchorSections as $ldAnchorSection): ?>
						<div class="item">
							<a class="some_link" href="#<?=htmlspecialcharsbx($ldAnchorSection['CODE'])?>"><?=htmlspecialcharsbx($ldAnchorSection['NAME'])?></a>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>
