<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
/* Видео о продукции — все ролики из инфоблока «Видео» (videos) сеткой, с фильтром
   по категориям и маркам (Ирина, 28.10.2026). Та же лента, что внизу страницы
   марки, — компонент latitudo:video.rail в режиме VIEW=GRID; ролики заводятся
   сами из «Видео в попапе» товаров (local/php_interface/include/latitudo_videos.php).

   В urlrewrite.php остался старый адрес #^/video/# на bitrix:news — всё, что
   глубже /video/, сюда и приходит: отдаём 404, чтобы не было дублей страницы. */
if (!preg_match('~^/video/(index\.php)?$~', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH))) {
	if (!defined("ERROR_404")) define("ERROR_404", "Y");
	CHTTP::SetStatus("404 Not Found");
	require($_SERVER["DOCUMENT_ROOT"]."/404.php");
	die();
}
$APPLICATION->SetTitle("Видео о продукции");
$APPLICATION->SetPageProperty("title", "Видео о продукции — обзоры террасной доски, фасадов и заборов из ДПК | Латитудо");
$APPLICATION->SetPageProperty("description", "Видеообзоры товаров Латитудо: террасная доска, фасадные панели, заборы и ограждения из ДПК. Фактура, цвета и монтаж — смотрите и сразу добавляйте в корзину.");
$APPLICATION->SetPageProperty("HIDETITLE", "N");
/* В новом дизайне H1 печатает не шапка: страница отдаёт его в отложенную область
   nd_page_head (page_blocks/page_title_newdesign.php), как каталог и поиск. */
if (SITE_TEMPLATE_ID === "aspro_next_newdesign") {
	$APPLICATION->AddViewContent("nd_page_head", '<div class="nd-cat-head"><h1 id="pagetitle">Видео о продукции</h1></div>');
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>
<?$APPLICATION->IncludeComponent(
	"latitudo:video.rail",
	"",
	array(
		"VIEW" => "GRID",
		"SUBTITLE" => "Фактура, цвета и монтаж — смотрите, прежде чем покупать. Товар из ролика можно сразу положить в корзину.",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => 3600,
	),
	false,
	array("HIDE_ICONS" => "Y")
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
