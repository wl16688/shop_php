@echo off
chcp 65001 >nul
echo ================================
echo    手动安装项目依赖
echo ================================
echo.

echo 选择安装方式:
echo 1. 使用系统Composer
echo 2. 下载并使用composer.phar
echo 3. 使用指定路径的PHP和Composer
echo.

choice /c 123 /m "请选择 (1-3): "

if errorlevel 3 goto :custom_path
if errorlevel 2 goto :use_phar
if errorlevel 1 goto :use_system

:use_system
echo.
echo 使用系统Composer安装依赖...
composer install --no-dev
goto :end

:use_phar
echo.
echo 检查composer.phar...
if not exist "composer.phar" (
    echo 下载composer.phar...
    powershell -Command "Invoke-WebRequest -Uri 'https://getcomposer.org/composer.phar' -OutFile 'composer.phar'"
    if %errorlevel% neq 0 (
        echo [错误] 下载失败
        pause
        exit /b 1
    )
)
echo 使用composer.phar安装依赖...
php composer.phar install --no-dev
goto :end

:custom_path
echo.
set /p PHP_PATH="请输入PHP路径 (如: C:\Wnmp\php\php.exe): "
set /p COMPOSER_PATH="请输入Composer路径 (如: C:\composer\composer.bat): "

if not exist "%PHP_PATH%" (
    echo [错误] PHP路径不存在
    pause
    exit /b 1
)

echo 使用自定义路径安装依赖...
"%COMPOSER_PATH%" install --no-dev

:end
echo.
if %errorlevel% equ 0 (
    echo [成功] 依赖安装完成
) else (
    echo [错误] 依赖安装失败
)

pause