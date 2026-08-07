# Запуск локальной копии latitudo.ru (OSPanel: Apache + PHP-8.2 + MySQL-5.7).
# Вызывается из start-latitudo-ru.cmd двойным кликом.
#
# Машинонезависимость: корень OSPanel вычисляется от папки проекта, поэтому
# C:\OSPanel и D:\OSPanel одинаково подходят. Готовность проверяется ПО ОТВЕТУ
# САЙТА, а не по портам: на другой машине адреса модулей OSPanel могут быть
# иными, и проверка по порту зря пугала бы ошибкой.
#
# Адреса ниже мы занимаем сами (они же прописаны в .osp\apache\httpd-standalone.conf
# и bitrix\.settings.php), у latitudo-pro.local свои — сайты не конфликтуют.

$ErrorActionPreference = 'Continue'
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$project = Split-Path -Parent $PSScriptRoot
$osp     = Split-Path -Parent (Split-Path -Parent $project)

$mysqlDir  = Join-Path $osp 'modules\MySQL-5.7'
$phpDir    = Join-Path $osp 'modules\PHP-8.2'
$apacheDir = Join-Path $osp 'modules\Apache'
$httpdConf = Join-Path $project '.osp\apache\httpd-standalone.conf'
$site      = 'http://latitudo-newdesign.loc/'

$mysqlAddr  = '127.0.1.26:3306'
$phpAddr    = '127.0.1.35:9001'
$apacheAddr = '127.0.1.12:80'

function Get-SiteCode {
    try {
        $r = Invoke-WebRequest -Uri $site -TimeoutSec 25 -UseBasicParsing -MaximumRedirection 0 -ErrorAction Stop
        return [int]$r.StatusCode
    } catch {
        $resp = $_.Exception.Response
        if ($resp -and $resp.StatusCode) { return [int]$resp.StatusCode }
        return 0   # соединения нет вообще
    }
}

function Test-Listening([string]$addr) {
    # Именно LISTENING: без этого фильтра сюда попадают висящие соединения
    # в TIME_WAIT от уже убитого процесса, и модуль считается запущенным.
    $rx = [regex]::Escape($addr) + '\s+\S+\s+LISTENING'
    return [bool](netstat -an | Select-String -Pattern $rx -Quiet)
}

Write-Host "Проект:  $project"
Write-Host "OSPanel: $osp"
Write-Host ""

if ((Get-SiteCode) -eq 200) {
    Write-Host "Сайт уже работает: HTTP 200 — $site" -ForegroundColor Green
    exit 0
}

Write-Host "Поднимаю окружение..."

# 1) MySQL-5.7. После аварийного выключения ПК стартует до минуты — восстановление InnoDB.
if (Test-Listening $mysqlAddr) {
    Write-Host "  [=] MySQL-5.7 уже работает"
} else {
    Start-Process -FilePath (Join-Path $mysqlDir 'bin\mysqld.exe') `
        -ArgumentList "--defaults-file=`"$(Join-Path $mysqlDir 'my.ini')`"" -WindowStyle Hidden
    Write-Host "  [+] MySQL-5.7"
}

# 2) PHP-8.2 FastCGI. PHP_FCGI_MAX_REQUESTS=0 обязателен: иначе php-cgi завершается
#    после 500 запросов и сайт молча перестаёт отвечать до перезапуска скрипта.
if (Test-Listening $phpAddr) {
    Write-Host "  [=] PHP-8.2 уже работает"
} else {
    $env:PHP_FCGI_MAX_REQUESTS = '0'
    $env:PHP_INI_SCAN_DIR = ''
    Start-Process -FilePath (Join-Path $phpDir 'php-cgi.exe') `
        -ArgumentList '-b', $phpAddr, '-c', (Join-Path $phpDir 'php.ini') -WindowStyle Hidden
    Write-Host "  [+] PHP-8.2 (FastCGI $phpAddr)"
}

# 3) Apache со своим конфигом.
if (Test-Listening $apacheAddr) {
    Write-Host "  [=] Apache уже работает"
} else {
    Start-Process -FilePath (Join-Path $apacheDir 'bin\httpd.exe') `
        -ArgumentList '-d', $apacheDir, '-f', $httpdConf -WindowStyle Hidden
    Write-Host "  [+] Apache ($apacheAddr)"
}

Write-Host ""
Write-Host "Жду ответа сайта (первый запрос долгий — Битрикс пересобирает кэш)..."

$code = 0
foreach ($i in 1..18) {
    Start-Sleep -Seconds 5
    $code = Get-SiteCode
    if ($code -eq 200) { break }
    Write-Host ("  ... попытка {0} из 18, ответ {1}" -f $i, $(if ($code) { $code } else { 'нет соединения' }))
}

if ($code -eq 200) {
    Write-Host ""
    Write-Host "Готово! Сайт отвечает HTTP 200." -ForegroundColor Green
    Write-Host "  $site                — боевой шаблон aspro_next"
    Write-Host "  ${site}?newdesign=Y   — шаблон редизайна aspro_next_newdesign"
    Write-Host "  ${site}bitrix/admin/  — админка"
    exit 0
}

Write-Host ""
Write-Host "Сайт не поднялся." -ForegroundColor Yellow
if ($code -eq 0) {
    Write-Host "Ответа нет вообще. Что проверить:"
    Write-Host "  - есть ли в hosts строка:  127.0.1.12  latitudo-newdesign.loc"
    Write-Host "    (файл C:\Windows\System32\drivers\etc\hosts, править от администратора)"
    Write-Host "  - не занял ли $apacheAddr другой Apache"
} else {
    Write-Host "Сайт ответил кодом $code — смотри логи:"
}
Write-Host "  $osp\logs\domains\latitudo-newdesign.loc_error.log"
Write-Host "  $osp\logs\domains\latitudo-newdesign.loc_php_error.log"
Write-Host "  $osp\logs\Apache\apache_error.log"
Write-Host ""
Write-Host "Частая причина 500 — правки .htaccess под локаль, см. WORKFLOW.md, раздел 6."
exit 1
