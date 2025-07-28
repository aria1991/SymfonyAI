# Manual Setup Instructions for Symfony AI

## Prerequisites Installation

### 1. Install PHP 8.2+
Download from: https://windows.php.net/download/
- Extract to C:\php
- Add C:\php to PATH environment variable
- Copy php.ini-development to php.ini

### 2. Install Composer
Download from: https://getcomposer.org/download/
- Run the installer
- Restart command prompt

### 3. Install Docker Desktop
Download from: https://docs.docker.com/desktop/setup/install/windows-install/
- Install and restart computer
- Enable WSL 2 if prompted

## Project Setup Commands

Run these commands in order after installing the prerequisites:

```bash
# 1. Install main project dependencies
composer install

# 2. Set up examples
cd examples
composer install

# 3. Create API key configuration
copy .env .env.local
# Edit .env.local and add your API keys

# 4. Go back to root and set up demo
cd ..
cd demo

# 5. Start Docker services
docker compose up -d

# 6. Install demo dependencies
docker compose run composer install

# 7. Create demo configuration
copy .env .env.local
# Edit .env.local and add your OpenAI API key

# 8. Access demo
# Open https://localhost/ in your browser
```

## Required API Keys

To use the examples and demo, you'll need API keys from:

### Essential (for getting started):
- **OpenAI**: https://platform.openai.com/api-keys
  - Needed for: Basic examples and demo app
  - Cost: Pay-per-use, ~$0.002 per 1K tokens

### Optional (for advanced examples):
- **Anthropic**: https://console.anthropic.com/
- **Google Gemini**: https://aistudio.google.com/app/apikey
- **Azure OpenAI**: https://portal.azure.com/
- **Mistral**: https://console.mistral.ai/

## Quick Start Commands

After setup:

```bash
# Run examples
cd examples
php runner

# Check demo
# Open https://localhost/ in browser

# View logs
cd demo
docker compose logs -f

# Stop services
docker compose down
```

## Troubleshooting

### Common Issues:
1. **PHP not found**: Make sure PHP is in your PATH
2. **Composer not found**: Restart PowerShell after installation
3. **Docker not starting**: Enable WSL 2 and restart
4. **Port conflicts**: Stop other services using ports 80, 443, 5432, 8000

### Useful Commands:
```bash
# Check versions
php --version
composer --version
docker --version

# Reset Docker services
cd demo
docker compose down
docker compose up -d --force-recreate

# Check running containers
docker ps

# View container logs
docker compose logs [service-name]
```
