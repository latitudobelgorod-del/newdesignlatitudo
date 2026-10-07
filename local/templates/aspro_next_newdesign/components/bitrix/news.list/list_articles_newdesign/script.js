// Слайдер «Полезно знать» на главной нового дизайна — по образцу брендов
// (list_brands_newdesign/script.js). Статьи приходят плоским списком, на слайды
// режем здесь по ширине экрана: 3 в ряд на десктопе, 2 на планшете, 2 одна под
// другой на мобильном. Стрелки показываем, только если слайдов больше одного.
(function () {
    'use strict';

    function perSlide() {
        if (window.matchMedia('(max-width: 1199px)').matches) return 2;
        return 3;
    }

    function init(root) {
        if (root.dataset.ndArticlesReady) return;
        root.dataset.ndArticlesReady = '1';

        var track = root.querySelector('[data-nd-articles-track]');
        var nav = root.querySelector('[data-nd-articles-nav]');
        var prev = root.querySelector('[data-nd-articles-prev]');
        var next = root.querySelector('[data-nd-articles-next]');
        if (!track) return;

        var items = Array.prototype.slice.call(track.querySelectorAll('.nd-articles__item'));
        if (!items.length) return;

        var current = 0;
        var size = 0;

        function build() {
            var nextSize = perSlide();
            if (nextSize === size) return;
            size = nextSize;

            track.classList.add('nd-articles__list--slider');
            track.innerHTML = '';

            for (var i = 0; i < items.length; i += size) {
                var slide = document.createElement('div');
                slide.className = 'nd-articles__slide';
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
            var target = current + delta;
            if (target < 0 || target > track.children.length - 1) return;
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
        var list = document.querySelectorAll('[data-nd-articles]');
        for (var i = 0; i < list.length; i++) init(list[i]);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
