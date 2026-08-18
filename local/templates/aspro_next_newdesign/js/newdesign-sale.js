/**
 * Детальная акции: сворачивание длинного описания.
 *
 * Разметка — components/bitrix/news.detail/news_newdesign/template.php:
 * полоса [data-nd-sale-lead] (картинка во float + текст, который её обтекает)
 * и кнопка [data-nd-sale-more], которая в разметке лежит скрытой.
 *
 * Решать, длинное описание или нет, приходится на клиенте: высота зависит от
 * картинки, ширины экрана и блоков редактора. Поэтому шаблон отдаёт полосу
 * целиком, а скрипт при загрузке сворачивает её и показывает кнопку — но
 * только если под срез действительно что-то уходит. Если JS не отработает,
 * посетитель увидит полный текст, а не обрезанный.
 *
 * Нижняя граница свёрнутого вида — картинка целиком плюс несколько строк
 * текста: обрезать саму картинку некрасиво, а она бывает выше любого
 * фиксированного лимита.
 */
(function () {
	var BASE_DESKTOP = 520;
	var BASE_MOBILE = 420;
	/* Ниже этого запаса сворачивать незачем: кнопка съест больше, чем скроет. */
	var MIN_HIDDEN = 120;

	function limitFor(lead) {
		var base = window.innerWidth <= 767 ? BASE_MOBILE : BASE_DESKTOP;
		var pic = lead.querySelector('.nd-sale-lead__pic');

		if (pic) {
			var rect = pic.getBoundingClientRect();
			/* Запас под картинкой: срез должен приходиться на текст, а не сразу
			   под фото — иначе на телефоне видно две строки и растушёвку. */
			var need = rect.height + (pic.offsetTop - lead.offsetTop) + 220;
			if (need > base) base = Math.round(need);
		}

		return base;
	}

	function setup(lead, btn) {
		var limit = limitFor(lead);

		lead.style.maxHeight = '';
		lead.classList.remove('nd-sale-lead--collapsed');

		if (lead.scrollHeight <= limit + MIN_HIDDEN) {
			btn.hidden = true;
			return;
		}

		btn.hidden = false;
		lead.dataset.ndSaleLimit = limit;

		if (!btn.classList.contains('is-open')) {
			lead.style.maxHeight = limit + 'px';
			lead.classList.add('nd-sale-lead--collapsed');
		}
	}

	function init() {
		var lead = document.querySelector('[data-nd-sale-lead]');
		var btn = document.querySelector('[data-nd-sale-more]');
		if (!lead || !btn) return;

		var label = btn.querySelector('.nd-sale-more__text');

		setup(lead, btn);

		btn.addEventListener('click', function () {
			var open = btn.classList.toggle('is-open');

			if (open) {
				lead.style.maxHeight = '';
				lead.classList.remove('nd-sale-lead--collapsed');
				if (label) label.textContent = 'Свернуть';
			} else {
				lead.style.maxHeight = (lead.dataset.ndSaleLimit || 520) + 'px';
				lead.classList.add('nd-sale-lead--collapsed');
				if (label) label.textContent = 'Показать все';
				/* Свернули из середины текста — возвращаем к началу полосы. */
				var top = lead.getBoundingClientRect().top;
				if (top < 0) lead.scrollIntoView({ block: 'start' });
			}
		});

		/* Поворот экрана и подгрузка картинки меняют высоту — пересчитываем. */
		var timer;
		window.addEventListener('resize', function () {
			clearTimeout(timer);
			timer = setTimeout(function () {
				if (!btn.classList.contains('is-open')) setup(lead, btn);
			}, 200);
		});

		var pic = lead.querySelector('.nd-sale-lead__pic img');
		if (pic && !pic.complete) {
			pic.addEventListener('load', function () {
				if (!btn.classList.contains('is-open')) setup(lead, btn);
			});
		}
	}

	if (document.readyState !== 'loading') init();
	else document.addEventListener('DOMContentLoaded', init);
})();
