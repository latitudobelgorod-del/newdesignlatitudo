@echo off
rem ---------------------------------------------------------------------------
rem  Start of the local copy of latitudo.ru (OSPanel: Apache + PHP-8.2 + MySQL-5.7).
rem  Double-click this file in Explorer.
rem
rem  This launcher is intentionally ASCII-only. cmd.exe decodes a batch file with
rem  the current console codepage, and "chcp 65001" does not always take effect
rem  in time -- Cyrillic comments then turn into garbage and break parsing of
rem  whole blocks. All logic, with the Russian messages, lives in
rem  tools/start-latitudo-ru.ps1 -- PowerShell reads UTF-8 fine.
rem ---------------------------------------------------------------------------

set "PS=powershell"
where pwsh >nul 2>&1 && set "PS=pwsh"

"%PS%" -NoProfile -ExecutionPolicy Bypass -File "%~dp0tools\start-latitudo-ru.ps1"

echo.
pause
