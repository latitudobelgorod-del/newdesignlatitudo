<?php
/* Калькулятор ограждений (/info/kalkulyator-ograzhdeniy/): кнопка «Добавить всё в корзину»
   (Ирина, 22.10.2026).

   Браузер присылает только ID товаров и количества из сметы — цену берёт корзина из каталога,
   как при обычном «В корзину». Кладём только товары раздела «Ограждения из ДПК» (ИБ 19,
   раздел 104 со всеми подразделами) и их торговые предложения (ИБ 20): кнопка не должна
   уметь добавлять что-то другое. Товар, который уже лежит в корзине, — количество прибавляется.

   POST: sessid, items (JSON [{id, qty}]). Ответ — JSON {ok, added, failed[], error?}. */

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$APPLICATION->RestartBuffer();
header('Content-Type: application/json; charset=utf-8');

$ndFbAnswer = function ($ok, $extra = array()) {
	echo json_encode(array_merge(array('ok' => (bool)$ok), $extra), JSON_UNESCAPED_UNICODE);
	die();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_bitrix_sessid()) {
	$ndFbAnswer(false, array('error' => 'Обновите страницу и попробуйте ещё раз.'));
}
if (!\Bitrix\Main\Loader::includeModule('iblock') || !\Bitrix\Main\Loader::includeModule('catalog') || !\Bitrix\Main\Loader::includeModule('sale')) {
	$ndFbAnswer(false, array('error' => 'Корзина сейчас недоступна.'));
}

// смета: не больше 30 строк, количество — целое от 1 до 10 000
$ndFbQty = array();
foreach (array_slice((array)json_decode((string)($_POST['items'] ?? ''), true), 0, 30) as $row) {
	$id = (int)($row['id'] ?? 0);
	$qty = (int)($row['qty'] ?? 0);
	if ($id > 0 && $qty >= 1 && $qty <= 10000) {
		$ndFbQty[$id] = ($ndFbQty[$id] ?? 0) + $qty;
	}
}
if (!$ndFbQty) {
	$ndFbAnswer(false, array('error' => 'В смете нет товаров с ценой.'));
}

// какие из присланных ID — товары ограждений: простые товары раздела 104 и предложения его товаров
$ndFbAllowed = array();
$res = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 19, 'ID' => array_keys($ndFbQty), 'SECTION_ID' => 104, 'INCLUDE_SUBSECTIONS' => 'Y', 'ACTIVE' => 'Y'), false, false, array('ID'));
while ($el = $res->Fetch()) {
	$ndFbAllowed[(int)$el['ID']] = true;
}
$ndFbOffers = array();
$res = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 20, 'ID' => array_keys($ndFbQty), 'ACTIVE' => 'Y'), false, false, array('ID', 'PROPERTY_CML2_LINK'));
while ($el = $res->Fetch()) {
	$ndFbOffers[(int)$el['ID']] = (int)$el['PROPERTY_CML2_LINK_VALUE'];
}
if ($ndFbOffers) {
	$res = CIBlockElement::GetList(array(), array('IBLOCK_ID' => 19, 'ID' => array_values(array_unique($ndFbOffers)), 'SECTION_ID' => 104, 'INCLUDE_SUBSECTIONS' => 'Y', 'ACTIVE' => 'Y'), false, false, array('ID'));
	$ndFbParents = array();
	while ($el = $res->Fetch()) {
		$ndFbParents[(int)$el['ID']] = true;
	}
	foreach ($ndFbOffers as $offerId => $parentId) {
		if (isset($ndFbParents[$parentId])) {
			$ndFbAllowed[$offerId] = true;
		}
	}
}

$ndFbAdded = 0;
$ndFbFailed = array();
foreach ($ndFbQty as $id => $qty) {
	if (empty($ndFbAllowed[$id])) {
		$ndFbFailed[] = $id;
		continue;
	}
	$r = \Bitrix\Catalog\Product\Basket::addProduct(array('PRODUCT_ID' => $id, 'QUANTITY' => $qty), array('USE_MERGE' => 'Y'));
	if ($r->isSuccess()) {
		$ndFbAdded++;
	} else {
		$ndFbFailed[] = $id;
	}
}

$ndFbAnswer($ndFbAdded > 0, array('added' => $ndFbAdded, 'failed' => $ndFbFailed,
	'error' => $ndFbAdded ? '' : 'Не удалось добавить товары в корзину.'));
