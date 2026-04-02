@echo off
chcp 65001 >nul
echo ========================================
echo    CRMEB Swoole Loader 安装脚本 (Wnmp专版)
echo ========================================
echo.

set "PHP_DIR=C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64"
set "PHP_EXE=%PHP_DIR%\php.exe"
set "PHP_INI=%PHP_DIR%\php.ini"
set "EXT_DIR=%PHP_DIR%\ext"

echo [1/6] 检查PHP环境...
if not exist "%PHP_EXE%" (
    echo ❌ 错误: 在指定路径未找到 php.exe
    echo    路径: %PHP_EXE%
    echo    请确认PHP安装路径是否正确
    pause
    exit /b 1
)

echo ✅ PHP可执行文件: %PHP_EXE%

echo.
echo [2/6] 检查PHP版本和配置...
"%PHP_EXE%" -v
echo.

echo [3/6] 检查扩展目录...
if not exist "%EXT_DIR%" (
    echo ❌ 错误: 扩展目录不存在: %EXT_DIR%
    pause
    exit /b 1
)
echo ✅ 扩展目录: %EXT_DIR%

echo.
echo [4/6] 检查php.ini文件...
if not exist "%PHP_INI%" (
    echo ⚠️  警告: php.ini文件不存在，尝试查找...
    
    if exist "%PHP_DIR%\php.ini-development" (
        echo 📋 找到开发模板，复制为php.ini...
        copy "%PHP_DIR%\php.ini-development" "%PHP_INI%"
        echo ✅ 已创建php.ini文件
    ) else if exist "%PHP_DIR%\php.ini-production" (
        echo 📋 找到生产模板，复制为php.ini...
        copy "%PHP_DIR%\php.ini-production" "%PHP_INI%"
        echo ✅ 已创建php.ini文件
    ) else (
        echo ❌ 错误: 无法找到php.ini模板文件
        echo    请手动创建php.ini文件或检查PHP安装
        pause
        exit /b 1
    )
) else (
    echo ✅ php.ini文件: %PHP_INI%
)

echo.
echo [5/6] 选择并安装Swoole Loader...

REM 获取脚本所在目录的绝对路径
set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

REM 使用PHP 8.0的Swoole Loader (通常兼容PHP 8.1)
set "SWOOLE_FILE=swoole_loader80_nzts_x64.dll"
set "SWOOLE_DIR=%SCRIPT_DIR%\help\swoole_loader"
set "SOURCE_FILE=%SWOOLE_DIR%\%SWOOLE_FILE%"
set "TARGET_FILE=%EXT_DIR%\%SWOOLE_FILE%"

echo 脚本目录: %SCRIPT_DIR%
echo Swoole目录: %SWOOLE_DIR%
echo 源文件: %SOURCE_FILE%

echo 检查Swoole Loader目录...
if not exist "%SWOOLE_DIR%" (
    echo ❌ 错误: Swoole Loader目录不存在
    echo    期望位置: %SWOOLE_DIR%
    echo.
    echo 🔍 检查help目录内容:
    if exist "%SCRIPT_DIR%\help\" (
        dir /b "%SCRIPT_DIR%\help\" 2>nul
    ) else (
        echo    help目录不存在: %SCRIPT_DIR%\help\
    )
    pause
    exit /b 1
)

echo ✅ Swoole Loader目录存在

echo 检查Swoole Loader文件...
if not exist "%SOURCE_FILE%" (
    echo ❌ 错误: 找不到指定的Swoole Loader文件
    echo    期望文件: %SOURCE_FILE%
    echo.
    echo 📁 可用的Swoole Loader文件:
    dir /b "%SWOOLE_DIR%\*.dll" 2>nul
    echo.
    echo 💡 说明: 使用PHP 8.0的Swoole Loader (通常兼容PHP 8.1)
    pause
    exit /b 1
)

echo 📦 复制Swoole Loader到扩展目录...
copy "%SOURCE_FILE%" "%TARGET_FILE%"
if errorlevel 1 (
    echo ❌ 错误: 复制Swoole Loader失败
    pause
    exit /b 1
)
echo ✅ 已复制: %SWOOLE_FILE%

echo.
echo 📝 配置php.ini...
REM 检查是否已经配置了swoole_loader
findstr /i "swoole_loader" "%PHP_INI%" >nul 2>&1
if errorlevel 1 (
    echo 添加Swoole Loader扩展配置...
    echo. >> "%PHP_INI%"
    echo ; Swoole Loader Extension >> "%PHP_INI%"
    echo extension=%SWOOLE_FILE% >> "%PHP_INI%"
    echo ✅ 已添加扩展配置到php.ini
) else (
    echo ⚠️  php.ini中已存在swoole_loader配置
)

echo.
echo [6/6] 验证安装...
echo 检查Swoole Loader是否成功加载...
"%PHP_EXE%" -m | findstr /i "swoole_loader" >nul 2>&1
if errorlevel 1 (
    echo ❌ Swoole Loader未成功加载
    echo.
    echo 🔍 诊断信息:
    echo PHP版本信息:
    "%PHP_EXE%" -v
    echo.
    echo 已加载的扩展:
    "%PHP_EXE%" -m
    echo.
    echo 💡 可能的解决方案:
    echo 1. PHP 8.0的Swoole Loader可能不完全兼容PHP 8.1
    echo 2. 检查php.ini配置是否正确
    echo 3. 重启Web服务器
    echo 4. 考虑降级到PHP 8.0或寻找PHP 8.1专用的Swoole Loader
    pause
    exit /b 1
) else (
    echo ✅ Swoole Loader安装成功！
    echo.
    echo 📋 安装摘要:
    echo    PHP路径: %PHP_DIR%
    echo    扩展文件: %SWOOLE_FILE%
    echo    配置文件: %PHP_INI%
    echo.
    echo 🎉 现在可以运行CRMEB项目了！
)

echo.
echo 按任意键继续...
pause >nul