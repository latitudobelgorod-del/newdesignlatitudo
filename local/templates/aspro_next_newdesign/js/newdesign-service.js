/**
 * Детальная страница услуги — новый дизайн.
 * Макет Figma «Чистовик», фрейм «Услуга» 20669:41851.
 *
 * В макете кнопки «Заказать консультацию» и «Посмотреть проекты» стоят внутри
 * карточки с фотографией, а в разметке страницы они лежат отдельной секцией
 * сразу под ней (детальный текст элемента, править его нельзя — это контент).
 * Стилями секцию внутрь карточки не убрать: это соседние узлы. Поэтому
 * переносим их здесь, а оформление берёт на себя css/newdesign-service.css.
 *
 * Порядок в макете обратный тому, что в разметке: сначала «Заказать
 * консультацию», потом «Посмотреть проекты».
 */
(function () {
	'use strict';

	if (window.__ndService) return;
	window.__ndService = true;

	function build() {
		var hero = document.querySelector('.services_newdesign #page_str_terras');
		if (!hero || hero.__ndDone) return;

		var card = hero.querySelector('.main_bl > h2');
		if (!card) return;

		/* Секция с кнопками — первая после шапки, у неё нет своего класса,
		   кроме общего .terrace-second-screen; отличаем по содержимому. */
		var section = null;
		var node = hero.nextElementSibling;
		while (node && !section) {
			if (node.querySelector && node.querySelector('.btn') && !node.querySelector('h2')) section = node;
			node = node.nextElementSibling;
		}
		if (!section) return;

		var cells = [].slice.call(section.querySelectorAll('[class*="col-md-6"]'));
		if (!cells.length) return;

		hero.__ndDone = true;

		var cta = document.createElement('div');
		cta.className = 'nd-srv-hero__cta';

		/* Форму («Заказать консультацию») ставим первой — так в макете. */
		cells.sort(function (a, b) {
			var af = a.querySelector('[data-event="jqm"]') ? 0 : 1;
			var bf = b.querySelector('[data-event="jqm"]') ? 0 : 1;
			return af - bf;
		});
		cells.forEach(function (cell) { cta.appendChild(cell); });

		card.classList.add('nd-srv-hero__card');
		card.appendChild(cta);

		/* Пустая секция после переноса схлопывается сама, но у неё остаются
		   поля темы — убираем, чтобы под шапкой не было дыры. */
		if (!section.textContent.trim()) section.style.display = 'none';
	}

	if (document.readyState !== 'loading') build();
	else document.addEventListener('DOMContentLoaded', build);

	/* Содержимое приезжает вместе со страницей, но блоки редактора иногда
	   дорисовываются скриптами — переспрашиваем. */
	setTimeout(build, 500);
	setTimeout(build, 1500);
})();
