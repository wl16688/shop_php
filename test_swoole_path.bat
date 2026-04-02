@echo off
chcp 65001 >nul
echo ========================================
echo    测试Swoole Loader路径
echo ========================================
echo.

echo 当前目录: %CD%
echo 脚本目录: %~dp0

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

echo 处理后脚本目录: %SCRIPT_DIR%

echo.
echo 检查help目录:
set "HELP_DIR=%SCRIPT_DIR%\help"
echo Help目录: %HELP_DIR%
if exist "%HELP_DIR%" (
    echo ✅ help目录存在
    echo 📁 help目录内容:
    dir /b "%HELP_DIR%" 2>nul
) else (
    echo ❌ help目录不存在
)

echo.
echo 检查swoole_loader目录:
set "SWOOLE_DIR=%HELP_DIR%\swoole_loader"
echo Swoole目录: %SWOOLE_DIR%
if exist "%SWOOLE_DIR%" (
    echo ✅ swoole_loader目录存在
    echo 📁 swoole_loader目录内容:
    dir /b "%SWOOLE_DIR%" 2>nul
) else (
    echo ❌ swoole_loader目录不存在
)

echo.
echo 检查具体文件:
set "SWOOLE_FILE=%SWOOLE_DIR%\swoole_loader80_nzts_x64.dll"
echo 文件路径: %SWOOLE_FILE%
if exist "%SWOOLE_FILE%" (
    echo ✅ swoole_loader80_nzts_x64.dll 存在
) else (
    echo ❌ swoole_loader80_nzts_x64.dll 不存在
)

echo.
pause