<?
/**
 * Верх внутренней страницы нового дизайна: хлебные крошки и (если страница
 * её попросила) шапка с H1 и кнопкой.
 *
 * Шапка выводится здесь, а не в шаблоне компонента, потому что по макету она
 * идёт над обеими колонками — а компонент рисуется уже внутри правой колонки
 * (`.right_block`), рядом с меню слева. Наполняет её сам компонент через
 * $APPLICATION->AddViewContent('nd_page_head', ...) — штатный механизм
 * отложенных функций: заглушка ставится здесь, содержимое приезжает ниже
 * по странице. Так же тема выводит section_bnr_content.
 *
 * Заменяет page_title_3.php, который выбирается настройкой темы PAGE_TITLE_TYPE.
 * Настройка одна на сайт, к шаблону не привязана (та же беда, что с HEADER_TYPE
 * и INDEX_TYPE), поэтому подключаем файл напрямую из header.php шаблона.
 *
 * Баннеры (рассрочка, «Мы переехали») в старом page_title_3.php прибиты прямо в
 * разметке; в новом дизайне их не выводим — по просьбе Ирины 2026-07-31.
 * Исключение — сквозной баннер перголы: 18 августа 2026 попросили показывать
 * его и здесь, как в старом дизайне, только со скруглением 4.
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
?>
<div class="nd-crumbs-wrap">
	<div id="navigation">
		<?$APPLICATION->IncludeComponent("bitrix:breadcrumb", "newdesign", array(
			"START_FROM" => "0",
			"PATH" => "",
			"SITE_ID" => SITE_ID,
			"SHOW_SUBSECTIONS" => "N"
			),
			false,
			array("HIDE_ICONS" => "Y")
		);?>
	</div>
</div>
<?/* Сквозной баннер «Комплект Пергола + мебель» — та же картинка и ссылка, что
   в старом дизайне (page_blocks/page_title_3.php). На самой странице перголы
   не печатаем: баннер туда и ведёт. */?>
<? if (strpos($APPLICATION->GetCurPage(false), '/materials/umnaya-pergola-3kh3-s-mebelyu-i-led-podsvetkoy-gotovyy-komplekt-dlya-idealnogo-otdykha/') !== 0): ?>
<div class="nd-topbanner">
	<a href="/materials/umnaya-pergola-3kh3-s-mebelyu-i-led-podsvetkoy-gotovyy-komplekt-dlya-idealnogo-otdykha/">
		<img src="/images/banners/banner_pergola.jpg" alt="Комплект Пергола + мебель" title="Комплект Пергола + мебель" loading="lazy">
	</a>
</div>
<? endif; ?>
<div class="nd-page-head-wrap"><?$APPLICATION->ShowViewContent('nd_page_head');?></div>
