/* Калькулятор ограждений (/info/kalkulyator-ograzhdeniy/), 21.10.2026.
   Длина ограждения и углы → секции и столбы → детали выбранной серии по правилам производителя → смета.
   Данные каталога — window.LFC_DATA (js/newdesign-fence-calc-data.js), правила сборки — RULES ниже.
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
  // деталь: [название, цена, длина мм, сечение «100х100», цвет, метки, адрес, картинка]
  function link(it) { return '<a href="' + SITE + it[6] + '" target="_blank" rel="noopener">' + esc(it[0]) + '</a>'; }
  function sec(it, i) { var m = String(it && it[3] || '').split('х'); return (+m[i || 0] || 0) / 1000; }
  function has(it, tag) { return (' ' + it[5] + ' ').indexOf(' ' + tag + ' ') >= 0; }

  /* Правила сборки по сериям (id раздела каталога). Источники: инструкции POLIVAN GROUP (Сингараджа,
     Денпасар, Нусадуа), инструкция «Террапол», каталог Латитудо (EasyDecking); NEXTWOOD — типовые.
     step     — наибольший шаг столбов по осям, м; stepDef — шаг по умолчанию
     postPer  — сколько столбов выходит из одной заготовки; h — высота ограждения, м
     rails    — ряды перил на секцию: rail (перила/поручень) и bottom (подбалясенник)
     balPer   — балясин из одной заготовки ДПК; perM — балясин на метр секции (иначе по просвету ≤ 110 мм)
     sup      — подпорки под нижним поручнем на секцию (функция от длины секции), supLen — их длина, м
     railFix  — крепёж перил на каждый конец каждого ряда
     alt      — Террапол Классик: большие и малые столбы через один, по краям большие */
  var BASE = { step: 1.5, postPer: 2, h: 1.1, rails: [['rail', 2]], balPer: 3, gap: 0.11, balFix: 2, railFix: true };
  var RULES = {
    361: { step: 1.5, stepDef: 1, sup: function (c) { return c > 1 + EPS ? 1 : 0; }, supLen: 0.1,
      note: 'Пролёт между столбами — не больше 1,5 м, рекомендуемый 1 м; при пролёте больше 1 м под нижними перилами ставится подпорка. Столбы надеваются на металлические закладные 80×80×950.',
      missing: { railfix: ['Уголок крепёжный 38×30×40 для перил', 'по 2 на каждый конец перил'] } },
    225: { step: 1.5, stepDef: 1, sup: function (c) { return c > 1 + EPS ? 1 : 0; }, supLen: 0.1, rails: [['rail', 1, 'top'], ['rail', 1, 'bottom']],
      note: 'Пролёт между столбами — не больше 1,5 м, рекомендуемый 1 м; при пролёте больше 1 м ставится подпорка. Сверху — выбранные перила, снизу — прямоугольные. Столбы — на закладные 80×80×950.',
      missing: { railfix: ['Уголок крепёжный 38×30×40 для перил', 'по 2 на каждый конец перил'] } },
    271: { step: 1.5, note: 'Пролёт между столбами — не больше 1,5 м. Перила сверху и снизу, балясины на прямом крепеже, столбы — на металлические кронштейны.' },
    318: { step: 1.5, note: 'Пролёт между столбами — не больше 1,5 м. Перила сверху и снизу, балясины на прямом крепеже, столбы — на металлические кронштейны. Крышки, юбки и крепёж — общие с NEXTWOOD Классик.' },
    356: { step: 1.56, postPer: 3, h: 0.964, balPer: 4, perM: 9 / 1.448, sup: function (c) { return Math.max(1, Math.round(c / 1.448 * 3)); }, supLen: 0.117,
      note: 'По инструкции POLIVAN: шаг столбов по осям — не больше 1560 мм, из столба 2900 мм выходит 3 столба по 964 мм, из поручня — 2 поручня по 1448 мм. На секцию — 9 балясин по 720 мм (из одной балясины — 4) и 3 подпорки под нижним поручнем, 21 крепление балясин.' },
    358: { step: 1.56, postPer: 3, h: 0.964, balPer: 4, perM: 9 / 1.448, sup: function (c) { return Math.max(1, Math.round(c / 1.448 * 3)); }, supLen: 0.117,
      note: 'По инструкции POLIVAN: шаг столбов по осям — не больше 1560 мм, из столба 2900 мм выходит 3 столба по 964 мм, из поручня — 2 поручня по 1448 мм. На секцию — 9 балясин по 720 мм (из одной балясины — 4) и 3 подпорки, 21 крепление балясин.' },
    357: { step: 1.59, postPer: 3, h: 0.964, balPer: 4, perM: 9 / 1.448, perMAlu: 10 / 1.448, sup: function (c) { return Math.max(1, Math.round(c / 1.448 * 3)); }, supLen: 0.108,
      note: 'По инструкции POLIVAN: шаг столбов по осям — не больше 1590 мм, столб 125×125, из заготовки 2900 мм — 3 столба по 964 мм. На секцию 1448 мм — 9 балясин ДПК (21 крепление) или 10 алюминиевых (26 креплений) и 3 подпорки под нижним поручнем.' },
    224: { step: 1.2, alt: true, rails: [['rail', 1], ['bottom', 2]], balFix: 0, sup: function (c) { return c > 0.8 + EPS ? 2 : 1; }, h: 1,
      note: 'По инструкции «Террапол»: шаг столбов — не больше 1200 мм, по краям и на углах большие столбы, между ними — малый. Сверху перила, под ними и внизу — подбалясенники; опорные элементы под нижним подбалясенником режутся из малого столба (1 на секцию до 800 мм, 2 — длиннее). Столбы надеваются на металлический армирующий профиль.',
      missing: { balfix: ['Крепление балясин 40×20×24 и уголки для перил', 'входят в комплект «Террапол», количество уточнит менеджер'] } },
    320: { step: 1.2, rails: [['rail', 1], ['bottom', 2]], balFix: 0, sup: function (c) { return c > 0.8 + EPS ? 2 : 1; }, h: 1,
      note: 'По инструкции «Террапол»: шаг столбов — не больше 1200 мм. Сверху перила, под ними и внизу — подбалясенники, опорные элементы под нижним подбалясенником. Столбы надеваются на металлический армирующий профиль.',
      missing: { balfix: ['Крепление балясин и уголки для перил', 'входят в комплект «Террапол», количество уточнит менеджер'] } }
  };
  function rule(S) { var r = {}, k, o = RULES[S.id] || {}; for (k in BASE) r[k] = BASE[k]; for (k in o) r[k] = o[k]; return r; }

  /* ---------- Выбор деталей ---------- */
  var cur = { s: 0, color: '', opt: {} }, excluded = {};
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
    var bal = S.p.bal || [];
    if (bal.some(function (it) { return has(it, 'alu'); }) && bal.some(function (it) { return !has(it, 'alu'); }))
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
  function opt(key) { var v = variants().find(function (x) { return x.key === key; }); return v ? (cur.opt[key] || v.def) : null; }

  /* ---------- Форма ---------- */
  function brands() { var b = []; DATA.S.forEach(function (S) { if (b.indexOf(S.b) < 0) b.push(S.b); }); return b; }
  function fillBrands() { $('lfcBrand').innerHTML = '<option value="">Все марки</option>' + brands().map(function (b) { return '<option>' + esc(b) + '</option>'; }).join(''); }
  function renderList() {
    var b = $('lfcBrand').value, html = '';
    DATA.S.forEach(function (S, i) {
      if (b && S.b !== b) return;
      var post = (S.p.post || [])[0], img = post && post[7] ? '<span class="ltd-thumb"><img src="' + post[7] + '" alt="" loading="lazy"></span>' : '<span class="ltd-thumb"></span>';
      var from = (S.p.post || []).reduce(function (m, it) { return Math.min(m, it[1] || Infinity); }, Infinity);
      html += '<button type="button" class="ltd-opt" role="option" aria-selected="' + (i === cur.s) + '" data-s="' + i + '">' + img +
        '<span>' + esc(S.b + ' · ' + S.n.replace(S.b, '').replace(/\s+/g, ' ').trim()) + '<small>' + S.c.length + ' ' + plural(S.c.length, 'цвет', 'цвета', 'цветов') + '</small></span>' +
        (isFinite(from) ? '<b>столб от ' + rub(from) + '</b>' : '<b></b>') + '</button>';
    });
    $('lfcList').innerHTML = html;
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
    renderList(); fillColors(); fillOpts(); render();
  }

  /* ---------- Расчёт ---------- */
  function calc() {
    var S = series(), R = rule(S);
    var L = num($('lfcL').value), corners = Math.round(num($('lfcCorners').value)), step = num($('lfcStep').value);
    var bad = {};
    if (!(L >= 0.5 && L <= 500)) bad.L = 'От 0,5 до 500 м';
    if (!(corners >= 0 && corners <= 50)) bad.corners = 'От 0 до 50';
    if (!(step >= 0.5)) bad.step = 'Не меньше 0,5 м';
    else if (step > R.step + EPS) bad.step = 'Не больше ' + fmt(R.step) + ' м для этой серии';
    if (Object.keys(bad).length) return { bad: bad };

    var size = opt('post') || postSizes()[0];
    var post = pick('post', function (it) { return !has(it, 'small') && (!size || it[3] === size); });
    var small = R.alt ? pick('post', function (it) { return has(it, 'small'); }) : null;
    var pw = sec(post) || 0.1;

    // секции: по длине с шагом не больше заданного, на каждом углу — свой столб
    var spans = Math.max(1, ceil(L / step)) + corners;
    if (R.alt && small && spans % 2) spans++;            // Террапол: по краям большие, значит секций чётное число
    var axis = L / spans, c = Math.max(0.05, axis - pw);   // просвет секции между столбами
    var N = spans + 1, big = R.alt && small ? Math.ceil(N / 2) : N, nSmall = N - big;

    var alu = opt('bal') === 'alu';
    var bal = pick('bal', function (it) { return alu ? has(it, 'alu') : !has(it, 'alu'); }) || pick('bal');
    var bw = alu ? 0.02 : (sec(bal) || 0.05);
    var nb = R.perM ? Math.max(1, ceil(c * (alu && R.perMAlu ? R.perMAlu : R.perM) - 0.05)) : Math.max(1, ceil((c - R.gap) / (bw + R.gap)));
    var nsup = R.sup ? R.sup(c) : 0;
    var r = { S: S, R: R, L: L, corners: corners, step: step, spans: spans, axis: axis, c: c, N: N, big: big, small: nSmall, pw: pw,
      nb: nb, nsup: nsup, alu: alu, bw: bw, size: size, rows: [] };

    function row(key, it, qty, sub, fallback) {
      if (!qty) return;
      r.rows.push({ key: key, name: it ? link(it) : esc(fallback), text: it ? it[0] : fallback, qty: qty, unit: 'шт',
        price: it && it[1] ? it[1] : null, sub: sub + (it && it.guess ? ' · цвет уточнит менеджер' : '') });
    }
    function cut(len, piece, per) { return per || Math.max(1, Math.floor((len / 1000 + KERF) / (piece + KERF))); }

    // столбы
    row('post', post, ceil(big / R.postPer), fmt(big) + ' ' + plural(big, 'столб', 'столба', 'столбов') + (R.alt && small ? ' больших' : '') + ', из заготовки — ' + R.postPer + ' ' + plural(R.postPer, 'столб', 'столба', 'столбов'));
    if (small && nSmall) row('postS', small, ceil(nSmall / R.postPer), fmt(nSmall) + ' малых, из заготовки — ' + R.postPer + '; из остатков — опорные элементы под подбалясенник');

    // перила, поручни, подбалясенники
    var top = opt('top');
    R.rails.forEach(function (rl, i) {
      var kind = rl[0], rows = rl[1], f = null;
      if (rl[2] === 'top') f = function (it) { return top === 'fig' ? has(it, 'fig') : !has(it, 'fig'); };
      if (rl[2] === 'bottom') f = function (it) { return !has(it, 'fig'); };
      var it = pick(kind, f), k = it && it[2] ? cut(it[2], c) : 1;
      var pcs = rows * ceil(spans / k);
      var what = kind === 'bottom' ? 'подбалясенник' : rl[2] === 'bottom' ? 'нижние перила' : rl[2] === 'top' ? 'верхние перила' : rows === 2 ? 'верх и низ' : 'верх';
      row('rail' + i, it, pcs, what + ': ' + rows * spans + ' ' + plural(rows * spans, 'отрезок', 'отрезка', 'отрезков') + ' по ' + fmt(Math.round(c * 1000)) + ' мм, из заготовки — ' + k);
    });

    // балясины и подпорки
    var nbAll = nb * spans, supAll = nsup * spans;
    if (bal) {
      if (alu) row('bal', bal, nbAll, nb + ' на секцию' + (supAll ? ', подпорки — из обрезков' : ''));
      else {
        var per = R.balPer, supWhips = R.supLen && supAll && R.perM ? ceil(supAll / Math.floor(((bal[2] || 2900) / 1000) / (R.supLen + KERF))) : 0;
        row('bal', bal, ceil(nbAll / per) + supWhips, nb + ' на секцию, ' + fmt(nbAll) + ' шт. всего, из заготовки — ' + per + (supWhips ? '; подпорки ' + fmt(supAll) + ' шт. по ' + fmt(R.supLen * 1000) + ' мм' : ''));
      }
    }
    // крепёж балясин: по 2 на балясину, у POLIVAN ещё на подпорки (у алюминиевых — по 2)
    if (R.balFix) {
      var bf = pick('balfix', function (it) { return (alu ? has(it, 'alu') : !has(it, 'alu')) && !has(it, 'turn'); }, true);
      var qf = nbAll * R.balFix + (R.perM ? supAll * (alu ? 2 : 1) : 0);
      if (bf) row('balfix', bf, qf, R.balFix + ' на балясину' + (R.perM ? ' и на подпорки' : '') + ', ' + Math.round(qf / spans) + ' на секцию');
      else row('balfix', null, qf, 'в каталоге нет — подберёт менеджер', alu ? 'Крепление для алюминиевых балясин' : 'Крепление для балясин');
    }
    // крепёж перил: на каждый конец каждого ряда
    var railRows = R.rails.reduce(function (t, rl) { return t + (rl[0] === 'rail' ? rl[1] : 0); }, 0);
    if (R.railFix && S.p.railfix) {
      var rf = pick('railfix', function (it) { return !has(it, 'turn'); });
      row('railfix', rf, spans * railRows * 2, '2 на каждый поручень секции, прямые');
    }
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
    row('mount', mount, N, '1 на столб' + (mount && /кронштейн/i.test(mount[0]) ? ', крепится к основанию террасы, не к доске' : ''));
    // чего нет в каталоге — строкой без цены
    Object.keys(R.missing || {}).forEach(function (k) { var m = R.missing[k]; row('miss-' + k, null, k === 'railfix' ? spans * railRows * 2 : spans, m[1], m[0]); });

    r.rows.forEach(function (x) { x.sum = x.price != null ? x.qty * x.price : null; });
    return r;
  }

  /* ---------- Схема секций ---------- */
  function draw(r) {
    var VW = 640, VH = 300, show = Math.min(r.spans, 4), H = r.R.h, padL = 30, padR = 20, padT = 46, padB = 52;
    var widthM = show * r.axis + r.pw, sc = Math.min((VW - padL - padR) / widthM, (VH - padT - padB) / H);
    var x0 = padL + (VW - padL - padR - widthM * sc) / 2, yB = VH - padB, X = function (m) { return x0 + m * sc; }, Y = function (m) { return yB - m * sc; };
    var o = '<rect x="0" y="0" width="' + VW + '" height="' + VH + '" fill="var(--ltd-panel)"/>';
    o += '<line x1="' + (x0 - 12) + '" y1="' + yB + '" x2="' + (X(widthM) + 12) + '" y2="' + yB + '" stroke="var(--ltd-muted)" stroke-width="1.5"/>';
    var railH = Math.max(0.035, sec(pick('rail'), 1) || 0.045), botY = r.R.perM ? 0.11 : 0.1, topY = H - 0.06;
    for (var i = 0; i < show; i++) {
      var a = i * r.axis + r.pw, b = a + r.c;
      // нижний и верхний ряды перил
      [[botY, 'var(--ltd-board)'], [topY - railH, 'var(--ltd-board)']].forEach(function (q) { o += '<rect x="' + X(a) + '" y="' + Y(q[0] + railH) + '" width="' + (r.c * sc) + '" height="' + (railH * sc) + '" fill="' + q[1] + '"/>'; });
      if (r.R.rails.some(function (rl) { return rl[0] === 'bottom'; })) o += '<rect x="' + X(a) + '" y="' + Y(topY - railH) + '" width="' + (r.c * sc) + '" height="' + (0.035 * sc) + '" fill="var(--ltd-board-2)"/>';
      // балясины поровну по секции
      var bw = r.bw, g = (r.c - r.nb * bw) / (r.nb + 1);
      for (var j = 0; j < r.nb; j++) { var bx = a + g + j * (bw + g); o += '<rect x="' + X(bx) + '" y="' + Y(topY - railH) + '" width="' + Math.max(1.2, bw * sc) + '" height="' + ((topY - railH - botY - railH) * sc) + '" fill="var(--ltd-board-2)"/>'; }
      // подпорки под нижним рядом
      for (var k = 0; k < r.nsup; k++) { var sx = a + r.c * (k + 1) / (r.nsup + 1) - 0.02; o += '<rect x="' + X(sx) + '" y="' + Y(botY) + '" width="' + (0.04 * sc) + '" height="' + (botY * sc) + '" fill="var(--ltd-board-2)"/>'; }
      // размер секции
      var my = Y(H) - 14;
      o += '<path d="M' + X(a) + ' ' + my + 'H' + X(b) + 'M' + X(a) + ' ' + (my - 4) + 'v8M' + X(b) + ' ' + (my - 4) + 'v8" stroke="var(--ltd-muted)" stroke-width="1"/>' +
        '<text x="' + X((a + b) / 2) + '" y="' + (my - 5) + '" fill="var(--ltd-ink)" font-size="11" text-anchor="middle">' + fmt(Math.round(r.c * 1000)) + ' мм</text>';
    }
    // столбы (у Террапола Классик через один малые — ниже, под перила)
    for (var p = 0; p <= show; p++) {
      var isSmall = r.R.alt && r.small && p % 2 === 1, px = p * r.axis, w = isSmall ? 0.07 : r.pw, ph = isSmall ? topY - railH : H;
      o += '<rect x="' + X(px + (r.pw - w) / 2) + '" y="' + Y(ph) + '" width="' + (w * sc) + '" height="' + (ph * sc) + '" fill="var(--ltd-joist)"/>';
      if (!isSmall) o += '<rect x="' + (X(px) - 3) + '" y="' + (Y(H) - 5) + '" width="' + (r.pw * sc + 6) + '" height="6" rx="1.5" fill="var(--ltd-joist)"/>';
    }
    // шаг по осям под схемой
    var ay = yB + 18;
    o += '<path d="M' + X(r.pw / 2) + ' ' + ay + 'H' + X(r.axis + r.pw / 2) + 'M' + X(r.pw / 2) + ' ' + (ay - 4) + 'v8M' + X(r.axis + r.pw / 2) + ' ' + (ay - 4) + 'v8" stroke="var(--ltd-accent)" stroke-width="1.5"/>' +
      '<text x="' + X(r.axis / 2 + r.pw / 2) + '" y="' + (ay + 14) + '" fill="var(--ltd-accent)" font-size="11" text-anchor="middle">шаг столбов ' + fmt(Math.round(r.axis * 1000)) + ' мм</text>';
    o += '<text x="' + (x0 - 8) + '" y="' + Y(H / 2) + '" fill="var(--ltd-muted)" font-size="11" text-anchor="middle" transform="rotate(-90 ' + (x0 - 8) + ' ' + Y(H / 2) + ')">≈ ' + fmt(Math.round(H * 1000)) + ' мм</text>';
    $('lfcSvg').innerHTML = o;
    $('lfcCap').textContent = show < r.spans ? 'показаны ' + show + ' из ' + r.spans + ' секций' : '';
    $('lfcLegendNote').textContent = r.nsup ? 'подпорки: ' + r.nsup + ' на секцию' : '';
  }

  /* ---------- Вывод ---------- */
  var last = null;
  function render() {
    root.querySelectorAll('.ltd-field[data-f]').forEach(function (f) { f.classList.remove('is-bad'); var e = f.querySelector('.ltd-err'); if (e) e.textContent = ''; });
    var R = rule(series());
    $('lfcStepHint').textContent = 'По осям столбов, не больше ' + fmt(R.step) + ' м для этой серии' + (R.stepDef ? ', рекомендуемый ' + fmt(R.stepDef) + ' м' : '') + '. Калькулятор делит длину на равные секции.';
    var r = calc(), out = $('lfcOut');
    if (r.bad) {
      Object.keys(r.bad).forEach(function (k) { var f = root.querySelector('.ltd-field[data-f="' + k + '"]'); if (f) { f.classList.add('is-bad'); f.querySelector('.ltd-err').textContent = r.bad[k]; } });
      out.classList.add('is-invalid'); $('lfcInvalid').hidden = false; $('lfcInvalid').textContent = 'Проверьте поля, отмеченные красным. Результат пересчитается автоматически.';
      return;
    }
    out.classList.remove('is-invalid'); $('lfcInvalid').hidden = true;
    var S = series();
    $('lfcSeriesInfo').innerHTML = '<a href="' + SITE + S.url + '" target="_blank" rel="noopener">Все товары серии в каталоге</a>';
    $('lfcBig').innerHTML = fmt(r.spans) + '<small>' + plural(r.spans, 'секция', 'секции', 'секций') + ' по ' + fmt(Math.round(r.c * 1000)) + ' мм</small>';
    $('lfcBigSub').innerHTML = 'Столбов <b>' + r.N + '</b>' + (r.small ? ' (' + r.big + ' больших, ' + r.small + ' малых)' : '') + ' · шаг ' + fmt(Math.round(r.axis * 1000)) + ' мм · балясин ' + r.nb + ' на секцию';
    $('lfcRules').innerHTML = '<b>Как считаем ' + esc(S.b) + ' ' + esc(S.n.replace(S.b, '').trim()) + '</b>' + esc(r.R.note || '');
    draw(r);
    var total = 0, priced = 0;
    $('lfcTable').innerHTML = r.rows.map(function (x) {
      x.off = !!excluded[x.key];
      if (x.sum != null && !x.off) { total += x.sum; priced++; }
      return '<tr' + (x.off ? ' class="is-off"' : '') + '><th scope="row"><div class="ltd-rowname"><input type="checkbox" class="ltd-on" data-key="' + x.key + '"' + (x.off ? '' : ' checked') + ' aria-label="Включить в смету"><div>' + x.name + '<div class="ltd-hint">' + esc(x.sub) + '</div></div></div></th><td class="q">' + fmt(x.qty) + ' ' + x.unit + '</td><td class="n">' + (x.price != null ? rub(x.price) : '—') + '</td><td class="n">' + (x.sum != null ? rub(x.sum) : '—') + '</td></tr>';
    }).join('');
    $('lfcFoot').innerHTML = priced ? '<tr><th scope="row" colspan="3">Итого по позициям с ценой</th><td class="n">' + rub(total) + '</td></tr>' : '';
    $('lfcSumBox').hidden = !priced; $('lfcSum').textContent = rub(total);
    last = { r: r, total: total };
  }

  /* ---------- Картинка схемы: «Увеличить» (fancybox) и PDF заявки ---------- */
  function planImage(W, type, q) {
    var svg = $('lfcSvg'), vb = svg.viewBox.baseVal, H = Math.round(W * vb.height / vb.width);
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
      'Ограждение: ' + fmt(r.L) + ' м' + (r.corners ? ', углов ' + r.corners : '') + ', шаг столбов ' + fmt(Math.round(r.axis * 1000)) + ' мм',
      'Серия: ' + S.b + ' ' + S.n.replace(S.b, '').trim() + ', цвет ' + cur.color + (r.alu ? ', алюминиевые балясины' : ''),
      'Секций: ' + r.spans + ' по ' + fmt(Math.round(r.c * 1000)) + ' мм, столбов ' + r.N + ', балясин ' + r.nb + ' на секцию'
    ];
    var calcData = { params: params, total: last.total,
      rows: r.rows.filter(function (x) { return !x.off; }).map(function (x) { return { name: x.text, note: x.sub, qty: fmt(x.qty) + ' ' + x.unit, price: x.price, sum: x.sum }; }) };
    planImage(1280, 'image/jpeg', 0.85).then(function (plan) {
      var body = new URLSearchParams(); body.append('sessid', BX.bitrix_sessid());
      body.append('data', JSON.stringify({ kind: 'fence', calc: calcData, plan: plan, page: location.href.split('#')[0] }));
      return fetch(url, { method: 'POST', body: body, credentials: 'same-origin' });
    }).catch(function () {}); // не вышло — заявка всё равно уйдёт, просто без PDF
  });

  /* ---------- События ---------- */
  $('lfcBrand').addEventListener('change', function () {
    renderList();
    var b = this.value; if (b && series().b !== b) selectSeries(DATA.S.findIndex(function (S) { return S.b === b; }));
  });
  $('lfcList').addEventListener('click', function (ev) { var o = ev.target.closest('.ltd-opt'); if (o) selectSeries(+o.getAttribute('data-s')); });
  $('lfcColor').addEventListener('change', function () { cur.color = this.value; render(); });
  $('lfcOpts').addEventListener('change', function (ev) { var k = ev.target.getAttribute('data-opt'); if (k) { cur.opt[k] = ev.target.value; render(); } });
  $('lfcForm').addEventListener('input', function (ev) { if (ev.target.id !== 'lfcColor' && !ev.target.getAttribute('data-opt')) render(); });
  $('lfcForm').addEventListener('submit', function (ev) { ev.preventDefault(); });
  $('lfcTable').addEventListener('change', function (ev) { var k = ev.target.getAttribute && ev.target.getAttribute('data-key'); if (k) { excluded[k] = !ev.target.checked; render(); } });

  // ?series=<код раздела> — сразу эта серия (ссылка из раздела каталога)
  var q = new URLSearchParams(location.search).get('series'), start = 0;
  if (q) { var fi = DATA.S.findIndex(function (S) { return S.url.indexOf(q + '/') >= 0; }); if (fi >= 0) start = fi; }
  fillBrands();
  selectSeries(start);
})();
