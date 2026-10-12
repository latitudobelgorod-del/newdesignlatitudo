// Контакты региона: пролистывание галереи стрелками и переключение карт
// (офис / склад) кнопками под ними.
(function () {
	'use strict';

	/* Карты 2ГИС: вместо iframe конструктора (он писал в историю браузера,
	   см. result_modifier.php) — пустой блок с data-nd-2gis, карту рисуем
	   API 2ГИС 2.0. Скрипт API грузим один раз и только когда карта нужна;
	   скрытую карту (вторая кнопка) — при показе, иначе у неё нулевой размер. */
	var DG_LOADER = 'https://maps.api.2gis.ru/2.0/loader.js?pkg=basic';
	var dgQueue = null;

	function withDG(cb) {
		if (window.DG && window.DG.then) { window.DG.then(cb); return; }
		if (dgQueue) { dgQueue.push(cb); return; }
		dgQueue = [cb];
		var s = document.createElement('script');
		s.src = DG_LOADER;
		s.async = true;
		s.onload = function () {
			var q = dgQueue;
			for (var i = 0; i < q.length; i++) window.DG.then(q[i]);
		};
		document.head.appendChild(s);
	}

	function initTwoGis(scope) {
		var boxes = scope.querySelectorAll('[data-nd-2gis]');
		for (var i = 0; i < boxes.length; i++) {
			var box = boxes[i];
			if (box.ndMapReady || !box.offsetWidth) continue; // скрыта — дорисуем при показе
			box.ndMapReady = true;
			(function (box) {
				var cfg;
				try { cfg = JSON.parse(box.getAttribute('data-nd-2gis')); } catch (e) { return; }
				withDG(function () {
					var map = window.DG.map(box, {
						center: [cfg.lat, cfg.lon],
						zoom: cfg.zoom,
						scrollWheelZoom: !!cfg.wheel,
						fullscreenControl: false
					});
					(cfg.points || []).forEach(function (p) {
						var marker = window.DG.marker([p.lat, p.lon]).addTo(map);
						var text = [p.title, p.descr].filter(Boolean).join('<br>');
						if (text) marker.bindPopup(text);
					});
				});
			})(box);
		}
	}

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
				initTwoGis(root);
			});
		}
	}

	function initAll() {
		var galleries = document.querySelectorAll('[data-nd-gallery]');
		for (var i = 0; i < galleries.length; i++) initGallery(galleries[i]);
		var maps = document.querySelectorAll('[data-nd-maps]');
		for (var j = 0; j < maps.length; j++) initMaps(maps[j]);
		initTwoGis(document);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}
})();
