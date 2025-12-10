@echo off
REM Windows 批處理腳本：安全執行 npx 指令
REM 使用方法：scripts\npx-safe.bat <npx指令參數>
REM 例如：scripts\npx-safe.bat nuxt@latest module add vueuse

cd /d "%~dp0\.."
set "ROOT_DIR=%CD%"
set "CURRENT_DIR=%CD%"

cd /d "%~dp0"
cd /d "%~dp0\.."
if "%CURRENT_DIR%"=="%ROOT_DIR%" (
    echo.
    echo ⚠️  警告：您正在根目錄執行 npx 指令！
    echo.
    echo 這個專案的 npm/npx 指令應該在以下目錄執行：
    echo   - admin/   (前端專案)
    echo   - api/     (後端專案)
    echo.
    echo 請使用以下指令：
    echo   cd admin
    echo   npx %*
    echo.
    echo 或使用一行指令：
    echo   cd admin ^&^& npx %*
    echo.
    exit /b 1
)

REM 如果在子目錄，直接執行 npx
cd /d "%CURRENT_DIR%"
call npx %*

