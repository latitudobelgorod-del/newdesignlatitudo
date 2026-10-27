/**
 * Подсказка «больше нет на складе» у счётчика количества (Ирина, 27.10.2026).
 *
 * У товаров с учётом количества (распродажа: QUANTITY_TRACE=Y, CAN_BUY_ZERO=N) счётчик темы
 * упирается в остаток молча: «+» перестаёт срабатывать, а вписанное число тихо уменьшается.
 * Покупатель жмёт и не понимает, что происходит. Ловим оба случая и показываем под счётчиком
 * «Больше нет на складе — доступно N шт.». Подсказка держится, пока количество не станет меньше
 * остатка (сама не гаснет — её не успевали прочитать).
 *
 * Работает делегированием на document: и в списке, и в карточке, и для товаров, подгруженных
 * «Показать ещё». У товаров без учёта количества «+» всегда прибавляет — подсказка там не появится.
 */
(function () {
	'use strict';

	var BOX = '.counter_block, .measure-block';

	// стиль — здесь же, чтобы подсказка была одним файлом: красный текст под счётчиком.
	// В плитке списка узко, а предки держат nowrap — переносим явно (Ирина, 27.10.2026: «обрезанная»).
	var css = document.createElement('style');
	css.textContent = '.nd-stock-note{flex-basis:100%;width:100%;max-width:100%;box-sizing:border-box;margin-top:6px;' +
		'font-size:13px;line-height:1.3;color:#c8161d;white-space:normal;overflow-wrap:anywhere;text-align:left;' +
		'opacity:0;max-height:0;overflow:hidden;transition:opacity .2s,max-height .2s}' +
		'.nd-stock-note.is-on{opacity:1;max-height:80px}';
	document.head.appendChild(css);

	function input(box) {
		return box.querySelector('input.text, input.measure-field, input[type="text"], input[type="number"]');
	}
	function num(v) {
		var n = parseFloat(String(v || '').replace(',', '.').replace(/[^\d.]/g, ''));
		return isNaN(n) ? 0 : n;
	}
	function fmt(n) {
		return String(Math.round(n * 100) / 100).replace('.', ',');
	}

	function show(box, qty) {
		var host = box.parentNode;
		if (!host) return;
		var note = host.querySelector(':scope > .nd-stock-note');
		if (!note) {
			note = document.createElement('div');
			note.className = 'nd-stock-note';
			note.setAttribute('role', 'status');
			host.appendChild(note);
		}
		// число и «шт.» не разрываем — иначе в узкой плитке «шт.» уезжает одно на вторую строку
		note.textContent = 'Больше нет на складе — доступно ' + fmt(qty) + ' шт.';
		note.classList.add('is-on');
		note._ndMax = qty;
	}

	// убрать подсказку, когда количество стало меньше остатка
	function hideIfBelow(box) {
		var note = box.parentNode && box.parentNode.querySelector(':scope > .nd-stock-note.is-on');
		var inp = input(box);
		if (note && inp && num(inp.value) < note._ndMax) note.classList.remove('is-on');
	}

	// «+»: значение до нажатия запоминаем в фазе захвата, до обработчика темы; «−» — убираем подсказку
	document.addEventListener('click', function (e) {
		var btn = e.target.closest && e.target.closest('.plus, .minus');
		var box = btn && btn.closest(BOX);
		var inp = box && input(box);
		if (!inp) return;
		if (btn.classList.contains('minus')) {
			setTimeout(function () { hideIfBelow(box); }, 120);
			return;
		}
		var before = num(inp.value);
		setTimeout(function () {
			var after = num(inp.value);
			if (before > 0 && after <= before) show(box, after);
		}, 120);
	}, true);

	// вписали число руками — тема срежет его до остатка
	document.addEventListener('input', function (e) {
		var box = e.target.closest && e.target.closest(BOX);
		if (box && e.target === input(box)) e.target._ndTyped = num(e.target.value);
	}, true);
	['change', 'blur'].forEach(function (type) {
		document.addEventListener(type, function (e) {
			var inp = e.target, box = inp.closest && inp.closest(BOX);
			if (!box || inp !== input(box) || !inp._ndTyped) return;
			var typed = inp._ndTyped;
			setTimeout(function () {
				var now = num(inp.value);
				if (now > 0 && now < typed) show(box, now);
				else hideIfBelow(box);
				inp._ndTyped = 0;
			}, 150);
		}, true);
	});
})();
