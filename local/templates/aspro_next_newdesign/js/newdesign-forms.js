/**
 * Формы нового дизайна: подписи полей внутри поля.
 *
 * В макете (Figma «Чистовик», фреймы 20554:96213 и ниже) подпись лежит
 * внутри поля вместо плейсхолдера, а как только поле заполнено — уезжает
 * наверх мелкой строкой. Разметку печатает штатный шаблон формы
 * (form.result.new/popup): подпись там обычный <label> перед полем, поэтому
 * всё оформление делает css/newdesign-forms.css, а скрипту остаётся ставить
 * классы состояния.
 *
 * Формы приходят ajax'ом в модальное окно, поэтому:
 *  - обработчики вешаем делегированием на документ (на самих полях они бы
 *    потерялись при следующем открытии окна);
 *  - за появлением окна следим наблюдателем — только так узнаём про поля,
 *    которые браузер заполнил автоподстановкой, и про textarea.
 */
(function () {
	if (window.__ndFormsInit) return;
	window.__ndFormsInit = true;

	var FIELD = 'input.inputtext, input.phone, input[type="text"], input[type="tel"], input[type="email"], textarea';

	function row(field) {
		return field && field.closest ? field.closest('.form-control') : null;
	}

	function mark(field) {
		var box = row(field);
		if (!box) return;

		box.classList.toggle('is-filled', !!String(field.value || '').trim());
		if (field.tagName === 'TEXTAREA') box.classList.add('nd-form-control--area');
	}

	function markAll(scope) {
		var fields = (scope || document).querySelectorAll('.form-container ' + FIELD);
		[].forEach.call(fields, mark);
	}

	document.addEventListener('input', function (e) {
		if (e.target && e.target.closest && e.target.closest('.form-container')) mark(e.target);
	}, true);

	document.addEventListener('change', function (e) {
		if (e.target && e.target.closest && e.target.closest('.form-container')) mark(e.target);
	}, true);

	/* Фокус поднимает подпись даже у пустого поля — как в макете «заполненного»
	   состояния: иначе курсор вставал бы прямо в текст подписи. */
	document.addEventListener('focusin', function (e) {
		var box = row(e.target);
		if (box) box.classList.add('is-focused');
	});

	document.addEventListener('focusout', function (e) {
		var box = row(e.target);
		if (box) {
			box.classList.remove('is-focused');
			mark(e.target);
		}
	});

	/* Окно с формой вставляется в конец body — ждём его появления. */
	function watch() {
		markAll();

		new MutationObserver(function (records) {
			for (var i = 0; i < records.length; i++) {
				var added = records[i].addedNodes;
				for (var j = 0; j < added.length; j++) {
					var node = added[j];
					if (node.nodeType !== 1) continue;
					if (node.querySelector && (node.querySelector('.form-container') || node.classList.contains('form-container'))) {
						markAll(node);
					}
				}
			}
		}).observe(document.body, { childList: true, subtree: true });
	}

	if (document.readyState !== 'loading') watch();
	else document.addEventListener('DOMContentLoaded', watch);
})();
