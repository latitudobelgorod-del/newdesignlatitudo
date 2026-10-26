#!/bin/bash
# Сверка раздела «Распродажа» с фидом metatool — cron раз в час на latitudo-ru (файлы в ~/sale-feed, вне www).
# Письмо на content@latitudo.ru — только если есть новые/выключенные/включённые товары или ошибка (sale_report.py).
# Отключить — убрать строку из crontab.
cd "$HOME/sale-feed" || exit 1
PHP=/opt/php/8.2/bin/php
mkdir -p sync
LOG="sync/$(date +%Y-%m-%d).log"
{
	echo "== $(date '+%d.%m.%Y %H:%M')"
	$PHP nd_sale_sync.php "$HOME/www/latitudo.ru" apply
} >>"$LOG" 2>&1
REP=$(ls -dt sync/*_apply 2>/dev/null | head -1)/report.json
[ -f "$REP" ] && /usr/bin/python3 sale_report.py "$REP" >>"$LOG" 2>&1
# старые прогоны без изменений не копим: папки старше 30 дней и журналы старше 60
find sync -maxdepth 1 -type d -name '*_apply' -mtime +30 -exec rm -rf {} + 2>/dev/null
find sync -name '*.log' -mtime +60 -delete
