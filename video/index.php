<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
/* Видео — все ролики из инфоблока «Видео» (videos) сеткой, с фильтром по типу:
   обзоры продукции, отзывы, эксперт, объекты из портфолио (Ирина, 28.10.2026).
   Та же лента, что внизу страницы марки, — компонент latitudo:video.rail в режиме
   VIEW=GRID; обзоры заводятся сами из «Видео в попапе» товаров, ролики портфолио
   перенесены local/tools/nd_videos_portfolio.php.

   В urlrewrite.php остался старый адрес #^/video/# на bitrix:news — всё, что
   глубже /video/, сюда и приходит: отдаём 404, чтобы не было дублей страницы. */
if (!preg_match('~^/video/(index\.php)?$~', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH))) {
	if (!defined("ERROR_404")) define("ERROR_404", "Y");
	CHTTP::SetStatus("404 Not Found");
	require($_SERVER["DOCUMENT_ROOT"]."/404.php");
	die();
}
// заголовки — после переноса роликов портфолио (Ирина, 28.10.2026: «подумай над новым тайтлом»)
$ndVideoH1 = "Видео: обзоры, отзывы и наши объекты";
// заголовок строкой, не переменной: генератор sitemap (логический режим) берёт только
// страницы, у которых PHPParser::getPageTitle находит SetTitle("…") — иначе /video/ нет в карте
$APPLICATION->SetTitle("Видео: обзоры, отзывы и наши объекты");
$APPLICATION->SetPageProperty("title", "Видео о ДПК: обзоры террасной доски, отзывы клиентов и наши объекты | Латитудо");
$APPLICATION->SetPageProperty("description", "Видео Латитудо: обзоры террасной доски, ограждений, фасадов и пергол, отзывы клиентов, советы эксперта и готовые террасы, заборы и беседки с наших объектов.");
$APPLICATION->SetPageProperty("HIDETITLE", "N");
/* В новом дизайне H1 печатает не шапка: страница отдаёт его в отложенную область
   nd_page_head (page_blocks/page_title_newdesign.php), как каталог и поиск. */
if (SITE_TEMPLATE_ID === "aspro_next_newdesign") {
	$APPLICATION->AddViewContent("nd_page_head", '<div class="nd-cat-head"><h1 id="pagetitle">' . $ndVideoH1 . '</h1></div>');
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
?>
<?$APPLICATION->IncludeComponent(
	"latitudo:video.rail",
	"",
	array(
		"VIEW" => "GRID",
		// фильтр по маркам пока не показываем (Ирина, 28.10.2026)
		"BRAND_FILTER" => "N",
		// ряд категорий товаров тоже убран — фильтр только по типу видео (Ирина, 28.10.2026)
		"CATEGORY_FILTER" => "N",
		"SUBTITLE" => "Обзоры продукции, отзывы клиентов и террасы, заборы и беседки, которые мы построили. Товар из обзора можно сразу положить в корзину.",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => 3600,
	),
	false,
	array("HIDE_ICONS" => "Y")
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
