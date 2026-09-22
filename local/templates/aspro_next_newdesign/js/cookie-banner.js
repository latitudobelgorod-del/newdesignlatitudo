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
  /* Полоса во всю ширину, прибитая к нижнему краю (Ирина, 16 сентября 2026).
     Прежде была широкая тёмная карточка на три строки с отступом снизу — она
     перекрывала низ страницы.

     Оформление повторяет контурную карточку-сообщение из каталога
     (.nd-catlist-note в newdesign-catalog.css): белый фон, рамка #e5e5ea,
     шрифт Nunito Sans, текст #101014. Отличия только те, что нужны полосе:
     рамка одна сверху, углы не скруглены, отступы и кегль мельче, кружка «i»
     нет. Цвета и шрифт держать согласованными с .nd-catlist-note.

     Содержимое прижато вправо. На десктопе справа внизу висит круглая кнопка
     звонка (Envybox, ~88px и отступ ~32px), поэтому под неё оставлен запас
     140px — иначе «Согласен» оказывается под кнопкой и по нему не попасть.
     На телефоне запас не нужен: там эта кнопка стоит ВЫШЕ полосы и с ней не
     пересекается, а лишний отступ только отрывал «Согласен» от края. */
  var style = document.createElement('style');
  style.textContent = [
    '#cb-wrap{position:fixed;left:0;right:0;bottom:0;background:#fff;color:#101014;',
    'border-top:1px solid #e5e5ea;padding:10px 140px 10px 16px;',
    'display:flex;align-items:center;justify-content:flex-end;gap:12px;',
    'z-index:99999;box-shadow:0 -2px 12px rgba(0,0,0,.06);box-sizing:border-box;',
    "font-family:'Nunito Sans','Nunito Fallback',Arial,sans-serif;",
    'font-size:14px;line-height:20px;',
    'animation:cb-up .3s cubic-bezier(.16,1,.3,1) both;}',
    '@keyframes cb-up{from{opacity:0;transform:translateY(100%)}',
    'to{opacity:1;transform:translateY(0)}}',
    '#cb-wrap.cb-hide{animation:cb-down .25s ease forwards;}',
    '@keyframes cb-down{to{opacity:0;transform:translateY(100%)}}',
    '#cb-text{margin:0;white-space:nowrap;}',
    '#cb-text a{color:#c60000;text-decoration:underline;text-underline-offset:2px;}',
    '#cb-text a:hover{color:#a80000;}',
    '#cb-accept{flex:0 0 auto;height:32px;padding:0 16px;border:0;border-radius:4px;',
    'background:#c60000;color:#fff;font-family:inherit;font-size:13px;font-weight:700;',
    'line-height:32px;cursor:pointer;white-space:nowrap;transition:background-color .15s;}',
    '#cb-accept:hover,#cb-accept:focus{background:#a80000;}',
    /* На телефоне текст в одну строку не влезает: переносим его и отдаём ему
       всю свободную ширину, кнопка встаёт вплотную к правому краю. */
    '@media(max-width:760px){#cb-wrap{justify-content:flex-start;',
    'padding:8px 10px;gap:8px;font-size:12px;line-height:16px;}',
    '#cb-text{white-space:normal;flex:1;}',
    '#cb-accept{height:30px;padding:0 12px;line-height:30px;font-size:12px;}}',
    /* До 991px внизу экрана стоит нижняя навигация (.nd-navbar) — полоса
       согласия ложилась поверх неё (Ирина, 22 сентября 2026). Поднимаем над
       навигацией, а на карточке товара — ещё и над панелью покупки: её верх
       js/newdesign-element.js кладёт в --nd-buybar-top. */
    '@media(max-width:991px){#cb-wrap{bottom:calc(var(--nd-navbar-h,56px) + env(safe-area-inset-bottom,0px));}',
    'html.nd-has-buybar #cb-wrap{bottom:var(--nd-buybar-top);}}'
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