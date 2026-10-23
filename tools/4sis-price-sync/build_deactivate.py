"""Список товаров 4SIS на снятие с latitudo.ru: нет ни в фиде поставщика, ни на сайте 4sis.ru.

    python -I build_deactivate.py <feed.json> <site.json> <ours.tsv> <out_dir>

feed.json — разобранный фид (id, name, price, oldprice), site.json — обход карточек 4sis.ru
(url, name, price, oldprice, availability), ours.tsv — наши товары бренда (ID, ACTIVE, XML_ID, NAME, PRICE).
Пишет deactivate.txt (ID для sync_4sis.php --deactivate), site_prices.tsv (ID, цена без акции
с карточки 4sis.ru — для товаров, которых нет в фиде, но они есть на сайте; --site-prices)
и not_in_feed.csv — разбор всех,
кого нет в фиде, с ближайшим названием на 4sis.ru для ручной проверки.
"""
import csv
import difflib
import json
import re
import sys

feed_path, site_path, ours_path, out = sys.argv[1:5]
feed = json.load(open(feed_path, encoding='utf-8'))
site = [s for s in json.load(open(site_path, encoding='utf-8')) if 'err' not in s]
ours = list(csv.DictReader(open(ours_path, encoding='utf-8'), delimiter='\t'))


def norm(s):
    s = s.lower().replace('ё', 'е')
    s = re.sub(r'["«»“”„]', '', s)
    return re.sub(r'[\s,]+', ' ', s).strip()


feed_ids = {f['id'] for f in feed}
site_by_name = {}
for s in site:
    site_by_name.setdefault(norm(s['name']), s)
# Товары 4SIS, у которых на latitudo.ru уже есть свой двойник (по коду 1С или по названию).
# Если наш «пропавший» товар похож на такой — это старый дубль, снимаем. Если похож на товар
# без двойника — скорее всего, 4SIS его переименовали: не снимаем, отдаём на ручную проверку.
feed_name_by_id = {f['id']: norm(f['name']) for f in feed}
covered = {feed_name_by_id[o['XML_ID']] for o in ours if o['XML_ID'] in feed_name_by_id}
covered |= {norm(o['NAME']) for o in ours if o['XML_ID'] in feed_ids}

deact = []
site_prices = []
with open(out + '/not_in_feed.csv', 'w', encoding='utf-8-sig', newline='') as fh:
    w = csv.writer(fh, delimiter=';')
    w.writerow(['Решение', 'ID', 'Активен', 'Код 1С', 'Название у нас', 'Цена у нас',
                'На 4sis.ru: название', 'цена', 'без акции', 'ссылка', 'Похожее на 4sis.ru (для проверки)'])
    for o in ours:
        if o['XML_ID'] in feed_ids:
            continue
        s = site_by_name.get(norm(o['NAME']))
        near = ''
        if s:
            decision = 'оставить: есть на 4sis.ru'
            regular = max(float(s['price'] or 0), float(s['oldprice'] or 0))
            if regular > 0:
                site_prices.append(f"{o['ID']}\t{regular:g}\t{s['url']}")
        else:
            m = difflib.get_close_matches(norm(o['NAME']), site_by_name.keys(), n=1, cutoff=0.8)
            near = site_by_name[m[0]]['name'] + ' — ' + site_by_name[m[0]]['url'] if m else ''
            renamed = m and m[0] not in covered and difflib.SequenceMatcher(None, norm(o['NAME']), m[0]).ratio() >= 0.9
            if o['ACTIVE'] != 'Y':
                decision = 'уже не активен'
            elif renamed:
                decision = 'проверить: похоже, переименован у 4SIS'
            else:
                decision = 'деактивировать'
                deact.append(o['ID'])
        w.writerow([decision, o['ID'], o['ACTIVE'], o['XML_ID'], o['NAME'], o['PRICE'],
                    s['name'] if s else '', s['price'] if s else '', s['oldprice'] if s else '',
                    s['url'] if s else '', near])

open(out + '/deactivate.txt', 'w').write('\n'.join(deact) + '\n')
open(out + '/site_prices.tsv', 'w', encoding='utf-8').write('\n'.join(site_prices) + '\n')
print('deactivate:', len(deact), 'site prices:', len(site_prices))
