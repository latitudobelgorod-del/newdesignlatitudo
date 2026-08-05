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

	/* ── Панели второго уровня ──
	   Каталог (разделы → подразделы) и разделы пунктов меню: «Услуги»,
	   «Партнерам», «Наши работы», «Производители». Разметка —
	   menu/catalog_mobile_newdesign и page_blocks/header_drops_newdesign.php
	   в режиме mobile. Панели лежат стопкой: у вложенной есть
	   data-nd-msub-parent, кнопка «назад» возвращает к нему.

	   Свой пункт панель находит по адресу (data-nd-msub-key = href ссылки) —
	   тот же приём, что у выпадающих панелей десктопной шапки: пункты меню
	   правятся из админки, и завязываться на их порядок нельзя. */
	var subs = {};
	var subByKey = {};
	var openSub = null;

	function subKey(href) {
		var a = document.createElement('a');
		a.href = href;
		var path = a.pathname || '';
		if (path.charAt(0) !== '/') path = '/' + path;
		return path.replace(/\/+$/, '') + '/';
	}

	Array.prototype.forEach.call(document.querySelectorAll('.nd-msub[data-nd-msub]'), function (sub) {
		subs[sub.getAttribute('data-nd-msub')] = sub;
		var key = sub.getAttribute('data-nd-msub-key');
		if (key) subByKey[subKey(key)] = sub.getAttribute('data-nd-msub');
	});

	/* Картинки подставляем при первом открытии: панель скрыта через hidden,
	   и в скрытом блоке браузер откладывает загрузку — список открывался
	   с пустыми миниатюрами (та же грабля, что у выпадающего каталога). */
	function loadSubImages(sub) {
		if (sub.ndImagesLoaded) return;
		sub.ndImagesLoaded = true;
		Array.prototype.forEach.call(sub.querySelectorAll('img[data-nd-src]'), function (img) {
			img.src = img.getAttribute('data-nd-src');
			img.removeAttribute('data-nd-src');
		});
	}

	/* Пока панель открыта, красной в нижней панели должна быть её вкладка,
	   а не вкладка текущего раздела — как и со шторками. */
	var subTab = null;

	function closeSubs() {
		Array.prototype.forEach.call(document.querySelectorAll('.nd-msub'), function (sub) {
			sub.hidden = true;
		});
		if (subTab) {
			subTab.classList.remove('is-active');
			subTab = null;
			if (pageTab) pageTab.classList.add('is-active');
		}
		openSub = null;
	}

	function openSubPanel(name) {
		var sub = subs[name];
		if (!sub) return false;
		closeSubs();
		loadSubImages(sub);
		sub.hidden = false;
		var list = sub.querySelector('.nd-msub__list');
		if (list) list.scrollTop = 0;
		openSub = name;

		/* Корень стопки знает свой адрес — по нему и находим вкладку. */
		var root = sub;
		while (root.getAttribute('data-nd-msub-parent') && subs[root.getAttribute('data-nd-msub-parent')]) {
			root = subs[root.getAttribute('data-nd-msub-parent')];
		}
		var key = root.getAttribute('data-nd-msub-key');
		if (key) {
			subTab = navbar.querySelector('a[href="' + key + '"]');
			if (subTab) {
				if (pageTab) pageTab.classList.remove('is-active');
				subTab.classList.add('is-active');
			}
		}

		document.body.classList.add('nd-sheet-open');
		return true;
	}

	/* «Назад»: к родительской панели, а если её нет — к панели «Меню»
	   (когда её открывали) или просто закрыть. */
	function subBack() {
		if (!openSub) return;
		var parent = subs[openSub].getAttribute('data-nd-msub-parent');
		if (parent && subs[parent]) {
			openSubPanel(parent);
			return;
		}
		closeSubs();
		if (openName === 'menu') document.body.classList.add('nd-sheet-open');
		else if (!openName) document.body.classList.remove('nd-sheet-open');
	}

	document.addEventListener('click', function (e) {
		if (!e.target.closest) return;

		var back = e.target.closest('[data-nd-msub-back]');
		if (back) {
			e.preventDefault();
			subBack();
			return;
		}

		var subOpener = e.target.closest('[data-nd-msub-open]');
		if (subOpener && openSubPanel(subOpener.getAttribute('data-nd-msub-open'))) {
			e.preventDefault();
			return;
		}

		/* Пункт меню или вкладка «Каталог», у которых есть своя панель:
		   тап раскрывает список, а не уводит по ссылке. Без JS ссылка
		   остаётся рабочей. */
		var link = e.target.closest('a[href]');
		if (link && !e.target.closest('.nd-msub')) {
			var name = subByKey[subKey(link.getAttribute('href') || '')];
			if (name && (navbar.contains(link) || (sheets.menu && sheets.menu.contains(link)))) {
				e.preventDefault();
				openSubPanel(name);
				return;
			}
		}

		var opener = e.target.closest('[data-nd-open]');
		if (opener) {
			e.preventDefault();
			closeSubs();
			open(opener.getAttribute('data-nd-open'));
			return;
		}

		if (e.target.closest('[data-nd-close]')) {
			e.preventDefault();
			closeSubs();
			close();
			document.body.classList.remove('nd-sheet-open');
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
		if (e.key !== 'Escape') return;
		if (openSub) { subBack(); return; }
		close();
	});

	// Ссылка внутри открытой шторки ведёт на другую страницу — шторку
	// закрываем, иначе при возврате «назад» из кеша браузера она останется
	// открытой поверх контента.
	window.addEventListener('pagehide', function () {
		closeSubs();
		close();
	});

	var header = document.getElementById('nd-mheader');
	if (header) {
		var onScroll = function () {
			header.classList.toggle('is-scrolled', window.pageYOffset > 4);
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}
})();
