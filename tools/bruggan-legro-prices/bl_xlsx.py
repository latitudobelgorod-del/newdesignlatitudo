"""Bruggan и Legro: цены сайта против прайса WPDecking 2026 (13.11.2026), курс 98 ₽/€.
  python bl_xlsx.py plan <папка с bl_1625.json, bl_23922.json>          -> bl_new_prices.json (план заливки)
  python bl_xlsx.py xlsx <папка> <файл.xlsx>                             -> таблица «было -> стало»
     в папке: bl_1625.json/bl_23922.json – выгрузка ДО заливки (было), after_1625.json/after_23922.json – ПОСЛЕ (сейчас).
Выгрузка – nd_bl_dump.php <ID бренда> на проде.
"""
import json
import re
import sys
from pathlib import Path

RATE = 98
SKIP = {}  # лента 35034: 13.11.2026 Ирина – цена за рулон (шт) 8,65 € × 98
# (шаблон названия на сайте, цена €/шт, позиция прайса)
EUR = [
    (r'Bruggan.*140х19.*(Белый|Серый)', 64.60, 'BRUGGAN MultiColor grey, smoke white 140*19*3000'),
    (r'Bruggan.*140х19', 60.96, 'BRUGGAN MultiColor cedar, wenge, sand 140*19*3000'),
    (r'Лага монтажная', 8.79, 'Лага стандартная 40*30*2200'),
    (r'Усиленная лага', 21.50, 'Лага усиленная 53*43*3000'),
    (r'Лага алюмин', 18.31, 'Лага алюминиевая 40*25*3000'),
    (r'Уголок декоративный Bruggan', 14.73, 'Уголок 40*40*3000'),
    (r'Плинтус Bruggan', 11.51, 'Плинтус 50*9*3000'),
    (r'[Кк]липса|PROCLIP|DECKFIX', 0.56, 'Клипса'),
    (r'138х22', 47.66, 'LEGRO Natural-Dunes / Burnt Wood 138*22*3000'),
    (r'140х22х3000', 48.46, 'LEGRO Natural-Dunes 140*22*3000'),
    (r'Ступенька', 76.2, 'Ступенька 140*22*4000'),
    (r'F-профиль', 35.29, 'F-профиль 70*40*2500'),
    (r'60х60х2900', 13.50, 'Уголок AC 60 60*60*2900'),
    (r'60х60х3600', 19.02, 'Уголок AC 60 60*60*3600'),
    (r'АС 80', 18.82, 'Плинтус AS 80 80*10*3000'),
    (r'АС 180', 40.78, 'Профиль AS 180 180*10*3600'),
    (r'ФС 15', 52.06, 'Фасадный профиль FS 15 150*27.5*3600'),
    (r'ФС 21', 67.37, 'Фасадный профиль FS 21 210*27.5*3600'),
    (r'АС 20', 39.05, 'Угловой профиль AC 20 100*40*3600'),
    (r'АС 10', 21.46, 'Стартовый профиль AC 10 60*43*3600'),
    (r'Алюминиевый профиль', 14.65, 'Профиль алюмин. AC 11 40x32x3600'),
    (r'Лента', 8.65, 'Лента звукоизоляционная 40x2x20000 (рулон; 0,43 €/м.п.)'),
]
BRANDS = ((1625, 'Bruggan'), (23922, 'Legro'))


def visible(d, prefix):
    out = []
    for b, mark in BRANDS:
        for r in json.load(open(d / f'{prefix}_{b}.json', encoding='utf-8')):
            if r['pactive'] == 'Y' and r['active'] == 'Y' and r['secon'] == 'Y':
                m = next(((v, t) for k, v, t in EUR if re.search(k, r['name'])), None)
                if not m:
                    sys.exit('нет позиции прайса: ' + r['name'])
                out.append((mark, r, m[0], m[1]))
    return out


def plan(d):
    p = []
    for mark, r, e, _ in visible(d, 'bl'):
        pid = r['oid'] or r['pid']
        if pid in SKIP:
            continue
        new = round(e * RATE)
        if abs(new - r['price']) >= 1:
            p.append({'id': pid, 'priceid': int(r['priceid']), 'name': r['name'], 'eur': e, 'old': r['price'], 'new': new})
    json.dump(p, open(d / 'bl_new_prices.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    print('в плане', len(p))


def xlsx(d, out):
    from openpyxl import Workbook
    from openpyxl.styles import Alignment, Font, PatternFill
    after = {}
    for b, _ in BRANDS:
        for r in json.load(open(d / f'after_{b}.json', encoding='utf-8')):
            after[r['oid'] or r['pid']] = r['price']
    wb = Workbook()
    ws = wb.active
    ws.title = 'Bruggan и Legro'
    hdr = ['Марка', 'ID товара', 'ID ТП', 'Название на сайте', 'Позиция прайса', 'Прайс, €/шт',
           'Было на сайте, ₽/шт', f'Стало на сайте, ₽/шт (× {RATE})', 'Разница, ₽', 'Ед. на сайте',
           'Было за ед.', 'Стало за ед.', 'Заметка', 'Ссылка']
    ws.append(hdr)
    for c in ws[1]:
        c.font = Font(bold=True)
        c.fill = PatternFill('solid', fgColor='DDDDDD')
        c.alignment = Alignment(wrap_text=True, vertical='top')
    yellow = PatternFill('solid', fgColor='FFF2CC')
    for mark, r, e, title in visible(d, 'bl'):
        pid = r['oid'] or r['pid']
        now = after.get(pid)
        k = r['koef'].get(r['base']) if r['base'] and r['koef'] else None
        unit = r['base'] or 'шт'
        note = SKIP.get(pid, '')
        if 'ФС ' in r['name']:
            note = 'было ниже прайса – стояла старая цена в €'
        if 'Лага монтажная' in r['name'] and mark == 'Legro':
            note = 'та же лага, что у Bruggan (40х30х2200); «3000» в прайсе Legro – опечатка'
        if now is not None and abs(now - round(e * RATE)) >= 1 and pid not in SKIP:
            note = (note + '; ' if note else '') + 'ВНИМАНИЕ: цена не совпала с планом'
        ws.append([mark, r['pid'], r['oid'] or '', r['name'], title, e, r['price'], now,
                   (now - r['price']) if now is not None else None, unit,
                   round(r['price'] * k) if k else r['price'], round(now * k) if (k and now) else now, note, r['url']])
        if note:
            for c in ws[ws.max_row]:
                c.fill = yellow
    for col, w in zip('ABCDEFGHIJKLMN', [9, 9, 8, 70, 45, 10, 12, 14, 10, 8, 11, 11, 50, 60]):
        ws.column_dimensions[col].width = w
    ws.freeze_panes = 'A2'
    ws.auto_filter.ref = ws.dimensions
    wb.save(out)
    print('строк', ws.max_row - 1)


if __name__ == '__main__':
    d = Path(sys.argv[2])
    plan(d) if sys.argv[1] == 'plan' else xlsx(d, sys.argv[3])
