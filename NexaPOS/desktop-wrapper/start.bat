@echo off
setlocal
set PORT=1818
set PHP_DIR=%~dp0php-portable
set WEBROOT=%~dp0..\

REM Cek php.exe
if not exist "%PHP_DIR%\php.exe" (
    echo [ERROR] php.exe tidak ditemukan di %PHP_DIR%
    echo Letakkan PHP portable di folder php-portable (sejajar start.bat)
    pause
    exit /b 1
)

REM Jalankan PHP built-in server (background window minimal)
start "php-server" /min "%PHP_DIR%\php.exe" -S 127.0.0.1:%PORT% -t "%WEBROOT%"

REM Deteksi browser (Edge/Chrome/Firefox) untuk mode app
set URL=http://127.0.0.1:%PORT%/pos/index.php

for %%B in ("%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe") do if exist %%B set BROWSER=%%B
if not defined BROWSER for %%B in ("%ProgramFiles%\Google\Chrome\Application\chrome.exe" "%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe") do if exist %%B set BROWSER=%%B
if not defined BROWSER for %%B in ("%ProgramFiles%\Mozilla Firefox\firefox.exe" "%ProgramFiles(x86)%\Mozilla Firefox\firefox.exe") do if exist %%B set BROWSER=%%B

if defined BROWSER (
    echo Membuka %URL% dengan %BROWSER%
    start "POS" "%BROWSER%" --app="%URL%" --new-window
) else (
    echo Browser tidak ditemukan, buka manual: %URL%
    start "POS" "%URL%"
)

exit /b 0
