@echo off
chcp 65001 >nul
echo ================================
echo    CRMEB项目智能启动脚本
echo ================================
echo.

echo 检查PHP环境...
php --version 2>nul
if %errorlevel% neq 0 (
    echo [错误] PHP未找到，尝试使用C:\Wnmp\php\php.exe
    if exist "C:\Wnmp\php\php.exe" (
        set PHP_CMD=C:\Wnmp\php\php.exe
        echo 使用: C:\Wnmp\php\php.exe
    ) else (
        echo [错误] 未找到PHP，请检查安装
        pause
        exit /b 1
    )
) else (
    set PHP_CMD=php
    echo PHP环境正常
)

echo.
echo 检查项目文件...
if not exist "vendor\autoload.php" (
    echo [警告] vendor目录不存在，可能需要运行 composer install
)

if not exist "public\index.php" (
    echo [错误] 项目入口文件不存在
    pause
    exit /b 1
)

echo.
echo 检查Swoole Loader...
php -m | findstr /i "swoole_loader" >nul
if %errorlevel% neq 0 (
    echo [警告] Swoole Loader 未安装
    echo.
    echo 有两种解决方案：
    echo 1. 使用Web安装向导（推荐）
    echo 2. 自动安装脚本
    echo.
    set /p choice=请选择 [1/2]: 
    
    if "!choice!"=="1" (
        echo.
        echo 正在启动Web安装向导...
        echo 请在浏览器中访问: http://localhost:8000/install/compiler
        echo 按照页面提示完成Swoole Loader安装
        echo.
        goto :start_server
    ) else if "!choice!"=="2" (
        echo.
        echo 正在运行自动安装脚本...
        if exist "install_swoole_loader.bat" (
            call install_swoole_loader.bat
            if %errorlevel% neq 0 (
                echo [错误] 自动安装失败，请使用Web安装向导
                echo 启动服务器后访问: http://localhost:8000/install/compiler
            )
        ) else (
            echo [错误] 找不到自动安装脚本
            echo 请使用Web安装向导: http://localhost:8000/install/compiler
        )
    ) else (
        echo 使用默认Web安装向导...
    )
) else (
    echo [信息] Swoole Loader 已安装
    php -r "if(extension_loaded('swoole_loader')) { echo 'Swoole Loader 版本: ' . phpversion('swoole_loader') . PHP_EOL; }"
)

:start_server
echo.
echo 创建必要目录...
if not exist "runtime" mkdir runtime
if not exist "runtime\log" mkdir runtime\log
if not exist "runtime\cache" mkdir runtime\cache
if not exist "runtime\temp" mkdir runtime\temp

echo.
echo 检查.env配置文件...
if not exist ".env" (
    if exist ".example.env" (
        echo 复制.example.env到.env...
        copy ".example.env" ".env"
    ) else (
        echo [警告] 未找到.env配置文件
    )
)

echo.
echo 启动开发服务器...
echo ================================
echo 项目地址: http://localhost:8000
echo 管理后台: http://localhost:8000/admin
echo Swoole Loader安装向导: http://localhost:8000/install/compiler
echo 项目安装向导: http://localhost:8000/install
echo ================================
echo 按 Ctrl+C 停止服务器
echo.

%PHP_CMD% think run -H 0.0.0.0 -p 8000

pause