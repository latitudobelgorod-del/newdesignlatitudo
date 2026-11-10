/* Табы производителей на странице «Сертификаты и лицензии» (licenses-tabs_newdesign).
   Прячем карточки других марок и уводим их из группы галереи, чтобы fancybox
   листал только выбранную марку. Без скрипта видны все карточки. */
(function () {
	function init(root) {
		if (root.getAttribute('data-nd-ready')) return;
		root.setAttribute('data-nd-ready', '1');
		var chips = [].slice.call(root.querySelectorAll('[data-nd-cert-brand]'));
		var cards = [].slice.call(root.querySelectorAll('[data-nd-cert-brands]'));
		if (!chips.length) return;

		function pick(chip) {
			var brand = chip.getAttribute('data-nd-cert-brand');
			chips.forEach(function (c) {
				var on = c === chip;
				c.classList.toggle('is-active', on);
				c.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			cards.forEach(function (card) {
				var list = (card.getAttribute('data-nd-cert-brands') || '').split(' ');
				var show = !brand || list.indexOf(brand) !== -1;
				card.hidden = !show;
				card.setAttribute('data-fancybox', show ? 'nd-certs' : 'nd-certs-off');
				card.setAttribute('rel', show ? 'nd-certs' : 'nd-certs-off');
			});
		}

		chips.forEach(function (chip) {
			chip.addEventListener('click', function () { pick(chip); });
		});
	}

	function run() {
		[].forEach.call(document.querySelectorAll('[data-nd-certtabs]'), init);
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
	else run();
})();
