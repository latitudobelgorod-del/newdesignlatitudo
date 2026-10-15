<?php
/* Страница сравнения товаров /compare/.
   В корне сайта лежит обёртка /compare/index.php (корень вне git):
       <?php require $_SERVER['DOCUMENT_ROOT'].'/local/compare/page.php';
   Вся страница — здесь, чтобы версионировалась.

   Пока идёт скрытый тест (ND_COMPARE_PUBLIC = false), страницу видит только
   группа «Тестирование сравнения»; остальным — 404, как будто адреса нет.
   robots: noindex, nofollow (после открытия — noindex, follow). */

/** @var CMain $APPLICATION */

// bitrix/header.php по частям: без левой колонки (как корзина, свойство раздела
// HIDE_LEFT_BLOCK) — ставим до вывода шапки, отдельный .section.php вне git не нужен
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
$APPLICATION->SetPageProperty('HIDE_LEFT_BLOCK', 'Y');
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_after.php');

if (!function_exists('ndCompareAllowed') || !ndCompareAllowed()
	|| !defined('SITE_TEMPLATE_ID') || SITE_TEMPLATE_ID !== 'aspro_next_newdesign') {
	\Bitrix\Iblock\Component\Tools::process404('', true, true, true, '');
	return;
}

$APPLICATION->SetTitle('Сравнение товаров');
$APPLICATION->SetPageProperty('title', 'Сравнение товаров');
$APPLICATION->SetPageProperty('robots', ND_COMPARE_PUBLIC ? 'noindex, follow' : 'noindex, nofollow');
// и заголовком ответа: страница в индекс не попадёт, даже если метатег потеряется
// (Ирина, 15.10.2026: варианты сравнения посетителей индексировать не надо)
header('X-Robots-Tag: noindex, ' . (ND_COMPARE_PUBLIC ? 'follow' : 'nofollow'));
// канонический — адрес без параметров (?tab=)
$APPLICATION->AddHeadString('<link rel="canonical" href="https://'.ndCurrentHost().'/compare/">', true);
$APPLICATION->AddChainItem('Сравнение товаров');

$APPLICATION->IncludeComponent('latitudo:catalog.compare', '.default', [], false, ['HIDE_ICONS' => 'Y']);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
