@echo off
chcp 65001 >nul
echo ========================================
echo    CRMEB启动脚本 (Wnmp专用配置)
echo ========================================
echo.

REM Wnmp PHP配置
set "PHP_DIR=C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64"
set "PHP_EXE=%PHP_DIR%\php.exe"
set "PHP_INI=%PHP_DIR%\php.ini"

echo [1] 检查PHP环境...
if not exist "%PHP_EXE%" (
    echo ❌ Wnmp PHP不存在: %PHP_EXE%
    pause
    exit /b 1
)
echo ✅ 使用Wnmp PHP: %PHP_EXE%

echo [2] 检查配置文件...
if not exist "%PHP_INI%" (
    echo ❌ php.ini不存在: %PHP_INI%
    echo 💡 请先运行 install_swoole_force_config.bat 安装Swoole Loader
    pause
    exit /b 1
)
echo ✅ 配置文件: %PHP_INI%

echo [3] 检查Swoole Loader...
"%PHP_EXE%" -c "%PHP_INI%" -m | findstr /i "swoole_loader" >nul 2>&1
if errorlevel 1 (
    echo ❌ Swoole Loader未安装或未加载
    echo 💡 请先运行 install_swoole_force_config.bat 安装Swoole Loader
    pause
    exit /b 1
)
echo ✅ Swoole Loader已加载

echo [4] 检查项目文件...
if not exist "think" (
    echo ❌ 错误: 未找到ThinkPHP命令行工具
    echo    请确保在项目根目录运行此脚本
    pause
    exit /b 1
)
echo ✅ ThinkPHP项目检查通过

echo [5] 检查运行时目录...
if not exist "runtime" mkdir runtime
if not exist "runtime\cache" mkdir runtime\cache
if not exist "runtime\log" mkdir runtime\log
if not exist "runtime\session" mkdir runtime\session
if not exist "runtime\temp" mkdir runtime\temp
echo ✅ 运行时目录检查完成

echo [6] 检查.env配置...
if not exist ".env" (
    if exist ".example.env" (
        echo 📋 复制.example.env为.env...
        copy ".example.env" ".env"
        echo ✅ 已创建.env配置文件
        echo ⚠️  请编辑.env文件配置数据库等信息
    ) else (
        echo ⚠️  警告: 未找到.env配置文件
    )
) else (
    echo ✅ .env配置文件存在
)

echo.
echo 🚀 启动CRMEB开发服务器...
echo 📍 访问地址: http://localhost:8000
echo 📍 安装页面: http://localhost:8000/install
echo.
echo ⚠️  注意事项:
echo    1. 使用Wnmp专用PHP配置
echo    2. 首次运行请访问 /install 完成系统初始化
echo    3. 确保MySQL和Redis服务已启动
echo    4. 按 Ctrl+C 可停止服务器
echo.
echo 正在启动服务器...
"%PHP_EXE%" -c "%PHP_INI%" think run