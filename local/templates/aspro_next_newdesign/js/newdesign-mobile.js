/**
 * Мобильный новый дизайн: прибитая нижняя панель, панель «Меню» и шторка
 * «Связаться с нами» (page_blocks/nav_bottom_newdesign.php), плюс тонкая
 * полоска у шапки на прокрутке (page_blocks/header_mobile_newdesign.php).
 *
 * Без jQuery: скрипт подключается прямо в разметке блока, а тема со своим
 * jQuery грузится ниже по странице.
 */
(function () {
	'use strict';

	var navbar = document.getElementById('nd-navbar');
	if (!navbar) return;

	var sheets = {
		menu: document.getElementById('nd-sheet-menu'),
		contacts: document.getElementById('nd-sheet-contacts')
	};
	var openName = null;

	function tabByName(name) {
		return navbar.querySelector('[data-nd-open="' + name + '"]');
	}

	// Вкладка текущего раздела подсвечена сервером. Пока открыта шторка,
	// красной должна быть только она — гасим остальные и возвращаем на месте.
	var pageTab = navbar.querySelector('.nd-navbar__tab.is-active');

	function close() {
		if (!openName) return;
		var sheet = sheets[openName];
		var tab = tabByName(openName);
		if (sheet) sheet.hidden = true;
		if (tab) {
			tab.classList.remove('is-active');
			tab.setAttribute('aria-expanded', 'false');
		}
		if (pageTab) pageTab.classList.add('is-active');
		openName = null;
		document.body.classList.remove('nd-sheet-open');
	}

	function open(name) {
		var sheet = sheets[name];
		if (!sheet) return;
		if (openName === name) {
			close();
			return;
		}
		close();
		sheet.hidden = false;
		if (pageTab) pageTab.classList.remove('is-active');
		var tab = tabByName(name);
		if (tab) {
			tab.classList.add('is-active');
			tab.setAttribute('aria-expanded', 'true');
		}
		openName = name;
		document.body.classList.add('nd-sheet-open');
	}

	document.addEventListener('click', function (e) {
		var opener = e.target.closest ? e.target.closest('[data-nd-open]') : null;
		if (opener) {
			e.preventDefault();
			open(opener.getAttribute('data-nd-open'));
			return;
		}

		if (e.target.closest && e.target.closest('[data-nd-close]')) {
			e.preventDefault();
			close();
			return;
		}

		// Выбор города: жмём штатный триггер десктопной шапки. Своего
		// aspro:regionality.list.next не заводим — второй экземпляр
		// продублировал бы окно «Ваш город … ?».
		var cityBtn = e.target.closest ? e.target.closest('[data-nd-city-chooser]') : null;
		if (cityBtn) {
			e.preventDefault();
			var trigger = document.querySelector('.js_city_chooser');
			close();
			if (trigger) {
				if (window.jQuery) window.jQuery(trigger).trigger('click');
				else trigger.click();
			}
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') close();
	});

	// Ссылка внутри открытой шторки ведёт на другую страницу — шторку
	// закрываем, иначе при возврате «назад» из кеша браузера она останется
	// открытой поверх контента.
	window.addEventListener('pagehide', close);

	var header = document.getElementById('nd-mheader');
	if (header) {
		var onScroll = function () {
			header.classList.toggle('is-scrolled', window.pageYOffset > 4);
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}
})();
