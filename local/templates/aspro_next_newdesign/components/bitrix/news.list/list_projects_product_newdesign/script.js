// Слайдер блока «Проекты с товаром»: стрелки листают ленту по видимой
// странице, счётчик показывает номер страницы. Разметка остаётся рабочей
// и без скрипта — лента прокручивается пальцем и колесом.
(function () {
	'use strict';

	function init(root) {
		if (root.ndRelInited) return;
		root.ndRelInited = true;

		var track = root.querySelector('[data-nd-relprojects-track]');
		var prev = root.querySelector('[data-nd-relprojects-prev]');
		var next = root.querySelector('[data-nd-relprojects-next]');
		var counter = root.querySelector('[data-nd-relprojects-counter]');
		if (!track) return;

		function pad(n) { return n < 10 ? '0' + n : String(n); }

		/* Считаем страницами из карточек, а не сдвигом ленты на её ширину: у ленты
		   включено «прилипание» (scroll-snap), и после программного сдвига браузер
		   доводил её до ближайшей карточки — счётчик перескакивал через страницу,
		   а «назад» возвращался туда же, откуда ушли (Ирина, 23.09.2026). */
		function items() {
			return [].slice.call(track.children).filter(function (el) { return el.nodeType === 1; });
		}

		function metrics() {
			var list = items();
			var w = track.clientWidth || 1;
			if (!list.length) return { list: list, per: 1, total: 1, current: 1, atStart: true, atEnd: true };
			var step = list.length > 1 ? (list[1].offsetLeft - list[0].offsetLeft) : list[0].offsetWidth;
			if (step < 1) step = list[0].offsetWidth || w;
			var per = Math.max(1, Math.round(w / step));
			var total = Math.max(1, Math.ceil(list.length / per));
			var first = Math.round(track.scrollLeft / step);
			var current = Math.min(total, Math.floor(first / per) + 1);
			return {
				list: list, step: step, per: per, total: total, current: current,
				atStart: track.scrollLeft <= 1,
				atEnd: track.scrollLeft + w >= track.scrollWidth - 1
			};
		}

		function render() {
			var s = metrics();
			if (counter) counter.textContent = pad(s.current) + '/' + pad(s.total);
			if (prev) prev.disabled = s.atStart;
			if (next) next.disabled = s.atEnd;
			var nav = root.querySelector('.nd-relprojects__nav');
			if (nav) nav.hidden = (s.total <= 1);
		}

		function goPage(dir) {
			var s = metrics();
			var page = Math.min(s.total, Math.max(1, s.current + dir));
			var target = s.list[Math.min(s.list.length - 1, (page - 1) * s.per)];
			if (!target) return;
			var left = target.offsetLeft - s.list[0].offsetLeft;
			if (track.scrollTo) track.scrollTo({ left: left, behavior: 'smooth' });
			else track.scrollLeft = left;
		}

		if (prev) prev.addEventListener('click', function () { goPage(-1); });
		if (next) next.addEventListener('click', function () { goPage(1); });
		track.addEventListener('scroll', render);
		window.addEventListener('resize', render);
		render();
	}

	function initAll() {
		var list = document.querySelectorAll('[data-nd-relprojects]');
		for (var i = 0; i < list.length; i++) init(list[i]);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}
	// товар перерисовывает вкладки аяксом — поднимаемся ещё раз после загрузки
	window.addEventListener('load', initAll);
})();
