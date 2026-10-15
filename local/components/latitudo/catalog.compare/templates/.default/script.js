/* Страница сравнения: вкладки без перезагрузки, «только различия», удаление,
   липкая шапка, список товаров в заявку менеджеру.
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

		var panels = root.querySelectorAll('[data-nd-cmp-panel]');

		function activePanel() {
			return root.querySelector('[data-nd-cmp-panel].is-active');
		}

		/* Вкладки без перезагрузки: панели уже на странице, переключаем классы
		   и адрес (?tab= — чтобы обновление и «назад» открывали ту же вкладку). */
		root.addEventListener('click', function (e) {
			var tab = e.target.closest('[data-nd-cmp-tab]');
			if (!tab) return;
			e.preventDefault();
			var key = tab.getAttribute('data-nd-cmp-tab');
			var tabs = root.querySelectorAll('[data-nd-cmp-tab]');
			for (var t = 0; t < tabs.length; t++) {
				var on = tabs[t] === tab;
				tabs[t].classList.toggle('is-active', on);
				tabs[t].setAttribute('aria-selected', on ? 'true' : 'false');
			}
			for (var p = 0; p < panels.length; p++) {
				panels[p].classList.toggle('is-active', panels[p].getAttribute('data-nd-cmp-panel') === key);
			}
			root.setAttribute('data-tab', key);
			if (window.history && history.replaceState) {
				history.replaceState(null, '', location.pathname + '?tab=' + encodeURIComponent(key));
			}
			window.dispatchEvent(new Event('resize')); // ленты, плитки и стрелки пересчитывают ширину
			updateSticky();
		});

		for (var p = 0; p < panels.length; p++) {
			setupPanel(panels[p]);
		}

		function setupPanel(panel) {
			/* Плитки стоят в первой строке таблицы. Помечаем каждую ID товара
			   (по нему липкая шапка находит «В корзину») и ставим крестик удаления. */
			var blocks = panel.querySelectorAll('[data-nd-cmp-tiles] .item_block');
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
			var diff = panel.querySelector('[data-nd-cmp-diff]');
			var applyDiff = function () {
				panel.classList.toggle('is-diff', !!(diff && diff.checked));
				updateSticky();
			};
			if (diff) {
				diff.addEventListener('change', applyDiff);
				applyDiff();
			}
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
			var clear = e.target.closest('[data-nd-cmp-clear-active]');
			if (clear && window.ndCompare) {
				e.preventDefault();
				// очищаем открытую вкладку, остальные остаются
				window.ndCompare.request('clear', {key: root.getAttribute('data-tab')}).then(function () {
					location.href = location.pathname;
				});
				return;
			}
			/* «В корзину» липкой шапки — жмём кнопку плитки: количество и
			   единица (шт/м²/п.м) берутся оттуда же, где их выбрали. */
			var buy = e.target.closest('[data-nd-cmp-buy]');
			if (buy) {
				e.preventDefault();
				var panel = activePanel();
				var cell = panel ? panel.querySelector('[data-nd-cmp-tile="' + buy.getAttribute('data-nd-cmp-buy') + '"]') : null;
				var btn = cell ? cell.querySelector('.to-cart:not([style*="display: none"]), .in-cart:not([style*="display: none"]), .nd-buy-ph') : null;
				if (btn) {
					btn.click();
				} else if (cell) {
					cell.scrollIntoView({behavior: 'smooth', block: 'center'});
				}
			}
		});

		/* Липкая шапка активной вкладки: видна, пока плитки ушли под шапку сайта,
		   а таблица ещё на экране. По горизонтали повторяет прокрутку таблицы. */
		function headerBottom(except) {
			var bottom = 0;
			var els = document.elementsFromPoint(window.innerWidth / 2, 2);
			for (var k = 0; k < els.length; k++) {
				for (var el = els[k]; el && el !== document.body; el = el.parentElement) {
					if (except && except.contains(el)) break;
					var pos = getComputedStyle(el).position;
					if (pos === 'fixed' || pos === 'sticky') {
						bottom = Math.max(bottom, el.getBoundingClientRect().bottom);
						break;
					}
				}
			}
			return bottom;
		}

		function updateSticky() {
			for (var p = 0; p < panels.length; p++) {
				var panel = panels[p];
				var sticky = panel.querySelector('[data-nd-cmp-sticky]');
				if (!sticky) continue;
				if (!panel.classList.contains('is-active')) {
					sticky.classList.remove('is-visible');
					continue;
				}
				var scroll = panel.querySelector('[data-nd-cmp-scroll]');
				var head = panel.querySelector('.nd-cmp__row--head');
				var grid = panel.querySelector('.nd-cmp__grid');
				var top = headerBottom(sticky);
				var h = head.getBoundingClientRect();
				var g = grid.getBoundingClientRect();
				var show = h.bottom < top && g.bottom > top + 120;
				sticky.classList.toggle('is-visible', show);
				if (show) {
					// полоса — на всю ширину экрана, окно с товарами — ровно над таблицей
					var s = scroll.getBoundingClientRect();
					var clip = sticky.querySelector('.nd-cmp__sticky-clip');
					sticky.style.top = top + 'px';
					clip.style.marginLeft = s.left + 'px';
					clip.style.width = scroll.clientWidth + 'px';
					clip.firstElementChild.style.transform = 'translateX(' + (-scroll.scrollLeft) + 'px)';
				}
			}
		}
		/* Стрелки по бокам карточек: видны, когда товаров больше, чем влезает;
		   листают на одну колонку, у края гаснут. */
		function updateArrows() {
			for (var p = 0; p < panels.length; p++) {
				var scroll = panels[p].querySelector('[data-nd-cmp-scroll]');
				if (!scroll) continue;
				var max = scroll.scrollWidth - scroll.clientWidth;
				var arrows = panels[p].querySelectorAll('[data-nd-cmp-arrow]');
				for (var a = 0; a < arrows.length; a++) {
					arrows[a].hidden = max <= 2;
					arrows[a].disabled = arrows[a].getAttribute('data-nd-cmp-arrow') === '-1'
						? scroll.scrollLeft <= 2
						: scroll.scrollLeft >= max - 2;
				}
			}
		}
		root.addEventListener('click', function (e) {
			var arrow = e.target.closest('[data-nd-cmp-arrow]');
			if (!arrow) return;
			var scroll = arrow.parentNode.querySelector('[data-nd-cmp-scroll]');
			var cell = scroll && scroll.querySelector('.nd-cmp__row--head .item_block, .nd-cmp__row--head .nd-cmp__cell');
			var step = cell ? cell.getBoundingClientRect().width : scroll.clientWidth / 5;
			// к ближайшей границе колонки, чтобы карточки не вставали обрезанными
			var target = Math.round((scroll.scrollLeft + Number(arrow.getAttribute('data-nd-cmp-arrow')) * step) / step) * step;
			scroll.scrollLeft = Math.max(0, Math.min(target, scroll.scrollWidth - scroll.clientWidth));
		});

		window.addEventListener('scroll', updateSticky, {passive: true});
		window.addEventListener('resize', updateSticky);
		var scrolls = root.querySelectorAll('[data-nd-cmp-scroll]');
		for (var sc = 0; sc < scrolls.length; sc++) {
			scrolls[sc].addEventListener('scroll', updateSticky, {passive: true});
			scrolls[sc].addEventListener('scroll', updateArrows, {passive: true});
		}
		window.addEventListener('resize', updateArrows);
		updateSticky();
		updateArrows();

		/* Заявка менеджеру: список сравниваемых товаров — в поле вопроса формы.
		   Форма приходит ajax'ом после клика; поле не блокируем — можно дописать. */
		root.addEventListener('click', function (e) {
			var calc = e.target.closest('[data-nd-cmp-list]');
			if (calc) {
				var text = calc.getAttribute('data-nd-cmp-list');
				var tries = 0;
				var timer = setInterval(function () {
					var field = document.querySelector('.jqmWindow [data-sid="QUESTION"], .popup [data-sid="QUESTION"], form [data-sid="QUESTION"]');
					if (field) {
						clearInterval(timer);
						/* В «Общей форме» вопрос — однострочное поле, а список товаров
						   длинный: подменяем на многострочное с тем же именем и
						   атрибутами — форма отправляется как прежде. */
						if (field.tagName === 'INPUT') {
							var area = document.createElement('textarea');
							for (var a = 0; a < field.attributes.length; a++) {
								var attr = field.attributes[a];
								if (attr.name !== 'type' && attr.name !== 'value') area.setAttribute(attr.name, attr.value);
							}
							area.rows = 5;
							area.value = field.value;
							field.parentNode.replaceChild(area, field);
							field = area;
						}
						if (!field.value) field.value = text;
						// подпись поля поднимается по событию input (js/newdesign-forms.js)
						field.dispatchEvent(new Event('input', {bubbles: true}));
						field.dispatchEvent(new Event('change', {bubbles: true}));
					} else if (++tries > 40) {
						clearInterval(timer);
					}
				}, 150);
			}
		});
	});
})();
