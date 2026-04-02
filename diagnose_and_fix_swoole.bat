@echo off
chcp 65001 >nul
echo ========================================
echo    Swoole Loader 诊断和修复工具
echo ========================================
echo.

REM PHP环境配置
set "PHP_DIR=C:\Wnmp\php-8.1.33-nts-Win32-vs16-x64"
set "PHP_EXE=%PHP_DIR%\php.exe"
set "PHP_INI=%PHP_DIR%\php.ini"
set "EXT_DIR=%PHP_DIR%\ext"

REM 项目路径配置
set "PROJECT_DIR=c:\workspace\trae.ai\yyyy"
set "SWOOLE_DIR=%PROJECT_DIR%\help\swoole_loader"

echo [1] 环境检查...
echo PHP目录: %PHP_DIR%
echo PHP可执行文件: %PHP_EXE%
echo 扩展目录: %EXT_DIR%
echo Swoole源目录: %SWOOLE_DIR%
echo.

echo [2] 检查文件存在性...
echo 检查PHP可执行文件:
if exist "%PHP_EXE%" (echo ✅ 存在) else (echo ❌ 不存在 & goto :end)

echo 检查扩展目录:
if exist "%EXT_DIR%" (echo ✅ 存在) else (echo ❌ 不存在 & goto :end)

echo 检查Swoole源目录:
if exist "%SWOOLE_DIR%" (echo ✅ 存在) else (echo ❌ 不存在 & goto :end)

echo.
echo [3] 检查可用的Swoole Loader文件...
echo 📁 可用文件:
dir /b "%SWOOLE_DIR%\*.dll" 2>nul
echo.

echo [4] 检查目标扩展目录中的文件...
echo 📁 扩展目录中的Swoole相关文件:
dir /b "%EXT_DIR%\*swoole*" 2>nul
echo.

echo [5] 尝试不同的Swoole Loader文件...
echo.

REM 尝试NTS版本
set "SWOOLE_NTS=%SWOOLE_DIR%\swoole_loader80_nzts_x64.dll"
set "TARGET_NTS=%EXT_DIR%\swoole_loader80_nzts_x64.dll"

echo 🔧 测试 NTS 版本: swoole_loader80_nzts_x64.dll
if exist "%SWOOLE_NTS%" (
    echo 复制文件...
    copy "%SWOOLE_NTS%" "%TARGET_NTS%" >nul 2>&1
    if exist "%TARGET_NTS%" (
        echo ✅ 文件复制成功
        echo 测试加载...
        "%PHP_EXE%" -c "%PHP_INI%" -m | findstr /i "swoole_loader" >nul 2>&1
        if errorlevel 1 (
            echo ❌ NTS版本加载失败
        ) else (
            echo ✅ NTS版本加载成功！
            goto :success
        )
    ) else (
        echo ❌ 文件复制失败
    )
) else (
    echo ❌ NTS源文件不存在
)

echo.

REM 尝试ZTS版本
set "SWOOLE_ZTS=%SWOOLE_DIR%\swoole_loader80_zts_x64.dll"
set "TARGET_ZTS=%EXT_DIR%\swoole_loader80_zts_x64.dll"

echo 🔧 测试 ZTS 版本: swoole_loader80_zts_x64.dll
if exist "%SWOOLE_ZTS%" (
    REM 先删除NTS版本
    if exist "%TARGET_NTS%" del "%TARGET_NTS%" >nul 2>&1
    
    echo 复制文件...
    copy "%SWOOLE_ZTS%" "%TARGET_ZTS%" >nul 2>&1
    if exist "%TARGET_ZTS%" (
        echo ✅ 文件复制成功
        
        REM 更新php.ini配置
        findstr /v /i "swoole_loader" "%PHP_INI%" > "%PHP_INI%.tmp"
        move "%PHP_INI%.tmp" "%PHP_INI%" >nul 2>&1
        echo extension=swoole_loader80_zts_x64.dll >> "%PHP_INI%"
        
        echo 测试加载...
        "%PHP_EXE%" -c "%PHP_INI%" -m | findstr /i "swoole_loader" >nul 2>&1
        if errorlevel 1 (
            echo ❌ ZTS版本加载失败
        ) else (
            echo ✅ ZTS版本加载成功！
            goto :success
        )
    ) else (
        echo ❌ 文件复制失败
    )
) else (
    echo ❌ ZTS源文件不存在
)

echo.
echo [6] 所有版本测试失败，进行深度诊断...
echo.

echo 🔍 PHP详细信息:
"%PHP_EXE%" -v
echo.

echo 🔍 PHP配置信息:
"%PHP_EXE%" -c "%PHP_INI%" --ini
echo.

echo 🔍 扩展目录配置:
"%PHP_EXE%" -c "%PHP_INI%" -r "echo 'Extension dir: ' . ini_get('extension_dir') . PHP_EOL;"
echo.

echo 🔍 已加载的扩展:
"%PHP_EXE%" -c "%PHP_INI%" -m
echo.

echo 💡 可能的解决方案:
echo 1. 安装 Visual C++ Redistributable for Visual Studio 2019
echo 2. 检查Windows系统是否缺少必要的运行库
echo 3. 尝试使用PHP 8.0而不是PHP 8.1
echo 4. 联系CRMEB获取PHP 8.1专用的Swoole Loader
echo.

echo 🔗 Visual C++ Redistributable 下载地址:
echo https://aka.ms/vs/17/release/vc_redist.x64.exe
echo.

goto :end

:success
echo.
echo 🎉 Swoole Loader 安装成功！
echo.
echo 📋 安装信息:
if exist "%TARGET_NTS%" (
    echo 使用版本: NTS (Non-Thread Safe)
    echo 文件: swoole_loader80_nzts_x64.dll
) else if exist "%TARGET_ZTS%" (
    echo 使用版本: ZTS (Thread Safe)
    echo 文件: swoole_loader80_zts_x64.dll
)
echo 配置文件: %PHP_INI%
echo 扩展目录: %EXT_DIR%
echo.

echo ✅ 现在可以启动CRMEB项目了！
echo 启动命令: "%PHP_EXE%" -c "%PHP_INI%" think run
echo.

:end
pause