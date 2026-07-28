// Слайдер брендов на главной нового дизайна.
// Логотипы приходят плоским списком, на слайды режем здесь — по ширине экрана:
// 10 штук (5x2) на десктопе, 6 на планшете (3x2) и на мобильном (2x3), как в макете.
(function () {
    'use strict';

    function perSlide() {
        if (window.matchMedia('(max-width: 767px)').matches) return 6;   // 2 в ряд x 3 ряда
        if (window.matchMedia('(max-width: 1199px)').matches) return 6;  // 3 в ряд x 2 ряда
        return 10;                                                       // 5 в ряд x 2 ряда
    }

    function init(root) {
        if (root.dataset.ndBrandsReady) return;
        root.dataset.ndBrandsReady = '1';

        var track = root.querySelector('[data-nd-brands-track]');
        var nav = root.querySelector('[data-nd-brands-nav]');
        var prev = root.querySelector('[data-nd-brands-prev]');
        var next = root.querySelector('[data-nd-brands-next]');
        if (!track) return;

        // Запоминаем исходный плоский список карточек
        var items = Array.prototype.slice.call(track.querySelectorAll('.nd-brands__item'));
        if (!items.length) return;

        var current = 0;
        var size = 0;

        function build() {
            var nextSize = perSlide();
            if (nextSize === size) return;
            size = nextSize;

            track.classList.add('nd-brands__track--slider');
            track.innerHTML = '';

            for (var i = 0; i < items.length; i += size) {
                var slide = document.createElement('div');
                slide.className = 'nd-brands__slide';
                for (var j = i; j < i + size && j < items.length; j++) {
                    slide.appendChild(items[j]);
                }
                track.appendChild(slide);
            }

            var total = track.children.length;
            if (nav) nav.hidden = total < 2;
            if (current > total - 1) current = total - 1;
            if (current < 0) current = 0;
            render();
        }

        function render() {
            var total = track.children.length;
            track.style.transform = 'translateX(' + (-current * 100) + '%)';
            if (prev) prev.disabled = current === 0;
            if (next) next.disabled = current >= total - 1;
        }

        function go(delta) {
            var total = track.children.length;
            var target = current + delta;
            if (target < 0 || target > total - 1) return;
            current = target;
            render();
        }

        if (prev) prev.addEventListener('click', function () { go(-1); });
        if (next) next.addEventListener('click', function () { go(1); });

        var timer = null;
        window.addEventListener('resize', function () {
            clearTimeout(timer);
            timer = setTimeout(build, 150);
        });

        build();
    }

    function initAll() {
        var list = document.querySelectorAll('[data-nd-brands]');
        for (var i = 0; i < list.length; i++) init(list[i]);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
