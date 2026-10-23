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
changed = []
report = reports[-1] + '/report.csv' if reports else ''
if report and os.path.exists(report):
    for r in csv.reader(io.open(report, encoding='utf-8-sig'), delimiter=';'):
        if r and (r[0].startswith('поднять') or r[0].startswith('снизить') or r[0].startswith('поставить')):
            changed.append(r)

created = re.findall(r'^(\d+) (.+?) — фото \d+$', log, re.M)
activated = re.findall(r'^  (\d+) (.+)$', log.split('== включить')[1].split('== цены')[0], re.M) if '== включить' in log and '== цены' in log else []
error = 'ОШИБКА' in log

lines = ['Ежедневная сверка товаров 4SIS с фидом поставщика (https://4sis.ru/images/111/XML_OCT_EACH.xml), ' + date + '.', '']
if error:
    lines += ['ВНИМАНИЕ: в работе были ошибки — см. журнал ниже.', '']
lines += ['Цены изменены: %d' % len(changed)]
for r in changed[:100]:
    lines.append('  %s — %s → %s ₽ (%s%%) %s' % (r[4], r[5], r[6], r[7], r[11]))
if len(changed) > 100:
    lines.append('  … полный список во вложении')
lines += ['', 'Включены (снова есть у 4SIS): %d' % len(activated)] + ['  %s %s' % a for a in activated]
lines += ['', 'Заведены новые товары: %d' % len(created)] + ['  %s %s' % c for c in created]
lines += ['', 'Цена у нас — цена 4SIS без акции. Товары, пропавшие из фида, не выключаются (фид у 4SIS неполный).',
          'Журнал на сервере: ~/4sis-sync/' + sys.argv[1], '', '--- журнал ---', log[-20000:]]

msg = MIMEMultipart()
msg['From'] = FROM
msg['To'] = TO
msg['Subject'] = Header('Сверка 4SIS %s: цен %d, включено %d, новых %d%s' % (date, len(changed), len(activated), len(created), ' — ЕСТЬ ОШИБКИ' if error else ''), 'utf-8')
msg.attach(MIMEText('\n'.join(lines), 'plain', 'utf-8'))
if report and os.path.exists(report):
    part = MIMEApplication(open(report, 'rb').read(), Name='4sis_prices_%s.csv' % date)
    part['Content-Disposition'] = 'attachment; filename="4sis_prices_%s.csv"' % date
    msg.attach(part)
subprocess.run(['/usr/sbin/sendmail', '-t', '-i', '-f', FROM], input=msg.as_bytes(), check=False)
