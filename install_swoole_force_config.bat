@echo off
chcp 65001 >nul
echo ========================================
echo    强制配置版Swoole Loader安装脚本
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

echo [2] 检查当前PHP配置...
echo 🔍 当前PHP使用的配置:
"%PHP_EXE%" --ini
echo.
echo 🔍 当前扩展目录:
"%PHP_EXE%" -r "echo 'Extension dir: ' . ini_get('extension_dir') . PHP_EOL;"
echo.

echo [3] 检查扩展目录...
if not exist "%EXT_DIR%" (
    echo ❌ 扩展目录不存在: %EXT_DIR%
    pause
    exit /b 1
)
echo ✅ 扩展目录: %EXT_DIR%

echo [4] 检查/创建php.ini...
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

echo [5] 配置扩展目录...
echo 🔧 确保php.ini中扩展目录配置正确...
findstr /i "extension_dir" "%PHP_INI%" >nul 2>&1
if errorlevel 1 (
    echo 添加扩展目录配置...
    echo. >> "%PHP_INI%"
    echo ; Extension directory >> "%PHP_INI%"
    echo extension_dir = "%EXT_DIR%" >> "%PHP_INI%"
    echo ✅ 已添加扩展目录配置
) else (
    echo ⚠️  扩展目录配置已存在，检查是否正确...
    findstr /i "extension_dir.*%EXT_DIR:\=\\%" "%PHP_INI%" >nul 2>&1
    if errorlevel 1 (
        echo ⚠️  扩展目录配置可能不正确，建议手动检查
    )
)

echo [6] 检查Swoole Loader源文件...
echo 源文件: %SWOOLE_SOURCE%
if not exist "%SWOOLE_SOURCE%" (
    echo ❌ Swoole Loader源文件不存在
    echo 📁 检查项目目录:
    dir /b "%PROJECT_DIR%\help\" 2>nul
    pause
    exit /b 1
)
echo ✅ 源文件存在

echo [7] 复制Swoole Loader...
set "TARGET_FILE=%EXT_DIR%\swoole_loader80_nzts_x64.dll"
copy "%SWOOLE_SOURCE%" "%TARGET_FILE%"
if errorlevel 1 (
    echo ❌ 复制失败
    pause
    exit /b 1
)
echo ✅ 复制成功: %TARGET_FILE%

echo [8] 配置Swoole Loader扩展...
findstr /i "swoole_loader" "%PHP_INI%" >nul 2>&1
if errorlevel 1 (
    echo 添加Swoole Loader扩展配置...
    echo. >> "%PHP_INI%"
    echo ; Swoole Loader Extension >> "%PHP_INI%"
    echo extension=swoole_loader80_nzts_x64.dll >> "%PHP_INI%"
    echo ✅ 已添加扩展配置
) else (
    echo ⚠️  Swoole Loader配置已存在
)

echo [9] 使用指定配置验证安装...
echo 🔧 强制使用Wnmp配置文件验证...
"%PHP_EXE%" -c "%PHP_INI%" -m | findstr /i "swoole_loader" >nul 2>&1
if errorlevel 1 (
    echo ❌ 验证失败，Swoole Loader未加载
    echo.
    echo 🔍 使用指定配置的诊断信息:
    echo PHP版本:
    "%PHP_EXE%" -c "%PHP_INI%" -v
    echo.
    echo 配置文件:
    "%PHP_EXE%" -c "%PHP_INI%" --ini
    echo.
    echo 扩展目录:
    "%PHP_EXE%" -c "%PHP_INI%" -r "echo 'Extension dir: ' . ini_get('extension_dir') . PHP_EOL;"
    echo.
    echo 已加载的扩展:
    "%PHP_EXE%" -c "%PHP_INI%" -m
    echo.
    echo 💡 可能的问题:
    echo 1. Swoole Loader文件损坏或不兼容
    echo 2. 缺少依赖的运行库
    echo 3. PHP版本不匹配
) else (
    echo ✅ 安装成功！Swoole Loader已加载
    echo.
    echo 📋 验证信息:
    echo 使用配置文件: %PHP_INI%
    echo 扩展目录: %EXT_DIR%
    echo 扩展文件: %TARGET_FILE%
)

echo.
echo 💡 重要提示:
echo 1. 确保Wnmp服务使用正确的PHP配置
echo 2. 重启Web服务器以应用更改
echo 3. 在项目中使用: "%PHP_EXE%" -c "%PHP_INI%" think run
echo.
pause