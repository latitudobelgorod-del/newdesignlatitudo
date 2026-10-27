#!/bin/bash
# Сверка раздела «Распродажа» с фидом metatool (файлы в ~/sale-feed на latitudo-ru, вне www).
# cron раз в минуту (27.10.2026; metatool сам обновляет остатки в фиде раз в ~20 минут — в :03, :23, :43,
# связи с ним нет, так что быстрее этого не получится; метки обновлений — sync/stamps.log);
# сверка запускается, только если фид изменился по существу (Ирина, 26.10.2026:
# «на любое изменение»): сравниваем отпечаток товаров, цен и остатков без меток времени фида.
# Раз в сутки (в 04:25) — сверка в любом случае, на случай если на сайте что-то поправили руками.
# Письмо на content@latitudo.ru — только если есть новые/выключенные/включённые товары или ошибка (sale_report.py).
# Отключить — убрать строку из crontab.
cd "$HOME/sale-feed" || exit 1
PHP=/opt/php/8.2/bin/php
FEED_URL=https://metatool.ru/api/feeds/latitudo-rasprodazha.json
mkdir -p sync
LOG="sync/$(date +%Y-%m-%d).log"

# не запускать вторую сверку, пока идёт первая
exec 9>sync/.lock
flock -n 9 || exit 0

curl -s -m 60 -o sync/feed.tmp "$FEED_URL" || { rm -f sync/feed.tmp; exit 0; }
SUM=$(/usr/bin/python3 - sync/feed.tmp <<'EOF'
import hashlib, json, os, sys
try:
    d = json.load(open(sys.argv[1], encoding='utf-8'))
    # как часто metatool обновляет фид: метка stock_updated_at — в sync/stamps.log при каждой смене
    st, last = d.get('stock_updated_at', ''), ''
    if os.path.exists('sync/stamp.last'):
        last = open('sync/stamp.last').read().strip()
    if st and st != last:
        open('sync/stamp.last', 'w').write(st)
        open('sync/stamps.log', 'a').write(st + '\n')
    items = [{k: v for k, v in i.items() if k != 'updated_at'} for i in d['items']]
    print(hashlib.md5(json.dumps(sorted(items, key=lambda i: i.get('xml_id', '')), sort_keys=True).encode()).hexdigest())
except Exception:
    print('bad')
EOF
)
LAST=$(cat sync/last.md5 2>/dev/null)
if [ "$SUM" = "$LAST" ] && [ "$(date +%H%M)" != "0425" ]; then
	rm -f sync/feed.tmp
	exit 0
fi
mv sync/feed.tmp sync/feed.json
{
	echo "== $(date '+%d.%m.%Y %H:%M') фид изменился ($SUM)"
	$PHP nd_sale_sync.php "$HOME/www/latitudo.ru" apply --feed="$HOME/sale-feed/sync/feed.json"
} >>"$LOG" 2>&1
# отпечаток запоминаем и при ошибке: иначе битый фид слал бы письмо каждые 5 минут
echo "$SUM" > sync/last.md5
REP=$(ls -dt sync/*_apply 2>/dev/null | head -1)/report.json
[ -f "$REP" ] && /usr/bin/python3 sale_report.py "$REP" >>"$LOG" 2>&1
# старые прогоны не копим: папки старше 30 дней и журналы старше 60
find sync -maxdepth 1 -type d -name '*_apply' -mtime +30 -exec rm -rf {} + 2>/dev/null
find sync -name '*.log' -mtime +60 -delete
