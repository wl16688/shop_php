@echo off
chcp 65001 >nul
echo ========================================
echo    Wnmp环境诊断脚本
echo ========================================
echo.

set "PHP_DIR=C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64"
set "PHP_EXE=%PHP_DIR%\php.exe"
set "PHP_INI=%PHP_DIR%\php.ini"
set "EXT_DIR=%PHP_DIR%\ext"

echo [1] 检查Wnmp PHP环境...
echo PHP目录: %PHP_DIR%
if exist "%PHP_DIR%" (
    echo ✅ PHP目录存在
) else (
    echo ❌ PHP目录不存在
    goto :end
)

if exist "%PHP_EXE%" (
    echo ✅ php.exe存在
) else (
    echo ❌ php.exe不存在
    goto :end
)

echo.
echo [2] PHP版本信息...
"%PHP_EXE%" -v
echo.

echo [3] PHP配置信息...
echo 配置文件: %PHP_INI%
if exist "%PHP_INI%" (
    echo ✅ php.ini存在
) else (
    echo ❌ php.ini不存在
    echo 📋 可用的配置模板:
    if exist "%PHP_DIR%\php.ini-development" echo    - php.ini-development
    if exist "%PHP_DIR%\php.ini-production" echo    - php.ini-production
)

echo.
echo 扩展目录: %EXT_DIR%
if exist "%EXT_DIR%" (
    echo ✅ 扩展目录存在
    echo 📁 扩展目录内容:
    dir /b "%EXT_DIR%\*.dll" 2>nul | findstr /v "^$"
) else (
    echo ❌ 扩展目录不存在
)

echo.
echo [4] 已加载的扩展...
"%PHP_EXE%" -m

echo.
echo [5] 检查关键扩展...
echo 检查MySQLi:
"%PHP_EXE%" -m | findstr /i "mysqli" >nul 2>&1
if errorlevel 1 (echo ❌ 未安装) else (echo ✅ 已安装)

echo 检查Redis:
"%PHP_EXE%" -m | findstr /i "redis" >nul 2>&1
if errorlevel 1 (echo ❌ 未安装) else (echo ✅ 已安装)

echo 检查Swoole Loader:
"%PHP_EXE%" -m | findstr /i "swoole_loader" >nul 2>&1
if errorlevel 1 (echo ❌ 未安装) else (echo ✅ 已安装)

echo 检查OpenSSL:
"%PHP_EXE%" -m | findstr /i "openssl" >nul 2>&1
if errorlevel 1 (echo ❌ 未安装) else (echo ✅ 已安装)

echo 检查cURL:
"%PHP_EXE%" -m | findstr /i "curl" >nul 2>&1
if errorlevel 1 (echo ❌ 未安装) else (echo ✅ 已安装)

echo 检查Fileinfo:
"%PHP_EXE%" -m | findstr /i "fileinfo" >nul 2>&1
if errorlevel 1 (echo ❌ 未安装) else (echo ✅ 已安装)

echo.
echo [6] 检查项目Swoole Loader文件...
echo 项目Swoole Loader目录: %~dp0help\swoole_loader
if exist "%~dp0help\swoole_loader\" (
    echo ✅ 目录存在
    echo 📁 可用文件:
    dir /b "%~dp0help\swoole_loader\*.dll" 2>nul
    echo.
    echo 💡 推荐文件: swoole_loader80_nzts_x64.dll (适用于PHP 8.1 NTS x64)
) else (
    echo ❌ 目录不存在
)

echo.
echo [7] 环境建议...
echo 🔧 针对您的Wnmp环境的建议:
echo    1. PHP版本: 8.1.33 (NTS, x64) ✅
echo    2. 推荐使用: swoole_loader80_nzts_x64.dll
echo    3. 如果php.ini不存在，建议复制php.ini-development
echo    4. 确保扩展目录权限正确
echo.

:end
echo 按任意键退出...
pause >nul