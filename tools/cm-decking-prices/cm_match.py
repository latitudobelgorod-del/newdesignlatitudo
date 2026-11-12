import json, re
from openpyxl import load_workbook

NEW = r'C:\Users\Irina\Desktop\Прайс на Cm Decking\Cottage Mode 2026 МСК РОЗНИЦА ОКТЯБРЬ.xlsx'
# Прайс, по которому стоят цены на сайте сейчас (с 12.11.2026 — «Лето 2026», раньше считали по «VIP»).
# Формат тот же: вторая вкладка, РРЦ ₽/шт в колонке O.
OLD = r'C:\Users\Irina\Desktop\Прайс на Cm Decking\Cottage Mode 2026 МСК Лето 2026.xlsx'


def load_price(path, sheet_idx, rrc_col, rrc_m2_col, note_col):
    ws = load_workbook(path, data_only=True).worksheets[sheet_idx]
    rows, sec = [], ''
    data = list(ws.iter_rows(values_only=True))
    for i, r in enumerate(data, 1):
        if r[0] and all(v is None for v in r[1:8]):
            sec = str(r[0]).strip()
            continue
        if not r[0] or not isinstance(r[rrc_col], (int, float)):
            continue
        note = r[note_col] if len(r) > note_col else None
        if not note and i < len(data):
            nx = data[i]
            if not nx[0] and len(nx) > note_col and nx[note_col]:
                note = nx[note_col]
        rows.append(dict(row=i, sec=sec, series=str(r[0]).strip(), colors=str(r[1] or '').strip(),
                         dims=str(r[2] or '').strip(), pack=r[3],
                         m2=r[5] if isinstance(r[5], (int, float)) else None,
                         rrc=float(r[rrc_col]),
                         rrc_m2=r[rrc_m2_col] if isinstance(r[rrc_m2_col], (int, float)) else None,
                         note=str(note).strip() if note else ''))
    return rows


new = load_price(NEW, 0, 6, 7, 8)
old = load_price(OLD, 1, 14, 15, 16)


def find(rows, sec_kw, series_kw, dims_kw=None, colors_kw=None):
    out = []
    for p in rows:
        if sec_kw.lower() not in p['sec'].lower():
            continue
        s = p['series'].lower()
        if not all(k.lower() in s for k in series_kw.split('|')):
            continue
        if dims_kw and not all(k in p['dims'].replace('x', 'х') for k in dims_kw.split('|')):
            continue
        if colors_kw and colors_kw.lower() not in p['colors'].lower():
            continue
        out.append(p)
    return out


def pick(rows, sec, ser, dims=None, colors=None, exclude=None):
    c = find(rows, sec, ser, dims, colors)
    if exclude:
        c = [p for p in c if not any(e.lower() in p['series'].lower() for e in exclude)]
    return c[0] if len(c) == 1 else None


COLOR_ALIASES = {'смок грэй': 'смок грей', 'чаркол': 'чаркоал', 'кэмэл': 'кэмел', 'блэк wood': 'черное дерево',
                 'стоун грэй': 'стоун грей', 'черное дерево полнотелая': 'черное дерево'}


def color_of(name):
    n = name.strip()
    m = re.search(r'\(([^)]+)\)\s*$', n)
    if m and not m.group(1).startswith('для') and m.group(1) != 'OLD':
        c = m.group(1)
    else:
        tail = re.split(r'\d+\s*[хx]\s*\d+(?:\s*[хx]\s*\d+)?', n)[-1]
        tail = re.sub(r'\(для [^)]*\)', '', tail)
        c = re.sub(r'(Полнотелая|Пустотелая)', '', tail).strip()
    c = c.lower().strip()
    return COLOR_ALIASES.get(c, c)


TERR = [('СОЛИД ВИНТАЖ', 'vintage solid'), ('РОБУСТ СОЛИД', 'robust solid'), ('ГРАНД', 'grand'), ('ФЛЭКС', 'flax'),
        ('ЛОКУСТ', 'locust'), ('НАТУР', 'natur'), ('ВИНТАЖ', 'vintage'), ('БАРК', 'bark'), ('РОБУСТ', 'robust'),
        ('МИКС', 'mix'), ('КАНТРИ', 'country'), ('УРБАН', 'urban'), ('ЗЕБРАНО', 'zebrano'), ('РЕВЕРС', 'reverse')]
CLAD = [('ВИНТАЖ', 'vintage'), ('БАРК', 'bark'), ('УРБАН', 'urban'), ('ФЬЮЖН', 'fusion'), ('ТИМБЕР', 'timber')]
RU = {'grand': 'гранд', 'flax': 'флэкс', 'locust': 'локуст', 'natur': 'натур', 'vintage solid': 'винтаж солид',
      'vintage': 'винтаж', 'robust solid': 'робуст солид', 'bark': 'барк', 'robust': 'робуст', 'mix': 'микс',
      'country': 'кантри', 'urban': 'урбан', 'zebrano': 'зебрано', 'reverse': 'реверс', 'fusion': 'фьюжн',
      'timber': 'тимбер', 'cm wall': 'волл', 'wall dual': 'волл дуал'}


def length(name):
    m = re.search(r'\d+\s*[хx]\s*\d+\s*[хx]\s*(\d{4})', name)
    return m.group(1) if m else None


def match(name, rows, is_old=False):
    """-> (строка прайса | None, комментарий)"""
    n = re.sub(r'\s+', ' ', name.replace('x', 'х')).strip()
    L = length(n)

    def ser(en):
        return RU[en] if is_old else en

    if '(OLD)' in n:
        return None, 'старая карточка (OLD)'
    if n.startswith('Террасная доска'):
        for k, en in TERR:
            if re.search(r'(?<![А-ЯЁ])' + k + r'(?![А-ЯЁ])', n):
                excl = [] if 'solid' in en else (['солид'] if is_old else ['solid'])
                return pick(rows, 'террасная доска', ser(en), L, exclude=excl), ''
    if n.startswith('Ступень ДПК'):
        col = color_of(n)
        if '345' in n:
            return pick(rows, 'аксессуары cm decking', 'ступень', '345'), 'на сайте 345х25, в прайсе 345х35'
        if col in ('ясень', 'тик', 'мербау'):
            return pick(rows, 'аксессуары cm decking', 'ступень', '305', 'ясень'), ''
        return pick(rows, 'аксессуары cm decking', 'ступень', '305', 'смок'), 'на сайте 305х25, в прайсе 305х35'
    if n.startswith('Доска финишная'):
        return pick(rows, 'аксессуары cm decking', 'финишная'), ''
    if n.startswith('Угловой профиль СМ Декинг 50х38'):
        return pick(rows, 'аксессуары cm decking', 'угловой', '50х38'), ''
    if n.startswith('Угловой профиль СМ Декинг Ко-Экс 60х40'):
        return pick(rows, 'аксессуары cm decking', 'угловой|co-ex', '60'), ''
    if n.startswith('Лага несущая из ДПК полнотелая СМ Декинг 40х25х'):
        return pick(rows, 'аксессуары cm decking', 'лага'), ''
    if n.startswith('Панель универсальная 3D СМ'):
        return pick(rows, 'аксессуары cm decking', 'панель универсальная 3d'), ''
    if n.startswith('Панель универсальная СМ'):
        if color_of(n) in ('ясень', 'тик', 'мербау'):
            return pick(rows, 'аксессуары cm decking', 'панель универсальная 3d'), 'в названии нет «3D», но цвет серии 3D'
        return pick(rows, 'аксессуары cm decking', 'панель универсальная', exclude=['3d']), ''
    if n.startswith('Торцевая доска'):
        return pick(rows, 'аксессуары cm decking', 'торцевая'), ''
    if n.startswith('Набор стартовых клипс'):
        return pick(rows, 'аксессуары cm decking', 'стартовых'), ''
    if n.startswith('Монтажный набор СМ Декинг для ступеней'):
        return pick(rows, 'аксессуары cm decking', 'для ступеней'), ''
    if n.startswith('Набор монтажных клипс'):
        kind = 'для лаг' if 'для лаг' in n else 'универсальный'
        s = 'нс 9мм' if '9 мм' in n else ('нс solid' if 'Солид' in n else 'нс (')
        c = [p for p in find(rows, 'аксессуары cm decking', 'клипс')
             if s in p['series'].lower() and kind in p['series'].lower()]
        return (c[0] if len(c) == 1 else None), ''
    if n.startswith('Фасадная панель СМ Кладдинг') or n.startswith('Фасадная панель CM Кладдинг'):
        for k, en in CLAD:
            if k in n:
                return pick(rows, 'cm cladding', ser(en)), ''
    if n.startswith('Угловой профиль СМ Декинг 70х70'):
        col = color_of(n)
        ck = 'смок' if col in ('орегон', 'смок грей', 'сонома') else 'ясень'
        return pick(rows, 'аксессуары cm cladding', 'угловой', None, ck, exclude=['co-ex']), ''
    if n.startswith('Угловой профиль СМ Декинг Ко-Экс 70х70'):
        return pick(rows, 'аксессуары cm cladding', 'co-ex'), ''
    if n.startswith('Фасадная реечная панель СМ Волл') and 'ЗЕБРАНО' not in n:
        dual = 'ДУАЛ' in n
        s = ser('wall dual') if dual else ser('cm wall')
        return pick(rows, 'cm wall', s, L, exclude=[] if dual else ['dual', 'дуал']), ''
    if n.startswith('Угловой профиль СМ Волл'):
        return pick(rows, 'аксессуары cm wall', 'угловой', L), ''
    if n.startswith('Садовый паркет СМ Гарден МОДЕРН'):
        return pick(rows, 'cm garden', 'modern'), 'в прайсе цена за упаковку 11 шт (1 м²), на сайте — за плитку'
    if n.startswith('Садовый паркет СМ Гарден GRASS'):
        return pick(rows, 'cm garden', 'grass'), 'в прайсе цена за упаковку 11 шт (1 м²), на сайте — за плитку'
    rail = [('Столб СМ Декинг 120х120', 'столб 120'), ('Столб СМ Декинг 90х90х3000', 'столб 90'),
            ('Профиль перила', 'профиль перила'), ('Промежуточный профиль', 'промежуточный'),
            ('Крышка столба', 'крышка'), ('Соединитель перила прямой', '(прямой)'),
            ('Соединитель перила с переходом', 'с переходом'), ('Стальное усиление столба', 'усиление'),
            ('Нижний кант', 'кант'), ('Стальной уголок', 'стальной уголок'), ('Фиксатор промежуточного', 'фиксатор')]
    for pre, s in rail:
        if n.startswith(pre):
            return pick(rows, 'перила', s), ''
    if n.startswith('Заборный профиль CM Fencing 210х20х'):
        return pick(rows, 'заборный', 'заборный'), ''
    if n.startswith('U-профиль СМ Декинг 40х30х'):
        return pick(rows, 'заборный', 'u-профиль', L), 'на сайте 40х30, в прайсе 30х27'
    return None, 'нет в прайсе'


def plus_for_color(note, col):
    m = re.search(r'^(.*?)\s*\+\s*(\d+)\s*%', note or '')
    if not m:
        return 0
    cols = [COLOR_ALIASES.get(c.strip().lower(), c.strip().lower()) for c in m.group(1).split(',')]
    return int(m.group(2)) if col in cols else 0


def color_listed(p, col):
    raw = p['colors'].lower().replace('ё', 'е')
    if not raw or raw == '-' or 'для серий' in raw:
        return True
    cs = [COLOR_ALIASES.get(c.strip(), c.strip()) for c in re.split(r'[,/]', raw)]
    parts = [COLOR_ALIASES.get(x.strip(), x.strip()) for x in col.replace('ё', 'е').split('/')]
    return all(x in cs for x in parts)
