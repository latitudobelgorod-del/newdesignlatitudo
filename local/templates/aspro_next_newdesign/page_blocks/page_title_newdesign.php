<?
/**
 * Верх внутренней страницы нового дизайна: только хлебные крошки.
 *
 * Заменяет page_title_3.php, который выбирается настройкой темы PAGE_TITLE_TYPE.
 * Настройка одна на сайт, к шаблону не привязана (та же беда, что с HEADER_TYPE
 * и INDEX_TYPE), поэтому подключаем файл напрямую из header.php шаблона.
 *
 * Баннеры (рассрочка, «Мы переехали») в старом page_title_3.php прибиты прямо в
 * разметке; в новом дизайне их пока не выводим — по просьбе Ирины 2026-07-31.
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
