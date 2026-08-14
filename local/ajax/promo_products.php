<?php
/* Догрузка карточек товаров акции (страница /sale/<код>/).

   Страница печатает первую порцию, кнопка «Показать ещё» просит хвост здесь.
   Отдаём голую сетку карточек — без обёрток списка, стилей и общих скриптов:
   на странице они уже стоят. Разметку рисует тот же include, что и страница
   (include/brand_products.php), поэтому параметры каталога совпадают до буквы.

   Сосед по механике — /local/ajax/brand_products.php (страница бренда). */

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

/* Карточка редизайна живёт только в aspro_next_newdesign: шаблон здесь
   выбирается так же, как на странице (по куке NEWDESIGN), и без неё компонент
   отдал бы разметку, к которой на странице нет ни стилей, ни скриптов. */
if (!defined('SITE_TEMPLATE_ID') || SITE_TEMPLATE_ID !== 'aspro_next_newdesign') {
	$ldFail('404 Not Found');
}

$ldPromoId = (int)($_GET['promo'] ?? 0);
$ldOffset = max(0, (int)($_GET['offset'] ?? 0));
$ldPer = min(200, max(1, (int)($_GET['per'] ?? 20)));

if ($ldPromoId <= 0) {
	$ldFail('400 Bad Request');
}

/* Список товаров читаем у самой акции, а не из запроса: клиент передаёт только
   её ID, поэтому подсунуть чужой фильтр нельзя. */
$ldIds = [];
$ldRes = CIBlockElement::GetList(
	[],
	['IBLOCK_ID' => 17, 'ID' => $ldPromoId, 'ACTIVE' => 'Y'],
	false,
	false,
	['ID', 'IBLOCK_ID', 'PROPERTY_LINK_GOODS']
);
while ($ldRow = $ldRes->Fetch()) {
	if ((int)$ldRow['PROPERTY_LINK_GOODS_VALUE'] > 0) {
		$ldIds[] = (int)$ldRow['PROPERTY_LINK_GOODS_VALUE'];
	}
}
$ldIds = array_values(array_unique($ldIds));

if (!$ldIds) {
	$ldFail('404 Not Found');
}

$APPLICATION->RestartBuffer();

$ldBrand = [
	'MODE' => 'flat',
	'FILTER' => ['ID' => $ldIds, 'ACTIVE' => 'Y'],
	'PER_SECTION' => $ldPer,
	'ITEMS_ONLY' => 'Y',
	'OFFSET' => $ldOffset,
];
include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/brand_products.php';

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
