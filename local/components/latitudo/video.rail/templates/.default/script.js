/* Лента «Видео о продукции» (latitudo:video.rail, 28.10.2026): стрелки,
   фильтр по категориям на месте, окно с плеером и товаром. Плеер (iframe)
   ставим только при открытии окна и снимаем при закрытии — до клика на
   странице одни превью. */
(function () {
	function init(root) {
		if (root.ndVrReady) return;
		root.ndVrReady = true;

		var data = [];
		try { data = JSON.parse(root.querySelector('[data-nd-vr-data]').textContent); } catch (e) { return; }

		var rail = root.querySelector('[data-nd-vr-rail]');
		var cards = [].slice.call(root.querySelectorAll('[data-nd-vr-i]'));
		var arrows = [].slice.call(root.querySelectorAll('[data-nd-vr-dir]'));
		var filters = [].slice.call(root.querySelectorAll('[data-nd-vr-filter]'));

		// окно — в конец body: у предков бывает transform/overflow, fixed внутри них ломается
		var modal = root.querySelector('[data-nd-vr-modal]');
		document.body.appendChild(modal);
		var q = function (name) { return modal.querySelector('[data-nd-vr-' + name + ']'); };
		var player = q('player'), kicker = q('kicker'), title = q('title'), variants = q('variants'),
			product = q('product'), price = q('price'), buy = q('buy'), msg = q('msg'), link = q('link');

		/* --- лента --- */
		function updateArrows() {
			var max = rail.scrollWidth - rail.clientWidth - 2;
			arrows[0] && (arrows[0].disabled = rail.scrollLeft <= 2);
			arrows[1] && (arrows[1].disabled = rail.scrollLeft >= max);
		}
		arrows.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var dir = parseInt(btn.getAttribute('data-nd-vr-dir'), 10) || 1;
				rail.scrollBy({ left: dir * rail.clientWidth * 0.8, behavior: 'smooth' });
			});
		});
		rail.addEventListener('scroll', updateArrows, { passive: true });
		window.addEventListener('resize', updateArrows);

		/* фильтры: категории, а на странице «Видео» ещё и марки — работают вместе.
		   Числа на кнопках считаются с учётом другого фильтра, пустые кнопки
		   прячутся: у LATITUDO нет ступеней — нет и «Ступеней» (Ирина, 28.10.2026). */
		var picked = {};
		var groups = filters.map(function (group) {
			var key = group.getAttribute('data-nd-vr-filter');
			picked[key] = '';
			return { key: key, chips: [].slice.call(group.querySelectorAll('[data-nd-vr-val]')) };
		});

		function matches(card, except) {
			return Object.keys(picked).every(function (key) {
				return key === except || picked[key] === '' || card.getAttribute('data-nd-vr-' + key) === picked[key];
			});
		}

		function setActive(g, chip) {
			picked[g.key] = chip.getAttribute('data-nd-vr-val');
			g.chips.forEach(function (c) {
				var on = c === chip;
				c.classList.toggle('is-active', on);
				c.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
		}

		function applyFilters() {
			groups.forEach(function (g) {
				g.chips.forEach(function (chip) {
					var val = chip.getAttribute('data-nd-vr-val');
					var n = cards.filter(function (card) {
						return matches(card, g.key) && (val === '' || card.getAttribute('data-nd-vr-' + g.key) === val);
					}).length;
					var num = chip.querySelector('.nd-vr__chip-n');
					if (num) num.textContent = n;
					chip.hidden = val !== '' && n === 0;
					// выбранная кнопка опустела (сменили марку) — возвращаемся к «Все»
					if (chip.hidden && chip.classList.contains('is-active')) setActive(g, g.chips[0]);
				});
			});
			cards.forEach(function (card) { card.hidden = !matches(card, null); });
			rail.scrollLeft = 0;
			updateArrows();
		}

		groups.forEach(function (g) {
			g.chips.forEach(function (chip) {
				chip.addEventListener('click', function () {
					setActive(g, chip);
					applyFilters();
					applyFilters(); // второй проход: сброс пустой кнопки меняет числа другого ряда
				});
			});
		});

		/* --- окно --- */
		var cur = null, lastFocus = null;

		function selectProduct(k) {
			var p = cur.products[k];
			[].forEach.call(variants.children, function (b, j) {
				b.classList.toggle('is-active', j === k);
				b.setAttribute('aria-pressed', j === k ? 'true' : 'false');
			});
			product.textContent = p.name;
			price.textContent = p.price;
			buy.hidden = !p.price;
			buy.disabled = false;
			msg.textContent = '';
			link.href = p.url;
		}

		function open(i) {
			cur = data[i];
			if (!cur) return;
			lastFocus = document.activeElement;
			kicker.textContent = [cur.cat, cur.series].filter(Boolean).join(' · ');
			title.textContent = cur.name;

			var frame = document.createElement('iframe');
			frame.src = cur.embed;
			frame.title = cur.name;
			frame.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media; clipboard-write';
			frame.setAttribute('allowfullscreen', '');
			player.innerHTML = '';
			player.appendChild(frame);

			variants.innerHTML = '';
			if (cur.products.length > 1) {
				cur.products.forEach(function (p, k) {
					var b = document.createElement('button');
					b.type = 'button';
					b.className = 'nd-vr-modal__variant';
					b.textContent = p.label;
					b.title = p.name;
					b.addEventListener('click', function () { selectProduct(k); });
					variants.appendChild(b);
				});
			}
			var has = cur.products.length > 0;
			[variants, product, price, link].forEach(function (el) { el.hidden = !has; });
			buy.hidden = !has;
			variants.hidden = cur.products.length < 2;
			if (has) selectProduct(0);

			modal.hidden = false;
			document.documentElement.classList.add('nd-vr-lock');
			q('close').focus();
		}

		function close() {
			if (modal.hidden) return;
			modal.hidden = true;
			player.innerHTML = '';
			document.documentElement.classList.remove('nd-vr-lock');
			if (lastFocus && lastFocus.focus) lastFocus.focus();
		}

		cards.forEach(function (card) {
			card.addEventListener('click', function (e) {
				// ссылка на товар под карточкой ведёт на товар, остальное открывает видео
				if (e.target.closest('a')) return;
				open(parseInt(card.getAttribute('data-nd-vr-i'), 10));
			});
		});
		q('close').addEventListener('click', close);
		modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
		document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });

		buy.addEventListener('click', function () {
			var p = cur && cur.products[[].indexOf.call(variants.children, variants.querySelector('.is-active'))] || (cur && cur.products[0]);
			if (!p || typeof window.ndCalcBasket !== 'function') return;
			window.ndCalcBasket(buy, msg, [{ id: p.buyId, qty: 1 }]);
		});

		updateArrows();
	}

	function run() {
		[].forEach.call(document.querySelectorAll('[data-nd-vr]'), init);
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
	else run();
})();
