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

		function pages() {
			return Math.max(1, Math.ceil(track.scrollWidth / track.clientWidth));
		}

		function current() {
			return Math.min(pages(), Math.round(track.scrollLeft / track.clientWidth) + 1);
		}

		function render() {
			var total = pages();
			if (counter) counter.textContent = pad(current()) + '/' + pad(total);
			var atStart = track.scrollLeft <= 1;
			var atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 1;
			if (prev) prev.disabled = atStart;
			if (next) next.disabled = atEnd;
			// один экран — листать нечего, прячем управление целиком
			var nav = root.querySelector('.nd-relprojects__nav');
			if (nav) nav.hidden = (total <= 1);
		}

		function scrollBy(dir) {
			track.scrollLeft += dir * track.clientWidth;
		}

		if (prev) prev.addEventListener('click', function () { scrollBy(-1); });
		if (next) next.addEventListener('click', function () { scrollBy(1); });
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
