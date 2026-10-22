#!/bin/bash
# Отрисовка страницы сайта через php-cgi нужной версии без переключения PHP в панели (22.10.2026).
# nd_render.sh <домен> <версия 7.4|8.3> <путь> → строка: код размер ошибки
D=$1; V=$2; U=$3
ROOT=${ND_ROOT:-$HOME/www/$D}
INI=${ND_INI:-$HOME/php-bin-php74-bx/php.ini}; [ -f "$INI" ] || INI=$HOME/php-bin-php74/php.ini
P=${U%%\?*}; Q=""; [[ "$U" == *\?* ]] && Q=${U#*\?}
if [ -f "$ROOT$P" ]; then S="$ROOT$P"; elif [ -f "$ROOT${P%/}/index.php" ]; then S="$ROOT${P%/}/index.php"; else S="$ROOT/bitrix/urlrewrite.php"; fi
OUT=$(cd "$(dirname "$S")" && env -i PATH=/usr/bin:/bin HOME=$HOME \
  GATEWAY_INTERFACE=CGI/1.1 SERVER_PROTOCOL=HTTP/1.1 REQUEST_METHOD=GET HTTPS=on SERVER_PORT=443 \
  SERVER_NAME=$D HTTP_HOST=$D DOCUMENT_ROOT=$ROOT SCRIPT_FILENAME="$S" SCRIPT_NAME="${S#$ROOT}" \
  REQUEST_URI="$U" QUERY_STRING="$Q" REDIRECT_STATUS=200 REMOTE_ADDR=127.0.0.1 \
  HTTP_USER_AGENT="Mozilla/5.0 (nd php-compat check)" HTTP_ACCEPT="text/html" \
  timeout 60 /opt/php/$V/bin/php-cgi -c "$INI" -d display_errors=${ND_DISPLAY:-1} -d log_errors=1 -d error_log=$HOME/nd_php_err.log -d error_reporting=E_ALL\&~E_NOTICE\&~E_DEPRECATED\&~E_STRICT\&~E_WARNING -d cgi.force_redirect=0 -d auto_prepend_file=$HOME/nd_noagent.php 2>&1)
CODE=$(printf "%s" "$OUT" | head -20 | grep -m1 -i "^Status:" | grep -o "[0-9]\{3\}"); CODE=${CODE:-200}
SIZE=$(printf "%s" "$OUT" | wc -c)
ERR=$(printf "%s" "$OUT" | grep -oE "(Fatal error|Parse error|Uncaught [A-Za-z\]+)[^<]{0,160}" | head -2 | tr "\n" " ")
TITLE=$(printf "%s" "$OUT" | grep -o "<title>[^<]*" | head -1 | cut -c8-60)
[ -n "$ND_SAVE" ] && printf "%s" "$OUT" > "$ND_SAVE"
echo "$CODE $SIZE | $TITLE | $ERR"
