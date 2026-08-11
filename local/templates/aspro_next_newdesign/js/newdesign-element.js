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
			/* Заглушку «НЕТ ФОТО» пропускаем — тот же отбор, что на сервере
			   в template.php при сборке $ndSlides. */
			if (!small || small.toLowerCase().indexOf('no_photo') !== -1) continue;
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

	/* Характеристики тема рисует отдельным рядом над документами, из-за чего
	   справа зияла пустота. По макету они стоят слева в одной строке с
	   документами и доставкой — переносим их в левую колонку нижнего ряда. */
	function moveChars() {
		var box = $('.nd-pd__chars');
		if (!box || box.children.length) return;

		/* Разметка у темы двух видов: для террасной доски это колонка
		   .nd-pd__chars-src с заголовком и .char_block внутри, для остальных
		   разделов — сам .char_block с колонкой и заголовком внутри. Поэтому
		   ищем таблицу характеристик и переносим тот узел, который лежит прямо
		   в ряду .desc_tab. */
		var block = $('.row.desc_tab .char_block');
		if (!block) return;
		var node = block;
		while (node.parentElement && !node.parentElement.classList.contains('desc_tab')) {
			node = node.parentElement;
		}
		box.appendChild(node);
		box.hidden = false;
		markCharRows();
	}

	/* Характеристики приходят несколькими таблицами (профиль, основные,
	   применение), и nth-child считает строки заново в каждой — заливка через
	   одну сбивалась на стыке. Размечаем строки сквозной нумерацией. */
	function markCharRows() {
		var rows = document.querySelectorAll('.nd-pd__chars .char_block tr');
		Array.prototype.forEach.call(rows, function (tr, i) {
			tr.classList.toggle('nd-alt', i % 2 === 0);
		});
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
		/* Заголовок «Документы» показываем только при реальном списке: у части
		   товаров в колонке лежит одна ссылка «Информация о производителе». */
		if ($('.nd-docs, .files_block', body)) box.hidden = false;
	}

	/* Кнопка «Заказать расчет» лежит в безымянном div — помечаем обёртку классом,
	   чтобы стили могли поставить её на место из макета (ссылка под счётчиком).
	   Текст кнопки не трогаем: в макете он свой, но на сайте кнопка настоящая. */
	function tagCalcButton() {
		var btn = $('.info_item .one_click');
		if (!btn) return;
		if (btn.parentElement) btn.parentElement.classList.add('nd-pd__calc');
		/* Заголовок всплывающей формы тема берёт из текста триггера, но для наших
		   кнопок hash.t приезжает как document — и в заголовок (а также в скрытое
		   поле NAMEFORM, которое уходит в письмо и CRM) попадало название веб-формы
		   «Общая форма». Починка живёт в js/newdesign-header.js и подключается
		   атрибутом data-nd-form-title (см. комментарий там же). */
		if (!btn.getAttribute('data-nd-form-title')) {
			var label = (btn.textContent || '').replace(/\s+/g, ' ').trim();
			if (label) btn.setAttribute('data-nd-form-title', label);
		}
	}

	/* «Цена:», «руб» и «Экономия» правим текстом, а не стилями. В макете цена
	   выглядит как «4 000₽/шт» и скидка как «скидка N₽», а тема печатает
	   «Цена: 4 501 руб/м²» и «Экономия»: из-за длины этих подписей строка цены
	   не влезала в колонку 543 и скидка срывалась на второй этаж.
	   Приём перенесён с vrn.easydecking.ru (catalog.element/main6, fixMoney):
	   обходим текстовые узлы TreeWalker'ом и правим только их, разметку не
	   трогаем — её всё равно перерисовывает скрипт темы.
	   Замена обязана переприменяться: ядро пересобирает эти строки при смене
	   длины, предложения и количества. Зацикливания через observer нет — после
	   первого прохода замена уже сделана, текст не меняется и мутаций не рождает. */
	function fixMoney(root) {
		if (!root) return;
		var wk = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null), tn;
		while ((tn = wk.nextNode())) {
			var t0 = tn.textContent;
			var t1 = t0.replace(/Цена\s*:\s*/g, '')
				.replace(/руб\.?/g, '₽')
				.replace(/Экономия/g, 'скидка');
			if (t1 !== t0) tn.textContent = t1;
		}
	}

	/* Состояние «В корзине». Тема сама переключает display у .to-cart/.in-cart,
	   но счётчик при этом остаётся на месте. На easydecking в этом состоянии
	   счётчик убирают, а кнопка занимает всю ширину — переносим тем же классом.
	   Привязываемся не к узлу кнопки, а к .counter_wrapp: тема пересоздаёт
	   .button_block при смене длины, и observer на самой кнопке отваливался бы
	   (та же грабля описана в комментарии easydecking). */
	function syncInCart() {
		if (!root) return;
		Array.prototype.forEach.call(root.querySelectorAll('.buy_block .counter_wrapp'), function (cw) {
			var inCart = cw.querySelector('.button_block .in-cart');
			var shown = !!inCart && getComputedStyle(inCart).display !== 'none';
			cw.classList.toggle('nd-incart', shown);
		});
	}

	/* Заодно обновляет состояние «В корзине»: обе правки нужны в одних и тех же
	   точках — после перерисовки блока покупки темой. */
	function fixMoneyAll() {
		if (!root) return;
		fixMoney($('.prices_block', root));
		/* .total_summ — родная строка Aspro (видна при количестве > 1),
		   .measure-block-desc — её аналог от компонента единиц измерения.
		   На latitudo ни того, ни другого в разметке нет — строки «Общая
		   стоимость» тут пока просто некому печатать. */
		Array.prototype.forEach.call(root.querySelectorAll('.total_summ, .measure-block-desc'), fixMoney);
		syncInCart();
		/* Тема перерисовывает блок покупки при смене длины и предложения —
		   после этого узлы надо снова забрать в мобильную панель. */
		applyBuyBar();
	}

	/* ================= мобильная прилипающая панель покупки =================
	   Макет 20512:84167: цена и единицы первой строкой, счётчик с корзиной
	   второй, панель над нижней навигацией. Приём перенесён с
	   vrn.easydecking.ru (#ld-mobile-buybar, applyBuybar).

	   Узлы НЕ копируются, а переносятся: на них висит offer-JS темы (пересчёт
	   цены, единицы, счётчик), копия перестала бы обновляться. По той же
	   причине панель создаётся ВНУТРИ карточки, а не в body — селекторы темы
	   ищут цену внутри своего контейнера. На десктопе всё возвращается на
	   исходные места, поэтому запоминаем родителя и следующего соседа. */
	var buyHomes = {};

	function rememberHome(el, key) {
		if (!el || buyHomes[key]) return;
		buyHomes[key] = { parent: el.parentNode, next: el.nextSibling };
	}

	function navHeight() {
		var nav = $('.nd-navbar');
		return nav ? Math.round(nav.getBoundingClientRect().height) : 0;
	}

	/* Панель без кнопки покупки бессмысленна, а её строит ядро уже после
	   загрузки — до этого момента не собираем. */
	function buyBarReady() {
		return !!(root && root.querySelector('.buy_block .button_block'));
	}

	function applyBuyBar() {
		if (!root) return;
		var isMobile = window.matchMedia('(max-width: 767px)').matches;
		var bar = document.getElementById('nd-pd-buybar');

		if (!isMobile) {
			if (!bar) return;
			['prices_block', 'measure-unit-block', 'buy_block'].forEach(function (cls, i) {
				var key = ['prices', 'units', 'buy'][i];
				var el = bar.querySelector('.' + cls);
				var home = buyHomes[key];
				if (el && home && home.parent) home.parent.insertBefore(el, home.next);
			});
			bar.parentNode.removeChild(bar);
			document.body.style.removeProperty('padding-bottom');
			return;
		}

		if (!bar && !buyBarReady()) return;

		var prices = root.querySelector('.prices_block');
		/* Переключателей единиц на странице несколько: свой есть у каждой
		   карточки сопутствующего товара. Простой querySelector брал первый
		   попавшийся и утаскивал в панель чужой блок («шт / п.м» вместо
		   «шт / м² / п.м»), поэтому целимся в правую колонку самой карточки.
		   Ищем от документа, а не от root: .item_main_info — внешняя обёртка
		   карточки, а не её потомок, и поиск внутри root ничего не находил.
		   Если блок уже в панели, берём его же — тогда перенос идемпотентен. */
		var units = (bar && bar.querySelector('.measure-unit-block'))
			|| document.querySelector('.item_main_info .right_info .measure-unit-block');
		var buy = root.querySelector('.buy_block');
		rememberHome(prices, 'prices');
		rememberHome(units, 'units');
		rememberHome(buy, 'buy');

		if (!bar) {
			bar = document.createElement('div');
			bar.id = 'nd-pd-buybar';
			bar.innerHTML = '<div class="nd-pd-buybar__top"></div>';
			(root.querySelector('.middle_info.main_item_wrapper') || root).appendChild(bar);
		}
		var top = bar.querySelector('.nd-pd-buybar__top');
		if (prices && prices.parentNode !== top) top.appendChild(prices);
		if (units && units.parentNode !== top) top.appendChild(units);
		/* Порядок из макета: цена слева, единицы справа. Единицы ядро строит
		   позже цены, и при первом заходе они могли уехать первыми — тогда
		   переставляем (appendChild перемещает уже существующего ребёнка). */
		if (prices && units && prices.parentNode === top && units.parentNode === top
			&& (prices.compareDocumentPosition(units) & Node.DOCUMENT_POSITION_PRECEDING)) {
			top.appendChild(units);
		}
		if (buy && buy.parentNode !== bar) bar.appendChild(buy);

		var nh = navHeight();
		bar.style.bottom = nh + 'px';
		/* Тело сдвигаем, иначе панель закрывает низ страницы. !important —
		   у темы свой padding-bottom под нижнюю навигацию. */
		document.body.style.setProperty('padding-bottom', (bar.offsetHeight + nh + 12) + 'px', 'important');
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
		moveChars();
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
				fixMoneyAll();
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
		window.addEventListener('load', function () { syncArticle(); tagCalcButton(); fixMoneyAll(); });
		/* Те же таймеры, что на easydecking: часть строк цены ядро дописывает
		   позже, уже после первого прохода observer'а. */
		fixMoneyAll();
		setTimeout(fixMoneyAll, 600);
		setTimeout(fixMoneyAll, 1500);

		/* Панель собираем, как только ядро построило кнопку покупки: опрос
		   каждые 250 мс, но не дольше ~8 секунд. Плюс реакция на смену
		   ориентации и ширины — при переходе на десктоп узлы возвращаются. */
		applyBuyBar();
		var barTries = 0;
		var barPoll = setInterval(function () {
			barTries++;
			applyBuyBar();
			/* Опрос НЕ прекращаем при появлении панели: переключатель единиц
			   ядро строит позже кнопки покупки, и он не успевал переехать. */
			if (barTries > 32) clearInterval(barPoll);
		}, 250);
		var mqBuy = window.matchMedia('(max-width: 767px)');
		if (mqBuy.addEventListener) mqBuy.addEventListener('change', applyBuyBar);
		else if (mqBuy.addListener) mqBuy.addListener(applyBuyBar);
		var barRz;
		window.addEventListener('resize', function () {
			clearTimeout(barRz);
			barRz = setTimeout(applyBuyBar, 150);
		});
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
