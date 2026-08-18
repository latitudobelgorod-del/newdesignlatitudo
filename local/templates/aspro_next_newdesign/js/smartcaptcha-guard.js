/**
 * Не даём отправить форму, пока не решена Яндекс SmartCaptcha.
 *
 * Модуль ceteralabs.smartcaptcha подменяет штатное поле <input name="captcha_word"
 * required> на <div class="smart-captcha">, а виджет Яндекса кладёт внутрь скрытый
 * <input name="smart-token">. Побочный эффект: проверять на клиенте стало нечего —
 * jquery.validate обязательного поля больше не видит, и кнопка «Отправить»
 * отправляет форму с пустым токеном. Сервер такую отправку отбивает
 * («Вы не прошли проверку капчей»), форма перерисовывается заново, и всё
 * заполненное теряется — со стороны это выглядит как «капча не даёт отправить».
 *
 * Поэтому: если в форме есть виджет, а токена нет — отправку отменяем и пишем
 * подсказку прямо под капчей.
 *
 * Обработчик висит на документе в фазе перехвата, чтобы сработать раньше
 * submitHandler'а jquery.validate и ajax-отправки темы.
 *
 * Файл одинаковый в обоих шаблонах (aspro_next и aspro_next_newdesign): формы
 * и капча общие, а подключать чужой шаблон по абсолютному пути некрасиво.
 */
(function () {
	if (window.__ndCaptchaGuard) return;
	window.__ndCaptchaGuard = true;

	var NOTE = 'nd-captcha-note';
	var TEXT = 'Подтвердите, что вы не робот';

	function solved(form) {
		var field = form.querySelector('[name="smart-token"]');
		return !!(field && field.value);
	}

	function showNote(box) {
		var note = box.parentNode.querySelector('.' + NOTE);

		if (!note) {
			note = document.createElement('div');
			note.className = NOTE;
			note.style.cssText = 'margin-top:6px;font-weight:400;font-size:12px;line-height:16px;color:#c60000;';
			note.textContent = TEXT;
			box.parentNode.insertBefore(note, box.nextSibling);
		}

		return note;
	}

	/* Решённую капчу подсказка ждать не должна: виджет заполняет токен без
	   события, поэтому проверяем по таймеру, пока подсказка на экране. */
	function watch() {
		var notes = document.querySelectorAll('.' + NOTE);
		if (!notes.length) return;

		[].forEach.call(notes, function (note) {
			var form = note.closest ? note.closest('form') : null;
			if (form && solved(form)) note.parentNode.removeChild(note);
		});
	}

	setInterval(watch, 1000);

	document.addEventListener('submit', function (e) {
		var form = e.target;
		if (!form || !form.querySelector) return;

		var box = form.querySelector('.smart-captcha');
		if (!box || solved(form)) return;

		e.preventDefault();
		e.stopPropagation();
		if (e.stopImmediatePropagation) e.stopImmediatePropagation();

		var note = showNote(box);
		if (note.scrollIntoView) note.scrollIntoView({ block: 'center' });
	}, true);
})();
