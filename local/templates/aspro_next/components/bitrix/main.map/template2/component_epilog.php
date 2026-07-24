<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

// Тестовый код - создаст заметное сообщение
AddMessage2Log("Component epilog loaded for sitemap", "main.map");

// Или выведите сообщение (только для теста)
// echo "<!-- Component epilog is working -->";

// Основной код для 404
if (isset($_GET['PAGEN_1']) && (int)$_GET['PAGEN_1'] > 1) {
    CHTTP::setStatus("404 Not Found");
define('ERROR_404','Y');
	Bitrix\Iblock\Component\Tools::process404(
	       'Не найден', //Сообщение
	       true, // Нужно ли определять 404-ю константу
	       true, // Устанавливать ли статус
	       true, // Показывать ли 404-ю страницу
	       false // Ссылка на отличную от стандартной 404-ю
	);
}