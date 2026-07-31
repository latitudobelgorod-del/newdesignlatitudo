<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
/**
 * Страница «Статьи» (/materials/) нового дизайна.
 *
 * Копия асprovского шаблона `blog`: оригинал не трогаем, он остаётся старому
 * дизайну. Страница /materials/index.php выбирает имя шаблона по SITE_TEMPLATE_ID,
 * поэтому старый дизайн продолжает брать `blog`.
 *
 * Вся разметка — в шаблоне news.list `list_articles_page_newdesign`,
 * навигация — общая с отзывами и портфолио (`pagination_newdesign`).
 *
 * Заголовок здесь не выводим: для блоговых страниц его печатает сам header.php
 * шаблона (`<h1 id="pagetitle">` внутри .content-md). Раньше он был пустой —
 * текст приходил только из $sSectionName в /materials/.section.php, а его к
 * этому моменту ещё нет; теперь /materials/index.php ставит SetTitle явно.
 * Оформление этого H1 и разделитель под ним — в style.css шаблона
 * news.list/list_articles_page_newdesign. Теги под заголовком в макете есть,
 * но пока не выводим (Ирина, 2026-07-31).
 */
$this->setFrameMode(true);

include __DIR__.'/page_blocks/list_elements_newdesign.php';
