/* Страница сравнения: раскладка плиток по колонкам, «только различия»,
   удаление, липкая шапка, список товаров в заявку менеджеру.
   Запросы к списку — window.ndCompare.request (js/newdesign-compare.js). */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	ready(function () {
		var root = document.getElementById('nd-cmp');
		if (!root) return;

		/* Плитки стоят в первой строке таблицы. Помечаем каждую ID товара
		   (по нему липкая шапка находит «В корзину») и ставим крестик удаления. */
		var blocks = root.querySelectorAll('[data-nd-cmp-tiles] .item_block');
		for (var i = 0; i < blocks.length; i++) {
			var marker = blocks[i].querySelector('[data-product-id], [data-item]');
			var id = marker ? (marker.getAttribute('data-product-id') || marker.getAttribute('data-item')) : '';
			if (!id) continue;
			blocks[i].setAttribute('data-nd-cmp-tile', id);
			var rm = document.createElement('button');
			rm.type = 'button';
			rm.className = 'nd-cmp__remove';
			rm.setAttribute('data-nd-cmp-remove', id);
			rm.title = 'Убрать из сравнения';
			rm.setAttribute('aria-label', 'Убрать из сравнения');
			rm.textContent = '×';
			blocks[i].appendChild(rm);
		}

		/* «Показывать только различия» — по умолчанию включено при трёх товарах и больше. */
		var diff = root.querySelector('[data-nd-cmp-diff]');
		function applyDiff() {
			root.classList.toggle('is-diff', !!(diff && diff.checked));
		}
		if (diff) {
			diff.addEventListener('change', applyDiff);
			applyDiff();
		}

		function reloadTab() {
			location.href = location.pathname + '?tab=' + encodeURIComponent(root.getAttribute('data-tab'));
		}
		root.addEventListener('click', function (e) {
			var rm = e.target.closest('[data-nd-cmp-remove]');
			if (rm && window.ndCompare) {
				e.preventDefault();
				rm.disabled = true;
				window.ndCompare.request('remove', {id: rm.getAttribute('data-nd-cmp-remove')}).then(reloadTab, reloadTab);
				return;
			}
			var clear = e.target.closest('[data-nd-cmp-clear]');
			if (clear && window.ndCompare) {
				e.preventDefault();
				window.ndCompare.request('clear', {key: clear.getAttribute('data-nd-cmp-clear')}).then(function () {
					location.href = location.pathname;
				});
				return;
			}
			/* «В корзину» липкой шапки — жмём кнопку плитки: количество и
			   единица (шт/м²/п.м) берутся оттуда же, где их выбрали. */
			var buy = e.target.closest('[data-nd-cmp-buy]');
			if (buy) {
				e.preventDefault();
				var cell = root.querySelector('[data-nd-cmp-tile="' + buy.getAttribute('data-nd-cmp-buy') + '"]');
				var btn = cell ? cell.querySelector('.to-cart:not([style*="display: none"]), .in-cart:not([style*="display: none"]), .nd-buy-ph') : null;
				if (btn) {
					btn.click();
				} else if (cell) {
					cell.scrollIntoView({behavior: 'smooth', block: 'center'});
				}
			}
		});

		/* Липкая шапка: видна, пока плитки ушли за верх экрана, а таблица ещё на экране.
		   По горизонтали повторяет прокрутку таблицы. */
		var sticky = root.querySelector('[data-nd-cmp-sticky]');
		var scroll = root.querySelector('[data-nd-cmp-scroll]');
		var head = root.querySelector('.nd-cmp__row--head');
		var grid = root.querySelector('.nd-cmp__grid');
		if (sticky && scroll && head && grid) {
			var inner = sticky.firstElementChild;
			/* Шапка сайта на прокрутке прибита к верху — встаём под неё. Ищем
			   прибитый элемент в верхней точке экрана (кроме самой липкой шапки). */
			var headerBottom = function () {
				var bottom = 0;
				var els = document.elementsFromPoint(window.innerWidth / 2, 2);
				for (var k = 0; k < els.length; k++) {
					for (var el = els[k]; el && el !== document.body; el = el.parentElement) {
						if (sticky.contains(el)) break;
						var pos = getComputedStyle(el).position;
						if (pos === 'fixed' || pos === 'sticky') {
							bottom = Math.max(bottom, el.getBoundingClientRect().bottom);
							break;
						}
					}
				}
				return bottom;
			};
			var update = function () {
				var h = head.getBoundingClientRect();
				var g = grid.getBoundingClientRect();
				var top = sticky.classList.contains('is-visible') ? parseFloat(sticky.style.top) || 0 : headerBottom();
				var show = h.bottom < top && g.bottom > top + 120;
				sticky.classList.toggle('is-visible', show);
				if (show) {
					sticky.style.top = headerBottom() + 'px';
					var s = scroll.getBoundingClientRect();
					sticky.style.left = s.left + 'px';
					sticky.style.width = scroll.clientWidth + 'px';
					inner.style.transform = 'translateX(' + (-scroll.scrollLeft) + 'px)';
				}
			};
			window.addEventListener('scroll', update, {passive: true});
			window.addEventListener('resize', update);
			scroll.addEventListener('scroll', update, {passive: true});
			update();
		}

		/* Заявка менеджеру: список сравниваемых товаров — в поле вопроса формы.
		   Форма приходит ajax'ом после клика; поле не блокируем — можно дописать. */
		var calc = root.querySelector('[data-nd-cmp-list]');
		if (calc) {
			calc.addEventListener('click', function () {
				var text = calc.getAttribute('data-nd-cmp-list');
				var tries = 0;
				var timer = setInterval(function () {
					var field = document.querySelector('.jqmWindow [data-sid="QUESTION"], .popup [data-sid="QUESTION"], form [data-sid="QUESTION"]');
					if (field) {
						if (!field.value) field.value = text;
						clearInterval(timer);
					} else if (++tries > 40) {
						clearInterval(timer);
					}
				}, 150);
			});
		}
	});
})();
