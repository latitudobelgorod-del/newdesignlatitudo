/**
 * Кружки цветов на плитках каталога — одной пачкой.
 *
 * В шаблонах плиток стоял свой $(document).ready, который на КАЖДУЮ плитку слал
 * /ajax/get_colors.php?id=… На главной это 34 запроса, каждый поднимает ядро
 * Битрикса целиком (на боевом 1,3–1,4 с), и на HTTP/1.1 они вставали в очередь
 * по шесть штук — за ними ждали и картинки. Страница «догружалась» секунд 20
 * (Ирина, 19 августа 2026).
 *
 * Здесь собираем все плитки разом и спрашиваем /ajax/get_colors.php?ids=…
 * Ответ — JSON {ID: html}. Тем, кого в ответе нет, цветов не нашлось: прячем,
 * как и раньше.
 *
 * Плитки приезжают и после загрузки — фильтр, «показать ещё», слайдеры на
 * главной, — поэтому следим за DOM и добираем новые той же пачкой.
 */
(function () {
	'use strict';

	if (window.__ndSkuColors) return;
	window.__ndSkuColors = true;

	var SELECTOR = '.list__catalog_sku[data-product-id]';
	var CHUNK = 100;          // столько ID эндпоинт берёт за раз
	var pending = [];
	var timer = null;

	function collect() {
		var blocks = [].slice.call(document.querySelectorAll(SELECTOR));
		var fresh = [];

		blocks.forEach(function (block) {
			if (block.__ndColors) return;
			block.__ndColors = true;
			if (block.getAttribute('data-product-id')) fresh.push(block);
		});

		if (fresh.length) {
			pending = pending.concat(fresh);
			schedule();
		}
	}

	/* Небольшая пауза: слайдеры дорисовывают плитки пачками, и без неё на
	   каждую пачку ушёл бы свой запрос. */
	function schedule() {
		clearTimeout(timer);
		timer = setTimeout(send, 50);
	}

	function send() {
		var batch = pending.splice(0, CHUNK);
		if (!batch.length) return;

		/* Одинаковые товары встречаются на странице дважды (акции и хиты) —
		   в запрос ID уходит один раз, разметку получают обе плитки. */
		var byId = {};
		var ids = [];

		batch.forEach(function (block) {
			var id = block.getAttribute('data-product-id');
			if (!byId[id]) { byId[id] = []; ids.push(id); }
			byId[id].push(block);
		});

		var xhr = new XMLHttpRequest();
		xhr.open('GET', '/ajax/get_colors.php?ids=' + ids.join(','), true);

		xhr.onload = function () {
			var data = null;

			try {
				data = JSON.parse(xhr.responseText);
			} catch (e) {
				/* Ответ не разобрался — плитки оставляем как есть, пустой блок
				   в вёрстке ничего не ломает. */
				return;
			}

			ids.forEach(function (id) {
				var html = data && data[id];

				byId[id].forEach(function (block) {
					if (html) block.innerHTML = html;
					else block.style.display = 'none';
				});
			});
		};

		xhr.send();

		if (pending.length) schedule();
	}

	if (document.readyState !== 'loading') collect();
	else document.addEventListener('DOMContentLoaded', collect);

	/* Новые плитки: фильтр, подгрузка следующей страницы, слайдеры. */
	if (window.MutationObserver) {
		var watching = false;

		var start = function () {
			if (watching || !document.body) return;
			watching = true;
			new MutationObserver(function (records) {
				for (var i = 0; i < records.length; i++) {
					if (records[i].addedNodes.length) { collect(); return; }
				}
			}).observe(document.body, { childList: true, subtree: true });
		};

		if (document.body) start();
		else document.addEventListener('DOMContentLoaded', start);
	}
})();
