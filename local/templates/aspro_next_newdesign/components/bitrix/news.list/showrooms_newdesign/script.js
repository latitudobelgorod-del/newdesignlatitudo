/**
 * Карусель шоу-румов на странице «О компании».
 *
 * Слайды лежат в ряд внутри .nd-shr__viewport с overflow:hidden, стрелки
 * двигают трек на ширину вьюпорта. Ширину считаем от вьюпорта, а не от
 * слайда, — так один и тот же код работает и на десктопе (один слайд в
 * кадре), и на мобилке.
 */
(function () {
	'use strict';

	function initShowrooms(root) {
		if (root.ndShrReady) {
			return;
		}
		root.ndShrReady = true;

		var track = root.querySelector('[data-nd-shr-track]');
		var prev = root.querySelector('[data-nd-shr-prev]');
		var next = root.querySelector('[data-nd-shr-next]');
		var counter = root.querySelector('[data-nd-shr-counter]');
		if (!track) {
			return;
		}

		var total = track.children.length;
		var index = 0;

		function pad(n) {
			return (n < 10 ? '0' : '') + n;
		}

		function render() {
			track.style.transform = 'translateX(' + (-index * 100) + '%)';
			if (counter) {
				counter.textContent = pad(index + 1) + '/' + pad(total);
			}
			if (prev) {
				prev.disabled = index === 0;
			}
			if (next) {
				next.disabled = index >= total - 1;
			}
		}

		if (prev) {
			prev.addEventListener('click', function () {
				if (index > 0) {
					index--;
					render();
				}
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				if (index < total - 1) {
					index++;
					render();
				}
			});
		}

		render();
	}

	function initAll() {
		var roots = document.querySelectorAll('[data-nd-shr]');
		for (var i = 0; i < roots.length; i++) {
			initShowrooms(roots[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}
})();
