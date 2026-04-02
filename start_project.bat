@echo off
chcp 65001 >nul
echo ================================
echo    CRMEB项目本地启动脚本
echo ================================
echo.

echo 1. 检查PHP环境...
php --version 2>nul
if %errorlevel% neq 0 (
    echo [错误] PHP未安装或未配置到PATH环境变量
    echo.
    echo 可能的解决方案：
    echo - 检查C:\Wnmp\php\php.exe是否存在
    echo - 将C:\Wnmp\php添加到系统PATH环境变量
    echo - 或者使用完整路径：C:\Wnmp\php\php.exe
    echo.
    pause
    exit /b 1
)

echo.
echo 2. 检查Composer...
composer --version 2>nul
if %errorlevel% neq 0 (
    echo [警告] Composer未安装或未配置到PATH
    echo.
    echo 尝试使用composer.phar...
    if exist "composer.phar" (
        echo 找到composer.phar，使用本地版本
        set COMPOSER_CMD=php composer.phar
    ) else (
        echo.
        echo Composer解决方案：
        echo 1. 下载并安装Composer: https://getcomposer.org/
        echo 2. 或者下载composer.phar到项目根目录
        echo 3. 跳过依赖安装，直接启动项目（如果依赖已存在）
        echo.
        choice /c 123 /m "请选择操作 (1-安装Composer, 2-下载composer.phar, 3-跳过依赖检查): "
        if errorlevel 3 goto :skip_composer
        if errorlevel 2 goto :download_composer
        if errorlevel 1 (
            start https://getcomposer.org/
            echo 请安装Composer后重新运行此脚本
            pause
            exit /b 1
        )
    )
) else (
    set COMPOSER_CMD=composer
)

:download_composer
echo.
echo 正在下载composer.phar...
powershell -Command "Invoke-WebRequest -Uri 'https://getcomposer.org/composer.phar' -OutFile 'composer.phar'"
if %errorlevel% neq 0 (
    echo [错误] 下载composer.phar失败
    echo 请手动下载或安装Composer
    pause
    exit /b 1
)
set COMPOSER_CMD=php composer.phar
echo composer.phar下载完成

:skip_composer
echo.
echo 3. 检查项目依赖...
if exist "vendor\autoload.php" (
    echo 依赖文件已存在，跳过安装
    goto :check_database
)

if defined COMPOSER_CMD (
    echo 安装项目依赖...
    %COMPOSER_CMD% install
    if %errorlevel% neq 0 (
        echo [错误] 依赖安装失败
        echo 请检查网络连接或手动运行: %COMPOSER_CMD% install
        pause
        exit /b 1
    )
) else (
    echo [警告] 无法安装依赖，vendor目录不存在
    echo 请手动安装Composer并运行: composer install
    pause
)

:check_database

echo.
echo 4. 检查数据库连接...
php think migrate:status
if %errorlevel% neq 0 (
    echo [警告] 数据库连接可能有问题，请检查配置
)

echo.
echo 5. 设置目录权限...
if not exist "runtime" mkdir runtime
if not exist "runtime\log" mkdir runtime\log
if not exist "runtime\cache" mkdir runtime\cache
if not exist "runtime\temp" mkdir runtime\temp

echo.
echo 6. 启动开发服务器...
echo 项目将在 http://localhost:8000 启动
echo 按 Ctrl+C 停止服务器
echo.
php think run -H 0.0.0.0 -p 8000

pause