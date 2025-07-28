# WSL Update Script
# This script must be run as Administrator

Write-Host "🐧 Updating Windows Subsystem for Linux (WSL)" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Host "❌ This script must be run as Administrator" -ForegroundColor Red
    Write-Host ""
    Write-Host "📋 Please do the following:" -ForegroundColor Yellow
    Write-Host "1. Right-click on PowerShell and select 'Run as Administrator'" -ForegroundColor White
    Write-Host "2. Navigate to this directory: cd '$PWD'" -ForegroundColor White
    Write-Host "3. Run: wsl --update" -ForegroundColor White
    Write-Host "4. After the update, restart your computer" -ForegroundColor White
    Write-Host "5. Then run Docker Desktop again" -ForegroundColor White
    Write-Host ""
    pause
    exit 1
}

Write-Host "✅ Running as Administrator" -ForegroundColor Green
Write-Host ""

try {
    Write-Host "🔄 Updating WSL..." -ForegroundColor Yellow
    wsl --update
    
    Write-Host ""
    Write-Host "✅ WSL update completed!" -ForegroundColor Green
    Write-Host ""
    Write-Host "📋 Next steps:" -ForegroundColor Yellow
    Write-Host "1. Restart your computer" -ForegroundColor White
    Write-Host "2. Start Docker Desktop" -ForegroundColor White
    Write-Host "3. Complete Docker's initial setup" -ForegroundColor White
    Write-Host "4. Run: .\check-docker-fixed.ps1" -ForegroundColor White
    Write-Host ""
    
} catch {
    Write-Host "❌ Error updating WSL: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host ""
    Write-Host "💡 Alternative solutions:" -ForegroundColor Yellow
    Write-Host "1. Try: wsl --install" -ForegroundColor White
    Write-Host "2. Download WSL manually from Microsoft Store" -ForegroundColor White
    Write-Host "3. Enable Windows features: dism.exe /online /enable-feature /featurename:Microsoft-Windows-Subsystem-Linux /all /norestart" -ForegroundColor White
}

Write-Host ""
pause
