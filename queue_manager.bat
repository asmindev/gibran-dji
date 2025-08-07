@echo off
setlocal enabledelayedexpansion

echo === QUEUE WORKER MANAGEMENT ===
echo Platform: Windows
echo.

if "%1"=="" (
    echo Usage: queue_manager.bat [start^|stop^|status^|restart]
    echo.
    echo Commands:
    echo   start   - Start the queue worker
    echo   stop    - Stop the queue worker
    echo   status  - Check worker status
    echo   restart - Restart the worker
    echo.
    pause
    exit /b 1
)

set ACTION=%1
cd /D "%~dp0"

echo Action: %ACTION%
echo Working directory: %cd%
echo.

if "%ACTION%"=="start" goto :start
if "%ACTION%"=="stop" goto :stop
if "%ACTION%"=="status" goto :status
if "%ACTION%"=="restart" goto :restart

echo Invalid action: %ACTION%
echo Use: start, stop, status, or restart
pause
exit /b 1

:start
echo Starting queue worker...
php artisan queue:manage start
goto :end

:stop
echo Stopping queue worker...
php artisan queue:manage stop
goto :end

:status
echo Checking queue worker status...
php artisan queue:manage status
goto :end

:restart
echo Restarting queue worker...
php artisan queue:manage restart
goto :end

:end
echo.
echo Operation completed at %date% %time%
if not "%2"=="--no-pause" pause
