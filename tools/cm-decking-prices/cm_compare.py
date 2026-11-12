"""Таблица «было → стало» по ценам СМ Декинг (12.11.2026).

Было  — цена на сайте сейчас (она выставлена по прайсу «Лето 2026», колонка «РРЦ, руб/шт»).
Стало — «РРЦ, руб/шт» из нового прайса «РОЗНИЦА ОКТЯБРЬ» (+ наценка за цвет, если поставщик её указал).
Для контроля рядом — РРЦ из «Лето 2026» по той же позиции: если она не совпала с ценой сайта,
строка помечена, и такую позицию лучше проверить глазами.

Только позиции, которые видны на сайте (товар, предложение и раздел активны).
Запуск: python cm_compare.py  (рядом должен лежать свежий cm_site.json из nd_cm_dump.php).
Цены на сайте этот скрипт НЕ меняет; рядом с xlsx пишет cm_new_prices.json — из него потом заливка.
"""
import json
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment
import cm_match as m

OUT = r'C:\Users\Irina\Desktop\Прайс на Cm Decking\СМ Декинг — было и стало по прайсу ОКТЯБРЬ 12.11.2026.xlsx'
GARDEN_PACK = 11  # садовый паркет: в прайсе цена за упаковку 11 плиток, на сайте — за плитку


def unit_price(p, col):
    v = p['rrc'] / GARDEN_PACK if 'cm garden' in p['sec'].lower() else p['rrc']
    return round(v * (1 + m.plus_for_color(p['note'], col) / 100))


d = json.load(open('cm_site.json', encoding='utf-8'))
rows, plan = [], []
for r in d:
    if not (r['pactive'] == 'Y' and r['active'] == 'Y' and r['secon'] == 'Y'):
        continue
    col = m.color_of(r['name'])
    pn, comment = m.match(r['name'], m.new)
    po, _ = m.match(r['name'], m.old, is_old=True)
    cur = r['price']
    new = unit_price(pn, col) if pn else None
    leto = unit_price(po, col) if po else None
    notes = [comment] if comment and comment != 'нет в прайсе' else []
    if pn and not m.color_listed(pn, col):
        notes.append(f'цвета «{col}» нет в строке нового прайса')
    if pn and m.plus_for_color(pn['note'], col):
        notes.append(f"наценка за цвет +{m.plus_for_color(pn['note'], col)}% (в новом прайсе)")
    if not pn:
        notes.append('нет в новом прайсе — цену не меняем')
    if leto is not None and cur and abs(leto - cur) >= 1:
        notes.append(f'сейчас на сайте {cur:g}, а по «Лето» {leto} — проверить')
    rows.append(dict(sec=r['sec'], art=r['art'] or (r['oid'] or r['pid']), id=r['oid'] or r['pid'], name=r['name'].strip(),
                     cur=cur, leto=leto, new=new, notes='; '.join(notes), url=r['url'],
                     series=pn['series'] if pn else '', unit=r['base'] or r['measure']))
    if new and cur and abs(new - cur) >= 1:
        plan.append(dict(id=r['oid'] or r['pid'], priceid=r['priceid'], name=r['name'].strip(), old=cur, new=new))

rows.sort(key=lambda x: (x['sec'], x['name']))
HDR = PatternFill('solid', fgColor='9E1414')
UP = PatternFill('solid', fgColor='FDE2E2')
DOWN = PatternFill('solid', fgColor='E2F4E2')
WARN = PatternFill('solid', fgColor='FFF3C4')

wb = Workbook()
ws = wb.active
ws.title = 'Было → стало'
ws.append(['Раздел', 'Артикул', 'Название на сайте', 'Сейчас на сайте, ₽/шт', 'Новая цена, ₽/шт',
           'Разница, ₽', 'Разница, %', 'По «Лето 2026», ₽/шт (контроль)', 'Строка нового прайса', 'Комментарий', 'Ссылка'])
st = dict(total=0, up=0, down=0, same=0, nomatch=0, warn=0)
for x in rows:
    diff = (x['new'] - x['cur']) if (x['new'] is not None and x['cur']) else None
    pct = round(diff / x['cur'] * 100, 1) if diff is not None else None
    ws.append([x['sec'], x['art'], x['name'], x['cur'], x['new'], diff, pct, x['leto'], x['series'], x['notes'], x['url']])
    rr = ws.max_row
    st['total'] += 1
    if x['new'] is None:
        st['nomatch'] += 1
    elif diff and diff > 0:
        st['up'] += 1
        for c in range(4, 8):
            ws.cell(rr, c).fill = UP
    elif diff and diff < 0:
        st['down'] += 1
        for c in range(4, 8):
            ws.cell(rr, c).fill = DOWN
    else:
        st['same'] += 1
    if 'проверить' in x['notes']:
        st['warn'] += 1
        ws.cell(rr, 10).fill = WARN
    ws.cell(rr, 11).hyperlink = x['url']
    ws.cell(rr, 11).style = 'Hyperlink'

for c in ws[1]:
    c.font = Font(bold=True, color='FFFFFF')
    c.fill = HDR
    c.alignment = Alignment(wrap_text=True, vertical='top')
ws.freeze_panes = 'D2'
ws.auto_filter.ref = ws.dimensions
for colname, w in zip('ABCDEFGHIJK', [26, 10, 62, 12, 12, 10, 9, 14, 30, 50, 40]):
    ws.column_dimensions[colname].width = w
ws.row_dimensions[1].height = 45

w2 = wb.create_sheet('Итого')
for k, v in [('Позиций на сайте (видимых)', st['total']), ('Цена вырастет', st['up']), ('Цена снизится', st['down']),
             ('Без изменений', st['same']), ('Нет в новом прайсе (цену не трогаем)', st['nomatch']),
             ('Цена сайта не совпала с «Лето 2026» — проверить', st['warn'])]:
    w2.append([k, v])
w2.column_dimensions['A'].width = 52
wb.save(OUT)
json.dump(plan, open('cm_new_prices.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print(OUT, st, 'к замене:', len(plan))
