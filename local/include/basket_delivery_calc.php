<?php
/**
 * Расчёт доставки LATITUDO на странице корзины (/basket/index.php) — только
 * справка: цена доставки не пишется ни в корзину, ни в сумму заказа, оплаты нет.
 *
 * С 02.10.2026 считаем через API калькулятора, а не фреймом
 * monitor.latitudo-scrum.ru/delivery-frame/: форма своя и короткая — склад,
 * город (подсказки ATI), кнопка. Состав — то, что лежит в корзине, его в
 * форме не правят; «По габаритам», улица/дом и 3D-схема убраны (схема
 * Ирины 02.10.2026). Запросы к API — с сервера, /local/ajax/nd_delivery_calc.php
 * (ключ в браузер не попадает), он же кладёт расчёт в сессию для заказа и
 * лида. Нет ключа (локальная сборка) — блока нет.
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

// Только новый дизайн: старый (aspro_next) не трогаем.
if (SITE_TEMPLATE_ID !== 'aspro_next_newdesign') {
	return;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/latitudo_delivery_api.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/latitudo_delivery_quote.php';

if (ndDeliveryApiKey() === '' || !\Bitrix\Main\Loader::includeModule('sale')) {
	return;
}

$ndDcItems = array();
$ndDcBasket = \Bitrix\Sale\Basket::loadItemsForFUser(
	\Bitrix\Sale\Fuser::getId(),
	\Bitrix\Main\Context::getCurrent()->getSite()
);
foreach ($ndDcBasket->getOrderableItems() as $ndDcItem) {
	$ndDcItems[] = array('id' => (int)$ndDcItem->getId(), 'qty' => (float)$ndDcItem->getQuantity());
}
if (!$ndDcItems) {
	return;
}

/* Расчёт, уже сохранённый для этого же состава корзины (перезагрузили
   страницу) — показываем сразу. */
$ndDcQuote = null;
$ndDcSaved = $_SESSION['ND_DELIVERY_QUOTE'] ?? null;
if (is_array($ndDcSaved)
	&& time() - (int)($ndDcSaved['TIME'] ?? 0) <= 86400
	&& (string)($ndDcSaved['KEY'] ?? '') === ndDeliveryQuoteBasketKey($ndDcBasket->getOrderableItems())) {
	$ndDcQuote = ndDeliveryQuoteForJs($ndDcSaved);
}

/* Склад по умолчанию — по городу сайта: krasnodar.latitudo.ru и rostov → Краснодар,
   belgorod → Белгород, vrn → Воронеж.
   Остальные города и основной домен — Москва. Покупатель может сменить. */
$ndDcHost = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
$ndDcSub = strpos($ndDcHost, '.') !== false ? substr($ndDcHost, 0, strpos($ndDcHost, '.')) : '';
$ndDcDefault = array('krasnodar' => 'krd', 'krd' => 'krd', 'rostov' => 'krd', 'belgorod' => 'bel', 'vrn' => 'vrn')[$ndDcSub] ?? 'msk';
if ($ndDcQuote && isset(ND_DELIVERY_WAREHOUSES[$ndDcQuote['warehouse']])) {
	$ndDcDefault = $ndDcQuote['warehouse'];
}
?>
<style>
.nd-delivery-calc { margin: 32px 0 8px; padding: 24px; border: 1px solid rgba(82, 82, 100, .15); border-radius: 8px; background: #fff; font-family: 'Nunito Sans', 'Nunito Fallback', Arial, sans-serif; color: #101014; }
.nd-delivery-calc__title { margin: 0 0 6px; font-size: 20px; font-weight: 700; line-height: 1.3; }
.nd-delivery-calc__note { margin: 0 0 16px; color: #8f8f9a; font-size: 14px; line-height: 1.4; }
.nd-delivery-calc__fields { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); gap: 12px; }
.nd-delivery-calc__field { position: relative; display: flex; flex-direction: column; gap: 6px; min-width: 0; }
#ndDeliveryCalc .nd-delivery-calc__label { display: block; margin: 0; padding: 0; font-size: 13px; font-weight: 400; line-height: 18px; color: #8f8f9a; }
.nd-delivery-calc__input { width: 100%; height: 48px; padding: 0 14px; border: 1px solid rgba(82, 82, 100, .3); border-radius: 4px; background: #fff; font: inherit; font-size: 16px; color: #101014; box-sizing: border-box; }
.nd-delivery-calc__input:focus { outline: none; border-color: #c60000; }
.nd-delivery-calc__input.is-error { border-color: #c60000; }
.nd-delivery-calc__list { position: absolute; z-index: 20; top: 100%; left: 0; right: 0; margin: 4px 0 0; padding: 4px 0; list-style: none; max-height: 280px; overflow-y: auto; border: 1px solid rgba(82, 82, 100, .2); border-radius: 4px; background: #fff; box-shadow: 0 8px 24px rgba(16, 16, 20, .12); }
.nd-delivery-calc__list[hidden] { display: none; }
.nd-delivery-calc__option { margin: 0; padding: 8px 14px; cursor: pointer; font-size: 15px; line-height: 20px; }
.nd-delivery-calc__option small { display: block; color: #8f8f9a; font-size: 13px; }
.nd-delivery-calc__option.is-active, .nd-delivery-calc__option:hover { background: #f4f4f6; }
.nd-delivery-calc__btn { display: flex; align-items: center; justify-content: center; width: 100%; height: 52px; margin: 16px 0 0; border: 0; border-radius: 2px; background: #c60000; color: #fff; font: inherit; font-size: 16px; font-weight: 700; cursor: pointer; transition: background-color .2s; }
.nd-delivery-calc__btn:hover { background: #a80000; }
.nd-delivery-calc__btn[disabled] { opacity: .6; cursor: default; }
.nd-delivery-calc__result { margin: 16px 0 0; padding: 16px; border-radius: 4px; background: #f6f6f8; }
.nd-delivery-calc__result[hidden] { display: none; }
.nd-delivery-calc__result.is-stale { opacity: .45; transition: opacity .2s; }
.nd-delivery-calc__price { font-size: 24px; font-weight: 800; line-height: 32px; }
.nd-delivery-calc__price small { font-size: 14px; font-weight: 500; color: #8f8f9a; }
.nd-delivery-calc__facts { margin: 4px 0 0; font-size: 14px; line-height: 20px; color: #525264; }
.nd-delivery-calc__warn { margin: 8px 0 0; font-size: 14px; line-height: 20px; color: #a85a00; }
.nd-delivery-calc__error { font-size: 15px; line-height: 22px; color: #525264; }
.nd-delivery-calc__disclaimer { margin: 12px 0 0; font-size: 12px; line-height: 16px; color: #8f8f9a; }
/* Тема красит поля и списки своими правилами (select ниже и серый, у li —
   точка-маркер): поля — через id блока, чтобы перебить. */
#ndDeliveryCalc .nd-delivery-calc__input { height: 48px; margin: 0; padding: 0 14px; border: 1px solid rgba(82, 82, 100, .3); border-radius: 4px; background-color: #fff; box-shadow: none; font-size: 16px; line-height: 46px; color: #101014; }
#ndDeliveryCalc select.nd-delivery-calc__input { padding-right: 36px; -webkit-appearance: none; appearance: none; cursor: pointer;
	background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' fill='none' stroke='%23525264' stroke-width='1.5'/%3E%3C/svg%3E") no-repeat right 14px center; }
#ndDeliveryCalc .nd-delivery-calc__input:focus,
#ndDeliveryCalc .nd-delivery-calc__input.is-error { border-color: #c60000; }
#ndDeliveryCalc .nd-delivery-calc__option { list-style: none; padding: 8px 14px; }
#ndDeliveryCalc .nd-delivery-calc__option:before { content: none; display: none; }
/* В правой колонке узко — склад и город друг под другом, отступы как
   между карточкой итогов и блоком «Уточните наличие». */
.nd-delivery-calc.nd-delivery-calc--aside { margin: 24px 0 0; }
.nd-delivery-calc--aside .nd-delivery-calc__fields { grid-template-columns: 1fr; }
@media (max-width: 600px) {
	.nd-delivery-calc { padding: 16px; }
	.nd-delivery-calc__fields { grid-template-columns: 1fr; }
}
/* Доставка в панели «Итого» (схема Ирины 02.10.2026): строка с галочкой
   «Доставка» и ценой — под «Товары», над «Итого»; куда везём — под кнопкой
   «Заказать» (.nd-total__meta). Классы — те же, что у панели. */
#basket-root .nd-dq { margin: 12px 0 0; }
#basket-root .nd-dq .nd-total__row { align-items: center; }
#basket-root .nd-total__sum-value[hidden] { display: none; }
#basket-root .nd-dq .nd-total__check { margin: 0; gap: 8px; color: #8f8f9a; }
</style>
<div class="nd-delivery-calc" id="ndDeliveryCalc">
	<h2 class="nd-delivery-calc__title">Расчёт доставки</h2>
	<p class="nd-delivery-calc__note">Выберите склад и город — посчитаем доставку товаров из корзины.</p>
	<form class="nd-delivery-calc__form" id="ndDeliveryCalcForm" autocomplete="off" novalidate>
		<div class="nd-delivery-calc__fields">
			<div class="nd-delivery-calc__field">
				<label class="nd-delivery-calc__label" for="ndDeliveryCalcWh">Склад отгрузки</label>
				<?/* iks-ignore — иначе тема подменяет список своим виджетом ikSelect (main.js, initSelects). */?>
				<select class="nd-delivery-calc__input iks-ignore" id="ndDeliveryCalcWh">
					<?foreach (ND_DELIVERY_WAREHOUSES as $ndDcCode => $ndDcWh):?>
						<option value="<?=$ndDcCode?>"<?=($ndDcCode === $ndDcDefault ? ' selected' : '')?>><?=htmlspecialcharsbx($ndDcWh['name'])?></option>
					<?endforeach;?>
				</select>
			</div>
			<div class="nd-delivery-calc__field">
				<label class="nd-delivery-calc__label" for="ndDeliveryCalcCity">Город доставки</label>
				<input type="text" class="nd-delivery-calc__input" id="ndDeliveryCalcCity" placeholder="Начните вводить город…"
					role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="ndDeliveryCalcList"
					value="<?=htmlspecialcharsbx($ndDcQuote['to'] ?? '')?>">
				<ul class="nd-delivery-calc__list" id="ndDeliveryCalcList" role="listbox" hidden></ul>
			</div>
		</div>
		<button type="submit" class="nd-delivery-calc__btn" id="ndDeliveryCalcBtn">Рассчитать доставку</button>
	</form>
	<div class="nd-delivery-calc__result" id="ndDeliveryCalcResult" aria-live="polite" hidden></div>
	<p class="nd-delivery-calc__disclaimer">Стоимость предварительная и не является публичной офертой. Точную цену подтвердит менеджер.</p>
</div>
<script>
(function () {
	var URL_CALC = '/local/ajax/nd_delivery_calc.php';
	var URL_FLAG = '/local/ajax/nd_delivery_quote.php';
	var SESSID = <?=CUtil::PhpToJSObject(bitrix_sessid())?>;
	var items = <?=CUtil::PhpToJSObject($ndDcItems)?>;
	var quote = <?=CUtil::PhpToJSObject($ndDcQuote)?>;

	var form = document.getElementById('ndDeliveryCalcForm');
	var whSelect = document.getElementById('ndDeliveryCalcWh');
	var cityInput = document.getElementById('ndDeliveryCalcCity');
	var list = document.getElementById('ndDeliveryCalcList');
	var btn = document.getElementById('ndDeliveryCalcBtn');
	var result = document.getElementById('ndDeliveryCalcResult');

	/* PhpToJSObject отдаёт числа и логические строками: '8737' + сумма
	   товаров давало склейку «345 708 737 ₽» (Ирина, 28.09.2026). */
	function normalize(q) {
		if (!q) return null;
		q.price = parseFloat(q.price) || 0;
		q['with'] = q['with'] === true || q['with'] === 'true' || q['with'] === '1' || q['with'] === 1 || q['with'] === 'Y';
		q.city_id = parseInt(q.city_id, 10) || 0;
		return q;
	}
	quote = normalize(quote);
	var city = quote && quote.city_id ? { id: quote.city_id, name: quote.to } : null;

	/* Место блока — в правой колонке сразу под карточкой «Ваш заказ», над
	   «Уточните наличие» (Ирина, 02.10.2026: внизу под товарами его не
	   видели). На телефоне колонки идут друг под другом — там это тоже место
	   сразу под итогами. Шаблон корзины не трогаем: блок подключается из
	   /basket/index.php после компонента и переезжает сам. Нет разметки
	   корзины — остаётся, где подключён. */
	(function () {
		var calc = document.getElementById('ndDeliveryCalc');
		var root = document.getElementById('basket-root');
		var total = root && root.querySelector('[data-entity="basket-total-block"]');
		if (!total) return;
		/* 992–1024px: тема прибивает правую колонку (.basket-total-outer —
		   position: fixed, см. newdesign-basket.css), и с блоком она выше экрана —
		   наезжала на товары. Там блок — под товарами, как было до 02.10.2026. */
		var w = window.innerWidth || document.documentElement.clientWidth;
		var left = root.querySelector('.basket-items-list-outer');
		if (w >= 992 && w <= 1024 && left) {
			left.appendChild(calc);
			return;
		}
		total.parentNode.insertBefore(calc, total.nextSibling);
		calc.classList.add('nd-delivery-calc--aside');
	})();

	function post(url, data) {
		var body = new URLSearchParams();
		body.append('sessid', SESSID);
		Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
		return fetch(url, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) { return r.json(); });
	}

	function money(v) {
		return Math.round(v).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' ₽';
	}

	function setText(node, text) {
		if (node.textContent !== text) node.textContent = text;
	}

	/* ---- Подсказки городов --------------------------------------------- */
	var suggest = [], active = -1, timer = 0, lastQuery = '';

	function closeList() {
		list.hidden = true;
		cityInput.setAttribute('aria-expanded', 'false');
		active = -1;
	}

	function renderList() {
		list.textContent = '';
		suggest.forEach(function (s, i) {
			var li = document.createElement('li');
			li.className = 'nd-delivery-calc__option' + (i === active ? ' is-active' : '');
			li.setAttribute('role', 'option');
			li.textContent = s.name;
			if (s.region && s.region !== s.name) {
				var sm = document.createElement('small');
				sm.textContent = s.region;
				li.appendChild(sm);
			}
			li.addEventListener('mousedown', function (e) { e.preventDefault(); pick(i); });
			list.appendChild(li);
		});
		list.hidden = !suggest.length;
		cityInput.setAttribute('aria-expanded', suggest.length ? 'true' : 'false');
	}

	function pick(i) {
		var s = suggest[i];
		if (!s) return;
		city = { id: s.id, name: s.name + (s.region && s.region !== s.name ? ', ' + s.region : '') };
		cityInput.value = city.name;
		cityInput.classList.remove('is-error');
		closeList();
		calculate();
	}

	cityInput.addEventListener('input', function () {
		city = null;
		var q = cityInput.value.trim();
		clearTimeout(timer);
		if (q.length < 2) { suggest = []; closeList(); return; }
		timer = setTimeout(function () {
			lastQuery = q;
			post(URL_CALC, { action: 'city', query: q }).then(function (d) {
				if (q !== lastQuery) return;
				suggest = (d && d.items) || [];
				active = suggest.length ? 0 : -1;
				renderList();
			}).catch(function () {});
		}, 250);
	});

	cityInput.addEventListener('keydown', function (e) {
		if (list.hidden) return;
		if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
			e.preventDefault();
			active = (active + (e.key === 'ArrowDown' ? 1 : -1) + suggest.length) % suggest.length;
			renderList();
		} else if (e.key === 'Enter') {
			e.preventDefault();
			pick(active < 0 ? 0 : active);
		} else if (e.key === 'Escape') {
			closeList();
		}
	});
	cityInput.addEventListener('blur', function () { setTimeout(closeList, 150); });

	/* ---- Расчёт --------------------------------------------------------- */
	var busy = false, pending = false;

	function showResult(q, unparsed) {
		result.textContent = '';
		var price = document.createElement('div');
		price.className = 'nd-delivery-calc__price';
		price.appendChild(document.createTextNode(money(q.price) + ' '));
		var vat = document.createElement('small');
		vat.textContent = 'с НДС';
		price.appendChild(vat);
		result.appendChild(price);
		var facts = [q.distance, q.truck, q.weight].filter(Boolean).join(' · ');
		if (facts) {
			var f = document.createElement('div');
			f.className = 'nd-delivery-calc__facts';
			f.textContent = facts;
			result.appendChild(f);
		}
		var stock = stockNote();
		if (stock) {
			var st = document.createElement('div');
			st.className = 'nd-delivery-calc__warn nd-delivery-calc__stock';
			st.textContent = stock;
			result.appendChild(st);
		}
		if (unparsed && unparsed.length) {
			var w = document.createElement('div');
			w.className = 'nd-delivery-calc__warn';
			w.textContent = 'Не все товары учтены в расчёте — точную стоимость назовёт менеджер.';
			result.appendChild(w);
		}
		result.hidden = false;
	}

	/* Наличие (Ирина, 02.10.2026): часть товаров в наличии, часть под заказ —
	   предупреждаем под ценой. Признак тот же, что у плашки «В наличии» над
	   фото: SHOW_STORES из mutator.php (положительный остаток хоть на одном
	   складе), его компонент обновляет при каждом пересчёте корзины. */
	function stockNote() {
		var bc = window.BX && BX.Sale && BX.Sale.BasketComponent;
		if (!bc || !bc.items) return '';
		var inStock = 0, onOrder = 0;
		currentItems().forEach(function (it) {
			var live = bc.items[it.id];
			if (!live) return;
			if (live.SHOW_STORES) inStock++; else onOrder++;
		});
		if (!onOrder) return '';
		return inStock
			? 'Часть товаров есть в наличии, часть — под заказ. Точные сроки поставки уточните у менеджера.'
			: 'Товары в корзине — под заказ. Точные сроки поставки уточните у менеджера.';
	}

	function showError(text) {
		result.textContent = '';
		var e = document.createElement('div');
		e.className = 'nd-delivery-calc__error';
		e.textContent = text;
		result.appendChild(e);
		result.hidden = false;
	}

	function calculate() {
		if (!city) {
			cityInput.classList.add('is-error');
			cityInput.focus();
			showError('Выберите город из списка подсказок.');
			return;
		}
		if (busy) { pending = true; return; }
		busy = true;
		btn.disabled = true;
		setText(btn, 'Считаем…');
		post(URL_CALC, { action: 'quote', warehouse: whSelect.value, city_id: city.id, city: city.name })
			.then(function (d) {
				if (d && d.ok && d.quote) {
					quote = normalize(d.quote);
					quote.key = itemsKey(currentItems());
					showResult(quote, d.unparsed);
				} else {
					quote = null;
					showError((d && d.error) || 'Не удалось рассчитать доставку автоматически — менеджер посчитает её при оформлении заказа.');
				}
			})
			.catch(function () {
				quote = null;
				showError('Не удалось рассчитать доставку автоматически — менеджер посчитает её при оформлении заказа.');
			})
			.then(function () {
				busy = false;
				btn.disabled = false;
				setText(btn, 'Рассчитать доставку');
				result.classList.remove('is-stale');
				applyTotal();
				try { window.dispatchEvent(new Event('ndDeliveryCalcChange')); } catch (e) {}
				if (pending) { pending = false; calculate(); }
			});
	}

	form.addEventListener('submit', function (e) { e.preventDefault(); calculate(); });
	whSelect.addEventListener('change', function () { if (city) calculate(); });

	/* ---- Доставка в панели «Итого» (Ирина, 28.09.2026) ----------------------
	   Строка с галочкой «Доставка» и ценой под «Товары», маршрут под кнопкой.
	   Панель — мустач-шаблон, корзина перерисовывает её при каждом пересчёте,
	   поэтому строку возвращает MutationObserver. Сумму «Итого» компонент
	   анимирует в [data-entity="basket-total-price"]: её не переписываем, а при
	   включённой доставке прячем и показываем рядом свою. */
	function goodsSum() {
		var bc = window.BX && BX.Sale && BX.Sale.BasketComponent;
		var t = bc && bc.result && bc.result.TOTAL_RENDER_DATA;
		/* Сумма уменьшилась — компонент до конца анимации держит в PRICE прежнюю,
		   а новую кладёт в PRICE_NEW (Ирина, 01.10.2026). */
		var v = t ? parseFloat(t.PRICE_NEW != null ? t.PRICE_NEW : t.PRICE) : NaN;
		return isNaN(v) ? null : v;
	}

	function applyTotal() {
		var root = document.getElementById('basket-root');
		var card = root && root.querySelector('[data-entity="basket-total-block"] .nd-total__card');
		if (!card) return;
		var sum = card.querySelector('.nd-total__sum');
		var orig = sum && sum.querySelector('[data-entity="basket-total-price"]');
		if (!sum || !orig) return;
		var box = card.querySelector('.nd-dq');
		var route = card.querySelector('.nd-dq-route');
		var own = sum.querySelector('.nd-dq-total');
		var goods = goodsSum();

		if (!quote || goods === null) {
			if (box) box.parentNode.removeChild(box);
			if (route) route.parentNode.removeChild(route);
			if (own) own.parentNode.removeChild(own);
			if (orig.hidden) orig.hidden = false;
			return;
		}

		if (!box) {
			box = document.createElement('div');
			box.className = 'nd-dq';
			box.innerHTML = '<div class="nd-total__row"><label class="nd-total__check nd-total__row-name"><input type="checkbox" class="nd-total__check-input">'
				+ '<span class="nd-total__check-box"></span><span class="nd-total__check-text">Доставка</span></label>'
				+ '<span class="nd-total__row-value"></span></div>';
			box.querySelector('input').addEventListener('change', function () {
				if (!quote) return;
				quote['with'] = this.checked;
				applyTotal();
				post(URL_FLAG, { action: 'with', value: quote['with'] ? 'Y' : 'N' }).catch(function () {});
			});
			sum.parentNode.insertBefore(box, sum);
		}
		/* Под кнопкой: «Доставка: склад Белгород → Ростов-на-Дону», без
		   галочки — «Самовывоз со склада» (Ирина, 02.10.2026). */
		var meta = card.querySelector('.nd-total__meta');
		if (meta && !route) {
			route = document.createElement('div');
			route.className = 'nd-dq-route';
			meta.appendChild(route);
		}
		var fromName = String(quote.from || '').replace(/\s*\(.*\)$/, '');
		var toName = String(quote.to || '').replace(/,\s*Россия(?=,|$)/, '');
		var path = [fromName ? 'склад ' + fromName : '', toName].filter(Boolean).join(' → ');
		if (route) setText(route, quote['with'] ? 'Доставка' + (path ? ': ' + path : '') : 'Самовывоз со склада');
		setText(box.querySelector('.nd-total__row-value'), quote.pending ? 'считаем…' : money(quote.price));
		var check = box.querySelector('input');
		if (check.checked !== !!quote['with']) check.checked = !!quote['with'];

		/* Пока пересчитываем — «Итого» штатное (товары), как его сразу
		   показывает корзина; строка доставки — «считаем…». */
		if (quote['with'] && !quote.pending) {
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

	/* ---- Состав корзины поменялся ----------------------------------------
	   Количество меняют без перезагрузки: актуальное берём из
	   BX.Sale.BasketComponent. Удалённая позиция там либо с SHOW_RESTORE
	   («Восстановить»), либо уже не в sortedItems. По DOM не судим: корзина
	   подгружает строки при прокрутке (USE_DYNAMIC_SCROLL). Город выбран —
	   пересчитываем сами, иначе прежняя цена не годится и в заказ не пойдёт
	   (сервер сверяет состав). */
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
			return { id: it.id, qty: live && live.QUANTITY ? parseFloat(live.QUANTITY) : it.qty };
		});
	}

	function itemsKey(list) {
		/* Количество из PHP приходит строкой, из компонента — числом: приводим. */
		return JSON.stringify(list.map(function (it) { return [String(it.id), parseFloat(it.qty)]; }));
	}

	if (quote) {
		quote.key = itemsKey(currentItems());
		showResult(quote, null);
	}
	applyTotal();

	/* Пересчёт после смены количества (Ирина, 02.10.2026: «Итого» менялось
	   сразу, а доставка — секунд через пять). Как только количество поменялось
	   на странице — помечаем цену доставки «считаем…», чтобы не висела
	   прежняя. Считаем, как только сервер корзины ответил (компонент
	   подменяет bc.result) — раньше нельзя: сервер расчёта берёт состав из
	   сохранённой корзины. Ответа нет 3 с — считаем всё равно. */
	var lastKey = itemsKey(currentItems()), changed = false, changedAt = 0, touchedAt = 0;
	var lastResult = window.BX && BX.Sale && BX.Sale.BasketComponent ? BX.Sale.BasketComponent.result : null;

	function markPending(on) {
		if (quote) quote.pending = on;
		result.classList.toggle('is-stale', !!on);
		applyTotal();
	}

	function afterChange() {
		changed = false;
		if (city) {
			calculate();
		} else if (quote) {
			quote = null;
			result.hidden = true;
			markPending(false);
			post(URL_FLAG, { action: 'clear' }).catch(function () {});
		}
	}

	/* Нажали «+»/«−» или правят число — «считаем…» сразу, не дожидаясь сервера. */
	(function () {
		var root = document.getElementById('basket-root');
		if (!root) return;
		function touch(e) {
			var t = e.target;
			if (!city || !quote || !t || !t.closest) return;
			if (t.closest('[data-entity="basket-item-quantity-plus"], [data-entity="basket-item-quantity-minus"], [data-entity="basket-item-quantity-field"], [data-entity="basket-item-delete"]')) {
				touchedAt = Date.now();
				markPending(true);
			}
		}
		root.addEventListener('click', touch);
		root.addEventListener('change', touch);
	})();

	/* ---- Склад отгрузки в плашках товаров (Ирина, 05.10.2026) ---------------
	   Покупатель выбрал склад в расчёте — у товаров, которых на этом складе
	   хватает на количество в корзине, плашка склада обводится яркой рамкой.
	   До первого выбора склада (или расчёта) не подсвечиваем: склад по
	   умолчанию покупатель ещё не выбирал. Корзина перерисовывает строки при
	   каждом пересчёте — подсветку ставим заново по наблюдателю. */
	var whTouched = !!quote;
	function markStores() {
		var root = document.getElementById('basket-root');
		if (!root) return;
		var opt = whSelect.options[whSelect.selectedIndex];
		var whName = whTouched && opt ? opt.text.trim().toLowerCase() : '';
		var bc = window.BX && BX.Sale && BX.Sale.BasketComponent;
		root.querySelectorAll('[data-entity="basket-item"]').forEach(function (row) {
			var id = row.getAttribute('data-id');
			var live = bc && bc.items ? bc.items[id] : null;
			var qty = live && live.QUANTITY ? parseFloat(live.QUANTITY) : 0;
			row.querySelectorAll('.nd-basket-stock__chip[data-nd-store]').forEach(function (chip) {
				var name = (chip.getAttribute('data-nd-store') || '').toLowerCase();
				var amount = parseFloat(chip.getAttribute('data-nd-amount')) || 0;
				var on = whName !== '' && name.indexOf(whName) !== -1 && amount > 0 && amount >= qty;
				if (chip.classList.contains('is-picked') !== on) chip.classList.toggle('is-picked', on);
			});
		});
	}
	whSelect.addEventListener('change', function () { whTouched = true; markStores(); });
	form.addEventListener('submit', function () { whTouched = true; markStores(); });
	(function () {
		var list = document.getElementById('basket-item-list');
		if (!list || !window.MutationObserver) return;
		var queued = false;
		new MutationObserver(function () {
			if (queued) return;
			queued = true;
			requestAnimationFrame(function () { queued = false; markStores(); });
		}).observe(list, { childList: true, subtree: true });
	})();
	markStores();

	setInterval(function () {
		var bc = window.BX && BX.Sale && BX.Sale.BasketComponent;
		var key = itemsKey(currentItems());
		if (key !== lastKey) {
			lastKey = key;
			changed = true;
			changedAt = Date.now();
			if (bc) lastResult = bc.result;
			if (city) markPending(true);
		}
		/* Нажали, а количество не поменялось (упёрлись в предел) — снимаем отметку. */
		if (!changed && !busy && quote && quote.pending && Date.now() - touchedAt > 3000) markPending(false);
		if (!changed) return;
		if ((bc && bc.result !== lastResult) || Date.now() - changedAt > 3000) {
			if (bc) lastResult = bc.result;
			afterChange();
		}
	}, 200);
})();
</script>
