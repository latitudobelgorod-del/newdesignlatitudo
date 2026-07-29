/**
 * Страница «Отзывы» нового дизайна.
 *
 * 1. Попап полного отзыва. Разметку не собираем на клиенте: у каждой карточки
 *    лежит <template class="nd-reviews__full"> с готовым содержимым, скрипт
 *    просто клонирует его в общее окно #nd-review-modal.
 * 2. Кнопка «Показать ещё» из шаблона навигации pagination_newdesign —
 *    подгружает следующую страницу и дописывает карточки в конец списка.
 *    Кнопка остаётся обычной ссылкой: без JS она просто уводит на страницу 2.
 */
(function () {
	'use strict';

	var MODAL_ID = 'nd-review-modal';
	var lastFocused = null;

	function modal() {
		return document.getElementById(MODAL_ID);
	}

	function openModal(card) {
		var box = modal();
		var tpl = card.querySelector('.nd-reviews__full');
		if (!box || !tpl) {
			return;
		}

		var head = box.querySelector('.nd-modal__head-content');
		var body = box.querySelector('.nd-modal__body');
		head.innerHTML = '';
		body.innerHTML = '';

		// заголовок и мета едут в шапку окна, фото и текст — в тело
		var parts = tpl.content.cloneNode(true);
		Array.prototype.slice.call(parts.children).forEach(function (node) {
			var toHead = node.classList.contains('nd-modal__title') || node.classList.contains('nd-modal__meta');
			(toHead ? head : body).appendChild(node);
		});

		lastFocused = document.activeElement;
		box.hidden = false;
		document.body.classList.add('nd-modal-open');
		body.scrollTop = 0;

		var close = box.querySelector('.nd-modal__close');
		if (close) {
			close.focus();
		}
	}

	function closeModal() {
		var box = modal();
		if (!box || box.hidden) {
			return;
		}
		box.hidden = true;
		document.body.classList.remove('nd-modal-open');
		if (lastFocused && typeof lastFocused.focus === 'function') {
			lastFocused.focus();
		}
		lastFocused = null;
	}

	document.addEventListener('click', function (e) {
		var opener = e.target.closest('[data-nd-review-open]');
		if (opener) {
			var card = opener.closest('.nd-reviews__card');
			if (card) {
				e.preventDefault();
				openModal(card);
			}
			return;
		}
		if (e.target.closest('[data-nd-review-close]')) {
			e.preventDefault();
			closeModal();
		}
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' || e.key === 'Esc') {
			closeModal();
		}
	});

	/* ---------------------- «Показать ещё» ---------------------- */

	var loading = false;

	document.addEventListener('click', function (e) {
		var more = e.target.closest('[data-nd-pager-more]');
		if (!more || loading) {
			return;
		}

		var list = document.querySelector('.nd-reviews__list');
		var nav = document.querySelector('.nd-reviews__nav');
		var href = more.getAttribute('href');
		if (!list || !nav || !href) {
			return; // без списка или адреса пусть отработает обычный переход
		}

		e.preventDefault();
		loading = true;
		more.classList.add('is-loading');

		fetch(href, { credentials: 'same-origin' })
			.then(function (r) {
				if (!r.ok) {
					throw new Error('HTTP ' + r.status);
				}
				return r.text();
			})
			.then(function (html) {
				var doc = new DOMParser().parseFromString(html, 'text/html');
				var nextList = doc.querySelector('.nd-reviews__list');
				var nextNav = doc.querySelector('.nd-reviews__nav');
				if (!nextList) {
					throw new Error('в ответе нет списка отзывов');
				}
				Array.prototype.slice.call(nextList.children).forEach(function (card) {
					list.appendChild(document.importNode(card, true));
				});
				// навигацию заменяем целиком: у неё сдвинулась текущая страница
				nav.innerHTML = nextNav ? nextNav.innerHTML : '';
				if (window.history && window.history.replaceState) {
					window.history.replaceState(null, '', href);
				}
			})
			.catch(function () {
				// не смогли подгрузить — уводим на страницу обычным переходом
				window.location.href = href;
			})
			.then(function () {
				loading = false;
				more.classList.remove('is-loading');
			});
	});
})();
