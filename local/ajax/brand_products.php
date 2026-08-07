<?php
/* Догрузка карточек одного раздела на странице бренда.

   Страница печатает по LD_PER_SECTION карточек в разделе (у Millargo товаров
   295 — все сразу, с полным JS карточки, в разметку не помещаются). Кнопка
   «Показать ещё» дозапрашивает хвост раздела сюда, а отдаём мы голую сетку
   карточек: без обёрток списка, стилей и общих скриптов — на странице они уже
   стоят. Разметку рисует тот же include, что и страница, поэтому параметры
   каталога совпадают до буквы. */

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/** @var CMain $APPLICATION */
global $APPLICATION;

$ldFail = function ($status) use ($APPLICATION) {
	$APPLICATION->RestartBuffer();
	CHTTP::SetStatus($status);
	require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
	die();
};

/* Карточка редизайна живёт только в aspro_next_newdesign. Шаблон сайта здесь
   выбирается так же, как на странице (по куке NEWDESIGN), и запрос идёт с той
   же страницы — но если куки не будет, компонент взял бы чужой шаблон и отдал
   разметку, к которой на странице нет ни стилей, ни скриптов. */
if (!defined('SITE_TEMPLATE_ID') || SITE_TEMPLATE_ID !== 'aspro_next_newdesign') {
	$ldFail('404 Not Found');
}

$ldBrandId = (int)($_GET['brand'] ?? 0);
$ldProp = (string)($_GET['prop'] ?? '');
$ldSectionId = max(0, (int)($_GET['section'] ?? 0));
$ldOffset = max(0, (int)($_GET['offset'] ?? 0));
$ldPer = min(200, max(1, (int)($_GET['per'] ?? 20)));

// prop уходит в имя ключа фильтра (PROPERTY_<код>), поэтому проверяем строго
if ($ldBrandId <= 0 || !preg_match('/^[A-Za-z0-9_]+$/', $ldProp)) {
	$ldFail('400 Bad Request');
}

$APPLICATION->RestartBuffer();

/* section=0 — бренд с «Шаблоном №2»: разделов нет, режем сплошной список
   порциями по $ldPer. С разделом отдаём его хвост целиком: раздел небольшой,
   и вторая кнопка в нём никому не нужна. */
$ldFilter = [
	'PROPERTY_'.$ldProp => $ldBrandId,
	'ACTIVE' => 'Y',
];
if ($ldSectionId > 0) {
	$ldFilter['IBLOCK_SECTION_ID'] = $ldSectionId;
}

$ldBrand = [
	'MODE' => ($ldSectionId > 0 ? 'sections' : 'flat'),
	'FILTER' => $ldFilter,
	'PER_SECTION' => $ldPer,
	'ITEMS_ONLY' => 'Y',
	'OFFSET' => $ldOffset,
];
include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/brand_products.php';

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
