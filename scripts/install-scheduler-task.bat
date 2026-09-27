@echo off
REM Đăng ký Task Scheduler Windows: Laravel schedule:run cần chạy mỗi phút
REM Chạy file này bằng quyền Administrator một lần.

set TASK_NAME=SmartHR-Scheduler
set PROJECT_DIR=%~dp0..
set BAT_PATH=%~dp0run-scheduler.bat

schtasks /Create /TN "%TASK_NAME%" /TR "\"%BAT_PATH%\"" /SC MINUTE /MO 1 /F

echo.
echo Da dang ky task "%TASK_NAME%" (moi phut).
echo Kiem tra: schtasks /Query /TN "%TASK_NAME%"
pause
