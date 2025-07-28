#!/bin/bash

# Composer Cache Fix Script
# Fixes corrupted downloads like nikic/php-parser
# Works on Linux, macOS, and WSL

set -e  # Exit on any error

echo "🔧 Fixing Composer cache issues..."

# Clear all Composer caches
echo "1. Clearing Composer caches..."
composer clear-cache || echo "Warning: composer clear-cache failed, continuing..."

# Remove cache directories safely
echo "2. Removing cache directories..."
[ -d "$HOME/.composer/cache" ] && rm -rf "$HOME/.composer/cache"
[ -d "$HOME/.cache/composer" ] && rm -rf "$HOME/.cache/composer"
[ -d "vendor/composer" ] && find vendor/composer -name "tmp-*" -type f -delete 2>/dev/null || true

# Remove potentially corrupted vendor directories
echo "3. Removing potentially corrupted installations..."
[ -d "vendor" ] && rm -rf vendor
[ -f "composer.lock" ] && rm -f composer.lock

# Reinstall with clean cache
echo "4. Reinstalling dependencies with clean cache..."
composer install --no-cache --prefer-dist --no-interaction --optimize-autoloader

echo "✅ Composer cache fix completed successfully!"
echo "💡 Tip: Run this script if you encounter download corruption errors in CI"
