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
	   телефоне список услуг пропадал. В макете вместо него выпадающий список
	   над шапкой. Делаем настоящий <select>: на телефоне он открывается родным
	   колесом выбора, и его не нужно чинить при перерисовках. */
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

		var select = document.createElement('select');
		select.className = 'nd-srv-menu__select';
		select.setAttribute('aria-label', 'Услуги');

		var here = location.pathname;

		items.forEach(function (a) {
			var option = document.createElement('option');
			option.value = a.getAttribute('href');
			option.textContent = (a.textContent || '').trim();
			if (option.value === here || a.parentNode.className.indexOf('current') > -1) {
				option.selected = true;
			}
			select.appendChild(option);
		});

		select.addEventListener('change', function () {
			if (select.value) location.href = select.value;
		});

		var wrap = document.createElement('div');
		wrap.className = 'nd-srv-menu';
		wrap.appendChild(select);
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
