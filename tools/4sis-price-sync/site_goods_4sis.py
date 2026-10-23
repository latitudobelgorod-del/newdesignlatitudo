# Товары 4sis.ru, которых нет ни у нас, ни в фиде (23.10.2026): данные с их карточки для заведения у нас.
#   python3 site_goods_4sis.py <наши товары ours_cmp.tsv> <feed.xml> <выход.json> [<часть> <частей>]
# Список карточек — обход ~/nd_4sis (карта сайта 22.10). Наличие — по видимой надписи у цены (schema.org у 4SIS врёт).
# Цена — «Розничный прайс-лист» (без акции). Сохраняем и «Нет в наличии» — отбор при заведении.
import glob, html, io, json, os, re, sys, time, urllib.request, xml.etree.ElementTree as ET

H = os.path.expanduser('~')
OURS, FEED, OUT = sys.argv[1], sys.argv[2], sys.argv[3]
PART, PARTS = (int(sys.argv[4]), int(sys.argv[5])) if len(sys.argv) > 5 else (0, 1)

def norm(s):
    s = s.lower().replace('ё', 'е').replace('(к)', ' ')
    return ' '.join(re.sub(r'[^\w]+', ' ', s).split())

def text(s):
    return re.sub(r'\s+', ' ', html.unescape(re.sub(r'<[^>]+>', ' ', s))).strip()

have = {norm(l.split('\t')[3]) for l in io.open(OURS, encoding='utf-8') if l.count('\t') > 3}
have |= {norm(o.findtext('name') or '') for o in ET.parse(FEED).getroot().iter('offer')}

pages = {}
for f in [H + '/nd_4sis/4sis.tsv'] + glob.glob(H + '/nd_4sis/out_part_*.tsv'):
    for l in io.open(f, encoding='utf-8'):
        c = l.rstrip('\n').split('\t')
        if len(c) > 2 and c[2] and norm(c[2]) not in have:
            pages.setdefault(norm(c[2]), c[0])

res = json.load(io.open(OUT, encoding='utf-8')) if os.path.exists(OUT) else {}
for i, (k, u) in enumerate(sorted(pages.items())):
    if u in res or i % PARTS != PART:
        continue
    try:
        req = urllib.request.Request(u, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=60) as r:
            s = r.read().decode('utf-8', 'replace'); final = r.geturl()
    except Exception as e:
        print('ERR', u, e, flush=True)
        continue
    g = {'url': final}
    m = re.search(r'<h1[^>]*id="pagetitle"[^>]*>(.*?)</h1>', s, re.S)
    g['name'] = text(m.group(1)) if m else ''
    i0 = s.find('cost prices detail')
    blk = s[i0:i0 + 8000] if i0 >= 0 else ''
    m = re.search(r'class="item-stock[^>]*>.*?<span class="value[^"]*">(.*?)</div>', blk, re.S)
    g['stock'] = text(m.group(1)) if m else ''
    # цены: строки матрицы «Розничный прайс-лист» / «Прайс-лист АКЦИЯ», иначе одна цена
    vals = [float(v) for v in re.findall(r'class="price[^"]*"[^>]*data-value="([0-9.]+)"', blk)]
    g['price'] = max(vals) if vals else 0
    g['sale'] = min(vals) if len(vals) > 1 and min(vals) < max(vals) else 0
    m = re.search(r'class="article__value"[^>]*>([^<]+)', s)
    g['article'] = m.group(1).strip() if m else ''
    m = re.search(r'<div class="content" itemprop="description">(.*?)</div>\s*</div>\s*<div class="tab-pane', s, re.S)
    if m:
        t = re.sub(r'<br\s*/?>[ \t]*\r?\n?', '\n', m.group(1)); t = re.sub(r'</p>\s*', '\n\n', t)
        t = html.unescape(re.sub(r'<[^>]+>', '', t))
        t = '\n'.join(l.strip() for l in t.replace('\r', '').split('\n'))
        g['desc'] = re.sub(r'\n{3,}', '\n\n', t).strip()
    else:
        g['desc'] = ''
    photos = []
    for p in re.findall(r'<a href="(/upload/[^"]+)" data-fancybox="gallery" class="product-detail-gallery__link', s):
        if p not in photos:
            photos.append(p)
    g['photos'] = ['https://4sis.ru' + p for p in photos]
    i1 = s.find('id="props"')
    props = re.findall(r'<span itemprop="name">(.*?)</span>.*?<span itemprop="value">(.*?)</span>', s[i1:i1 + 30000], re.S) if i1 > 0 else []
    g['props'] = [[text(a), text(b)] for a, b in props if text(b)]
    g['crumbs'] = [text(c) for c in re.findall(r'itemprop="itemListElement"[^>]*>.*?itemprop="name"[^>]*>(.*?)</span>', s, re.S)]
    res[u] = g
    print(len(res), g['stock'], g['price'], g['name'][:70], flush=True)
    if len(res) % 10 == 0:
        json.dump(res, io.open(OUT, 'w', encoding='utf-8'), ensure_ascii=False)
    time.sleep(1)
json.dump(res, io.open(OUT, 'w', encoding='utf-8'), ensure_ascii=False)
print('готово', len(res))
