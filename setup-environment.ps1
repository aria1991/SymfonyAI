# Symfony AI Development Environment Setup Script
# Run this script after installing PHP, Composer, and Docker

Write-Host "🚀 Setting up Symfony AI Development Environment..." -ForegroundColor Green

# Check if required tools are installed
Write-Host "📋 Checking prerequisites..." -ForegroundColor Yellow

try {
    $phpVersion = php --version
    Write-Host "✅ PHP: $($phpVersion.Split("`n")[0])" -ForegroundColor Green
} catch {
    Write-Host "❌ PHP not found. Please install PHP 8.2+ first." -ForegroundColor Red
    exit 1
}

try {
    $composerVersion = composer --version
    Write-Host "✅ Composer: $($composerVersion)" -ForegroundColor Green
} catch {
    Write-Host "❌ Composer not found. Please install Composer first." -ForegroundColor Red
    exit 1
}

try {
    $dockerVersion = docker --version
    Write-Host "✅ Docker: $($dockerVersion)" -ForegroundColor Green
} catch {
    Write-Host "❌ Docker not found. Please install Docker Desktop first." -ForegroundColor Red
    exit 1
}

# Install main project dependencies
Write-Host "📦 Installing main project dependencies..." -ForegroundColor Yellow
composer install

# Set up examples
Write-Host "📝 Setting up examples..." -ForegroundColor Yellow
Set-Location examples
composer install

# Create .env.local template for examples
$envTemplate = @"
# Copy this file to .env.local and add your API keys

# OpenAI
OPENAI_API_KEY=your_openai_api_key_here

# Anthropic
ANTHROPIC_API_KEY=your_anthropic_api_key_here

# Google Gemini
GEMINI_API_KEY=your_gemini_api_key_here

# Azure OpenAI
AZURE_OPENAI_API_KEY=your_azure_openai_api_key_here
AZURE_OPENAI_ENDPOINT=your_azure_openai_endpoint_here

# Mistral
MISTRAL_API_KEY=your_mistral_api_key_here

# Add other API keys as needed...
"@

$envTemplate | Out-File -FilePath ".env.local.template" -Encoding UTF8
Write-Host "📋 Created .env.local.template in examples directory" -ForegroundColor Green
Write-Host "   Copy this to .env.local and add your API keys" -ForegroundColor Yellow

Set-Location ..

# Set up demo application
Write-Host "🎮 Setting up demo application..." -ForegroundColor Yellow
Set-Location demo

# Start Docker services for demo
Write-Host "🐳 Starting Docker services..." -ForegroundColor Yellow
docker compose up -d

# Install demo dependencies
composer install

# Create .env.local template for demo
$demoEnvTemplate = @"
# Copy this file to .env.local and add your API keys

# OpenAI API Key (required for demo)
OPENAI_API_KEY=your_openai_api_key_here

# Database URL (already configured for Docker)
DATABASE_URL=postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8

# ChromaDB URL (already configured for Docker)
CHROMA_URL=http://localhost:8000
"@

$demoEnvTemplate | Out-File -FilePath ".env.local.template" -Encoding UTF8
Write-Host "📋 Created .env.local.template in demo directory" -ForegroundColor Green

Set-Location ..

Write-Host "" -ForegroundColor White
Write-Host "🎉 Environment setup complete!" -ForegroundColor Green
Write-Host "" -ForegroundColor White
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "1. Get API keys from AI providers (OpenAI, Anthropic, etc.)" -ForegroundColor White
Write-Host "2. Copy .env.local.template to .env.local in examples/ and demo/ directories" -ForegroundColor White
Write-Host "3. Add your API keys to the .env.local files" -ForegroundColor White
Write-Host "4. Run examples: cd examples && ./runner" -ForegroundColor White
Write-Host "5. Try the demo: Open https://localhost/ in your browser" -ForegroundColor White
Write-Host "" -ForegroundColor White
Write-Host "📚 Useful commands:" -ForegroundColor Yellow
Write-Host "  - Run examples: cd examples && php runner" -ForegroundColor White
Write-Host "  - Check demo logs: cd demo && docker compose logs -f" -ForegroundColor White
Write-Host "  - Restart demo: cd demo && docker compose restart" -ForegroundColor White
Write-Host "  - Stop demo: cd demo && docker compose down" -ForegroundColor White
