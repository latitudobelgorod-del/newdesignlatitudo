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

	function init() {
		initDesc();
		initAnchors();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
