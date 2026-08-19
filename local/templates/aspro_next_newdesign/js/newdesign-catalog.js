/* ============================================================================
   Список товаров раздела — новый дизайн.

   Разметку карточки даёт штатный шаблон Аспро, и порядок блоков в ней другой,
   чем в макете: сначала название, потом цена. Переставлять на сервере нельзя —
   на этой же разметке висят JCCatalogSection (смена оффера) и maxyss:measure_unit
   (единицы измерения, счётчик), они ищут узлы по своим селекторам. Поэтому
   переставляем в браузере, идемпотентно: если блок уже на месте — не трогаем.

   Порядок по макету:
     картинка → [цена + единицы] → [старая цена + скидка] → название →
     цвета → длины → счётчик с корзиной → общая стоимость

   Что ещё делает файл:
     - «В НАЛИЧИИ ▾» для товаров без торговых предложений (штатный
       JCCatalogSection строит эту плашку только при выборе оффера);
     - «руб» → «₽» в источнике данных, чтобы цена не мигала при смене длины;
     - строка «старая цена / скидка» из данных офферов;
     - плитка акции — в последнюю ячейку второго ряда сетки.
   ========================================================================= */
(function () {
    'use strict';

    var READY = 'nd-ready';

    function isMobile() {
        return window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
    }

    /* ---------------------------------------------------------------------
       Перестановка блоков карточки
       ------------------------------------------------------------------ */
    function arrangeCard(card) {
        try {
            /* Товару с офферами счётчик и единицы строит measure_unit (~500мс).
               Переносить DOM раньше нельзя — сломаем сборку компонента.
               Есть ли офферы, видно сразу из разметки: .counter_block[data-offers]. */
            var cblock = card.querySelector('.counter_block');
            var willHaveUnits = !cblock || cblock.getAttribute('data-offers') === 'Y';
            if (willHaveUnits && !card.querySelector('.measure-block')) return;

            var units = card.querySelector('.measure-unit-block');
            var cwrap = card.querySelector('.counter_wrapp');
            if (cwrap) {
                /* нативный счётчик рабочий только там, где measure_unit не подключился */
                cwrap.classList.toggle('nd-native-counter', !!cblock && !cwrap.querySelector('.measure-wrapper'));
            }

            var info = card.querySelector('.item_info') || card.querySelector('.inner_wrap') || card;
            var price = card.querySelector('.cost.prices');
            var title = card.querySelector('.item-title');
            var sku = card.querySelector('.sku_props');
            var colors = card.querySelector('.list__catalog_sku');

            if (price) {
                var prow = info.querySelector('.nd-price-row');
                if (!prow) {
                    prow = document.createElement('div');
                    prow.className = 'nd-price-row';
                    price.parentNode.insertBefore(prow, price);
                }
                if (price.parentNode !== prow) prow.appendChild(price);
                /* На десктопе единицы измерения стоят в строке цены, на
                   мобильном по макету — отдельной строкой под длинами. */
                if (units && !isMobile() && units.parentNode !== prow) prow.appendChild(units);

                var orow = info.querySelector('.nd-old-row');
                if (!orow) {
                    orow = document.createElement('div');
                    orow.className = 'nd-old-row';
                    orow.style.display = 'none';
                    orow.innerHTML = '<span class="nd-old-row__old"></span><span class="nd-old-row__diff"></span>';
                    prow.parentNode.insertBefore(orow, prow.nextSibling);
                }

                if (title && title.previousElementSibling !== orow) info.insertBefore(title, orow.nextSibling);
                if (colors && title && colors.previousElementSibling !== title) info.insertBefore(colors, title.nextSibling);
                if (sku && sku.previousElementSibling !== (colors || title)) info.insertBefore(sku, (colors || title).nextSibling);

                /* мобильный: единицы измерения — своей строкой под длинами */
                if (units && isMobile()) {
                    var after = sku || colors || title;
                    if (after && units.previousElementSibling !== after) info.insertBefore(units, after.nextSibling);
                }

                rublesToSign(price);
            }

            /* Строка «Общая стоимость». Штатный .total_summ темы рисуется только
               при смене количества и прячется при количестве 1 — в макете строка
               видна всегда, поэтому блок свой, а значение считаем как тема:
               цена из data-value кнопки × базовое количество (штуки). */
            if (cwrap && !cwrap.querySelector('.nd-total')) {
                var total = document.createElement('div');
                total.className = 'nd-total';
                total.innerHTML = '<span class="nd-total__label">Общая стоимость</span><span class="nd-total__value"></span>';
                cwrap.appendChild(total);

                /* Строка появляется, только когда покупатель тронул счётчик —
                   как в макете. Слушаем и свои кнопки компонента единиц, и
                   штатный счётчик Аспро, и ручной ввод. */
                var showTotal = function () {
                    cwrap.classList.add('nd-total-on');
                    updateTotal(card);
                    /* Значение в поле меняет скрипт, а не разметка — наблюдатель
                       карточки такое не ловит. Ширину поля под новое число
                       пересчитываем здесь же; с задержкой — потому что компонент
                       единиц измерения дописывает значение после клика. */
                    syncQtyUnit(card);
                    setTimeout(function () { syncQtyUnit(card); }, 60);
                };
                cwrap.addEventListener('click', function (e) {
                    if (e.target.closest('.measure-button, .counter_block .plus, .counter_block .minus')) showTotal();
                });
                cwrap.addEventListener('change', function (e) {
                    if (e.target.closest('.measure-field, .counter_block input')) showTotal();
                });
                cwrap.addEventListener('input', function (e) {
                    if (e.target.closest('.measure-field, .counter_block input')) showTotal();
                });
            }

            /* Дерево SKU с одним-единственным цветом не переключатель, а дубль:
               рядом уже стоит ряд .list__catalog_sku со всеми цветами товара.
               В макете ряд цветов один — лишний прячем. */
            var scu = card.querySelector('.item_info .bx_item_detail_scu');
            if (scu && scu.querySelectorAll('ul li').length < 2) scu.style.display = 'none';

            collapseCardTags(card);

            /* кнопка видео и плашка гарантии — внутрь блока картинки */
            var imgw = card.querySelector('.image_wrapper_block');
            var vid = card.querySelector('.colproduct_video');
            if (vid && imgw && vid.parentNode !== imgw) imgw.appendChild(vid);
            updateVideo(card);

            /* Артикул: в шаблоне два блока (для оффера и без) — оставляем заполненный,
               префикс «Артикул:» в макете не выводится. */
            var arts = card.querySelectorAll('.article_block.item-article, .article_block_nooffer');
            var picked = null;
            [].forEach.call(arts, function (a) {
                var t = (a.textContent || '').trim();
                if (t && !picked) {
                    picked = a;
                    a.style.display = '';
                } else {
                    a.style.display = 'none';
                }
            });
            if (picked) {
                var txt = (picked.textContent || '').replace(/^\s*Артикул\s*:?\s*/i, '').trim();
                if (txt !== picked.textContent) picked.textContent = txt;
            }

            card.classList.add(READY);
        } catch (e) { }
    }

    /* Метки на картинке. Карточка на мобильном вдвое уже, и три метки
       закрывают половину фото — по макету показываем те, что влезли в строку,
       остальные раскрывает круглая стрелка. На десктопе показываем все. */
    function collapseCardTags(card) {
        try {
            var box = card.querySelector('.nd-badges__tags');
            if (!box) return;

            var chips = [].slice.call(box.querySelectorAll('.nd-tag'));
            if (chips.length < 2) return;

            var btn = box.querySelector('.nd-badges__more');

            if (!isMobile() || box.classList.contains('is-open')) {
                chips.forEach(function (c) { c.style.display = ''; });
                if (btn && !isMobile()) btn.remove();
                return;
            }

            if (!btn) {
                btn = document.createElement('span');
                btn.className = 'nd-badges__more';
                btn.setAttribute('aria-label', 'Показать все метки');
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    box.classList.add('is-open');
                    chips.forEach(function (c) { c.style.display = ''; });
                    btn.remove();
                });
                box.appendChild(btn);
            }

            chips.forEach(function (c) { c.style.display = ''; });
            var top0 = chips[0].getBoundingClientRect().top;
            var hidden = 0;
            chips.forEach(function (c, i) {
                if (i === 0) return;
                if (c.getBoundingClientRect().top > top0 + 2) {
                    c.style.display = 'none';
                    hidden++;
                }
            });
            if (!hidden) btn.remove();
        } catch (e) { }
    }

    /* «руб» → «₽» в уже отрисованном узле */
    function rublesToSign(node) {
        try {
            var wk = document.createTreeWalker(node, NodeFilter.SHOW_TEXT, null);
            var tn;
            while ((tn = wk.nextNode())) {
                var t0 = tn.textContent, t1 = t0.replace(/руб\.?/gi, '₽');
                if (t1 !== t0) tn.textContent = t1;
            }
        } catch (e) { }
    }

    /* «руб» → «₽» в ИСТОЧНИКЕ данных: при смене длины Аспро перерисовывает цену
       из своих offers[], и замена в готовом DOM успевает мигнуть. */
    function patchOfferCurrency() {
        try {
            if (window.__ndCurPatched) return;
            window.__ndCurPatched = 1;
            var keys = ['PRINT_VALUE', 'PRINT_DISCOUNT_VALUE', 'PRINT_RATIO_PRICE', 'PRINT_DISCOUNT_DIFF',
                'PRINT_SUM', 'PRINT_PRICE', 'PRINT_BASE_PRICE', 'PRINT_RATIO_BASE_PRICE'];
            var fix = function (s) {
                return (typeof s === 'string' && /руб/i.test(s)) ? s.replace(/руб\.?/gi, '₽') : s;
            };
            var walk = function (o, depth) {
                if (!o || typeof o !== 'object' || depth > 4) return;
                for (var k in o) {
                    try {
                        var v = o[k];
                        if (typeof v === 'string') {
                            if (keys.indexOf(k) >= 0 || /руб/i.test(v)) o[k] = fix(v);
                        } else if (v && typeof v === 'object') {
                            walk(v, depth + 1);
                        }
                    } catch (e) { }
                }
            };
            for (var w in window) {
                try {
                    var obj = window[w];
                    if (obj && typeof obj === 'object' && obj.offers && obj.offers.length) walk(obj.offers, 0);
                } catch (e) { }
            }
        } catch (e) { }
    }

    /* ---------------------------------------------------------------------
       Плашка наличия для товаров БЕЗ торговых предложений.
       Штатный updateStoresList вызывается только при выборе оффера, поэтому
       у таких товаров остаётся плоский серверный список складов.
       ------------------------------------------------------------------ */
    function ensureStoresTrigger(card) {
        try {
            var box = card.querySelector('.product-item-stores');
            if (!box) return;
            var list = box.querySelector('.stores-list');
            if (!list || list.querySelector('.stores-trigger')) return;

            var badges = [].slice.call(list.children).filter(function (el) {
                return el.classList && el.classList.contains('store-badge');
            });
            if (!badges.length) return;

            /* Ни на одном складе нет положительного остатка — плашку не рисуем
               вовсе. Отличить «указан ноль» от «количество не заведено» в
               разметке нельзя: result_modifier подставляет 0 всем складам, где
               записи нет. Так же ведёт себя штатный updateStoresList у товаров
               с торговыми предложениями — он прячет блок при нулевой сумме. */
            var hasStock = badges.some(function (b) { return /store-badge-(green|orange)/.test(b.className); });
            if (!hasStock) {
                box.style.display = 'none';
                return;
            }

            var inner = badges.map(function (b) { return b.outerHTML; }).join('');
            list.innerHTML = '<div class="stores-trigger stores-trigger-green">В наличии'
                + ' <span class="stores-arrow">▼</span></div>'
                + '<div class="stores-dropdown" style="display:none;">' + inner + '</div>';

            /* блок скрыт стилями до готовности — показываем его сами */
            box.style.display = 'block';

            var ci = card.closest('.catalog_item');
            if (ci) ci.classList.add('has-stores-block');

            var trigger = list.querySelector('.stores-trigger');
            var dd = list.querySelector('.stores-dropdown');
            var arrow = trigger.querySelector('.stores-arrow');
            trigger.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var open = dd.style.display !== 'none';
                [].forEach.call(document.querySelectorAll('.stores-dropdown'), function (d) {
                    if (d !== dd) {
                        d.style.display = 'none';
                        var a = d.parentNode.querySelector('.stores-arrow');
                        if (a) a.style.transform = 'rotate(0deg)';
                    }
                });
                dd.style.display = open ? 'none' : '';
                if (arrow) arrow.style.transform = open ? 'rotate(0deg)' : 'rotate(180deg)';
            });

            if (!window.__ndStoresOutside) {
                window.__ndStoresOutside = 1;
                document.addEventListener('click', function () {
                    [].forEach.call(document.querySelectorAll('.stores-dropdown'), function (d) {
                        d.style.display = 'none';
                        var a = d.parentNode.querySelector('.stores-arrow');
                        if (a) a.style.transform = 'rotate(0deg)';
                    });
                });
            }
        } catch (e) { }
    }

    /* ---------------------------------------------------------------------
       Единица измерения в счётчике карточки — «0,45 м²» вместо голого «0,45».
       Так же подписан счётчик на детальной (js/newdesign-element.js,
       .nd-pd-qty-unit) и в корзине (.basket-item-amount-unit): в счётчике
       всегда видно, чего именно взяли столько.

       Внутрь <input> текст не положить, поэтому рядом с ним держим span.

       Пара «число + единица» стоит по центру плитки — как одна надпись.
       Поделить плитку пополам между полем и подписью нельзя: по центру
       оказывался бы стык, а не середина надписи, и «1,00 шт» заметно
       уезжало влево (у числа половина шире, чем у «шт»). Поэтому поле
       подгоняем под ширину значения (fitQtyInput), а центрирует пару CSS
       автоотступами у кнопок «−» и «+».

       Название единицы даёт переключатель «шт / м² / п.м» этой же карточки:
       у активной кнопки лежит data-unit-name (его ставит maxyss:measure_unit).
       Переключателя нет у товара с одной единицей — тогда берём приписку
       к цене, «₽/шт» в .price_measure. Искать строго внутри карточки: свой
       .price_measure есть у каждой из них, по документу попадётся чужой.
       ------------------------------------------------------------------ */
    function cardUnitName(card) {
        var active = card.querySelector('.measure-unit-block .measure-unit-active');
        var name = active && active.getAttribute('data-unit-name');
        if (name) return name.trim();

        var measure = card.querySelector('.price_measure');
        return measure ? (measure.textContent || '').replace(/[\s\/]/g, '') : '';
    }

    /* Ширина поля по содержимому. Своей ширины «по значению» у <input> нет
       (`width: auto` — это дефолтные ~20 символов), а без неё пару не
       центрировать. Меряем зеркальным span'ом тем же шрифтом; результат
       кэшируем на самом поле, чтобы не мерить на каждый тик наблюдателя. */
    var qtyRuler = null;

    function fitQtyInput(input) {
        var val = String(input.value == null ? '' : input.value);
        var cs = getComputedStyle(input);
        /* В ключ входит и шрифт: на телефоне у поля свой размер, и при смене
           ширины окна ту же величину нужно померить заново. */
        var key = val + '|' + cs.fontSize + '|' + cs.fontWeight + '|' + cs.fontFamily;
        if (input.__ndFitKey === key) return;

        if (!qtyRuler) {
            qtyRuler = document.createElement('span');
            qtyRuler.style.cssText = 'position:absolute;left:-9999px;top:-9999px;' +
                'white-space:pre;visibility:hidden;pointer-events:none';
            document.body.appendChild(qtyRuler);
        }
        qtyRuler.style.font = cs.font || (cs.fontWeight + ' ' + cs.fontSize + ' ' + cs.fontFamily);
        qtyRuler.style.letterSpacing = cs.letterSpacing;
        qtyRuler.textContent = val;

        input.__ndFitKey = key;
        /* +1px — на округление подпиксельной ширины, иначе последний знак
           изредка срезается */
        input.style.width = (Math.ceil(qtyRuler.getBoundingClientRect().width) + 1) + 'px';
    }

    /* «1,00 шт» в одной карточке и «1 шт» в соседней: количество печатают два
       разных счётчика. Компонент единиц гонит значение через toFixed(2), а
       штатный счётчик темы отдаёт целое как есть; у товаров без единиц
       компонента нет — отсюда и разнобой (Ирина, 19 августа 2026).
       Хвост из нулей снимаем, дробные значения (0,45 м²) не трогаем. Значение
       компонент читает через parseFloat, так что «1» ему равно «1.00»; события
       не шлём — пересчитывать нечего. Запятую вместо точки рисует сам браузер:
       поле type=number показывает значение по локали. */
    function trimQty(input) {
        var raw = String(input.value == null ? '' : input.value);
        if (!/^-?\d+(?:[.,]\d+)?$/.test(raw)) return;

        var num = parseFloat(raw.replace(',', '.'));
        if (!isFinite(num) || num !== Math.round(num)) return;

        var whole = String(Math.round(num));
        if (raw !== whole) input.value = whole;
    }

    function syncQtyUnit(card) {
        try {
            var name = cardUnitName(card);
            /* Счётчиков в карточке два — штатный .counter_block темы и
               .measure-block компонента единиц. Видно всегда один, но какой
               именно — решает компонент, поэтому подписываем оба. */
            [].forEach.call(card.querySelectorAll('.counter_wrapp .measure-block, .counter_wrapp .counter_block'), function (box) {
                var input = box.querySelector('input');
                if (!input) return;

                trimQty(input);

                var unit = box.querySelector('.nd-qty-unit');
                if (!name) {
                    if (unit) unit.parentNode.removeChild(unit);
                    input.style.width = '';
                    input.__ndFitKey = null;
                    return;
                }
                if (!unit) {
                    unit = document.createElement('span');
                    unit.className = 'nd-qty-unit';
                    input.parentNode.insertBefore(unit, input.nextSibling);
                }
                /* только при реальном изменении: правка текста будит
                   MutationObserver карточки, а он зовёт syncState обратно */
                if (unit.textContent !== name) unit.textContent = name;
                fitQtyInput(input);
            });
        } catch (e) { }
    }

    /* ---------------------------------------------------------------------
       Текст и состояния — без переносов DOM (иначе ломаются клики)
       ------------------------------------------------------------------ */
    function syncState(card) {
        try {
            var price = card.querySelector('.cost.prices');
            if (price) rublesToSign(price);

            var tsum = card.querySelector('.total_summ');
            if (tsum) rublesToSign(tsum);

            updateOldPrice(card);
            updateTotal(card);
            updateVideo(card);
            syncQtyUnit(card);

            var cw = card.querySelector('.counter_wrapp'),
                ic = card.querySelector('.in-cart'),
                tc = card.querySelector('.to-cart');

            if (cw && ic) {
                /* Баг Аспро: при смене длины состояние «в корзине» не восстанавливается.
                   Запоминаем офферы, для которых оно хоть раз показывалось. */
                window.__ndBasket = window.__ndBasket || {};
                window.__ndT0 = window.__ndT0 || Date.now();
                var offerId = (tc && tc.getAttribute('data-item')) || ic.getAttribute('data-item');
                var icShown = getComputedStyle(ic).display !== 'none';
                if (icShown && offerId && (Date.now() - window.__ndT0 < 2000)) window.__ndBasket[offerId] = 1;

                var forced = false;
                if (!icShown && offerId && window.__ndBasket[offerId]) {
                    ic.style.display = '';
                    if (tc) tc.style.display = 'none';
                    forced = true;
                }
                var added = icShown || forced;
                cw.classList.toggle('nd-incart', added);
            }
        } catch (e) { }
    }

    /* Кнопка «видео». Ссылку на ролик Аспро проставляет только при выборе
       торгового предложения (offers[].PROD_VIDEO); до этого в href лежит адрес
       товара, и fancybox честно пытается загрузить страницу — отсюда «The
       requested content cannot be loaded». Берём адрес сами, а без ролика
       кнопку прячем.
       Rutube переводим на embed-адрес и открываем во фрейме: обычную страницу
       ролика fancybox не распознаёт (YouTube и Vimeo он умеет сам). */
    function updateVideo(card) {
        try {
            var vid = card.querySelector('.colproduct_video');
            if (!vid) return;

            var obj = findCardObject(card);
            var offer = obj && obj.offers && obj.offers[obj.offerNum];
            var url = (offer && offer.PROD_VIDEO) || vid.getAttribute('data-nd-video') || '';

            /* href, оставшийся от Аспро, ссылкой на ролик не считаем */
            if (!/^https?:\/\//i.test(url)) {
                vid.style.display = 'none';
                return;
            }

            var rutube = /rutube\.ru\/(?:video|play\/embed)\/([0-9a-f]+)/i.exec(url);
            if (rutube) {
                url = 'https://rutube.ru/play/embed/' + rutube[1];
                vid.setAttribute('data-type', 'iframe');
            } else if (!/youtu|vimeo/i.test(url)) {
                vid.setAttribute('data-type', 'iframe');
            }

            vid.setAttribute('data-nd-video', url);
            vid.setAttribute('href', url);
            vid.style.display = '';
        } catch (e) { }
    }

    /* Значение строки «Общая стоимость»: как в теме (updateTotalSumm) —
       цена из data-value кнопки корзины × количество в базовых единицах.
       Базовое количество лежит в скрытом штатном счётчике .counter_block,
       его пересчитывает компонент единиц измерения при смене «шт / м² / п.м». */
    function updateTotal(card) {
        try {
            var box = card.querySelector('.nd-total');
            if (!box) return;

            var btn = card.querySelector('.to-cart');
            var qtyInput = card.querySelector('.counter_block input.text') || card.querySelector('.measure-field');
            if (!btn || !qtyInput) {
                box.style.display = 'none';
                return;
            }

            var value = parseFloat(btn.getAttribute('data-value'));
            var qty = parseFloat(String(qtyInput.value).replace(',', '.'));
            if (!value || !qty || qty < 0) {
                box.style.display = 'none';
                return;
            }

            var sum = value * qty;
            var text;
            try {
                text = BX.Currency.currencyFormat(sum, btn.getAttribute('data-currency') || 'RUB', true).replace(/руб\.?/gi, '₽');
            } catch (e) {
                text = Math.round(sum) + ' ₽';
            }
            box.querySelector('.nd-total__value').innerHTML = text;
            box.style.display = '';
        } catch (e) { }
    }

    /* Строка «старая цена + скидка». Данных о базовой цене в разметке нет —
       берём их из объекта JCCatalogSection карточки (offers[].ITEM_PRICES). */
    function updateOldPrice(card) {
        try {
            var row = card.querySelector('.nd-old-row');
            if (!row) return;

            var obj = findCardObject(card);
            var offer = obj && obj.offers && obj.offers[obj.offerNum];
            var prices = offer && offer.ITEM_PRICES && offer.ITEM_PRICES[offer.ITEM_PRICE_SELECTED || 0];
            if (!prices) {
                row.style.display = 'none';
                return;
            }

            var base = parseFloat(prices.RATIO_BASE_PRICE || prices.BASE_PRICE || 0);
            var now = parseFloat(prices.RATIO_PRICE || prices.PRICE || 0);
            if (!base || !now || base <= now) {
                row.style.display = 'none';
                return;
            }

            var fmt = function (v) {
                try {
                    return BX.Currency.currencyFormat(v, prices.CURRENCY || 'RUB', true).replace(/руб\.?/gi, '₽');
                } catch (e) {
                    return Math.round(v) + ' ₽';
                }
            };
            /* ITEM_PRICES хранит цены в БАЗОВОЙ единице (шт), а цена в карточке
               показана в выбранной покупателем (м², п.м) — без пересчёта старая
               цена и скидка расходились с ней в разы (Ирина, 2026-08-12).
               Коэффициент лежит у активной кнопки переключателя: тем же числом
               компонент единиц умножает и саму цену. */
            var ndKoef = 1;
            var ndActive = card.querySelector('.measure-unit.measure-unit-active');
            if (ndActive) {
                var k = parseFloat(ndActive.getAttribute('data-unit'));
                if (k > 0) ndKoef = k;
            }
            /* Округляем до рубля: после умножения на коэффициент выходят копейки
               (6 233.33), а тема в своей строке показывает целые. */
            row.querySelector('.nd-old-row__old').innerHTML = fmt(Math.round(base * ndKoef));
            row.querySelector('.nd-old-row__diff').innerHTML = 'скидка ' + fmt(Math.round((base - now) * ndKoef));
            row.style.display = '';
        } catch (e) { }
    }

    /* Объект JCCatalogSection карточки: имя переменной — ob + id карточки */
    function findCardObject(card) {
        try {
            var id = card.querySelector('[id^="bx_"][id$="_sku_tree"]') || card.querySelector('.catalog_item[id]');
            var main = card.querySelector('.catalog_item[id]');
            if (!main) return null;
            var ob = window['ob' + main.id];
            return (ob && ob.offers) ? ob : null;
        } catch (e) {
            return null;
        }
    }

    /* ---------------------------------------------------------------------
       Плитка акции — в каждом втором ряду (2, 4, 6…), сторона чередуется:
       первая последней ячейкой ряда, вторая первой, третья снова последней.
       При четырёх колонках это ячейки 8 и 13 — ровно как в макете Figma
       (Ирина, 2026-08-11). Было «через каждые четыре ряда» и всегда справа:
       в разделе с тремя акциями вторая и третья метили в 6-й и 10-й ряд —
       на странице из пяти рядов их нет, обе падали в конец сетки рядом.

       Акции, которым ряда пока не хватило, оставляем в пуле: после «Показать
       ещё» рядов становится больше, placePromo зовётся снова (по nd:appended)
       и ставит их на место. В конец сетки не сваливаем — так и получалась
       пара плиток подряд.

       Число колонок берём у самого грида, поэтому на мобильном (2 колонки)
       плитка встаёт туда же, что и в макете.
       ------------------------------------------------------------------ */
    function placePromo() {
        try {
            var grid = document.querySelector('.nd-catlist .catalog_block');
            if (!grid) return;
            var pool = document.getElementById('nd-promo-pool');
            if (!pool) return;

            var promos = [].slice.call(pool.querySelectorAll('.nd-promo-cell'));
            var placed = [].slice.call(grid.querySelectorAll('.nd-promo-cell'));
            var all = placed.concat(promos);
            if (!all.length) return;

            var cols = 1;
            try {
                cols = getComputedStyle(grid).gridTemplateColumns.split(' ').filter(Boolean).length || 1;
            } catch (e) { }
            if (grid.__ndCols === cols && !promos.length) return;
            grid.__ndCols = cols;

            /* Товары в текущем порядке. Считаем только ячейки .item_block:
               между ними лежат <script> компонента единиц измерения, и по
               общему списку детей позиция плитки уезжала вдвое. */
            var items = [].slice.call(grid.children).filter(function (el) {
                return el.classList.contains('item_block') && !el.classList.contains('nd-promo-cell');
            });

            all.forEach(function (promo, i) {
                /* Ряд у i-й плитки — каждый второй: 2, 4, 6… А сторона чередуется:
                   первая стоит ПОСЛЕДНЕЙ ячейкой ряда, следующая — ПЕРВОЙ, и так
                   по очереди (в макете это ячейки 8 и 13 при четырёх колонках). */
                var row = 2 * (i + 1);
                var cell = (i % 2 === 0) ? cols * row : cols * (row - 1) + 1;
                /* Перед i-й плиткой к этому моменту стоит i плиток, поэтому товаров
                   перед ней на i меньше — иначе каждая следующая уезжала на ряд ниже. */
                var itemsBefore = cell - 1 - i;
                var before = items[itemsBefore];
                if (!before) {
                    /* ряда ещё нет — ждём «Показать ещё», пока держим в пуле */
                    if (promo.parentNode !== pool) pool.appendChild(promo);
                    return;
                }
                if (promo.nextElementSibling !== before) grid.insertBefore(promo, before);
            });
        } catch (e) { }
    }

    /* ---------------------------------------------------------------------
       Ссылки на посадочные страницы из описания раздела — в ряд с сортировкой.

       У разных разделов эти ссылки приходят по-разному. Террасная доска
       набирает их блоком «верхние теги» (sprint.editor), и он печатается прямо
       в панели сортировки — .section_tag_top. У заборной доски такого блока
       нет, а ссылки лежат внутри описания раздела отдельным блоком
       .landings_list_inline: серые плашки под H2, а строка с сортировкой
       пустая (Ирина, 19 августа 2026).

       Собрать их на сервере не выходит: описание раздела печатает другой
       компонент и раньше, чем панель сортировки, а выдрать из него один блок
       нечем. Поэтому переносим ссылки на месте и оформляем как обычные чипы —
       дальше их подхватывает collapseTags и сворачивает в один ряд.
       ------------------------------------------------------------------ */
    function adoptLandingTags() {
        try {
            var box = document.querySelector('.nd-catlist-sort__tags .section_tag_top');
            if (!box || box.__ndAdopted) return;
            /* Свои теги у раздела есть — чужие не добавляем. */
            if (box.querySelector('.tag_ank')) return;

            var src = document.querySelector('.landings_list_inline');
            if (!src) return;

            var links = [].slice.call(src.querySelectorAll('.item a[href]'));
            if (!links.length) return;

            box.__ndAdopted = true;
            links.forEach(function (a) {
                var chip = document.createElement('div');
                chip.className = 'tag_ank';
                chip.appendChild(a);
                box.appendChild(chip);
            });
            src.style.display = 'none';
        } catch (e) { }
    }

    /* ---------------------------------------------------------------------
       Чипы тегов: в макете они свёрнуты в один ряд, остальные прячет «…»
       ------------------------------------------------------------------ */
    function collapseTags() {
        adoptLandingTags();
        try {
            var box = document.querySelector('.nd-catlist-sort__tags .section_tag_top');
            if (!box || box.__ndOpened) return;

            var chips = [].slice.call(box.querySelectorAll('.tag_ank'));
            if (!chips.length) return;

            var more = box.querySelector('.nd-catlist-sort__more');
            if (!more) {
                more = document.createElement('span');
                more.className = 'nd-catlist-sort__more';
                more.textContent = '…';
                more.title = 'Показать все теги';
                more.addEventListener('click', function () {
                    box.__ndOpened = true;
                    chips.forEach(function (c) { c.classList.remove('nd-tag-hidden'); });
                    more.remove();
                });
                box.appendChild(more);
            }

            /* Считаем по фактическому положению: в ряд попадают чипы, чей
               верх совпадает с верхом первого. Кнопке «…» оставляем место. */
            chips.forEach(function (c) { c.classList.remove('nd-tag-hidden'); });
            var top0 = chips[0].getBoundingClientRect().top;
            var limit = box.getBoundingClientRect().right - more.getBoundingClientRect().width - 8;
            var cut = -1;
            for (var i = 0; i < chips.length; i++) {
                var r = chips[i].getBoundingClientRect();
                if (r.top > top0 + 2 || r.right > limit) { cut = i; break; }
            }
            if (cut < 0) {
                more.remove();
                return;
            }
            for (var j = cut; j < chips.length; j++) chips[j].classList.add('nd-tag-hidden');
        } catch (e) { }
    }

    /* ---------------------------------------------------------------------
       Сортировка — выпадающий список
       ------------------------------------------------------------------ */
    function initSort() {
        var sel = document.querySelector('.nd-catlist-sort__select');
        if (!sel || sel.__ndBound) return;
        sel.__ndBound = 1;
        var cur = sel.querySelector('.nd-catlist-sort__current');
        if (!cur) return;
        cur.addEventListener('click', function (e) {
            e.stopPropagation();
            sel.classList.toggle('opened');
        });
        document.addEventListener('click', function () {
            sel.classList.remove('opened');
        });

        /* «Фильтры» на мобильном — штатный триггер темы лежит отдельным блоком
           над списком, а по макету кнопка стоит в строке сортировки */
        var fbtn = document.querySelector('[data-nd-filter-opener]');
        var opener = document.querySelector('.adaptive_filter .filter_opener');
        if (fbtn && opener) {
            fbtn.addEventListener('click', function (e) {
                e.preventDefault();
                opener.click();
            });
        } else if (fbtn) {
            fbtn.style.display = 'none';
        }
    }

    /* ------------------------------------------------------------------ */
    function run() {
        if (!window.__ndClickBound) {
            window.__ndClickBound = 1;
            window.__ndBasket = window.__ndBasket || {};
            window.__ndT0 = window.__ndT0 || Date.now();
            document.addEventListener('click', function (e) {
                var t = e.target && e.target.closest && e.target.closest('.to-cart');
                if (!t) return;
                var id = t.getAttribute('data-item');
                if (id) window.__ndBasket[id] = 1;
            }, true);
        }

        patchOfferCurrency();
        initSort();
        collapseTags();
        placePromo();

        [].forEach.call(document.querySelectorAll('.nd-catlist .catalog_item_wrapp.item'), function (card) {
            /* аварийный предел: если компонент единиц вообще не отработает,
               карточку всё равно показываем (скелетон снимается по таймеру) */
            if (!card.__ndSkelT) {
                card.__ndSkelT = setTimeout(function () { card.classList.add(READY); }, 15000);
            }
            arrangeCard(card);
            ensureStoresTrigger(card);
            syncState(card);

            if (!card.__ndObs) {
                card.__ndObs = true;
                try {
                    new MutationObserver(function () {
                        if (card.__ndT) return;
                        card.__ndT = setTimeout(function () {
                            card.__ndT = 0;
                            if (!card.classList.contains(READY)) arrangeCard(card);
                            syncState(card);
                        }, 60);
                    }).observe(card, {
                        childList: true, subtree: true, characterData: true,
                        attributes: true, attributeFilter: ['style', 'class']
                    });
                } catch (e) { }
            }
        });
    }

    if (document.readyState !== 'loading') run();
    else document.addEventListener('DOMContentLoaded', run);

    setTimeout(run, 600);
    setTimeout(run, 1400);
    setTimeout(run, 2600);
    setTimeout(run, 4200);
    setTimeout(run, 6000);

    /* При смене ширины карточку надо пересобрать: единицы измерения на
       мобильном стоят под длинами, на десктопе — в строке цены. */
    window.addEventListener('resize', function () {
        if (window.__ndRz) return;
        window.__ndRz = setTimeout(function () {
            window.__ndRz = 0;
            var mob = isMobile();
            if (window.__ndMob !== mob) {
                window.__ndMob = mob;
                run();
            } else {
                placePromo();
                collapseTags();
            }
        }, 200);
    });

    /* список перерисовывается ajax'ом (фильтр, «показать ещё») */
    if (window.BX && BX.addCustomEvent) {
        BX.addCustomEvent('onAjaxSuccess', function () { setTimeout(run, 100); });
    }

    /* Догруженные «Показать ещё» карточки приходят через DOMParser + importNode,
       а такие <script> браузер не выполняет: у карточки не появились бы ни
       JCCatalogSection (смена длины и цены), ни единицы измерения. Пересоздаём
       их — новый узел выполняется при вставке. */
    function runAppendedScripts() {
        try {
            var grid = document.querySelector('.nd-catlist .catalog_block');
            if (!grid) return;
            [].forEach.call(grid.querySelectorAll('script:not([data-nd-run])'), function (old) {
                old.setAttribute('data-nd-run', '1');
                var s = document.createElement('script');
                if (old.src) s.src = old.src; else s.textContent = old.textContent;
                old.parentNode.replaceChild(s, old);
                s.setAttribute('data-nd-run', '1');
            });
        } catch (e) { }
    }

    /* серверные скрипты первой страницы уже отработали — помечаем, чтобы не гонять их снова */
    function markInitialScripts() {
        try {
            var grid = document.querySelector('.nd-catlist .catalog_block');
            if (!grid) return;
            [].forEach.call(grid.querySelectorAll('script'), function (s) {
                s.setAttribute('data-nd-run', '1');
            });
        } catch (e) { }
    }


    /* ---------------------------------------------------------------------
       «С этим товаром покупают» — лента вместо сетки.

       Разметку блока печатает catalog.element/main6/component_epilog.php,
       карточки — тот же шаблон списка, поэтому здесь только стрелки и
       счётчик. Листаем на видимую страницу (ширину ленты), и счётчик тоже
       страничный: «какая страница из скольких», как в блоке «Проекты
       с товаром» (news.list/list_projects_product_newdesign/script.js).
       ------------------------------------------------------------------ */
    function pad2(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function initRelated(box) {
        if (box.ndRelatedReady) return;
        var track = box.querySelector('.catalog_block');
        if (!track) return;
        var items = track.querySelectorAll('.item_block');
        if (!items.length) return;

        box.ndRelatedReady = true;

        var counter = box.querySelector('.nd-related__counter');
        var prev = box.querySelector('.nd-related__arrow--prev');
        var next = box.querySelector('.nd-related__arrow--next');

        /* Страниц столько, сколько экранов в ленте. Скрытые карточки
           (в корзине лента подрезается, когда товар убирают из заказа)
           из потока выпадают, поэтому считать их отдельно не нужно —
           scrollWidth уже про оставшиеся. */
        function state() {
            var w = track.clientWidth || 1;
            var total = Math.max(1, Math.ceil((track.scrollWidth - 1) / w));
            /* Конец ленты ловим с запасом в 1px: при дробной ширине карточки
               scrollLeft не дотягивает до scrollWidth - clientWidth. */
            var atEnd = track.scrollLeft >= track.scrollWidth - w - 1;
            return {
                w: w,
                total: total,
                atStart: track.scrollLeft <= 1,
                atEnd: atEnd,
                /* Последняя страница почти всегда неполная — лента упирается
                   в край раньше, чем пройдёт целый экран, и по scrollLeft
                   номер выходил бы на единицу меньше. */
                current: atEnd ? total : Math.min(total, Math.floor(track.scrollLeft / w) + 1)
            };
        }

        function sync() {
            var s = state();
            if (counter) counter.textContent = pad2(s.current) + '/' + pad2(s.total);
            if (prev) prev.disabled = s.atStart;
            if (next) next.disabled = s.atEnd;
            box.classList.toggle('is-static', s.total <= 1);
        }

        function page(dir) {
            /* Прыгаем на номер страницы, а не сдвигаем ленту на экран:
               на последней странице лента стоит впритык к краю, и сдвиг
               назад промахивался мимо начала предыдущей. */
            var s = state();
            var target = Math.min(s.total, Math.max(1, s.current + dir));
            track.scrollLeft = Math.min((target - 1) * s.w, track.scrollWidth - s.w);
            /* scroll-behavior:smooth — событие scroll придёт позже, но
               счётчик обновляем и сразу, чтобы стрелка не мигала. */
            setTimeout(sync, 350);
        }

        if (prev) prev.addEventListener('click', function () { page(-1); });
        if (next) next.addEventListener('click', function () { page(1); });
        track.addEventListener('scroll', sync, { passive: true });
        window.addEventListener('resize', sync);
        sync();
    }

    function initAllRelated() {
        Array.prototype.forEach.call(document.querySelectorAll('.nd-related'), initRelated);
    }

    if (document.readyState !== 'loading') initAllRelated();
    else document.addEventListener('DOMContentLoaded', initAllRelated);
    /* Блок приезжает отложенным component_epilog'ом — переспрашиваем. */
    setTimeout(initAllRelated, 800);
    setTimeout(initAllRelated, 2000);

    if (document.readyState !== 'loading') markInitialScripts();
    else document.addEventListener('DOMContentLoaded', markInitialScripts);

    document.addEventListener('nd:appended', function () {
        runAppendedScripts();
        setTimeout(run, 100);
        setTimeout(run, 900);
        setTimeout(run, 2000);
    });
})();

/* Страница бренда: «Показать ещё» в разделе.

   Раздел показывает первые несколько карточек, остальное лежит за кнопкой:
   у Millargo 295 товаров, и вся сетка с полным JS карточек весит под два
   мегабайта. Ответ /local/ajax/brand_products.php — голые карточки вместе со
   своими <script>, которые считают цены, размеры и остатки. Скрипты, попавшие
   в документ через innerHTML, браузер не выполняет, поэтому пересоздаём их
   узлами; дальше nd:appended — на него подписан пересчёт цен и раскладка
   карточки выше в этом же файле. */
(function () {
    'use strict';

    var loading = false;

    /* Подпись кнопки после догрузки — та же, что печатает шаблон списка. */
    function goodsWord(n) {
        var last = n % 10, two = n % 100;
        if (last === 1 && two !== 11) return 'товар';
        if (last >= 2 && last <= 4 && (two < 12 || two > 14)) return 'товара';
        return 'товаров';
    }

    /* Пересоздаём <script> уже после вставки в документ: сработает он только
       на месте, в живом дереве. data-nd-run — метка для runAppendedScripts(),
       чтобы тот не запустил их по второму разу. */
    function runScripts(nodes) {
        nodes.forEach(function (node) {
            if (node.nodeType !== 1) return;
            var scripts = node.tagName === 'SCRIPT' ? [node] : [].slice.call(node.querySelectorAll('script'));
            scripts.forEach(function (old) {
                var fresh = document.createElement('script');
                if (old.src) fresh.src = old.src;
                else fresh.textContent = old.textContent;
                fresh.setAttribute('data-nd-run', '1');
                old.parentNode.replaceChild(fresh, old);
            });
        });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-nd-brand-more]') : null;
        if (!btn || loading) return;

        var box = btn.closest('.nd-brandsect__more');
        var grid = box ? box.previousElementSibling : null;
        var url = btn.getAttribute('data-url');
        if (!box || !url || !grid || !grid.classList.contains('catalog_block')) return;

        loading = true;
        btn.classList.add('is-loading');

        fetch(url, { credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.text();
            })
            .then(function (html) {
                var holder = document.createElement('div');
                holder.innerHTML = html;
                var added = [].slice.call(holder.childNodes);
                added.forEach(function (node) {
                    grid.appendChild(node);
                });
                runScripts(added);

                /* Метку с остатком печатает шаблон списка. Раздел приходит
                   хвостом целиком (остатка нет, кнопка уходит), сплошной
                   список бренда — порциями, и кнопка сдвигается на следующую. */
                var next = grid.querySelector('[data-nd-brand-next]');
                var left = next ? parseInt(next.getAttribute('data-left'), 10) || 0 : 0;
                var offset = next ? parseInt(next.getAttribute('data-offset'), 10) || 0 : 0;
                if (next) next.parentNode.removeChild(next);

                if (left > 0) {
                    btn.setAttribute('data-url', url.replace(/([?&]offset=)\d+/, '$1' + offset));
                    btn.textContent = 'Показать еще ' + left + ' ' + goodsWord(left);
                    btn.classList.remove('is-loading');
                } else {
                    box.parentNode.removeChild(box);
                }
                document.dispatchEvent(new CustomEvent('nd:appended'));
            })
            .catch(function (err) {
                btn.classList.remove('is-loading');
                console.log('brand more: ' + err.message);
            })
            .then(function () {
                loading = false;
            });
    });
})();
