// Слайдер брендов на главной нового дизайна.
// Листает целыми слайдами (10 логотипов), обновляет счётчик и гасит стрелки на краях.
(function () {
    'use strict';

    function init(root) {
        if (root.dataset.ndBrandsReady) return;
        root.dataset.ndBrandsReady = '1';

        var viewport = root.querySelector('[data-nd-brands-viewport]');
        var track = viewport && viewport.querySelector('.nd-brands__track');
        var slides = track ? track.querySelectorAll('.nd-brands__slide') : [];
        var prev = root.querySelector('[data-nd-brands-prev]');
        var next = root.querySelector('[data-nd-brands-next]');
        var counter = root.querySelector('[data-nd-brands-counter]');
        if (!track || slides.length < 2) return;

        var current = 0;

        function pad(n) { return (n < 10 ? '0' : '') + n; }

        function render() {
            track.style.transform = 'translateX(' + (-current * 100) + '%)';
            if (counter) counter.textContent = pad(current + 1) + '/' + pad(slides.length);
            if (prev) prev.disabled = current === 0;
            if (next) next.disabled = current === slides.length - 1;
        }

        function go(delta) {
            var target = current + delta;
            if (target < 0 || target > slides.length - 1) return;
            current = target;
            render();
        }

        if (prev) prev.addEventListener('click', function () { go(-1); });
        if (next) next.addEventListener('click', function () { go(1); });

        render();
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
