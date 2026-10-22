"""Снимок каталога ограждений для калькулятора ограждений (/info/kalkulyator-ograzhdeniy/).

Пересборка (цены, новые цвета, новые товары):
  scp tools/fence-calc/export.php latitudo-ru:nd_fence_export.php
  ssh latitudo-ru 'cd www/latitudo.ru && php ~/nd_fence_export.php; rm ~/nd_fence_export.php' > fences.json
  python -X utf8 tools/fence-calc/build.py fences.json local/templates/aspro_next_newdesign/js/newdesign-fence-calc-data.js

Серии — подразделы «Ограждений из ДПК» (#104) третьего уровня. Детали раскладываются по типам
по названию (kind), цвет — по списку цветов столбов серии. Правила сборки (шаг столбов, балясины
на секцию, крепёж) — в самом калькуляторе, js/newdesign-fence-calc.js, объект RULES.
"""
import json, re, sys, datetime

src, dst = sys.argv[1], sys.argv[2]
d = json.load(open(src, encoding='utf-8'))
secs = {s['id']: s for s in d['sections']}

# порядок важен: «Комплект крышка/юбка» раньше крышки, крепёж раньше балясин и перил
KINDS = [
    ('capskirt', r'комплект крышка/юбка'),
    ('cap', r'крышк|пирамид|сфера'),
    ('skirt', r'юбк|рамк'),
    ('railfix', r'креп\w*\s.*(перил|поручн)'),
    ('balfix', r'креп\w*\s.*балясин|клипса монтажная для балясин'),
    ('mount', r'кронштейн|закладн|опора для столба'),
    ('post', r'столб'),
    ('bal', r'балясин'),
    ('bottom', r'подбалясенник'),
    ('rail', r'перил|поручен'),
]


def kind(n):
    s = n.lower()
    for k, p in KINDS:
        if re.search(p, s):
            return k
    return None


def norm(s):
    s = re.sub(r'(?<=\d)\s*[xX×]\s*(?=\d)', 'х', s)   # латинская x только в размерах
    return re.sub(r'\s+', ' ', s).strip()


def ce(s):
    return s.lower().replace('ё', 'е')


def size_len(n):
    """(сечение «100х100», длина заготовки в мм) из названия"""
    n = norm(n)
    m = re.search(r'(\d+)х(\d+)х(\d{3,4})', n)
    if m:
        return '%sх%s' % (m.group(1), m.group(2)), int(m.group(3))
    m = re.search(r'(\d+)х(\d{3,4})(?!\d)', n)          # алюминиевая балясина 20х750
    if m and int(m.group(2)) >= 600:
        return '%sх%s' % (m.group(1), m.group(1)), int(m.group(2))
    m = re.search(r'(\d+)х(\d+)', n)
    return ('%sх%s' % (m.group(1), m.group(2)) if m else ''), None


def post_color(n):
    n = norm(n)
    m = re.search(r'\d+х\d+(?:х\d+)?\s+(.+)$', n)
    c = m.group(1) if m else ''
    return re.sub(r'\s*Шлифованн\S*$', '', c).strip()


series = {}
for it in d['items']:
    s = secs.get(it['s'])
    if not s or s['d'] != 3:
        continue
    series.setdefault(s['id'], {'sec': s, 'items': []})['items'].append(it)

# все цвета столбов всех серий — словарь для цвета деталей (крышки NEXTWOOD общие для Классик и 3D)
VOCAB = []
for sr in series.values():
    for it in sr['items']:
        if kind(it['n']) == 'post':
            c = post_color(it['o'][0]['n'])
            if c and c not in VOCAB:
                VOCAB.append(c)
for extra in ('Коричневый', 'Белый', 'Черный', 'Серый'):
    if extra not in VOCAB:
        VOCAB.append(extra)
VOCAB.sort(key=len, reverse=True)

OUT = []
for sid, sr in series.items():
    s = sr['sec']
    brand = next((b for b in ('EasyDecking', 'Террапол', 'NEXTWOOD', 'Polivan') if b.lower() in s['n'].lower()), '')
    name = re.sub(r'^\s*(Белые\s+)?Ограждения\s+', '', s['n']).replace('Polivan ', '').strip()
    name = re.sub(r'\s+', ' ', name)
    colors = []
    for it in sr['items']:
        if kind(it['n']) == 'post':
            c = post_color(it['o'][0]['n'])
            if c and c not in colors:
                colors.append(c)
    parts = {}
    for it in sr['items']:
        k = kind(it['n'])
        if not k:
            print('?? не разобран:', it['n'], file=sys.stderr)
            continue
        for o in it['o']:
            on = norm(o['n'])
            col = next((c for c in VOCAB if ce(c) in ce(on)), '')
            size, ln = size_len(on)
            low = on.lower()
            tags = []
            if 'алюмин' in low and k == 'bal':
                tags.append('alu')
            if k == 'balfix' and ('алюмин' in low or 'пластик' in low):
                tags.append('alu')
            if 'углов' in low or 'поворот' in low:
                tags.append('turn')
            if 'малый' in low or 'малого' in low:
                tags.append('small')
            if 'большой' in low or 'большого' in low:
                tags.append('big')
            if 'фигурн' in low:
                tags.append('fig')
            if 'подсветк' in low:
                tags.append('led')
            parts.setdefault(k, []).append([on, o['p'], ln, size, col, ' '.join(tags),
                                            it['url'].replace('/catalog/ograzhdeniya-iz-dpk/', ''), it['img'], o['id']])
    OUT.append({'id': sid, 'b': brand, 'n': name, 'url': s['url'].replace('/catalog/ograzhdeniya-iz-dpk/', ''),
                'c': colors, 'p': parts})

# общий крепёж из корня раздела (кронштейн и закладная 120х120) — для серий без своего
common = []
for it in d['items']:
    if it['s'] == 104 and kind(it['n']) == 'mount':
        o = it['o'][0]
        size, ln = size_len(o['n'])
        common.append([norm(o['n']), o['p'], ln, size, '', '', it['url'].replace('/catalog/ograzhdeniya-iz-dpk/', ''), it['img'], o['id']])

# недостающие детали серии берём у соседней серии той же марки (NEXTWOOD 3D ← Классик: крышки, юбки,
# кронштейн, крепёж; белые EasyDecking ← Вуд-Икс: закладная, крепление алюминиевых балясин)
for x in OUT:
    for y in OUT:
        if y is x or y['b'] != x['b']:
            continue
        for k in ('mount', 'cap', 'skirt', 'capskirt', 'balfix', 'railfix'):
            if k in y['p'] and k not in x['p'] and not (k in ('cap', 'skirt') and 'capskirt' in x['p']):
                x['p'][k] = y['p'][k]
                x.setdefault('borrow', []).append(k)

ORDER = ['EasyDecking', 'NEXTWOOD', 'Polivan', 'Террапол']
OUT.sort(key=lambda x: (ORDER.index(x['b']) if x['b'] in ORDER else 9, x['n']))
data = {'S': OUT, 'common': common}
with open(dst, 'w', encoding='utf-8', newline='\n') as f:
    f.write('/* Снимок каталога ограждений для калькулятора ограждений на %s. Детали серий по типам: post, rail, bottom, bal, '
            'balfix, railfix, cap, skirt, capskirt, mount — [название, цена, длина мм, сечение, цвет, метки, адрес, картинка]. '
            'Пересборка: tools/fence-calc/build.py */\n' % datetime.date.today().strftime('%d.%m.%Y'))
    f.write('window.LFC_DATA = ' + json.dumps(data, ensure_ascii=False, separators=(',', ':')) + ';\n')
print('серий:', len(OUT), {x['n']: len(x['c']) for x in OUT})
