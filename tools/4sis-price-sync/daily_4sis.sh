#!/bin/bash
# Ежедневная сверка 4SIS (cron 04:00 на latitudo-ru, файлы в ~/4sis-sync, вне www), по просьбе Ирины 23.10.2026:
#   1) свежий фид поставщика;
#   2) новые товары фида — завести (new_goods_4sis.php);
#   3) выключенные товары, которые снова есть в фиде, — включить (activate_from_feed.php);
#   4) цены — по фиду, без акции (sync_4sis.php apply);
#   5) письмо с итогами на content@latitudo.ru (daily_report.py).
# Ничего не выключает: фид у 4SIS неполный, товар может пропасть из него и вернуться.
cd "$HOME/4sis-sync" || exit 1
PHP=/opt/php/8.2/bin/php
D=$(date +%Y-%m-%d)
LOG="daily/$D.log"
mkdir -p daily
exec >"$LOG" 2>&1
echo "== $(date '+%d.%m.%Y %H:%M') сверка 4SIS"

if ! curl -s -m 120 -o daily/feed.xml.tmp https://4sis.ru/images/111/XML_OCT_EACH.xml || [ "$(grep -c '<offer ' daily/feed.xml.tmp)" -lt 500 ]; then
	echo "ОШИБКА: фид не скачался или в нём мало товаров — ничего не меняю"
	/usr/bin/python3 daily_report.py "$LOG"
	exit 1
fi
mv daily/feed.xml.tmp daily/feed.xml
FEED="$HOME/4sis-sync/daily/feed.xml"

echo "== новые товары"
$PHP new_goods_4sis.php --feed="$FEED" apply
echo "== включить вернувшиеся в фид"
$PHP activate_from_feed.php --feed="$FEED" apply
echo "== цены"
$PHP sync_4sis.php --feed="$FEED" apply
echo "== выключить пропавшие"
REPORT=$(ls -dt "$HOME"/4sis-sync/*_apply | grep -v '_new_\|_vendor_' | head -1)/report.csv
rm -f daily/off.txt
/usr/bin/python3 check_missing_4sis.py "$REPORT" daily/off.txt
if [ -s daily/off.txt ]; then
	cp daily/off.txt "daily/off_$D.txt"
	$PHP nd_activate.php N "$(cat daily/off.txt)"
fi
echo "== таблица сверки"
$PHP export_cmp.php > daily/ours_cmp.tsv
cp daily/feed.xml daily/feed_now.xml
cp "$HOME/nd_4sis/4sis.tsv" daily/4sis.tsv 2>/dev/null || : > daily/4sis.tsv
cat "$HOME"/nd_4sis/out_part_*.tsv > daily/4sis_parts.tsv 2>/dev/null || : > daily/4sis_parts.tsv
PYTHONPATH="$HOME/nd_4sis/pkg" /usr/bin/python3 compare_4sis_xlsx.py daily "daily/Сверка_цен_4SIS_$D.xlsx"
echo "== готово $(date '+%H:%M')"
/usr/bin/python3 daily_report.py "$LOG"
find daily -name '*.log' -mtime +60 -delete
