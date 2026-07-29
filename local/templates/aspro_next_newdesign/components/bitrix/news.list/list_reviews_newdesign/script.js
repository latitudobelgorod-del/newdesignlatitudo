/**
 * Страница «Отзывы» нового дизайна.
 *
 * 1. Попап полного отзыва. Разметку не собираем на клиенте: у каждой карточки
 *    лежит <template class="nd-reviews__full"> с готовым содержимым, скрипт
 *    просто клонирует его в общее окно #nd-review-modal.
 * 2. Лента фото в карточке: затемнение со стрелкой, пока есть что листать.
 *
 * Фильтр и кнопку «Показать ещё» обслуживает общий js/newdesign-ui.js —
 * они одинаковые на отзывах и портфолио. После дозагрузки карточек он шлёт
 * событие nd:appended, по нему пересчитываем ленты фото.
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

	/* ------------------- лента фото в карточке ------------------- */

	// Затемнение со стрелкой показываем, только пока ленту есть куда листать.
	function syncPhotos(box) {
		var track = box.querySelector('.nd-reviews__track');
		if (!track) {
			return;
		}
		var rest = track.scrollWidth - track.clientWidth - track.scrollLeft;
		box.classList.toggle('is-scrollable', rest > 1);
	}

	function syncAllPhotos() {
		Array.prototype.forEach.call(document.querySelectorAll('[data-nd-photos]'), syncPhotos);
	}

	document.addEventListener('click', function (e) {
		var next = e.target.closest('[data-nd-photos-next]');
		if (!next) {
			return;
		}
		e.preventDefault();
		var box = next.closest('[data-nd-photos]');
		var track = box.querySelector('.nd-reviews__track');
		// листаем ровно на видимую часть ленты
		track.scrollLeft += track.clientWidth;
	}, true);

	document.addEventListener('scroll', function (e) {
		if (e.target.classList && e.target.classList.contains('nd-reviews__track')) {
			syncPhotos(e.target.closest('[data-nd-photos]'));
		}
	}, true);

	window.addEventListener('resize', syncAllPhotos);
	window.addEventListener('load', syncAllPhotos);
	document.addEventListener('DOMContentLoaded', syncAllPhotos);
	document.addEventListener('nd:appended', syncAllPhotos);

})();
