# Ночная сверка 4SIS, шаг «выключить пропавшие» (правило Ирины 23.10.2026): включённый у нас товар, которого нет
# в фиде, ищем на живом 4sis.ru (/store/?q=<модель>, точное название). Нет на сайте или «Нет в наличии» — выключаем;
# продаётся («Достаточно», «Мало», «Под заказ…») — оставляем и берём цену с их карточки (без акции).
# Наличие — ТОЛЬКО по надписи у цены, которую видит покупатель: schema.org availability у 4SIS врёт (23.10.2026
# «OutOfStock» стоял у товаров с «Достаточно» — по нему 44 товара были выключены зря).
#   python3 check_missing_4sis.py <report.csv от sync_4sis.php> <файл ID на выключение> <файл ID<TAB>цена>
# Если 4sis.ru не ответил — товар не трогаем. Больше 40 товаров за ночь не выключаем (похоже на сбой) — только письмо.
import csv, glob, html, io, json, os, re, sys, time, urllib.error, urllib.parse, urllib.request

LIMIT = 40
# ID товаров, цену которых не трогаем (решение по ним за людьми), — ~/4sis-sync/price_hold.txt, по одному ID в строке
try:
    HOLD = {l.split()[0] for l in io.open(__import__('os').path.expanduser('~/4sis-sync/price_hold.txt'), encoding='utf-8') if l.strip() and not l.startswith('#')}
except IOError:
    HOLD = set()

def norm(s):
    # «(К)» 4SIS дописывает к названиям части товаров — на сопоставление не влияет
    s = s.lower().replace('ё', 'е').replace('(к)', ' ')
    return ' '.join(re.sub(r'[^\w]+', ' ', s).split())

def get(u):
    req = urllib.request.Request(u, headers={'User-Agent': 'Mozilla/5.0'})
    with urllib.request.urlopen(req, timeout=60) as r:
        return r.read().decode('utf-8', 'replace')

cand = []
for r in csv.reader(io.open(sys.argv[1], encoding='utf-8-sig'), delimiter=';'):
    if r and r[0].startswith('нет в фиде') and r[2] == 'Y':
        cand.append((r[1], r[4]))
print('Включённых у нас, но нет в фиде: %d' % len(cand))

def stock_and_price(s):
    i = s.find('cost prices detail')
    blk = s[i:i + 8000] if i >= 0 else ''
    m = re.search(r'class="item-stock[^>]*>.*?<span class="value[^"]*">(.*?)</div>', blk, re.S)
    st = re.sub(r'\s+', ' ', re.sub(r'<[^>]+>', '', m.group(1))).strip() if m else ''
    cur = re.search(r'price_matrix_wrapper "?>\s*<div class="price font-bold[^"]*"[^>]*data-value="([0-9.]+)"', blk)
    old = re.search(r'strike_block">\s*<div class="price[^"]*"[^>]*data-value="([0-9.]+)"', blk)
    return st, max(float(cur.group(1)) if cur else 0, float(old.group(1)) if old else 0)

# Адреса карточек 4sis.ru, известные по обходу сайта (site_goods*.json от еженедельного шага, ключ — адрес).
# Поиск 4sis.ru /store/?q= находит не всё: 27.10.2026 по «Аликанте» он отдавал только лаунж-зону, и 48
# продающихся товаров (заведены 23.10 с сайта) четыре ночи подряд шли «на выключение». Не нашли поиском —
# открываем карточку по известному адресу; «нет на 4sis.ru» — только если её там правда нет (404).
known = {}
for f in glob.glob(os.path.expanduser('~/4sis-sync/site_goods*.json')):
    try:
        for u, x in json.load(io.open(f, encoding='utf-8')).items():
            if isinstance(x, dict) and x.get('name'):
                known.setdefault(norm(x['name']), u)
    except (ValueError, IOError):
        pass

cache, off, prices = {}, [], []
for id_, name in cand:
    m = re.match(r'\s*"([^"]+)"', name)
    q = m.group(1) if m else ' '.join(name.split()[:4])
    try:
        if q not in cache:
            found = {}
            for page in range(1, 6):
                s = get('https://4sis.ru/store/?q=' + urllib.parse.quote(q) + ('&PAGEN_1=%d' % page if page > 1 else ''))
                new = 0
                for href, t in re.findall(r'<div class="item-title">\s*<a href="([^"]+)"[^>]*><span>([^<]*)</span>', s):
                    t = html.unescape(t).strip()
                    if t not in found:
                        found[t] = 'https://4sis.ru' + href; new += 1
                time.sleep(1)
                if not new or 'PAGEN_1=%d' % (page + 1) not in s:
                    break
            cache[q] = found
        exact = [u for t, u in cache[q].items() if norm(t) == norm(name)] or ([known[norm(name)]] if norm(name) in known else [])
        if not exact:
            off.append((id_, name, 'нет на 4sis.ru'))
            continue
        try:
            s = get(exact[0]); time.sleep(1)
        except urllib.error.HTTPError as e:
            if e.code == 404:
                off.append((id_, name, 'нет на 4sis.ru (карточка удалена)'))
                continue
            raise
        st, price = stock_and_price(s)
        if st == '':
            print('  не проверен %s %s — не разобрала наличие на %s' % (id_, name, exact[0]))
        elif 'нет в наличии' in st.lower():
            off.append((id_, name, 'на 4sis.ru «%s»' % st))
        else:
            print('  оставляем %s %s — на 4sis.ru «%s», %d ₽' % (id_, name, st, price))
            if price > 0 and id_ not in HOLD:
                prices.append((id_, price))
    except Exception as e:
        print('  не проверен %s %s — 4sis.ru не ответил: %s' % (id_, name, e))

if len(off) > LIMIT:
    print('ОШИБКА: на выключение %d товаров — больше %d, похоже на сбой, ничего не выключаю' % (len(off), LIMIT))
    for id_, name, why in off:
        print('  %s %s — %s' % (id_, name, why))
    off = []
for id_, name, why in off:
    print('  выключить %s %s — %s' % (id_, name, why))
io.open(sys.argv[2], 'w', encoding='utf-8').write(','.join(i for i, _, _ in off))
io.open(sys.argv[3], 'w', encoding='utf-8').write(''.join('%s\t%d\n' % p for p in prices))
