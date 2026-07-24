/**
 * Cookie Consent Banner + Блокировка трекеров до согласия
 * Путь: /local/templates/aspro_next/js/cookie-banner.js
 *
 * Управляет загрузкой: Top.Mail.Ru, Яндекс.Метрика, Envybox
 * Трекеры подключаются через этот файл — из header.php их нужно убрать.
 */

(function () {
  var COOKIE_NAME  = 'cookie_consent';
  var COOKIE_DAYS  = 30;

  // ─── Конфиг трекеров ────────────────────────────────────────────────────────
  var METRIKA_ID   = '62259859';
  var TOPMAIL_ID   = '3477275';
  var ENVYBOX_CODE = 'e4de92bacc448ee6b674c4cb61afd66e';
  // ────────────────────────────────────────────────────────────────────────────

  function getCookie(name) {
    var m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : null;
  }

  function setCookie(name, value, days) {
    var exp = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = name + '=' + encodeURIComponent(value) +
      '; expires=' + exp + '; path=/; SameSite=Lax';
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

    // 1. Top.Mail.Ru
    var _tmr = window._tmr || (window._tmr = []);
    _tmr.push({ id: TOPMAIL_ID, type: 'pageView', start: (new Date()).getTime() });
    loadScript('https://top-fwz1.mail.ru/js/code.js', 'tmr-code');

    // 2. Яндекс.Метрика
    loadScript('https://mc.yandex.ru/metrika/tag.js?id=' + METRIKA_ID, 'ym-tag', function () {
      (function (m, i) {
        m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments); };
        m[i].l = 1 * new Date();
      })(window, 'ym');
      window.ym(METRIKA_ID, 'init', {
        ssr: true,
        webvisor: true,
        clickmap: true,
        ecommerce: 'dataLayer',
        accurateTrackBounce: true,
        trackLinks: true
      });
    });

    // 3. Envybox
    if (!document.getElementById('envybox-css')) {
      var link = document.createElement('link');
      link.id   = 'envybox-css';
      link.rel  = 'stylesheet';
      link.href = 'https://cdn.envybox.io/widget/cbk.css';
      document.head.appendChild(link);
    }
    loadScript(
      'https://cdn.envybox.io/widget/cbk.js?wcb_code=' + ENVYBOX_CODE,
      'envybox-cbk'
    );
  }

  // ─── Если уже согласились — грузим сразу ────────────────────────────────────
  var consent = getCookie(COOKIE_NAME);
  if (consent === 'accepted') { loadTrackers(); return; }
  if (consent === 'declined') { return; }

  // ─── Стили баннера ──────────────────────────────────────────────────────────
  var style = document.createElement('style');
  style.textContent = [
    '#cb-wrap{position:fixed;bottom:24px;left:50%;transform:translateX(-50%);',
    'width:calc(100% - 48px);max-width:860px;background:#1a1a1a;color:#f0f0f0;',
    'border-radius:14px;padding:18px 22px;display:flex;align-items:center;gap:18px;',
    'z-index:99999;box-shadow:0 8px 32px rgba(0,0,0,.28);font-family:inherit;',
    'font-size:14px;line-height:1.5;box-sizing:border-box;',
    'animation:cb-up .35s cubic-bezier(.16,1,.3,1) both;}',
    '@keyframes cb-up{from{opacity:0;transform:translateX(-50%) translateY(20px)}',
    'to{opacity:1;transform:translateX(-50%) translateY(0)}}',
    '#cb-wrap.cb-hide{animation:cb-down .3s ease forwards;}',
    '@keyframes cb-down{to{opacity:0;transform:translateX(-50%) translateY(20px)}}',
    '#cb-text{flex:1;color:#c8c8c8;}',
    '#cb-text a{color:#fff;text-decoration:underline;text-underline-offset:3px;}',
    '#cb-text a:hover{opacity:.75;}',
    '#cb-actions{display:flex;gap:10px;flex-shrink:0;}',
    '#cb-accept{background:#fff;color:#1a1a1a;border:none;border-radius:8px;',
    'padding:9px 22px;font-size:14px;font-weight:600;cursor:pointer;',
    'white-space:nowrap;transition:opacity .15s;}',
    '#cb-accept:hover{opacity:.85;}',
    '#cb-decline{background:transparent;color:#888;border:1px solid #444;',
    'border-radius:8px;padding:9px 16px;font-size:14px;cursor:pointer;',
    'white-space:nowrap;transition:border-color .15s,color .15s;}',
    '#cb-decline:hover{border-color:#888;color:#ccc;}',
    '@media(max-width:600px){#cb-wrap{flex-direction:column;align-items:flex-start;',
    'bottom:12px;width:calc(100% - 24px);padding:16px 18px;}',
    '#cb-actions{width:100%;}#cb-accept,#cb-decline{flex:1;text-align:center;}}'
  ].join('');
  document.head.appendChild(style);

  // ─── Разметка баннера ───────────────────────────────────────────────────────
  var wrap = document.createElement('div');
  wrap.id = 'cb-wrap';
  wrap.setAttribute('role', 'dialog');
  wrap.setAttribute('aria-label', 'Уведомление об использовании файлов cookie');
  wrap.innerHTML =
    '<p id="cb-text">Мы используем файлы cookie и аналитические сервисы для корректной ' +
    'работы сайта и улучшения качества обслуживания. Нажимая «Принять», вы соглашаетесь ' +
    'на обработку данных согласно ' +
    '<a href="/info/licenses_detail/" target="_blank" rel="noopener">Политике конфиденциальности</a>.</p>' +
    '<div id="cb-actions">' +
      '<button id="cb-decline">Отклонить</button>' +
      '<button id="cb-accept">Принять</button>' +
    '</div>';

  function dismiss(accepted) {
    setCookie(COOKIE_NAME, accepted ? 'accepted' : 'declined', COOKIE_DAYS);
    if (accepted) loadTrackers();
    wrap.classList.add('cb-hide');
    setTimeout(function () { wrap.parentNode && wrap.parentNode.removeChild(wrap); }, 320);
  }

  function mountBanner() {
    document.body.appendChild(wrap);
    document.getElementById('cb-accept').addEventListener('click', function () { dismiss(true); });
    document.getElementById('cb-decline').addEventListener('click', function () { dismiss(false); });
    document.addEventListener('keydown', function esc(e) {
      if (e.key === 'Escape') { dismiss(false); document.removeEventListener('keydown', esc); }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountBanner);
  } else {
    mountBanner();
  }

})();