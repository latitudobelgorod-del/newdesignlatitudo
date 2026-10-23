# Ночная сверка 4SIS, шаг «выключить пропавшие» (правило Ирины 23.10.2026): включённый у нас товар, которого нет
# в фиде, ищем на живом 4sis.ru (/store/?q=<модель>, точное название). Нет на сайте или «нет в наличии» — выключаем.
#   python3 check_missing_4sis.py <report.csv от sync_4sis.php> <файл, куда записать ID на выключение>
# Если 4sis.ru не ответил — товар не трогаем. Больше 40 товаров за ночь не выключаем (похоже на сбой) — только письмо.
import csv, html, io, re, sys, time, urllib.parse, urllib.request

LIMIT = 40

def norm(s):
    s = s.lower().replace('ё', 'е')
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

cache, off = {}, []
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
        exact = [u for t, u in cache[q].items() if norm(t) == norm(name)]
        if not exact:
            off.append((id_, name, 'нет на 4sis.ru'))
            continue
        s = get(exact[0]); time.sleep(1)
        av = re.search(r'itemprop="availability"[^>]*href="[^"]*/(\w+)"', s)
        if av and av.group(1) == 'OutOfStock':
            off.append((id_, name, 'на 4sis.ru нет в наличии'))
        else:
            print('  оставляем %s %s — есть на 4sis.ru %s' % (id_, name, exact[0]))
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
