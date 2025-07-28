# Troubleshooting Composer Issues

## Problem: nikic/php-parser Download Corruption

If you encounter errors like:

```
Failed to extract nikic/php-parser: (9) /usr/bin/unzip -qq ... 
End-of-central-directory signature not found
```

This indicates a corrupted download, which can happen in CI environments or due to network issues.

## Solutions

### Quick Fix (Manual)

**Linux/macOS:**
```bash
# Run the automated fix script
./fix-composer-cache.sh
```

**Windows:**
```cmd
# Run the automated fix script  
fix-composer-cache.bat
```

### Manual Fix Steps

1. **Clear Composer caches:**
   ```bash
   composer clear-cache
   rm -rf ~/.composer/cache
   rm -rf ~/.cache/composer
   ```

2. **Remove corrupted temporary files:**
   ```bash
   rm -rf vendor/composer/tmp-*
   ```

3. **Reinstall dependencies:**
   ```bash
   composer install --no-cache --prefer-dist --no-interaction
   ```

### CI/GitHub Actions Fix

If this happens in GitHub Actions, the workflow will automatically retry with cache clearing. You can also manually trigger the cache fix workflow.

### Prevention

- Use `--prefer-dist` for more reliable downloads
- Enable `--no-cache` for CI environments
- Set proper timeout values for slow networks

## Root Cause

This issue occurs when:
- Network interruptions during package download
- Disk space issues during extraction
- Corrupted Composer cache entries
- GitHub API rate limiting affecting zipball downloads

The dev-assistant-bundle includes automated handling for these scenarios.
