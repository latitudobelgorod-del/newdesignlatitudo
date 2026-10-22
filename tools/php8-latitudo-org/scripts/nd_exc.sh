#!/bin/bash
# nd_exc.sh <путь> — отрисовать копию на 8.3 и показать первое исключение с кодом вокруг
U=${1:-/}; F=/tmp/nd_u3_exc.html
ND_SAVE=$F ND_DISPLAY=0 ND_ROOT=$HOME/nd_stage/latitudo.org ND_INI=/var/www/php-bin/u3018995/latitudo.org/php.ini ~/nd_render.sh latitudo.org 8.3 "$U"
L=$(grep -A3 ">\[[A-Za-z\]*\(Error\|Exception\)\]\|^\[[A-Za-z\]*\(Error\|Exception\)\]" $F | sed -E "s#<[^>]+>##g" | head -4)
echo "$L"
P=$(echo "$L" | grep -oE "/var/www[^ ]+\.php:[0-9]+" | head -1)
[ -n "$P" ] && { f=${P%:*}; n=${P##*:}; sed -n "$((n-3)),$((n+2))p" "$f" | cat -n; }
