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

	function has(id) {
		return ids.indexOf(Number(id)) !== -1;
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

	/* В карточке правую колонку перестраивает скрипт карточки (newdesign-element.js),
	   и метка оказывается над выбором цвета. Ставим кнопку под «Рассчитать вместе
	   с менеджером» (.nd-pd__calc) — рядом с «В корзину». */
	function placeDetail() {
		var btn = document.querySelector('.nd-cmp-btn--detail');
		var calc = document.querySelector('.nd-pd__calc');
		if (btn && calc && calc.nextElementSibling !== btn) {
			calc.parentNode.insertBefore(btn, calc.nextSibling);
		}
		// колонка раскладывается flex'ом с order — встаём в тот же ряд, что ссылка
		if (btn && calc) {
			var order = getComputedStyle(calc).order;
			if (btn.style.order !== order) btn.style.order = order;
		}
	}

	/* Кнопки: состояние «в сравнении» видно сразу. */
	function paint() {
		placeDetail();
		var buttons = document.querySelectorAll('[data-nd-cmp]');
		for (var i = 0; i < buttons.length; i++) {
			var on = has(buttons[i].getAttribute('data-nd-cmp'));
			buttons[i].classList.toggle('is-active', on);
			buttons[i].setAttribute('aria-pressed', on ? 'true' : 'false');
			buttons[i].title = on ? 'Убрать из сравнения' : 'Добавить к сравнению';
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
		var adding = !has(id);
		btn.classList.add('is-busy');
		request(adding ? 'add' : 'remove', {id: id})
			.then(function (res) {
				if (adding && res && res.last) toast(res.last);
			})
			.catch(function () {})
			.then(function () {
				btn.classList.remove('is-busy');
			});
	}, true);

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

	window.ndCompare = {request: request, has: has, ids: function () { return ids.slice(); }};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', paint);
	} else {
		paint();
	}
})();
