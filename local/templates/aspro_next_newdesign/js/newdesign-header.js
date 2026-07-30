/* Поведение шапки нового дизайна: высота под fixed-шапку, выпадающий каталог,
   попапы «Шоурум» и «Склад». Логика перенесена с easydecking.ru (header_9.php),
   зависимостей нет — чистый JS, работает до загрузки jQuery. */
(function () {
	'use strict';

	/* ── Реальная высота шапки в --nd-header-h ──
	   Ниже 992px десктопная шапка скрыта и работает мобильная (#mobileheader),
	   поэтому берём ту, что сейчас видна. Нулевую высоту не пишем — остаётся
	   значение из макета. Элементы ищем на каждом вызове: мобильная шапка идёт
	   в разметке ниже и на момент разбора этого скрипта ещё не существует. */
	function syncHeaderHeight() {
		var els = [document.getElementById('nd-header'),
		           document.getElementById('mobileheader')];
		var h = 0, i, height;
		for (i = 0; i < els.length; i++) {
			if (!els[i]) continue;
			height = els[i].getBoundingClientRect().height;
			if (height > h) h = height;
		}
		if (h > 0) document.documentElement.style.setProperty('--nd-header-h', h + 'px');
	}

	syncHeaderHeight();
	window.addEventListener('load', syncHeaderHeight);
	window.addEventListener('resize', syncHeaderHeight);

	/* ── Выпадающий каталог ── */
	var catalogBtn     = document.getElementById('nd-catalog-btn');
	var catalogDrop    = document.getElementById('nd-catalog-drop');
	var catalogOverlay = document.getElementById('nd-catalog-overlay');
	var closeTimer     = null;

	function openCatalog() {
		if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
		catalogBtn.classList.add('is-open');
		catalogDrop.classList.add('is-open');
		catalogOverlay.classList.add('is-open');
	}

	function closeCatalogSoon() {
		closeTimer = setTimeout(closeCatalogNow, 150);
	}

	function closeCatalogNow() {
		if (closeTimer) { clearTimeout(closeTimer); closeTimer = null; }
		if (catalogBtn) catalogBtn.classList.remove('is-open');
		if (catalogDrop) catalogDrop.classList.remove('is-open');
		if (catalogOverlay) catalogOverlay.classList.remove('is-open');
	}

	if (catalogBtn && catalogDrop && catalogOverlay) {
		/* На тач-устройствах hover эмулируется: меню успевает открыться и тут же
		   закрывается, а тап уходит по ссылке на /catalog/. Поэтому там первый
		   тап только раскрывает меню, а повторный — переходит в каталог. */
		var noHover = window.matchMedia && window.matchMedia('(hover: none)').matches;

		if (noHover) {
			catalogBtn.addEventListener('click', function (e) {
				if (!catalogDrop.classList.contains('is-open')) {
					e.preventDefault();
					openCatalog();
				}
			});
			catalogOverlay.addEventListener('click', closeCatalogNow);
			document.addEventListener('click', function (e) {
				if (!catalogDrop.classList.contains('is-open')) return;
				if (catalogBtn.contains(e.target) || catalogDrop.contains(e.target)) return;
				closeCatalogNow();
			});
		} else {
			catalogBtn.addEventListener('mouseenter', openCatalog);
			catalogBtn.addEventListener('mouseleave', closeCatalogSoon);
			catalogDrop.addEventListener('mouseenter', openCatalog);
			catalogDrop.addEventListener('mouseleave', closeCatalogSoon);
			catalogOverlay.addEventListener('mouseenter', closeCatalogSoon);
		}
	}

	/* ── Каталог: переключение правой панели по разделу слева ──
	   Наведение (и фокус с клавиатуры) меняет активный чип и показывает панель
	   с его подразделами. Сам чип остаётся ссылкой на раздел, поэтому клик
	   работает как обычный переход — переключение только визуальное. */
	if (catalogDrop) {
		var chips = Array.prototype.slice.call(catalogDrop.querySelectorAll('.nd-cat__chip'));
		var panels = Array.prototype.slice.call(catalogDrop.querySelectorAll('.nd-cat__panel'));

		function activateSection(target) {
			chips.forEach(function (c) {
				c.classList.toggle('is-active', c.getAttribute('data-nd-cat-target') === target);
			});
			panels.forEach(function (p) {
				p.classList.toggle('is-active', p.getAttribute('data-nd-cat-panel') === target);
			});
		}

		chips.forEach(function (chip) {
			var target = chip.getAttribute('data-nd-cat-target');
			chip.addEventListener('mouseenter', function () { activateSection(target); });
			chip.addEventListener('focus', function () { activateSection(target); });
		});
	}

	/* ── Попапы «Шоурум» и «Склад» ── */
	var pops = Array.prototype.slice.call(document.querySelectorAll('.nd-pop'));

	function closePop(pop) {
		pop.classList.remove('is-open');
		if (pop.ndTimer) { clearTimeout(pop.ndTimer); pop.ndTimer = null; }
		/* hidden снимаем/ставим вокруг transition, иначе display:none
		   обрывает анимацию появления. */
		pop.ndHideTimer = setTimeout(function () {
			var body = pop.querySelector('.nd-pop__body');
			if (body && !pop.classList.contains('is-open')) body.hidden = true;
		}, 200);
	}

	function openPop(pop) {
		pops.forEach(function (other) { if (other !== pop) closePop(other); });
		if (pop.ndTimer) { clearTimeout(pop.ndTimer); pop.ndTimer = null; }
		if (pop.ndHideTimer) { clearTimeout(pop.ndHideTimer); pop.ndHideTimer = null; }
		var body = pop.querySelector('.nd-pop__body');
		if (body) body.hidden = false;
		/* Кадр между снятием hidden и is-open — чтобы браузер успел применить
		   стартовое состояние перехода. */
		requestAnimationFrame(function () { pop.classList.add('is-open'); });
	}

	pops.forEach(function (pop) {
		pop.addEventListener('mouseenter', function () { openPop(pop); });
		pop.addEventListener('mouseleave', function () {
			pop.ndTimer = setTimeout(function () { closePop(pop); }, 120);
		});

		var label = pop.querySelector('.nd-pop__label');
		if (label) {
			label.addEventListener('click', function () {
				if (pop.classList.contains('is-open')) closePop(pop);
				else openPop(pop);
			});
			label.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					openPop(pop);
				}
			});
		}

		var close = pop.querySelector('.nd-pop__close');
		if (close) {
			close.addEventListener('click', function (e) {
				e.stopPropagation();
				closePop(pop);
			});
		}
	});

	/* Клик вне попапа — закрыть (fallback для тача) */
	document.addEventListener('click', function (e) {
		pops.forEach(function (pop) {
			if (!pop.contains(e.target)) closePop(pop);
		});
	});

	/* ── Заголовок всплывающей формы = подпись кнопки ──
	   Тема подставляет его сама в onLoadjqm (js/main.js): берёт текст триггера
	   через $(hash.t).text() и пишет в h2.formnameru и в скрытое поле NAMEFORM
	   (последнее уходит в письмо и в CRM, поэтому важно не меньше заголовка).
	   Но привязку триггера jqModal она строит по CSS-селектору из всех атрибутов
	   элемента, и для нашей кнопки hash.t приезжает как document — в заголовке
	   остаётся имя веб-формы «Общая форма», а в NAMEFORM попадает текст всей
	   страницы. Поэтому подставляем сами, как только окно с формой отрисовалось. */
	var formTitleObserver = null;
	var formTitleTimer = null;

	function setFormTitle(label) {
		var head = document.querySelector('div.form_head h2.formnameru');
		if (!head) return;

		if (head.textContent !== label) head.textContent = label;

		var wrap = head.closest('.form_head');
		var form = wrap && wrap.parentElement ? wrap.parentElement.querySelector('form') : null;
		if (!form) return;

		var nameInput = form.querySelector('input[data-sid="NAMEFORM"]');
		if (nameInput && nameInput.value !== label) nameInput.value = label;

		/* Подпись уходит ещё и в action — оттуда её читает обработчик отправки.
		   Дважды не добавляем: окно открывается повторно, а форма перерисовывается. */
		var action = form.getAttribute('action') || '';
		if (action.indexOf('formhead=') === -1) {
			form.setAttribute('action', action + '&formhead=' + encodeURIComponent(label));
		}
	}

	/* Форма прилетает аяксом и по дороге перерисовывается несколько раз (в DOM
	   вдобавок остаётся окно от прошлого открытия), поэтому одной подстановки
	   мало — держим её наблюдателем, пока идёт загрузка. */
	function applyFormTitle(label) {
		if (formTitleObserver) formTitleObserver.disconnect();
		if (formTitleTimer) clearTimeout(formTitleTimer);

		setFormTitle(label);

		formTitleObserver = new MutationObserver(function () { setFormTitle(label); });
		formTitleObserver.observe(document.body, { childList: true, subtree: true });

		formTitleTimer = setTimeout(function () {
			if (formTitleObserver) formTitleObserver.disconnect();
			formTitleObserver = null;
		}, 8000);
	}

	document.addEventListener('click', function (e) {
		var trigger = e.target.closest('#nd-header [data-event="jqm"]');
		if (!trigger) return;

		/* data-nd-form-title задаёт заголовок явно — нужен там, где подпись кнопки
		   короче названия формы (мессенджеры: подпись «MAX», заголовок «Написать в MAX»).
		   Без атрибута берём текст кнопки («Оставить заявку»). */
		var label = trigger.getAttribute('data-nd-form-title')
			|| (trigger.textContent || '').replace(/\s+/g, ' ').trim();
		if (label) applyFormTitle(label);
	});

	/* Escape — закрыть всё */
	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape') return;
		closeCatalogNow();
		pops.forEach(closePop);
	});

})();
