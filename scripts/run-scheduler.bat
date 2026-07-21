@echo off
REM Chạy lịch SmartHR (auto xác nhận lương sau 3 ngày)
cd /d "%~dp0"
php artisan schedule:run
