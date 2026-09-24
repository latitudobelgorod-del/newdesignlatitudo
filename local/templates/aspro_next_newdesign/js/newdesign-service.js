/**
 * Детальная страница услуги — новый дизайн.
 * Макет Figma «Чистовик», фрейм «Услуга» 20669:41851.
 *
 * Шапку услуги печатает содержимое элемента, а не шаблон: в детальном тексте
 * лежит блок #page_str_terras с заголовком и плашками. Править его нельзя —
 * это контент. Но одними стилями нужную раскладку не собрать:
 *
 *  - в макете заголовок, описание и кнопки лежат внутри карточки с фотографией,
 *    а в разметке они соседи (у «Террасы на кровле» после заголовка идут ещё
 *    описание и <br>, и в сетке они становились отдельными ячейками — вёрстка
 *    разъезжалась);
 *  - кнопки и вовсе лежат отдельной секцией под блоком.
 *
 * Поэтому здесь собираем карточку: всё, что не .main_bl_flex, заворачиваем в
 * .nd-srv-hero__card, туда же переносим кнопки. Оформление — в
 * css/newdesign-service.css.
 */
(function () {
	'use strict';

	if (window.__ndService) return;
	window.__ndService = true;

	/* Кнопки: в разметке сначала «Посмотреть проекты», в макете первой стоит
	   форма — сортируем по наличию триггера формы. */
	function sortCta(cells) {
		return cells.sort(function (a, b) {
			var af = a.querySelector('[data-event="jqm"]') ? 0 : 1;
			var bf = b.querySelector('[data-event="jqm"]') ? 0 : 1;
			return af - bf;
		});
	}

	/* Секция с кнопками идёт следом за шапкой: у неё нет своего класса, кроме
	   общего .terrace-second-screen, поэтому узнаём по содержимому. */
	function findCta(hero) {
		var node = hero.nextElementSibling;
		while (node) {
			if (node.querySelector && node.querySelector('.btn') && !node.querySelector('h2')) return node;
			node = node.nextElementSibling;
		}
		return null;
	}

	function build() {
		var hero = document.querySelector('.services_newdesign #page_str_terras');
		if (!hero || hero.__ndDone) return;

		var main = hero.querySelector('.main_bl');
		var right = hero.querySelector('.main_bl .main_bl_flex');
		if (!main || !right) return;

		hero.__ndDone = true;

		var card = document.createElement('div');
		card.className = 'nd-srv-hero__card';

		/* Всё до правой колонки — содержимое карточки. Список делаем заранее:
		   переносить узлы, идя по живой коллекции детей, нельзя. */
		[].slice.call(main.children).forEach(function (node) {
			if (node !== right) card.appendChild(node);
		});
		main.insertBefore(card, right);

		var section = findCta(hero);
		var cells = section ? [].slice.call(section.querySelectorAll('[class*="col-md-6"]')) : [];

		if (cells.length) {
			var cta = document.createElement('div');
			cta.className = 'nd-srv-hero__cta';
			sortCta(cells).forEach(function (cell) { cta.appendChild(cell); });
			card.appendChild(cta);

			/* Опустевшая секция оставляет после себя поля темы — прячем. */
			if (!section.textContent.trim()) section.style.display = 'none';
		}
	}

	/* --- меню услуг на телефоне ---------------------------------------------
	   Тема прячет боковую колонку целиком ниже 992px (css/media.css), и на
	   телефоне список услуг пропадал. По макету («Список открытый»,
	   20817:36736) над шапкой кнопка с текущей услугой, а по нажатию снизу
	   выезжает шторка «Услуги / Закрыть» со списком (24.09.2026; до этого был
	   <select>, и телефон открывал непонятное системное колесо выбора).
	   Шторка — общие .nd-sheet из css/newdesign-mobile.css, кладём её в body:
	   внутри колонки её перекрыл бы контекст наложения темы. */
	var CHEVRON = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 6l4 4 4-4" stroke="#101014" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

	function esc(s) {
		return String(s).replace(/[&<>"]/g, function (c) {
			return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;'}[c];
		});
	}

	function buildMenu() {
		var menu = document.querySelector('.left_block .left_menu');
		if (!menu || menu.__ndDone) return;

		var items = [].slice.call(menu.querySelectorAll('li > a[href]'));
		if (items.length < 2) return;

		var host = document.querySelector('.detail.services_newdesign');
		var before = host ? host.firstChild : null;

		if (!host) {
			var hero = document.querySelector('#page_str_terras');
			if (!hero) return;
			host = hero.parentNode;
			before = hero;
		}

		menu.__ndDone = true;

		var here = location.pathname;
		var current = '';
		var links = '';

		items.forEach(function (a) {
			var href = a.getAttribute('href');
			var name = (a.textContent || '').trim();
			var isCur = href === here || a.parentNode.className.indexOf('current') > -1;
			if (isCur) current = name;
			links += '<a class="nd-srvsheet__item' + (isCur ? ' is-current' : '') + '" href="' + esc(href) + '"'
				+ (isCur ? ' aria-current="page"' : '') + '>' + esc(name) + '</a>';
		});

		var sheet = document.createElement('div');
		sheet.className = 'nd-sheet nd-sheet--bottom nd-srvsheet';
		sheet.hidden = true;
		sheet.innerHTML =
			'<div class="nd-sheet__overlay" data-nd-srv-close></div>' +
			'<div class="nd-sheet__panel">' +
				'<div class="nd-sheet__head">' +
					'<span class="nd-sheet__grip"></span>' +
					'<div class="nd-sheet__headrow">' +
						'<span class="nd-sheet__title">Услуги</span>' +
						'<button class="nd-sheet__closetext" type="button" data-nd-srv-close>Закрыть</button>' +
					'</div>' +
				'</div>' +
				'<nav class="nd-srvsheet__list" aria-label="Услуги">' + links + '</nav>' +
			'</div>';
		document.body.appendChild(sheet);

		function close() {
			if (sheet.hidden) return;
			sheet.hidden = true;
			document.body.classList.remove('nd-sheet-open');
		}

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'nd-srv-menu__btn';
		btn.setAttribute('aria-haspopup', 'dialog');
		btn.innerHTML = '<span>' + esc(current || 'Выбрать раздел') + '</span>' + CHEVRON;
		btn.addEventListener('click', function () {
			sheet.hidden = false;
			document.body.classList.add('nd-sheet-open');
			/* текущий пункт — в поле зрения, если список длинный */
			var cur = sheet.querySelector('.is-current');
			if (cur && cur.scrollIntoView) cur.scrollIntoView({block: 'nearest'});
		});

		sheet.addEventListener('click', function (e) {
			if (e.target.closest && e.target.closest('[data-nd-srv-close]')) {
				e.preventDefault();
				close();
			}
		});
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') close();
		});

		var wrap = document.createElement('div');
		wrap.className = 'nd-srv-menu';
		wrap.appendChild(btn);
		host.insertBefore(wrap, before);
	}

	function run() { build(); buildMenu(); }

	if (document.readyState !== 'loading') run();
	else document.addEventListener('DOMContentLoaded', run);

	/* Содержимое приезжает вместе со страницей, но блоки редактора иногда
	   дорисовываются скриптами — переспрашиваем. */
	setTimeout(run, 500);
	setTimeout(run, 1500);
})();
