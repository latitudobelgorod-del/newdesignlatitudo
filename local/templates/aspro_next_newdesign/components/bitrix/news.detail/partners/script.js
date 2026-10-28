/* Страница бренда нового дизайна, только телефон (Ирина, 24.09.2026):
   - описание марки сворачивается шторкой «Показать все», как «Описание»
     в карточке товара (js/newdesign-element.js, syncDesc);
   - якоря разделов показываются в три строки с кнопкой «Ещё N» / «Свернуть»,
     как теги в разделе каталога (js/newdesign-catalog.js, collapseTags).
   На компьютере всё видно целиком. */
(function () {
	'use strict';

	var mobileQuery = window.matchMedia ? window.matchMedia('(max-width: 767px)') : null;

	function isMobile() {
		return !!(mobileQuery && mobileQuery.matches);
	}

	/* Если после сворачивания верх блока ушёл под шапку — возвращаем его в окно,
	   иначе под пальцем оказывается уже другой блок. */
	function keepInView(el) {
		var head = document.getElementById('nd-mheader');
		var offset = head ? Math.round(head.getBoundingClientRect().height) : 0;
		var top = el.getBoundingClientRect().top;
		if (top < offset) {
			window.scrollTo(0, window.pageYOffset + top - offset - 16);
		}
	}

	/* ---------------- описание ---------------- */
	var DESC_LIMIT = 208;   // та же обрезка, что у описания товара на телефоне
	var DESC_SHADE = 96;    // запас: короче этого сворачивать нет смысла

	function initDesc() {
		var box = document.querySelector('.nd-brandhead__text');
		var btn = box && box.querySelector('.nd-brandhead__more');
		if (!box || !btn) return;
		var label = btn.querySelector('.nd-brandhead__more-text');

		function sync() {
			if (!isMobile()) {
				box.classList.remove('is-collapsed');
				box.removeAttribute('data-nd-open');
				btn.hidden = true;
				return;
			}
			if (box.getAttribute('data-nd-open') === '1') return;

			btn.hidden = true;
			box.classList.remove('is-collapsed');
			if (box.scrollHeight <= DESC_LIMIT + DESC_SHADE) return;

			box.classList.add('is-collapsed');
			btn.hidden = false;
			btn.setAttribute('aria-expanded', 'false');
			if (label) label.textContent = 'Показать все';
		}

		btn.addEventListener('click', function () {
			var collapsed = box.classList.toggle('is-collapsed');
			box.setAttribute('data-nd-open', collapsed ? '0' : '1');
			btn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
			if (label) label.textContent = collapsed ? 'Показать все' : 'Скрыть';
			if (collapsed) keepInView(box);
		});

		sync();
		window.addEventListener('load', sync);
		var rz;
		window.addEventListener('resize', function () {
			clearTimeout(rz);
			rz = setTimeout(sync, 150);
		});
	}

	/* ---------------- якоря разделов ---------------- */
	var ANCHOR_ROWS = 3;

	function initAnchors() {
		var box = document.querySelector('.nd-brandsect__anchors');
		if (!box) return;
		var chips = [].slice.call(box.querySelectorAll('.nd-brandsect__anchor'));
		if (!chips.length) return;

		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'nd-catlist-sort__more nd-brandsect__toggle';
		btn.hidden = true;
		box.appendChild(btn);

		function collapse() {
			box.classList.remove('nd-tags-open');
			chips.forEach(function (c) { c.classList.remove('nd-tag-hidden'); });
			btn.hidden = true;

			if (!isMobile()) {
				return;
			}

			/* Меряем по фактическому положению: сколько таблеток умещается
			   в три строки. */
			var rowTops = [];
			var cut = -1;
			for (var i = 0; i < chips.length; i++) {
				var t = chips[i].getBoundingClientRect().top;
				if (!rowTops.length || t > rowTops[rowTops.length - 1] + 2) rowTops.push(t);
				if (rowTops.length > ANCHOR_ROWS) { cut = i; break; }
			}
			if (cut < 0) return;

			for (var j = cut; j < chips.length; j++) chips[j].classList.add('nd-tag-hidden');
			btn.hidden = false;
			btn.textContent = 'Ещё ' + (chips.length - cut);
			btn.title = 'Показать все разделы';

			/* Кнопка должна остаться в третьей строке: перескочила — прячем
			   ещё одну таблетку. */
			while (cut > 1) {
				var last = chips[cut - 1].getBoundingClientRect();
				if (btn.getBoundingClientRect().top <= last.top + 2) break;
				cut--;
				chips[cut].classList.add('nd-tag-hidden');
				btn.textContent = 'Ещё ' + (chips.length - cut);
			}
		}

		btn.addEventListener('click', function () {
			if (box.classList.contains('nd-tags-open')) {
				collapse();
				keepInView(box);
			} else {
				box.classList.add('nd-tags-open');
				chips.forEach(function (c) { c.classList.remove('nd-tag-hidden'); });
				btn.textContent = 'Свернуть';
				btn.title = 'Свернуть список';
			}
		});

		collapse();
		var rz;
		var width = window.innerWidth;
		window.addEventListener('resize', function () {
			/* На телефоне resize приходит и от скрытия адресной строки при
			   прокрутке — пересчитываем только при смене ширины. */
			if (window.innerWidth === width) return;
			width = window.innerWidth;
			clearTimeout(rz);
			rz = setTimeout(function () {
				if (!box.classList.contains('nd-tags-open')) collapse();
			}, 150);
		});
	}

	/* ---------------- переходы по якорям страницы ---------------- */
	/* Якоря разделов, «Документация и сертификаты», «Видео товаров …» ведут
	   через всю ленту товаров (Ирина, 28.10.2026). Плавная прокрутка
	   (html { scroll-behavior: smooth } в css/custom.css) тянулась через тысячи
	   пикселей с подгружающимися картинками: на телефоне страница тормозила и
	   останавливалась не там. Переходим сразу, с поправкой на шапку, а если
	   ленивые картинки выше сдвинули блок — доводим ещё раз. */
	function headerOffset() {
		/* на телефоне шапка своя и ниже — --nd-header-h там всё равно 156 */
		var head = isMobile() ? document.getElementById('nd-mheader') : null;
		var h = head ? Math.round(head.getBoundingClientRect().height)
			: parseInt(getComputedStyle(document.documentElement).getPropertyValue('--nd-header-h'), 10) || 156;
		return h + 16;
	}

	/* Разделы, которые страница не напечатала сразу, догружаются при подъезде к
	   экрану (js/newdesign-catalog.js, data-nd-brand-lazy) — и выше выбранного
	   раздела вырастают уже после перехода. Поэтому ещё несколько секунд держим
	   раздел на месте после каждой догрузки (событие nd:appended), пока
	   покупатель сам не начал листать. */
	var HOLD_MS = 6000;
	var stopHold = null;

	function jumpTo(target) {
		if (stopHold) stopHold();
		var go = function () {
			var top = target.getBoundingClientRect().top - headerOffset();
			if (Math.abs(top) > 4) window.scrollTo({ top: window.pageYOffset + top, behavior: 'instant' });
		};
		var timers = [250, 700, 1500].map(function (ms) { return setTimeout(go, ms); });
		var userEvents = ['wheel', 'touchstart', 'keydown', 'mousedown'];
		var stop = function () {
			timers.forEach(clearTimeout);
			clearTimeout(timer);
			document.removeEventListener('nd:appended', go);
			userEvents.forEach(function (ev) { window.removeEventListener(ev, stop, true); });
			stopHold = null;
		};
		var timer = setTimeout(stop, HOLD_MS);
		go();
		document.addEventListener('nd:appended', go);
		userEvents.forEach(function (ev) { window.addEventListener(ev, stop, true); });
		stopHold = stop;
	}

	function initPageLinks() {
		document.addEventListener('click', function (e) {
			var a = e.target.closest('.nd-brandhead__text a[href^="#"], .nd-brandsect__anchor');
			if (!a) return;
			var id = a.getAttribute('href').slice(1);
			var target = id && document.getElementById(id);
			if (!target) return;
			e.preventDefault();
			if (history.pushState) history.pushState(null, '', '#' + id);
			jumpTo(target);
		});

		var box = document.querySelector('.nd-brandhead__text');
		if (!box) return;

		/* Ролики у марки есть — рядом с «Документацией» ставим ссылку на них. */
		var video = document.getElementById('video');
		var docs = box.querySelector('a[href="#dokumentatsiya"]');
		if (video && docs && !box.querySelector('a[href="#video"]')) {
			var h1 = document.querySelector('h1');
			var link = document.createElement('a');
			link.href = '#video';
			link.className = 'nd-brandhead__videolink';
			link.textContent = 'Видео товаров' + (h1 ? ' ' + h1.textContent.trim() : '');
			docs.parentNode.insertBefore(link, docs.nextSibling);
		}
	}

	function init() {
		initDesc();
		initAnchors();
		initPageLinks();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
