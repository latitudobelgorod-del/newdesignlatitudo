#!/bin/bash
# Ежедневная сверка 4SIS (cron 04:00 на latitudo-ru, файлы в ~/4sis-sync, вне www), по просьбе Ирины 23.10.2026:
#   1) свежий фид поставщика;
#   2) новые товары фида — завести (new_goods_4sis.php);
#   3) выключенные товары, которые снова есть в фиде, — включить (activate_from_feed.php);
#   4) «Артикул производителя» и код 1С тем, кто вернулся в фид после заведения (vendor_code_4sis.php, с 28.10);
#   5) цены — с карточек 4sis.ru, НЕ из фида (с 16.11.2026): фид у товаров «под заказ» отдаёт
#      случайные числа (Эспрессо–150 S 57 800 вместо 218 700). Обход карточек наших товаров →
#      site_price_plan_4sis.py → site_price_4sis.php: есть цена – розничная, нет цены – «Цена по
#      запросу» (цену убираем). Убрать больше 40 за ночь – отказ, ОШИБКА в письме.
#      sync_4sis.php теперь только сухой прогон: его report.csv нужен проверке пропавших и акциям.
#   6) письмо с итогами на content@latitudo.ru (daily_report.py).
# Ничего не выключает: фид у 4SIS неполный, товар может пропасть из него и вернуться.
cd "$HOME/4sis-sync" || exit 1
PHP=/opt/php/8.2/bin/php
D=$(date +%Y-%m-%d)
LOG="daily/$D.log"
mkdir -p daily
exec >"$LOG" 2>&1
echo "== $(date '+%d.%m.%Y %H:%M') сверка 4SIS"

if ! curl -s -m 120 -o daily/feed.xml.tmp https://4sis.ru/images/111/XML_OCT_EACH.xml || [ "$(grep -o '<offer ' daily/feed.xml.tmp | wc -l)" -lt 500 ]; then
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
# артикул ставился один раз 23.10 — товары, которых тогда не было в фиде, оставались без него (28.10.2026)
echo "== артикул производителя"
$PHP vendor_code_4sis.php --feed="$FEED" apply | grep -v 'уже всё есть\|нет в фиде'
echo "== сопоставление с фидом (без записи цен)"
$PHP sync_4sis.php --feed="$FEED" | grep -E '^Фид:|^ +[0-9]+ '
echo "== выключить пропавшие"
REPORT=$(ls -dt "$HOME"/4sis-sync/*_dry | head -1)/report.csv
rm -f daily/off.txt daily/site_prices.tsv daily/site_promo.tsv
/usr/bin/python3 check_missing_4sis.py "$REPORT" daily/off.txt daily/site_prices.tsv daily/site_promo.tsv
if [ -s daily/off.txt ]; then
	cp daily/off.txt "daily/off_$D.txt"
	$PHP nd_activate.php N "$(cat daily/off.txt)"
fi
echo "== цены с сайта 4sis.ru"
# адреса карточек наших товаров – из последнего полного обхода (воскресенье, ~/nd_4sis)
$PHP export_cmp.php > daily/ours_now.tsv
/usr/bin/python3 site_price_plan_4sis.py daily/ours_now.tsv "$HOME/nd_4sis" | cut -f3 | grep -o 'https://[^ ]*' | sort -u > daily/crawl_urls.txt
rm -rf daily/crawl && mkdir -p daily/crawl
split -n l/4 -d daily/crawl_urls.txt daily/crawl/part_
for k in 00 01 02 03; do /usr/bin/python3 crawl4sis.py daily/crawl/part_$k daily/crawl/out_part_$k.tsv > /dev/null 2>&1 & done; wait
echo "карточек обойдено: $(cat daily/crawl/out_part_*.tsv | awk -F'	' '$3!=""' | wc -l) из $(wc -l < daily/crawl_urls.txt)"
if [ "$(cat daily/crawl/out_part_*.tsv | awk -F'	' '$3!=""' | wc -l)" -lt "$(( $(wc -l < daily/crawl_urls.txt) * 9 / 10 ))" ]; then
	echo "ОШИБКА: обойдено меньше 90% карточек 4sis.ru – цены не меняю"
else
	/usr/bin/python3 site_price_plan_4sis.py daily/ours_now.tsv daily/crawl > daily/site_plan.tsv
	$PHP site_price_4sis.php daily/site_plan.tsv apply | grep -v '^Сухой'
fi
# стикер «Акция» у товаров, у которых на 4sis.ru акция (цена у нас — розничная, без акции; 27.10.2026)
echo "== стикер Акция"
$PHP promo_4sis.php "$REPORT" daily/site_promo.tsv apply
# по воскресеньям — весь сайт 4sis.ru: товары не из фида, «Доступные варианты»
if [ "$(date +%u)" = "7" ]; then
	./weekly_4sis.sh
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
