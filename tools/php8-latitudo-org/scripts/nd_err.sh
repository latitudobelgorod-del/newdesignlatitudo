#!/bin/bash
# nd_err.sh <версия> <путь> [корень] — отрисовка с полным выводом ошибок (без HTML), для разбора
V=$1; U=$2; ROOT=${3:-$HOME/nd_stage/latitudo.org}
P=${U%%\?*}; Q=""; [[ "$U" == *\?* ]] && Q=${U#*\?}
if [ -f "$ROOT$P" ]; then S="$ROOT$P"; elif [ -f "$ROOT${P%/}/index.php" ]; then S="$ROOT${P%/}/index.php"; else S="$ROOT/bitrix/urlrewrite.php"; fi
cd "$(dirname "$S")" && env -i PATH=/usr/bin:/bin HOME=$HOME GATEWAY_INTERFACE=CGI/1.1 SERVER_PROTOCOL=HTTP/1.1 REQUEST_METHOD=GET HTTPS=on SERVER_PORT=443 \
  SERVER_NAME=latitudo.org HTTP_HOST=latitudo.org DOCUMENT_ROOT=$ROOT SCRIPT_FILENAME="$S" SCRIPT_NAME="${S#$ROOT}" REQUEST_URI="$U" QUERY_STRING="$Q" \
  REDIRECT_STATUS=200 REMOTE_ADDR=127.0.0.1 HTTP_USER_AGENT="Mozilla/5.0 (nd php-compat check)" \
  timeout 90 /opt/php/$V/bin/php-cgi -c /var/www/php-bin/u3018995/latitudo.org/php.ini -d display_errors=1 -d html_errors=0 -d error_reporting=E_ALL -d cgi.force_redirect=0 -d auto_prepend_file=$HOME/nd_noagent.php 2>&1 \
  | grep -E "^(PHP )?(Fatal error|Parse error|Warning|Deprecated|Notice)|Uncaught|Stack trace|^#[0-9]|При выполнении скрипта" | sed "s#$ROOT/##g; s#$HOME/www/latitudo.org/##g" | awk '!seen[$0]++' | head -${4:-30}
