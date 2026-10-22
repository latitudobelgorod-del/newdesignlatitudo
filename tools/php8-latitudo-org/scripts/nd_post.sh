#!/bin/bash
# nd_post.sh <версия> <корень> <путь> <тело> — POST через php-cgi, печатает тело ответа (без предупреждений)
V=$1; ROOT=$2; U=$3; B=$4; S="$ROOT${U%%\?*}"; Q=""; [[ "$U" == *\?* ]] && Q=${U#*\?}
cd "$(dirname "$S")" && printf "%s" "$B" | env -i PATH=/usr/bin:/bin HOME=$HOME GATEWAY_INTERFACE=CGI/1.1 SERVER_PROTOCOL=HTTP/1.1 REQUEST_METHOD=POST HTTPS=on SERVER_PORT=443 \
  SERVER_NAME=latitudo.org HTTP_HOST=latitudo.org DOCUMENT_ROOT=$ROOT SCRIPT_FILENAME="$S" SCRIPT_NAME="${S#$ROOT}" REQUEST_URI="$U" QUERY_STRING="$Q" \
  CONTENT_TYPE=application/x-www-form-urlencoded CONTENT_LENGTH=${#B} HTTP_X_REQUESTED_WITH=XMLHttpRequest \
  REDIRECT_STATUS=200 REMOTE_ADDR=127.0.0.1 HTTP_USER_AGENT="Mozilla/5.0 (nd php-compat check)" \
  timeout 90 /opt/php/$V/bin/php-cgi -c /var/www/php-bin/u3018995/latitudo.org/php.ini -d display_errors=0 -d cgi.force_redirect=0 -d auto_prepend_file=$HOME/nd_noagent.php 2>&1 | perl ~/nd_norm.pl | sed -n "/^\r\?$/,\$p" | head -c ${5:-600}
