# Письмо с итогами ежедневной сверки 4SIS: журнал daily_4sis.sh + отчёт цен (report.csv) во вложении.
import csv, glob, io, os, re, subprocess, sys
from email.mime.application import MIMEApplication
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.header import Header

TO = 'content@latitudo.ru'
FROM = 'info@latitudo.ru'
log = io.open(sys.argv[1], encoding='utf-8', errors='replace').read()
date = os.path.basename(sys.argv[1])[:10]

# отчёт цен этого прогона: последняя папка *_apply от sync_4sis.php
reports = sorted(d for d in glob.glob(os.path.expanduser('~/4sis-sync/' + date.replace('-', '-') + '_*_apply')) if '_new_' not in d and '_vendor_' not in d)
# за ночь цены пишутся дважды (по фиду и по сайту 4sis.ru) — собираем изменения из всех прогонов этого дня
reports = [d for d in reports if os.path.getmtime(d) > __import__('time').time() - 6 * 3600]
changed = []
for d in reports:
    if os.path.exists(d + '/report.csv'):
        for r in csv.reader(io.open(d + '/report.csv', encoding='utf-8-sig'), delimiter=';'):
            if r and (r[0].startswith('поднять') or r[0].startswith('снизить') or r[0].startswith('поставить')):
                changed.append(r)
report = reports[0] + '/report.csv' if reports else ''

created = re.findall(r'^(\d+) (.+?) — фото \d+$', log, re.M)
activated = re.findall(r'^  (\d+) (.+)$', log.split('== включить')[1].split('== цены')[0], re.M) if '== включить' in log and '== цены' in log else []
deactivated = re.findall(r'^  выключить (\d+) (.+)$', log, re.M)
error = 'ОШИБКА' in log or 'Traceback' in log
xlsx = os.path.expanduser('~/4sis-sync/daily/Сверка_цен_4SIS_%s.xlsx' % date)

# Письмо — коротко и о том, что требует внимания (Ирина, 27.10.2026): раньше в тело шёл хвост журнала,
# 150+ строк «оставляем …» съедали начало, а ошибка терялась внизу. Полный журнал — вложением.
kept = len(re.findall(r'^  оставляем ', log, re.M))
unchecked = re.findall(r'^  не проверен (\d+) (.+)$', log, re.M)
errors = [l for l in log.splitlines() if l.startswith('ОШИБКА') or 'Traceback' in l]
# товары, которые предохранитель не дал выключить: строки списка сразу после «ОШИБКА: на выключение …»
blocked = []
m = re.search(r'^ОШИБКА: на выключение.*\n((?:  .+\n?)*)', log, re.M)
if m:
    blocked = re.findall(r'^  (\d+) (.+)$', m.group(1), re.M)
# итог сопоставления цен из sync_4sis.php: «Фид: N товаров; у нас 4SIS: M» и строки «   898 цена совпадает»
stats = []
m = re.search(r'^(Фид: .+)\n((?:\s+\d+ .+\n)+)', log, re.M)
if m:
    stats = [m.group(1)] + [' '.join(l.split()) for l in m.group(2).splitlines()]
weekly = re.findall(r'^Товаров с вариантами: .+$', log, re.M)
# стикер «Акция» (promo_4sis.php): итоговая строка и списки поставленных/снятых
promo_line = re.findall(r'^Акция у 4SIS: .+$', log, re.M)
promo_add = re.findall(r'^  \+ (\d+ .+)$', log, re.M)
promo_del = re.findall(r'^  − (\d+ .+ — акция у 4SIS закончилась)$', log, re.M)

lines = ['Ежедневная сверка товаров 4SIS с фидом поставщика (https://4sis.ru/images/111/XML_OCT_EACH.xml), ' + date + '.', '']
if error:
    lines += ['ВНИМАНИЕ, ошибки:'] + ['  ' + e for e in errors] + ['']
lines += ['Цены изменены: %d' % len(changed)]
for r in changed[:100]:
    lines.append('  %s — %s → %s ₽ (%s%%) %s' % (r[4], r[5], r[6], r[7], r[11]))
if len(changed) > 100:
    lines.append('  … полный список во вложении')
lines += ['', 'Включены (снова есть у 4SIS): %d' % len(activated)] + ['  %s %s' % a for a in activated]
lines += ['', 'Заведены новые товары: %d' % len(created)] + ['  %s %s' % c for c in created]
lines += ['', 'Выключены (нет в фиде, а на 4sis.ru нет или «нет в наличии»): %d' % len(deactivated)] + ['  %s %s' % d for d in deactivated]
if blocked:
    lines += ['', 'НЕ выключены — сработал предохранитель (за ночь больше 40), проверьте вручную: %d' % len(blocked)] + ['  %s %s' % b for b in blocked]
lines += ['', 'Товары не из фида, проверенные по сайту 4sis.ru: в продаже %d, не проверены %d' % (kept, len(unchecked))]
lines += ['  не проверен %s %s' % u for u in unchecked]
if promo_line:
    lines += ['', 'Стикер «Акция» (на 4sis.ru акция, цена у нас розничная): ' + promo_line[-1].split(': ', 1)[1]]
    for title, items in (('поставлен', promo_add), ('снят', promo_del)):
        if items:
            lines += ['  %s:' % title] + ['    ' + x for x in items[:30]]
            if len(items) > 30:
                lines.append('    … и ещё %d — в журнале во вложении' % (len(items) - 30))
if stats:
    lines += ['', 'Сопоставление цен:'] + ['  ' + s for s in stats]
if weekly:
    lines += ['', 'Еженедельно: ' + '; '.join(weekly)]
lines += ['', 'Цена у нас — цена 4SIS без акции. Во вложении — таблица сверки (xlsx), отчёт по ценам (csv) и полный журнал (txt).',
          'Журнал на сервере: ~/4sis-sync/' + sys.argv[1]]
if '--print' in sys.argv:  # проверка без отправки
    print('\n'.join(lines))
    sys.exit(0)

msg = MIMEMultipart()
msg['From'] = FROM
msg['To'] = TO
msg['Subject'] = Header('Сверка 4SIS %s: цен %d, включено %d, выключено %d, новых %d%s' % (date, len(changed), len(activated), len(deactivated), len(created), ' — ЕСТЬ ОШИБКИ' if error else ''), 'utf-8')
msg.attach(MIMEText('\n'.join(lines), 'plain', 'utf-8'))
if os.path.exists(xlsx):
    part = MIMEApplication(open(xlsx, 'rb').read(), Name='4sis_%s.xlsx' % date)
    part.add_header('Content-Disposition', 'attachment', filename=('utf-8', '', os.path.basename(xlsx)))
    msg.attach(part)
part = MIMEApplication(log.encode('utf-8'), Name='4sis_log_%s.txt' % date)
part['Content-Disposition'] = 'attachment; filename="4sis_log_%s.txt"' % date
msg.attach(part)
if report and os.path.exists(report):
    part = MIMEApplication(open(report, 'rb').read(), Name='4sis_prices_%s.csv' % date)
    part['Content-Disposition'] = 'attachment; filename="4sis_prices_%s.csv"' % date
    msg.attach(part)
subprocess.run(['/usr/sbin/sendmail', '-t', '-i', '-f', FROM], input=msg.as_bytes(), check=False)
