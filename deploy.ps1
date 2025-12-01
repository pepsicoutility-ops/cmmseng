# PEPSICO CMMS Deployment Script
# Run this on your LOCAL machine (Windows PowerShell)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "PEPSICO CMMS - VPS Deployment" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$VPS_IP = "43.133.152.67"
$VPS_USER = "ubuntu"
$PROJECT_NAME = "cmmseng"
$LOCAL_PATH = "C:\laragon\www\cmmseng"

Write-Host "VPS Details:" -ForegroundColor Yellow
Write-Host "  IP: $VPS_IP"
Write-Host "  User: $VPS_USER"
Write-Host "  Project: $PROJECT_NAME"
Write-Host ""

# Step 1: Clean local project
Write-Host "[Step 1/5] Cleaning local project..." -ForegroundColor Green
cd $LOCAL_PATH

if (Test-Path "node_modules") {
    Write-Host "  Removing node_modules..."
    Remove-Item -Recurse -Force node_modules -ErrorAction SilentlyContinue
}

if (Test-Path "vendor") {
    Write-Host "  Removing vendor..."
    Remove-Item -Recurse -Force vendor -ErrorAction SilentlyContinue
}

Write-Host "  Clearing Laravel caches..."
Remove-Item -Recurse -Force storage\framework\cache\data\* -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force storage\framework\sessions\* -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force storage\framework\views\* -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force storage\logs\* -ErrorAction SilentlyContinue

Write-Host "  ✓ Local cleanup complete" -ForegroundColor Green
Write-Host ""

# Step 2: Create deployment package
Write-Host "[Step 2/5] Creating deployment package..." -ForegroundColor Green

$BACKUP_DIR = "C:\temp\cmms-deploy"
if (-not (Test-Path $BACKUP_DIR)) {
    New-Item -ItemType Directory -Path $BACKUP_DIR | Out-Null
}

$TIMESTAMP = Get-Date -Format "yyyyMMdd_HHmmss"
$ARCHIVE_NAME = "cmmseng-deploy-$TIMESTAMP.zip"
$ARCHIVE_PATH = "$BACKUP_DIR\$ARCHIVE_NAME"

Write-Host "  Creating archive: $ARCHIVE_NAME"

# Compress project (exclude .git, node_modules, vendor, .env)
$excludePatterns = @(
    "*.git*",
    "*node_modules*",
    "*vendor*",
    "*.env",
    "*storage\logs*",
    "*storage\framework\cache*",
    "*storage\framework\sessions*",
    "*storage\framework\views*"
)

# Use 7-Zip if available, otherwise use PowerShell compression
if (Test-Path "C:\Program Files\7-Zip\7z.exe") {
    Write-Host "  Using 7-Zip for compression..."
    & "C:\Program Files\7-Zip\7z.exe" a -tzip "$ARCHIVE_PATH" "$LOCAL_PATH\*" -xr!.git -xr!node_modules -xr!vendor -x!.env | Out-Null
} else {
    Write-Host "  Using PowerShell compression (slower)..."
    Compress-Archive -Path "$LOCAL_PATH\*" -DestinationPath $ARCHIVE_PATH -Force
}

$archiveSize = (Get-Item $ARCHIVE_PATH).Length / 1MB
Write-Host "  ✓ Archive created: $([math]::Round($archiveSize, 2)) MB" -ForegroundColor Green
Write-Host ""

# Step 3: Upload to VPS
Write-Host "[Step 3/5] Uploading to VPS..." -ForegroundColor Green
Write-Host "  This may take 5-10 minutes depending on your internet speed..."
Write-Host ""

scp "$ARCHIVE_PATH" "${VPS_USER}@${VPS_IP}:/tmp/$ARCHIVE_NAME"

if ($LASTEXITCODE -eq 0) {
    Write-Host "  ✓ Upload complete!" -ForegroundColor Green
} else {
    Write-Host "  ✗ Upload failed! Please check SSH connection." -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 4: Generate VPS setup commands
Write-Host "[Step 4/5] Generating VPS setup script..." -ForegroundColor Green

$VPS_SCRIPT = @'
#!/bin/bash
set -e

echo "=========================================="
echo "PEPSICO CMMS - VPS Setup"
echo "=========================================="
echo ""

# Update system
echo "[1/11] Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install basic utilities
echo "[2/11] Installing basic utilities..."
sudo apt install -y curl wget git unzip software-properties-common

# Install Nginx
echo "[3/11] Installing Nginx..."
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx

# Install MySQL 8.0
echo "[4/11] Installing MySQL..."
sudo apt install -y mysql-server

# Install PHP 8.4
echo "[5/11] Installing PHP 8.4..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml \
  php8.4-mbstring php8.4-curl php8.4-zip php8.4-gd \
  php8.4-bcmath php8.4-intl php8.4-readline

# Install Composer
echo "[6/11] Installing Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Node.js 20.x
echo "[7/11] Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install -y nodejs

# Setup database
echo "[8/11] Setting up MySQL database..."
sudo mysql <<EOF
CREATE DATABASE IF NOT EXISTS cmms_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'cmms_user'@'localhost' IDENTIFIED BY 'Cmms@SecureDB2025!';
GRANT ALL PRIVILEGES ON cmms_production.* TO 'cmms_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Extract project
echo "[9/11] Extracting project files..."
sudo mkdir -p /var/www
sudo unzip -q /tmp/$ARCHIVE_NAME -d /var/www/cmmseng
sudo chown -R www-data:www-data /var/www/cmmseng

# Install dependencies
echo "[10/11] Installing project dependencies..."
cd /var/www/cmmseng
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install
sudo -u www-data npm run build

# Setup .env
echo "[11/11] Configuring environment..."
sudo -u www-data cp .env.example .env 2>/dev/null || true

cat <<ENVEOF | sudo tee /var/www/cmmseng/.env
APP_NAME="PEPSICO CMMS"
APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=Asia/Jakarta
APP_URL=http://$VPS_IP

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cmms_production
DB_USERNAME=cmms_user
DB_PASSWORD=Cmms@SecureDB2025!

SESSION_DRIVER=file
SESSION_LIFETIME=120
CACHE_DRIVER=file
QUEUE_CONNECTION=database

FILAMENT_PATH=pep

WAHA_API_URL=
WAHA_API_TOKEN=
WAHA_SESSION=default
WAHA_GROUP_ID=
WAHA_ENABLED=false
ENVEOF

# Generate app key
sudo -u www-data php artisan key:generate --force

# Run migrations
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --class=MasterDataSeeder --force
sudo -u www-data php artisan db:seed --class=BarcodeTokenSeeder --force

# Create storage link
sudo -u www-data php artisan storage:link

# Cache configs
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Set permissions
sudo chown -R www-data:www-data /var/www/cmmseng
sudo chmod -R 755 /var/www/cmmseng
sudo chmod -R 775 /var/www/cmmseng/storage
sudo chmod -R 775 /var/www/cmmseng/bootstrap/cache

# Configure Nginx
cat <<NGINXEOF | sudo tee /etc/nginx/sites-available/cmms
server {
    listen 80;
    listen [::]:80;
    server_name $VPS_IP;
    root /var/www/cmmseng/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;

    charset utf-8;

    access_log /var/log/nginx/cmms-access.log;
    error_log /var/log/nginx/cmms-error.log;

    client_max_body_size 20M;

    location / {
        try_files \\\$uri \\\$uri/ /index.php?\\\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \\\$realpath_root\\\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location = /service-worker.js {
        add_header Cache-Control "no-store, no-cache, must-revalidate";
        try_files \\\$uri =404;
    }
}
NGINXEOF

# Enable site
sudo ln -sf /etc/nginx/sites-available/cmms /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test and restart Nginx
sudo nginx -t
sudo systemctl restart nginx

# Setup firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
echo "y" | sudo ufw enable

# Setup cron
(sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd /var/www/cmmseng && php artisan schedule:run >> /dev/null 2>&1") | sudo crontab -u www-data -

# Cleanup
rm /tmp/$ARCHIVE_NAME

echo ""
echo "=========================================="
echo "✓ Deployment Complete!"
echo "=========================================="
echo ""
echo "Access your application:"
echo "  Admin Panel: http://$VPS_IP/pep/login"
echo "  PWA: http://$VPS_IP/barcode/form-selector/{token}"
echo ""
echo "Default Super Admin:"
echo "  Email: admin@pepsico.com"
echo "  Password: password (CHANGE IMMEDIATELY!)"
echo ""
echo "Database:"
echo "  Name: cmms_production"
echo "  User: cmms_user"
echo "  Password: Cmms@SecureDB2025!"
echo ""
'@

$VPS_SCRIPT_PATH = "$BACKUP_DIR\vps-setup.sh"
$VPS_SCRIPT | Out-File -FilePath $VPS_SCRIPT_PATH -Encoding UTF8
Write-Host "  ✓ VPS setup script created" -ForegroundColor Green
Write-Host ""

# Step 5: Execute on VPS
Write-Host "[Step 5/5] Ready to deploy on VPS!" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Yellow
Write-Host "  1. Upload setup script to VPS:"
Write-Host "     scp $VPS_SCRIPT_PATH ${VPS_USER}@${VPS_IP}:/tmp/setup.sh" -ForegroundColor Cyan
Write-Host ""
Write-Host "  2. Connect to VPS:"
Write-Host "     ssh ${VPS_USER}@${VPS_IP}" -ForegroundColor Cyan
Write-Host ""
Write-Host "  3. Run setup script on VPS:"
Write-Host "     chmod +x /tmp/setup.sh" -ForegroundColor Cyan
Write-Host "     /tmp/setup.sh" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Password: mYL94UZG~!x(d3x" -ForegroundColor White
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Local preparation complete!" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
