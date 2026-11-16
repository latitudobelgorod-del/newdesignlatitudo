# План цен 4SIS по сайту 4sis.ru (16.11.2026) для site_price_4sis.php.
#   python3 site_price_plan_4sis.py <ours.tsv из export_cmp.php> <папка с out_part_*.tsv обхода crawl4sis.py> > plan.tsv
# Сопоставление: артикул производителя (кроме «б/а»), иначе точное название.
# На карточке 4sis.ru есть цена – розничная (зачёркнутая, если идёт акция). Цены нет – NONE.
# Карточку не нашли – товара в плане нет (цену не трогаем, в отчёт – «нет на 4sis.ru»).
import glob, html, io, re, sys


def norm(s):
    s = html.unescape(s or '').lower().replace('ё', 'е')
    s = re.sub(r'\(к\)', '', s)
    s = re.sub(r'[–—-]', '-', s)
    s = re.sub(r'[«»"\']', '', s)
    return re.sub(r'\s+', ' ', s).strip()


def nart(s):
    return re.sub(r'\s+', ' ', (s or '').strip().lower())


def num(x):
    try:
        return float(x)
    except ValueError:
        return 0.0


ours_f, crawl_dir = sys.argv[1:3]
by_name, by_art = {}, {}
for f in sorted(glob.glob(crawl_dir + '/out_part_*.tsv')):
    for l in io.open(f, encoding='utf-8', errors='replace'):
        r = l.rstrip('\n').split('\t')
        if len(r) < 7 or not r[2] or not r[1].startswith('200'):
            continue
        rec = (r[0], num(r[3]), num(r[4]))
        by_name.setdefault(norm(r[2]), rec)
        if r[6] and nart(r[6]) != 'б/а':
            by_art.setdefault(nart(r[6]), rec)

out = io.open(sys.stdout.fileno(), 'w', encoding='utf-8', closefd=False)
for l in io.open(ours_f, encoding='utf-8'):
    r = l.rstrip('\n').split('\t')
    if len(r) < 7 or not r[0].isdigit():
        continue
    pid, name, art = r[0], r[3], r[6]
    s = (by_art.get(nart(art)) if art and nart(art) != 'б/а' else None) or by_name.get(norm(name))
    if not s:
        continue
    url, cur, old = s
    if cur <= 0:
        out.write('%s\tNONE\tна 4sis.ru цены нет %s\n' % (pid, url))
    else:
        out.write('%s\t%d\t4sis.ru розничная %s\n' % (pid, max(cur, old), url))
