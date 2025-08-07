@echo off
echo === LARAVEL SCHEDULER STATUS CHECK ===
echo Current time: %date% %time%
echo.

cd /D "%~dp0"

echo 1. Scheduled Tasks List:
php artisan schedule:list
echo.

echo 2. Running Schedule (manually):
php artisan schedule:run
echo.

echo 3. Queue Status:
php artisan queue:work --once
echo.

echo 4. Recent Laravel Logs (last 20 lines):
if exist "storage\logs\laravel.log" (
    powershell "Get-Content 'storage\logs\laravel.log' -Tail 20"
) else (
    echo No Laravel log found
)
echo.

echo 5. Check if any PHP processes are running:
tasklist /FI "IMAGENAME eq php.exe" 2>nul
echo.

echo 6. Current directory:
echo %cd%
echo.

echo === END CHECK ===
pause
