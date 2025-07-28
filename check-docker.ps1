# Docker Desktop Startup and Verification Script

Write-Host "🐳 Docker Desktop Setup and Verification" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if Docker Desktop is installed
$dockerPath = "C:\Program Files\Docker\Docker\Docker Desktop.exe"
if (Test-Path $dockerPath) {
    Write-Host "✅ Docker Desktop is installed" -ForegroundColor Green
} else {
    Write-Host "❌ Docker Desktop not found at expected location" -ForegroundColor Red
    Write-Host "   Please install Docker Desktop from: https://docs.docker.com/desktop/setup/install/windows-install/" -ForegroundColor Yellow
    exit 1
}

# Check if Docker Desktop is running
$dockerProcesses = Get-Process | Where-Object {$_.ProcessName -like "*docker*"}
if ($dockerProcesses.Count -eq 0) {
    Write-Host "⚠️ Docker Desktop is not running. Starting it..." -ForegroundColor Yellow
    Start-Process $dockerPath
    Write-Host "⏳ Waiting for Docker Desktop to start..." -ForegroundColor Yellow
    Start-Sleep -Seconds 15
} else {
    Write-Host "✅ Docker Desktop processes are running" -ForegroundColor Green
}

# Test Docker CLI
Write-Host ""
Write-Host "🧪 Testing Docker CLI..." -ForegroundColor Yellow

$maxAttempts = 12  # 12 attempts = 2 minutes
$attempt = 0

do {
    $attempt++
    try {
        $dockerVersion = docker --version 2>$null
        if ($dockerVersion) {
            Write-Host "✅ Docker CLI is working: $dockerVersion" -ForegroundColor Green
            break
        }
    } catch {
        # Docker command failed
    }
    
    if ($attempt -lt $maxAttempts) {
        Write-Host "   Attempt $attempt/$maxAttempts - Docker still initializing..." -ForegroundColor Gray
        Start-Sleep -Seconds 10
    }
} while ($attempt -lt $maxAttempts)

if ($attempt -eq $maxAttempts) {
    Write-Host "❌ Docker CLI not responding after 2 minutes" -ForegroundColor Red
    Write-Host "   This is normal for first-time setup. Please:" -ForegroundColor Yellow
    Write-Host "   1. Check if Docker Desktop opened in your system tray" -ForegroundColor White
    Write-Host "   2. Complete any initial setup dialogs" -ForegroundColor White
    Write-Host "   3. Wait for Docker to finish initializing" -ForegroundColor White
    Write-Host "   4. Run this script again: .\check-docker.ps1" -ForegroundColor White
} else {
    Write-Host ""
    Write-Host "🎉 Docker is ready!" -ForegroundColor Green
    Write-Host ""
    Write-Host "📋 Next steps:" -ForegroundColor Yellow
    Write-Host "1. Run the final setup: .\final-setup.ps1" -ForegroundColor White
    Write-Host "2. Get your API keys (especially OpenAI)" -ForegroundColor White
    Write-Host "3. Configure .env.local files" -ForegroundColor White
    Write-Host "4. Start the demo: cd demo; docker compose up -d" -ForegroundColor White
}

Write-Host ""
