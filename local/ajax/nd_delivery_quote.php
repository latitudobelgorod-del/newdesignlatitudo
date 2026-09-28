<?php
/* Расчёт доставки из корзины → сессия (Ирина, 28.09.2026).

   Блок «Расчёт доставки» на /basket/ (local/include/basket_delivery_calc.php)
   присылает сюда то, что посчитал фрейм LATITUDO, и галочку «С доставкой».
   Запоминаем вместе с составом корзины на момент расчёта; при оформлении
   заказа ndDeliveryQuoteToOrder() (local/php_interface/include/
   latitudo_delivery_quote.php) дописывает расчёт в комментарий заказа —
   если состав с тех пор не менялся.

   Цена приходит из браузера, поэтому в сумму заказа она не идёт: это только
   текст для менеджера с пометкой «предварительно».

   POST: sessid, action=save|clear; для save — to (куда), price (₽), with (Y|N).
   Ответ — JSON {ok: true|false}. */

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$APPLICATION->RestartBuffer();
header('Content-Type: application/json; charset=utf-8');

$ndDqAnswer = function ($ok) {
	echo json_encode(array('ok' => (bool)$ok));
	die();
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_bitrix_sessid()) {
	$ndDqAnswer(false);
}

$ndDqAction = (string)($_POST['action'] ?? '');

if ($ndDqAction === 'clear') {
	unset($_SESSION['ND_DELIVERY_QUOTE']);
	$ndDqAnswer(true);
}

if ($ndDqAction !== 'save' || !\Bitrix\Main\Loader::includeModule('sale')) {
	$ndDqAnswer(false);
}

$ndDqPrice = round((float)str_replace(array(' ', ','), array('', '.'), (string)($_POST['price'] ?? '')), 2);
if ($ndDqPrice <= 0 || $ndDqPrice >= 10000000) {
	$ndDqAnswer(false);
}

/* Текст «куда» — только для комментария: без тегов и переводов строк, не длиннее 200. */
$ndDqTo = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($_POST['to'] ?? ''))));
$ndDqTo = mb_substr($ndDqTo, 0, 200);

require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/latitudo_delivery_quote.php';

$ndDqBasket = \Bitrix\Sale\Basket::loadItemsForFUser(
	\Bitrix\Sale\Fuser::getId(),
	\Bitrix\Main\Context::getCurrent()->getSite()
);
$ndDqKey = ndDeliveryQuoteBasketKey($ndDqBasket->getOrderableItems());
if ($ndDqKey === '') {
	$ndDqAnswer(false);
}

$_SESSION['ND_DELIVERY_QUOTE'] = array(
	'TO'    => $ndDqTo,
	'PRICE' => $ndDqPrice,
	'WITH'  => (($_POST['with'] ?? '') === 'Y'),
	'KEY'   => $ndDqKey,
	'TIME'  => time(),
);
$ndDqAnswer(true);
