#!/bin/bash
# Еженедельно (по воскресеньям из daily_4sis.sh, 23.10.2026): весь сайт 4sis.ru по свежей карте сайта —
#   1) обход карточек (название, адрес) → ~/nd_4sis/4sis.tsv + out_part_*.tsv (их читают site_goods/variants/check);
#   2) товары, которые 4SIS продаёт, а у нас их нет ни под этим названием, ни с этим артикулом, — завести и включить;
#   3) «Доступные варианты» заново по спискам с карточек 4sis.ru.
# Вывод — в журнал ночной сверки (письмо). 4 потока, ~40–60 минут.
cd "$HOME/4sis-sync" || exit 1
PHP=/opt/php/8.2/bin/php
PY=/usr/bin/python3
C="$HOME/nd_4sis"
W="$HOME/4sis-sync/weekly"
mkdir -p "$W"

echo "== еженедельно: обход сайта 4sis.ru"
if ! curl -s -m 120 https://4sis.ru/sitemap.xml -o "$W/sitemap.xml" || [ "$(grep -c '/store/' "$W/sitemap.xml")" -lt 500 ]; then
	echo "ОШИБКА: карта сайта 4sis.ru не скачалась — еженедельный шаг пропущен"
	exit 0
fi
grep -o '<loc>[^<]*/store/[^<]*</loc>' "$W/sitemap.xml" | sed 's#</\?loc>##g' > "$W/urls.txt"
rm -f "$W"/part_* "$C"/out_part_*.tsv
: > "$C/4sis.tsv"
split -n l/4 -d "$W/urls.txt" "$W/part_"
for k in 00 01 02 03; do $PY crawl4sis.py "$W/part_$k" "$C/out_part_$k.tsv" > /dev/null 2>&1 & done; wait
echo "карточек товаров: $(cat "$C"/out_part_*.tsv | awk -F'\t' '$3!=""' | wc -l)"

echo "== еженедельно: товары с сайта 4sis.ru, которых у нас нет"
$PHP export_cmp.php > "$W/ours.tsv"
rm -f "$W"/site_goods_*.json
for k in 0 1 2 3; do $PY site_goods_4sis.py "$W/ours.tsv" daily/feed.xml "$W/site_goods_$k.json" $k 4 > /dev/null 2>&1 & done; wait
$PY - "$W" <<'PY'
import glob, json, sys
d = {}
for f in glob.glob(sys.argv[1] + '/site_goods_[0-9].json'):
    d.update(json.load(open(f)))
json.dump(d, open(sys.argv[1] + '/site_goods.json', 'w'), ensure_ascii=False)
PY
$PHP new_goods_4sis.php --feed="$HOME/4sis-sync/daily/feed.xml" --site="$W/site_goods.json" apply
NEW=$(ls -dt "$HOME"/4sis-sync/*_new_apply 2>/dev/null | head -1)/created.json
if [ -s "$NEW" ] && [ "$(cat "$NEW")" != "[]" ]; then
	$PHP nd_activate.php Y "$($PY -c "import json,sys;print(','.join(map(str,json.load(open(sys.argv[1])))))" "$NEW")" > /dev/null
	echo "новые товары с сайта включены"
fi

echo "== еженедельно: доступные варианты"
rm -f "$W"/variants_*.json
for k in 0 1 2 3; do $PY variants_4sis.py "$W/variants_$k.json" $k 4 > /dev/null 2>&1 & done; wait
$PY - "$W" <<'PY'
import glob, json, sys
d = {}
for f in glob.glob(sys.argv[1] + '/variants_[0-9].json'):
    d.update(json.load(open(f)))
json.dump(d, open(sys.argv[1] + '/variants.json', 'w'), ensure_ascii=False)
print('карточек с вариантами:', sum(1 for v in d.values() if v['variants']))
PY
$PHP variants_apply_4sis.php --json="$W/variants.json" apply | grep -E "Товаров с вариантами|Записано"
