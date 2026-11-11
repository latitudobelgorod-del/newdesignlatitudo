<?php
// latitudo.org: getMailEol() → CRLF на PHP 8 (как в ядре 26.x latitudo.ru). 11.11.2026.
// php nd_mail_eol.php run|rollback   бэкап ~/org-backups/mail.php.bak-20261111
$f = getenv('HOME') . '/www/latitudo.org/bitrix/modules/main/lib/mail/mail.php';
$bak = getenv('HOME') . '/org-backups/mail.php.bak-20261111';
if (($argv[1] ?? '') === 'rollback') { copy($bak, $f); exit("restored\n"); }
$s = file_get_contents($f);
if (strpos($s, 'phpversion())[0]) >= 8') !== false) exit("already patched\n");
$nl = strpos($s, "\r\n") !== false ? "\r\n" : "\n";
$old = "\t\tif(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')";
if (substr_count($s, $old) !== 1) exit("not found\n");
@mkdir(dirname($bak));
if (!file_exists($bak)) copy($f, $bak);
$new = "\t\t// PHP 8: mail() разделяет заголовки через CRLF — с одиночным LF письма приходили «сырыми»{$nl}"
     . "\t\t// (видны Content-Type и HTML). Перенесено из ядра 26.x latitudo.ru, 11.11.2026.{$nl}"
     . "\t\tif ((int)(explode('.', phpversion())[0]) >= 8){$nl}\t\t{{$nl}\t\t\t\$eol = \"\r\n\";{$nl}\t\t}{$nl}"
     . "\t\telseif(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')";
file_put_contents($f, str_replace($old, $new, $s));
echo "patched\n";
