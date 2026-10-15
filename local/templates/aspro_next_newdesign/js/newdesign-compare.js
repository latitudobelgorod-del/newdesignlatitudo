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

	/* В карточке кнопка — квадрат с иконкой, как в шапке, слева от номера
	   товара (номер остаётся у правого края, его кладёт в H1 newdesign-element.js).
	   В сам H1 кнопку класть нельзя: тема при загрузке переписывает его
	   содержимое. Ставим следом за H1 и выравниваем по номеру; место под неё
	   перед номером держит стиль .nd-has-cmp. */
	function placeDetail() {
		var btn = document.querySelector('.nd-cmp-btn--detail');
		var h1 = document.querySelector('.catalog_detail.element_newdesign > h1');
		if (!btn || !h1) return;
		if (h1.nextElementSibling !== btn) h1.parentNode.insertBefore(btn, h1.nextSibling);
		if (!h1.classList.contains('nd-has-cmp')) h1.classList.add('nd-has-cmp');
		var box = h1.parentNode.getBoundingClientRect();
		var article = h1.querySelector(':scope > .nd-pd__article');
		// на телефоне номер в заголовке скрыт (он в верхней полосе карточки) —
		// тогда кнопка справа от заголовка, место под неё держит .nd-has-cmp-noart
		if (article && !article.offsetWidth) article = null;
		h1.classList.toggle('nd-has-cmp-noart', !article);
		var r = article ? article.getBoundingClientRect() : null;
		var right = (article ? box.right - r.left + 12 : 0) + 'px';
		var top = (article ? r.top - box.top + (r.height - btn.offsetHeight) / 2 : h1.offsetTop + h1.offsetHeight - btn.offsetHeight) + 'px';
		if (btn.style.right !== right) btn.style.right = right;
		if (btn.style.top !== top) btn.style.top = top;
	}

	/* Кнопки: состояние «в сравнении» видно сразу. */
	function paint() {
		placeDetail();
		var buttons = document.querySelectorAll('[data-nd-cmp]');
		for (var i = 0; i < buttons.length; i++) {
			var on = has(buttons[i].getAttribute('data-nd-cmp'));
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

	window.addEventListener('resize', placeDetail);
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', paint);
	} else {
		paint();
	}
})();
