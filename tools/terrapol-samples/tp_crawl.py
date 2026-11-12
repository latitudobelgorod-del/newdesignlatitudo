"""Сбор с terrapol.ru карточек недостающих у нас цветов/позиций (12.11.2026).
Обходит нужные разделы, заходит только на страницы с нужными цветами (и в комплекты ограждений
Смарт 3D целиком), с каждой страницы товара берёт название, цену, характеристики и фото галереи.
Бережно: одна страница в секунду.
Запуск: python -X utf8 tp_crawl.py <папка для terrapol.json>
"""
import html
import json
import re
import sys
import time
import urllib.parse
import urllib.request

B = 'https://terrapol.ru'
OUTDIR = sys.argv[1]
START = [
    '/product/ograzhdeniya%20DPK/ograzhdeniya-smart-3d/',
    '/product/ograzhdeniya%20DPK/Ograzhdeniya-praktik/',
    '/product/terrasnaya-doska-DPK/terrasnaya-doska-smart-3d/',
    '/product/terrasnaya-doska-DPK/terrasnaya-doska-klassik/',
    '/product/terrasnaya-doska-DPK/terrasnaya-doska-praktik/',
    '/product/zabornaya-doska%20DPK/zabornaya-doska-smart-3d/',
    '/product/Soputstvuyushie-tovari/komplektuyushchie-dlya-terrasnoy-doski/ugolki-dekorativnye-klassik/',
    '/product/Soputstvuyushie-tovari/komplektuyushchie-dlya-terrasnoy-doski/planki-dekorativnye-smart-3d-klassik/',
    '/product/Stupeni%20DPK/',
]
NEED = ['anis', 'dub-belyenyy', 'venge-sorento', 'tibet', 'provans', 'chyernaya-zhemchuzhina', 'serdolik', 'marengo',
        'ugolki-dekorativnye-klassik', 'planki-dekorativnye-smart-3d-klassik']
SKIP = ['tortsevaya', 'kantri', 'filter', 'compare', '/projects/']


def get(path):
    url = path if path.startswith('http') else B + path
    req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
    time.sleep(1)
    return urllib.request.urlopen(req, timeout=40).read().decode('utf-8', 'replace')


def text_lines(h):
    t = re.sub(r'<script.*?</script>|<style.*?</style>', '', h, flags=re.S)
    t = html.unescape(re.sub(r'<[^>]+>', '\n', t))
    return [l.strip() for l in t.split('\n') if l.strip()]


def parse(path, h):
    title = html.unescape(re.search(r'<title>(.*?)</title>', h, re.S).group(1)).split('—')[0].strip()
    gallery = list(dict.fromkeys(re.findall(
        r'id="big-photo-\d+"[^>]*>\s*<a href="(/upload/[^"]+)"', h)))
    L = text_lines(h)
    price, props = '', {}
    try:
        k = [i for i, l in enumerate(L) if l == title][1]
    except IndexError:
        k = 0
    for l in L[k:k + 15]:
        if '₽' in l:
            price = l
            break
    if 'Характеристики' in L[k:]:
        i = L.index('Характеристики', k) + 1
        while i + 1 < len(L) and L[i] not in ('Вам также понадобится', 'Описание', 'Документы', 'Отзывы'):
            props[L[i]] = L[i + 1]
            i += 2
    return dict(url=B + path, title=title, price=price, props=props, photos=[B + g for g in gallery])


seen, pages, queue = set(), [], list(START)
while queue:
    p = queue.pop(0)
    if p in seen:
        continue
    seen.add(p)
    try:
        h = get(p)
    except Exception as e:
        print('!!', p, e)
        continue
    base = p.split('?')[0]
    if p not in START:
        info = parse(p, h)
        pages.append(info)
        print(len(pages), info['title'], '|', info['price'], '| фото', len(info['photos']))
    for href in re.findall(r'href="(/product/[^"#]+)"', h):
        hb = href.split('?')[0]
        if not hb.startswith(base) or hb == base or any(s in href for s in SKIP):
            continue
        if p in START and not any(n in hb for n in NEED) and 'ograzhdeniya-smart-3d' not in base:
            continue
        if hb.count('/') > 7:
            continue
        queue.append(href)
json.dump(pages, open(OUTDIR + '/terrapol.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('страниц:', len(pages))
