/* «Добавить всё в корзину» в калькуляторах террасы и ограждений (22.10.2026).
   ndCalcBasket(кнопка, подпись, строки) — строки сметы [{id?, name?, qty}] уходят в
   /local/ajax/nd_calc_basket.php, цену берёт корзина из каталога. Потом — счётчик корзины в шапке,
   как после обычного «В корзину». */
window.ndCalcBasket = function (btn, msg, items) {
  function plural(n, a, b, c) { n = Math.abs(n) % 100; var n1 = n % 10; if (n > 10 && n < 20) return c; if (n1 > 1 && n1 < 5) return b; if (n1 === 1) return a; return c; }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  if (!window.BX || !BX.bitrix_sessid) return;
  if (!items.length) { msg.textContent = 'В смете нет товаров с ценой.'; return; }
  btn.disabled = true; msg.textContent = 'Добавляем…';
  var body = new URLSearchParams(); body.append('sessid', BX.bitrix_sessid()); body.append('items', JSON.stringify(items));
  fetch('/local/ajax/nd_calc_basket.php', { method: 'POST', body: body, credentials: 'same-origin' })
    .then(function (res) { return res.json(); })
    .then(function (d) {
      var failed = d.failed && d.failed.length ? ' Не нашли в каталоге: ' + esc(d.failed.join(', ')) + '.' : '';
      if (!d.ok) { msg.innerHTML = esc(d.error || 'Не удалось добавить товары в корзину.') + failed; return; }
      msg.innerHTML = 'Добавлено в корзину: ' + d.added + ' ' + plural(d.added, 'позиция', 'позиции', 'позиций') + '.' + failed + ' <a href="/basket/">Перейти в корзину</a>';
      if (typeof window.getActualBasket === 'function') getActualBasket();
      if (typeof window.reloadTopBasket === 'function' && window.jQuery && jQuery('#ajax_basket').length) reloadTopBasket('add', jQuery('#ajax_basket'), 200, 5000, 'N');
      if (BX.onCustomEvent) BX.onCustomEvent('OnBasketChange');
    })
    .catch(function () { msg.textContent = 'Не удалось добавить товары в корзину. Попробуйте ещё раз.'; })
    .then(function () { btn.disabled = false; });
};
