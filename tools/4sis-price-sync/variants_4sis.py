# «Доступные варианты» со страниц 4sis.ru: для каждой карточки 4sis.ru (обход ~/nd_4sis, 22.10.2026) —
# название и список ссылок на варианты. Результат — variants.json {slug: {"name":…, "url":…, "variants":[slug,…]}}.
# slug — последняя часть адреса: пути у 4SIS меняются (sale/…, раздел/…), а она — нет.
#   python3 variants_4sis.py <выход.json> [<номер части> <всего частей>]  — части для обхода в несколько потоков
import glob, html, io, json, os, re, sys, time, urllib.request

H = os.path.expanduser('~')
OUT = sys.argv[1]
PART, PARTS = (int(sys.argv[2]), int(sys.argv[3])) if len(sys.argv) > 3 else (0, 1)

def slug(u):
    return u.rstrip('/').rsplit('/', 1)[-1]

pages = {}
for f in [H + '/nd_4sis/4sis.tsv'] + glob.glob(H + '/nd_4sis/out_part_*.tsv'):
    for l in io.open(f, encoding='utf-8'):
        c = l.rstrip('\n').split('\t')
        if len(c) > 2 and c[2]:
            pages.setdefault(slug(c[0]), c[0])

res = json.load(io.open(OUT, encoding='utf-8')) if os.path.exists(OUT) else {}
for i, (s, u) in enumerate(sorted(pages.items())):
    if s in res or i % PARTS != PART:
        continue
    try:
        req = urllib.request.Request(u, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=60) as r:
            p = r.read().decode('utf-8', 'replace')
            final = r.geturl()
    except Exception as e:
        print('ERR', u, e)
        continue
    h1 = re.search(r'<h1[^>]*id="pagetitle"[^>]*>(.*?)</h1>', p, re.S)
    name = html.unescape(re.sub(r'\s+', ' ', h1.group(1))).strip() if h1 else ''
    blk = ''
    i0 = p.find('Доступные варианты')
    if i0 >= 0:
        i1 = p.find('choose-color__list', i0)
        i2 = p.find('<div class="buy_block', i1)
        blk = p[i1:i2 if i2 > 0 else i1 + 60000]
    variants = [slug(h) for h in re.findall(r'<div class="choose-color__item[^"]*">\s*<a[^>]*href="([^"]+)"', blk)]
    res[s] = {'name': name, 'url': final, 'variants': variants}
    if len(res) % 20 == 0:
        json.dump(res, io.open(OUT, 'w', encoding='utf-8'), ensure_ascii=False)
        print(len(res), flush=True)
    time.sleep(1)
json.dump(res, io.open(OUT, 'w', encoding='utf-8'), ensure_ascii=False)
print('страниц', len(res), 'с вариантами', sum(1 for v in res.values() if v['variants']))
