@echo off
REM Composer Cache Fix Script for Windows
REM Fixes corrupted downloads like nikic/php-parser

echo 🔧 Fixing Composer cache issues...

REM Clear all Composer caches
echo 1. Clearing Composer caches...
composer clear-cache
if exist "%USERPROFILE%\.composer\cache" rmdir /s /q "%USERPROFILE%\.composer\cache"
if exist "%USERPROFILE%\.cache\composer" rmdir /s /q "%USERPROFILE%\.cache\composer"  
if exist "vendor\composer\tmp-*" del /q /s "vendor\composer\tmp-*"

REM Remove vendor directories that might be corrupted
echo 2. Removing potentially corrupted vendor directories...
if exist "vendor" rmdir /s /q "vendor"
if exist "composer.lock" del "composer.lock"

REM Reinstall with clean cache
echo 3. Reinstalling dependencies with clean cache...
composer install --no-cache --prefer-dist --no-interaction --optimize-autoloader

echo ✅ Composer cache fix completed!
