<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Страница «Портфолио» нового дизайна — только разделы инфоблока.
 *
 * Так же устроен и старый дизайн: его news.php выводит заголовок, подпись и
 * page_block `sections_3` с плитками разделов. Сами проекты живут на страницах
 * разделов (section.php) — там фильтры, плашки разделов и сетка карточек.
 *
 * Копия асprovского шаблона `projects`: оригинал не трогаем, он остаётся
 * старому дизайну. Страница /projects/index.php выбирает имя шаблона
 * по SITE_TEMPLATE_ID.
 */
$this->setFrameMode(true);

if ($arParams['USE_RSS'] !== 'N') {
	CNext::ShowRSSIcon($arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss']);
}

$ndIb = (int) $arParams['IBLOCK_ID'];

/* Кнопка ведёт на ту же веб-форму, что и блок «Обсудить проект» внизу страницы
   в старом дизайне (MAINFORM, data-name detail_project) — по макету она
   переехала в шапку страницы и получила текст «Заказать расчет проекта».

   `data-nd-form-title` обязателен: в новом дизайне тема не может привязать
   триггер (hash.t приезжает как document), из-за чего в заголовке формы
   остаётся «Общая форма», а в скрытое поле NAMEFORM — текст всей страницы,
   и он уходит в письмо и в CRM. Подпись подставляет js/newdesign-header.js —
   та же починка, что у кнопок шапки. */
?>
<div class="nd-page-head">
	<h1 id="pagetitle" class="nd-projects__h1">Портфолио объектов Латитудо</h1>
	<div class="nd-page-head__cta"><span class="callback-block animate-load" data-event="jqm" data-param-form_id="MAINFORM" data-name="detail_project" data-nd-form-title="Заказать расчет проекта">Заказать расчет проекта</span></div>
</div>
<p class="nd-page-head__lead">Посмотрите на фотографии наших проектов. Выберите то, что понравилось - так нам будет проще понять друг друга.</p>
<?

/* Фильтры над плитками (Ирина, 11 сентября 2026): «Есть отзыв», «Есть
   видео», «Цвет», «Бренд», «Товары» — тот же блок, что на странице раздела.
   Пока ничего не выбрано, он печатает только панель, и ниже идут плитки
   разделов. С выбранным фильтром блок сам выводит плашки разделов и сетку
   подходящих объектов из всех разделов — плитки тогда не нужны. */
$ndProjectsRoot = true;
$ndActive = false;
include __DIR__.'/page_blocks/list_elements_newdesign.php';

if ($ndActive) {
	// выборки по фильтру — дубли общей страницы, из индекса убираем, как у разделов
	$APPLICATION->SetPageProperty('robots', 'noindex, nofollow');
} else {
	include __DIR__.'/page_blocks/sections_list_newdesign.php';
}
