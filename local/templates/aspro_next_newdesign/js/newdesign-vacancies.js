/**
 * Страница «Вакансии» в новом дизайне: аккордеон вакансий и две ленты
 * с прокруткой (фото «Наша жизнь» и отзывы сотрудников).
 *
 * Разметка — include/company/vacancies_page.php, стили — css/newdesign-vacancies.css.
 * Файл подключается тегом <script defer> прямо из включаемой области, jQuery
 * не нужен: обе механики держатся на нативной прокрутке контейнера.
 */
(function () {
    'use strict';

    function initAccordions(root) {
        var items = root.querySelectorAll('[data-nd-vac-acc]');
        Array.prototype.forEach.call(items, function (item) {
            var head = item.querySelector('.nd-vac__acc-head');
            if (!head) {
                return;
            }
            head.addEventListener('click', function () {
                var open = item.classList.toggle('is-open');
                head.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });
    }

    /* Шаг прокрутки — ширина первой карточки плюс отступ между карточками.
       Считаем по факту, а не по константе: карточек в ряду разное число
       на разной ширине экрана. */
    function stepOf(rail) {
        var first = rail.firstElementChild;
        if (!first) {
            return rail.clientWidth;
        }
        var styles = window.getComputedStyle(rail);
        var gap = parseFloat(styles.columnGap || styles.gap || '0') || 0;
        return first.getBoundingClientRect().width + gap;
    }

    function initRail(rail) {
        /* У ленты фото стрелки лежат поверх неё, у отзывов — в шапке блока
           рядом с заголовком. Поэтому кнопки ищем в ближайшем общем родителе. */
        var root = rail.closest('.nd-vac__hh-reviews') || rail.closest('[data-nd-vac-slider]');
        if (!root) {
            return;
        }
        var prev = root.querySelector('[data-nd-vac-prev]');
        var next = root.querySelector('[data-nd-vac-next]');
        var count = root.querySelector('[data-nd-vac-count]');
        var total = rail.children.length;

        function pad(n) {
            return (n < 10 ? '0' : '') + n;
        }

        function sync() {
            var step = stepOf(rail);
            var max = rail.scrollWidth - rail.clientWidth;
            var atStart = rail.scrollLeft <= 1;
            var atEnd = rail.scrollLeft >= max - 1;

            if (prev) {
                prev.classList.toggle('is-hidden', atStart);
                prev.disabled = atStart;
            }
            if (next) {
                next.classList.toggle('is-hidden', atEnd);
                next.disabled = atEnd;
            }
            if (count) {
                var current = step > 0 ? Math.round(rail.scrollLeft / step) + 1 : 1;
                count.textContent = pad(Math.min(current, total)) + '/' + pad(total);
            }
        }

        function scrollBy(dir) {
            rail.scrollLeft += dir * stepOf(rail);
        }

        if (prev) {
            prev.addEventListener('click', function () {
                scrollBy(-1);
            });
        }
        if (next) {
            next.addEventListener('click', function () {
                scrollBy(1);
            });
        }
        rail.addEventListener('scroll', sync);
        window.addEventListener('resize', sync);
        sync();
    }

    function init() {
        var page = document.querySelector('.nd-vac');
        if (!page) {
            return;
        }
        initAccordions(page);
        Array.prototype.forEach.call(page.querySelectorAll('[data-nd-vac-rail]'), initRail);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
