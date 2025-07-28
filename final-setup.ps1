# Symfony AI - Final Setup Script
# Run this after the installations are complete

Write-Host "🎉 Symfony AI Development Environment Setup" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host ""

# Test installations
Write-Host "📋 Checking installations..." -ForegroundColor Yellow

# Check PHP
try {
    $phpVersion = php --version 2>$null | Select-Object -First 1
    if ($phpVersion -match "PHP (\d+\.\d+\.\d+)") {
        Write-Host "✅ PHP: $($matches[1])" -ForegroundColor Green
    } else {
        Write-Host "❌ PHP not working properly" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ PHP not found" -ForegroundColor Red
}

# Check Composer
if (Test-Path "composer.phar") {
    Write-Host "✅ Composer: Local installation ready" -ForegroundColor Green
} else {
    Write-Host "❌ Composer not found" -ForegroundColor Red
}

# Check Docker
try {
    $dockerVersion = docker --version 2>$null
    if ($dockerVersion) {
        Write-Host "✅ Docker: $dockerVersion" -ForegroundColor Green
    } else {
        Write-Host "⏳ Docker: Installation may be in progress" -ForegroundColor Yellow
    }
} catch {
    Write-Host "⏳ Docker: Installation may be in progress" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🛠️ Setting up project..." -ForegroundColor Yellow

# Install main dependencies
Write-Host "Installing main project dependencies..." -ForegroundColor White
php composer.phar install --ignore-platform-reqs

# Set up examples
Write-Host "Setting up examples..." -ForegroundColor White
Set-Location examples
..\composer.bat install --ignore-platform-reqs

# Create environment template
$envTemplate = @"
# Symfony AI - Environment Configuration
# Copy this file to .env.local and add your API keys

# OpenAI (Required for most examples and demo)
OPENAI_API_KEY=your_openai_api_key_here

# Anthropic Claude
ANTHROPIC_API_KEY=your_anthropic_api_key_here

# Google Gemini
GEMINI_API_KEY=your_gemini_api_key_here

# Azure OpenAI
AZURE_OPENAI_API_KEY=your_azure_openai_api_key_here
AZURE_OPENAI_ENDPOINT=your_azure_openai_endpoint_here

# Mistral
MISTRAL_API_KEY=your_mistral_api_key_here

# Ollama (local AI)
OLLAMA_URL=http://localhost:11434

# Other providers...
# Add API keys as needed for the providers you want to test
"@

$envTemplate | Out-File -FilePath ".env.local.template" -Encoding UTF8
Write-Host "✅ Created .env.local.template" -ForegroundColor Green

Set-Location ..

Write-Host ""
Write-Host "🎮 Setting up demo application..." -ForegroundColor Yellow

Set-Location demo

# Install demo dependencies
..\composer.bat install --ignore-platform-reqs

# Create demo environment template
$demoEnvTemplate = @"
# Symfony AI Demo - Environment Configuration
# Copy this file to .env.local and add your API keys

# OpenAI API Key (Required for demo)
OPENAI_API_KEY=your_openai_api_key_here

# Database URL (configured for Docker)
DATABASE_URL=postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8

# ChromaDB URL (configured for Docker)
CHROMA_URL=http://localhost:8000

# App Environment
APP_ENV=dev
APP_SECRET=change_me_in_production
"@

$demoEnvTemplate | Out-File -FilePath ".env.local.template" -Encoding UTF8
Write-Host "✅ Created demo .env.local.template" -ForegroundColor Green

Set-Location ..

Write-Host ""
Write-Host "🎉 Setup Complete!" -ForegroundColor Green
Write-Host "=================" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Next Steps:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. 🔑 Get API Keys:" -ForegroundColor White
Write-Host "   • OpenAI: https://platform.openai.com/api-keys" -ForegroundColor Gray
Write-Host "   • Anthropic: https://console.anthropic.com/" -ForegroundColor Gray
Write-Host "   • Google: https://aistudio.google.com/app/apikey" -ForegroundColor Gray
Write-Host ""
Write-Host "2. ⚙️ Configure Environment:" -ForegroundColor White
Write-Host "   • Copy examples\.env.local.template to examples\.env.local" -ForegroundColor Gray
Write-Host "   • Copy demo\.env.local.template to demo\.env.local" -ForegroundColor Gray
Write-Host "   • Add your API keys to both .env.local files" -ForegroundColor Gray
Write-Host ""
Write-Host "3. 🚀 Start Development:" -ForegroundColor White
Write-Host "   • Run examples: cd examples && php runner" -ForegroundColor Gray
Write-Host "   • Start demo: cd demo && docker compose up -d" -ForegroundColor Gray
Write-Host "   • View demo: Open https://localhost/ in browser" -ForegroundColor Gray
Write-Host ""
Write-Host "💡 Useful Commands:" -ForegroundColor Yellow
Write-Host "   • Install packages: .\composer.bat install" -ForegroundColor Gray
Write-Host "   • Run specific example: cd examples && php anthropic/chat.php" -ForegroundColor Gray
Write-Host "   • Check demo logs: cd demo && docker compose logs -f" -ForegroundColor Gray
Write-Host "   • Stop demo: cd demo && docker compose down" -ForegroundColor Gray
Write-Host ""
Write-Host "🐛 Note: PHP extension warnings are normal and won't affect functionality" -ForegroundColor Cyan
Write-Host ""
