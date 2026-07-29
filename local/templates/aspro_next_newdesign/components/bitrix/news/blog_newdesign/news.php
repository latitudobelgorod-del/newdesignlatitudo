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
 */
$this->setFrameMode(true);

include __DIR__.'/page_blocks/list_elements_newdesign.php';
