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
                if (units && units.parentNode !== prow) prow.appendChild(units);

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

            var hasStock = badges.some(function (b) { return /store-badge-(green|orange)/.test(b.className); });
            var inner = badges.map(function (b) { return b.outerHTML; }).join('');
            list.innerHTML = '<div class="stores-trigger ' + (hasStock ? 'stores-trigger-green' : 'stores-trigger-gray') + '">'
                + (hasStock ? 'В наличии' : 'Под заказ') + ' <span class="stores-arrow">▼</span></div>'
                + '<div class="stores-dropdown" style="display:none;">' + inner + '</div>';

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
            row.querySelector('.nd-old-row__old').innerHTML = fmt(base);
            row.querySelector('.nd-old-row__diff').innerHTML = 'скидка ' + fmt(base - now);
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
       Плитка акции — последняя ячейка второго ряда сетки, дальше через
       каждые четыре ряда. Число колонок берём у самого грида, поэтому на
       мобильном (2 колонки) плитка встаёт туда же, что и в макете.
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
                var pos = cols * (2 + i * 4) - 1;   /* последняя ячейка 2-го, 6-го, 10-го ряда */
                if (pos > items.length) {
                    /* товаров меньше — ставим в конец, но внутри сетки */
                    grid.appendChild(promo);
                    return;
                }
                var before = items[pos];
                if (before) {
                    if (promo.nextElementSibling !== before) grid.insertBefore(promo, before);
                } else {
                    grid.appendChild(promo);
                }
            });
        } catch (e) { }
    }

    /* ---------------------------------------------------------------------
       Чипы тегов: в макете они свёрнуты в один ряд, остальные прячет «…»
       ------------------------------------------------------------------ */
    function collapseTags() {
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

    window.addEventListener('resize', function () {
        if (window.__ndRz) return;
        window.__ndRz = setTimeout(function () { window.__ndRz = 0; placePromo(); collapseTags(); }, 200);
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

    if (document.readyState !== 'loading') markInitialScripts();
    else document.addEventListener('DOMContentLoaded', markInitialScripts);

    document.addEventListener('nd:appended', function () {
        runAppendedScripts();
        setTimeout(run, 100);
        setTimeout(run, 900);
        setTimeout(run, 2000);
    });
})();
