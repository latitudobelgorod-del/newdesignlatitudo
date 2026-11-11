/* Табы производителей на «Монтажных схемах и инструкциях» (documents-tabs_newdesign,
   11.11.2026) — как на «Сертификатах и лицензиях». Без скрипта видны все документы. */
(function () {
	function init(root) {
		if (root.getAttribute('data-nd-ready')) return;
		root.setAttribute('data-nd-ready', '1');
		var chips = [].slice.call(root.querySelectorAll('[data-nd-doc-brand]'));
		var cells = [].slice.call(root.querySelectorAll('[data-nd-doc-brands]'));
		if (!chips.length) return;

		function pick(chip) {
			var brand = chip.getAttribute('data-nd-doc-brand');
			chips.forEach(function (c) {
				var on = c === chip;
				c.classList.toggle('is-active', on);
				c.setAttribute('aria-pressed', on ? 'true' : 'false');
			});
			cells.forEach(function (cell) {
				var list = (cell.getAttribute('data-nd-doc-brands') || '').split(' ');
				cell.hidden = !(!brand || list.indexOf(brand) !== -1);
			});
		}

		chips.forEach(function (chip) {
			chip.addEventListener('click', function () { pick(chip); });
		});
	}

	function run() {
		[].forEach.call(document.querySelectorAll('[data-nd-doctabs]'), init);
	}
	if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
	else run();
})();
