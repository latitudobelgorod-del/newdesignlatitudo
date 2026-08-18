/* ============================================================================
   Фильтр каталога на мобильном — новый дизайн.
   Макет: Figma «Чистовик», фреймы «Фильтры» 21408:72598 и «Бренды» 21408:72662.

   Разметку даёт штатный catalog.smart.filter (шаблон main_newdesign), трогать
   её нельзя: на инпутах висит smartFilter темы, а поля обязаны остаться внутри
   <form> — иначе они не уйдут в запрос. Поэтому здесь только достраиваем
   обвязку панели и переключаем состояния классами:

     .nd-fm-head   — шапка «‹ Фильтры ✕»
     .nd-fm-chips  — выбранные значения чипами с крестиками
     .nd-fm-scroll — прокручиваемая середина (в неё переезжает .bx_filter_section)
     .nd-fm-bar    — прибитые снизу «Сбросить фильтры» и «Применить»

   Экран «Все» (список одного свойства с чекбоксами) — не отдельная страница,
   а состояние `.nd-fm-sub` на той же панели: прячем остальные группы, а
   выбранной возвращаем вид списка. Так поля остаются в форме.
   ========================================================================= */
(function () {
    'use strict';

    /* Сколько значений показываем в группе до кнопки «Все» */
    var VISIBLE = 6;

    function isMobile() {
        return window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
    }

    function $$(root, sel) {
        return [].slice.call(root.querySelectorAll(sel));
    }

    function icon(kind) {
        var d = kind === 'back' ? 'M15 6l-6 6 6 6' : 'M6 6l12 12M18 6L6 18';
        return '<svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">'
            + '<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="' + d + '"/></svg>';
    }

    /* Закрыть панель.
       Кнопкой-открывашкой этого не сделать: тема повесила на .filter_opener
       только OpenMobileFilter(), а он при открытой шторке молча выходит —
       поэтому «✕», «Применить» и стрелка «назад» ничего не закрывали.
       Закрытие у темы живёт в глобальной CloseMobileFilter(); если её вдруг
       нет, остаются её же триггеры: штатный крестик внутри шторки (мы его
       прячем, но обработчик висит на документе) и клик по затемнению. */
    function closePanel() {
        if (typeof window.CloseMobileFilter === 'function') {
            try {
                window.CloseMobileFilter();
                return;
            } catch (e) { }
        }
        var close = document.querySelector('#mobilefilter .svg-close.close-icons');
        if (close) {
            close.click();
            return;
        }
        var overlay = document.querySelector('#mobilefilter-overlay');
        if (overlay) overlay.click();
    }

    function build(panel) {
        if (panel.__ndFm) return;
        panel.__ndFm = true;

        var section = panel.querySelector('.bx_filter_section');
        if (!section) return;

        /* --- шапка --- */
        var head = document.createElement('div');
        head.className = 'nd-fm-head';
        head.innerHTML = '<button type="button" class="nd-fm-head__back" aria-label="Назад">' + icon('back') + '</button>'
            + '<span class="nd-fm-head__title">Фильтры</span>'
            + '<button type="button" class="nd-fm-head__close" aria-label="Закрыть">' + icon('close') + '</button>';
        panel.insertBefore(head, panel.firstChild);

        /* --- прокручиваемая середина --- */
        var scroll = document.createElement('div');
        scroll.className = 'nd-fm-scroll';
        panel.insertBefore(scroll, section);

        var chips = document.createElement('div');
        chips.className = 'nd-fm-chips';
        scroll.appendChild(chips);
        scroll.appendChild(section);

        /* --- нижняя панель --- */
        var bar = document.createElement('div');
        bar.className = 'nd-fm-bar';
        bar.innerHTML = '<button type="button" class="nd-fm-bar__reset">Сбросить фильтры</button>'
            + '<button type="button" class="nd-fm-bar__apply">Применить</button>';
        panel.appendChild(bar);

        /* --- поведение --- */
        head.querySelector('.nd-fm-head__close').addEventListener('click', closePanel);
        head.querySelector('.nd-fm-head__back').addEventListener('click', function () {
            if (panel.classList.contains('nd-fm-sub')) closeSub(panel);
            else closePanel();
        });
        bar.querySelector('.nd-fm-bar__apply').addEventListener('click', function () {
            if (panel.classList.contains('nd-fm-sub')) closeSub(panel);
            else closePanel();
        });
        bar.querySelector('.nd-fm-bar__reset').addEventListener('click', function () {
            resetAll(panel);
        });

        /* Значения меняются и кликом по подписи, и скриптом темы —
           перерисовываем чипы по любому изменению внутри панели. */
        panel.addEventListener('change', function () { setTimeout(function () { renderChips(panel); }, 0); });
        panel.addEventListener('click', function (e) {
            if (e.target.closest('.bx_filter_param_label')) {
                setTimeout(function () { renderChips(panel); }, 0);
            }
        });
    }

    function resetAll(panel) {
        var del = panel.querySelector('#del_filter, .bx_filter_search_reset');
        if (del) del.click();
    }

    /* Подпись значения без счётчика в скобках */
    function labelText(label) {
        var t = label.querySelector('.bx_filter_param_text');
        var img = label.querySelector('img');
        var text = (t ? t.getAttribute('title') || t.textContent : label.textContent) || '';
        text = text.replace(/\s*\(\d+\)\s*$/, '').trim();
        if (!text && img) text = img.getAttribute('alt') || '';
        return text;
    }

    /* Чипы выбранных значений + «Сбросить фильтры» */
    function renderChips(panel) {
        var box = panel.querySelector('.nd-fm-chips');
        if (!box) return;

        var items = [];
        $$(panel, '.bx_filter_parameters_box input[type=checkbox], .bx_filter_parameters_box input[type=radio]').forEach(function (input) {
            if (!input.checked) return;
            var label = panel.querySelector('label[for="' + input.id + '"]');
            if (!label) return;
            items.push({id: input.id, text: labelText(label)});
        });

        /* цена — одним чипом «от … до …» */
        var min = panel.querySelector('input.min-price'), max = panel.querySelector('input.max-price');
        if ((min && min.value) || (max && max.value)) {
            items.push({
                price: true,
                text: (min && min.value ? min.value : (min ? (min.placeholder || '').replace(/\D+/g, '') : ''))
                    + ' — ' + (max && max.value ? max.value : (max ? (max.placeholder || '').replace(/\D+/g, '') : '')) + ' ₽'
            });
        }

        if (!items.length) {
            box.innerHTML = '';
            box.classList.remove('is-filled');
            return;
        }

        box.classList.add('is-filled');
        box.innerHTML = items.map(function (it) {
            return '<button type="button" class="nd-fm-chip" data-for="' + (it.id || '') + '"'
                + (it.price ? ' data-price="Y"' : '') + '>' + it.text
                + '<span class="nd-fm-chip__x" aria-hidden="true"></span></button>';
        }).join('') + '<button type="button" class="nd-fm-chip nd-fm-chip--reset">Сбросить фильтры</button>';

        $$(box, '.nd-fm-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                if (chip.classList.contains('nd-fm-chip--reset')) {
                    resetAll(panel);
                    return;
                }
                if (chip.getAttribute('data-price')) {
                    if (min) min.value = '';
                    if (max) max.value = '';
                    fire(min || max, 'input');
                    return;
                }
                var input = document.getElementById(chip.getAttribute('data-for'));
                if (!input) return;
                input.checked = false;
                /* штатный обработчик темы висит инлайном на самом инпуте */
                if (typeof window.smartFilter !== 'undefined' && window.smartFilter.click) {
                    try { window.smartFilter.click(input); } catch (e) { }
                }
                fire(input, 'change');
            });
        });
    }

    function fire(el, type) {
        if (!el) return;
        if (window.jQuery) window.jQuery(el).trigger(type);
        else el.dispatchEvent(new Event(type, {bubbles: true}));
    }

    /* Кнопка «Все» у групп, где значений больше, чем помещается */
    function markGroups(panel) {
        $$(panel, '.bx_filter_parameters_box').forEach(function (group) {
            if (group.classList.contains('price_numbers')) return;
            /* «Лишние» значения тема сама складывает в .hidden_values —
               по нему и понимаем, что группе нужен экран «Все». */
            var hidden = $$(group, '.hidden_values .bx_filter_param_label').length;
            var labels = $$(group, '.bx_filter_param_label');
            var many = hidden > 0 || labels.length > VISIBLE;
            group.classList.toggle('nd-fm-many', many);
            if (!many) return;

            var title = group.querySelector('.bx_filter_parameters_box_title');
            if (!title || title.querySelector('.nd-fm-all')) return;

            var all = document.createElement('span');
            all.className = 'nd-fm-all';
            all.textContent = 'Все';
            all.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();   /* иначе тема свернёт группу */
                openSub(panel, group);
            });
            title.appendChild(all);
        });
    }

    function openSub(panel, group) {
        $$(panel, '.nd-fm-open').forEach(function (g) { g.classList.remove('nd-fm-open'); });
        group.classList.add('nd-fm-open', 'active');
        panel.classList.add('nd-fm-sub');

        /* Имя свойства: в заголовке рядом лежат подсказка темы (.char_name)
           и наша кнопка «Все» — берём копию без них. */
        var nameNode = group.querySelector('.bx_filter_parameters_box_title');
        var name = '';
        if (nameNode) {
            var copy = nameNode.cloneNode(true);
            $$(copy, '.char_name, .nd-fm-all, .props_list').forEach(function (n) { n.remove(); });
            name = (copy.textContent || '').replace(/\s+/g, ' ').trim();
        }
        var title = panel.querySelector('.nd-fm-head__title');
        if (title) {
            title.setAttribute('data-main', title.getAttribute('data-main') || title.textContent);
            title.textContent = name || 'Фильтр';
        }
        var scroll = panel.querySelector('.nd-fm-scroll');
        if (scroll) scroll.scrollTop = 0;
    }

    function closeSub(panel) {
        panel.classList.remove('nd-fm-sub');
        $$(panel, '.nd-fm-open').forEach(function (g) { g.classList.remove('nd-fm-open'); });
        var title = panel.querySelector('.nd-fm-head__title');
        if (title && title.getAttribute('data-main')) title.textContent = title.getAttribute('data-main');
    }

    function run() {
        if (!isMobile()) return;
        $$(document, '.nd-filter').forEach(function (panel) {
            build(panel);
            markGroups(panel);
            renderChips(panel);
        });
    }

    if (document.readyState !== 'loading') run();
    else document.addEventListener('DOMContentLoaded', run);

    setTimeout(run, 600);
    setTimeout(run, 1600);
    window.addEventListener('resize', function () {
        if (window.__ndFmRz) return;
        window.__ndFmRz = setTimeout(function () { window.__ndFmRz = 0; run(); }, 250);
    });
})();
