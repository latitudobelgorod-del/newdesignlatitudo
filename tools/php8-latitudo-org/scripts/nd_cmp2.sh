#!/bin/bash
# nd_cmp.sh <путь> — живой сайт 7.4 против копии 8.3: размер и число отличающихся строк HTML
U=$1; INI=/var/www/php-bin/u3018995/latitudo.org/php.ini
A=$(ND_SAVE=/tmp/nd_cA.html ND_DISPLAY=0 ND_INI=$INI ~/nd_render.sh latitudo.org 7.4 "$U")
B=$(ND_SAVE=/tmp/nd_cB.html ND_DISPLAY=0 ND_INI=$INI ND_ROOT=$HOME/nd_stage/latitudo.org ~/nd_render.sh latitudo.org ${ND_V:-8.3} "$U")
perl ~/nd_norm.pl /tmp/nd_cA.html > /tmp/nd_cA.n; perl ~/nd_norm.pl /tmp/nd_cB.html > /tmp/nd_cB.n
D=$(diff /tmp/nd_cA.n /tmp/nd_cB.n | grep -c "^[<>]")
EXC=$(grep -m1 -oE "\[[A-Za-z\]*(Error|Exception)\][^<]*|При выполнении скрипта" /tmp/nd_cB.html)
echo "$U | 7.4: $A | 8.3: $B | разн.строк: $D $EXC"
