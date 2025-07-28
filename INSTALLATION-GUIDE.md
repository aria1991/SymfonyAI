# Symfony AI - Manual Installation Guide

## 🔧 Step-by-Step Installation

### Step 1: Install PHP 8.4+

1. **Download PHP:**
   - Go to: https://windows.php.net/download/
   - Download "Non Thread Safe" x64 ZIP version of PHP 8.4+

2. **Install PHP:**
   - Extract the ZIP to `C:\php`
   - Copy `php.ini-development` to `php.ini`
   - Add `C:\php` to your system PATH:
     - Press `Win + R`, type `sysdm.cpl`, press Enter
     - Click "Environment Variables"
     - Under "System Variables", find "Path" and click "Edit"
     - Click "New" and add `C:\php`
     - Click "OK" to save

3. **Enable required extensions in php.ini:**
   - Open `C:\php\php.ini` in a text editor
   - Uncomment these lines (remove the `;` at the beginning):
     ```
     extension=curl
     extension=fileinfo
     extension=mbstring
     extension=openssl
     extension=pdo_sqlite
     extension=sqlite3
     ```

4. **Test PHP installation:**
   - Open a new PowerShell window
   - Run: `php --version`

### Step 2: Install Composer

1. **Download Composer:**
   - Go to: https://getcomposer.org/Composer-Setup.exe
   - Download and run the installer

2. **Follow the installer:**
   - It will automatically detect your PHP installation
   - Leave all settings as default
   - Click "Install"

3. **Test Composer installation:**
   - Open a new PowerShell window
   - Run: `composer --version`

### Step 3: Install Docker Desktop

1. **Download Docker Desktop:**
   - Go to: https://desktop.docker.com/win/main/amd64/Docker%20Desktop%20Installer.exe
   - Download the installer

2. **Install Docker:**
   - Run the installer
   - Accept the license agreement
   - Choose "Use WSL 2 instead of Hyper-V" if prompted
   - Complete the installation

3. **Setup Docker:**
   - Restart your computer
   - Start Docker Desktop
   - Complete the initial setup wizard
   - Sign in or create a Docker account if prompted

4. **Test Docker installation:**
   - Open PowerShell
   - Run: `docker --version`

### Step 4: Install Git (if needed)

Git is already installed on your system, but if you need to install it:
- Go to: https://git-scm.com/download/win
- Download and install Git for Windows

## 🚀 Quick Installation Commands

If you prefer using package managers, here are alternative methods:

### Using Chocolatey (after installing it manually):
```powershell
# Install Chocolatey first (run as Administrator):
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://community.chocolatey.org/install.ps1'))

# Restart PowerShell, then install packages:
choco install php --version=8.4.2 -y
choco install composer -y
choco install docker-desktop -y
```

### Using Winget (built into Windows 10+):
```powershell
winget install --id=PHP.PHP.8.4 --exact
winget install --id=Docker.DockerDesktop --exact
# Note: Composer may need manual installation via PHAR file
```

### Starting Docker Desktop after installation:
```powershell
# Start Docker Desktop manually:
Start-Process "C:\Program Files\Docker\Docker\Docker Desktop.exe"

# Wait for it to initialize (may take 1-2 minutes)
# Check if it's running:
Get-Process | Where-Object {$_.ProcessName -like "*docker*"}

# Test Docker (after initialization):
docker --version
```

## 🧪 Verification Steps

After installation, verify everything works:

```powershell
# Check versions
php --version
composer --version
docker --version
git --version

# Check PHP extensions
php -m | findstr -i "curl fileinfo mbstring openssl pdo sqlite"
```

## 🔧 Troubleshooting

### Common Issues:

1. **PHP not found after installation:**
   - Restart PowerShell/Command Prompt
   - Check if `C:\php` is in your PATH environment variable

2. **Composer installation fails:**
   - Make sure PHP is working first: `php --version`
   - Try downloading Composer manually and placing it in a folder in your PATH

3. **Docker requires WSL 2:**
   - Enable WSL 2: `wsl --install`
   - Restart computer
   - Install Docker Desktop again

4. **Permission errors:**
   - Run PowerShell as Administrator for installations
   - Check Windows Defender/Antivirus isn't blocking downloads

## 📋 Next Steps

After successful installation:

1. **Run the project setup:**
   ```powershell
   cd "C:\Users\ariav\symfony-ai"
   .\setup-environment.ps1
   ```

2. **Get API keys:**
   - OpenAI: https://platform.openai.com/api-keys
   - Others as needed

3. **Start developing:**
   ```powershell
   cd examples
   composer install
   php runner
   ```

## 📞 Need Help?

If you encounter issues:
1. Check the official documentation for each tool
2. Restart your computer after installations
3. Run installations as Administrator if needed
4. Disable antivirus temporarily during installation if it blocks downloads
