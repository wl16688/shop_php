@echo off
chcp 65001 >nul
echo ========================================
echo    简化版Swoole Loader安装脚本
echo ========================================
echo.

REM PHP环境配置
set "PHP_DIR=C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64"
set "PHP_EXE=%PHP_DIR%\php.exe"
set "PHP_INI=%PHP_DIR%\php.ini"
set "EXT_DIR=%PHP_DIR%\ext"

REM 项目路径配置
set "PROJECT_DIR=c:\workspace\trae.ai\yyyy"
set "SWOOLE_SOURCE=%PROJECT_DIR%\help\swoole_loader\swoole_loader80_nzts_x64.dll"

echo [1] 检查PHP环境...
if not exist "%PHP_EXE%" (
    echo ❌ PHP不存在: %PHP_EXE%
    pause
    exit /b 1
)
echo ✅ PHP: %PHP_EXE%

echo [2] 检查扩展目录...
if not exist "%EXT_DIR%" (
    echo ❌ 扩展目录不存在: %EXT_DIR%
    pause
    exit /b 1
)
echo ✅ 扩展目录: %EXT_DIR%

echo [3] 检查php.ini...
if not exist "%PHP_INI%" (
    echo ⚠️  php.ini不存在，尝试创建...
    if exist "%PHP_DIR%\php.ini-development" (
        copy "%PHP_DIR%\php.ini-development" "%PHP_INI%"
        echo ✅ 已创建php.ini
    ) else (
        echo ❌ 无法创建php.ini
        pause
        exit /b 1
    )
) else (
    echo ✅ php.ini: %PHP_INI%
)

echo [4] 检查Swoole Loader源文件...
echo 源文件: %SWOOLE_SOURCE%
if not exist "%SWOOLE_SOURCE%" (
    echo ❌ Swoole Loader源文件不存在
    echo 📁 检查项目目录:
    dir /b "%PROJECT_DIR%\help\" 2>nul
    pause
    exit /b 1
)
echo ✅ 源文件存在

echo [5] 复制Swoole Loader...
set "TARGET_FILE=%EXT_DIR%\swoole_loader80_nzts_x64.dll"
copy "%SWOOLE_SOURCE%" "%TARGET_FILE%"
if errorlevel 1 (
    echo ❌ 复制失败
    pause
    exit /b 1
)
echo ✅ 复制成功: %TARGET_FILE%

echo [6] 配置php.ini...
findstr /i "swoole_loader" "%PHP_INI%" >nul 2>&1
if errorlevel 1 (
    echo extension=swoole_loader80_nzts_x64.dll >> "%PHP_INI%"
    echo ✅ 已添加扩展配置
) else (
    echo ⚠️  配置已存在
)

echo [7] 检查PHP配置...
echo 🔍 PHP配置诊断:
echo 使用的php.ini文件:
"%PHP_EXE%" --ini
echo.
echo 扩展目录:
"%PHP_EXE%" -i | findstr "extension_dir" 2>nul
echo.

echo [8] 验证安装...
"%PHP_EXE%" -m | findstr /i "swoole_loader" >nul 2>&1
if errorlevel 1 (
    echo ❌ 验证失败，Swoole Loader未加载
    echo.
    echo 🔍 详细诊断信息:
    echo PHP版本:
    "%PHP_EXE%" -v
    echo.
    echo PHP配置文件:
    "%PHP_EXE%" --ini
    echo.
    echo 扩展目录:
    "%PHP_EXE%" -r "echo ini_get('extension_dir');"
    echo.
    echo.
    echo 已加载的扩展:
    "%PHP_EXE%" -m
    echo.
    echo 💡 问题分析:
    echo 1. PHP可能使用了系统默认配置而不是Wnmp配置
    echo 2. 需要确保PHP使用正确的php.ini文件
    echo 3. 扩展目录路径可能不匹配
    echo.
    echo 🔧 建议解决方案:
    echo 1. 检查环境变量PATH中的PHP路径
    echo 2. 使用完整路径指定php.ini: php -c "%PHP_INI%"
    echo 3. 确认Wnmp服务配置
) else (
    echo ✅ 安装成功！Swoole Loader已加载
)

echo.
pause