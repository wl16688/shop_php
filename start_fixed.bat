@echo off
chcp 65001 >nul
echo ================================
echo    CRMEB项目完整启动脚本
echo    (包含Swoole Loader检测)
echo ================================
echo.

echo 步骤1: 检查PHP环境...
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
echo 步骤2: 检查关键扩展...
set MISSING_EXT=0

%PHP_CMD% -m | findstr /i "mysqli" >nul
if %errorlevel% neq 0 (
    echo [×] MySQLi扩展未安装
    set MISSING_EXT=1
) else (
    echo [√] MySQLi扩展已安装
)

%PHP_CMD% -m | findstr /i "redis" >nul
if %errorlevel% neq 0 (
    echo [×] Redis扩展未安装
    set MISSING_EXT=1
) else (
    echo [√] Redis扩展已安装
)

echo.
echo 步骤3: 检查Swoole Loader...
%PHP_CMD% -m | findstr /i "swoole_loader" >nul
if %errorlevel% neq 0 (
    echo [×] Swoole Loader未安装
    echo.
    echo 解决方案：
    echo 1. Web安装向导（推荐）- 启动后访问 http://localhost:8000/install/compiler
    echo 2. 自动安装脚本 - 运行 install_swoole_loader.bat
    echo 3. 环境检测脚本 - 运行 check_swoole_environment.bat
    echo.
    echo 项目将启动，但可能无法正常运行，请先安装Swoole Loader
    set MISSING_EXT=1
) else (
    echo [√] Swoole Loader已安装
    %PHP_CMD% -r "if(extension_loaded('swoole_loader')) { echo 'Swoole Loader 版本: ' . phpversion('swoole_loader') . PHP_EOL; }"
)

echo.
echo 步骤4: 修复helpers.php文件...
if exist "app\helpers.php" (
    REM 检查文件是否损坏（包含空字符）
    findstr /R /C:"" "app\helpers.php" >nul 2>&1
    if %errorlevel% equ 0 (
        echo [警告] helpers.php文件可能损坏，正在备份并重新创建...
        if not exist "app\helpers.php.backup" (
            copy "app\helpers.php" "app\helpers.php.backup" >nul 2>&1
        )
        
        echo ^<?php > "app\helpers.php"
        echo // +---------------------------------------------------------------------->> "app\helpers.php"
        echo // ^| CRMEB [ CRMEB赋能开发者，助力企业发展 ]>> "app\helpers.php"
        echo // +---------------------------------------------------------------------->> "app\helpers.php"
        echo // ^| Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.>> "app\helpers.php"
        echo // +---------------------------------------------------------------------->> "app\helpers.php"
        echo // ^| Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权>> "app\helpers.php"
        echo // +---------------------------------------------------------------------->> "app\helpers.php"
        echo // ^| Author: CRMEB Team ^<admin@crmeb.com^>>> "app\helpers.php"
        echo // +---------------------------------------------------------------------->> "app\helpers.php"
        echo.>> "app\helpers.php"
        echo /**>> "app\helpers.php"
        echo  * 应用辅助函数文件>> "app\helpers.php"
        echo  */>> "app\helpers.php"
        echo.>> "app\helpers.php"
        echo if ^(!function_exists('app_helper_example'^)^) {>> "app\helpers.php"
        echo     function app_helper_example^(^)>> "app\helpers.php"
        echo     {>> "app\helpers.php"
        echo         // 这里可以添加全局辅助函数>> "app\helpers.php"
        echo         return 'CRMEB Helper Functions';>> "app\helpers.php"
        echo     }>> "app\helpers.php"
        echo }>> "app\helpers.php"
        
        echo [√] helpers.php文件已修复
    ) else (
        echo [√] helpers.php文件正常
    )
) else (
    echo [!] helpers.php文件不存在，正在创建...
    echo ^<?php > "app\helpers.php"
    echo // CRMEB应用辅助函数文件>> "app\helpers.php"
    echo [√] helpers.php文件已创建
)

echo.
echo 步骤5: 检查项目文件...
if not exist "public\index.php" (
    echo [错误] 项目入口文件不存在
    pause
    exit /b 1
) else (
    echo [√] 项目入口文件存在
)

if not exist "vendor\autoload.php" (
    echo [警告] vendor目录不存在，可能需要运行 composer install
) else (
    echo [√] vendor目录存在
)

echo.
echo 步骤6: 创建运行时目录...
if not exist "runtime" mkdir runtime
if not exist "runtime\log" mkdir runtime\log
if not exist "runtime\cache" mkdir runtime\cache
if not exist "runtime\temp" mkdir runtime\temp
if not exist "runtime\session" mkdir runtime\session
echo [√] 运行时目录已创建

echo.
echo 步骤7: 检查配置文件...
if not exist ".env" (
    if exist ".example.env" (
        echo 复制.example.env到.env...
        copy ".example.env" ".env"
        echo [√] .env配置文件已创建
    ) else (
        echo [警告] 未找到.env配置文件模板
    )
) else (
    echo [√] .env配置文件存在
)

echo.
echo ================================
echo 启动信息
echo ================================

if %MISSING_EXT% equ 1 (
    echo [警告] 检测到缺少必要扩展，项目可能无法正常运行
    echo.
    echo 建议先解决以下问题：
    if not exist "vendor\autoload.php" echo - 运行 composer install 安装依赖
    
    %PHP_CMD% -m | findstr /i "swoole_loader" >nul
    if %errorlevel% neq 0 echo - 安装Swoole Loader扩展
    
    %PHP_CMD% -m | findstr /i "redis" >nul
    if %errorlevel% neq 0 echo - 安装Redis扩展
    
    %PHP_CMD% -m | findstr /i "mysqli" >nul
    if %errorlevel% neq 0 echo - 安装MySQLi扩展
    
    echo.
)

echo 项目地址: http://localhost:8000
echo 管理后台: http://localhost:8000/admin
echo 项目安装: http://localhost:8000/install
echo Swoole Loader安装: http://localhost:8000/install/compiler
echo.
echo 按 Ctrl+C 停止服务器
echo ================================

echo.
echo 正在启动开发服务器...
chcp 65001 >nul
echo ================================
echo    CRMEB项目启动脚本 v2.0
echo ================================
echo.

echo [1/6] 检查PHP环境...

REM 首先尝试系统PATH中的PHP
php -v >nul 2>&1
if not errorlevel 1 (
    echo ✅ 使用系统PATH中的PHP
    set "PHP_PATH=php"
    goto :php_found
)

REM 检查Wnmp环境
set "WNMP_PHP=C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64\php.exe"
if exist "%WNMP_PHP%" (
    echo ✅ 检测到Wnmp PHP环境: %WNMP_PHP%
    set "PHP_PATH=%WNMP_PHP%"
    goto :php_found
)

REM 检查其他常见PHP路径
for %%p in (
    "C:\php\php.exe"
    "C:\xampp\php\php.exe"
    "C:\wamp\bin\php\php*\php.exe"
    "C:\laragon\bin\php\php*\php.exe"
) do (
    if exist "%%p" (
        echo ✅ 找到PHP: %%p
        set "PHP_PATH=%%p"
        goto :php_found
    )
)

echo ❌ 错误: 未找到PHP环境
echo    请确保PHP已正确安装，或检查以下路径:
echo    - 系统PATH环境变量
echo    - C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64\php.exe
echo    - C:\php\php.exe
echo    - C:\xampp\php\php.exe
pause
exit /b 1

:php_found
echo 📋 PHP版本信息:
"%PHP_PATH%" -v

echo.
echo [2/6] 修复helpers.php文件...
if exist app\helpers.php (
    echo 备份原helpers.php文件...
    copy app\helpers.php app\helpers.php.backup >nul 2>&1
)

echo 创建新的helpers.php文件...
(
echo ^<?php
echo // +----------------------------------------------------------------------
echo // ^| CRMEB [ CRMEB赋能开发者，助力企业发展 ]
echo // +----------------------------------------------------------------------
echo // ^| Copyright ^(c^) 2016~2020 https://www.crmeb.com All rights reserved.
echo // +----------------------------------------------------------------------
echo // ^| Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
echo // +----------------------------------------------------------------------
echo // ^| Author: CRMEB Team ^<admin@crmeb.com^>
echo // +----------------------------------------------------------------------
echo.
echo /**
echo  * 应用辅助函数文件
echo  * 此文件包含项目中使用的全局辅助函数
echo  */
echo.
echo // 这里可以添加项目特定的辅助函数
) > app\helpers.php

echo ✅ helpers.php文件已修复

echo.
echo [3/6] 检查项目文件...
if not exist public\index.php (
    echo ❌ 项目入口文件不存在: public\index.php
    pause
    exit /b 1
)
echo ✅ 项目入口文件存在

if not exist vendor\autoload.php (
    echo ❌ 依赖文件不存在，需要运行 composer install
    echo 请先运行 install_deps.bat 安装依赖
    pause
    exit /b 1
)
echo ✅ 依赖文件存在

echo.
echo [4/6] 创建运行时目录...
if not exist runtime mkdir runtime
if not exist runtime\cache mkdir runtime\cache
if not exist runtime\log mkdir runtime\log
if not exist runtime\session mkdir runtime\session
if not exist runtime\temp mkdir runtime\temp
echo ✅ 运行时目录已创建

echo.
echo [5/6] 检查配置文件...
if not exist .env (
    echo ⚠️  .env配置文件不存在，使用默认配置
) else (
    echo ✅ 配置文件存在
)

echo.
echo [6/6] 启动开发服务器...
echo 正在启动ThinkPHP开发服务器...
echo 访问地址: http://localhost:8000
echo 按 Ctrl+C 停止服务器
echo.

%PHP_CMD% think run -H 0.0.0.0 -p 8000

echo.
echo 服务器已停止
pause