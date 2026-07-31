// Контакты региона: пролистывание галереи стрелками и переключение карт
// (офис / склад) кнопками под ними.
(function () {
	'use strict';

	function initGallery(root) {
		var track = root.querySelector('[data-nd-gallery-track]');
		var prev = root.querySelector('[data-nd-gallery-prev]');
		var next = root.querySelector('[data-nd-gallery-next]');
		if (!track) return;

		function render() {
			var scrollable = track.scrollWidth - track.clientWidth > 1;
			if (prev) prev.disabled = !scrollable || track.scrollLeft <= 1;
			if (next) next.disabled = !scrollable || track.scrollLeft + track.clientWidth >= track.scrollWidth - 1;
		}

		if (prev) prev.addEventListener('click', function () { track.scrollLeft -= track.clientWidth; });
		if (next) next.addEventListener('click', function () { track.scrollLeft += track.clientWidth; });
		track.addEventListener('scroll', render);
		window.addEventListener('resize', render);
		render();
	}

	function initMaps(root) {
		var btns = root.querySelectorAll('[data-nd-map-btn]');
		var maps = root.querySelectorAll('[data-nd-map]');
		if (btns.length < 2) return; // одна карта — переключать нечего

		for (var i = 0; i < btns.length; i++) {
			btns[i].addEventListener('click', function () {
				var idx = this.getAttribute('data-nd-map-btn');
				for (var j = 0; j < maps.length; j++) {
					maps[j].classList.toggle('is-active', maps[j].getAttribute('data-nd-map') === idx);
				}
				for (var k = 0; k < btns.length; k++) {
					btns[k].classList.toggle('is-active', btns[k] === this);
				}
			});
		}
	}

	function initAll() {
		var galleries = document.querySelectorAll('[data-nd-gallery]');
		for (var i = 0; i < galleries.length; i++) initGallery(galleries[i]);
		var maps = document.querySelectorAll('[data-nd-maps]');
		for (var j = 0; j < maps.length; j++) initMaps(maps[j]);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}
})();
