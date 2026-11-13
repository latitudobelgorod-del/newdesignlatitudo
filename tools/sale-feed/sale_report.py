# Письмо об изменениях раздела «Распродажа» после сверки с фидом (nd_sale_sync.php → report.json).
# --print – показать, не отправляя; --only-new – только письмо со списком новых.
# Шлём только когда есть что сказать: новые товары, выключенные/включённые, ошибка (Ирина, 26.10.2026).
# Цены и остатки меняются часто — они в журнале на сервере, в письмо идут, только если письмо и так уходит.
import io, json, subprocess, sys
from email.mime.text import MIMEText
from email.header import Header

TO = 'content@latitudo.ru'
FROM = 'info@latitudo.ru'
# ссылки на товар: на сайте (Ирина, 13.11.2026) и в админке
SITE = 'https://latitudo.ru/catalog/rasprodazha/%s/'
ADMIN = 'https://latitudo.ru/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=19&type=aspro_next_catalog&ID=%s'
rep = json.load(io.open(sys.argv[1], encoding='utf-8'))
if not (rep['created'] or rep['deactivated'] or rep['activated'] or rep['error']):
    sys.exit(0)

L = ['Сверка раздела «Распродажа» (https://latitudo.ru/catalog/rasprodazha/) с фидом',
     'https://metatool.ru/api/feeds/latitudo-rasprodazha.json — ' + rep['date'] + '.', '']
if rep['error']:
    L += ['ОШИБКА: ' + rep['error'], 'На сайте ничего не изменено.', '']
if rep['created']:
    L += ['Заведены новые товары: %d (добавлены в акцию «Распродажа остатков»)' % len(rep['created'])]
    for c in rep['created']:
        L.append('  %s %s — %s ₽ → %s ₽, остаток %s%s' % (c['id'], c['name'], c['price'], c['sale'], c['total'],
                 ', фото с товара ' + c['photo'] if c['photo'] else ', БЕЗ ФОТО — нужно подобрать'))
        if c['id']:
            if c.get('code'):
                L.append('    на сайте: ' + SITE % c['code'])
            L.append('    в админке: ' + ADMIN % c['id'])
    L.append('')
# выключенный товар на сайте не открывается (404) — ему ссылка в админку
if rep['deactivated']:
    L += ['Выключены: %d' % len(rep['deactivated'])]
    for c in rep['deactivated']:
        L += ['  %s %s — %s' % (c['id'], c['name'], c['why']), '    в админке: ' + ADMIN % c['id']]
    L.append('')
if rep['activated']:
    L += ['Включены снова: %d' % len(rep['activated'])]
    for c in rep['activated']:
        L.append('  %s %s — %s' % (c['id'], c['name'], c['why']))
        if c.get('code'):
            L.append('    на сайте: ' + SITE % c['code'])
    L.append('')
if rep['updated'] or rep['discounts']:
    L += ['Другие изменения:'] + ['  ' + x for x in rep['updated'] + rep['discounts']] + ['']
L += ['Отчёт на сервере: ' + rep['out']]

subj = 'Распродажа %s: новых %d, выключено %d, включено %d%s' % (rep['date'], len(rep['created']), len(rep['deactivated']),
                                                                   len(rep['activated']), ' — ОШИБКА' if rep['error'] else '')


def money(v):
    return ('%.2f' % v).rstrip('0').rstrip('.').replace('.', ',')


# Второе письмо – только список новых товаров со ссылками на сайт, чтобы переслать дальше (Ирина, 13.11.2026)
new = [c for c in rep['created'] if c['id'] and c.get('code')]
N = ['Новые товары в разделе «Распродажа» (https://latitudo.ru/catalog/rasprodazha/) – %s, %d шт.' % (rep['date'], len(new)), '']
for n, c in enumerate(new, 1):
    price = money(c['price']) + ' ₽' + ('' if c['sale'] == c['price'] else ', со скидкой ' + money(c['sale']) + ' ₽')
    N += ['%d. %s' % (n, c['name'].replace('[Распродажа] ', '', 1)), '   %s, остаток %s' % (price, money(c['total'])),
          '   ' + SITE % c['code'], '']
subjNew = 'Распродажа %s: новые товары на сайте latitudo.ru (%d)' % (rep['date'], len(new))
letters = ([] if '--only-new' in sys.argv else [(subj, L)]) + ([(subjNew, N)] if new else [])

for sj, body in letters:
    if '--print' in sys.argv:  # проверка без отправки
        print(sj + '\n\n' + '\n'.join(body) + '\n' + '=' * 40)
        continue
    msg = MIMEText('\n'.join(body), 'plain', 'utf-8')
    msg['From'] = FROM
    msg['To'] = TO
    msg['Subject'] = Header(sj, 'utf-8')
    subprocess.run(['/usr/sbin/sendmail', '-t', '-i', '-f', FROM], input=msg.as_bytes(), check=False)
