/**
 * Калькулятор террасы (/info/kalkulyator-terrasy/): расчёт досок по рядам, лаг, кляммеров, опор и смета.
 * Разметка — include/calc/terrace_calc_page.php, стили — css/newdesign-terrace-calc.css,
 * данные каталога — js/newdesign-terrace-calc-data.js. Исходники и сборка: «Калькулятор террасы/src».
 */
/* Ядро расчёта калькулятора террасы Латитудо (без DOM).
   Все размеры в метрах, кроме: ширина доски и зазор — мм, шаг лаг и шаг опор — см.
   Форма террасы — либо набор непересекающихся прямоугольников [x, y, w, h] (rects),
   либо один выпуклый многоугольник [[x, y], ...] по часовой стрелке на экране (poly):
   x — вдоль длины террасы, y — вдоль ширины (вниз). Внутри всё считается по выпуклым кускам. */
var LtdCore = (function () {
  var EPS = 1e-9;
  function ceil(x) { return Math.ceil(x - EPS); }

  /* Формы. p: L, W (габариты), a, b (размеры выреза / второй части). */
  var SHAPES = {
    rect: { name: 'Прямоугольник', params: [], rects: function (p) { return [[0, 0, p.L, p.W]]; } },
    lshape: { name: 'Г-образная', params: ['a', 'b'], hint: 'Вырез в углу: a — по длине, b — по ширине',
      rects: function (p) { return [[0, p.b, p.L, p.W - p.b], [0, 0, p.L - p.a, p.b]]; },
      perimeter: function (p) { return 2 * (p.L + p.W); } },
    ushape: { name: 'П-образная', params: ['a', 'b'], hint: 'Вырез по центру: a — по длине, b — по ширине',
      rects: function (p) { var s = (p.L - p.a) / 2; return [[0, p.b, p.L, p.W - p.b], [0, 0, s, p.b], [s + p.a, 0, s, p.b]]; },
      perimeter: function (p) { return 2 * (p.L + p.W) + 2 * p.b; } },
    tshape: { name: 'Т-образная', params: ['a', 'b'], hint: 'Верх на всю длину глубиной b, ножка шириной a',
      rects: function (p) { return [[0, 0, p.L, p.b], [(p.L - p.a) / 2, p.b, p.a, p.W - p.b]]; },
      perimeter: function (p) { return 2 * (p.L + p.W); } },
    trap: { name: 'Трапеция', params: ['a'], hint: 'Длина — нижняя сторона, a — верхняя сторона (по центру)',
      poly: function (p) { var s = (p.L - p.a) / 2; return [[s, 0], [s + p.a, 0], [p.L, p.W], [0, p.W]]; } },
    bevel: { name: 'Скошенный угол', params: ['a', 'b'], hint: 'Скос в углу: a — по длине, b — по ширине',
      poly: function (p) { return [[0, 0], [p.L - p.a, 0], [p.L, p.b], [p.L, p.W], [0, p.W]]; } },
    pool: { name: 'Вокруг бассейна', params: ['a', 'b'], hint: 'Бассейн по центру: a — по длине, b — по ширине',
      rects: function (p) { var x1 = (p.L - p.a) / 2, y1 = (p.W - p.b) / 2;
        return [[0, 0, p.L, y1], [0, y1 + p.b, p.L, p.W - y1 - p.b], [0, y1, x1, p.b], [x1 + p.a, y1, p.L - x1 - p.a, p.b]]; },
      perimeter: function (p) { return 2 * (p.L + p.W) + 2 * (p.a + p.b); } }
  };

  function validShape(p) {
    var sh = SHAPES[p.shape];
    if (!sh || !sh.params.length) return '';
    if (!(p.a > 0) || (sh.params.length > 1 && !(p.b > 0))) return 'Укажите размеры выреза';
    if (p.shape === 'tshape') { if (p.a >= p.L) return 'Ширина ножки a должна быть меньше длины'; if (p.b >= p.W) return 'Глубина верха b должна быть меньше ширины'; return ''; }
    if (p.shape === 'trap') return p.a >= p.L ? 'Верхняя сторона a должна быть меньше длины' : '';
    if (p.shape === 'bevel') { if (p.a >= p.L) return 'Скос a должен быть меньше длины'; if (p.b >= p.W) return 'Скос b должен быть меньше ширины'; return ''; }
    if (p.a >= p.L - 0.2) return 'Вырез a должен быть меньше длины хотя бы на 0,2 м';
    if (p.b >= p.W - 0.2) return 'Вырез b должен быть меньше ширины хотя бы на 0,2 м';
    return '';
  }

  function rectPoly(q) { return [[q[0], q[1]], [q[0] + q[2], q[1]], [q[0] + q[2], q[1] + q[3]], [q[0], q[1] + q[3]]]; }
  /* Выпуклые куски формы в координатах террасы */
  function pieces(s) {
    var sh = SHAPES[s.shape] || SHAPES.rect;
    return sh.poly ? [sh.poly(s)] : sh.rects(s).filter(function (q) { return q[2] > EPS && q[3] > EPS; }).map(rectPoly);
  }
  function polyArea(pl) { var t = 0; for (var i = 0; i < pl.length; i++) { var p = pl[i], q = pl[(i + 1) % pl.length]; t += p[0] * q[1] - q[0] * p[1]; } return Math.abs(t) / 2; }
  function polyPerimeter(pl) { var t = 0; for (var i = 0; i < pl.length; i++) { var p = pl[i], q = pl[(i + 1) % pl.length]; t += Math.hypot(q[0] - p[0], q[1] - p[1]); } return t; }

  /* Отрезки пересечения линии с формой. axis 'y' — горизонтальная линия на высоте v (отрезки по x),
     axis 'x' — вертикальная линия x = v (отрезки по y). */
  function cut(polys, axis, v) {
    var a = axis === 'y' ? 1 : 0, b = 1 - a, segs = [];
    polys.forEach(function (pl) {
      var lo = Infinity, hi = -Infinity;
      for (var i = 0; i < pl.length; i++) {
        var p = pl[i], q = pl[(i + 1) % pl.length];
        var p1 = Math.min(p[a], q[a]), p2 = Math.max(p[a], q[a]);
        if (v < p1 - 1e-7 || v > p2 + 1e-7) continue;
        if (p2 - p1 < 1e-9) { lo = Math.min(lo, p[b], q[b]); hi = Math.max(hi, p[b], q[b]); continue; }
        var t = p[b] + (v - p[a]) * (q[b] - p[b]) / (q[a] - p[a]);
        lo = Math.min(lo, t); hi = Math.max(hi, t);
      }
      if (hi - lo > 1e-6) segs.push([lo, hi]);
    });
    segs.sort(function (m, n) { return m[0] - n[0]; });
    var out = [];
    segs.forEach(function (sg) {
      var last = out[out.length - 1];
      if (last && sg[0] <= last[1] + 1e-6) last[1] = Math.max(last[1], sg[1]); else out.push([sg[0], sg[1]]);
    });
    return out;
  }
  /* Отрезки пересечения диагонали x + y = c с формой (по x) — средняя линия ряда при укладке 45° */
  function diagSegs(polys, c) {
    var segs = [];
    polys.forEach(function (pl) {
      var xs = [];
      for (var i = 0; i < pl.length; i++) {
        var p = pl[i], q = pl[(i + 1) % pl.length], g1 = p[0] + p[1] - c, g2 = q[0] + q[1] - c;
        if ((g1 <= 0) !== (g2 <= 0)) xs.push(p[0] + g1 / (g1 - g2) * (q[0] - p[0]));
      }
      xs.sort(function (m, n) { return m - n; });
      for (var j = 0; j + 1 < xs.length; j += 2) if (xs[j + 1] - xs[j] > 1e-6) segs.push([xs[j], xs[j + 1]]);
    });
    segs.sort(function (m, n) { return m[0] - n[0]; });
    var out = [];
    segs.forEach(function (sg) {
      var last = out[out.length - 1];
      if (last && sg[0] <= last[1] + 1e-6) last[1] = Math.max(last[1], sg[1]); else out.push([sg[0], sg[1]]);
    });
    return out;
  }
  /* Отрезки ряда досок в полосе [y0, y1]: объединение срезов по краям и середине полосы
     (у косой стороны доска режется по самой длинной кромке) */
  function bandSegs(polys, y0, y1) {
    var d = Math.min(1e-4, (y1 - y0) / 4), all = [];
    [y0 + d, (y0 + y1) / 2, y1 - d].forEach(function (y) { all = all.concat(cut(polys, 'y', y)); });
    all.sort(function (m, n) { return m[0] - n[0]; });
    var out = [];
    all.forEach(function (sg) {
      var last = out[out.length - 1];
      if (last && sg[0] <= last[1] + 1e-6) last[1] = Math.max(last[1], sg[1]); else out.push([sg[0], sg[1]]);
    });
    return out;
  }

  /* Поворот формы так, чтобы доски всегда шли по оси x («вдоль»). */
  function orient(polys, dir) {
    if (dir !== 'wid') return polys;
    return polys.map(function (pl) { return pl.map(function (p) { return [p[1], p[0]]; }); });
  }

  /* Сдвиг стыков в чётных (2-м, 4-м…) рядах при укладке вразбежку: половина доски, на ближайшей лаге */
  function staggerOffset(bl, step) { var o = Math.max(step, Math.round((bl / 2) / step) * step); return o >= bl - 1e-6 ? bl / 2 : o; }

  /* Куски досок в ряду: от начала ряда, первый кусок короче при сдвиге */
  function rowPieces(len, bl, off) {
    var out = [], x = 0;
    if (off > 0 && off < len - 1e-6) { out.push(off); x = off; }
    while (len - x > 1e-6) { var p = Math.min(bl, len - x); out.push(p); x += p; }
    return out;
  }

  /* Ряд не длиннее доски при укладке вразбежку: один стык на лаге, в соседних рядах — около 1/3 и 2/3
     длины ряда, чтобы стыки не шли в линию. Обрезок от одного куска идёт на второй кусок того же ряда или следующего.
     x0 — начало ряда (лаги стоят на x, кратных step). Слишком короткий ряд кладётся целиком. */
  function shortRowPieces(x0, len, step, odd) {
    var snap = function (t) { return Math.round((x0 + t) / step) * step - x0; };
    var p = snap(len * (odd ? 2 : 1) / 3);
    if (p < 0.3 - 1e-6 || len - p < 0.3 - 1e-6) return [len];
    return [p, len - p];
  }

  function calc(s) {
    var sh = SHAPES[s.shape] || SHAPES.rect;
    var base = pieces(s);
    var area = base.reduce(function (t, pl) { return t + polyArea(pl); }, 0);
    var perimeter = sh.perimeter ? sh.perimeter(s) : sh.poly ? polyPerimeter(base[0]) : 2 * (s.L + s.W);
    var diag = s.dir === 'diag';
    var polys = orient(base, s.dir);
    var along = s.dir === 'wid' ? s.W : s.L;     // габарит вдоль досок
    var across = s.dir === 'wid' ? s.L : s.W;    // габарит поперёк
    var useful = (s.bw + s.gap) / 1000;
    var k = 1 + s.res / 100;
    var stagger = s.pattern === 'stagger';
    var r = { area: area, perimeter: perimeter, along: along, across: across, useful: useful, diag: diag, polys: polys, base: base,
      poly: sh.poly ? base[0] : null, rectsBase: sh.poly ? null : sh.rects(s), stagger: stagger };

    r.step = s.step / 100 * (diag ? 0.7 : 1);

    // Доски
    if (!diag) {
      r.rows = ceil(across / useful);
      r.offset = stagger ? staggerOffset(s.bl, r.step) : 0;
      var raw = 0, rowSegs = [], rowCuts = [], pool = [];
      for (var i = 0; i < r.rows; i++) {
        var segs = bandSegs(polys, i * useful, Math.min((i + 1) * useful, across));
        rowSegs.push(segs);
        rowCuts.push(segs.map(function (sg) {
          var len = sg[1] - sg[0];
          var ps = stagger && len <= s.bl + 1e-6 ? shortRowPieces(sg[0], len, r.step, i % 2) : rowPieces(len, s.bl, stagger && i % 2 ? r.offset : 0);
          // целые доски — новые; укороченный кусок берём из подходящего обрезка, иначе режем новую доску
          ps.forEach(function (p) {
            if (p >= s.bl - 1e-6) { raw++; return; }
            var best = -1;
            pool.forEach(function (o, j) { if (o >= p - 1e-6 && (best < 0 || o < pool[best])) best = j; });
            var rest;
            if (best >= 0) { rest = pool[best] - p; pool.splice(best, 1); } else { raw++; rest = s.bl - p; }
            if (rest >= 0.3) pool.push(rest);
          });
          return ps;
        }));
      }
      r.rowSegs = rowSegs;
      r.rowCuts = rowCuts;   // куски досок по рядам и отрезкам — по ним рисуются стыки
      r.perRow = ceil(along / s.bl);
      r.raw = raw;
      r.total = ceil(raw * k);
    } else {
      r.run = area / useful;
      r.raw = ceil(r.run / s.bl);
      r.total = ceil(r.run / s.bl * k);
    }

    // Лаги: линии поперёк досок с шагом step
    var n = ceil(along / r.step) + 1;
    var lines = [];
    for (var j = 0; j < n; j++) {
      var x = Math.min(j * r.step, along);
      var xs = Math.min(Math.max(x, 1e-6), along - 1e-6);
      var ys = cut(polys, 'x', xs);
      if (ys.length) lines.push({ x: x, segs: ys, len: ys.reduce(function (t, q) { return t + q[1] - q[0]; }, 0) });
    }
    r.lines = lines;
    r.joists = lines.length;
    r.joistSegs = lines.reduce(function (t, l) { return t + l.segs.length; }, 0);
    r.joistMeters = lines.reduce(function (t, l) { return t + l.len; }, 0);
    r.joistLen = across;
    // Диагональ: стыки в рядах — на последней лаге, до которой достаёт доска; в нечётных рядах
    // первый кусок вдвое короче, чтобы стыки соседних рядов не шли в линию. Под стыками — вторая лага, как в прямой укладке.
    r.diagJoints = [];
    if (diag) {
      // стыки сводятся на редкую сетку линий лаг (шаг g — около полудоски), иначе вторая лага
      // понадобилась бы почти под каждой линией; соседние ряды — через одну линию сетки
      var dd = useful * Math.SQRT2, mx = s.bl / Math.SQRT2, g = Math.max(1, Math.floor(mx / 2 / r.step + 1e-9)) * r.step;
      for (var k2 = 0; k2 * dd < s.L + s.W && k2 < 2000; k2++) {
        diagSegs(polys, (k2 + 0.5) * dd).forEach(function (sg) {
          var pos = sg[0], lim = k2 % 2 ? mx / 2 : mx;
          while (sg[1] - pos > lim + 1e-6) {
            var jx = Math.floor((pos + lim) / g + 1e-9) * g;
            if (jx <= pos + 1e-6) jx = pos + lim;
            r.diagJoints.push({ x: jx, c0: k2 * dd, c1: (k2 + 1) * dd });
            pos = jx; lim = mx;
          }
        });
      }
    }
    // Двойные лаги: стык двух досок лежит на ближайшей линии лаг, под ним ставится вторая лага,
    // чтобы у каждого торца была своя опора. Удваивается отрезок линии, на который приходится хотя бы один стык.
    var dbl = {}, jointCount = 0;
    if (!diag && lines.length) {
      r.rowCuts.forEach(function (row, i) {
        var yc = Math.min((i + 0.5) * useful, across - 1e-6);
        row.forEach(function (ps, si) {
          var x = r.rowSegs[i][si][0];
          ps.slice(0, -1).forEach(function (p) {
            x += p; jointCount++;
            var li = 0;
            lines.forEach(function (l, k) { if (Math.abs(l.x - x) < Math.abs(lines[li].x - x)) li = k; });
            lines[li].segs.forEach(function (q, qi) { if (yc >= q[0] - 1e-6 && yc <= q[1] + 1e-6) dbl[li + ':' + qi] = 1; });
          });
        });
      });
    }
    // то же для диагонали: стык лежит на своей линии лаг, удваивается отрезок под серединой ряда
    if (diag && lines.length) {
      r.diagJoints.forEach(function (jt) {
        var yc = (jt.c0 + jt.c1) / 2 - jt.x, li = 0;
        jointCount++;
        lines.forEach(function (l, k) { if (Math.abs(l.x - jt.x) < Math.abs(lines[li].x - jt.x)) li = k; });
        lines[li].segs.forEach(function (q, qi) { if (yc >= q[0] - 1e-6 && yc <= q[1] + 1e-6) dbl[li + ':' + qi] = 1; });
      });
    }
    r.joints = jointCount;
    r.extra = [];   // вторые лаги под стыками: { x, seg: [y0, y1] }
    Object.keys(dbl).forEach(function (key) { var p = key.split(':'), l = lines[+p[0]]; r.extra.push({ x: l.x, seg: l.segs[+p[1]] }); });
    r.extraLines = r.extra.reduce(function (t, e) { return t.indexOf(e.x) < 0 ? t.concat([e.x]) : t; }, []).length;
    r.extraMeters = r.extra.reduce(function (t, e) { return t + e.seg[1] - e.seg[0]; }, 0);
    r.joistMeters += r.extraMeters;
    if (s.lagLen > 0) {
      r.lagPieces = lines.reduce(function (t, l) { return t + l.segs.reduce(function (u, q) { return u + ceil((q[1] - q[0]) / s.lagLen); }, 0); }, 0) +
        r.extra.reduce(function (t, e) { return t + ceil((e.seg[1] - e.seg[0]) / s.lagLen); }, 0);
    }

    // Клипсы: пересечения доска × лага
    var cross = 0;
    if (!diag) {
      r.rowSegs.forEach(function (segs) {
        lines.forEach(function (l) { segs.forEach(function (sg) { if (l.x >= sg[0] - 1e-6 && l.x <= sg[1] + 1e-6) cross++; }); });
      });
    } else {
      lines.forEach(function (l) { l.segs.forEach(function (q) { cross += ceil((q[1] - q[0]) / (useful * Math.SQRT2)); }); });
    }
    cross += jointCount;   // у стыка второй торец крепится к своей лаге
    r.cross = cross;
    r.clips = ceil(cross * 1.1);
    r.startClips = (r.joistSegs + r.extra.length) * 2;

    // Торцевая планка 2 м
    r.planks = ceil(perimeter / 2);
    r.boardMeters = r.total * s.bl;

    // Опоры: по каждой лаге с шагом supStep, плюс опора на конце
    if (s.supStep > 0) {
      var st = s.supStep / 100;
      r.supports = lines.reduce(function (t, l) { return t + l.segs.reduce(function (u, q) { return u + ceil((q[1] - q[0]) / st) + 1; }, 0); }, 0) +
        r.extra.reduce(function (t, e) { return t + ceil((e.seg[1] - e.seg[0]) / st) + 1; }, 0);
      r.supStepM = st;
    }

    // Обрезки (только прямоугольник и прямая укладка, как в ТЗ)
    r.waste = null;
    if (!diag && s.lengths && s.lengths.length) {
      var rem = along - Math.floor(along / s.bl + EPS) * s.bl;
      if (rem > 0.001 && rem < 0.3 * s.bl) {
        var best = null;
        s.lengths.forEach(function (Lb) {
          var w = ceil(along / Lb) * Lb - along;
          if (!best || w < best.w - 1e-6) best = { Lb: Lb, w: w };
        });
        var curW = ceil(along / s.bl) * s.bl - along;
        r.waste = { rem: rem, curW: curW, best: best && best.w < curW - 1e-6 ? best : null };
      }
    }
    return r;
  }

  return { SHAPES: SHAPES, calc: calc, cut: cut, validShape: validShape, staggerOffset: staggerOffset, rowPieces: rowPieces };
})();

(function () {
  var DATA = window.LTD_CALC_DATA;
  var SITE = 'https://latitudo.ru/catalog/';
  var I = DATA.I;
  var root = document.getElementById('ltdCalc'); if (!root) return;
  var $ = function (id) { return document.getElementById(id); };
  var EPS = 1e-9, ceil = function (x) { return Math.ceil(x - EPS); };
  var resTouched = false, last = null, drag = null, excluded = {};
  /* Сколько штук в упаковке (на сайте не указано): дополняйте по мере уточнения */
  var PACKS = [[/Кляймер Latitudo Фикс/i, 100]];
  /* Стартовые клипсы для всех досок: Кляймер стартовый Latitudo 6мм */
  var START_CLIP = I.findIndex(function (it) { return it[0] === 'Кляймер стартовый Latitudo 6мм'; });
  function packSize(name) { for (var i = 0; i < PACKS.length; i++) if (PACKS[i][0].test(name)) return PACKS[i][1]; return 0; }

  function num(v) { var n = parseFloat(String(v).replace(',', '.')); return isFinite(n) ? n : NaN; }
  function fmt(n, d) { return Number(n).toLocaleString('ru-RU', { minimumFractionDigits: d || 0, maximumFractionDigits: d == null ? 2 : d }); }
  function rub(n) { return fmt(Math.round(n), 0) + ' ₽'; }
  function plural(n, a, b, c) { n = Math.abs(n) % 100; var n1 = n % 10; if (n > 10 && n < 20) return c; if (n1 > 1 && n1 < 5) return b; if (n1 === 1) return a; return c; }
  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function opt(v, t, sel) { return '<option value="' + esc(v) + '"' + (sel ? ' selected' : '') + '>' + esc(t) + '</option>'; }
  function link(it) { return '<a href="' + SITE + it[7] + '" target="_blank" rel="noopener">' + esc(it[0]) + '</a>'; }

  /* ---------- Формы ---------- */
  var SHAPE_ICONS = {
    rect: '<rect x="4" y="5" width="40" height="24"/>',
    lshape: '<path d="M4 5h26v10h14v14H4z"/>',
    ushape: '<path d="M4 5h12v12h16V5h12v24H4z"/>',
    tshape: '<path d="M4 5h40v10H31v14H17V15H4z"/>',
    trap: '<path d="M14 5h20l10 24H4z"/>',
    bevel: '<path d="M4 5h28l12 10v14H4z"/>',
    pool: '<rect x="4" y="5" width="40" height="24"/><rect x="16" y="12" width="16" height="10"/>'
  };
  var CUT = {
    lshape: ['Вырез по длине a, м', 'Вырез по ширине b, м'],
    ushape: ['Вырез по длине a, м', 'Вырез по ширине b, м'],
    tshape: ['Ширина ножки a, м', 'Глубина верха b, м'],
    trap: ['Верхняя сторона a, м'],
    bevel: ['Скос по длине a, м', 'Скос по ширине b, м'],
    pool: ['Бассейн по длине a, м', 'Бассейн по ширине b, м']
  };
  var SHAPE_LABEL = { rect: 'Прямо­угольник', lshape: 'Г-образная', ushape: 'П-образная', tshape: 'Т-образная', trap: 'Трапеция', bevel: 'Скошенный угол', pool: 'Вокруг бассейна' };
  $('ltdShapes').innerHTML = Object.keys(LtdCore.SHAPES).map(function (k, i) {
    return '<label lang="ru"><input type="radio" name="ltdShape" value="' + k + '"' + (i ? '' : ' checked') + '><span><svg viewBox="0 0 48 34" aria-hidden="true">' + SHAPE_ICONS[k] + '</svg>' + SHAPE_LABEL[k] + '</span></label>';
  }).join('');

  /* ---------- Каталог: бренд + список досок бренда с поиском ---------- */
  var SW = [[/венге|тёмно-кор|темно-кор|эбен|чёрн|черн|ebony/i, '#3f2c22'], [/антрацит|графит|уголь/i, '#3b3b3d'], [/сер|grey|gray|кварц/i, '#8c8c8a'],
    [/шоколад|орех|walnut|каштан|кофе|мокка/i, '#5b3a27'], [/натур|бежев|песоч|светл|сосна|клён|клен/i, '#c3a27a'], [/тик|кедр|cedar|дуб|oak|амбар|мербау/i, '#9a6b42'], [/бел/i, '#e6e2dc']];
  function swatch(name) { for (var i = 0; i < SW.length; i++) if (SW[i][0].test(name)) return SW[i][1]; return '#8a5a3c'; }
  var sel = { m: 0, c: 0, o: 0 };
  var DEF_BRAND = Math.max(0, DATA.B.findIndex(function (b) { return b.n === 'EasyDecking'; }));   // бренд по умолчанию
  var STEP_MAX = 40;                                                                                  // шаг лаг по осям, см
  var GAP_MAX = 5;                                                                                    // зазор между досками, мм
  var DEF_COLOR = 'Венге';                                                                            // и его цвет
  $('ltdBrand').innerHTML = DATA.B.map(function (b, i) { return opt(i, b.n + ' (' + b.m.reduce(function (t, m) { return t + m.c.length; }, 0) + ')', i === DEF_BRAND); }).join('') + opt('own', 'Своя доска (ввести размеры)');
  function brand() { var v = $('ltdBrand').value; return v === 'own' ? null : DATA.B[+v]; }
  function model() { var b = brand(); return b ? b.m[sel.m] : null; }
  function color() { var m = model(); return m ? m.c[sel.c] : null; }
  function offer() { var c = color(); return c ? c.o[sel.o] || c.o[0] : null; }
  function boardTitle(b, m, c, o) { return b.n + ' ' + m.n + ' ' + m.w + '×' + m.t + '×' + o[0] + (c.n ? ' ' + c.n : ''); }
  function fillModels() {
    var b = brand(); if (!b) return;
    var mi = Math.max(0, b.m.findIndex(function (m) { return m.n === 'Евро'; }));
    var ci = b === DATA.B[DEF_BRAND] ? Math.max(0, b.m[mi].c.findIndex(function (c) { return c.n === DEF_COLOR; })) : 0;
    var oi = Math.max(0, b.m[mi].c[ci].o.findIndex(function (o) { return nominal(o[0]) === 4000; }));
    sel = { m: mi, c: ci, o: oi }; $('ltdFind').value = '';
    renderList(); fillLengths(); fillAcc();
    var on = $('ltdList').querySelector('[aria-selected=true]');
    if (on) $('ltdList').scrollTop = on.offsetTop - $('ltdList').offsetTop - 4;
  }
  function renderList() {
    var b = brand(); if (!b) return;
    var q = $('ltdFind').value.trim().toLowerCase().split(/\s+/).filter(Boolean), html = '', n = 0;
    b.m.forEach(function (m, mi) {
      m.c.forEach(function (c, ci) {
        var img = '<span class="ltd-thumb' + (c.i ? ' has-img' : '') + '" style="--ltd-sw:' + swatch(c.n) + '">' + (c.i ? '<img src="' + esc(c.i) + '" alt="" loading="lazy" onerror="this.parentNode.classList.remove(\'has-img\');this.remove()">' : '') + '</span>';
        c.o.forEach(function (o, oi) {
          var t = o[2] || ('Террасная доска ' + boardTitle(b, m, c, o));
          if (q.length && !q.every(function (w) { return t.toLowerCase().indexOf(w) >= 0; })) return;
          if (lenFilter && nominal(o[0]) !== lenFilter) return;
          var on = mi === sel.m && ci === sel.c && oi === sel.o;
          html += '<button type="button" class="ltd-opt" role="option" aria-selected="' + on + '" data-m="' + mi + '" data-c="' + ci + '" data-o="' + oi + '">' + img +
            '<span>' + esc(t) + '<small>длина ' + fmt(o[0] / 1000) + ' м · ' + rub(o[1] / (o[0] / 1000)) + '/п.м</small></span><b>' + rub(o[1]) + '</b></button>';
          n++;
        });
      });
    });
    $('ltdList').innerHTML = n ? html : '<div class="ltd-empty">Ничего не нашлось. Попробуйте другое слово.</div>';
  }
  $('ltdList').addEventListener('click', function (ev) {
    var o = ev.target.closest('.ltd-opt'); if (!o) return;
    sel = { m: +o.getAttribute('data-m'), c: +o.getAttribute('data-c'), o: +o.getAttribute('data-o') };
    $('ltdList').querySelectorAll('.ltd-opt').forEach(function (x) { x.setAttribute('aria-selected', x === o ? 'true' : 'false'); });
    fillLengths(); fillAcc(); render();
  });
  $('ltdFind').addEventListener('input', function (ev) { ev.stopPropagation(); renderList(); });
  /* Длина доски: в каталоге это фильтр списка (мм, 0 = все), для своей доски — длина в метрах */
  var lenFilter = 0;
  /* 3010 мм → 3000, 4010 → 4000: номинальная длина для фильтра (в расчёте остаётся точная) */
  function nominal(mm) { return Math.floor(mm / 100) * 100; }
  function fillLengths() {
    var b = brand(), prev = $('ltdBLsel').value;
    if (b) {
      var set = {}; b.m.forEach(function (m) { m.c.forEach(function (c) { c.o.forEach(function (o) { set[nominal(o[0])] = 1; }); }); });
      var ls = Object.keys(set).map(Number).sort(function (x, y) { return x - y; });
      if (lenFilter && ls.indexOf(lenFilter) < 0) lenFilter = 0;
      $('ltdBLsel').innerHTML = opt('all', 'Все длины', !lenFilter) + ls.map(function (l) { return opt(l, fmt(l / 1000) + ' м', l === lenFilter); }).join('');
    } else {
      var list = [3, 4, 6], def = ['3', '4', '6', 'custom'].indexOf(prev) >= 0 ? prev : '4';
      $('ltdBLsel').innerHTML = list.map(function (l) { return opt(l, l + ' м', String(l) === def); }).join('') + opt('custom', 'Своя длина', def === 'custom');
    }
  }
  $('ltdBLsel').addEventListener('change', function () {
    if (!brand()) return;
    var v = $('ltdBLsel').value; lenFilter = v === 'all' ? 0 : +v;
    var c = color();
    if (lenFilter && c) { var oi = c.o.findIndex(function (o) { return nominal(o[0]) === lenFilter; }); if (oi >= 0) sel.o = oi; }
    renderList(); fillAcc();
  });
  function accOf(kind) {
    var c = color(), own = c && c.a[kind] ? c.a[kind] : [];
    var all = []; I.forEach(function (it, i) { if (it[6] === kind && own.indexOf(i) < 0 && !(kind === 'start' && /доска/i.test(it[0]))) all.push(i); });
    return { own: own.filter(function (i) { return !(kind === 'start' && /доска/i.test(I[i][0])); }), rest: all };
  }
  function fillSelect(id, kind, label) {
    var a = accOf(kind), html = '';
    if (a.own.length) html += '<optgroup label="Рекомендуем к этой доске">' + a.own.map(function (i) { return opt(i, label(I[i])); }).join('') + '</optgroup>';
    html += '<optgroup label="Другие">' + a.rest.map(function (i) { return opt(i, label(I[i])); }).join('') + '</optgroup>';
    html += opt('none', 'Без позиции в смете');
    $(id).innerHTML = html;
  }
  function fillAcc() {
    fillSelect('ltdLag', 'lag', function (it) { return it[0] + ' · ' + rub(it[1]); });
    fillSupports();
  }
  function fillSupports() {
    var a = accOf('op'), h = num($('ltdH').value), list = a.own.length ? a.own : a.rest;
    var fit = list.filter(function (i) { return h >= I[i][4] && h <= I[i][5]; });
    var pick = fit[0] != null ? fit[0] : (list[0] != null ? list[0] : null);
    var html = list.map(function (i) { return opt(i, I[i][0].replace('Регулируемая опора ', '') + ' · ' + rub(I[i][1]), i === pick); }).join('');
    var others = a.own.length ? a.rest : [];
    if (others.length) html += '<optgroup label="Другие">' + others.map(function (i) { return opt(i, I[i][0].replace('Регулируемая опора ', '') + ' · ' + rub(I[i][1])); }).join('') + '</optgroup>';
    $('ltdSup').innerHTML = html + opt('none', 'Без позиции в смете');
  }
  /* Комплектующее к доске: сначала из «С этим товаром покупают», иначе того же бренда (и цвета, если есть) из каталога */
  var pickGuess = {};
  function pickIdx(kind) {
    var a = accOf(kind); pickGuess[kind] = false;
    if (a.own.length) return a.own[0];
    var b = brand(), c = color(); if (!b) return null;
    var key = b.n.split(' ')[0].toLowerCase(), col = c && c.n ? c.n.toLowerCase().split(/[\s/]+/)[0] : '';
    var same = a.rest.filter(function (i) { return I[i][0].toLowerCase().indexOf(key) >= 0; });
    if (!same.length) return null;
    var byColor = col ? same.filter(function (i) { return I[i][0].toLowerCase().indexOf(col) >= 0; }) : [];
    pickGuess[kind] = !byColor.length && kind === 'corner';
    return (byColor.length ? byColor : same)[0];
  }

  /* ---------- Ввод ---------- */
  function read() {
    var g = function (n) { var r = root.querySelector('input[name=' + n + ']:checked'); return r ? r.value : null; };
    var c = color(), off = offer(), blSel = c ? '' : $('ltdBLsel').value;
    var bl = c ? off[0] / 1000 : (blSel === 'custom' ? num($('ltdBL').value) : num(blSel));
    var lagSel = $('ltdLag').value, supSel = $('ltdSup').value;
    return {
      shape: g('ltdShape'), dir: g('ltdDir'), pattern: g('ltdPattern'), L: num($('ltdL').value), W: num($('ltdW').value), a: num($('ltdA').value), b: num($('ltdB').value),
      own: !c, bw: c ? model().w : num($('ltdBW').value), bt: c ? model().t : null, bl: bl, blCustom: blSel === 'custom',
      lengths: c ? c.o.map(function (o) { return o[0] / 1000; }) : [3, 4, 6],
      price: c ? off[1] : ($('ltdPrice').value === '' ? 0 : num($('ltdPrice').value)),
      gap: num($('ltdGap').value), res: num($('ltdRes').value), step: num($('ltdStep').value),
      lagOn: $('ltdLagOn').checked, lag: lagSel === 'none' ? null : +lagSel,
      supOn: $('ltdSupOn').checked, sup: supSel === 'none' ? null : +supSel, h: num($('ltdH').value), supStepCm: num($('ltdSupStep').value)
    };
  }
  function validate(s) {
    var e = {};
    if (!(s.L >= 0.5 && s.L <= 50)) e.L = 'От 0,5 до 50 м';
    if (!(s.W >= 0.5 && s.W <= 50)) e.W = 'От 0,5 до 50 м';
    if (LtdCore.SHAPES[s.shape].params.length) {
      if (!(s.a > 0)) e.a = 'Положительное число'; if (LtdCore.SHAPES[s.shape].params.length > 1 && !(s.b > 0)) e.b = 'Положительное число';
      var m = LtdCore.validShape(s); if (m && !e.a && !e.b) e.a = m;
    }
    if (!(s.bw > 0)) e.bw = 'Положительное число';
    if (s.blCustom && !(s.bl > 0)) e.bl = 'Положительное число';
    if (!(s.gap >= 0)) e.gap = 'Не меньше 0'; else if (s.gap > GAP_MAX) e.gap = 'Не больше ' + GAP_MAX + ' мм';
    if (!(s.res >= 0)) e.res = 'Не меньше 0';
    if (!(s.step >= 10)) e.step = 'Не меньше 10 см'; else if (s.step > STEP_MAX) e.step = 'Не больше ' + STEP_MAX + ' см';
    if (!(s.price >= 0)) e.price = 'Не меньше 0';
    if (s.supOn) { if (!(s.h > 0)) e.h = 'Положительное число'; if (!(s.supStepCm >= 10)) e.sup = 'Не меньше 10 см'; }
    return e;
  }
  function showErrors(e) {
    root.querySelectorAll('.ltd-field[data-f]').forEach(function (f) {
      var msg = e[f.getAttribute('data-f')] || ''; f.classList.toggle('is-bad', !!msg);
      var em = f.querySelector('.ltd-err'); if (em) em.textContent = msg;
    });
    // в строках «Лаги» и «Опоры» ошибка выводится не под полем, а в пояснении под строкой, чтобы строка не прыгала
    var stepHint = $('ltdStepHint'), supHint = $('ltdSupHint'), supMsg = e.h || e.sup;
    stepHint.classList.toggle('is-bad', !!e.step); stepHint.textContent = e.step ? 'Шаг лаг: ' + e.step.toLowerCase() : 'Шаг лаг по осям, не больше ' + STEP_MAX + ' см.';
    supHint.classList.toggle('is-bad', !!supMsg); supHint.textContent = supMsg ? (e.h ? 'Высота опоры: ' : 'Шаг опор: ') + supMsg.toLowerCase() : SUP_HINT;
  }
  var SUP_HINT = $('ltdSupHint').textContent;

  /* ---------- Смета ---------- */
  function estimate(s, r) {
    var rows = [], c = color(), off = offer();
    var boardName = c ? (off[2] || 'Террасная доска ' + boardTitle(brand(), model(), c, off)) : 'Террасная доска ' + fmt(s.bw) + ' мм, ' + fmt(s.bl) + ' м';
    rows.push({ key: 'board', name: c ? '<a href="' + SITE + 'terrasnaya-doska-iz-dpk/' + c.u + '/" target="_blank" rel="noopener">' + esc(boardName) + '</a>' : esc(boardName), text: boardName,
      qty: r.total, unit: 'шт', price: s.price || null, sub: 'с запасом ' + fmt(s.res) + '%, без запаса ' + r.raw + ' · ' + fmt(r.boardMeters, 1) + ' пог. м · ' + fmt(r.area, 2) + ' м²' });
    if (s.lagOn) {
      var lg = s.lag != null ? I[s.lag] : null;
      if (lg && lg[3]) {
        rows.push({ key: 'lag', name: link(lg), text: lg[0], qty: r.lagPieces, unit: 'шт', price: lg[1], sub: joistText(r) });
      } else {
        rows.push({ key: 'lag', name: 'Лаги', text: 'Лаги', qty: r.joists + r.extra.length, unit: 'шт', price: null, sub: 'по ' + fmt(r.joistLen, 2) + ' м, ' + joistText(r) });
      }
    }
    var cl = pickIdx('clip'), st = START_CLIP >= 0 ? START_CLIP : pickIdx('start');
    function clipRow(idx, qty, fallback, sub) {
      var it = idx != null ? I[idx] : null;
      if (it && it[2] === 'уп') {
        var pack = packSize(it[0]);
        if (pack) return { name: link(it), text: it[0], qty: ceil(qty / pack), unit: 'уп.', price: it[1], sub: sub + ' · нужно ' + fmt(qty) + ' шт, в упаковке ' + pack + ' шт' };
        return { name: link(it), text: it[0], qty: qty, unit: 'шт', price: null, sub: sub + ' · упаковка ' + rub(it[1]) + ', фасовку уточнит менеджер' };
      }
      return { name: it ? link(it) : fallback, text: it ? it[0] : fallback, qty: qty, unit: 'шт', price: it ? it[1] : null, sub: sub };
    }
    var x1 = clipRow(cl, r.clips, 'Клипсы (кляммеры)', fmt(r.cross) + ' пересечений + 10%'); x1.key = 'clip'; rows.push(x1);
    var x2 = clipRow(st, r.startClips, 'Стартовые клипсы', '2 на каждую лагу'); x2.key = 'start'; rows.push(x2);
    var cor = pickIdx('corner');
    if (cor != null && I[cor][3]) {
      var Lc = I[cor][3] / 1000, n = ceil(r.perimeter / Lc);
      rows.push({ key: 'corner', name: link(I[cor]), text: I[cor][0], qty: n, unit: 'шт', price: I[cor][1], sub: 'торцевая отделка по периметру ' + fmt(r.perimeter, 1) + ' м' + (pickGuess.corner ? ' · подобран по бренду, цвет уточните' : '') });
    } else {
      rows.push({ key: 'corner', name: 'Торцевая планка / уголок 2 м', text: 'Торцевая планка 2 м', qty: r.planks, unit: 'шт', price: null, sub: 'периметр ' + fmt(r.perimeter, 1) + ' м' });
    }
    if (s.supOn && r.supports) {
      var sp = s.sup != null ? I[s.sup] : null;
      rows.push({ key: 'sup', name: sp ? link(sp) : 'Регулируемые опоры', text: sp ? sp[0] : 'Регулируемые опоры', qty: r.supports, unit: 'шт', price: sp ? sp[1] : null,
        sub: 'шаг ' + fmt(s.supStepCm) + ' см по каждой лаге' + (sp && !(s.h >= sp[4] && s.h <= sp[5]) ? ' · высота ' + s.h + ' мм вне диапазона опоры' : '') });
    }
    rows.forEach(function (x) { x.sum = x.price != null ? x.qty * x.price : null; });
    return rows;
  }

  /* ---------- План ---------- */
  function P(r, s, along, across) { return s.dir === 'wid' ? [across, along] : [along, across]; }
  function frame(s, VW, VH, pad) {
    var sc = drag ? drag.sc : Math.min((VW - pad[3] - pad[1]) / s.L, (VH - pad[0] - pad[2]) / s.W);
    var w = s.L * sc, h = s.W * sc;
    var x0 = drag ? drag.x0 : pad[3] + (VW - pad[3] - pad[1] - w) / 2, y0 = drag ? drag.y0 : pad[0] + (VH - pad[0] - pad[2] - h) / 2;
    return { sc: sc, x0: x0, y0: y0, w: w, h: h, X: function (x) { return x0 + x * sc; }, Y: function (y) { return y0 + y * sc; } };
  }
  function shapePath(r, f) {
    if (r.poly) return '<path d="' + r.poly.map(function (p, i) { return (i ? 'L' : 'M') + f.X(p[0]) + ' ' + f.Y(p[1]); }).join('') + 'Z"/>';
    return r.rectsBase.map(function (q) { return '<rect x="' + f.X(q[0]) + '" y="' + f.Y(q[1]) + '" width="' + (q[2] * f.sc) + '" height="' + (q[3] * f.sc) + '"/>'; }).join('');
  }
  /* Стороны контура: у многоугольника — его рёбра (обход по часовой, нормаль наружу), иначе — по прямоугольникам */
  function edges(r) {
    if (r.poly) return r.poly.map(function (p, i) {
      var q = r.poly[(i + 1) % r.poly.length], len = Math.hypot(q[0] - p[0], q[1] - p[1]);
      return { x1: p[0], y1: p[1], x2: q[0], y2: q[1], nx: (q[1] - p[1]) / len, ny: -(q[0] - p[0]) / len };
    });
    return rectEdges(r.rectsBase);
  }
  /* Стороны контура объединения прямоугольников: отрезки с нормалью наружу */
  function rectEdges(base) {
    var xs = [], ys = [], eps = 1e-6;
    base.forEach(function (q) { xs.push(q[0], q[0] + q[2]); ys.push(q[1], q[1] + q[3]); });
    var uniq = function (a) { a.sort(function (m, n) { return m - n; }); return a.filter(function (v, i) { return !i || v - a[i - 1] > eps; }); };
    xs = uniq(xs); ys = uniq(ys);
    function inside(x, y) { return base.some(function (q) { return x > q[0] + eps && x < q[0] + q[2] - eps && y > q[1] + eps && y < q[1] + q[3] - eps; }); }
    var cell = function (i, j) { return i >= 0 && j >= 0 && i < xs.length - 1 && j < ys.length - 1 && inside((xs[i] + xs[i + 1]) / 2, (ys[j] + ys[j + 1]) / 2); };
    var out = [];
    function push(x1, y1, x2, y2, nx, ny) {
      var l = out[out.length - 1];
      if (l && l.nx === nx && l.ny === ny && Math.abs(l.x2 - x1) < eps && Math.abs(l.y2 - y1) < eps) { l.x2 = x2; l.y2 = y2; }
      else out.push({ x1: x1, y1: y1, x2: x2, y2: y2, nx: nx, ny: ny });
    }
    var i, j;
    for (i = 0; i < xs.length; i++) for (j = 0; j < ys.length - 1; j++) {
      var L = cell(i - 1, j), R = cell(i, j);
      if (L !== R) push(xs[i], ys[j], xs[i], ys[j + 1], R ? -1 : 1, 0);
    }
    for (j = 0; j < ys.length; j++) for (i = 0; i < xs.length - 1; i++) {
      var T = cell(i, j - 1), B = cell(i, j);
      if (T !== B) push(xs[i], ys[j], xs[i + 1], ys[j], 0, B ? -1 : 1);
    }
    return out;
  }
  function outline(r, f) {
    return edges(r).map(function (e) { return 'M' + f.X(e.x1) + ' ' + f.Y(e.y1) + 'L' + f.X(e.x2) + ' ' + f.Y(e.y2); }).join('');
  }
  /* подписи длины каждой стороны (кроме общих габаритов сверху и слева: они в dims) */
  function edgeLabels(s, r, f) {
    var o = '', eps = 1e-6;
    edges(r).forEach(function (e) {
      var len = Math.hypot(e.x2 - e.x1, e.y2 - e.y1);
      if (len * f.sc < 26) return;
      if (Math.abs(e.ny + 1) < eps && Math.abs(e.y1) < eps && Math.abs(len - s.L) < eps) return;
      if (Math.abs(e.nx + 1) < eps && Math.abs(e.x1) < eps && Math.abs(len - s.W) < eps) return;
      // у верхней и левой кромки снаружи стоят общие габариты — подпись части стороны ставим внутрь
      var inner = (Math.abs(e.ny + 1) < eps && Math.abs(e.y1) < eps) || (Math.abs(e.nx + 1) < eps && Math.abs(e.x1) < eps) ? -1 : 1;
      var mx = f.X((e.x1 + e.x2) / 2) + e.nx * 9 * inner, my = f.Y((e.y1 + e.y2) / 2) + e.ny * 9 * inner;
      var txt = fmt(Math.round(len * 1000)) + ' мм';
      var attr = 'fill="var(--ltd-ink)" font-size="10" text-anchor="middle" dominant-baseline="middle" paint-order="stroke" stroke="var(--ltd-panel)" stroke-width="3" style="font-variant-numeric:tabular-nums"';
      // подпись вдоль стороны: читается слева направо или снизу вверх
      var ang = Math.atan2(e.y2 - e.y1, e.x2 - e.x1) * 180 / Math.PI;
      if (ang > 90) ang -= 180; if (ang <= -90) ang += 180; if (Math.abs(ang - 90) < 1e-6) ang = -90;
      o += Math.abs(ang) > 0.01 ? '<text x="' + mx + '" y="' + my + '" ' + attr + ' transform="rotate(' + ang.toFixed(2) + ' ' + mx + ' ' + my + ')">' + txt + '</text>' : '<text x="' + mx + '" y="' + my + '" ' + attr + '>' + txt + '</text>';
    });
    return o;
  }
  function dims(s, f) {
    var t = 'fill="var(--ltd-muted)" font-size="11" text-anchor="middle" style="font-variant-numeric:tabular-nums"', ln = 'stroke="var(--ltd-muted)" stroke-width="1"';
    var o = '<path d="M' + f.x0 + ' ' + (f.y0 - 12) + 'H' + (f.x0 + f.w) + 'M' + f.x0 + ' ' + (f.y0 - 17) + 'V' + (f.y0 - 7) + 'M' + (f.x0 + f.w) + ' ' + (f.y0 - 17) + 'V' + (f.y0 - 7) + '" ' + ln + '/>';
    o += '<text x="' + (f.x0 + f.w / 2) + '" y="' + (f.y0 - 16) + '" ' + t + '>' + fmt(Math.round(s.L * 1000)) + ' мм</text>';
    o += '<path d="M' + (f.x0 - 12) + ' ' + f.y0 + 'V' + (f.y0 + f.h) + 'M' + (f.x0 - 17) + ' ' + f.y0 + 'H' + (f.x0 - 7) + 'M' + (f.x0 - 17) + ' ' + (f.y0 + f.h) + 'H' + (f.x0 - 7) + '" ' + ln + '/>';
    o += '<text x="' + (f.x0 - 16) + '" y="' + (f.y0 + f.h / 2) + '" ' + t + ' transform="rotate(-90 ' + (f.x0 - 16) + ' ' + (f.y0 + f.h / 2) + ')">' + fmt(Math.round(s.W * 1000)) + ' мм</text>';
    return o;
  }
  function cutCorner(s) {
    // точка, которую тянут, чтобы изменить вырез (в координатах террасы, м)
    if (s.shape === 'lshape') return [s.L - s.a, s.b];
    if (s.shape === 'ushape') return [(s.L + s.a) / 2, s.b];
    if (s.shape === 'tshape') return [(s.L + s.a) / 2, s.b];
    if (s.shape === 'trap') return [(s.L + s.a) / 2, 0];
    if (s.shape === 'bevel') return [s.L - s.a, s.b];
    if (s.shape === 'pool') return [(s.L + s.a) / 2, (s.W + s.b) / 2];
    return null;
  }
  function fixedFrame(s, VW, VH, pad) {
    var sc = Math.min((VW - pad[3] - pad[1]) / s.L, (VH - pad[0] - pad[2]) / s.W), w = s.L * sc, h = s.W * sc;
    var x0 = pad[3] + (VW - pad[3] - pad[1] - w) / 2, y0 = pad[0] + (VH - pad[0] - pad[2] - h) / 2;
    return { sc: sc, x0: x0, y0: y0, w: w, h: h, X: function (x) { return x0 + x * sc; }, Y: function (y) { return y0 + y * sc; } };
  }
  function boardLayer(s, r, f, clipId) {
    var u = r.useful, o = '';
    o += '<defs><clipPath id="' + clipId + '">' + shapePath(r, f) + '</clipPath></defs>';
    o += '<g clip-path="url(#' + clipId + ')"><rect x="' + f.x0 + '" y="' + f.y0 + '" width="' + f.w + '" height="' + f.h + '" fill="var(--ltd-board)"/>';
    var seamW = Math.max(0.6, Math.min(2, u * f.sc * 0.12));
    if (!r.diag) {
      r.rowSegs.forEach(function (segs, i) {
        var a0 = i * u, a1 = Math.min((i + 1) * u, r.across);
        segs.forEach(function (sg, si) {
          var p0 = P(r, s, sg[0], a0), p1 = P(r, s, sg[1], a1);
          var x = Math.min(p0[0], p1[0]), y = Math.min(p0[1], p1[1]), w = Math.abs(p1[0] - p0[0]), h = Math.abs(p1[1] - p0[1]);
          o += '<rect x="' + f.X(x) + '" y="' + f.Y(y) + '" width="' + (w * f.sc) + '" height="' + (h * f.sc) + '" fill="' + (i % 2 ? 'var(--ltd-board-2)' : 'var(--ltd-board)') + '"/>';
          // стыки — между кусками досок, как их разложило ядро (r.rowCuts)
          var ps = r.rowCuts[i][si], j = 0;
          ps.slice(0, -1).forEach(function (p) {
            j += p;
            var q0 = P(r, s, sg[0] + j, a0), q1 = P(r, s, sg[0] + j, a1);
            o += '<line x1="' + f.X(q0[0]) + '" y1="' + f.Y(q0[1]) + '" x2="' + f.X(q1[0]) + '" y2="' + f.Y(q1[1]) + '" stroke="var(--ltd-joint)" stroke-width="2"/>';
          });
        });
        if (i) { var e0 = P(r, s, 0, a0), e1 = P(r, s, r.along, a0); o += '<line x1="' + f.X(e0[0]) + '" y1="' + f.Y(e0[1]) + '" x2="' + f.X(e1[0]) + '" y2="' + f.Y(e1[1]) + '" stroke="var(--ltd-bg)" stroke-opacity=".8" stroke-width="' + seamW + '"/>'; }
      });
    } else {
      var d = u * Math.SQRT2 * f.sc;
      for (var p = -f.h, k = 0; p < f.w && k < 800; p += d, k++) o += '<line x1="' + (f.x0 + p) + '" y1="' + (f.y0 + f.h) + '" x2="' + (f.x0 + p + f.h) + '" y2="' + f.y0 + '" stroke="var(--ltd-bg)" stroke-opacity=".8" stroke-width="' + seamW + '"/>';
      // стыки — отрезок лаги поперёк ряда
      r.diagJoints.forEach(function (j) { o += '<line x1="' + f.X(j.x) + '" y1="' + f.Y(j.c0 - j.x) + '" x2="' + f.X(j.x) + '" y2="' + f.Y(j.c1 - j.x) + '" stroke="var(--ltd-joint)" stroke-width="2"/>'; });
    }
    return o + '</g>';
  }
  function joistText(r) {
    return r.joists + ' ' + plural(r.joists, 'линия', 'линии', 'линий') + (r.extraLines ? ' + ' + r.extraLines + ' ' + plural(r.extraLines, 'двойная', 'двойные', 'двойных') + ' под стыками' : '') +
      ', ' + fmt(r.joistMeters, 1) + ' пог. м, шаг ' + fmt(r.step * 100, 1) + ' см';
  }
  function joistLayer(s, r, f, withSup) {
    var o = '', jw = Math.max(1.5, Math.min(5, 0.04 * f.sc));
    r.extra.forEach(function (e) {
      var d = jw * 1.3 / f.sc, x = Math.min(e.x, r.along - 0.04) + (e.x + d > r.along - 0.04 ? -d : d);
      var a = P(r, s, x, e.seg[0]), b = P(r, s, x, e.seg[1]);
      if (s.dir === 'wid') o += '<rect x="' + f.X(a[0]) + '" y="' + (f.Y(a[1]) - jw / 2) + '" width="' + ((b[0] - a[0]) * f.sc) + '" height="' + jw + '" fill="var(--ltd-joint)"/>';
      else o += '<rect x="' + (f.X(a[0]) - jw / 2) + '" y="' + f.Y(a[1]) + '" width="' + jw + '" height="' + ((b[1] - a[1]) * f.sc) + '" fill="var(--ltd-joint)"/>';
    });
    r.lines.forEach(function (l) {
      var x = Math.min(l.x, r.along - 0.04);
      l.segs.forEach(function (q) {
        var a = P(r, s, x, q[0]), b = P(r, s, x, q[1]);
        if (s.dir === 'wid') o += '<rect x="' + f.X(a[0]) + '" y="' + (f.Y(a[1]) - jw / 2) + '" width="' + ((b[0] - a[0]) * f.sc) + '" height="' + jw + '" fill="var(--ltd-joist)"/>';
        else o += '<rect x="' + (f.X(a[0]) - jw / 2) + '" y="' + f.Y(a[1]) + '" width="' + jw + '" height="' + ((b[1] - a[1]) * f.sc) + '" fill="var(--ltd-joist)"/>';
        if (withSup && r.supStepM) {
          var L = q[1] - q[0], n = ceil(L / r.supStepM);
          for (var i = 0; i <= n; i++) { var t = Math.min(q[0] + i * r.supStepM, q[1]); var pt = P(r, s, x, t); o += '<circle cx="' + f.X(pt[0]) + '" cy="' + f.Y(pt[1]) + '" r="' + Math.max(1.8, Math.min(3.6, jw * .9)) + '" fill="var(--ltd-support)"/>'; }
        }
      });
    });
    return o;
  }
  function stepMark(s, r, f) {
    if (r.lines.length < 2) return '';
    var A = P(r, s, r.lines[0].x, 0), B = P(r, s, r.lines[1].x, 0);
    var ax = f.X(A[0]), ay = f.Y(A[1]) - 8, bx = f.X(B[0]), by = f.Y(B[1]) - 8;
    if (s.dir === 'wid') { ax = f.x0 - 8; bx = ax; ay = f.Y(A[1]); by = f.Y(B[1]); }
    return '<line x1="' + ax + '" y1="' + ay + '" x2="' + bx + '" y2="' + by + '" stroke="var(--ltd-accent)" stroke-width="1.5"/>' +
      '<text x="' + (s.dir === 'wid' ? ax - 4 : (ax + bx) / 2) + '" y="' + (s.dir === 'wid' ? (ay + by) / 2 : ay - 4) + '" fill="var(--ltd-accent)" font-size="11" text-anchor="' + (s.dir === 'wid' ? 'end' : 'middle') + '">' + fmt(r.step * 1000, 0) + ' мм</text>';
  }
  function drawBoards(s, r) {
    var f = frame(s, 640, 360, [34, 30, 20, 40]);
    var o = boardLayer(s, r, f, 'ltdClipB') + '<path d="' + outline(r, f) + '" fill="none" stroke="var(--ltd-ink)" stroke-width="1.6"/>' + dims(s, f) + edgeLabels(s, r, f);
    o += '<circle class="ltd-handle" data-h="LW" cx="' + f.X(s.L) + '" cy="' + f.Y(s.W) + '" r="8" fill="var(--ltd-accent)" stroke="var(--ltd-bg)" stroke-width="2"/>';
    var cc = cutCorner(s);
    if (cc) o += '<circle class="ltd-handle" data-h="ab" cx="' + f.X(cc[0]) + '" cy="' + f.Y(cc[1]) + '" r="7" fill="var(--ltd-bg)" stroke="var(--ltd-accent)" stroke-width="3"/>';
    $('ltdSvgBoards').innerHTML = o;
    $('ltdSvgBoards').__f = f;
  }
  function drawAll(s, r) {
    var f = fixedFrame(s, 640, 360, [34, 30, 20, 40]);
    $('ltdSvgAll').innerHTML = '<g opacity=".5">' + boardLayer(s, r, f, 'ltdClipA') + '</g>' + joistLayer(s, r, f, s.supOn) +
      '<path d="' + outline(r, f) + '" fill="none" stroke="var(--ltd-ink)" stroke-width="1.6"/>' + dims(s, f) + edgeLabels(s, r, f);
  }
  function drawJoists(s, r, svgId, withSup) {
    var f = fixedFrame(s, 400, 300, [34, 20, 20, 40]);
    var o = '<defs><clipPath id="' + svgId + 'C">' + shapePath(r, f) + '</clipPath></defs><g clip-path="url(#' + svgId + 'C)"><rect x="' + f.x0 + '" y="' + f.y0 + '" width="' + f.w + '" height="' + f.h + '" fill="var(--ltd-bg)"/></g>';
    o += joistLayer(s, r, f, withSup) + '<path d="' + outline(r, f) + '" fill="none" stroke="var(--ltd-ink)" stroke-width="1.4"/>' + stepMark(s, r, f);
    $(svgId).innerHTML = o;
  }

  /* ---------- Рендер ---------- */
  function render() {
    var s = read();
    var resDef = s.dir === 'diag' ? 15 : s.pattern === 'stagger' ? 10 : 7;
    if (!resTouched) { $('ltdRes').value = resDef; s.res = resDef; }
    $('ltdResHint').textContent = (s.dir === 'diag' ? 'Диагональ' : s.pattern === 'stagger' ? 'Вразбежку' : 'Прямой рисунок') + ': ' + resDef + '%';
    var cuts = CUT[s.shape];
    $('ltdCutBox').hidden = !cuts;
    if (cuts) { $('ltdALabel').textContent = cuts[0]; $('ltdBLabel').textContent = cuts[1] || ''; $('ltdCutHint').textContent = LtdCore.SHAPES[s.shape].hint; }
    root.querySelector('[data-f=b]').hidden = !cuts || cuts.length < 2;
    $('ltdPatternBox').hidden = s.dir === 'diag';
    $('ltdCatBox').hidden = s.own; $('ltdOwnBox').hidden = !s.own;
    $('ltdBLwrap').hidden = !s.blCustom;
    $('ltdLagWrap').classList.toggle('is-off', !s.lagOn);
    root.querySelector('[data-f=h]').classList.toggle('is-off', !s.supOn);
    root.querySelector('[data-f=sup]').classList.toggle('is-off', !s.supOn);
    $('ltdSupBox').hidden = !s.supOn;
    var c = color();
    $('ltdBoardInfo').innerHTML = c ? 'Ширина ' + model().w + ' мм, толщина ' + model().t + ' мм · ' + (s.price ? rub(s.price) + ' за доску' : 'цена по запросу') + ' · <a href="' + SITE + 'terrasnaya-doska-iz-dpk/' + c.u + '/" target="_blank" rel="noopener">карточка товара</a>' : '';

    var e = validate(s); showErrors(e);
    var bad = Object.keys(e).length > 0;
    $('ltdOut').classList.toggle('is-invalid', bad); $('ltdInvalid').hidden = !bad;
    if (bad) { $('ltdInvalid').textContent = 'Проверьте поля, отмеченные красным. Результат пересчитается автоматически.'; return; }

    var lg = s.lagOn && s.lag != null ? I[s.lag] : null;
    var r = LtdCore.calc({ shape: s.shape, L: s.L, W: s.W, a: s.a, b: s.b, dir: s.dir, pattern: s.pattern, bw: s.bw, gap: s.gap, bl: s.bl, res: s.res, step: s.step,
      lengths: s.lengths, supStep: s.supOn ? s.supStepCm : 0, lagLen: lg && lg[3] ? lg[3] / 1000 : 0 });
    // пояснение к рисунку: прямой без стыков — доски целиком; вразбежку на рядах короче доски — из двух кусков
    var joints = !r.diag && r.rowCuts.some(function (row) { return row.some(function (ps) { return ps.length > 1; }); });
    var shortRows = !r.diag && r.rowSegs.every(function (segs) { return segs.every(function (sg) { return sg[1] - sg[0] <= s.bl + 1e-6; }); });
    $('ltdPatternHint').textContent = r.diag ? '' : !joints ? 'Ряд не длиннее доски (' + fmt(s.bl, 2) + ' м): доски кладутся целиком, без стыков.'
      : s.pattern === 'stagger' && shortRows ? 'Ряд короче доски: каждый ряд собирается из двух кусков, стыки в соседних рядах разнесены. Обрезок одного куска идёт на следующий.' : '';
    $('ltdStepHint').textContent = 'Шаг лаг по осям, не больше ' + STEP_MAX + ' см.' + (r.diag ? ' Диагональ: × 0,7 = ' + fmt(r.step * 100, 1) + ' см.' : '');

    $('ltdBoards').innerHTML = fmt(r.total) + '<small>' + plural(r.total, 'доска', 'доски', 'досок') + ' по ' + fmt(s.bl) + ' м</small>';
    $('ltdBoardsSub').innerHTML = 'С запасом ' + fmt(s.res) + '%. Без запаса <b>' + fmt(r.raw) + '</b>' + (r.diag ? '' : ' · ' + r.rows + ' ' + plural(r.rows, 'ряд', 'ряда', 'рядов'));
    $('ltdArea').innerHTML = 'Площадь вашей террасы составит <b>' + fmt(r.area, 2) + ' м²</b>' + (s.shape !== 'rect' ? ' <span class="ltd-hint">(' + LtdCore.SHAPES[s.shape].name.toLowerCase() + ', габариты ' + fmt(s.L) + ' × ' + fmt(s.W) + ' м)</span>' : '');

    var rows = estimate(s, r), total = 0, priced = 0;
    $('ltdTable').innerHTML = rows.map(function (x) {
      x.off = !!excluded[x.key];
      if (x.sum != null && !x.off) { total += x.sum; priced++; }
      return '<tr' + (x.off ? ' class="is-off"' : '') + '><th scope="row"><div class="ltd-rowname"><input type="checkbox" class="ltd-on" data-key="' + x.key + '"' + (x.off ? '' : ' checked') + ' aria-label="Включить в смету"><div>' + x.name + '<div class="ltd-hint">' + x.sub + '</div></div></div></th><td class="q">' + fmt(x.qty) + ' ' + x.unit + '</td><td class="n">' + (x.price != null ? rub(x.price) : '—') + '</td><td class="n">' + (x.sum != null ? rub(x.sum) : '—') + '</td></tr>';
    }).join('');
    $('ltdFoot').innerHTML = priced ? '<tr><th scope="row" colspan="3">Итого по позициям с ценой</th><td class="n">' + rub(total) + '</td></tr>' : '';
    $('ltdSumBox').hidden = !priced; $('ltdSum').textContent = rub(total);

    var wb = $('ltdWaste');
    if (r.waste && s.shape === 'rect') {
      var msg = '<b>Много обрезков</b>В каждом ряду остаётся кусок ' + fmt(r.waste.rem, 2) + ' м, и на него уходит целая доска ' + fmt(s.bl) + ' м: отход ' + fmt(r.waste.curW, 2) + ' м с ряда.';
      if (r.waste.best) msg += ' С доской <b style="display:inline">' + fmt(r.waste.best.Lb) + ' м</b> отход будет ' + fmt(r.waste.best.w, 2) + ' м с ряда.';
      wb.innerHTML = msg; wb.hidden = false;
    } else wb.hidden = true;

    drawBoards(s, r);
    drawJoists(s, r, 'ltdSvgJoists', false);
    drawAll(s, r);
    TAB_CAP.all = s.supOn ? 'доски, лаги и опоры' : 'доски и лаги · включите опоры, чтобы увидеть их';
    $('ltdJoistCap').textContent = joistText(r);
    TAB_CAP.joists = $('ltdJoistCap').textContent;
    $('ltdTabSup').disabled = !s.supOn;
    $('ltdTabSup').title = s.supOn ? '' : 'Включите «Добавить опоры»';
    if (s.supOn) { drawJoists(s, r, 'ltdSvgSup', true); $('ltdSupCap').textContent = fmt(r.supports) + ' шт., шаг ' + fmt(s.supStepCm) + ' см'; TAB_CAP.sup = $('ltdSupCap').textContent; }
    if (tab === 'sup' && !s.supOn) showTab('boards'); else $('ltdTabCap').textContent = TAB_CAP[tab] || '';

    last = { s: s, r: r, rows: rows, total: total };
  }

  /* ---------- Интерактивный план: перетаскивание и подсказки ---------- */
  var svgB = $('ltdSvgBoards'), tip = $('ltdTip');
  function svgPt(ev) { var p = svgB.createSVGPoint(); p.x = ev.clientX; p.y = ev.clientY; return p.matrixTransform(svgB.getScreenCTM().inverse()); }
  svgB.addEventListener('pointerdown', function (ev) {
    var h = ev.target.getAttribute && ev.target.getAttribute('data-h'); if (!h || !svgB.__f) return;
    var f = svgB.__f; drag = { h: h, sc: f.sc, x0: f.x0, y0: f.y0 }; svgB.setPointerCapture(ev.pointerId); ev.preventDefault();
  });
  svgB.addEventListener('pointermove', function (ev) {
    var pt = svgPt(ev), f = svgB.__f; if (!f) return;
    if (drag) {
      var x = Math.round(((pt.x - drag.x0) / drag.sc) * 10) / 10, y = Math.round(((pt.y - drag.y0) / drag.sc) * 10) / 10, s = last ? last.s : read();
      if (drag.h === 'LW') { $('ltdL').value = Math.min(50, Math.max(0.5, x)); $('ltdW').value = Math.min(50, Math.max(0.5, y)); }
      else if (s.shape === 'trap') { $('ltdA').value = Math.max(0.1, Math.round((2 * x - s.L) * 10) / 10); }
      else if (s.shape === 'lshape' || s.shape === 'bevel') { $('ltdA').value = Math.max(0.1, Math.round((s.L - x) * 10) / 10); $('ltdB').value = Math.max(0.1, y); }
      else if (s.shape === 'ushape' || s.shape === 'tshape') { $('ltdA').value = Math.max(0.1, Math.round((2 * x - s.L) * 10) / 10); $('ltdB').value = Math.max(0.1, y); }
      else if (s.shape === 'pool') { $('ltdA').value = Math.max(0.1, Math.round((2 * x - s.L) * 10) / 10); $('ltdB').value = Math.max(0.1, Math.round((2 * y - s.W) * 10) / 10); }
      render(); tip.hidden = true; return;
    }
    if (!last) return;
    var mx = (pt.x - f.x0) / f.sc, my = (pt.y - f.y0) / f.sc, s2 = last.s, r2 = last.r;
    if (mx < 0 || my < 0 || mx > s2.L || my > s2.W || r2.diag) { tip.hidden = true; return; }
    var along = s2.dir === 'wid' ? my : mx, across = s2.dir === 'wid' ? mx : my;
    var i = Math.floor(across / r2.useful), segs = r2.rowSegs[i];
    if (!segs || !segs.some(function (sg) { return along >= sg[0] && along <= sg[1]; })) { tip.hidden = true; return; }
    var len = segs.reduce(function (t, sg) { return t + sg[1] - sg[0]; }, 0), nb = segs.reduce(function (t, sg) { return t + ceil((sg[1] - sg[0]) / s2.bl); }, 0);
    var box = svgB.getBoundingClientRect(), par = svgB.parentNode.getBoundingClientRect();
    tip.style.left = (ev.clientX - par.left) + 'px'; tip.style.top = (ev.clientY - par.top) + 'px';
    tip.textContent = 'Ряд ' + (i + 1) + ': ' + fmt(len, 2) + ' м, ' + nb + ' ' + plural(nb, 'доска', 'доски', 'досок');
    tip.hidden = false;
  });
  function endDrag() { if (drag) { drag = null; render(); } }
  svgB.addEventListener('pointerup', endDrag); svgB.addEventListener('pointercancel', endDrag);
  svgB.addEventListener('pointerleave', function () { if (!drag) tip.hidden = true; });

  /* ---------- События ---------- */
  // шаг лаг не больше STEP_MAX: при выходе из поля большее значение срезается до максимума
  $('ltdStep').addEventListener('change', function () { if (num(this.value) > STEP_MAX) this.value = STEP_MAX; });
  $('ltdBrand').addEventListener('change', function () { if (brand()) fillModels(); else { fillLengths(); fillAcc(); } });
  $('ltdTable').addEventListener('change', function (ev) {
    var k = ev.target.getAttribute && ev.target.getAttribute('data-key'); if (!k) return;
    excluded[k] = !ev.target.checked; render();
  });

  /* вкладки планов */
  var planFig = root.querySelector('.ltd-main');
  var tab = 'boards', TAB_CAP = { boards: 'тяните за красные точки, чтобы изменить размеры' };
  function showTab(t) {
    tab = t;
    planFig.querySelectorAll('.ltd-tabs [role=tab]').forEach(function (b) { b.setAttribute('aria-selected', b.getAttribute('data-tab') === t ? 'true' : 'false'); });
    planFig.querySelectorAll('[data-pane]').forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== t; });
    $('ltdTabCap').textContent = TAB_CAP[t] || '';
  }
  /* «Увеличить»: открытый план — крупной картинкой в fancybox, как фото на всём сайте */
  var TAB_NAME = { boards: 'План укладки доски', joists: 'План лаг', sup: 'План опор', all: 'Доски, лаги и опоры вместе' };
  $('ltdZoom').addEventListener('click', function () {
    var svg = planFig.querySelector('[data-pane="' + tab + '"] svg'), vb = svg.viewBox.baseVal, W = 2400, H = Math.round(W * vb.height / vb.width);
    planImage(svg, W, H, 'image/png').then(function (src) {
      if (!src) return;
      if (window.jQuery && jQuery.fancybox) jQuery.fancybox.open({ src: src, type: 'image', opts: { caption: TAB_NAME[tab] || '' } });
      else window.open().document.write('<img src="' + src + '" style="max-width:100%">');
    });
  });
  planFig.querySelector('.ltd-tabs').addEventListener('click', function (ev) { var b = ev.target.closest('[role=tab]'); if (b && !b.disabled) showTab(b.getAttribute('data-tab')); });
  $('ltdH').addEventListener('input', fillSupports);
  $('ltdRes').addEventListener('input', function () { resTouched = true; });
  $('ltdForm').addEventListener('input', render);
  $('ltdForm').addEventListener('change', render);
  $('ltdForm').addEventListener('submit', function (ev) { ev.preventDefault(); });

  function summary() {
    var s = last.s, r = last.r;
    var lines = ['Терраса: ' + LtdCore.SHAPES[s.shape].name + ', ' + fmt(s.L) + ' × ' + fmt(s.W) + ' м' + (CUT[s.shape] ? ' (a ' + fmt(s.a) + ' м, b ' + fmt(s.b) + ' м)' : '') + ', ' + fmt(r.area, 2) + ' м²',
      'Укладка: ' + { len: 'вдоль длины', wid: 'вдоль ширины', diag: 'по диагонали 45°' }[s.dir] + (s.dir !== 'diag' ? (s.pattern === 'stagger' ? ', вразбежку' : ', прямой рисунок') : '') + ', зазор ' + fmt(s.gap) + ' мм, запас ' + fmt(s.res) + '%'];
    last.rows.forEach(function (x) { if (x.off) return; lines.push(x.text + ': ' + fmt(x.qty) + ' ' + x.unit + (x.sum != null ? ' = ' + rub(x.sum) : '')); });
    if (last.total) lines.push('Итого по позициям с ценой: ' + rub(last.total));
    return lines.join('\n');
  }
  /* ---------- «Рассчитать с менеджером»: общая форма MAINFORM + расчёт в PDF к заявке ----------
     Окно формы открывает тема (data-event="jqm" на кнопке). Мы в ту же секунду шлём расчёт на сервер:
     он собирает PDF и запоминает его, а после отправки формы прикладывает к письму и лиду
     (local/php_interface/include/latitudo_terrace_calc_crm.php). */

  /* План «Всё вместе» → JPEG: переменные цвета подставляем значениями, точки перетаскивания убираем */
  /* SVG плана → картинка (data URL): для PDF заявки и для «Увеличить» */
  function planImage(svg, W, H, type, q) {
    return new Promise(function (resolve) {
      try {
        var cs = getComputedStyle(root);
        // XMLSerializer, а не outerHTML: в подписях &nbsp;, а в SVG-картинке такой сущности нет — она не загрузится
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
  function planJpeg() { return planImage($('ltdSvgAll'), 1280, 720, 'image/jpeg', 0.85); }
  function leadPayload() {
    var s = last.s, r = last.r, sum = summary().split('\n');
    var params = sum.slice(0, 2);
    var c = color();
    params.push('Доска: ' + (c ? boardTitle(brand(), model(), c, offer()) : 'своя, ' + fmt(s.bw) + ' мм × ' + fmt(s.bl) + ' м'));
    if (s.lagOn) params.push('Лаги: шаг по осям ' + fmt(s.step) + ' см' + (r.extraLines ? ', двойные лаги под стыками: ' + r.extraLines : ''));
    if (s.supOn) params.push('Опоры: высота ' + fmt(s.h) + ' мм, шаг ' + fmt(s.supStepCm) + ' см');
    return {
      params: params,
      rows: last.rows.filter(function (x) { return !x.off; }).map(function (x) {
        return { name: x.text, note: String(x.sub || '').replace(/<[^>]*>/g, ''), qty: fmt(x.qty) + ' ' + x.unit, price: x.price, sum: x.sum };
      }),
      total: last.total
    };
  }
  $('ltdPdfOpen').addEventListener('click', function () {
    var url = window.LTD_CALC_LEAD_URL;
    if (!url || !last || !last.rows || !window.BX || !BX.bitrix_sessid) return;
    var calc = leadPayload(), page = location.href.split('#')[0];
    planJpeg().then(function (plan) {
      var body = new FormData(); body.append('sessid', BX.bitrix_sessid());
      body.append('data', JSON.stringify({ calc: calc, plan: plan, page: page }));
      return fetch(url, { method: 'POST', body: body, credentials: 'same-origin' });
    }).catch(function () {}); // не вышло — заявка всё равно уйдёт, просто без PDF
  });

  /* Переход с карточки доски (кнопка «Калькулятор террасы», 21.10.2026):
     ?board=<символьный код товара>&len=<длина, мм> — сразу выбираем эту доску
     и её длину. Код не нашёлся в снимке каталога — остаётся доска по умолчанию. */
  function pickFromUrl() {
    var q = new URLSearchParams(location.search), code = q.get('board'), len = nominal(+q.get('len') || 0);
    if (!code) return;
    for (var bi = 0; bi < DATA.B.length; bi++) {
      for (var mi = 0; mi < DATA.B[bi].m.length; mi++) {
        var cs = DATA.B[bi].m[mi].c;
        for (var ci = 0; ci < cs.length; ci++) {
          if (cs[ci].u !== code) continue;
          var oi = len ? cs[ci].o.findIndex(function (o) { return nominal(o[0]) === len; }) : -1;
          if (oi < 0) oi = Math.max(0, cs[ci].o.findIndex(function (o) { return nominal(o[0]) === 4000; }));
          $('ltdBrand').value = bi;
          sel = { m: mi, c: ci, o: oi }; $('ltdFind').value = '';
          renderList(); fillLengths(); fillAcc();
          var on = $('ltdList').querySelector('[aria-selected=true]');
          if (on) $('ltdList').scrollTop = on.offsetTop - $('ltdList').offsetTop - 4;
          return;
        }
      }
    }
  }

  fillModels();
  pickFromUrl();
  render();
})();
