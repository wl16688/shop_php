@echo off
chcp 65001 >nul
echo ================================
echo    环境检查脚本
echo ================================
echo.

echo 1. 检查PHP...
php --version 2>nul
if %errorlevel% neq 0 (
    echo [×] PHP未找到在PATH中
    if exist "C:\Wnmp\php\php.exe" (
        echo [√] 找到PHP: C:\Wnmp\php\php.exe
        C:\Wnmp\php\php.exe --version
    ) else (
        echo [×] 未找到PHP在C:\Wnmp\php\
    )
) else (
    echo [√] PHP环境正常
)

echo.
echo 2. 检查Composer...
composer --version 2>nul
if %errorlevel% neq 0 (
    echo [×] Composer未找到在PATH中
    if exist "composer.phar" (
        echo [√] 找到本地composer.phar
    ) else (
        echo [×] 未找到composer.phar
    )
) else (
    echo [√] Composer环境正常
)

echo.
echo 3. 检查MySQL...
mysql --version 2>nul
if %errorlevel% neq 0 (
    echo [×] MySQL未找到在PATH中
    if exist "C:\Wnmp\mysql\bin\mysql.exe" (
        echo [√] 找到MySQL: C:\Wnmp\mysql\bin\mysql.exe
    ) else (
        echo [×] 未找到MySQL在C:\Wnmp\mysql\bin\
    )
) else (
    echo [√] MySQL环境正常
)

echo.
echo 4. 检查项目文件...
if exist "vendor\autoload.php" (
    echo [√] Composer依赖已安装
) else (
    echo [×] Composer依赖未安装
)

if exist "public\index.php" (
    echo [√] 项目入口文件存在
) else (
    echo [×] 项目入口文件不存在
)

if exist ".env" (
    echo [√] 环境配置文件存在
) else (
    echo [×] 环境配置文件不存在
)

echo.
echo 5. 检查必要目录...
if exist "runtime" (
    echo [√] runtime目录存在
) else (
    echo [×] runtime目录不存在
    mkdir runtime
    echo [√] 已创建runtime目录
)

echo.
echo ================================
echo 检查完成
echo ================================

pause