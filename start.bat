@echo off
echo ========================================
echo  Starting GradSmart (Production Mode)
echo ========================================
echo.
echo [1/2] Building frontend assets...
call npm run build
if errorlevel 1 (
    echo ERROR: Build failed!
    pause
    exit /b 1
)
echo.
echo [2/2] Starting Laravel server...
echo.
echo App will be available at: http://127.0.0.1:8000
echo Press Ctrl+C to stop the server
echo.
php artisan serve
