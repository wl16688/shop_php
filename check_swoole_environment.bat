@echo off
chcp 65001 >nul
echo ================================
echo    Swoole环境检测脚本
echo    (基于CRMEB Install.php逻辑)
echo ================================
echo.

echo 正在检测PHP环境...
php --version 2>nul
if %errorlevel% neq 0 (
    echo [错误] PHP未找到
    pause
    exit /b 1
)

echo.
echo ================================
echo 环境信息检测
echo ================================

echo 操作系统: %OS%
echo PHP版本:
php -r "echo PHP_VERSION . PHP_EOL;"

echo PHP运行模式:
php -r "echo php_sapi_name() . PHP_EOL;"

echo PHP配置文件:
php --ini | findstr "Loaded Configuration File"

echo PHP扩展目录:
php -r "echo ini_get('extension_dir') . PHP_EOL;"

echo PHP线程安全:
php -r "echo ZEND_THREAD_SAFE ? '线程安全' : '非线程安全' . PHP_EOL;"

echo PHP位数:
php -r "echo PHP_INT_SIZE == 8 ? '64位' : '32位' . PHP_EOL;"

echo.
echo ================================
echo 扩展检测
echo ================================

echo 检查Redis扩展:
php -m | findstr /i "redis" >nul
if %errorlevel% equ 0 (
    echo [√] Redis扩展已安装
) else (
    echo [×] Redis扩展未安装 - 需要安装
)

echo 检查MySQLi扩展:
php -m | findstr /i "mysqli" >nul
if %errorlevel% equ 0 (
    echo [√] MySQLi扩展已安装
) else (
    echo [×] MySQLi扩展未安装 - 需要安装
)

echo 检查cURL扩展:
php -m | findstr /i "curl" >nul
if %errorlevel% equ 0 (
    echo [√] cURL扩展已安装
) else (
    echo [×] cURL扩展未安装 - 需要安装
)

echo 检查BCMath扩展:
php -m | findstr /i "bcmath" >nul
if %errorlevel% equ 0 (
    echo [√] BCMath扩展已安装
) else (
    echo [×] BCMath扩展未安装 - 需要安装
)

echo 检查OpenSSL扩展:
php -m | findstr /i "openssl" >nul
if %errorlevel% equ 0 (
    echo [√] OpenSSL扩展已安装
) else (
    echo [×] OpenSSL扩展未安装 - 需要安装
)

echo 检查Fileinfo扩展:
php -m | findstr /i "fileinfo" >nul
if %errorlevel% equ 0 (
    echo [√] Fileinfo扩展已安装
) else (
    echo [×] Fileinfo扩展未安装 - 需要安装
)

echo.
echo ================================
echo Swoole Loader检测
echo ================================

php -m | findstr /i "swoole_loader" >nul
if %errorlevel% equ 0 (
    echo [√] Swoole Loader已安装
    echo 版本信息:
    php -r "if(extension_loaded('swoole_loader')) { echo 'Swoole Loader 版本: ' . phpversion('swoole_loader') . PHP_EOL; } else { echo 'Swoole Loader 未正确加载' . PHP_EOL; }"
) else (
    echo [×] Swoole Loader未安装
    echo.
    echo 推荐的Swoole Loader文件:
    
    REM 获取PHP版本
    for /f "tokens=1,2 delims=." %%a in ('php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;"') do (
        set PHP_MAJOR=%%a
        set PHP_MINOR=%%b
    )
    
    REM 检查线程安全
    php -r "echo ZEND_THREAD_SAFE ? 'ZTS' : 'NTS';" > temp_ts.txt
    set /p THREAD_SAFE=<temp_ts.txt
    del temp_ts.txt
    
    if "%THREAD_SAFE%"=="ZTS" (
        echo - swoole_loader%PHP_MAJOR%%PHP_MINOR%_zts_x64.dll （线程安全版本）
        set RECOMMENDED_FILE=swoole_loader%PHP_MAJOR%%PHP_MINOR%_zts_x64.dll
    ) else (
        echo - swoole_loader%PHP_MAJOR%%PHP_MINOR%_nzts_x64.dll （非线程安全版本）
        set RECOMMENDED_FILE=swoole_loader%PHP_MAJOR%%PHP_MINOR%_nzts_x64.dll
    )
    
    echo.
    echo 检查可用的Swoole Loader文件:
    if exist "help\swoole_loader\%RECOMMENDED_FILE%" (
        echo [√] 找到推荐文件: help\swoole_loader\%RECOMMENDED_FILE%
    ) else (
        echo [×] 未找到推荐文件: %RECOMMENDED_FILE%
        echo 可用文件:
        if exist "help\swoole_loader" (
            dir /b help\swoole_loader\*.dll 2>nul
        ) else (
            echo [×] 未找到help\swoole_loader目录
        )
    )
)

echo.
echo ================================
echo 不兼容扩展检测
echo ================================

set INCOMPATIBLE_FOUND=0

php -m | findstr /i "xdebug" >nul
if %errorlevel% equ 0 (
    echo [!] 发现不兼容扩展: Xdebug
    set INCOMPATIBLE_FOUND=1
)

php -m | findstr /i "ioncube" >nul
if %errorlevel% equ 0 (
    echo [!] 发现不兼容扩展: ionCube
    set INCOMPATIBLE_FOUND=1
)

php -m | findstr /i "zend_loader" >nul
if %errorlevel% equ 0 (
    echo [!] 发现不兼容扩展: Zend Loader
    set INCOMPATIBLE_FOUND=1
)

if %INCOMPATIBLE_FOUND% equ 0 (
    echo [√] 未发现不兼容扩展
) else (
    echo.
    echo [警告] 发现不兼容扩展，可能影响Swoole Loader运行
    echo 建议在php.ini中禁用这些扩展
)

echo.
echo ================================
echo 目录权限检测
echo ================================

echo 检查runtime目录:
if exist "runtime" (
    echo [√] runtime目录存在
) else (
    echo [!] runtime目录不存在，正在创建...
    mkdir runtime
    mkdir runtime\log
    mkdir runtime\cache
    mkdir runtime\temp
    echo [√] runtime目录已创建
)

echo 检查public\uploads目录:
if exist "public\uploads" (
    echo [√] public\uploads目录存在
) else (
    echo [!] public\uploads目录不存在，正在创建...
    mkdir public\uploads
    echo [√] public\uploads目录已创建
)

echo.
echo ================================
echo 检测完成
echo ================================

php -m | findstr /i "swoole_loader" >nul
if %errorlevel% equ 0 (
    echo [结果] 环境检测通过，可以启动项目
    echo.
    echo 启动命令: php think run
    echo 或运行: start_with_installer.bat
) else (
    echo [结果] 需要安装Swoole Loader
    echo.
    echo 解决方案:
    echo 1. 运行Web安装向导: http://localhost:8000/install/compiler
    echo 2. 运行自动安装脚本: install_swoole_loader.bat
    echo 3. 手动安装（参考上述推荐文件）
)

echo.
pause