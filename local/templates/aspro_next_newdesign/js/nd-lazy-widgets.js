/**
 * Сторонние виджеты, которые подключаются только по первому действию посетителя
 * (касание, прокрутка, движение мыши, клавиша, клик):
 *  - виджет обратного звонка envybox (он же тянет чат saas-support/whitesaas);
 *  - значок рейтинга Яндекс Бизнеса в блоке «О компании» на главной
 *    (<div data-nd-rating-id="…"> из include/mainpage/about_company.php).
 *
 * Отдельным файлом, а не кодом в странице (07.10.2026): проверка 152-ФЗ (vlip.site)
 * находила адреса виджетов в HTML и ставила «загрузку трекеров до взаимодействия
 * пользователя», хотя грузились они только после действия. У vrn.easydecking.ru
 * сторонних виджетов в HTML нет — там замечания нет. Метрика и Top.Mail.Ru здесь
 * не трогаются: они считают все посещения, как и на vrn.
 *
 * Отложенный envybox появился 22.09.2026 ради скорости: на телефоне он занимал
 * процессор ~0,4 с, его CSS задерживал отрисовку.
 */
(function () {
	var ENVYBOX_CSS = 'https://cdn.envybox.io/widget/cbk.css';
	var ENVYBOX_JS = 'https://cdn.envybox.io/widget/cbk.js?wcb_code=e4de92bacc448ee6b674c4cb61afd66e';
	var RATING_SRC = 'https://yandex.ru/sprav/widget/rating-badge/';

	var done = false, evs = ['touchstart', 'scroll', 'mousemove', 'keydown', 'click'];

	function loadEnvybox() {
		var l = document.createElement('link');
		l.rel = 'stylesheet';
		l.href = ENVYBOX_CSS;
		document.head.appendChild(l);
		var s = document.createElement('script');
		s.src = ENVYBOX_JS;
		s.charset = 'UTF-8';
		s.async = true;
		document.body.appendChild(s);
	}

	function showRating() {
		document.querySelectorAll('[data-nd-rating-id]').forEach(function (box) {
			var id = box.getAttribute('data-nd-rating-id');
			if (!/^\d+$/.test(id) || box.querySelector('iframe')) return;
			var f = document.createElement('iframe');
			f.src = RATING_SRC + id + '?type=rating';
			f.width = '150';
			f.height = '50';
			f.setAttribute('frameborder', '0');
			box.appendChild(f);
		});
	}

	function run() {
		if (done) return;
		done = true;
		evs.forEach(function (e) { window.removeEventListener(e, run, true); });
		loadEnvybox();
		showRating();
	}

	evs.forEach(function (e) { window.addEventListener(e, run, {capture: true, passive: true}); });
})();
