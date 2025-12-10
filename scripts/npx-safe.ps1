# PowerShell 腳本：安全執行 npx 指令
# 使用方法：.\scripts\npx-safe.ps1 <npx指令參數>
# 例如：.\scripts\npx-safe.ps1 nuxt@latest module add vueuse

$currentDir = Get-Location
$rootDir = Split-Path -Parent $PSScriptRoot

if ($currentDir.Path -eq $rootDir) {
    Write-Host ""
    Write-Host "⚠️  警告：您正在根目錄執行 npx 指令！" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "這個專案的 npm/npx 指令應該在以下目錄執行："
    Write-Host "  - admin/   (前端專案)"
    Write-Host "  - api/     (後端專案)"
    Write-Host ""
    Write-Host "請使用以下指令："
    Write-Host "  cd admin"
    Write-Host "  npx $args"
    Write-Host ""
    Write-Host "或使用一行指令："
    Write-Host "  cd admin && npx $args"
    Write-Host ""
    exit 1
}

# 如果在子目錄，直接執行 npx
npx $args

