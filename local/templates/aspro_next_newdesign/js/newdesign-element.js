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

	var root, gallery, photo, img, thumbsWrap, track, nextBtn, dots;
	var slides = [];
	var current = 0;

	function $(sel, ctx) { return (ctx || document).querySelector(sel); }

	/* ============ подпорка под гонку в модуле единиц измерения ============
	   Симптом: на части загрузок у товара пропадали и переключатель
	   «шт / м² / п.м», и плитка счётчика — оставался голый счётчик темы, а на
	   телефоне панель покупки разъезжалась (Ирина, 2026-08-15). В консоли при
	   этом «Uncaught TypeError: Cannot read properties of null (reading
	   'querySelector')» в aspro_element_tp/script.js.

	   Разбор. Компонент maxyss:measure_unit печатает внизу страницы
	   `new MeasureUnitSwitcherEl(..., obbx_<id>, ...)` и откладывает разбор
	   разметки на 500 мс. В нём он читает `parentObj.obProduct` — узел карточки.
	   А заполняет это поле шаблон темы в `JCCatalogElement.Init`, который висит
	   на `BX.ready`, то есть на DOMContentLoaded. Кто успеет первым — гонка:
	   на лёгкой странице сначала DOMContentLoaded (всё работает), на тяжёлой —
	   сначала таймер, и `obProduct` ещё null → исключение рвёт инициализацию
	   компонента целиком. Детальная у нас тяжёлая: 600 КБ разметки.

	   Чинить чужой модуль не нужно — достаточно заполнить поле раньше него:
	   значение то же самое, что позже присвоит `Init` (узел по id карточки), и
	   повторное присваивание ничего не ломает.

	   Опрос, а не DOMContentLoaded: объект `obbx_<id>` создаётся встроенным
	   скриптом в середине страницы, и к моменту DOMContentLoaded таймер
	   компонента может уже отработать. Первые 10 секунд после разбора нашего
	   файла проверяем каждые 50 мс — это заведомо раньше чужого таймера,
	   который заводится ещё ниже по странице. */
	function primeOfferObject() {
		var main = $('.item_main_info');
		if (!main || !main.id) return false;
		var ob = window['ob' + main.id];
		if (!ob) return false;
		if (!ob.obProduct) ob.obProduct = document.getElementById(main.id);
		return !!ob.obProduct;
	}

	(function watchOfferObject() {
		if (primeOfferObject()) return;
		var tries = 0;
		var t = setInterval(function () {
			if (primeOfferObject() || ++tries > 200) clearInterval(t);
		}, 50);
	})();

	/* --- превью --- */

	function renderThumbs() {
		if (!track) return;
		track.innerHTML = slides.map(function (s, i) {
			return '<button type="button" class="nd-pd__thumb' + (i === current ? ' is-active' : '') +
				'" data-index="' + i + '"><img src="' + s.thumb + '" alt="' + (s.alt || '') + '" loading="lazy"></button>';
		}).join('');
		updateArrow();
	}

	/* Точки под фото. Нужны на телефоне: там лента превью скрыта, и без них
	   ни показать, сколько всего фото, ни переключить их нечем. На одном фото
	   точка ровно одна и смысла не несёт — тогда ряд не рисуем вовсе. */
	function renderDots() {
		if (!dots) return;
		if (slides.length < 2) {
			dots.innerHTML = '';
			return;
		}
		dots.innerHTML = slides.map(function (s, i) {
			return '<button type="button" class="nd-pd__dot' + (i === current ? ' is-active' : '') +
				'" data-index="' + i + '" role="tab" aria-label="Фото ' + (i + 1) + '"' +
				(i === current ? ' aria-selected="true"' : '') + '></button>';
		}).join('');
	}

	function syncDots() {
		if (!dots) return;
		Array.prototype.forEach.call(dots.children, function (el, i) {
			el.classList.toggle('is-active', i === current);
			if (i === current) el.setAttribute('aria-selected', 'true');
			else el.removeAttribute('aria-selected');
		});
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
		syncDots();
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
		renderDots();
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
		syncTitle();
		syncBarArticle();
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
		/* У товаров с предложениями тема кладёт в .product-article голый номер,
		   у товаров без них шаблон печатает «арт. <span class="value">N</span>».
		   В заголовке по макету стоит одно число — берём .value, если он есть. */
		var value = $('.value', article) || article;
		span.textContent = value.textContent.trim();
	}

	/* Название выбранного торгового предложения в заголовке.
	   Тема умеет это сама (настройка CHANGE_TITLE_ITEM), но в шаблоне карточки
	   блок закомментирован ещё в старом дизайне, и в h1 оставалось имя товара —
	   хотя цвет и длина уже выбраны. Берём имя оттуда же, откуда артикул: тема
	   держит его в своём втором заголовке .product-title-edit (в макете его нет,
	   он скрыт стилями) и переписывает при каждом переключении предложения.
	   Меняем только первый текстовый узел h1 — рядом в нём лежит артикул. */
	function syncTitle() {
		var h1 = $('.catalog_detail.element_newdesign > h1');
		var src = $('.info_item > .product-title-edit');
		if (!h1 || !src) return;

		var name = (src.textContent || '').replace(/\s+/g, ' ').trim();
		if (!name) return;

		var node = h1.firstChild;
		if (node && node.nodeType === 3) {
			if (node.nodeValue.replace(/\s+/g, ' ').trim() !== name) node.nodeValue = name + ' ';
		} else {
			h1.insertBefore(document.createTextNode(name + ' '), h1.firstChild);
		}
	}

	/* Панель карточки на телефоне (макет 20512:84167, фрейм «Catalog»).
	   В макете вместо шапки сайта на карточке стоит полоса «‹ ⋯ раздел артикул»,
	   поэтому шапку прячем классом nd-has-pdbar на <html> — и только когда
	   панель действительно отрисовалась. Раздел и ссылку берём из хлебных
	   крошек: они уже собраны темой и переживают смену предложения.
	   Поведение из просьбы Ирины (18 августа 2026): при прокрутке вниз панель
	   уезжает, наверху страницы возвращается. */
	function initBar() {
		var bar = $('.nd-pd__bar');
		if (!bar || bar.__ndBar) return;
		bar.__ndBar = true;

		var links = document.querySelectorAll('#navigation .nd-crumbs__link');
		var section = links.length ? links[links.length - 1] : null;
		var crumb = $('.nd-pd__bar-crumb', bar);
		var title = $('.nd-pd__bar-title', bar);
		if (section && title && crumb) {
			title.textContent = (section.textContent || '').replace(/\s+/g, ' ').trim();
			crumb.setAttribute('href', section.getAttribute('href'));
		}

		var back = $('.nd-pd__bar-back', bar);
		if (back) {
			back.addEventListener('click', function () {
				/* Пришли по ссылке из списка — возвращаем назад, открыли карточку
				   сразу (поиск, реклама) — уводим в раздел. */
				if (window.history.length > 1) window.history.back();
				else if (crumb) window.location.href = crumb.getAttribute('href');
			});
		}

		bar.hidden = false;
		document.documentElement.classList.add('nd-has-pdbar');
		syncBarArticle();

		var onScroll = function () {
			var y = window.pageYOffset || document.documentElement.scrollTop || 0;
			bar.classList.toggle('is-off', y > 8);
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	/* Артикул в панели — тот же, что в заголовке (меняется вместе с
	   предложением, см. syncArticle). */
	function syncBarArticle() {
		var art = $('.nd-pd__bar-art');
		var src = $('.catalog_detail.element_newdesign > h1 > .nd-pd__article');
		if (!art || !src) return;
		var text = src.textContent.trim();
		if (art.textContent !== text) art.textContent = text;
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
		syncBottomLayout();
		syncCharsLink();
	}

	/* Ссылка «Все характеристики». У темы она разворачивает блок на месте, а по
	   макету должна вести к самому блоку — прокручиваем к нему. Если
	   характеристик у товара нет, ссылку убираем: вести некуда.

	   Обработчик в фазе перехвата и с stopPropagation — иначе сначала
	   отработает делегированный обработчик темы по data-block. */
	function syncCharsLink() {
		var link = document.querySelector('[data-block=".all_charakter"]');
		if (!link) return;

		var chars = $('.nd-pd__chars');
		var target = (chars && chars.children.length) ? chars : $('.row.desc_tab .char_block');

		if (!target) {
			link.hidden = true;
			return;
		}

		link.hidden = false;
		if (link.getAttribute('data-nd-chars-link')) return;
		link.setAttribute('data-nd-chars-link', '1');

		link.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			if (e.stopImmediatePropagation) e.stopImmediatePropagation();
			target.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}, true);
	}

	/* Место блока акций зависит от характеристик (макеты 20475:75976 и
	   23470:58086): без них акции встают в левую колонку рядом с документами,
	   с ними — отдельным рядом во всю ширину. Разметка одна, раскладку в CSS
	   переключает класс. Характеристики переносит moveChars(), и до его
	   работы левая колонка пуста, поэтому решение принимаем после него. */
	function syncBottomLayout() {
		var bottom = $('.nd-pd__bottom');
		var chars = $('.nd-pd__chars');
		if (!bottom) return;
		bottom.classList.toggle('nd-pd__bottom--nochars', !chars || !chars.children.length);
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

	/* ================= подпись выбора предложения =================
	   В макете (Figma 20752:35990) над плашками стоит «Длина: 3000мм» — с
	   выбранным значением, как у цвета выше. Тема печатает только название
	   свойства («Длина, мм»), а выбор показывает подсветкой плашки.

	   Единицу берём из самого названия: у свойства она сидит после запятой
	   («Длина, мм» → «Длина» + «мм»). Тем же приёмом её дописывает к плашкам
	   правило .nd-sku--dlina li .cnt::after в css/newdesign.css.
	   Исходное название запоминаем в data-атрибуте: после первой же перерисовки
	   в подписи уже лежит наш текст, и разбирать его заново нельзя. */
	function syncSkuTitles() {
		var blocks = document.querySelectorAll('.item_main_info .wrapper_sku .nd-sku');
		Array.prototype.forEach.call(blocks, function (blk) {
			var title = $('.bx_item_section_name', blk);
			if (!title) return;

			if (!title.hasAttribute('data-nd-name')) {
				var parts = (title.textContent || '').replace(/\s+/g, ' ').trim().split(',');
				title.setAttribute('data-nd-name', parts.shift().trim());
				title.setAttribute('data-nd-unit', parts.join(',').trim());
			}

			/* Значение есть только у текстовых плашек (длина, размер). У цвета
			   в плашке лежит картинка — там подпись остаётся прежней, а
			   выбранный цвет карточка показывает своим блоком выше. */
			var active = blk.querySelector('li.active .cnt');
			var value = active ? (active.textContent || '').replace(/\s+/g, ' ').trim() : '';

			var name = $('.nd-sku__name', title);
			if (!name) {
				title.textContent = '';
				name = document.createElement('span');
				name.className = 'nd-sku__name';
				title.appendChild(name);
			}
			setText(name, title.getAttribute('data-nd-name') + (value ? ':' : ''));

			var val = $('.nd-sku__value', title);
			if (value && !val) {
				val = document.createElement('span');
				val.className = 'nd-sku__value';
				title.appendChild(val);
			}
			if (val) setText(val, value ? value + title.getAttribute('data-nd-unit') : '');
		});
	}

	/* ================= единица в счётчике =================
	   В макете внутри поля количества стоит «1 шт» (Figma 20489:32372), то есть
	   число с выбранной единицей. Внутрь <input> текст не положить, поэтому
	   рядом с ним держим span, а половинки поля разводит CSS.

	   Название единицы даёт переключатель: у активной кнопки лежит
	   data-unit-name. Если переключателя нет (у товара одна единица), берём его
	   из приписки к цене — «/шт» в .price_measure.

	   Цену ищем там же, где и переключатель, — в правой колонке карточки или в
	   мобильной панели. Просто по документу нельзя: свой .price_measure есть у
	   каждой карточки сопутствующего товара, и первым попадался чужой. */
	function currentUnitName() {
		var ub = unitsBlock();
		var active = ub && ub.querySelector('.measure-unit-active');
		var name = active && active.getAttribute('data-unit-name');
		if (name) return name.trim();

		var bar = document.getElementById('nd-pd-buybar');
		var prices = (bar && bar.querySelector('.prices_block'))
			|| document.querySelector('.item_main_info .right_info .prices_block');
		var measure = prices && prices.querySelector('.price_measure');
		return measure ? (measure.textContent || '').replace(/[\s\/]/g, '') : '';
	}

	function syncQtyUnit() {
		if (!root) return;
		var name = currentUnitName();
		/* Счётчиков в карточке два — штатный .counter_block темы и .measure-block
		   компонента единиц. Видим всегда один, но какой именно — решает
		   компонент, поэтому подписываем оба. */
		Array.prototype.forEach.call(root.querySelectorAll('.buy_block .measure-block, .buy_block .counter_block'), function (box) {
			var input = box.querySelector('input');
			if (!input) return;
			var unit = box.querySelector('.nd-pd-qty-unit');
			if (!name) {
				if (unit) unit.parentNode.removeChild(unit);
				return;
			}
			if (!unit) {
				unit = document.createElement('span');
				unit.className = 'nd-pd-qty-unit';
				input.parentNode.insertBefore(unit, input.nextSibling);
			}
			setText(unit, name);
		});
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
			/* Счётчиков в блоке два: свой у компонента единиц (.measure-block
			   внутри .measure-wrapper) и штатный .counter_block. Когда работает
			   компонент, штатный он прячет инлайновым display:none — но в
			   мобильной панели раскладку счётчика приходится задавать заново, и
			   `display:flex !important` эту заглушку перебивал: в панели стояло
			   два счётчика, а поле единиц сжималось до одного символа.
			   Кто из них лишний, решаем здесь, а не в CSS: у :has() поддержка
			   на старых мобильных браузерах неполная. */
			cw.classList.toggle('nd-has-measure', !!cw.querySelector('.measure-wrapper'));
			showMeasureTile(cw);
		});
	}

	/* Плитка счётчика от модуля единиц: снимаем инлайновый display:none.
	   Модуль ставит его один раз при разборе разметки — «раз штатный счётчик
	   темы сейчас скрыт, спрячу и себя» — и больше никогда не возвращает
	   (в aspro_element_tp/script.js есть ровно одно присваивание
	   wrapper.style.display, и оно в none). Пока модуль стартовал позже темы,
	   это не мешало; после починки гонки (см. primeOfferObject) он успевает
	   раньше, и плитка оставалась невидимой — в панели была одна кнопка во всю
	   ширину вместо «счётчик + В корзину».

	   Только показываем и только когда кнопка «В корзину» на месте: состояние
	   «уже в корзине» прячет счётчик своим классом .nd-incart, и спорить с ним
	   не нужно. */
	function showMeasureTile(cw) {
		var wrap = cw.querySelector('.measure-wrapper');
		if (!wrap || wrap.style.display !== 'none') return;
		var toCart = cw.querySelector('.button_block .to-cart');
		if (toCart && getComputedStyle(toCart).display !== 'none') {
			wrap.style.removeProperty('display');
		}
	}

	/* ================= «Общая стоимость» и пересчёт в единицы =================
	   Макет Figma 23470:58033 (Frame 2087329372): строка под счётчиком, слева
	   «10 шт. = 52 м2 / 34 п.м.», справа «Общая стоимость <сумма>».

	   Источника в разметке нет — ни одного из двух возможных:
	   - штатный `.total_summ` дописывает setPriceItem() из js/main.js, но только
	     при `!is_sku`; у всех товаров latitudo есть торговые предложения,
	     поэтому строка не появляется никогда;
	   - `.measure-block-desc` компонент maxyss:measure_unit создаёт, но в
	     шаблоне aspro_element_tp никуда не вставляет — узел так и висит в
	     воздухе (на easydecking ровно то же самое, там строки тоже нет).
	   Чинить чужой модуль и js темы ради одной строки дороже, чем посчитать
	   самим, и считаем мы ровно то, что посчитала бы тема: `data-value` кнопки
	   корзины × количество. Тот же приём уже работает в списке раздела
	   (js/newdesign-catalog.js, updateTotal).

	   Количество берём из ШТАТНОГО `.counter_block`: компонент единиц держит в
	   нём базовые единицы, а в своём `.measure-field` — выбранные покупателем. */

	function buyBlock() {
		return root ? root.querySelector('.buy_block') : null;
	}

	/* Переключателей единиц на странице несколько (свой у каждой карточки
	   сопутствующего товара) — целимся в правую колонку самой карточки, а на
	   мобильном блок уже переехал в панель покупки. Та же оговорка, что в
	   applyBuyBar(). */
	function unitsBlock() {
		var bar = document.getElementById('nd-pd-buybar');
		return (bar && bar.querySelector('.measure-unit-block'))
			|| document.querySelector('.item_main_info .right_info .measure-unit-block');
	}

	/* «52,6», а не «52.60»: разделитель запятая, хвостовые нули не нужны. */
	function fmtQty(n) {
		return String(Math.round(n * 100) / 100).replace('.', ',');
	}

	/* Писать только при реальном изменении: узлы строки лежат внутри
	   .info_item, за которым следит MutationObserver, а присвоение textContent
	   — это childList-мутация. Без проверки observer вызывал бы себя вечно. */
	function setText(el, txt) {
		if (el && el.textContent !== txt) el.textContent = txt;
	}

	function ensureTotalRow() {
		var buy = buyBlock();
		if (!buy) return null;
		var row = buy.querySelector('.nd-pd-total');
		if (!row) {
			row = document.createElement('div');
			row.className = 'nd-pd-total';
			row.innerHTML = '<span class="nd-pd-total__conv"></span>' +
				'<span class="nd-pd-total__sum">' +
				'<span class="nd-pd-total__label">Общая стоимость</span>' +
				'<span class="nd-pd-total__value"></span></span>';
		}
		/* Строка должна оставаться последней в блоке покупки: тема дописывает
		   в него свои узлы, а на мобильном весь .buy_block уезжает в панель.
		   Сравниваем именно с nextElementSibling: пробельные текстовые узлы
		   считать соседями нельзя, иначе перестановка повторялась бы на каждом
		   проходе, а каждая перестановка будит MutationObserver. */
		if (row.parentNode !== buy || row.nextElementSibling) buy.appendChild(row);
		return row;
	}

	function updateTotal() {
		var row = ensureTotalRow();
		if (!row) return;
		var buy = buyBlock();
		var btn = buy.querySelector('.to-cart');
		var qtyInput = buy.querySelector('.counter_block input.text');
		var value = btn ? parseFloat(btn.getAttribute('data-value')) : 0;
		var qty = qtyInput ? parseFloat(String(qtyInput.value).replace(',', '.')) : 0;

		if (!value || !qty || qty <= 0) {
			row.hidden = true;
			return;
		}
		row.hidden = false;

		var text;
		try {
			text = BX.Currency.currencyFormat(value * qty, btn.getAttribute('data-currency') || 'RUB', true);
		} catch (e) {
			text = Math.round(value * qty) + ' руб.';
		}
		setText($('.nd-pd-total__value', row), text.replace(/руб\.?/gi, '₽'));

		/* «10 шт. = 52 м2 / 34 п.м.». У каждой кнопки переключателя лежит
		   data-unit — сколько базовых единиц в одной выбранной; по нему же
		   компонент считает и цену, и содержимое своего поля количества
		   (calculateQuantiyAspro: value = базовое количество / data-unit).
		   Первая кнопка — базовая единица (data-unit = 1). */
		var conv = '';
		var ub = unitsBlock();
		var items = ub ? ub.querySelectorAll('.measure-unit') : [];
		if (items.length > 1) {
			var parts = [];
			Array.prototype.forEach.call(items, function (it, i) {
				if (!i) return;
				var koef = parseFloat(it.getAttribute('data-unit'));
				if (koef) parts.push(fmtQty(qty / koef) + ' ' + (it.getAttribute('data-unit-name') || ''));
			});
			if (parts.length) {
				conv = fmtQty(qty) + ' ' + (items[0].getAttribute('data-unit-name') || '') +
					' = ' + parts.join(' / ');
			}
		}
		setText($('.nd-pd-total__conv', row), conv);
	}

	/* Счётчик тема меняет через jQuery .change(), а его нативные слушатели не
	   видят. Поэтому пересчитываем от действий покупателя (клик по «+/−», по
	   единице, ввод в поле) и повторяем с задержкой: компонент единиц правит
	   базовое количество уже после клика. */
	function scheduleTotal() {
		updateTotal();
		syncQtyUnit();
		setTimeout(function () { updateTotal(); syncQtyUnit(); }, 60);
		setTimeout(function () { updateTotal(); syncQtyUnit(); }, 250);
	}

	/* Заодно обновляет состояние «В корзине»: обе правки нужны в одних и тех же
	   точках — после перерисовки блока покупки темой. */
	function fixMoneyAll() {
		if (!root) return;
		fixMoney($('.prices_block', root));
		/* .total_summ — родная строка Aspro (видна при количестве > 1),
		   .measure-block-desc — её аналог от компонента единиц измерения.
		   На latitudo ни того, ни другого в разметке нет (см. комментарий к
		   updateTotal) — если вдруг появятся, подписи всё равно будут нашими. */
		Array.prototype.forEach.call(root.querySelectorAll('.total_summ, .measure-block-desc'), fixMoney);
		updateTotal();
		syncInCart();
		syncSkuTitles();
		syncQtyUnit();
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

	/* Второе условие сборки — переключатель единиц, если он на товаре есть.
	   Компонент maxyss:measure_unit ищет своё место так:
	   findParent(.counter_block, 'col-md-6') — и падает, если такого предка
	   нет. Панель уносит .buy_block вместе со счётчиком из колонки темы, и
	   когда она успевала собраться раньше компонента (тот стартует по
	   setTimeout 500 мс), его инициализация обрывалась исключением: на
	   мобильном не было ни переключателя «шт / м² / п.м», ни пересчёта в
	   строке «Общая стоимость». Поэтому пока компонент заявлен, но ещё не
	   отработал, счётчик не трогаем.
	   Заявлен ⟺ определён класс MeasureUnitSwitcherEl: и файл скрипта, и
	   вызов конструктора шаблон aspro_element_tp печатает под одним условием
	   (непустой MEASURE_USED). Карточки сопутствующих товаров этот признак не
	   портят — у них свой класс MeasureUnitSwitcher из шаблона aspro_list_tp.

	   Ждём по ЧАСАМ, а не по числу заходов. Раньше стоял счётчик вызовов
	   (12 штук «по 250 мс»), но applyBuyBar зовут не только из опроса: его
	   дёргает fixMoneyAll на каждую мутацию правой колонки, а во время загрузки
	   их десятки. Лимит расходовался за доли секунды, панель собиралась раньше
	   компонента и роняла его — на мобильном пропадали и переключатель
	   «шт / м² / п.м», и плитка счётчика (Ирина, 2026-08-15). */
	var unitsWaitStart = 0;
	var UNITS_WAIT_MS = 3000;

	function unitsPending() {
		return typeof window.MeasureUnitSwitcherEl === 'function' && !unitsBlock();
	}

	/* Ждать ли ещё компонент. Побочный эффект (запуск отсчёта) держим здесь,
	   чтобы applyBuyBar читался одной строкой. */
	function shouldWaitForUnits() {
		if (!unitsPending()) return false;
		if (!unitsWaitStart) unitsWaitStart = Date.now();
		return Date.now() - unitsWaitStart < UNITS_WAIT_MS;
	}

	/* «Доступно в рассрочку» в мобильном макете (20512:84167) — первая строка
	   правой колонки, выше плашки о цене. Одним order это не сделать: плашка и
	   блок наличия лежат прямо в .info_item, а сама строка — во вложенном
	   флекс-ряду .middle_info .row, и order действует только среди соседей.
	   Переносить узел безопасно: он наш, торговый JS темы его не ищет. */
	function applyInstallment(isMobile) {
		var inst = root.querySelector('.nd-pd__installment');
		if (!inst) return;
		var col = document.querySelector('.item_main_info .right_info .info_item');
		rememberHome(inst, 'installment');
		var home = buyHomes.installment;

		if (isMobile) {
			if (col && inst !== col.firstElementChild) col.insertBefore(inst, col.firstChild);
		} else if (home && home.parent && inst.parentNode !== home.parent) {
			home.parent.insertBefore(inst, home.next);
		}
	}

	function applyBuyBar() {
		if (!root) return;
		var isMobile = window.matchMedia('(max-width: 767px)').matches;
		var bar = document.getElementById('nd-pd-buybar');

		applyInstallment(isMobile);

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

		if (!bar) {
			if (!buyBarReady()) return;
			/* Ждём компонент единиц, но не дольше ~3 с: если он не поднялся по
			   другой причине, панель всё равно нужна. */
			if (shouldWaitForUnits()) return;
		}

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
			/* col-md-6 — страховка, а не раскладка: компонент maxyss ищет своё
			   место как findParent(.counter_block, 'col-md-6') и без такого
			   предка падает на `undefined.prepend`. Ожидание выше почти всегда
			   успевает, но если компонент запоздает (медленный телефон), панель
			   уже не сломает его: он найдёт себя здесь и положит переключатель
			   в панель — там ему и место по макету.
			   Бутстраповские float и width у класса действуют с 992px, а панель
			   живёт только до 768; поля перебивает правило #nd-pd-buybar. */
			bar.className = 'col-md-6';
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
		/* Панель садится вплотную над нижней навигацией — отступ под вырез
		   экрана держит уже она. Если навигации нет, панель оказывается на
		   самом низу, и вырез компенсируем здесь. */
		bar.style.bottom = nh ? nh + 'px' : 'env(safe-area-inset-bottom, 0px)';
		/* Тело сдвигаем, иначе панель закрывает низ страницы. !important —
		   у темы свой padding-bottom под нижнюю навигацию. */
		document.body.style.setProperty('padding-bottom', (bar.offsetHeight + nh + 12) + 'px', 'important');
	}

	/* Листание фото пальцем. На телефоне лента превью скрыта, и до появления
	   точек фото вообще нельзя было сменить. Порог 40px, чтобы обычный тап и
	   вертикальная прокрутка страницы не считались перелистыванием; при явно
	   вертикальном движении жест отдаём странице. Слушатели пассивные —
	   preventDefault тут не нужен, а с ним прокрутка начинает дёргаться. */
	function bindSwipe() {
		if (!photo) return;
		var x0 = null, y0 = null;

		photo.addEventListener('touchstart', function (e) {
			if (e.touches.length !== 1) { x0 = null; return; }
			x0 = e.touches[0].clientX;
			y0 = e.touches[0].clientY;
		}, { passive: true });

		photo.addEventListener('touchend', function (e) {
			if (x0 === null || slides.length < 2) return;
			var t = e.changedTouches[0];
			var dx = t.clientX - x0;
			var dy = t.clientY - y0;
			x0 = null;
			if (Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy)) return;
			show(dx < 0 ? current + 1 : current - 1);
		}, { passive: true });
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
		if (dots) {
			dots.addEventListener('click', function (e) {
				var d = e.target.closest('.nd-pd__dot');
				if (d) show(parseInt(d.getAttribute('data-index'), 10) || 0);
			});
		}
		bindSwipe();
		window.addEventListener('resize', updateArrow);

		/* Пересчёт «Общей стоимости» — от действий покупателя. Слушаем на root:
		   мобильная панель покупки лежит внутри него, и переехавшие узлы
		   продолжают отдавать события сюда. */
		root.addEventListener('click', function (e) {
			if (e.target.closest('.plus, .minus, .measure-button, .measure-unit, .sku_props li, .to-cart, .in-cart')) {
				scheduleTotal();
			}
		});
		root.addEventListener('input', scheduleTotal);
		root.addEventListener('change', scheduleTotal);
	}

	/* Якорные ссылки карточки («Все характеристики», «Примеры применения
	   материала») прокручивает scrollToBlock() из js/main.js. Он вычитает
	   высоту #headerfixed — старой прилипающей шапки, которой в новом дизайне
	   нет (она отключена, шапка сама position:fixed и её высота лежит в
	   --nd-header-h). Из-за этого заголовок уезжал под шапку.
	   scroll-margin-top тут не помогает: прокрутка идёт не якорем, а
	   jQuery.animate({scrollTop}) — правим саму функцию.
	   Ветку с data-toggle (вкладки) оставляем теме: там своя логика. */
	function patchScrollToBlock() {
		var jq = window.jQuery;
		if (!jq || typeof window.scrollToBlock !== 'function' || window.scrollToBlock.__ndPatched) return;
		var orig = window.scrollToBlock;
		var patched = function (block) {
			var $b = jq(block);
			if (!$b.length || typeof $b.data('toggle') !== 'undefined') return orig.apply(this, arguments);
			var h = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--nd-header-h')) || 156;
			var offset = $b.offset().top - h - 16;
			if (typeof $b.data('offset') !== 'undefined') offset += $b.data('offset');
			jq('body, html').animate({ scrollTop: Math.max(0, offset) }, 500);
		};
		patched.__ndPatched = true;
		window.scrollToBlock = patched;
	}

	function init() {
		root = $('.catalog_detail.element_newdesign');
		if (!root) return;
		/* Панель ставим до проверки галереи: она нужна и там, где своей галереи
		   нет (товар без фотографий), а дальше init() выходит раньше. */
		initBar();
		gallery = $('.nd-pd__gallery', root);
		if (!gallery) return;
		photo = $('.nd-pd__photo', gallery);
		img = $('.nd-pd__img', gallery);
		thumbsWrap = $('.nd-pd__thumbs', gallery);
		track = $('.nd-pd__thumbs-track', gallery);
		nextBtn = $('.nd-pd__thumbs-next', gallery);
		dots = $('.nd-pd__dots', gallery);

		try { slides = JSON.parse(gallery.getAttribute('data-nd-slides') || '[]'); } catch (e) { slides = []; }

		renderDots();
		moveNodes();
		moveChars();
		/* moveChars() выходит раньше, если характеристик нет — раскладку
		   нижнего ряда в этом случае надо доопределить самим. */
		syncBottomLayout();
		moveDocs();
		tagCalcButton();
		syncSkuTitles();
		patchScrollToBlock();
		bind();

		/* Карточку предложения тема перерисовывает после загрузки — следим за
		   правой колонкой и подхватываем артикул и кнопку расчёта, когда появятся. */
		var info = $('.item_main_info .info_item');
		if (info) {
			new MutationObserver(function () {
				syncArticle();
				syncTitle();
				syncBarArticle();
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
				/* Тема иногда переписывает заголовок именем товара — возвращаем
				   имя предложения, иначе оно «не встаёт» (Ирина, 19 августа 2026). */
				syncTitle();
			}).observe(h1, { childList: true, characterData: true, subtree: true });
		}
		/* patchScrollToBlock ещё раз на load: если js/main.js в сборке окажется
		   ниже нашего файла, к моменту init() глобальной scrollToBlock ещё нет. */
		window.addEventListener('load', function () {
			syncArticle(); syncTitle(); syncBarArticle(); tagCalcButton(); patchScrollToBlock(); fixMoneyAll();
		});
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

/* ============================================================================
   ?pid=<ID предложения> — открыть карточку сразу на нужном торговом
   предложении (Ирина, 2026-08-17).

   Так с поиска приходят ссылки, найденные по артикулу предложения: страница
   выдачи (components/bitrix/catalog/main/search.php) и подсказки в шапке ведут
   на карточку товара с этим параметром.

   Своего адреса у предложения нет: инфоблок 20 отдаёт DETAIL_PAGE_URL
   родителя (#PRODUCT_URL#), а выбор варианта живёт в браузере. Поэтому
   выбираем его так же, как это сделал бы человек, — нажимаем нужные пункты
   списка свойств. Соответствие «предложение → значения свойств» знает объект
   JCCatalogElement шаблона main6: он лежит в переменной со сгенерированным
   именем, поэтому ищем его перебором по window. Объект создаётся инлайновым
   скриптом внизу карточки, так что несколько раз проверяем по таймеру.
   ========================================================================= */
(function () {
	'use strict';

	var match = location.search.match(/[?&]pid=(\d+)/);
	if (!match) return;
	var pid = match[1];

	/* Параметр — служебный, покупателю его видеть незачем: как только выбор
	   применён, убираем его из адресной строки. replaceState не перезагружает
	   страницу и не оставляет лишнего шага в истории «назад». */
	function cleanUrl() {
		if (!window.history || !history.replaceState) return;
		var search = location.search
			.replace(/([?&])pid=\d+&?/, '$1')
			.replace(/[?&]$/, '');
		history.replaceState(null, '', location.pathname + search + location.hash);
	}

	/* Объектов может быть несколько: у карточки свой, и ещё по одному у блоков
	   «с этим товаром покупают» и быстрого просмотра. Обходим все — нужное
	   предложение найдётся только у своего, а пункты жмём внутри его же
	   контейнера (obTree), чтобы не задеть соседние товары. */
	function findElementObjects() {
		var found = [];
		for (var key in window) {
			var obj;
			try { obj = window[key]; } catch (e) { continue; }
			if (obj && typeof obj === 'object'
				&& obj.offers && obj.offers.length
				&& obj.treeProps && obj.treeProps.length
				&& typeof obj.SelectOfferProp === 'function') {
				found.push(obj);
			}
		}
		return found;
	}

	function selectIn(obj) {
		var offer = null, i;
		for (i = 0; i < obj.offers.length; i++) {
			if (String(obj.offers[i].ID) === pid) { offer = obj.offers[i]; break; }
		}
		// Предложение не с этой карточки — молча оставляем выбор по умолчанию.
		if (!offer || !offer.TREE) return;

		var scope = obj.obTree || document;
		for (i = 0; i < obj.treeProps.length; i++) {
			var propId = obj.treeProps[i].ID;
			var value = offer.TREE['PROP_' + propId];
			if (value === undefined || value === null) continue;

			var nodes = scope.querySelectorAll('[data-treevalue="' + propId + '_' + value + '"]');
			for (var j = 0; j < nodes.length; j++) {
				if (/ active(\s|$)/.test(' ' + nodes[j].className)) continue;
				// Обработчик темы навешен через jQuery .on('click'), поэтому
				// обычного клика достаточно — jQuery слушает нативные события.
				nodes[j].click();
			}
		}
	}

	function apply() {
		var objects = findElementObjects();
		if (!objects.length) return false;
		for (var i = 0; i < objects.length; i++) selectIn(objects[i]);
		cleanUrl();
		return true;
	}

	var tries = 0;
	var timer = setInterval(function () {
		if (apply() || ++tries > 40) {
			clearInterval(timer);
			// Объект так и не появился (у товара нет предложений) — адрес всё
			// равно приводим в порядок, показывать служебный хвост незачем.
			cleanUrl();
		}
	}, 100);
})();
