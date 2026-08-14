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

		function state() {
			var w = track.clientWidth || 1;
			var total = Math.max(1, Math.ceil((track.scrollWidth - 1) / w));
			var atEnd = track.scrollLeft + w >= track.scrollWidth - 1;
			return {
				w: w,
				total: total,
				atStart: track.scrollLeft <= 1,
				atEnd: atEnd,
				// последняя страница неполная: лента упирается в край раньше, чем
				// пройдёт целый экран, и номер по scrollLeft был бы на единицу меньше
				current: atEnd ? total : Math.min(total, Math.floor(track.scrollLeft / w) + 1)
			};
		}

		function render() {
			var s = state();
			if (counter) counter.textContent = pad(s.current) + '/' + pad(s.total);
			if (prev) prev.disabled = s.atStart;
			if (next) next.disabled = s.atEnd;
			// один экран — листать нечего, прячем управление целиком
			var nav = root.querySelector('.nd-relprojects__nav');
			if (nav) nav.hidden = (s.total <= 1);
		}

		// прыгаем на номер страницы, а не сдвигаем ленту на экран: на последней
		// странице она стоит впритык к краю, и сдвиг назад промахивался мимо начала
		function goPage(dir) {
			var s = state();
			var target = Math.min(s.total, Math.max(1, s.current + dir));
			track.scrollLeft = Math.min((target - 1) * s.w, track.scrollWidth - s.w);
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
