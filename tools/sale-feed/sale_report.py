# Письмо об изменениях раздела «Распродажа» после сверки с фидом (nd_sale_sync.php → report.json).
# Шлём только когда есть что сказать: новые товары, выключенные/включённые, ошибка (Ирина, 26.10.2026).
# Цены и остатки меняются часто — они в журнале на сервере, в письмо идут, только если письмо и так уходит.
import io, json, subprocess, sys
from email.mime.text import MIMEText
from email.header import Header

TO = 'content@latitudo.ru'
FROM = 'info@latitudo.ru'
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
            L.append('    https://latitudo.ru/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=19&type=aspro_next_catalog&ID=%s' % c['id'])
    L.append('')
if rep['deactivated']:
    L += ['Выключены: %d' % len(rep['deactivated'])] + ['  %s %s — %s' % (c['id'], c['name'], c['why']) for c in rep['deactivated']] + ['']
if rep['activated']:
    L += ['Включены снова: %d' % len(rep['activated'])] + ['  %s %s — %s' % (c['id'], c['name'], c['why']) for c in rep['activated']] + ['']
if rep['updated'] or rep['discounts']:
    L += ['Другие изменения:'] + ['  ' + x for x in rep['updated'] + rep['discounts']] + ['']
L += ['Отчёт на сервере: ' + rep['out']]

subj = 'Распродажа %s: новых %d, выключено %d, включено %d%s' % (rep['date'], len(rep['created']), len(rep['deactivated']),
                                                                   len(rep['activated']), ' — ОШИБКА' if rep['error'] else '')
if '--print' in sys.argv:  # проверка без отправки
    print(subj + '\n\n' + '\n'.join(L))
    sys.exit(0)
msg = MIMEText('\n'.join(L), 'plain', 'utf-8')
msg['From'] = FROM
msg['To'] = TO
msg['Subject'] = Header(subj, 'utf-8')
subprocess.run(['/usr/sbin/sendmail', '-t', '-i', '-f', FROM], input=msg.as_bytes(), check=False)
