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

/* Расчёт, уже сохранённый для этого же состава корзины (перезагрузили
   страницу) — показываем его в итогах сразу, фрейм не ждём. */
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/latitudo_delivery_quote.php';
$ndDcQuote = null;
$ndDcSaved = $_SESSION['ND_DELIVERY_QUOTE'] ?? null;
if (is_array($ndDcSaved)
	&& time() - (int)($ndDcSaved['TIME'] ?? 0) <= 86400
	&& (string)($ndDcSaved['KEY'] ?? '') === ndDeliveryQuoteBasketKey($ndDcBasket->getOrderableItems())) {
	$ndDcQuote = array(
		'to'    => (string)$ndDcSaved['TO'],
		'price' => (float)$ndDcSaved['PRICE'],
		'with'  => !empty($ndDcSaved['WITH']),
	);
}
?>
<style>
.nd-delivery-calc { margin: 32px 0 8px; padding: 20px; border: 1px solid #e6e6e6; border-radius: 4px; background: #fafafa; }
.nd-delivery-calc__title { margin: 0 0 6px; font-size: 20px; font-weight: 700; line-height: 1.3; }
.nd-delivery-calc__note { margin: 0 0 14px; color: #777; font-size: 14px; line-height: 1.4; }
.nd-delivery-calc__frame { display: block; width: 100%; min-height: 320px; border: 0; }
/* Пока фрейм не прислал высоту — его не видно: пустой фрейм давал серое окно,
   а в стартовой высоте мелькал скролл. На его месте — надпись загрузки. */
/* Ширина — полная: по ней фрейм считает свою высоту. */
.nd-delivery-calc__frame.is-loading { height: 0 !important; min-height: 0; visibility: hidden; }
.nd-delivery-calc__loading { display: flex; align-items: center; justify-content: center; min-height: 120px; color: #777; font-size: 14px; }
.nd-delivery-calc__loading[hidden] { display: none; }
.nd-delivery-calc__result { margin-top: 12px; font-size: 16px; line-height: 1.4; }
.nd-delivery-calc__result[hidden] { display: none; }
.nd-delivery-calc__price { font-weight: 700; }
@media (max-width: 600px) { .nd-delivery-calc { padding: 14px; } }
/* Доставка в панели «Итого»: строка и галочка под суммой. Классы строки и
   галочки — те же, что у панели (.nd-total__row, .nd-total__check). */
#basket-root .nd-dq { margin: 16px 0 0; }
#basket-root .nd-total__sum-value[hidden] { display: none; }
#basket-root .nd-dq .nd-total__row-name { white-space: normal; }
#basket-root .nd-dq .nd-total__check { margin-top: 8px; }
</style>
<div class="nd-delivery-calc" id="ndDeliveryCalc">
	<h2 class="nd-delivery-calc__title">Расчёт доставки</h2>
	<p class="nd-delivery-calc__note">Укажите город или адрес — посчитаем доставку товаров из корзины. Точную стоимость подтвердит менеджер.</p>
	<?/* src ставит скрипт ниже, уже на новом месте блока: перенос iframe в DOM
	     перезагружает его, и фрейм грузился бы дважды. */?>
	<iframe id="ndDeliveryCalcFrame" data-src="https://monitor.latitudo-scrum.ru/delivery-frame/" title="Расчёт доставки" scrolling="no" class="nd-delivery-calc__frame is-loading"></iframe>
	<div class="nd-delivery-calc__loading" id="ndDeliveryCalcLoading">Загружаем расчёт доставки…</div>
	<div class="nd-delivery-calc__result" id="ndDeliveryCalcResult" hidden></div>
</div>
<script>
(function () {
	var ORIGIN = 'https://monitor.latitudo-scrum.ru';
	var items = <?=CUtil::PhpToJSObject($ndDcItems)?>;
	var frame = document.getElementById('ndDeliveryCalcFrame');

	/* Место блока — под товарами в левой колонке корзины (Ирина, 28.09.2026).
	   Шаблон корзины не трогаем: блок подключается из /basket/index.php после
	   компонента и сам переезжает в .basket-items-list-outer. На узком экране
	   (≤991px, колонки стоят друг под другом) — после итогов с кнопкой
	   «Заказать», чтобы не отодвигать её вниз. Нет разметки корзины — блок
	   остаётся, где подключён. Место выбираем один раз: при повороте экрана
	   не двигаем, иначе фрейм перезагрузится и расчёт сбросится. */
	(function () {
		var calc = document.getElementById('ndDeliveryCalc');
		var root = document.getElementById('basket-root');
		var row = root && root.querySelector('.basket-items-list.flexbox--row');
		var left = row && row.querySelector('.basket-items-list-outer');
		if (!row || !left) return;
		if (window.matchMedia && window.matchMedia('(max-width: 991px)').matches) {
			row.parentNode.insertBefore(calc, row.nextSibling);
		} else {
			left.appendChild(calc);
		}
	})();
	frame.src = frame.getAttribute('data-src');

	/* Своей прокрутки у фрейма нет (scrolling="no"): высоту он задаёт сам
	   сообщением LATITUDO_HEIGHT. Со стартовыми 320px, пока сообщение не
	   пришло, внутри мелькала полоса прокрутки (Ирина, 28.09.2026). Не пришла
	   высота за 4 с после загрузки — ставим с запасом, чтобы не обрезать. */
	var gotHeight = false;
	var loading = document.getElementById('ndDeliveryCalcLoading');
	function showFrame() {
		frame.classList.remove('is-loading');
		loading.hidden = true;
	}
	frame.addEventListener('load', function () {
		setTimeout(function () {
			if (!gotHeight) { frame.style.height = '900px'; showFrame(); }
		}, 4000);
	});
	var result = document.getElementById('ndDeliveryCalcResult');
	var ready = false;
	var sentKey = '';

	/* ---- Доставка в панели «Итого» (Ирина, 28.09.2026) ----------------------
	   После расчёта в панели итогов под суммой — строка «Доставка — куда: N ₽»
	   и галочка «С доставкой»: с ней сумма доставки прибавляется к «Итого».
	   Панель — мустач-шаблон, корзина перерисовывает её при каждом пересчёте,
	   поэтому строку возвращает MutationObserver. Сумму «Итого» компонент
	   анимирует, пересчитывая цифры в [data-entity="basket-total-price"]: её не
	   переписываем, а при включённой доставке прячем и показываем рядом свою.
	   Расчёт и галочка уходят в сессию (/local/ajax/nd_delivery_quote.php),
	   при оформлении они попадут в комментарий заказа. Состав корзины
	   изменился — расчёт сбрасываем и тут, и в сессии. */
	var SESSID = <?=CUtil::PhpToJSObject(bitrix_sessid())?>;
	var quote = <?=CUtil::PhpToJSObject($ndDcQuote)?>;
	if (quote) quote.key = itemsKey(currentItems());

	function itemsKey(list) {
		/* Количество из PHP приходит строкой, из компонента — числом: приводим. */
		return JSON.stringify(list.map(function (it) { return [String(it.id), parseFloat(it.qty)]; }));
	}

	function saveQuote() {
		var body = new URLSearchParams();
		body.append('sessid', SESSID);
		if (quote) {
			body.append('action', 'save');
			body.append('to', quote.to || '');
			body.append('price', String(quote.price));
			body.append('with', quote['with'] ? 'Y' : 'N');
		} else {
			body.append('action', 'clear');
		}
		fetch('/local/ajax/nd_delivery_quote.php', { method: 'POST', body: body, credentials: 'same-origin' })
			.catch(function () {});
	}

	function goodsSum() {
		var bc = window.BX && BX.Sale && BX.Sale.BasketComponent;
		var t = bc && bc.result && bc.result.TOTAL_RENDER_DATA;
		var v = t ? parseFloat(t.PRICE) : NaN;
		return isNaN(v) ? null : v;
	}

	function setText(node, text) {
		if (node.textContent !== text) node.textContent = text;
	}

	function applyTotal() {
		var root = document.getElementById('basket-root');
		var card = root && root.querySelector('[data-entity="basket-total-block"] .nd-total__card');
		if (!card) return;
		var sum = card.querySelector('.nd-total__sum');
		var orig = sum && sum.querySelector('[data-entity="basket-total-price"]');
		if (!sum || !orig) return;
		var box = card.querySelector('.nd-dq');
		var own = sum.querySelector('.nd-dq-total');
		var goods = goodsSum();

		if (!quote || goods === null) {
			if (box) box.parentNode.removeChild(box);
			if (own) own.parentNode.removeChild(own);
			if (orig.hidden) orig.hidden = false;
			return;
		}

		if (!box) {
			box = document.createElement('div');
			box.className = 'nd-dq';
			box.innerHTML = '<div class="nd-total__row"><span class="nd-total__row-name"></span><span class="nd-total__row-value"></span></div>'
				+ '<label class="nd-total__check"><input type="checkbox" class="nd-total__check-input">'
				+ '<span class="nd-total__check-box"></span><span class="nd-total__check-text">С доставкой</span></label>';
			box.querySelector('input').addEventListener('change', function () {
				if (!quote) return;
				quote['with'] = this.checked;
				applyTotal();
				saveQuote();
			});
			sum.parentNode.insertBefore(box, sum.nextSibling);
		}
		setText(box.querySelector('.nd-total__row-name'), 'Доставка' + (quote.to ? ' — ' + quote.to : ''));
		setText(box.querySelector('.nd-total__row-value'), money(quote.price));
		var check = box.querySelector('input');
		if (check.checked !== !!quote['with']) check.checked = !!quote['with'];

		if (quote['with']) {
			if (!own) {
				own = document.createElement('span');
				own.className = 'nd-total__sum-value nd-dq-total';
				sum.appendChild(own);
			}
			setText(own, money(goods + quote.price));
			if (!orig.hidden) orig.hidden = true;
		} else {
			if (own) own.parentNode.removeChild(own);
			if (orig.hidden) orig.hidden = false;
		}
	}

	function dropQuote() {
		if (!quote) return;
		quote = null;
		applyTotal();
		saveQuote();
	}

	(function () {
		var root = document.getElementById('basket-root');
		var block = root && root.querySelector('[data-entity="basket-total-block"]');
		if (!block || !window.MutationObserver) return;
		var queued = false;
		new MutationObserver(function () {
			if (queued) return;
			queued = true;
			requestAnimationFrame(function () { queued = false; applyTotal(); });
		}).observe(block, { childList: true, subtree: true });
	})();
	applyTotal();

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
			gotHeight = true;
			frame.style.height = d.px + 'px';
			showFrame();
		} else if (d.type === 'LATITUDO_QUOTE') {
			var price = d.ok && d.price ? (d.price.costWithVAT != null ? d.price.costWithVAT : d.price.cost) : null;
			if (price != null) {
				quote = { to: d.to && d.to.text ? String(d.to.text) : '', price: parseFloat(price), 'with': true, key: itemsKey(currentItems()) };
				applyTotal();
				saveQuote();
				var to = d.to && d.to.text ? ' — ' + String(d.to.text) : '';
				result.textContent = '';
				result.appendChild(document.createTextNode('Доставка' + to + ': '));
				var b = document.createElement('span');
				b.className = 'nd-delivery-calc__price';
				b.textContent = money(price);
				result.appendChild(b);
			} else {
				dropQuote();
				result.textContent = 'Не удалось рассчитать доставку автоматически — менеджер посчитает её при оформлении заказа.';
			}
			result.hidden = false;
		}
	});

	setInterval(function () {
		if (quote && itemsKey(currentItems()) !== quote.key) dropQuote();
		pushOrder(false);
	}, 1500);
})();
</script>
