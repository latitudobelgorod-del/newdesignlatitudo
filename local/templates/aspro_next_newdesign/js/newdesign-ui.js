/**
 * Общие элементы нового дизайна: панель фильтров (.nd-filter) и кнопка
 * «Показать ещё» в постраничной навигации.
 * Используется на страницах отзывов и портфолио, поэтому вынесена из шаблонов.
 *
 * Раскрытие списков делает сам <details> — без JS фильтр тоже работает,
 * в панели есть кнопка «Применить». Скрипт лишь отправляет форму сразу
 * после выбора, закрывает соседние списки и клик мимо панели.
 */
(function () {
	'use strict';

	/* ----------------------- фильтры ----------------------- */

	// Со скриптом форма уходит сразу после выбора — кнопки «Применить» прячем.
	// Раскрытие списков делает сам <details>, нам остаётся закрывать соседние
	// и клик мимо панели.
	function initFilter() {
		var filterForm = document.querySelector('.nd-filter');
		if (!filterForm || filterForm.classList.contains('is-js')) {
			return;
		}
		filterForm.classList.add('is-js');

		filterForm.addEventListener('change', function () {
			filterForm.submit();
		});

		filterForm.addEventListener('toggle', function (e) {
			if (!e.target.open) {
				return;
			}
			Array.prototype.forEach.call(filterForm.querySelectorAll('.nd-filter__drop[open]'), function (d) {
				if (d !== e.target) {
					d.open = false;
				}
			});
		}, true);

		document.addEventListener('click', function (e) {
			if (filterForm.contains(e.target)) {
				return;
			}
			Array.prototype.forEach.call(filterForm.querySelectorAll('.nd-filter__drop[open]'), function (d) {
				d.open = false;
			});
		});
	}

	// скрипт шаблона может подключиться до разметки — инициализируем и сразу,
	// и по готовности документа
	initFilter();
	document.addEventListener('DOMContentLoaded', initFilter);


	/* ---------------------- «Показать ещё» ---------------------- */

	var loading = false;

	/**
	 * Имя блока берём из обёртки навигации: `nd-mat__nav` → `nd-mat`.
	 * Список ищем как `<блок>__list`, а если такого нет — сам `<блок>`
	 * (у брендов карточки лежат прямо в `.nd-brandlist`). Так кнопка работает
	 * на всех страницах со сквозной навигацией — отзывы, портфолио, акции,
	 * материалы, бренды — и не надо вести список селекторов.
	 */
	function ndPagerBlock(more) {
		var wrap = more.closest('[class*="__nav"]');
		var name = wrap ? /(?:^|\s)([\w-]+)__nav(?:\s|$)/.exec(wrap.className) : null;
		if (!name) {
			return null;
		}
		return {
			nav: wrap,
			navSel: '.' + name[1] + '__nav',
			// именно двумя запросами: в одной группе селекторов победил бы
			// внешний `.nd-mat` — он идёт в разметке раньше своего `__list`
			findList: function (root) {
				return root.querySelector('.' + name[1] + '__list') || root.querySelector('.' + name[1]);
			}
		};
	}

	document.addEventListener('click', function (e) {
		var more = e.target.closest ? e.target.closest('[data-nd-pager-more]') : null;
		if (!more || loading) {
			return;
		}

		var block = ndPagerBlock(more);
		var list = block ? block.findList(document) : null;
		var nav = block ? block.nav : null;
		var href = more.getAttribute('href');
		if (!list || !nav || !href) {
			return; // без списка или адреса пусть отработает обычный переход
		}

		e.preventDefault();
		loading = true;
		more.classList.add('is-loading');

		fetch(href, { credentials: 'same-origin' })
			.then(function (r) {
				if (!r.ok) {
					throw new Error('HTTP ' + r.status);
				}
				return r.text();
			})
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				var nextList = block.findList(doc);
				var nextNav = doc.querySelector(block.navSel);
				if (!nextList) {
					throw new Error('в ответе нет списка');
				}
				Array.prototype.slice.call(nextList.children).forEach(function (card) {
					list.appendChild(document.importNode(card, true));
				});
				// навигацию заменяем целиком: у неё сдвинулась текущая страница
				nav.innerHTML = nextNav ? nextNav.innerHTML : '';
				document.dispatchEvent(new CustomEvent('nd:appended'));
				if (window.history && window.history.replaceState) {
					window.history.replaceState(null, '', href);
				}
			})
			.catch(function () {
				// не смогли подгрузить — уводим на страницу обычным переходом
				window.location.href = href;
			})
			.then(function () {
				loading = false;
				more.classList.remove('is-loading');
			});
	});
})();
