/* ============================================================================
   Детальная карточка товара в новом дизайне: своя галерея и мелкие переносы
   узлов, которые нельзя сделать стилями.

   Разметку и всю торговую логику (смена цвета/длины, цена, единицы, счётчик,
   корзина) по-прежнему ведёт шаблон темы main6 — его DOM мы не ломаем. Штатная
   галерея уведена за левый край стилями и остаётся источником данных: при смене
   торгового предложения тема зовёт JCCatalogElement.SetSliderPict, мы за неё
   цепляемся и пересобираем свою ленту.
   ========================================================================= */
(function () {
	'use strict';

	var root, gallery, photo, img, thumbsWrap, track, nextBtn;
	var slides = [];
	var current = 0;

	function $(sel, ctx) { return (ctx || document).querySelector(sel); }

	/* --- превью --- */

	function renderThumbs() {
		if (!track) return;
		track.innerHTML = slides.map(function (s, i) {
			return '<button type="button" class="nd-pd__thumb' + (i === current ? ' is-active' : '') +
				'" data-index="' + i + '"><img src="' + s.thumb + '" alt="' + (s.alt || '') + '" loading="lazy"></button>';
		}).join('');
		updateArrow();
	}

	function updateArrow() {
		if (!thumbsWrap || !track) return;
		/* стрелка нужна, только когда лента длиннее видимой части */
		thumbsWrap.classList.toggle('has-more', track.scrollHeight - track.clientHeight > 2);
	}

	function show(index) {
		if (!slides.length) return;
		current = Math.max(0, Math.min(index, slides.length - 1));
		var s = slides[current];
		if (img) {
			img.src = s.small;
			img.alt = s.alt || '';
			img.title = s.title || '';
		}
		if (track) {
			Array.prototype.forEach.call(track.children, function (el, i) {
				el.classList.toggle('is-active', i === current);
			});
			var active = track.children[current];
			if (active && (active.offsetTop < track.scrollTop || active.offsetTop + active.offsetHeight > track.scrollTop + track.clientHeight)) {
				track.scrollTop = active.offsetTop;
			}
		}
	}

	/* Пересборка после смены торгового предложения: тема отдаёт объект слайдера
	   со своими ключами (SMALL/BIG/THUMB/SRC), приводим к нашему виду. */
	function rebuild(offerSlider) {
		var next = [];
		for (var k in offerSlider) {
			var s = offerSlider[k];
			if (!s || typeof s !== 'object') continue;
			var small = (s.SMALL && s.SMALL.src) || s.SRC || '';
			if (!small) continue;
			next.push({
				small: small,
				big: (s.BIG && s.BIG.src) || s.SRC || '',
				thumb: (s.THUMB && s.THUMB.src) || small,
				alt: s.ALT || '',
				title: s.TITLE || ''
			});
		}
		if (!next.length) return;
		slides = next;
		current = 0;
		renderThumbs();
		show(0);
		syncLinks();
		syncVideo();
	}

	/* --- ссылки для просмотра во весь экран и кнопка видео --- */

	function syncLinks() {
		var box = $('.nd-pd__links', photo);
		if (!box) return;
		box.innerHTML = slides.map(function (s) {
			return '<a href="' + s.big + '" data-fancybox="nd-pd" title="' + (s.title || '') + '"></a>';
		}).join('');
	}

	function syncVideo() {
		var btn = $('.nd-pd__video', photo);
		if (!btn) return;
		/* адрес ролика тема держит в скрытой галерее — у активного оффера */
		var src = $('.img_wrapper .rut-video.active a') || $('.img_wrapper .rut-video a');
		var href = src && src.getAttribute('href');
		if (href && href !== 'javascript:void(0)') {
			btn.setAttribute('href', href);
			btn.hidden = false;
		} else {
			btn.hidden = true;
		}
	}

	/* --- переносы узлов темы --- */

	function moveNodes() {
		/* плашки товара — поверх фото */
		var stickers = $('.item_main_info .img_wrapper > .stickers');
		if (stickers && photo && !photo.contains(stickers)) photo.appendChild(stickers);

		syncArticle();
	}

	/* Артикул — в строку заголовка справа (в макете второго заголовка нет).
	   Сам узел переносить нельзя: тема при смене предложения ищет его внутри
	   .info_item и вырезает, если не нашла. Поэтому оригинал прячем стилями, а в
	   заголовок кладём копию.
	   Зовём не только на старте: карточку торгового предложения тема дорисовывает
	   после загрузки, и на DOMContentLoaded артикула в разметке ещё нет. */
	function syncArticle() {
		var h1 = $('.catalog_detail.element_newdesign > h1');
		var article = $('.info_item > .product-article');
		if (!h1 || !article) return;
		var span = $('.nd-pd__article', h1);
		if (!span) {
			span = document.createElement('span');
			span.className = 'nd-pd__article';
			h1.appendChild(span);
		}
		span.textContent = article.textContent.trim();
	}

	/* Документы тема рисует в ряду с характеристиками, а по макету они в нижнем
	   ряду рядом с доставкой. Переносим сам список, а не копируем: ссылки на
	   файлы и микроразметку дублировать незачем. */
	function moveDocs() {
		var box = $('.nd-pd__docs');
		var body = $('.nd-pd__docs-body');
		var src = $('.nd-pd__docs-src');
		if (!box || !body || !src || body.children.length) return;

		/* Переносим всё содержимое колонки: и файлы товара, и блоки sprint.editor
		   с сертификатами. Заголовок темы выбрасываем — свой уже стоит. */
		Array.prototype.slice.call(src.children).forEach(function (el) {
			if (el.tagName === 'H4') { el.remove(); return; }
			body.appendChild(el);
		});
		if (body.children.length) box.hidden = false;
	}

	/* Кнопка «Заказать расчет» лежит в безымянном div — помечаем обёртку классом,
	   чтобы стили могли поставить её на место из макета (ссылка под счётчиком).
	   Текст кнопки не трогаем: в макете он свой, но на сайте кнопка настоящая. */
	function tagCalcButton() {
		var btn = $('.info_item .one_click');
		if (btn && btn.parentElement) btn.parentElement.classList.add('nd-pd__calc');
	}

	function bind() {
		if (track) {
			track.addEventListener('click', function (e) {
				var btn = e.target.closest('.nd-pd__thumb');
				if (btn) show(parseInt(btn.getAttribute('data-index'), 10) || 0);
			});
		}
		if (nextBtn) {
			nextBtn.addEventListener('click', function () {
				var step = 119; /* превью 115 + зазор 4 */
				var atEnd = track.scrollTop + track.clientHeight >= track.scrollHeight - 2;
				track.scrollTop = atEnd ? 0 : track.scrollTop + step * 3;
				nextBtn.classList.toggle('is-up', !atEnd && track.scrollTop + track.clientHeight >= track.scrollHeight - 2);
			});
		}
		var zoom = $('.nd-pd__zoom', photo);
		if (zoom) {
			zoom.addEventListener('click', function () {
				var links = photo.querySelectorAll('.nd-pd__links a');
				if (links[current]) links[current].click();
				else if (links[0]) links[0].click();
			});
		}
		window.addEventListener('resize', updateArrow);
	}

	function init() {
		root = $('.catalog_detail.element_newdesign');
		if (!root) return;
		gallery = $('.nd-pd__gallery', root);
		if (!gallery) return;
		photo = $('.nd-pd__photo', gallery);
		img = $('.nd-pd__img', gallery);
		thumbsWrap = $('.nd-pd__thumbs', gallery);
		track = $('.nd-pd__thumbs-track', gallery);
		nextBtn = $('.nd-pd__thumbs-next', gallery);

		try { slides = JSON.parse(gallery.getAttribute('data-nd-slides') || '[]'); } catch (e) { slides = []; }

		moveNodes();
		moveDocs();
		tagCalcButton();
		bind();

		/* Карточку предложения тема перерисовывает после загрузки — следим за
		   правой колонкой и подхватываем артикул и кнопку расчёта, когда появятся. */
		var info = $('.item_main_info .info_item');
		if (info) {
			new MutationObserver(function () {
				syncArticle();
				tagCalcButton();
			}).observe(info, { childList: true, subtree: true, characterData: true });
		}

		/* Заголовок тема тоже переписывает целиком (вместе с нашим артикулом) —
		   возвращаем копию, как только она пропала. */
		var h1 = $('.catalog_detail.element_newdesign > h1');
		if (h1) {
			new MutationObserver(function () {
				if (!$('.nd-pd__article', h1)) syncArticle();
			}).observe(h1, { childList: true });
		}
		window.addEventListener('load', function () { syncArticle(); tagCalcButton(); });
		updateArrow();
		syncVideo();

		/* Смена цвета/длины: оборачиваем метод темы, чтобы после её работы
		   пересобрать свою ленту фотографиями выбранного предложения. */
		if (window.jQuery) {
			window.jQuery(function () {
				var proto = window.JCCatalogElement && window.JCCatalogElement.prototype;
				if (!proto || proto.__ndPatched) return;
				proto.__ndPatched = true;
				var orig = proto.SetSliderPict;
				proto.SetSliderPict = function (obj, slider, config) {
					try { if (orig) orig.apply(this, arguments); } catch (e) {}
					try { rebuild(slider); } catch (e) {}
				};
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
