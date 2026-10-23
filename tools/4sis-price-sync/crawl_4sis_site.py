"""Обход карточек 4sis.ru по карте сайта: название (h1), цена, цена без акции, наличие.

    python -I crawl_4sis_site.py <папка с urls.txt>  ->  <папка>/site.json

urls.txt — адреса из https://4sis.ru/sitemap.xml. Два потока с паузой: сайт поставщика не грузим.
"""
import sys, json, re, html, ssl, time, urllib.request
from concurrent.futures import ThreadPoolExecutor
S = sys.argv[1]
urls = [u.strip() for u in open(S+'/urls.txt') if '/store/' in u and u.strip().rstrip('/').count('/') >= 6]
ctx = ssl.create_default_context()
def get(u):
    for a in range(3):
        try:
            r = urllib.request.urlopen(urllib.request.Request(u, headers={'User-Agent': 'Mozilla/5.0 latitudo-price-check'}), timeout=40, context=ctx)
            return r.geturl(), r.read().decode('utf-8', 'replace')
        except Exception as e:
            err = str(e); time.sleep(3)
    return u, 'ERR ' + err
def one(u):
    final, t = get(u); time.sleep(0.3)
    if t.startswith('ERR'): return dict(url=u, err=t)
    h1 = re.search(r'<h1[^>]*id="pagetitle"[^>]*>(.*?)</h1>', t, re.S)
    p = re.search(r'itemprop="price" content="([\d.]+)"', t)
    if not (h1 and p): return None
    old = re.search(r'discount" data-currency="RUB" data-value="([\d.]+)"', t)
    av = re.search(r'itemprop="availability" href="http://schema.org/(\w+)"', t)
    return dict(url=final, name=html.unescape(re.sub('<[^>]+>', '', h1.group(1))).strip(), price=p.group(1),
                oldprice=old.group(1) if old else '', availability=av.group(1) if av else '')
res = []
with ThreadPoolExecutor(2) as ex:
    for i, r in enumerate(ex.map(one, urls)):
        if r: res.append(r)
        if i % 100 == 0: print(i, len(urls), len(res), flush=True)
json.dump(res, open(S+'/site.json', 'w', encoding='utf-8'), ensure_ascii=False)
print('done', len(res), 'products,', sum(1 for r in res if 'err' in r), 'errors')
