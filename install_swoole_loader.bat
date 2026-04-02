@echo off
chcp 65001 >nul
echo ================================
echo    Swoole Loader 自动安装脚本
echo ================================
echo.

echo 检查PHP环境...
php --version 2>nul
if %errorlevel% neq 0 (
    echo [错误] PHP未找到，尝试使用C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64\php.exe
    if exist "C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64\php.exe" (
        set PHP_CMD=C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64\php.exe
        set PHP_DIR=C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64
        echo 使用: C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64\php.exe
    ) else (
        echo [错误] 未找到PHP，请检查安装
        pause
        exit /b 1
    )
) else (
    set PHP_CMD=php
    echo PHP环境正常
    
    REM 获取PHP安装目录
    for /f "tokens=*" %%i in ('php -r "echo PHP_BINARY;"') do set PHP_BINARY=%%i
    for %%i in ("%PHP_BINARY%") do set PHP_DIR=%%~dpi
    set PHP_DIR=%PHP_DIR:~0,-1%
)

echo PHP目录: %PHP_DIR%

echo.
echo 检查PHP版本...
for /f "tokens=2 delims= " %%i in ('php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;"') do set PHP_VERSION=%%i
echo PHP版本: %PHP_VERSION%

echo.
echo 检查PHP扩展目录...
for /f "tokens=*" %%i in ('php -r "echo ini_get('extension_dir');"') do set EXT_DIR=%%i
echo 扩展目录: %EXT_DIR%

REM 如果扩展目录是相对路径，转换为绝对路径
if "%EXT_DIR:~1,1%" neq ":" (
    set EXT_DIR=%PHP_DIR%\%EXT_DIR%
)

echo 完整扩展目录: %EXT_DIR%

echo.
echo 检查是否已安装Swoole Loader...
php -m | findstr /i "swoole_loader" >nul
if %errorlevel% equ 0 (
    echo [信息] Swoole Loader 已安装
    goto :check_version
)

echo.
echo 选择合适的Swoole Loader文件...

REM 检查是否为线程安全版本
php -r "echo ZEND_THREAD_SAFE ? 'ZTS' : 'NTS';" > temp_ts.txt
set /p THREAD_SAFE=<temp_ts.txt
del temp_ts.txt

REM 检查架构
php -r "echo PHP_INT_SIZE == 8 ? 'x64' : 'x86';" > temp_arch.txt
set /p ARCH=<temp_arch.txt
del temp_arch.txt

echo 线程安全: %THREAD_SAFE%
echo 架构: %ARCH%

REM 选择对应的DLL文件
if "%THREAD_SAFE%"=="ZTS" (
    set LOADER_FILE=swoole_loader80_zts_x64.dll
) else (
    set LOADER_FILE=swoole_loader80_nzts_x64.dll
)

echo 选择的Loader文件: %LOADER_FILE%

echo.
echo 检查Loader文件是否存在...
if not exist "help\swoole_loader\%LOADER_FILE%" (
    echo [错误] 找不到 %LOADER_FILE%
    echo 可用的文件:
    dir /b help\swoole_loader\
    pause
    exit /b 1
)

echo.
echo 创建扩展目录（如果不存在）...
if not exist "%EXT_DIR%" (
    mkdir "%EXT_DIR%"
    echo 已创建目录: %EXT_DIR%
)

echo.
echo 复制Swoole Loader到扩展目录...
copy "help\swoole_loader\%LOADER_FILE%" "%EXT_DIR%\php_swoole_loader.dll"
if %errorlevel% neq 0 (
    echo [错误] 复制失败，可能需要管理员权限
    echo 请以管理员身份运行此脚本
    pause
    exit /b 1
)

echo 复制成功: %EXT_DIR%\php_swoole_loader.dll

echo.
echo 查找php.ini文件...
for /f "tokens=*" %%i in ('php --ini ^| findstr "Loaded Configuration File"') do (
    for /f "tokens=4*" %%j in ("%%i") do set PHP_INI=%%j %%k
)

REM 去除可能的多余空格
set PHP_INI=%PHP_INI: =%

if "%PHP_INI%"=="(none)" (
    echo [警告] 未找到php.ini文件，尝试在PHP目录查找...
    if exist "%PHP_DIR%\php.ini" (
        set PHP_INI=%PHP_DIR%\php.ini
    ) else (
        echo [错误] 找不到php.ini文件
        pause
        exit /b 1
    )
)

echo php.ini位置: %PHP_INI%

echo.
echo 检查php.ini中是否已有swoole_loader配置...
findstr /i "swoole_loader" "%PHP_INI%" >nul
if %errorlevel% equ 0 (
    echo [信息] php.ini中已有swoole_loader配置
) else (
    echo 添加swoole_loader扩展到php.ini...
    echo. >> "%PHP_INI%"
    echo ; Swoole Loader Extension >> "%PHP_INI%"
    echo extension=php_swoole_loader >> "%PHP_INI%"
    echo 已添加配置到php.ini
)

:check_version
echo.
echo 验证安装...
php -m | findstr /i "swoole_loader"
if %errorlevel% equ 0 (
    echo [成功] Swoole Loader 安装成功！
    echo.
    echo 扩展信息:
    php -r "if(extension_loaded('swoole_loader')) { echo 'Swoole Loader 版本: ' . phpversion('swoole_loader') . PHP_EOL; } else { echo 'Swoole Loader 未加载' . PHP_EOL; }"
) else (
    echo [错误] Swoole Loader 安装失败
    echo 请检查:
    echo 1. 是否以管理员权限运行
    echo 2. PHP版本是否匹配
    echo 3. 扩展文件是否正确复制
)

echo.
echo 安装完成！现在可以运行项目了。
pause