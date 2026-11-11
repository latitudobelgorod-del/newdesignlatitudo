import json, re
from openpyxl import Workbook
from openpyxl.styles import Font, PatternFill, Alignment
import cm_match as m

d = json.load(open('cm_site.json', encoding='utf-8'))
OUT = r'C:\Users\Irina\Desktop\Прайс на Cm Decking\СМ Декинг — сайт и новый прайс 11.11.2026.xlsx'

GARDEN_PACK = 11  # садовый паркет: в прайсе цена за упаковку 11 плиток


def per_unit(row, p):
    v = p['rrc']
    if 'cm garden' in p['sec'].lower():
        v = v / GARDEN_PACK
    return v


def nrm(s):
    return re.sub(r'[\s()]', '', str(s).lower().replace('x', 'х').replace('мм', ''))


def latin(series):
    m2 = re.search(r'\(([A-Za-z][^)]*)\)', series)
    s = m2.group(1) if m2 else series
    return re.sub(r'\(под заказ\)', '', s).strip().lower()


sale = m.load_price(m.NEW, 1, 6, 7, 99)


def sale_for(p):
    for q in sale:
        sq = re.sub(r'\s+', ' ', re.sub(r'\(под заказ\)', '', q['series'])).strip().lower()
        sp = re.sub(r'\s+', ' ', re.sub(r'\(под заказ\)', '', p['series'])).strip().lower()
        if sq == sp and nrm(q['dims']) == nrm(p['dims']) and nrm(q['colors']) == nrm(p['colors']):
            return q
    return None


old_by_key = {(nrm(p['sec']), latin(p['series']), nrm(p['dims']), nrm(p['colors'])): p for p in m.old}


def old_row_for(p):
    k = (nrm(p['sec']), latin(re.sub(r'\(под заказ\)', '', p['series'])), nrm(p['dims']), nrm(p['colors']))
    if k in old_by_key:
        return old_by_key[k]
    for kk, v in old_by_key.items():  # серия без «CM »
        if kk[0] == k[0] and kk[2] == k[2] and kk[3] == k[3] and (k[1].replace('cm ', '') in kk[1] or kk[1] in k[1]):
            return v
    return None


HDR = PatternFill('solid', fgColor='9E1414')
UP = PatternFill('solid', fgColor='FDE2E2')
DOWN = PatternFill('solid', fgColor='E2F4E2')
GREY = PatternFill('solid', fgColor='EEEEEE')

wb = Workbook()
ws = wb.active
ws.title = 'Сайт → новая цена'
head = ['Раздел', 'Товар', 'ID товара', 'Предложение', 'Артикул', 'Виден на сайте', 'Цена на сайте показывается за',
        'Сейчас ₽/шт', 'Сейчас ₽/м²', 'Сейчас ₽/п.м',
        'Новая ₽/шт', 'Новая ₽/м²', 'Новая ₽/п.м', 'Изменение, %',
        'Строка нового прайса: серия', 'цвета', 'размеры', 'РРЦ ₽/шт в прайсе', 'Наценка за цвет', 'Пометка поставщика',
        'Комментарий', 'Ссылка']
ws.append(head)
matched_by_row = {}
stats = dict(total=0, vis=0, matched=0, changed=0)
for r in sorted(d, key=lambda r: (r['sec'], r['name'])):
    vis = r['pactive'] == 'Y' and r['active'] == 'Y' and r['secon'] == 'Y'
    p, comment = m.match(r['name'], m.new)
    col = m.color_of(r['name'])
    k = r['koef']
    cur = r['price']
    row = [r['sec'], r['pname'], r['pid'], r['name'] if r['oid'] else '', r['art'], 'да' if vis else 'нет',
           r['base'] or r['measure'],
           cur, round(cur * k['м²']) if cur and 'м²' in k else None, round(cur * k['п.м']) if cur and 'п.м' in k else None]
    stats['total'] += 1
    stats['vis'] += vis
    if p:
        stats['matched'] += 1
        plus = m.plus_for_color(p['note'], col)
        newp = round(per_unit(r, p) * (1 + plus / 100))
        if not m.color_listed(p, col):
            comment = (comment + '; ' if comment else '') + f'цвета «{col}» нет в этой строке прайса'
        if cur and abs(newp - cur) >= 1:
            stats['changed'] += 1
        row += [newp, round(newp * k['м²']) if 'м²' in k else None, round(newp * k['п.м']) if 'п.м' in k else None,
                round((newp - cur) / cur * 100, 1) if cur else None,
                p['series'], p['colors'], p['dims'], p['rrc'], f'+{plus}%' if plus else '', p['note']]
        matched_by_row.setdefault(p['row'], []).append((r, vis))
    else:
        row += [None, None, None, None, '', '', '', None, '', '']
    row += [comment, r['url']]
    ws.append(row)
    rr = ws.max_row
    ch = ws.cell(rr, 14).value
    fill = None
    if ch is not None and ch > 0:
        fill = UP
    elif ch is not None and ch < 0:
        fill = DOWN
    if fill:
        for c in range(11, 15):
            ws.cell(rr, c).fill = fill
    if not vis:
        for c in range(1, 8):
            ws.cell(rr, c).font = Font(color='888888')
    ws.cell(rr, 22).hyperlink = r['url']
    ws.cell(rr, 22).style = 'Hyperlink'

# --- новый прайс, 1-я вкладка, рядом старая РРЦ и что на сайте ---
w2 = wb.create_sheet('Новый прайс + сайт')
w2.append(['Строка прайса', 'Раздел прайса', 'Серия', 'Цвета', 'Размеры', 'Упаковка', 'м² в упаковке',
           'Новая РРЦ ₽/шт', 'Новая РРЦ ₽/м²', 'Пометка поставщика', 'Старая РРЦ ₽/шт', 'Старая РРЦ ₽/м²',
           'Изменение РРЦ, %', 'Позиций на сайте', 'из них видны', 'Цена на сайте сейчас ₽/шт'])
for p in m.new:
    o = old_row_for(p)
    items = matched_by_row.get(p['row'], [])
    prices = sorted({it[0]['price'] for it in items if it[0]['price']})
    cur = (f'{prices[0]:g}' if len(prices) == 1 else f'{prices[0]:g} – {prices[-1]:g}') if prices else ''
    w2.append([p['row'], p['sec'], p['series'], p['colors'], p['dims'], p['pack'], p['m2'], p['rrc'], p['rrc_m2'], p['note'],
               o['rrc'] if o else None, o['rrc_m2'] if o else None,
               round((p['rrc'] - o['rrc']) / o['rrc'] * 100, 1) if o else None,
               len(items), sum(1 for it in items if it[1]), cur])
    rr = w2.max_row
    ch = w2.cell(rr, 13).value
    if ch:
        w2.cell(rr, 13).fill = UP if ch > 0 else DOWN
    if not items:
        for c in range(1, 17):
            w2.cell(rr, c).fill = GREY

for s in (ws, w2):
    for c in s[1]:
        c.font = Font(bold=True, color='FFFFFF')
        c.fill = HDR
        c.alignment = Alignment(wrap_text=True, vertical='top')
    s.freeze_panes = 'A2'
    s.auto_filter.ref = s.dimensions
for col, w in zip('ABCDEFGHIJKLMNOPQRSTUV', [24, 40, 9, 48, 9, 8, 10, 9, 9, 9, 9, 9, 9, 9, 24, 26, 20, 9, 8, 22, 36, 40]):
    ws.column_dimensions[col].width = w
for col, w in zip('ABCDEFGHIJKLMNOP', [8, 26, 30, 36, 24, 8, 8, 10, 10, 24, 10, 10, 10, 9, 9, 14]):
    w2.column_dimensions[col].width = w
ws.row_dimensions[1].height = 45
w2.row_dimensions[1].height = 45
wb.save(OUT)
print(OUT, stats)
