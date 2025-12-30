@echo off
REM Stop PHP built-in server by window title
for /f "tokens=2" %%i in ('tasklist /FI "WINDOWTITLE eq php-server" /NH') do taskkill /PID %%i /F >nul 2>&1
exit /b 0
