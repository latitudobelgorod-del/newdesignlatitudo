/* Сравнение товаров: кнопки «Сравнить» в плитках и карточке, счётчики в шапке
   и в нижней панели телефона, уведомление после добавления.

   Подключается только группе «Тестирование сравнения» (header.php шаблона),
   кнопки на место меток ставит сервер (ndCompareTokens). Список хранит сервер
   (/local/ajax/compare.php: сессия + cookie на 30 дней), здесь — только его
   копия для отрисовки состояния. Начальное состояние — window.ndCompareInit. */
(function () {
	'use strict';

	var init = window.ndCompareInit || {};
	var ids = (init.ids || []).map(Number);
	// товар → выбранная длина (торговое предложение), 0 — без неё
	var offers = init.offers || {};

	/* Выбранная сейчас длина рядом с кнопкой: «В корзину» карточки или плитки
	   держит в data-item текущее предложение (у товара без длин — сам товар). */
	function currentOffer(btn) {
		var pid = Number(btn.getAttribute('data-nd-cmp'));
		var box = btn.closest('.item_block, .catalog_detail');
		if (!box) return 0;
		var carts = box.querySelectorAll('.to-cart[data-item], .in-cart[data-item]');
		for (var i = 0; i < carts.length; i++) {
			if (!carts[i].offsetParent) continue;
			var oid = Number(carts[i].getAttribute('data-item'));
			return oid && oid !== pid ? oid : 0;
		}
		return 0;
	}

	/* В сравнении ли товар — и именно эта длина. Другая длина того же товара
	   кнопку не подсвечивает: нажатие заменит длину в сравнении. */
	function has(id, btn) {
		id = Number(id);
		if (ids.indexOf(id) === -1) return false;
		var saved = Number(offers[id] || 0);
		if (!btn || !saved) return true;
		var cur = currentOffer(btn);
		return !cur || cur === saved;
	}

	/* Цели Яндекс Метрики: compare_add, compare_open, compare_cart, compare_request.
	   Счётчик — init.metrika (latitudo_compare.php). Нет Метрики — молча. */
	function goal(name) {
		try {
			if (init.metrika && typeof window.ym === 'function') window.ym(init.metrika, 'reachGoal', name);
		} catch (e) {}
	}

	function request(action, data) {
		var body = new URLSearchParams();
		body.append('action', action);
		body.append('sessid', init.sessid || '');
		Object.keys(data || {}).forEach(function (k) {
			body.append(k, data[k]);
		});
		return fetch(init.url, {method: 'POST', body: body, credentials: 'same-origin'})
			.then(function (r) {
				if (!r.ok) throw new Error('compare ' + r.status);
				return r.json();
			})
			.then(function (res) {
				if (res && res.ok) {
					ids = (res.ids || []).map(Number);
					offers = res.offers || {};
					paint();
				}
				return res;
			});
	}

	/* Текст меняем только если он другой: иначе каждая запись — новая мутация,
	   и наблюдатель за страницей (ниже) крутился бы по кругу. */
	function setText(el, text) {
		if (el && el.textContent !== text) el.textContent = text;
	}

	/* В карточке кнопка — квадрат с иконкой в ряду покупки, первой
	   (Ирина, 15.10.2026): сравнение | счётчик | В корзину.
	   Рядов в разметке два (товар и торговое предложение) — берём видимый;
	   тема перерисовывает ряд при смене предложения, поэтому зовём на каждую
	   мутацию (paint) и ставим заново, если кнопку вынесло. */
	function placeDetail() {
		var btn = document.querySelector('.nd-cmp-btn--detail');
		if (!btn) return;
		var h1 = document.querySelector('.catalog_detail.element_newdesign > h1');
		// телефон с нижней панелью покупки: кнопка в её строке «Рассчитать с менеджером», последней —
		// панель всегда на виду, а у заголовка кнопка уезжала при прокрутке (Ирина, 22.10.2026)
		var barRow = window.innerWidth < 768 && document.querySelector('#nd-pd-buybar .nd-pd__calc');
		if (barRow) {
			if (h1) h1.classList.remove('nd-has-cmp');
			if (btn.style.top) btn.style.top = '';
			if (barRow.lastElementChild !== btn) barRow.appendChild(btn);
			return;
		}
		// телефон: ряд покупки узкий — кнопка справа от заголовка (в сам H1 нельзя:
		// тема при загрузке переписывает его содержимое)
		if (h1 && window.innerWidth < 768) {
			if (h1.nextElementSibling !== btn) h1.parentNode.insertBefore(btn, h1.nextSibling);
			if (!h1.classList.contains('nd-has-cmp')) h1.classList.add('nd-has-cmp');
			var top = (h1.offsetTop + h1.offsetHeight - btn.offsetHeight) + 'px';
			if (btn.style.top !== top) btn.style.top = top;
			return;
		}
		if (h1) h1.classList.remove('nd-has-cmp');
		btn.style.top = '';
		var rows = document.querySelectorAll('.catalog_detail .info_item .buy_block .counter_wrapp');
		for (var i = 0; i < rows.length; i++) {
			var cart = rows[i].querySelector(':scope > .button_block');
			if (!cart || !rows[i].offsetParent) continue;
			// первой в ряду — слева от счётчика (Ирина, 15.10.2026)
			if (rows[i].firstElementChild !== btn) rows[i].insertBefore(btn, rows[i].firstElementChild);
			return;
		}
	}

	window.addEventListener('resize', placeDetail);

	/* Кнопки: состояние «в сравнении» видно сразу. */
	function paint() {
		placeDetail();
		var buttons = document.querySelectorAll('[data-nd-cmp]');
		for (var i = 0; i < buttons.length; i++) {
			var on = has(buttons[i].getAttribute('data-nd-cmp'), buttons[i]);
			buttons[i].classList.toggle('is-active', on);
			buttons[i].setAttribute('aria-pressed', on ? 'true' : 'false');
			var title = on ? 'Убрать из сравнения' : 'Добавить к сравнению';
			if (buttons[i].title !== title) buttons[i].title = title;
			if (buttons[i].hasAttribute('aria-label') && buttons[i].getAttribute('aria-label') !== title) buttons[i].setAttribute('aria-label', title);
			var text = buttons[i].querySelector('.nd-cmp-btn__text');
			setText(text, on ? 'В сравнении' : 'Сравнить');
		}
		var counts = document.querySelectorAll('[data-nd-cmp-count]');
		for (var j = 0; j < counts.length; j++) {
			setText(counts[j], String(ids.length));
			counts[j].classList.toggle('empted', !ids.length);
		}
	}

	var toastTimer = null;
	function toast(last) {
		var el = document.querySelector('.nd-cmp-toast');
		if (!el) {
			el = document.createElement('div');
			el.className = 'nd-cmp-toast';
			el.setAttribute('role', 'status');
			document.body.appendChild(el);
		}
		var href = init.page + '?tab=' + encodeURIComponent(last.KEY);
		el.innerHTML = '';
		el.appendChild(document.createTextNode('Добавлено к сравнению · '));
		var a = document.createElement('a');
		a.href = href;
		a.rel = 'nofollow';
		a.textContent = 'Сравнить (' + last.COUNT + ')';
		el.appendChild(a);
		el.classList.add('is-visible');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () {
			el.classList.remove('is-visible');
		}, 4000);
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest ? e.target.closest('[data-nd-cmp]') : null;
		if (!btn) return;
		e.preventDefault();
		e.stopPropagation();
		if (btn.classList.contains('is-busy')) return;
		var id = btn.getAttribute('data-nd-cmp');
		var adding = !has(id, btn);
		btn.classList.add('is-busy');
		request(adding ? 'add' : 'remove', adding ? {id: id, offer: currentOffer(btn)} : {id: id})
			.then(function (res) {
				if (adding && res && res.last) {
					toast(res.last);
					goal('compare_add');
				}
			})
			.catch(function () {})
			.then(function () {
				btn.classList.remove('is-busy');
			});
	}, true);

	/* Смена длины в карточке или плитке: тема меняет предложение, местами только
	   атрибутами (их наблюдатель ниже не видит) — перерисовываем кнопки. */
	document.addEventListener('click', function (e) {
		if (e.target.closest && e.target.closest('[data-treevalue], [data-onevalue]')) {
			setTimeout(paint, 300);
			setTimeout(paint, 1000);
		}
	});

	/* Плитки догружаются («Показать ещё», лента «С этим товаром покупают») —
	   красим новые кнопки. Наблюдаем дешёво: только за появлением кнопок. */
	var pending = false;
	new MutationObserver(function () {
		if (pending) return;
		pending = true;
		requestAnimationFrame(function () {
			pending = false;
			paint();
		});
	}).observe(document.documentElement, {childList: true, subtree: true});

	window.ndCompare = {request: request, has: has, goal: goal, ids: function () { return ids.slice(); }};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', paint);
	} else {
		paint();
	}
})();
