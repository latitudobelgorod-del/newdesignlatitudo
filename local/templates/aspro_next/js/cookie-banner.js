/**
 * Cookie Consent Banner
 * Путь: /local/templates/aspro_next/js/cookie-banner.js
 *
 * Баннер — уведомление: любая из двух кнопок прячет его на 30 дней.
 * Загрузку трекеров он не решает — так попросили 8 сентября 2026 года.
 *
 * Яндекс.Метрику вставляет в каждую страницу модуль yandex.metrika,
 * Envybox прибит в footer.php шаблона. Прежде этот файл подключал их
 * второй раз после «Принять» — счётчик задваивался (замер в браузере:
 * до нажатия 1, после 2). Поэтому здесь остался только Top.Mail.Ru —
 * его больше нигде на странице нет.
 */

(function () {
  var COOKIE_NAME  = 'cookie_consent';
  var COOKIE_DAYS  = 30;

  // ─── Конфиг трекеров ────────────────────────────────────────────────────────
  var TOPMAIL_ID   = '3477275';
  // ────────────────────────────────────────────────────────────────────────────

  function getCookie(name) {
    var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : null;
  }

  /* Города — поддомены (tula.latitudo.ru и т.п.), выбор города уводит с
     latitudo.ru на поддомен. Кука без domain жила только на одном адресе,
     и на следующем баннер всплывал снова. Ставим её на весь домен сайта:
     пробуем от latitudo.ru вниз, браузер сам отклонит недопустимый уровень.
     На локали (домен из одного слова) — обычная кука. */
  function setCookie(name, value, days) {
    var exp = new Date(Date.now() + days * 864e5).toUTCString();
    var base = name + '=' + encodeURIComponent(value) +
      '; expires=' + exp + '; path=/; SameSite=Lax';
    var parts = location.hostname.split('.');
    for (var i = parts.length - 2; i >= 0; i--) {
      document.cookie = base + '; domain=.' + parts.slice(i).join('.');
      if (getCookie(name) === value) return;
    }
    document.cookie = base;
  }

  function loadScript(src, id, onload) {
    if (id && document.getElementById(id)) return;
    var s = document.createElement('script');
    s.type  = 'text/javascript';
    s.async = true;
    s.src   = src;
    if (id) s.id = id;
    if (onload) s.onload = onload;
    document.head.appendChild(s);
  }

  // ─── Загрузка трекеров ──────────────────────────────────────────────────────
  function loadTrackers() {

    // Top.Mail.Ru
    var _tmr = window._tmr || (window._tmr = []);
    _tmr.push({ id: TOPMAIL_ID, type: 'pageView', start: (new Date()).getTime() });
    loadScript('https://top-fwz1.mail.ru/js/code.js', 'tmr-code');
  }

  // ─── Трекеры грузим всегда, не дожидаясь кнопки ─────────────────────────────
  loadTrackers();

  // Кнопку уже нажимали — баннер больше не показываем.
  if (getCookie(COOKIE_NAME)) { return; }

  // ─── Стили баннера ──────────────────────────────────────────────────────────
  /* Узкая полоса, прибитая к нижнему краю по центру (Ирина, 16 сентября 2026).
     Прежде была широкая тёмная карточка на три строки с отступом снизу — она
     перекрывала низ страницы. Теперь: светлая плашка в один ряд, скруглены
     только верхние углы (4px, как у .nd-btn), кнопка фирменного красного
     #c60000. На телефоне текст переносится, но плашка остаётся тонкой. */
  var style = document.createElement('style');
  style.textContent = [
    '#cb-wrap{position:fixed;bottom:0;left:50%;transform:translateX(-50%);',
    'max-width:calc(100% - 24px);background:#fff;color:#444;',
    'border:1px solid #e6e6e6;border-bottom:0;border-radius:4px 4px 0 0;',
    'padding:8px 8px 8px 16px;display:flex;align-items:center;gap:14px;',
    'z-index:99999;box-shadow:0 -2px 12px rgba(0,0,0,.08);font-family:inherit;',
    'font-size:13px;line-height:18px;box-sizing:border-box;',
    'animation:cb-up .3s cubic-bezier(.16,1,.3,1) both;}',
    '@keyframes cb-up{from{opacity:0;transform:translateX(-50%) translateY(100%)}',
    'to{opacity:1;transform:translateX(-50%) translateY(0)}}',
    '#cb-wrap.cb-hide{animation:cb-down .25s ease forwards;}',
    '@keyframes cb-down{to{opacity:0;transform:translateX(-50%) translateY(100%)}}',
    '#cb-text{margin:0;white-space:nowrap;}',
    '#cb-text a{color:#c60000;text-decoration:underline;text-underline-offset:2px;}',
    '#cb-text a:hover{color:#a80000;}',
    '#cb-accept{flex:0 0 auto;height:34px;padding:0 18px;border:0;border-radius:4px;',
    'background:#c60000;color:#fff;font-family:inherit;font-size:13px;font-weight:700;',
    'line-height:34px;cursor:pointer;white-space:nowrap;transition:background-color .15s;}',
    '#cb-accept:hover,#cb-accept:focus{background:#a80000;}',
    /* На телефоне в одну строку текст не влезает — разрешаем перенос, но
       держим плашку мелкой и прижатой к низу во всю ширину экрана. */
    '@media(max-width:760px){#cb-wrap{left:0;right:0;transform:none;max-width:none;',
    'border-left:0;border-right:0;border-radius:0;padding:8px 10px;gap:10px;',
    'font-size:12px;line-height:16px;animation-name:cb-up-m;}',
    '@keyframes cb-up-m{from{opacity:0;transform:translateY(100%)}',
    'to{opacity:1;transform:translateY(0)}}',
    '#cb-wrap.cb-hide{animation:cb-down-m .25s ease forwards;}',
    '@keyframes cb-down-m{to{opacity:0;transform:translateY(100%)}}',
    '#cb-text{white-space:normal;}',
    '#cb-accept{height:30px;padding:0 14px;line-height:30px;font-size:12px;}}'
  ].join('');
  document.head.appendChild(style);

  // ─── Разметка баннера ───────────────────────────────────────────────────────
  var wrap = document.createElement('div');
  wrap.id = 'cb-wrap';
  wrap.setAttribute('role', 'dialog');
  wrap.setAttribute('aria-label', 'Уведомление об использовании файлов cookie');
  wrap.innerHTML =
    '<p id="cb-text">Сайт использует cookie и аналитику, согласно ' +
    '<a href="/info/licenses_detail/" target="_blank" rel="noopener">Политике конфиденциальности</a>.</p>' +
    '<button id="cb-accept">Согласен</button>';

  /* Кнопка одна, отклонять нечего — пишем всегда accepted. Прежнее
     значение declined у тех, кто нажимал «Отклонить» раньше, баннер
     тоже прячет: показ решает наличие куки, а не её значение. */
  function dismiss() {
    setCookie(COOKIE_NAME, 'accepted', COOKIE_DAYS);
    wrap.classList.add('cb-hide');
    setTimeout(function () { wrap.parentNode && wrap.parentNode.removeChild(wrap); }, 320);
  }

  function mountBanner() {
    document.body.appendChild(wrap);
    document.getElementById('cb-accept').addEventListener('click', function () { dismiss(); });
    document.addEventListener('keydown', function esc(e) {
      if (e.key === 'Escape') { dismiss(); document.removeEventListener('keydown', esc); }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountBanner);
  } else {
    mountBanner();
  }

})();