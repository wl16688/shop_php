@echo off
chcp 65001 >nul
echo ================================
echo    CRMEB项目快速启动脚本
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
echo 创建必要目录...
if not exist "runtime" mkdir runtime
if not exist "runtime\log" mkdir runtime\log
if not exist "runtime\cache" mkdir runtime\cache
if not exist "runtime\temp" mkdir runtime\temp

echo.
echo 启动开发服务器...
echo 项目地址: http://localhost:8000
echo 管理后台: http://localhost:8000/admin
echo 按 Ctrl+C 停止服务器
echo.

%PHP_CMD% think run -H 0.0.0.0 -p 8000

pause