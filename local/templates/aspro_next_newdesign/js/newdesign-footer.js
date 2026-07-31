/*
 * Подвал нового дизайна.
 *
 * 1. Аккордеон колонок меню — работает только на мобильном (<768), на десктопе
 *    списки всегда открыты, а заголовок остаётся обычной ссылкой.
 * 2. Дозагрузка видео звонка: <source> у него с data-src, чтобы видео не
 *    тянулось на каждой странице до того, как подвал попал в зону видимости.
 */
(function () {
	'use strict';

	var ACC_BREAKPOINT = 767;

	function isAccordion() {
		return window.matchMedia('(max-width: ' + ACC_BREAKPOINT + 'px)').matches;
	}

	document.addEventListener('click', function (e) {
		if (!isAccordion()) return;

		var head = e.target.closest ? e.target.closest('.nd-footer [data-nd-acc-head]') : null;
		if (!head) return;

		var group = head.parentNode;
		if (!group || !group.classList.contains('nd-fmenu__group')) return;

		e.preventDefault();
		group.classList.toggle('is-open');
	});

	/* Видео подгружаем, когда подвал показался на экране. Без IntersectionObserver
	   (старые браузеры) просто грузим сразу — поведение как в старом подвале. */
	function loadVideo(video) {
		var sources = video.querySelectorAll('source[data-src]');
		if (!sources.length) return;
		for (var i = 0; i < sources.length; i++) {
			sources[i].src = sources[i].getAttribute('data-src');
			sources[i].removeAttribute('data-src');
		}
		video.load();
		var playing = video.play();
		if (playing && playing.catch) playing.catch(function () {});
	}

	function initVideo() {
		var wrap = document.querySelector('.nd-fpromo__phone');
		if (!wrap) return;
		var video = wrap.querySelector('video');
		if (!video) return;

		/* Ниже 992 вместо видео показывается фотография — грузить его незачем. */
		if (window.matchMedia('(max-width: 991px)').matches) return;

		if (!('IntersectionObserver' in window)) {
			loadVideo(video);
			return;
		}

		var io = new IntersectionObserver(function (entries) {
			for (var i = 0; i < entries.length; i++) {
				if (entries[i].isIntersecting) {
					loadVideo(video);
					io.disconnect();
					return;
				}
			}
		}, { rootMargin: '200px' });
		io.observe(wrap);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initVideo);
	} else {
		initVideo();
	}
})();
