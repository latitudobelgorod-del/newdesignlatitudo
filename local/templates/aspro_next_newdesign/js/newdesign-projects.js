/* ---------------------------------------------------------------------------
   Галерея проекта — новый дизайн (.nd-gal).

   Лента листается нативной прокруткой со scroll-snap, здесь только связка:
   стрелки прокручивают на кадр, превью — к своему кадру, а активное превью
   и видимость стрелок обновляются по событию scroll. Держим на нём, а не на
   счётчике: ленту можно листать пальцем и колесом, и счётчик разъезжался бы.
   ------------------------------------------------------------------------ */
(function () {
	'use strict';

	function initGallery(root) {
		if (root.__ndGal) return;
		root.__ndGal = true;

		var strip = root.querySelector('[data-nd-gal-strip]');
		if (!strip) return;

		var slides = [].slice.call(strip.querySelectorAll('[data-nd-gal-slide]'));
		var thumbs = [].slice.call(root.querySelectorAll('[data-nd-gal-thumb]'));
		var prev = root.querySelector('[data-nd-gal-prev]');
		var next = root.querySelector('[data-nd-gal-next]');
		if (!slides.length) return;

		/* Текущим считаем кадр, чья левая граница ближе всего к левому краю
		   ленты, — так же, как его показывает scroll-snap. */
		function current() {
			var best = 0, bestDist = Infinity;
			var left = strip.getBoundingClientRect().left;
			slides.forEach(function (s, i) {
				var d = Math.abs(s.getBoundingClientRect().left - left);
				if (d < bestDist) { bestDist = d; best = i; }
			});
			return best;
		}

		function scrollToSlide(i) {
			i = Math.max(0, Math.min(slides.length - 1, i));
			strip.scrollTo({ left: slides[i].offsetLeft - slides[0].offsetLeft, behavior: 'smooth' });
		}

		function sync() {
			var i = current();
			thumbs.forEach(function (t, k) { t.classList.toggle('is-active', k === i); });
			/* Сравнение с допуском в 1px: при дробном масштабе страницы
			   scrollLeft не дотягивает до конца ровно на доли пикселя. */
			if (prev) prev.classList.toggle('is-hidden', strip.scrollLeft <= 1);
			if (next) next.classList.toggle('is-hidden', strip.scrollLeft >= strip.scrollWidth - strip.clientWidth - 1);
		}

		if (prev) prev.addEventListener('click', function () { scrollToSlide(current() - 1); });
		if (next) next.addEventListener('click', function () { scrollToSlide(current() + 1); });

		thumbs.forEach(function (t) {
			t.addEventListener('click', function () {
				scrollToSlide(parseInt(t.getAttribute('data-nd-gal-thumb'), 10) || 0);
			});
		});

		var tm;
		strip.addEventListener('scroll', function () {
			if (tm) return;
			tm = setTimeout(function () { tm = 0; sync(); }, 80);
		});
		window.addEventListener('resize', sync);
		sync();
	}

	function run() {
		[].forEach.call(document.querySelectorAll('[data-nd-gal]'), initGallery);
	}

	if (document.readyState !== 'loading') run();
	else document.addEventListener('DOMContentLoaded', run);
})();
