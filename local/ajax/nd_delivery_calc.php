<?php
/* Расчёт доставки в корзине через API калькулятора LATITUDO (Ирина, 02.10.2026).
   Раньше считал фрейм monitor.latitudo-scrum.ru/delivery-frame/ и присылал
   цену из браузера; теперь блок local/include/basket_delivery_calc.php
   спрашивает этот файл, а он — API с ключом (ключ в браузер не попадает,
   local/php_interface/include/latitudo_delivery_api.php).

   POST, sessid обязателен. Ответ — JSON.
     action=city,  query (от 2 букв)       → {ok, items: [{id, name, region}]}
     action=quote, warehouse (msk|krd|bel|vrn), city_id, city (название)
       → {ok, quote: {…}} или {ok: false, error: 'текст для покупателя'}
   Состав для расчёта — текущая корзина покупателя, с сервера. Удачный расчёт
   сразу кладём в сессию (ND_DELIVERY_QUOTE) с галочкой «Доставка»: при
   оформлении ndDeliveryQuoteToOrder() допишет его в заказ и лид — если
   состав корзины с тех пор не менялся.

   Ограничение: 30 расчётов и 300 подсказок городов за 10 минут на сессию —
   каждый расчёт стоит запросов к ATI. */

define('STOP_STATISTICS', true);
define('NO_AGENT_CHECK', true);
define('NOT_CHECK_PERMISSIONS', true);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$APPLICATION->RestartBuffer();
header('Content-Type: application/json; charset=utf-8');

$ndDcAnswer = function (array $data) {
	echo json_encode($data, JSON_UNESCAPED_UNICODE);
	die();
};
$ndDcFail = 'Не удалось рассчитать доставку автоматически — менеджер посчитает её при оформлении заказа.';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !check_bitrix_sessid()) {
	$ndDcAnswer(array('ok' => false, 'error' => 'Обновите страницу и попробуйте ещё раз.'));
}

require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/latitudo_delivery_api.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/local/php_interface/include/latitudo_delivery_quote.php';

if (ndDeliveryApiKey() === '') {
	$ndDcAnswer(array('ok' => false, 'error' => $ndDcFail));
}

/** Не больше $limit обращений вида $kind за 10 минут на сессию. */
$ndDcRateOk = function (string $kind, int $limit): bool {
	$now = time();
	$list = array_filter((array)($_SESSION['ND_DC_RATE'][$kind] ?? array()), function ($t) use ($now) {
		return $now - (int)$t < 600;
	});
	if (count($list) >= $limit) {
		return false;
	}
	$list[] = $now;
	$_SESSION['ND_DC_RATE'][$kind] = array_values($list);
	return true;
};

$ndDcAction = (string)($_POST['action'] ?? '');

if ($ndDcAction === 'city') {
	$query = ndDeliveryQuoteClean($_POST['query'] ?? '', 60);
	if (mb_strlen($query) < 2 || !$ndDcRateOk('city', 300)) {
		$ndDcAnswer(array('ok' => true, 'items' => array()));
	}
	list($code, $data) = ndDeliveryApiRequest('/ati-autocomplete?query=' . rawurlencode($query), null, 10);
	$items = array();
	if ($code === 200 && !empty($data['suggestions']) && is_array($data['suggestions'])) {
		foreach (array_slice($data['suggestions'], 0, 10) as $s) {
			if (empty($s['id']) || empty($s['name'])) {
				continue;
			}
			$items[] = array(
				'id'     => (int)$s['id'],
				'name'   => (string)$s['name'],
				'region' => (string)($s['region_name'] ?? ''),
			);
		}
	}
	$ndDcAnswer(array('ok' => true, 'items' => $items));
}

if ($ndDcAction !== 'quote' || !\Bitrix\Main\Loader::includeModule('sale')) {
	$ndDcAnswer(array('ok' => false, 'error' => $ndDcFail));
}

$whCode = (string)($_POST['warehouse'] ?? '');
$cityId = (int)($_POST['city_id'] ?? 0);
$cityName = ndDeliveryQuoteClean($_POST['city'] ?? '', 150);
if (!isset(ND_DELIVERY_WAREHOUSES[$whCode]) || $cityId <= 0 || $cityName === '') {
	$ndDcAnswer(array('ok' => false, 'error' => 'Выберите город из списка подсказок.'));
}
if (!$ndDcRateOk('quote', 30)) {
	$ndDcAnswer(array('ok' => false, 'error' => 'Слишком много расчётов подряд — попробуйте через несколько минут.'));
}
$wh = ND_DELIVERY_WAREHOUSES[$whCode];

$basket = \Bitrix\Sale\Basket::loadItemsForFUser(
	\Bitrix\Sale\Fuser::getId(),
	\Bitrix\Main\Context::getCurrent()->getSite()
);
$items = $basket->getOrderableItems();
$positions = ndDeliveryBasketPositions($items);
$key = ndDeliveryQuoteBasketKey($items);
if (!$positions || $key === '') {
	$ndDcAnswer(array('ok' => false, 'error' => 'Корзина пуста.'));
}

list($code, $data) = ndDeliveryApiRequest('/quote-order', array(
	'from'        => array('atiCityId' => $wh['atiCityId'], 'text' => $wh['text']),
	'to'          => array('atiCityId' => $cityId, 'text' => $cityName),
	'positions'   => $positions,
	'carType'     => 'тент',
	'packingMode' => 'summary',
), 60);

$price = isset($data['price']['costWithVAT']) ? (float)$data['price']['costWithVAT'] : 0;
if ($code !== 200 || empty($data['ok']) || $price <= 0) {
	unset($_SESSION['ND_DELIVERY_QUOTE']);
	$ndDcAnswer(array('ok' => false, 'error' => $code === 422
		? 'Заказ не помещается в одну машину — доставку посчитает менеджер.'
		: $ndDcFail));
}

$truck = (array)($data['truck'] ?? array());
$cargo = (array)($data['cargo'] ?? array());
$unparsed = array_values(array_filter(array_map('strval', (array)($data['unparsedPositions'] ?? array()))));
$notes = array();
if ($unparsed) {
	$notes[] = 'Не распознаны позиции: ' . implode(', ', $unparsed);
}
foreach ((array)($data['warnings'] ?? array()) as $w) {
	if (is_scalar($w) && (string)$w !== '') {
		$notes[] = (string)$w;
	}
}
$itemsText = implode('; ', array_map(function ($p) {
	return $p['name'] . ' — ' . rtrim(rtrim(number_format($p['qty'], 3, '.', ''), '0'), '.') . ' ' . $p['unit'];
}, $positions));

$quote = array(
	'FROM'      => $wh['name'] . ' (' . $wh['text'] . ')',
	'TO'        => $cityName,
	'DISTANCE'  => isset($data['distanceKm']) ? round((float)$data['distanceKm']) . ' км' : '',
	'TRUCK'     => !empty($truck['name']) ? (string)$truck['name'] . ((int)($truck['count'] ?? 1) > 1 ? ' × ' . (int)$truck['count'] : '') : '',
	'WEIGHT'    => isset($cargo['weightKg']) ? round((float)$cargo['weightKg']) . ' кг' : '',
	'ITEMS'     => ndDeliveryQuoteClean($itemsText, 3000),
	'NOTES'     => ndDeliveryQuoteClean(implode(' ', $notes), 1000),
	'VAT'       => true,
	'PRICE'     => round($price, 2),
	'WITH'      => true,
	'KEY'       => $key,
	'TIME'      => time(),
	'WAREHOUSE' => $whCode,
	'CITY_ID'   => $cityId,
);
$_SESSION['ND_DELIVERY_QUOTE'] = $quote;

$ndDcAnswer(array('ok' => true, 'quote' => ndDeliveryQuoteForJs($quote), 'unparsed' => $unparsed));
