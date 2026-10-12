<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?
/**
 * Стили и скрипт карусели «Шоу-румы компании».
 *
 * Карусель — news.list showrooms_newdesign внутри текста страницы
 * (include/company/about_page.php). На /info/ эта страница выводится
 * news.detail с CACHE_TYPE=A: при попадании в кеш шаблон не выполняется,
 * вложенный news.list не запускается и свои style.css/script.js не
 * подключает — фото шоу-румов вставали столбиком (12.10.2026). Эпилог
 * выполняется всегда, поэтому подключаем их отсюда. На /info/company/
 * они подключаются и самим компонентом — Asset не дублирует.
 * Без карусели на странице стили и скрипт ничего не делают (всё под .nd-shr
 * и [data-nd-shr]).
 */
$ndShrPath = SITE_TEMPLATE_PATH.'/components/bitrix/news.list/showrooms_newdesign/';
$APPLICATION->SetAdditionalCSS($ndShrPath.'style.css');
$APPLICATION->AddHeadScript($ndShrPath.'script.js');
