# 59 товаров 4SIS, которых нет в фиде: ищем на живом 4sis.ru поиском /store/?q=<модель>, точное совпадение названия.
import difflib, html, io, json, re, sys, time, urllib.parse, urllib.request

def norm(s):
    s = s.lower().replace('ё', 'е')
    return ' '.join(re.sub(r'[^\w]+', ' ', s).split())

def get(u):
    req = urllib.request.Request(u, headers={'User-Agent': 'Mozilla/5.0'})
    with urllib.request.urlopen(req, timeout=60) as r:
        return r.read().decode('utf-8', 'replace')

cache = {}
out = []
for l in io.open('notfeed59.tsv', encoding='utf-8'):
    c = l.rstrip('\n').split('\t')
    id_, name, price = c[0], c[1], c[2]
    m = re.match(r'\s*"([^"]+)"', name)
    q = m.group(1) if m else ' '.join(name.split()[:4])
    if q not in cache:
        found = {}
        for page in range(1, 6):
            s = get('https://4sis.ru/store/?q=' + urllib.parse.quote(q) + ('&PAGEN_1=%d' % page if page > 1 else ''))
            items = re.findall(r'<div class="item-title">\s*<a href="([^"]+)"[^>]*><span>([^<]*)</span>', s)
            new = 0
            for href, t in items:
                t = html.unescape(t).strip()
                if t not in found:
                    found[t] = 'https://4sis.ru' + href; new += 1
            time.sleep(1)
            if not new or 'PAGEN_1=%d' % (page + 1) not in s:
                break
        cache[q] = found
    found = cache[q]
    exact = [t for t in found if norm(t) == norm(name)]
    if exact:
        t = exact[0]; u = found[t]
        s = get(u); time.sleep(1)
        cur = re.search(r'price_matrix_wrapper "?>\s*<div class="price font-bold[^"]*"[^>]*data-value="([0-9.]+)"', s) or re.search(r'itemprop="price" content="([0-9.]+)"', s)
        old = re.search(r'strike_block">\s*<div class="price[^"]*"[^>]*data-value="([0-9.]+)"', s)
        av = re.search(r'itemprop="availability"[^>]*href="[^"]*/(\w+)"', s)
        out.append(dict(id=id_, name=name, price=price, status='есть на 4sis.ru', url=u, cur=cur.group(1) if cur else '', old=old.group(1) if old else '', avail=av.group(1) if av else ''))
    else:
        near = difflib.get_close_matches(norm(name), [norm(t) for t in found], n=2, cutoff=0.8)
        nearT = [t for t in found if norm(t) in near]
        out.append(dict(id=id_, name=name, price=price, status='не найден на 4sis.ru', query=q, results=len(found), near=[(t, found[t]) for t in nearT]))
    print(out[-1]['status'], id_, name[:80], flush=True)
json.dump(out, io.open('check59.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
