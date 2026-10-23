# Обход карточек 4sis.ru из sitemap: название, цена, старая цена (если скидка), наличие. 22.10.2026
import gzip, html, io, json, re, sys, time, urllib.request

OUT = sys.argv[2]
UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36'
urls = [u.strip() for u in io.open(sys.argv[1], encoding='utf-8') if '/store/' in u]
done = set()
try:
    for l in io.open(OUT, encoding='utf-8'):
        done.add(l.split('\t')[0])
except FileNotFoundError:
    pass
f = io.open(OUT, 'a', encoding='utf-8')

def get(u):
    req = urllib.request.Request(u, headers={'User-Agent': UA, 'Accept-Encoding': 'gzip'})
    with urllib.request.urlopen(req, timeout=60) as r:
        b = r.read()
        if r.headers.get('Content-Encoding') == 'gzip':
            b = gzip.decompress(b)
        cs = r.headers.get_content_charset() or ('windows-1251' if b'windows-1251' in b[:3000].lower() else 'utf-8')
        return r.status, b.decode(cs, 'replace')

for n, u in enumerate(urls):
    if u in done:
        continue
    row = [u, '', '', '', '', '', '']
    for attempt in range(3):
        try:
            st, s = get(u)
            break
        except Exception as e:
            st, s = 'ERR ' + str(e)[:60], ''
            time.sleep(5)
    row[1] = str(st)
    if s and 'cost prices detail' in s:  # карточка товара (у разделов этого блока нет)
        h1 = re.search(r'<h1[^>]*id="pagetitle"[^>]*>(.*?)</h1>', s, re.S)
        row[2] = html.unescape(re.sub(r'\s+', ' ', h1.group(1))).strip() if h1 else ''
        i = s.find('cost prices detail')
        blk = s[i:i + 6000]
        # матрица цен: перечёркнутая строка (strike_block) — старая цена, обычная — действующая (акционная, если есть старая)
        cur = re.search(r'price_matrix_wrapper "?>\s*<div class="price font-bold[^"]*"[^>]*data-value="([0-9.]+)"', blk)             or re.search(r'itemprop="price" content="([0-9.]+)"', s)
        old = re.search(r'strike_block">\s*<div class="price[^"]*"[^>]*data-value="([0-9.]+)"', blk)             or re.search(r'class="price discount"[^>]*data-value="([0-9.]+)"', blk)
        row[3] = cur.group(1) if cur else ''
        row[4] = old.group(1) if old else ''
        av = re.search(r'itemprop="availability"[^>]*href="[^"]*/(\w+)"', s)
        row[5] = av.group(1) if av else ''
        art = re.search(r'Артикул[^<]*</span>\s*<span[^>]*>([^<]+)', s)
        row[6] = art.group(1).strip() if art else ''
    elif s:
        row[1] = str(st) + ' section'
    f.write('\t'.join(row) + '\n')
    f.flush()
    time.sleep(1)
f.close()
print('done')
