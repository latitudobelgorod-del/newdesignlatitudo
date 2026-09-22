<?php
/**
 * Расчёт доставки LATITUDO на странице корзины (/basket/index.php) — только
 * справка: цена доставки не пишется ни в корзину, ни в заказ, оплаты нет.
 *
 * Сам расчёт — фрейм https://monitor.latitudo-scrum.ru/delivery-frame/
 * (разработчик доставки, архивы latitudo_korzina / latitudo_oformlenie_zakaza).
 * Протокол postMessage взят из его order/index.php:
 *   фрейм → LATITUDO_READY, LATITUDO_HEIGHT {px}, LATITUDO_QUOTE {ok, price, to}
 *   страница → LATITUDO_ORDER {items: [{id, name, qty, unit}]}
 * Фрейм разрешает встраивание только на latitudo.ru / www.latitudo.ru
 * (CSP frame-ancestors): на региональных поддоменах и локальной копии он
 * не откроется, поэтому там блок не выводим.
 *
 * Пока блок в проверке — только с ?delivery_calc=Y (кука на сутки) (22.09.2026).
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

// Только новый дизайн: старый (aspro_next) не трогаем.
if (SITE_TEMPLATE_ID !== 'aspro_next_newdesign') {
	return;
}

$ndDcHost = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
if ($ndDcHost !== 'latitudo.ru' && $ndDcHost !== 'www.latitudo.ru') {
	return;
}

if (isset($_GET['delivery_calc'])) {
	$ndDcOn = ($_GET['delivery_calc'] === 'Y');
	headers_sent() || setcookie('ND_DELIVERY_CALC', $ndDcOn ? 'Y' : '', $ndDcOn ? time() + 86400 : time() - 3600, '/');
} else {
	$ndDcOn = (($_COOKIE['ND_DELIVERY_CALC'] ?? '') === 'Y');
}
if (!$ndDcOn || !\Bitrix\Main\Loader::includeModule('sale')) {
	return;
}

$ndDcItems = array();
$ndDcBasket = \Bitrix\Sale\Basket::loadItemsForFUser(
	\Bitrix\Sale\Fuser::getId(),
	\Bitrix\Main\Context::getCurrent()->getSite()
);
foreach ($ndDcBasket->getOrderableItems() as $ndDcItem) {
	$ndDcUnitRaw = mb_strtolower(trim((string)$ndDcItem->getField('MEASURE_NAME')));
	if ($ndDcUnitRaw === 'м' || mb_strpos($ndDcUnitRaw, 'метр') !== false || mb_strpos($ndDcUnitRaw, 'пог') !== false) {
		$ndDcUnit = 'м';
	} elseif (mb_strpos($ndDcUnitRaw, 'упак') !== false || mb_strpos($ndDcUnitRaw, 'уп.') !== false) {
		$ndDcUnit = 'упак';
	} else {
		$ndDcUnit = 'шт';
	}
	$ndDcItems[] = array(
		'id'   => (int)$ndDcItem->getId(),
		'name' => (string)$ndDcItem->getField('NAME'),
		'qty'  => (float)$ndDcItem->getQuantity(),
		'unit' => $ndDcUnit,
	);
}
if (!$ndDcItems) {
	return;
}
?>
<style>
.nd-delivery-calc { margin: 32px 0 8px; padding: 20px; border: 1px solid #e6e6e6; border-radius: 4px; background: #fafafa; }
.nd-delivery-calc__title { margin: 0 0 6px; font-size: 20px; font-weight: 700; line-height: 1.3; }
.nd-delivery-calc__note { margin: 0 0 14px; color: #777; font-size: 14px; line-height: 1.4; }
.nd-delivery-calc__frame { display: block; width: 100%; min-height: 320px; border: 0; }
.nd-delivery-calc__result { margin-top: 12px; font-size: 16px; line-height: 1.4; }
.nd-delivery-calc__result[hidden] { display: none; }
.nd-delivery-calc__price { font-weight: 700; }
@media (max-width: 600px) { .nd-delivery-calc { padding: 14px; } }
</style>
<div class="nd-delivery-calc" id="ndDeliveryCalc">
	<h2 class="nd-delivery-calc__title">Расчёт доставки</h2>
	<p class="nd-delivery-calc__note">Укажите город или адрес — посчитаем доставку товаров из корзины. Точную стоимость подтвердит менеджер.</p>
	<iframe class="nd-delivery-calc__frame" id="ndDeliveryCalcFrame" src="https://monitor.latitudo-scrum.ru/delivery-frame/" title="Расчёт доставки" loading="lazy"></iframe>
	<div class="nd-delivery-calc__result" id="ndDeliveryCalcResult" hidden></div>
</div>
<script>
(function () {
	var ORIGIN = 'https://monitor.latitudo-scrum.ru';
	var items = <?=CUtil::PhpToJSObject($ndDcItems)?>;
	var frame = document.getElementById('ndDeliveryCalcFrame');
	var result = document.getElementById('ndDeliveryCalcResult');
	var ready = false;
	var sentKey = '';

	/* Количество в корзине меняют без перезагрузки: берём актуальное из
	   BX.Sale.BasketComponent. Удалённая позиция там либо с SHOW_RESTORE
	   («Восстановить»), либо уже не в sortedItems. По DOM не судим: корзина
	   подгружает строки при прокрутке (USE_DYNAMIC_SCROLL). */
	function currentItems() {
		var bc = window.BX && BX.Sale && BX.Sale.BasketComponent;
		if (!bc || !bc.items) return items;
		var sorted = (bc.filter && bc.filter.isActive && bc.filter.isActive()) ? bc.filter.realSortedItems : bc.sortedItems;
		return items.filter(function (it) {
			var live = bc.items[it.id];
			if (live && live.SHOW_RESTORE) return false;
			return !sorted || !sorted.length || sorted.indexOf(String(it.id)) !== -1;
		}).map(function (it) {
			var live = bc.items[it.id];
			return { id: it.id, name: it.name, unit: it.unit, qty: live && live.QUANTITY ? parseFloat(live.QUANTITY) : it.qty };
		});
	}

	function pushOrder(force) {
		if (!ready || !frame.contentWindow) return;
		var list = currentItems();
		var key = JSON.stringify(list.map(function (it) { return [it.id, it.qty]; }));
		if (!force && key === sentKey) return;
		sentKey = key;
		if (!force) result.hidden = true;   // состав изменился — прежняя цена неактуальна
		frame.contentWindow.postMessage({ type: 'LATITUDO_ORDER', autoCalc: false, items: list }, ORIGIN);
	}

	function money(v) {
		return Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽';
	}

	window.addEventListener('message', function (e) {
		if (e.origin !== ORIGIN) return;
		var d = e.data || {};
		if (d.type === 'LATITUDO_READY') {
			ready = true;
			pushOrder(true);
		} else if (d.type === 'LATITUDO_HEIGHT' && d.px) {
			frame.style.height = d.px + 'px';
		} else if (d.type === 'LATITUDO_QUOTE') {
			var price = d.ok && d.price ? (d.price.costWithVAT != null ? d.price.costWithVAT : d.price.cost) : null;
			if (price != null) {
				var to = d.to && d.to.text ? ' — ' + String(d.to.text) : '';
				result.textContent = '';
				result.appendChild(document.createTextNode('Доставка' + to + ': '));
				var b = document.createElement('span');
				b.className = 'nd-delivery-calc__price';
				b.textContent = money(price);
				result.appendChild(b);
			} else {
				result.textContent = 'Не удалось рассчитать доставку автоматически — менеджер посчитает её при оформлении заказа.';
			}
			result.hidden = false;
		}
	});

	setInterval(function () { pushOrder(false); }, 1500);
})();
</script>
