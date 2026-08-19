/**
 * Цифра у корзины после добавления товара.
 *
 * Штатная цепочка темы этого не делала: после успешного `ajax/item.php` кнопка
 * переключалась на «В корзине», а `getActualBasket()` и `reloadTopBasket()` —
 * те, что обновляют `arBasketPrices` и пишут число в шапку и нижнее меню, — не
 * вызывались (проверено на боевом сайте 19 августа 2026: после нажатия уходит
 * ровно один запрос, `item.php`, и больше ничего). Обработчиков `.to-cart` у
 * темы несколько, ветвлений внутри ещё больше, и какой именно отработает,
 * зависит от вида карточки — чинить их по одному смысла нет.
 *
 * Поэтому спрашиваем итог сами. `ajax/show_basket_actual.php` отдаёт скрипт с
 * `arBasketPrices`, оттуда и берём BASKET_COUNT. Разметку счётчика не выдумываем:
 * пишем в те же узлы, что и тема (.basket-link.basket .count с классами
 * empted / basket-count), — они есть и в шапке, и в нижней панели.
 *
 * Спрашиваем не один раз: добавление на боевом занимает секунду-полторы, и
 * первый же запрос успевает раньше самого добавления — сервер отдаёт прежнее
 * число. Поэтому опрашиваем с нарастающей паузой и останавливаемся, как только
 * число изменилось (или после последней попытки).
 */
(function () {
	'use strict';

	if (window.__ndBasketCount) return;
	window.__ndBasketCount = true;

	var DELAYS = [700, 1500, 2500, 4000, 6000];
	var timers = [];
	var startCount = null;

	function siteDir() {
		var o = window.arNextOptions;
		return (o && o.SITE_DIR) ? o.SITE_DIR : '/';
	}

	/* Что сейчас нарисовано у корзины — с этим и сравниваем ответы сервера. */
	function shownCount() {
		var box = document.querySelector('.basket-link.basket .count');
		if (!box) return null;
		var n = parseInt((box.textContent || '').trim(), 10);
		return isNaN(n) ? 0 : n;
	}

	function apply(count) {
		var n = parseInt(count, 10);
		if (isNaN(n)) return;

		[].forEach.call(document.querySelectorAll('.basket-link.basket'), function (link) {
			link.classList.toggle('basket-count', n > 0);

			var box = link.querySelector('.count');
			if (!box) return;

			box.classList.toggle('empted', n <= 0);
			if (box.textContent.trim() !== String(n)) box.textContent = n;
		});
	}

	function refresh() {
		var xhr = new XMLHttpRequest();
		xhr.open('POST', siteDir() + 'ajax/show_basket_actual.php', true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.onload = function () {
			/* Ответ — кусок html со скриптом, разбирать его целиком незачем:
			   нужно одно число. */
			var m = /'BASKET_COUNT'\s*:\s*'(\d+)'/.exec(xhr.responseText || '');
			if (!m) return;

			apply(m[1]);

			/* Дождались изменения — остальные попытки отменяем. */
			if (startCount !== null && parseInt(m[1], 10) !== startCount) {
				timers.forEach(clearTimeout);
				timers = [];
			}
		};
		try { xhr.send('ACTION=add'); } catch (e) { }
	}

	function schedule() {
		timers.forEach(clearTimeout);
		startCount = shownCount();
		timers = DELAYS.map(function (ms) { return setTimeout(refresh, ms); });
	}

	/* Фаза перехвата: тема на некоторых кнопках останавливает всплытие. */
	document.addEventListener('click', function (e) {
		var t = e.target;
		if (!t || !t.closest) return;
		if (t.closest('.to-cart')) schedule();
	}, true);
})();
