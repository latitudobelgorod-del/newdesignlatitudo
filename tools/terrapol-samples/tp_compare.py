"""Образцы Террапол (12.11.2026): что из списка образцов, разосланных по филиалам, есть на сайте.
Вход: xlsx Ирины + tp_site.json (выгрузка товаров бренда Террапол с прода, nd_tp_dump.php).
Выход: «Образцы Террапол — что есть на сайте 12.11.2026.xlsx» рядом с исходником.
Запуск: python tp_compare.py tp_site.json
"""
import json, re, sys
from collections import Counter
from openpyxl import Workbook, load_workbook
from openpyxl.styles import Font, PatternFill, Alignment

DIR = r'C:\Users\Irina\Desktop\Добавить на сайт отсутствующие позиции Террапола'
SRC = DIR + r'\Образцы Террапол - Латитудо 2026 ПОЛУЧЕНО.xlsx'
OUT = DIR + r'\Образцы Террапол – что есть на сайте 12.11.2026.xlsx'
site = json.load(open(sys.argv[1], encoding='utf-8'))['items']


def norm(s):
    return re.sub(r'\s+', ' ', s.lower().replace('ё', 'е')).strip()


ALIAS = {'артика': 'арктика'}  # опечатка в названии карточки 33490


def has(*w):
    return lambda n: all(x in n for x in w)


def no(*w):
    return lambda n: not any(x in n for x in w)


def both(*f):
    return lambda n: all(g(n) for g in f)


TD = 'террасная доска террапол '
RULES = {
    'Террасная доска Террапол СМАРТ пустотелая': both(has(TD + 'смарт 130х22'), no('3d', 'полнотел')),
    'Террасная доска Террапол СМАРТ полнотелая с пазом': both(has(TD + 'смарт 130х22 полнотелая'), no('без паза')),
    'Террасная доска Террапол СМАРТ 3D пустотелая': both(has(TD + 'смарт 3d 130х22'), no('полнотел')),
    'Террасная доска Террапол СМАРТ 3D полнотелая с пазом': both(has(TD + 'смарт 3d 130х24 полнотелая'), no('без паза')),
    'Террасная доска Террапол Классик пустотелая': both(has(TD + '147х24 классик'), no('полнотел')),
    'Террасная доска Террапол Классик полнотелая с пазом': both(has(TD + '147х24 классик полнотелая'), no('без паза')),
    'Террасная доска Террапол Классик полнотелая без паза': has(TD + '147х24 классик полнотелая без паза'),
    'Террасная доска ПРАКТИК КОЭКСТРУЗИЯ Моноколор': has(TD + 'практик коэкструзия моноколор'),
    'Террасная доска ПРАКТИК КОЭКСТРУЗИЯ Мультиколор': has(TD + 'практик коэкструзия мультиколор'),
    'Террасная доска АНТИК КОЭКСТРУЗИЯ Мультиколор': has(TD + 'антик 147х23'),
    'Ступень Террапол КЛАССИК пустотелая': has('ступень дпк террапол классик 320х24 пустотелая'),
    'Декоративный уголок Террапол': has('уголок декоративный террапол 4х40х40'),
    'Декоративный уголок Террапол КОЭКСТРУЗИЯ': has('уголок декоративный террапол коэкструзия'),
    'Универсальная доска Террапол ПРАКТИК КОЭКСТРУЗИЯ': has('универсальная доска террапол практик коэкструзия'),
    'Универсальная доска Террапол АНТИК КОЭКСТРУЗИЯ': has('универсальная доска террапол антик коэкструзия'),
    'Декоративная планка Террапол 3D/Браш': has('декоративная планка террапол 59х10'),
    'Декоративная панель Террапол 3D/Браш': has('декоративная панель террапол 3d/браш'),
    'Столб большой Практик 100': has('столб большой террапол практик 100х100'),
    'Перила Практик': has('перила террапол практик'),
    'Подбалясенник Практик': has('подбалясенник террапол практик'),
    'Балясина Практик': has('балясина террапол практик'),
    'Столб большой Классик 120': both(has('столб большой террапол 120х120'), no('практик')),
    'Рамка для большого столба 120': has('рамка для большого столба террапол'),
    'Столб малый Классик 70': has('столб малый террапол 70х70'),
    'Рамка для малого столба 70': has('рамка для малого столба террапол'),
    'Перила Классик': both(has('перила террапол 86х31'), no('практик')),
    'Подбалясенник Классик': has('подбалясенник 70х35'),
    'Балясина Классик': both(has('балясина террапол 50х30'), no('практик')),
    'Крышка для столба 120': has('крышка для столба террапол 130х130'),
    'Заборная доска 120': has('заборная доска террапол 120х16'),
    'Заборная доска полнотелая 100': both(has('заборная доска террапол 100х16'), no('3d')),
    'Заборная доска 3D полнотелая 100': has('заборная доска 3d террапол 100х16'),
    'Комплект Пирамида для столба 100': both(has('комплект пирамида террапол'), no('подсветк')),
    'Столб ограждения Смарт 3D': has('столб', 'смарт 3d'),
    'Подбалясенник ограждения Смарт 3D': has('подбалясенник', 'смарт 3d'),
    'Балясина ограждения Смарт 3D': has('балясина', 'смарт 3d'),
    'Перила ограждения Смарт 3D': has('перила', 'смарт 3d'),
    'Крышка 100 Смарт 3D': has('крышка 3d дпк для столба террапол'),
    'Рамка 100 Смарт 3D': has('рамка дпк для столба террапол 3d'),
}
NOTE = {
    'Крышка 100 Смарт 3D': 'на сайте «Крышка 3D ДПК для столба 110x110» – сверить, та ли это крышка',
    'Рамка 100 Смарт 3D': 'на сайте «Рамка ДПК для столба 3D 130х130» – сверить, та ли это рамка',
    'Крышка для столба 120': 'на сайте «Крышка для столба 130х130»',
    'Рамка для большого столба 120': 'на сайте 147х147',
    'Рамка для малого столба 70': 'на сайте 99х99',
}


def visible(p):
    if p['active'] != 'Y' or p['secon'] != 'Y':
        return False
    return not p['offers'] or any(o['active'] == 'Y' for o in p['offers'])


def words(s):
    return ' '.join(ALIAS.get(w, w) for w in norm(s).split())


ws = load_workbook(SRC, data_only=True).worksheets[0]
rows = []
for r in list(ws.iter_rows(values_only=True))[1:]:
    if not r[0]:
        continue
    name = str(r[0]).strip()
    colors = [c.strip() for c in str(r[1] or '').split(',') if c.strip()]
    rule = RULES[name]
    cands = [p for p in site if rule(norm(p['name'])) and '[old]' not in norm(p['name'])]
    for c in colors:
        cn = words(c)
        hit = [p for p in cands if cn in words(p['name'])]
        vis = [p for p in hit if visible(p)]
        if vis:
            st, p = 'есть на сайте', vis[0]
        elif hit:
            st, p = 'есть, но выключен', hit[0]
        else:
            st, p = 'НЕТ на сайте', None
        rows.append(dict(name=name, color=c, st=st, art=(p['id'] if p else ''), site=(p['name'] if p else ''),
                         url=(p['url'] if p else ''), note=NOTE.get(name, '') if p else ''))

HDR = PatternFill('solid', fgColor='9E1414')
FILL = {'есть на сайте': PatternFill('solid', fgColor='E2F4E2'),
        'есть, но выключен': PatternFill('solid', fgColor='FFF3C4'),
        'НЕТ на сайте': PatternFill('solid', fgColor='FDE2E2')}
wb = Workbook()
w1 = wb.active
w1.title = 'Чего нет на сайте'
w2 = wb.create_sheet('Все образцы')
w3 = wb.create_sheet('Итого по позициям')
for w in (w1, w2):
    w.append(['Позиция из списка образцов', 'Цвет', 'На сайте', 'Артикул (ID товара)', 'Товар на сайте', 'Примечание', 'Ссылка'])
for x in rows:
    line = [x['name'], x['color'], x['st'], x['art'], x['site'], x['note'], x['url']]
    for w in ([w1, w2] if x['st'] != 'есть на сайте' else [w2]):
        w.append(line)
        w.cell(w.max_row, 3).fill = FILL[x['st']]
        if x['url']:
            w.cell(w.max_row, 7).hyperlink = x['url']
            w.cell(w.max_row, 7).style = 'Hyperlink'
w3.append(['Позиция из списка образцов', 'Цветов в образцах', 'Есть на сайте', 'Выключены', 'Нет на сайте', 'Каких цветов нет'])
order = list(dict.fromkeys(x['name'] for x in rows))
for n in order:
    g = [x for x in rows if x['name'] == n]
    cnt = lambda s: sum(1 for x in g if x['st'] == s)
    w3.append([n, len(g), cnt('есть на сайте'), cnt('есть, но выключен'), cnt('НЕТ на сайте'),
               ', '.join(x['color'] for x in g if x['st'] != 'есть на сайте')])
    if cnt('есть на сайте') < len(g):
        w3.cell(w3.max_row, 6).fill = FILL['НЕТ на сайте']
w3.append([])
w3.append(['ВСЕГО', len(rows), sum(x['st'] == 'есть на сайте' for x in rows),
           sum(x['st'] == 'есть, но выключен' for x in rows), sum(x['st'] == 'НЕТ на сайте' for x in rows)])
w3.cell(w3.max_row, 1).font = Font(bold=True)
for w, widths in [(w1, [44, 18, 18, 12, 70, 40, 50]), (w2, [44, 18, 18, 12, 70, 40, 50]), (w3, [52, 12, 12, 12, 12, 70])]:
    for c in w[1]:
        c.font = Font(bold=True, color='FFFFFF')
        c.fill = HDR
        c.alignment = Alignment(wrap_text=True, vertical='top')
    for i, wd in enumerate(widths):
        w.column_dimensions[chr(65 + i)].width = wd
    w.freeze_panes = 'A2'
    w.auto_filter.ref = w.dimensions
wb.save(OUT)
print(OUT, Counter(x['st'] for x in rows))
for n in order:
    g = [x for x in rows if x['name'] == n]
    miss = [x['color'] + ('' if x['st'] == 'НЕТ на сайте' else ' (выкл)') for x in g if x['st'] != 'есть на сайте']
    if miss:
        print(' ', n, ':', ', '.join(miss))
