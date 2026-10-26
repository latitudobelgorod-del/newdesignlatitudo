/* Калькулятор ограждений (/info/kalkulyator-ograzhdeniy/), 21.10.2026.
   Длина ограждения (прямые участки, погонные метры) → секции и столбы → вид заполнения секции (TYPES)
   → детали выбранной серии по правилам производителя → смета.
   Данные каталога — window.LFC_DATA (js/newdesign-fence-calc-data.js), правила сборки — RULES ниже,
   названия и картинки подразделов каталога — window.LFC_SECTIONS (со страницы, из инфоблока).
   Внешний вид — классы калькулятора террасы (ltd-*). */
(function () {
  var DATA = window.LFC_DATA;
  var root = document.getElementById('lfcCalc'); if (!root || !DATA) return;
  var SITE = 'https://latitudo.ru/catalog/ograzhdeniya-iz-dpk/';
  var $ = function (id) { return document.getElementById(id); };
  var EPS = 1e-9, KERF = 0.003;
  var ceil = function (x) { return Math.ceil(x - EPS); };
  function num(v) { var n = parseFloat(String(v).replace(',', '.')); return isFinite(n) ? n : NaN; }
  function fmt(n, d) { return Number(n).toLocaleString('ru-RU', { minimumFractionDigits: d || 0, maximumFractionDigits: d == null ? 2 : d }); }
  function rub(n) { return fmt(Math.round(n), 0) + ' ₽'; }
  function plural(n, a, b, c) { n = Math.abs(n) % 100; var n1 = n % 10; if (n > 10 && n < 20) return c; if (n1 > 1 && n1 < 5) return b; if (n1 === 1) return a; return c; }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function ce(s) { return String(s || '').toLowerCase().replace(/ё/g, 'е'); }
  // деталь: [название, цена, длина мм, сечение «100х100», цвет, метки, адрес, картинка, ID товара/предложения]
  function link(it) { return '<a href="' + SITE + it[6] + '" target="_blank" rel="noopener">' + esc(it[0]) + '</a>'; }
  function sec(it, i) { var m = String(it && it[3] || '').split('х'); return (+m[i || 0] || 0) / 1000; }
  function mm(it) { return it && it[2] ? ' ' + fmt(it[2]) + ' мм' : ''; }
  function has(it, tag) { return (' ' + it[5] + ' ').indexOf(' ' + tag + ' ') >= 0; }

  /* Правила сборки по сериям (id раздела каталога). Источники: инструкции POLIVAN GROUP (Сингараджа,
     Денпасар, Нусадуа), инструкция «Террапол», каталог Латитудо (EasyDecking); NEXTWOOD — типовые.
     step     — наибольший шаг столбов по осям, м; stepDef — шаг по умолчанию
     postPer  — сколько столбов выходит из одной заготовки; h — высота ограждения по умолчанию, м (клиент может задать свою)
     rails    — ряды перил на секцию: rail (перила/поручень) и bottom (подбалясенник)
     balPer   — балясин из одной заготовки ДПК; perM — балясин на метр секции (иначе по просвету ≤ 110 мм)
     sup      — подпорки под нижним поручнем на секцию (функция от длины секции), supLen — их длина, м
     railFix  — крепёж перил на каждый конец каждого ряда
     alt      — Террапол Классик: большие и малые столбы через один, по краям большие */
  // Подпорка под нижними перилами — минимум одна на секцию у всех серий, как на картинках видов
  // (Ирина, 22.10.2026); режется из обрезков балясин, не хватит обрезков — из целой балясины.
  var BASE = { step: 1.5, postPer: 2, h: 1.1, rails: [['rail', 2]], balPer: 3, gap: 0.11, balFix: 2, railFix: true, sup: function () { return 1; }, supLen: 0.1 };
  var RULES = {
    361: { step: 1.5, stepDef: 1,
      note: 'Пролёт между столбами — не больше 1,5 м, рекомендуемый 1 м; под нижними перилами — подпорка. Столбы надеваются на металлические закладные 80×80×950.',
      missing: { railfix: ['Уголок крепёжный 38×30×40 для перил', 'по 2 на каждый конец перил'] } },
    225: { step: 1.5, stepDef: 1, rails: [['rail', 1, 'top'], ['rail', 1, 'bottom']],
      note: 'Пролёт между столбами — не больше 1,5 м, рекомендуемый 1 м; под нижними перилами — подпорка. Сверху — выбранные перила, снизу — прямоугольные. Столбы — на закладные 80×80×950.',
      missing: { railfix: ['Уголок крепёжный 38×30×40 для перил', 'по 2 на каждый конец перил'] } },
    271: { step: 1.5, note: 'Пролёт между столбами — не больше 1,5 м. Перила сверху и снизу, балясины на прямом крепеже, столбы — на металлические кронштейны.' },
    318: { step: 1.5, note: 'Пролёт между столбами — не больше 1,5 м. Перила сверху и снизу, балясины на прямом крепеже, столбы — на металлические кронштейны. Крышки, юбки и крепёж — общие с NEXTWOOD Классик.' },
    356: { step: 1.56, postPer: 3, h: 0.964, balPer: 4, perM: 9 / 1.448, sup: function (c) { return Math.max(1, Math.round(c / 1.448 * 3)); }, supLen: 0.117,
      note: 'По инструкции POLIVAN: шаг столбов по осям — не больше 1560 мм, из столба 2900 мм выходит 3 столба по 964 мм, из поручня — 2 поручня по 1448 мм. На секцию — 9 балясин по 720 мм (из одной балясины — 4) и 3 подпорки под нижним поручнем, 21 крепление балясин.' },
    358: { step: 1.56, postPer: 3, h: 0.964, balPer: 4, perM: 9 / 1.448, sup: function (c) { return Math.max(1, Math.round(c / 1.448 * 3)); }, supLen: 0.117,
      note: 'По инструкции POLIVAN: шаг столбов по осям — не больше 1560 мм, из столба 2900 мм выходит 3 столба по 964 мм, из поручня — 2 поручня по 1448 мм. На секцию — 9 балясин по 720 мм (из одной балясины — 4) и 3 подпорки, 21 крепление балясин.' },
    357: { step: 1.59, postPer: 3, h: 0.964, balPer: 4, perM: 9 / 1.448, perMAlu: 10 / 1.448, sup: function (c) { return Math.max(1, Math.round(c / 1.448 * 3)); }, supLen: 0.108,
      note: 'По инструкции POLIVAN: шаг столбов по осям — не больше 1590 мм, столб 125×125, из одного столба 2900 мм выходит 3 по 964 мм. На секцию 1448 мм — 9 балясин ДПК (21 крепление) или 10 алюминиевых (26 креплений) и 3 подпорки под нижним поручнем.' },
    224: { step: 1.2, alt: true, rails: [['rail', 1], ['bottom', 2]], balFix: 0, sup: function (c) { return c > 0.8 + EPS ? 2 : 1; }, supLen: 0, h: 1,
      note: 'По инструкции «Террапол»: шаг столбов — не больше 1200 мм, по краям и на углах большие столбы, между ними — малый. Сверху перила, под ними и внизу — подбалясенники; опорные элементы под нижним подбалясенником режутся из малого столба (1 на секцию до 800 мм, 2 — длиннее). Столбы надеваются на металлический армирующий профиль.',
      missing: { balfix: ['Крепление балясин 40×20×24 и уголки для перил', 'входят в комплект «Террапол», количество уточнит менеджер'] } },
    320: { step: 1.2, rails: [['rail', 1], ['bottom', 2]], balFix: 0, sup: function (c) { return c > 0.8 + EPS ? 2 : 1; }, supLen: 0, h: 1,
      note: 'По инструкции «Террапол»: шаг столбов — не больше 1200 мм. Сверху перила, под ними и внизу — подбалясенники, опорные элементы под нижним подбалясенником. Столбы надеваются на металлический армирующий профиль.',
      missing: { balfix: ['Крепление балясин и уголки для перил', 'входят в комплект «Террапол», количество уточнит менеджер'] } }
  };
  function rule(S) { var r = {}, k, o = RULES[S.id] || {}; for (k in BASE) r[k] = BASE[k]; for (k in o) r[k] = o[k]; return r; }

  /* Виды ограждений — по листовке «Виды ограждений» LATITUDO (картинки — images/fence-calc/vid-N.jpg).
     Заполнение секции режется из балясин серии; geo(c, h, nb, bw) отдаёт детали одной секции отрезками
     [x1, y1, x2, y2] в метрах: x — от левого края просвета, y — от верха нижних перил до низа верхних.
     mid — средние перила (двойные перила): на этой высоте от верха снизу вниз, выше — короткие балясины.
     vert — заполнение только вертикальное (тогда годятся и алюминиевые балясины). */
  var MID = 0.12, SUP_GAP = 0.5;
  function verts(n, x0, x1, y0, y1, bw) { var out = [], g = (x1 - x0 - n * bw) / (n + 1); for (var j = 0; j < n; j++) { var x = x0 + g + j * (bw + g) + bw / 2; out.push([x, y0, x, y1]); } return out; }
  function hors(h, bw, gap) { var k = Math.max(2, Math.floor((h - gap) / (bw + gap) + EPS)), g = (h - k * bw) / (k + 1), ys = []; for (var i = 0; i < k; i++) ys.push(g + i * (bw + g) + bw / 2); return { ys: ys, g: g }; }
  function cross(x0, x1, y0, y1) { return [[x0, y0, x1, y1], [x0, y1, x1, y0]]; }
  var TYPES = [
    { id: 1, name: 'Вертикальные', label: 'Верти­кальные', vert: true, geo: function (c, h, nb, bw) { return verts(nb, 0, c, 0, h, bw); } },
    { id: 2, name: 'Горизонтальные с подпорками', label: 'Гори­зонталь­ные с под­порками', geo: function (c, h, nb, bw) {
      var z = hors(h, bw, 0.08), out = z.ys.map(function (y) { return [0, y, c, y]; }), lv = [0].concat(z.ys.map(function (y) { return y + bw / 2; }));
      // подпорки между рядами: через ряд две и одна, как на рисунке
      lv.forEach(function (y, i) { var top = i < z.ys.length ? z.ys[i] - bw / 2 : h; (i % 2 ? [0.5] : [0.25, 0.75]).forEach(function (f) { out.push([c * f, y, c * f, top]); }); });
      return out; } },
    { id: 3, name: 'Горизонтальные', label: 'Гори­зонталь­ные', geo: function (c, h, nb, bw) { return hors(h, bw, 0.08).ys.map(function (y) { return [0, y, c, y]; }); } },
    { id: 4, name: 'Вертикальные, двойные перила', label: 'Верти­кальные, двойные перила', vert: true, mid: MID, geo: function (c, h, nb, bw) {
      var yM = h - MID; return verts(nb, 0, c, 0, yM, bw).concat(verts(Math.max(2, Math.round(nb / 2)), 0, c, yM, h, bw)); } },
    { id: 5, name: 'Ранчо', geo: function (c, h, nb, bw) { return hors(h, bw, 0.04).ys.map(function (y) { return [0, y, c, y]; }); } },
    { id: 6, name: 'Двойной крест', geo: function (c, h) { return [[0, 0, 0.65 * c, h], [0.35 * c, 0, c, h], [0, h, 0.65 * c, 0], [0.35 * c, h, c, 0]]; } },
    { id: 7, name: 'V-образные', geo: function (c, h) {
      var out = [[c / 2, 0, c / 2, h]], w = c / 2;
      [0, w].forEach(function (x0) { out.push([x0, h, x0 + 0.35 * w, 0], [x0 + w / 2, 0, x0 + w / 2, h], [x0 + w, h, x0 + 0.65 * w, 0]); });
      return out; } },
    { id: 8, name: 'Крест', geo: function (c, h) { return cross(0, c, 0, h); } },
    { id: 9, name: 'Крест, двойные перила', mid: MID, geo: function (c, h, nb, bw) {
      var yM = h - MID, a = 0.06 * c, b = 0.94 * c;
      return [[a, 0, a, yM], [c / 2, 0, c / 2, yM], [b, 0, b, yM]].concat(cross(a, c / 2, 0, yM), cross(c / 2, b, 0, yM), verts(Math.max(2, Math.round(nb / 2)), 0, c, yM, h, bw)); } },
    { id: 10, name: 'Крест в балясинах', label: 'Крест в баля­синах', geo: function (c, h) { var a = 0.12 * c, b = 0.88 * c; return [[a, 0, a, h], [b, 0, b, h]].concat(cross(a, b, 0, h)); } }
  ];
  function ftype() { return TYPES.find(function (t) { return t.id === cur.type; }) || TYPES[0]; }
  function segLen(s) { return Math.hypot(s[2] - s[0], s[3] - s[1]); }
  /** Сколько заготовок длиной W уйдёт на детали (первая подходящая по убыванию длины). */
  function whips(lens, W) {
    var bins = [];
    var all = [];
    // деталь длиннее заготовки (горизонтальные в широком проёме) — целые заготовки и остаток, со стыком
    lens.forEach(function (l) { while (l > W + EPS) { bins.push(0); l -= W; } all.push(l); });
    all.sort(function (a, b) { return b - a; }).forEach(function (l) {
      var i = bins.findIndex(function (r) { return r >= l + KERF - EPS; });
      if (i < 0) bins.push(W - l - KERF); else bins[i] -= l + KERF;
    });
    return bins.length;
  }

  /* ---------- По схеме террасы (23.10.2026, пожелание менеджеров) ----------
     Формы — как в калькуляторе террасы (js/newdesign-terrace-calc.js, LtdCore.SHAPES): контур многоугольником по часовой
     стрелке от левого верхнего угла, x — по длине, y — по ширине вниз. Каждая отмеченная сторона — свой прямой участок со своими
     секциями; столб на углу между двумя отмеченными сторонами общий. Все стороны отмечены — контур замкнут, концевых столбов нет. */
  var SHAPES = {
    rect: { n: 'Прямо­угольник', ic: '<rect x="4" y="5" width="40" height="24"/>', poly: function (L, W) { return [[0, 0], [L, 0], [L, W], [0, W]]; } },
    lshape: { n: 'Г-образная', ic: '<path d="M4 5h26v10h14v14H4z"/>', cut: ['Вырез по длине a, м', 'Вырез по ширине b, м'], hint: 'Вырез в правом верхнем углу: a — по длине, b — по ширине.',
      poly: function (L, W, a, b) { return [[0, 0], [L - a, 0], [L - a, b], [L, b], [L, W], [0, W]]; } },
    ushape: { n: 'П-образная', ic: '<path d="M4 5h12v12h16V5h12v24H4z"/>', cut: ['Вырез по длине a, м', 'Вырез по ширине b, м'], hint: 'Вырез по центру верхней стороны: a — по длине, b — по ширине.',
      poly: function (L, W, a, b) { var s = (L - a) / 2; return [[0, 0], [s, 0], [s, b], [s + a, b], [s + a, 0], [L, 0], [L, W], [0, W]]; } },
    tshape: { n: 'Т-образная', ic: '<path d="M4 5h40v10H31v14H17V15H4z"/>', cut: ['Ширина ножки a, м', 'Глубина верха b, м'], hint: 'Верх на всю длину глубиной b, по центру ножка шириной a.',
      poly: function (L, W, a, b) { var s = (L - a) / 2; return [[0, 0], [L, 0], [L, b], [s + a, b], [s + a, W], [s, W], [s, b], [0, b]]; } },
    trap: { n: 'Трапеция', ic: '<path d="M14 5h20l10 24H4z"/>', cut: ['Верхняя сторона a, м'], hint: 'Длина — нижняя сторона, a — верхняя (по центру).',
      poly: function (L, W, a) { var s = (L - a) / 2; return [[s, 0], [s + a, 0], [L, W], [0, W]]; } },
    bevel: { n: 'Скошенный угол', ic: '<path d="M4 5h28l12 10v14H4z"/>', cut: ['Скос по длине a, м', 'Скос по ширине b, м'], hint: 'Скос в правом верхнем углу: a — по длине, b — по ширине.',
      poly: function (L, W, a, b) { return [[0, 0], [L - a, 0], [L, b], [L, W], [0, W]]; } }
  };
  var plan = { shape: 'rect', on: [] };
  function planMode() { var r = root.querySelector('input[name="lfcMode"]:checked'); return !!r && r.value === 'plan'; }
  /** Контур и стороны террасы; ошибка — { err, f } (поле формы). */
  function planGeo() {
    var sh = SHAPES[plan.shape], L = num($('lfcPL').value), W = num($('lfcPW').value), a = num($('lfcPa').value), b = num($('lfcPb').value), need = (sh.cut || []).length;
    if (!(L >= 0.5 && L <= 100)) return { err: 'От 0,5 до 100 м', f: 'PL' };
    if (!(W >= 0.5 && W <= 100)) return { err: 'От 0,5 до 100 м', f: 'PW' };
    if (need && !(a > 0)) return { err: 'Укажите размер', f: 'Pa' };
    if (need > 1 && !(b > 0)) return { err: 'Укажите размер', f: 'Pb' };
    if (need && a >= L - 0.2) return { err: 'Меньше длины хотя бы на 0,2 м', f: 'Pa' };
    if (need > 1 && b >= W - 0.2) return { err: 'Меньше ширины хотя бы на 0,2 м', f: 'Pb' };
    var pts = sh.poly(L, W, a, b), sides = pts.map(function (p, i) { var q = pts[(i + 1) % pts.length]; return { p: p, q: q, len: Math.hypot(q[0] - p[0], q[1] - p[1]) }; });
    if (plan.on.length !== sides.length) plan.on = sides.map(function () { return true; });
    var runs = [], open = 0, corners = [];
    sides.forEach(function (s, i) {
      var prev = (i + sides.length - 1) % sides.length;
      if (!plan.on[i]) return;
      runs.push(s.len);
      if (!plan.on[prev]) { open++; return; }
      // угол между двумя отмеченными сторонами — в вершине pts[i]: угол между перилами 0…180° (у внутреннего угла Г-формы тоже 90°)
      var a = sides[prev].p, b = pts[i], c = s.q, ax = a[0] - b[0], ay = a[1] - b[1], cx = c[0] - b[0], cy = c[1] - b[1];
      var deg = Math.acos(Math.max(-1, Math.min(1, (ax * cx + ay * cy) / (Math.hypot(ax, ay) * Math.hypot(cx, cy))))) * 180 / Math.PI;
      corners.push({ at: i, deg: Math.round(deg), turn: Math.abs(deg - 90) > 3 });
    });
    return { pts: pts, sides: sides, runs: runs, open: open, corners: corners, total: runs.reduce(function (t, x) { return t + x; }, 0), L: L, W: W };
  }
  function renderShapes() {
    $('lfcShapes').innerHTML = Object.keys(SHAPES).map(function (k) {
      return '<label lang="ru"><input type="radio" name="lfcShape" value="' + k + '"' + (k === plan.shape ? ' checked' : '') + '><span><svg viewBox="0 0 48 34" aria-hidden="true">' + SHAPES[k].ic + '</svg>' + SHAPES[k].n + '</span></label>';
    }).join('');
  }
  function renderPlanForm(P) {
    var sh = SHAPES[plan.shape], cut = sh.cut || [];
    $('lfcCutBox').hidden = !cut.length; $('lfcBField').style.display = cut.length > 1 ? '' : 'none';
    if (cut[0]) $('lfcALabel').textContent = cut[0]; if (cut[1]) $('lfcBLabel').textContent = cut[1];
    $('lfcCutHint').textContent = sh.hint || '';
    $('lfcSides').innerHTML = P && P.sides ? P.sides.map(function (s, i) {
      return '<label class="ltd-toggle' + (plan.on[i] ? ' is-on' : '') + '"><input type="checkbox" data-side="' + i + '"' + (plan.on[i] ? ' checked' : '') + '>Сторона ' + (i + 1) + ' — ' + fmt(s.len, 2) + ' м</label>';
    }).join('') : '';
  }
  /** План террасы: стороны с ограждением — цветом, без — пунктиром; столбы по расчёту; клик по стороне — включить/выключить. */
  function drawPlan(P, r) {
    var VW = 640, VH = 360, pad = 46, sc = Math.min((VW - 2 * pad) / P.L, (VH - 2 * pad) / P.W);
    var ox = (VW - P.L * sc) / 2, oy = (VH - P.W * sc) / 2, X = function (v) { return ox + v * sc; }, Y = function (v) { return oy + v * sc; };
    var cx = P.L / 2, cy = P.W / 2;
    var o = '<rect x="0" y="0" width="' + VW + '" height="' + VH + '" fill="var(--ltd-panel)"/>';
    o += '<polygon points="' + P.pts.map(function (p) { return X(p[0]) + ',' + Y(p[1]); }).join(' ') + '" fill="var(--ltd-board)" fill-opacity=".18"/>';
    var ri = 0;
    P.sides.forEach(function (s, i) {
      var on = plan.on[i], x1 = X(s.p[0]), y1 = Y(s.p[1]), x2 = X(s.q[0]), y2 = Y(s.q[1]);
      o += '<g class="lfc-side' + (on ? ' is-on' : '') + '" data-side="' + i + '"><title>Сторона ' + (i + 1) + (on ? ' — убрать ограждение' : ' — поставить ограждение') + '</title>' +
        '<line x1="' + x1 + '" y1="' + y1 + '" x2="' + x2 + '" y2="' + y2 + '" stroke="transparent" stroke-width="22"/>' +
        '<line x1="' + x1 + '" y1="' + y1 + '" x2="' + x2 + '" y2="' + y2 + '" stroke="' + (on ? 'var(--ltd-accent)' : 'var(--ltd-muted)') + '" stroke-width="' + (on ? 6 : 2) + '"' + (on ? '' : ' stroke-dasharray="6 5" opacity=".7"') + ' stroke-linecap="round"/></g>';
      // номер и длина снаружи контура
      var mx = (s.p[0] + s.q[0]) / 2, my = (s.p[1] + s.q[1]) / 2, nx = s.q[1] - s.p[1], ny = -(s.q[0] - s.p[0]), nl = Math.hypot(nx, ny) || 1;
      nx /= nl; ny /= nl; if ((mx - cx) * nx + (my - cy) * ny < 0) { nx = -nx; ny = -ny; }
      var tx = X(mx) + nx * 22, ty = Y(my) + ny * 22 + 4;
      o += '<text x="' + tx + '" y="' + ty + '" font-size="12" text-anchor="middle" fill="' + (on ? 'var(--ltd-ink)' : 'var(--ltd-muted)') + '" style="pointer-events:none"><tspan font-weight="700">' + (i + 1) + '</tspan> · ' + fmt(s.len, 2) + ' м</text>';
      // столбы отмеченной стороны: по секциям участка
      if (on && r && r.runs[ri]) {
        var u = r.runs[ri++];
        for (var k = 0; k <= u.spans; k++) { var t = k / u.spans, px = X(s.p[0] + (s.q[0] - s.p[0]) * t), py = Y(s.p[1] + (s.q[1] - s.p[1]) * t);
          o += '<rect x="' + (px - 4) + '" y="' + (py - 4) + '" width="8" height="8" rx="1.5" fill="var(--ltd-joist)" style="pointer-events:none"/>'; }
      }
    });
    // углы между отмеченными сторонами: угол между перилами; не 90° — цветом (там поворотный крепёж перил)
    P.corners.forEach(function (k) {
      var v = P.pts[k.at], dx = v[0] - cx, dy = v[1] - cy, dl = Math.hypot(dx, dy) || 1;
      o += '<text x="' + (X(v[0]) + dx / dl * 16) + '" y="' + (Y(v[1]) + dy / dl * 16 + 4) + '" font-size="11" text-anchor="middle" fill="' + (k.turn ? 'var(--ltd-accent)' : 'var(--ltd-muted)') + '"' + (k.turn ? ' font-weight="700"' : '') + ' style="pointer-events:none">' + k.deg + '°</text>';
    });
    $('lfcPlan').innerHTML = o;
  }

  /* ---------- Выбор деталей ---------- */
  var cur = { s: 0, type: 1, color: '', opt: {}, hSet: false }, excluded = {};
  function series() { return DATA.S[cur.s]; }
  /** Деталь серии нужного типа: сначала в цвет, потом без цвета, потом любая (цвет уточнит менеджер). */
  function pick(kind, f, brandWide) {
    var list = (series().p[kind] || []).filter(function (it) { return !f || f(it); });
    // нет в серии — у соседних серий той же марки (универсальное крепление балясин POLIVAN 24×24×30)
    if (!list.length && brandWide) DATA.S.forEach(function (S) { if (S.b === series().b && S !== series()) list = list.concat((S.p[kind] || []).filter(function (it) { return !f || f(it); })); });
    if (!list.length) return null;
    var c = ce(cur.color);
    return list.find(function (it) { return ce(it[4]) === c; }) || list.find(function (it) { return !it[4]; }) || Object.assign([], list[0], { guess: true });
  }
  function postSizes() {
    var out = [];
    (series().p.post || []).forEach(function (it) { if (!has(it, 'small') && out.indexOf(it[3]) < 0) out.push(it[3]); });
    return out.sort();
  }

  /* Варианты серии, из которых клиент выбирает: сечение столба, балясины, верхние перила, крышка */
  function variants() {
    var S = series(), v = [], sizes = postSizes();
    if (sizes.length > 1) v.push({ key: 'post', label: 'Столб', opts: sizes.map(function (z) { return [z, z.replace('х', '×') + ' мм']; }), def: S.id === 357 ? '125х125' : sizes[0] });
    if (rule(S).alt && (S.p.post || []).some(function (it) { return has(it, 'small'); }))
      v.push({ key: 'posts', label: 'Столбы', opts: [['alt', 'большие и малые через один'], ['big', 'только большие']], def: 'alt' });
    var bal = S.p.bal || [];
    if (ftype().vert && bal.some(function (it) { return has(it, 'alu'); }) && bal.some(function (it) { return !has(it, 'alu'); }))
      v.push({ key: 'bal', label: 'Балясины', opts: [['wpc', 'ДПК'], ['alu', 'алюминиевые']], def: 'wpc' });
    var rails = S.p.rail || [];
    if (rails.some(function (it) { return has(it, 'fig'); }) && rails.some(function (it) { return !has(it, 'fig'); }))
      v.push({ key: 'top', label: 'Верхние перила', opts: [['fig', 'фигурные'], ['rect', 'прямоугольные']], def: 'fig' });
    var caps = (S.p.cap || []).map(capFamily).filter(function (x, i, a) { return a.indexOf(x) === i; });
    if (caps.length > 1) v.push({ key: 'cap', label: 'Крышка столба', opts: caps.map(function (x) { return [x, x]; }), def: caps[0] });
    var ck = (S.p.capskirt || []).map(function (it) { return /алюмин/i.test(it[0]) ? 'алюминий' : 'ДПК'; }).filter(function (x, i, a) { return a.indexOf(x) === i; });
    if (ck.length > 1) v.push({ key: 'ck', label: 'Крышка и юбка', opts: ck.map(function (x) { return [x, x]; }), def: 'ДПК' });
    return v;
  }
  function capFamily(it) {
    var n = it[0];
    if (/подсветк/i.test(n)) return 'пирамида с подсветкой' + (/Террасвет/i.test(n) ? ' «Террасвет»' : '');
    if (/пирамид/i.test(n)) return 'пирамида';
    if (/сфер/i.test(n)) return 'сфера металлическая';
    if (/3D/i.test(n)) return 'крышка 3D ДПК';
    return 'крышка';
  }
  /* «По краям» (Ирина, 22.10.2026, вместо списка «Установка»; галочка «Юбки на столбы» рядом
     убрана 26.10.2026 — юбки теперь всегда):
     столбы ДПК или бетонные блоки/стены: тогда считаются проёмы между блоками,
     перила крепятся к блокам, а в широком проёме — промежуточные столбы ДПК по шагу. */
  function wallEnds() { return $('lfcEnds').value === 'walls'; }
  function opt(key) { var v = variants().find(function (x) { return x.key === key; }); return v ? (cur.opt[key] || v.def) : null; }

  /* ---------- Форма ---------- */
  function brands() { var b = []; DATA.S.forEach(function (S) { if (b.indexOf(S.b) < 0) b.push(S.b); }); return b; }
  function fillBrands() { $('lfcBrand').innerHTML = brands().map(function (b) { return '<option' + (b === series().b ? ' selected' : '') + '>' + esc(b) + '</option>'; }).join(''); }
  /* Серии марки — плитками, как подразделы в каталоге: картинка и название раздела из инфоблока (LFC_SECTIONS) */
  function secInfo(S) { return (window.LFC_SECTIONS || {})[S.id] || {}; }
  function seriesName(S) { return secInfo(S).n || S.n; }
  function renderList() {
    var b = series().b, html = '';
    DATA.S.forEach(function (S, i) {
      if (S.b !== b) return;
      var post = (S.p.post || [])[0], src = secInfo(S).img || (post && post[7]);
      html += '<label><input type="radio" name="lfcSeries" value="' + i + '"' + (i === cur.s ? ' checked' : '') + '><span>' +
        (src ? '<img src="' + esc(src) + '" alt="" loading="lazy">' : '<i></i>') + esc(seriesName(S)) + '</span></label>';
    });
    $('lfcList').innerHTML = html;
  }
  function renderTypes() {
    $('lfcTypes').innerHTML = TYPES.map(function (t) {
      return '<label><input type="radio" name="lfcType" value="' + t.id + '"' + (t.id === cur.type ? ' checked' : '') + '><span><img src="' + window.LFC_IMG + 'vid-' + t.id + '.jpg" alt="" loading="lazy">' + esc(t.label || t.name) + '</span></label>';
    }).join('');
  }
  function fillColors() {
    var S = series();
    if (S.c.map(ce).indexOf(ce(cur.color)) < 0) cur.color = S.c[0] || '';
    $('lfcColor').innerHTML = S.c.map(function (c) { return '<option' + (c === cur.color ? ' selected' : '') + '>' + esc(c) + '</option>'; }).join('');
  }
  function fillOpts() {
    $('lfcOpts').innerHTML = variants().map(function (v) {
      var val = cur.opt[v.key] || v.def;
      return '<label class="ltd-field"><span>' + v.label + '</span><select class="iks-ignore" data-opt="' + v.key + '">' +
        v.opts.map(function (o) { return '<option value="' + esc(o[0]) + '"' + (o[0] === val ? ' selected' : '') + '>' + esc(o[1]) + '</option>'; }).join('') + '</select></label>';
    }).join('');
  }
  function selectSeries(i) {
    cur.s = i; cur.opt = {}; excluded = {};
    var r = rule(series());
    $('lfcStep').value = r.stepDef || r.step;
    if (!cur.hSet || !(num($('lfcH').value) > 0)) $('lfcH').value = Math.round(r.h * 1000); // свою высоту клиента при смене серии не сбрасываем
    fillBrands(); renderList(); fillColors(); fillOpts(); render();
  }

  /* ---------- Расчёт ---------- */
  function calc() {
    var S = series(), R = rule(S);
    var L = num($('lfcL').value), step = num($('lfcStep').value), H = num($('lfcH').value) / 1000, T = ftype();
    var pm = planMode(), P = pm ? planGeo() : null;
    // юбки на столбы ставим всегда (Ирина, 26.10.2026: галочку «Юбки на столбы» убрали)
    var skirtOn = true, noPosts = !pm && wallEnds(), nOpen = Math.round(num($('lfcN').value));
    var bad = {};
    if (pm) {
      // по схеме террасы: длина — сумма отмеченных сторон, по краям всегда столбы ДПК
      if (P.err) bad[P.f] = P.err;
      else if (!P.runs.length) bad.sides = 'Отметьте хотя бы одну сторону';
      else L = P.total;
    } else if (noPosts) {
      // L — ширина одного проёма в свету, проёмов nOpen
      if (!(L >= 0.3 && L <= 100)) bad.L = 'От 0,3 до 100 м';
      if (!(nOpen >= 1 && nOpen <= 100)) bad.N = 'От 1 до 100';
    } else if (!(L >= 0.5 && L <= 500)) bad.L = 'От 0,5 до 500 м';
    if (!(step >= 0.5)) bad.step = 'Не меньше 0,5 м';
    else if (step > R.step + EPS) bad.step = 'Не больше ' + fmt(R.step) + ' м для этой серии';
    var size = opt('post') || postSizes()[0];
    var post = pick('post', function (it) { return !has(it, 'small') && (!size || it[3] === size); });
    var postW = (post && post[2] || 3000) / 1000;
    if (!(H >= 0.6 && H <= 1.5)) bad.H = 'От 600 до 1500 мм';
    else if (H > postW + EPS && !noPosts) bad.H = 'Не больше ' + fmt(postW * 1000) + ' мм — это длина столба серии';
    if (Object.keys(bad).length) return { bad: bad };
    // Террапол Классик: по инструкции большие и малые через один; можно выбрать «только большие»
    var small = !noPosts && R.alt && opt('posts') !== 'big' ? pick('post', function (it) { return has(it, 'small'); }) : null;
    var pw = sec(post) || 0.1;

    // секции: каждый прямой участок делим поровну с шагом не больше заданного; участок один (длина в пог. м)
    // или по стороне на каждую отмеченную сторону террасы. runs — участки: len, spans, axis, c (просвет секции)
    var runs = [], k = 1, spans, N, nSmall = 0;
    if (noPosts) {
      // проём между блоками: узкий — одна секция во всю ширину без столбов ДПК; шире шага серии —
      // внутри промежуточные столбы ДПК, k секций по ≤ шага (Ирина, 22.10.2026: на 6 м без столба нельзя)
      k = Math.max(1, ceil(L / step)); spans = nOpen * k;
      var cw = (L - (k - 1) * pw) / k;
      runs.push({ len: L, spans: spans, c: cw, axis: cw + pw });
      N = nOpen * (k - 1);
    } else {
      (pm ? P.runs : [L]).forEach(function (len) {
        var sp = Math.max(1, ceil(len / step));
        if (R.alt && small && sp % 2) sp++;            // Террапол: по краям и на углах большие, значит секций чётное число
        runs.push({ len: len, spans: sp, axis: len / sp, c: Math.max(0.05, len / sp - pw) });
      });
      spans = runs.reduce(function (t, u) { return t + u.spans; }, 0);
      // столбов: по одному на секцию и ещё по одному в начале каждой цепочки сторон; замкнутый контур — без концевых
      N = spans + (pm ? P.open : 1);
      if (R.alt && small) nSmall = runs.reduce(function (t, u) { return t + Math.floor(u.spans / 2); }, 0);
    }
    var big = N - nSmall;
    // главный участок — самый длинный: его секции на схеме и в подписях
    var main = runs.reduce(function (a, u) { return u.len > a.len + EPS ? u : a; }, runs[0]), axis = main.axis, c = main.c;
    // сколько деталей длиной len выходит из заготовки W (м)
    function fit(W, len) { return Math.max(1, Math.floor((W + KERF) / (len + KERF) + EPS)); }
    // столбов из заготовки — по высоте; если по правилам серии из заготовки выходит меньше, чем по длине
    // (Террапол: 2 из 3050 мм), правило остаётся потолком, а выше нормы столбов выходит меньше
    function postsPer(W, len, len0) { var g = fit(W, len); return fit(W, len0) > R.postPer ? Math.min(g, R.postPer) : g; }
    var postPer = postsPer(postW, H, R.h);

    // алюминиевые балясины — только у вертикальных видов; у EasyDecking Вуд-Икс других в серии нет
    var alu = !!T.vert && opt('bal') === 'alu';
    var bal = T.vert ? pick('bal', function (it) { return alu ? has(it, 'alu') : !has(it, 'alu'); }) || pick('bal') : pick('bal', function (it) { return !has(it, 'alu'); });
    if (bal && has(bal, 'alu')) alu = true;
    var bw = alu ? 0.02 : (sec(bal) || 0.05);
    var PER = noPosts && k === 1 ? 'на проём' : 'на секцию';
    // высота заполнения: от верха нижних перил до низа верхних (у Террапола ещё подбалясенник под перилами)
    var railH = Math.max(0.035, sec(pick('rail'), 1) || 0.045), botY = R.perM ? 0.11 : 0.1, topY = H - 0.06;
    var y0 = botY + railH, y1 = topY - railH - (R.rails.some(function (rl) { return rl[0] === 'bottom'; }) ? 0.035 : 0);
    // на каждом участке своя ширина секции: балясины, подпорки и детали заполнения — по ней.
    // Подпорки под нижними перилами — по правилу серии, но не реже чем через SUP_GAP по длине секции:
    // в широком проёме (до 6 м без столбов) одной-двух мало (Ирина, 22.10.2026)
    runs.forEach(function (u) {
      u.nb = R.perM ? Math.max(1, ceil(u.c * (alu && R.perMAlu ? R.perMAlu : R.perM) - 0.05)) : Math.max(1, ceil((u.c - R.gap) / (bw + R.gap)));
      u.nsup = R.sup ? Math.max(R.sup(u.c), ceil(u.c / SUP_GAP) - 1) : 0;
      u.segs = T.geo(u.c, y1 - y0, u.nb, bw);
    });
    var nb = main.nb, nsup = main.nsup, segs = main.segs;
    /** Значение на секцию по участкам: одно число или «от–до». */
    function rng(f, d) { var v = runs.map(f), a = Math.min.apply(null, v), b = Math.max.apply(null, v); return Math.abs(a - b) < 1e-6 ? fmt(a, d) : fmt(a, d) + '–' + fmt(b, d); }
    var cTxt = rng(function (u) { return Math.round(u.c * 1000); }, 0);
    // все отрезки (секции) с их просветами — для раскроя перил
    var cuts = []; runs.forEach(function (u) { for (var s = 0; s < u.spans; s++) cuts.push(u.c); });
    var r = { S: S, R: R, T: T, L: L, H: H, skirtOn: skirtOn, k: k, nOpen: nOpen, noPosts: noPosts, step: step, spans: spans, axis: axis, c: c, N: N, big: big, small: nSmall, pw: pw,
      nb: nb, nsup: nsup, alu: alu, bw: bw, size: size, railH: railH, botY: botY, topY: topY, y0: y0, y1: y1, segs: segs, rows: [],
      pm: pm, P: P, runs: runs, main: main, cTxt: cTxt, nbTxt: rng(function (u) { return u.nb; }), segTxt: rng(function (u) { return u.segs.length; }) };

    function row(key, it, qty, sub, fallback) {
      if (!qty) return;
      r.rows.push({ key: key, id: it && it[8] || 0, name: it ? link(it) : esc(fallback), text: it ? it[0] : fallback, qty: qty, unit: 'шт',
        price: it && it[1] ? it[1] : null, sub: sub + (it && it.guess ? ' · цвет уточнит менеджер' : '') });
    }
    function cut(len, piece, per) { return per || Math.max(1, Math.floor((len / 1000 + KERF) / (piece + KERF))); }
    // сколько заготовок длиной len (мм) на n отрезков по piece (м): короче заготовки — по k из одной,
    // длиннее — каждый собирается из нескольких со стыком над подпоркой
    function railPcs(len, piece, n) { var W = len / 1000; return piece > W + EPS ? n * ceil(piece / W) : ceil(n / cut(len, piece)); }
    // один ряд перил на все секции: участок один — как раньше, несколько — общий раскрой заготовок (обрезки идут в дело)
    function railsFor(it) { return !it || !it[2] ? spans : runs.length === 1 ? railPcs(it[2], c, spans) : whips(cuts, it[2] / 1000); }
    function railNote(it, what) { return runs.length === 1 ? railTxt(it, c, what) : ' — раскрой из ' + (what === 'подбалясенника' ? 'подбалясенников' : 'перилин') + mm(it) + ' на все стороны сразу, с учётом обрезков'; }
    function railTxt(it, piece, what) {
      var W = it && it[2] ? it[2] / 1000 : 0;
      return W && piece > W + EPS ? ' — длиннее ' + what + mm(it) + ', каждый собирается из ' + ceil(piece / W) + ' со стыком' : ' — из ' + (what === 'подбалясенника' ? 'одного подбалясенника' : 'одной перилины') + mm(it) + ' выходит ' + (W ? cut(it[2], piece) : 1);
    }

    // столбы
    row('post', post, ceil(big / postPer), 'Нужно ' + fmt(big) + ' ' + (R.alt && small ? plural(big, 'большой столб', 'больших столба', 'больших столбов') : plural(big, 'столб', 'столба', 'столбов')) + ' по ' + fmt(Math.round(H * 1000)) + ' мм — из одного столба' + mm(post) + ' выходит ' + postPer);
    if (small && nSmall) {
      var smallPer = postsPer((small[2] || 3000) / 1000, topY - railH, R.h - 0.06 - railH);
      row('postS', small, ceil(nSmall / smallPer), 'Нужно ' + fmt(nSmall) + ' ' + plural(nSmall, 'малый столб', 'малых столба', 'малых столбов') + ' — из одного столба' + mm(small) + ' выходит ' + smallPer + ', из остатков — подпорки под подбалясенник');
    }

    // перила, поручни, подбалясенники
    var top = opt('top');
    R.rails.forEach(function (rl, i) {
      var kind = rl[0], rows = rl[1], f = null;
      if (rl[2] === 'top') f = function (it) { return top === 'fig' ? has(it, 'fig') : !has(it, 'fig'); };
      if (rl[2] === 'bottom') f = function (it) { return !has(it, 'fig'); };
      var it = pick(kind, f);
      var what = kind === 'bottom' ? 'подбалясенник' : rl[2] === 'bottom' ? 'нижние перила' : rl[2] === 'top' ? 'верхние перила' : rows === 2 ? 'верх и низ' : 'верх';
      row('rail' + i, it, rows * railsFor(it), 'Нужно ' + rows * spans + ' ' + plural(rows * spans, 'отрезок', 'отрезка', 'отрезков') + ' по ' + cTxt + ' мм (' + what + ')' + railNote(it, kind === 'bottom' ? 'подбалясенника' : 'перилины'));
    });
    // двойные перила: ещё один ряд прямоугольных перил посередине
    if (T.mid) {
      var mr = pick('rail', function (it) { return !has(it, 'fig'); }) || pick('rail');
      row('railMid', mr, railsFor(mr), 'Нужно ' + spans + ' ' + plural(spans, 'отрезок', 'отрезка', 'отрезков') + ' по ' + cTxt + ' мм (средние перила)' + railNote(mr, 'перилины'));
    }

    // балясины и подпорки
    var sumRuns = function (f) { return runs.reduce(function (t, u) { return t + f(u) * u.spans; }, 0); };
    var nbAll = sumRuns(function (u) { return u.nb; }), supAll = sumRuns(function (u) { return u.nsup; });
    if (T.id !== 1) {
      // другие виды: детали заполнения режем из балясин серии, раскрой — по длинам деталей
      var nSeg = r.segTxt, segAll = sumRuns(function (u) { return u.segs.length; }), what2 = 'Нужно ' + fmt(segAll) + ' ' + plural(segAll, 'деталь', 'детали', 'деталей') + ' (' + nSeg + ' ' + PER + ')';
      if (bal && alu) row('bal', bal, segAll, what2);
      else if (bal) {
        var W = (bal[2] || 2900) / 1000, lens = [];
        runs.forEach(function (u) { for (var s = 0; s < u.spans; s++) u.segs.forEach(function (g) { lens.push(segLen(g)); }); });
        var supW = R.supLen && supAll;
        if (supW) for (var t = 0; t < supAll; t++) lens.push(R.supLen);
        row('bal', bal, whips(lens, W), 'Нужно ' + fmt(segAll) + ' ' + plural(segAll, 'деталь', 'детали', 'деталей') + ' (' + nSeg + ' ' + PER + ')' + (supW ? ' и ' + fmt(supAll) + ' ' + plural(supAll, 'подпорка', 'подпорки', 'подпорок') : '') + ' — режутся из балясин ' + fmt(W * 1000) + ' мм');
      } else row('bal', null, segAll, what2 + ' — в этой серии только алюминиевые балясины 750 мм, для такого вида деталь подберёт менеджер', 'Детали заполнения «' + T.name + '»');
      if (R.balFix) {
        var bf2 = pick('balfix', function (it) { return (alu ? has(it, 'alu') : !has(it, 'alu')) && !has(it, 'turn'); }, true);
        var qf2 = segAll * R.balFix + (R.perM ? supAll * (alu ? 2 : 1) : 0);
        if (bf2) row('balfix', bf2, qf2, R.balFix + ' на деталь' + (R.perM ? ' и на подпорки' : '') + ', ' + Math.round(qf2 / spans) + ' ' + PER);
        else row('balfix', null, qf2, 'в каталоге нет — подберёт менеджер', 'Крепление деталей заполнения');
      }
    } else if (bal) {
      if (alu) row('bal', bal, nbAll, 'Нужно ' + fmt(nbAll) + ' ' + plural(nbAll, 'балясина', 'балясины', 'балясин') + ' (' + r.nbTxt + ' ' + PER + ')' + (supAll ? ', подпорки — из обрезков' : '') + (Math.abs(H - R.h) >= 0.001 ? ' · балясины готовой длины' + mm(bal) + ', подойдут ли к высоте ' + fmt(Math.round(H * 1000)) + ' мм — уточнит менеджер' : ''));
      else {
        // балясин из заготовки — по высоте заполнения (при высоте серии по умолчанию — balPer из правил)
        var BW = (bal[2] || 2900) / 1000, per = Math.abs(H - R.h) < 0.001 ? R.balPer : fit(BW, y1 - y0), balWh = ceil(nbAll / per), supWhips = 0, fromLeft = 0;
        if (R.supLen && supAll) {
          // POLIVAN — по инструкции из отдельных балясин; остальные — сначала из обрезков
          fromLeft = R.perM ? 0 : balWh * Math.max(0, Math.floor((BW - per * (y1 - y0 + KERF) + KERF) / (R.supLen + KERF) + EPS));
          supWhips = ceil(Math.max(0, supAll - fromLeft) / Math.floor((BW + KERF) / (R.supLen + KERF)));
        }
        row('bal', bal, balWh + supWhips, 'Нужно ' + fmt(nbAll) + ' ' + plural(nbAll, 'балясина', 'балясины', 'балясин') + ' (' + r.nbTxt + ' ' + PER + ') — из одной балясины' + mm(bal) + ' выходит ' + per +
          (R.supLen && supAll ? '; ещё ' + fmt(supAll) + ' ' + plural(supAll, 'подпорка', 'подпорки', 'подпорок') + ' по ' + fmt(R.supLen * 1000) + ' мм' + (supWhips ? (fromLeft ? ' — из обрезков, не хватает — ещё ' + supWhips + ' ' + plural(supWhips, 'балясина', 'балясины', 'балясин') : '') : ' — из обрезков') : ''));
      }
    }
    // крепёж балясин: по 2 на балясину, у POLIVAN ещё на подпорки (у алюминиевых — по 2)
    if (T.id === 1 && R.balFix) {
      var bf = pick('balfix', function (it) { return (alu ? has(it, 'alu') : !has(it, 'alu')) && !has(it, 'turn'); }, true);
      var qf = nbAll * R.balFix + (R.perM ? supAll * (alu ? 2 : 1) : 0);
      if (bf) row('balfix', bf, qf, R.balFix + ' на балясину' + (R.perM ? ' и на подпорки' : '') + ', ' + Math.round(qf / spans) + ' ' + PER);
      else row('balfix', null, qf, 'в каталоге нет — подберёт менеджер', alu ? 'Крепление для алюминиевых балясин' : 'Крепление для балясин');
    }
    // крепёж перил: на каждый конец каждого ряда
    var railRows = R.rails.reduce(function (t, rl) { return t + (rl[0] === 'rail' ? rl[1] : 0); }, 0) + (T.mid ? 1 : 0);
    // углы не под 90° (трапеция, скос): на угловом столбе оба конца каждого ряда перил — на поворотном крепеже;
    // у серий без поворотного крепежа в каталоге — строкой без цены
    var turnN = pm ? P.corners.filter(function (k) { return k.turn; }).length : 0, turnEnds = turnN * 2 * railRows;
    var rft = turnN && S.p.railfix ? pick('railfix', function (it) { return has(it, 'turn'); }) : null;
    if (R.railFix && S.p.railfix) {
      var rf = pick('railfix', function (it) { return !has(it, 'turn'); });
      row('railfix', rf, spans * railRows * 2 - (rft ? turnEnds : 0), '2 на каждый поручень секции, прямые' + (rft ? ' (кроме концов на углах не под 90°)' : ''));
      if (rft) row('railfixT', rft, turnEnds, '2 на каждый ряд перил на угловом столбе: ' + turnN + ' ' + plural(turnN, 'угол', 'угла', 'углов') + ' не под 90°');
    }
    if (turnN && !rft) row('miss-turn', null, turnEnds, 'на ' + turnN + ' ' + plural(turnN, 'угле', 'углах', 'углах') + ' не под 90° перила подходят к столбу под углом — поворотного крепежа у серии в каталоге нет, подберёт менеджер', 'Крепёж перил под углом');
    // крышки и юбки (Террапол Классик: крышка только на большие столбы)
    var ck = opt('ck'), capKind = opt('cap');
    if (S.p.capskirt && !S.p.cap) {
      row('capskirt', pick('capskirt', function (it) { return (!ck || (ck === 'алюминий') === /алюмин/i.test(it[0])) && (!size || it[3] === size || !(series().p.capskirt || []).some(function (x) { return x[3] === size; })); }), N, '1 на столб');
    } else {
      var szOk = function (it) { return !size || it[3] === size || !(series().p.cap || []).some(function (x) { return x[3] === size; }); };
      var cap = pick('cap', function (it) { return (!capKind || capFamily(it) === capKind) && szOk(it); });
      row('cap', cap, big, '1 на ' + (R.alt && small ? 'большой ' : '') + 'столб');
      if (R.alt && small) {
        row('skirt', pick('skirt', function (it) { return has(it, 'big'); }), big, 'рамка на большой столб');
        row('skirtS', pick('skirt', function (it) { return has(it, 'small'); }), nSmall, 'рамка на малый столб');
      } else {
        var skOk = function (it) { return !size || it[3] === size || !(series().p.skirt || []).some(function (x) { return x[3] === size; }); };
        row('skirt', pick('skirt', function (it) { return !has(it, 'small') && skOk(it); }), N, '1 на столб');
      }
    }
    // крепление столбов: кронштейн, закладная или опора серии; к столбу 120×120 без своего — общий из каталога
    var mount = pick('mount', function (it) { return !size || it[3] === size || sec(it) <= pw; });
    if (size === '120х120' && mount && /кронштейн/i.test(mount[0]) && mount[3] !== '120х120') mount = DATA.common.find(function (it) { return /кронштейн/i.test(it[0]); }) || mount;
    row('mount', mount, N, '1 на столб' + (mount && /кронштейн/i.test(mount[0]) ? (noPosts ? ', крепится к основанию анкерами' : ', крепится к основанию террасы, не к доске') : ''));
    // чего нет в каталоге — строкой без цены
    Object.keys(R.missing || {}).forEach(function (k) { var m = R.missing[k]; row('miss-' + k, null, k === 'railfix' ? spans * railRows * 2 - (turnN && !rft ? turnEnds : 0) : spans, m[1], m[0]); });

    r.rows.forEach(function (x) { x.sum = x.price != null ? x.qty * x.price : null; });
    return r;
  }

  /* ---------- Схема секций ---------- */
  function draw(r) {
    // по схеме террасы показываем секции самой длинной из отмеченных сторон
    var VW = 640, VH = 300, show = r.noPosts ? Math.min(r.k, 6) : Math.min(r.main.spans, 4), H = r.H, padL = 30, padR = 20, padT = 46, padB = 52;
    var widthM = show * r.axis + r.pw, sc = Math.min((VW - padL - padR) / widthM, (VH - padT - padB) / H);
    var x0 = padL + (VW - padL - padR - widthM * sc) / 2, yB = VH - padB, X = function (m) { return x0 + m * sc; }, Y = function (m) { return yB - m * sc; };
    var o = '<rect x="0" y="0" width="' + VW + '" height="' + VH + '" fill="var(--ltd-panel)"/>';
    o += '<line x1="' + (x0 - 12) + '" y1="' + yB + '" x2="' + (X(widthM) + 12) + '" y2="' + yB + '" stroke="var(--ltd-muted)" stroke-width="1.5"/>';
    var railH = r.railH, botY = r.botY, topY = r.topY;
    for (var i = 0; i < show; i++) {
      var a = i * r.axis + r.pw, b = a + r.c;
      // заполнение секции по виду: детали — линиями толщиной в балясину, обрезаны по просвету секции
      o += '<clipPath id="lfcClip' + i + '"><rect x="' + X(a) + '" y="' + Y(r.y1) + '" width="' + (r.c * sc) + '" height="' + ((r.y1 - r.y0) * sc) + '"/></clipPath><g clip-path="url(#lfcClip' + i + ')">';
      r.segs.forEach(function (s) { o += '<line x1="' + X(a + s[0]) + '" y1="' + Y(r.y0 + s[1]) + '" x2="' + X(a + s[2]) + '" y2="' + Y(r.y0 + s[3]) + '" stroke="var(--ltd-board-2)" stroke-width="' + Math.max(1.2, r.bw * sc) + '"/>'; });
      o += '</g>';
      // нижний и верхний ряды перил (и средний у двойных перил)
      [botY, topY - railH].concat(r.T.mid ? [r.y0 + (r.y1 - r.y0) - r.T.mid - railH / 2] : []).forEach(function (q) { o += '<rect x="' + X(a) + '" y="' + Y(q + railH) + '" width="' + (r.c * sc) + '" height="' + (railH * sc) + '" fill="var(--ltd-board)"/>'; });
      if (r.R.rails.some(function (rl) { return rl[0] === 'bottom'; })) o += '<rect x="' + X(a) + '" y="' + Y(topY - railH) + '" width="' + (r.c * sc) + '" height="' + (0.035 * sc) + '" fill="var(--ltd-board-2)"/>';
      // подпорки под нижним рядом
      for (var k = 0; k < r.nsup; k++) { var sx = a + r.c * (k + 1) / (r.nsup + 1) - 0.02; o += '<rect x="' + X(sx) + '" y="' + Y(botY) + '" width="' + (0.04 * sc) + '" height="' + (botY * sc) + '" fill="var(--ltd-board-2)"/>'; }
      // размер секции
      var my = Y(H) - 14;
      o += '<path d="M' + X(a) + ' ' + my + 'H' + X(b) + 'M' + X(a) + ' ' + (my - 4) + 'v8M' + X(b) + ' ' + (my - 4) + 'v8" stroke="var(--ltd-muted)" stroke-width="1"/>' +
        '<text x="' + X((a + b) / 2) + '" y="' + (my - 5) + '" fill="var(--ltd-ink)" font-size="11" text-anchor="middle">' + fmt(Math.round(r.c * 1000)) + ' мм</text>';
    }
    // столбы (у Террапола Классик через один малые — ниже, под перила)
    for (var p = 0; p <= show; p++) {
      // проём без столбов: по краям бетонные блоки или стены
      if (r.noPosts && (p === 0 || p === r.k)) { o += '<rect x="' + X(p * r.axis) + '" y="' + Y(H + 0.1) + '" width="' + (r.pw * sc) + '" height="' + ((H + 0.1) * sc) + '" fill="var(--ltd-muted)" opacity=".45"/>'; continue; }
      var isSmall = r.R.alt && r.small && p % 2 === 1, px = p * r.axis, w = isSmall ? 0.07 : r.pw, ph = isSmall ? topY - railH : H;
      o += '<rect x="' + X(px + (r.pw - w) / 2) + '" y="' + Y(ph) + '" width="' + (w * sc) + '" height="' + (ph * sc) + '" fill="var(--ltd-joist)"/>';
      if (!isSmall) o += '<rect x="' + (X(px) - 3) + '" y="' + (Y(H) - 5) + '" width="' + (r.pw * sc + 6) + '" height="6" rx="1.5" fill="var(--ltd-joist)"/>';
      // юбка у основания столба — шире столба, закрывает крепление (ставится на каждый столб)
      // высота как у настоящей юбки (~50 мм) и не выше половины просвета под нижними перилами
      var skH = Math.max(4, Math.min(0.05, botY * 0.5) * sc), skX = X(px + (r.pw - w) / 2) - 3;
      o += '<rect x="' + skX + '" y="' + (yB - skH) + '" width="' + (w * sc + 6) + '" height="' + skH + '" rx="1" fill="var(--ltd-joist)" fill-opacity=".55" stroke="var(--ltd-joist)" stroke-width="1"/>';
    }
    // шаг по осям под схемой
    var ay = yB + 18;
    if (!r.noPosts) o += '<path d="M' + X(r.pw / 2) + ' ' + ay + 'H' + X(r.axis + r.pw / 2) + 'M' + X(r.pw / 2) + ' ' + (ay - 4) + 'v8M' + X(r.axis + r.pw / 2) + ' ' + (ay - 4) + 'v8" stroke="var(--ltd-accent)" stroke-width="1.5"/>' +
      '<text x="' + X(r.axis / 2 + r.pw / 2) + '" y="' + (ay + 14) + '" fill="var(--ltd-accent)" font-size="11" text-anchor="middle">шаг столбов ' + fmt(Math.round(r.axis * 1000)) + ' мм</text>';
    o += '<text x="' + (x0 - 8) + '" y="' + Y(H / 2) + '" fill="var(--ltd-muted)" font-size="11" text-anchor="middle" transform="rotate(-90 ' + (x0 - 8) + ' ' + Y(H / 2) + ')">≈ ' + fmt(Math.round(H * 1000)) + ' мм</text>';
    $('lfcSvg').innerHTML = o;
    $('lfcCap').textContent = r.noPosts ? (show < r.k ? 'показаны ' + show + ' из ' + r.k + ' секций проёма' : r.nOpen > 1 ? 'показан 1 из ' + r.nOpen + ' проёмов' : '') : r.pm ? 'сторона ' + (r.P.sides.indexOf(r.P.sides.filter(function (s, i) { return plan.on[i]; })[r.runs.indexOf(r.main)]) + 1) + ': ' + (show < r.main.spans ? 'показаны ' + show + ' из ' + r.main.spans + ' секций' : r.main.spans + ' ' + plural(r.main.spans, 'секция', 'секции', 'секций'))
      : show < r.spans ? 'показаны ' + show + ' из ' + r.spans + ' секций' : '';
    $('lfcLegendNote').textContent = r.nsup ? 'подпорки: ' + r.nsup + ' ' + (r.noPosts ? 'на проём' : 'на секцию') : '';
  }

  /* ---------- Вывод ---------- */
  var last = null;
  function render() {
    root.querySelectorAll('.ltd-field[data-f]').forEach(function (f) { f.classList.remove('is-bad'); var e = f.querySelector('.ltd-err'); if (e) e.textContent = ''; });
    var R = rule(series());
    // по схеме террасы: вместо длины и «По краям» — форма, размеры и стороны; по краям всегда столбы ДПК
    var pm = planMode(), P = pm ? planGeo() : null;
    $('lfcPlanBox').hidden = !pm; $('lfcPlanFig').hidden = !pm;
    $('lfcEndsField').style.display = pm ? 'none' : '';
    root.querySelector('.ltd-field[data-f="L"]').style.display = pm ? 'none' : '';
    if (pm) renderPlanForm(P);
    var np = !pm && wallEnds();
    $('lfcNField').style.display = np ? '' : 'none';
    $('lfcLLabel').textContent = np ? 'Ширина проёма, м' : 'Длина ограждения, пог. м';
    $('lfcStepHint').textContent = np ? 'Ширина проёма — в свету, между бетонными блоками или стенами. Перила крепятся к блокам; если проём шире шага, внутри ставятся столбы ДПК. Шаг — не больше ' + fmt(R.step) + ' м для этой серии. Высота — от основания до верха перил, у этой серии обычно ' + fmt(Math.round(R.h * 1000)) + ' мм.' : 'Шаг — по осям столбов, не больше ' + fmt(R.step) + ' м для этой серии' + (R.stepDef ? ', рекомендуемый ' + fmt(R.stepDef) + ' м' : '') + '. Калькулятор делит длину на равные секции. Высота — от основания до верха перил, у этой серии обычно ' + fmt(Math.round(R.h * 1000)) + ' мм.';
    var r = calc(), out = $('lfcOut');
    if (pm && P && !P.err) drawPlan(P, r.bad ? null : r);
    if (r.bad) {
      Object.keys(r.bad).forEach(function (k) { var f = root.querySelector('.ltd-field[data-f="' + k + '"]'); if (f) { f.classList.add('is-bad'); f.querySelector('.ltd-err').textContent = r.bad[k]; } });
      out.classList.add('is-invalid'); $('lfcInvalid').hidden = false; $('lfcInvalid').textContent = 'Проверьте поля, отмеченные красным. Результат пересчитается автоматически.';
      return;
    }
    out.classList.remove('is-invalid'); $('lfcInvalid').hidden = true;
    var S = series();
    $('lfcSeriesInfo').innerHTML = '<a href="' + SITE + S.url + '" target="_blank" rel="noopener">Все товары серии в каталоге</a>';
    var fill = (r.T.id === 1 ? 'балясин ' + r.nbTxt : 'деталей заполнения ' + r.segTxt);
    $('lfcBig').innerHTML = r.noPosts ? fmt(r.nOpen) + '<small>' + plural(r.nOpen, 'проём', 'проёма', 'проёмов') + ' по ' + fmt(Math.round(r.L * 1000)) + ' мм</small>'
      : r.pm ? fmt(r.L, 2) + '<small>пог. м · ' + r.spans + ' ' + plural(r.spans, 'секция', 'секции', 'секций') + ' по ' + r.cTxt + ' мм</small>'
      : fmt(r.spans) + '<small>' + plural(r.spans, 'секция', 'секции', 'секций') + ' по ' + fmt(Math.round(r.c * 1000)) + ' мм</small>';
    $('lfcBigSub').innerHTML = r.noPosts ? (r.k > 1 ? 'Промежуточных столбов ДПК <b>' + (r.k - 1) + '</b> на проём · ' + r.k + ' ' + plural(r.k, 'секция', 'секции', 'секций') + ' по ' + fmt(Math.round(r.c * 1000)) + ' мм · ' + fill + ' на секцию' : 'Без столбов ДПК · ' + fill + ' на проём')
      : 'Столбов <b>' + r.N + '</b>' + (r.small ? ' (' + r.big + ' больших, ' + r.small + ' малых)' : '') + (r.pm ? ' · ' + r.runs.length + ' ' + plural(r.runs.length, 'сторона', 'стороны', 'сторон') + (r.P.open ? '' : ', контур замкнут') + (r.P.corners.length ? ' · ' + plural(r.P.corners.length, 'угол', 'угла', 'углов').replace(/^/, r.P.corners.length + ' ') + (r.P.corners.some(function (k) { return k.turn; }) ? ', не под 90° — ' + r.P.corners.filter(function (k) { return k.turn; }).length : '') : '') : ' · шаг ' + fmt(Math.round(r.axis * 1000)) + ' мм') + ' · ' + fill + ' на секцию';
    $('lfcRules').innerHTML = '<b>Как считаем ' + esc(seriesName(S)) + '</b>' + esc(r.R.note || '') +
      (r.pm ? ' По схеме террасы: каждая отмеченная сторона делится на равные секции с шагом не больше заданного; на углах между отмеченными сторонами — один общий столб' + (r.P.open ? ', на свободных концах — свои столбы' : '') + '. На углах 90° перила подходят к соседним граням углового столба — крепёж обычный; на углах не под 90° концы перил — на поворотном крепеже серии (нет в каталоге — подберёт менеджер).' : '') +
      (r.noPosts ? ' Установка в проём между бетонными блоками или стенами: перила крепятся к блокам — анкеры и уголки подберёт менеджер.' + (r.k > 1 ? ' Проём шире ' + fmt(r.R.step) + ' м, поэтому внутри ' + (r.k - 1) + ' ' + plural(r.k - 1, 'промежуточный столб', 'промежуточных столба', 'промежуточных столбов') + ' ДПК: ' + r.k + ' ' + plural(r.k, 'секция', 'секции', 'секций') + ' по ' + fmt(Math.round(r.c * 1000)) + ' мм, столбы крепятся к основанию, на каждом — юбка.' : ' Столбов ДПК, крышек, юбок и креплений столбов в смете нет.') : '') +
      (Math.abs(r.H - r.R.h) >= 0.001 ? ' Высота задана своя — ' + fmt(Math.round(r.H * 1000)) + ' мм вместо ' + fmt(Math.round(r.R.h * 1000)) + ' мм по правилам серии: длины столбов и балясин пересчитаны под неё, проверьте с менеджером.' : '') +
      (r.R.alt && !r.small && !r.noPosts ? ' Выбраны только большие столбы: малые не ставятся, подпорки под нижний подбалясенник режутся из обрезков.' : '') +
      (r.T.id !== 1 ? ' Вид «' + esc(r.T.name) + '»: детали заполнения нарезаются из балясин серии, сколько балясин купить — посчитано с учётом распила.' : '');
    draw(r);
    var total = 0, priced = 0;
    $('lfcTable').innerHTML = r.rows.map(function (x) {
      x.off = !!excluded[x.key];
      if (x.sum != null && !x.off) { total += x.sum; priced++; }
      return '<tr' + (x.off ? ' class="is-off"' : '') + '><th scope="row"><div class="ltd-rowname"><input type="checkbox" class="ltd-on" data-key="' + x.key + '"' + (x.off ? '' : ' checked') + ' aria-label="Включить в смету"><div>' + x.name + (x.id ? ' <span class="ltd-art">арт. ' + esc(x.id) + '</span>' : '') + '<div class="ltd-hint">' + esc(x.sub) + '</div></div></div></th><td class="q">' + fmt(x.qty) + ' ' + x.unit + '</td><td class="n">' + (x.price != null ? rub(x.price) : '—') + '</td><td class="n">' + (x.sum != null ? rub(x.sum) : '—') + '</td></tr>';
    }).join('');
    $('lfcFoot').innerHTML = priced ? '<tr><th scope="row" colspan="3">Итого по позициям с ценой</th><td class="n">' + rub(total) + '</td></tr>' : '';
    $('lfcSumBox').hidden = !priced; $('lfcSum').textContent = rub(total);
    last = { r: r, total: total };
  }

  /* ---------- Картинка схемы: «Увеличить» (fancybox) и PDF заявки ---------- */
  function planImage(W, type, q, id) {
    var svg = $(id || 'lfcSvg'), vb = svg.viewBox.baseVal, H = Math.round(W * vb.height / vb.width);
    return new Promise(function (resolve) {
      try {
        var cs = getComputedStyle(root);
        var src = new XMLSerializer().serializeToString(svg).replace(/var\((--ltd-[a-z0-9-]+)\)/g, function (m, v) { return cs.getPropertyValue(v).trim() || '#000'; })
          .replace(/^<svg /, '<svg width="' + W + '" height="' + H + '" font-family="Arial, sans-serif" ');
        var img = new Image();
        img.onload = function () {
          var c = document.createElement('canvas'); c.width = W; c.height = H;
          var g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, W, H); g.drawImage(img, 0, 0, W, H);
          try { resolve(c.toDataURL(type, q)); } catch (e) { resolve(''); }
        };
        img.onerror = function () { resolve(''); };
        img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(src);
      } catch (e) { resolve(''); }
    });
  }
  function zoom() {
    planImage(2400, 'image/png').then(function (src) {
      if (src && window.jQuery && jQuery.fancybox) jQuery.fancybox.open({ src: src, type: 'image', opts: { caption: 'Схема секций ограждения' } });
    });
  }
  $('lfcZoom').addEventListener('click', zoom);
  $('lfcSvg').addEventListener('click', zoom);

  /* ---------- «Рассчитать с менеджером»: расчёт → PDF → письмо и лид (как у террасы) ---------- */
  $('lfcPdfOpen').addEventListener('click', function () {
    var url = window.LFC_LEAD_URL;
    if (!url || !last || !window.BX || !BX.bitrix_sessid) return;
    var r = last.r, S = r.S, params = [
      r.noPosts ? 'Ограждение: ' + r.nOpen + ' ' + plural(r.nOpen, 'проём', 'проёма', 'проёмов') + ' по ' + fmt(r.L) + ' м между бетонными блоками или стенами, ' + (r.k > 1 ? 'промежуточных столбов ДПК ' + (r.k - 1) + ' на проём' : 'без столбов ДПК') + ', высота ' + fmt(Math.round(r.H * 1000)) + ' мм'
        : r.pm ? 'Ограждение по схеме террасы: ' + SHAPES[plan.shape].n.replace(/­/g, '').toLowerCase() + ' ' + fmt(r.P.L) + '×' + fmt(r.P.W) + ' м, стороны ' +
          r.P.sides.map(function (s, i) { return plan.on[i] ? (i + 1) + ' (' + fmt(s.len, 2) + ' м)' : ''; }).filter(Boolean).join(', ') + ' — всего ' + fmt(r.L, 2) + ' пог. м, высота ' + fmt(Math.round(r.H * 1000)) + ' мм'
        : 'Ограждение: ' + fmt(r.L) + ' пог. м, высота ' + fmt(Math.round(r.H * 1000)) + ' мм, шаг столбов ' + fmt(Math.round(r.axis * 1000)) + ' мм',
      'Вид ' + r.T.id + ': ' + r.T.name.toLowerCase(),
      'Серия: ' + seriesName(S) + ', цвет ' + cur.color + (r.alu ? ', алюминиевые балясины' : ''),
      r.noPosts ? 'Проёмов: ' + r.nOpen + (r.k > 1 ? ', в каждом ' + r.k + ' ' + plural(r.k, 'секция', 'секции', 'секций') + ' по ' + fmt(Math.round(r.c * 1000)) + ' мм' : '') + ', ' + (r.T.id === 1 ? 'балясин ' + r.nb : 'деталей заполнения ' + r.segs.length) + (r.k > 1 ? ' на секцию' : ' на проём') : 'Секций: ' + r.spans + ' по ' + r.cTxt + ' мм, столбов ' + r.N + ', ' + (r.T.id === 1 ? 'балясин ' + r.nbTxt : 'деталей заполнения ' + r.segTxt) + ' на секцию'
    ];
    var calcData = { params: params, total: last.total,
      rows: r.rows.filter(function (x) { return !x.off; }).map(function (x) { return { name: x.text, art: x.id ? String(x.id) : '', note: x.sub, qty: fmt(x.qty) + ' ' + x.unit, price: x.price, sum: x.sum }; }) };
    // по схеме террасы в PDF — план со сторонами ограждения, иначе схема секций
    planImage(1280, 'image/jpeg', 0.85, r.pm ? 'lfcPlan' : 'lfcSvg').then(function (plan) {
      var body = new URLSearchParams(); body.append('sessid', BX.bitrix_sessid());
      body.append('data', JSON.stringify({ kind: 'fence', calc: calcData, plan: plan, page: location.href.split('#')[0] }));
      return fetch(url, { method: 'POST', body: body, credentials: 'same-origin' });
    }).catch(function () {}); // не вышло — заявка всё равно уйдёт, просто без PDF
  });

  /* ---------- «Добавить всё в корзину»: строки сметы с ценой, кроме снятых галочек (js/newdesign-calc-basket.js) ---------- */
  $('lfcCart').addEventListener('click', function () {
    if (!last || !window.ndCalcBasket) return;
    ndCalcBasket(this, $('lfcCartMsg'), last.r.rows.filter(function (x) { return !x.off && x.id && x.price != null; }).map(function (x) { return { id: x.id, qty: x.qty }; }));
  });

  /* ---------- События ---------- */
  $('lfcBrand').addEventListener('change', function () {
    var b = this.value; if (series().b !== b) selectSeries(DATA.S.findIndex(function (S) { return S.b === b; }));
  });
  $('lfcList').addEventListener('change', function (ev) { if (ev.target.name === 'lfcSeries') selectSeries(+ev.target.value); });
  $('lfcTypes').addEventListener('change', function (ev) { if (ev.target.name === 'lfcType') { cur.type = +ev.target.value; fillOpts(); render(); } });
  $('lfcForm').addEventListener('change', function (ev) {
    var t = ev.target;
    if (t.name === 'lfcShape') { plan.shape = t.value; plan.on = []; }   // новая форма — все стороны снова с ограждением
    if (t.hasAttribute && t.hasAttribute('data-side')) plan.on[+t.getAttribute('data-side')] = t.checked;
    if (t.id === 'lfcEnds' || t.name === 'lfcMode' || t.name === 'lfcShape' || t.hasAttribute('data-side')) render();
  });
  // клик по стороне на плане — поставить или убрать ограждение
  $('lfcPlan').addEventListener('click', function (ev) {
    var g = ev.target.closest && ev.target.closest('[data-side]'); if (!g) return;
    var i = +g.getAttribute('data-side'); plan.on[i] = !plan.on[i]; render();
  });
  $('lfcColor').addEventListener('change', function () { cur.color = this.value; render(); });
  $('lfcOpts').addEventListener('change', function (ev) { var k = ev.target.getAttribute('data-opt'); if (k) { cur.opt[k] = ev.target.value; render(); } });
  $('lfcForm').addEventListener('input', function (ev) { if (ev.target.id === 'lfcH') cur.hSet = true; if (ev.target.type === 'number') render(); });
  $('lfcForm').addEventListener('submit', function (ev) { ev.preventDefault(); });
  $('lfcTable').addEventListener('change', function (ev) { var k = ev.target.getAttribute && ev.target.getAttribute('data-key'); if (k) { excluded[k] = !ev.target.checked; render(); } });

  // ?series=<код раздела> — сразу эта серия (ссылка из раздела каталога)
  var q = new URLSearchParams(location.search).get('series'), start = 0;
  if (q) { var fi = DATA.S.findIndex(function (S) { return S.url.indexOf(q + '/') >= 0; }); if (fi >= 0) start = fi; }
  renderTypes();
  renderShapes();
  selectSeries(start);
})();
