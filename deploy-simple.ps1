# PEPSICO CMMS - Simple Deployment Script
# Run on LOCAL Windows machine

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "PEPSICO CMMS - Deployment Preparation" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

$VPS_IP = "43.133.152.67"
$LOCAL_PATH = "C:\laragon\www\cmmseng"
$BACKUP_DIR = "C:\temp\cmms-deploy"

# Create backup directory
if (-not (Test-Path $BACKUP_DIR)) {
    New-Item -ItemType Directory -Path $BACKUP_DIR | Out-Null
}

# Step 1: Clean project
Write-Host "`n[1/3] Cleaning local project..." -ForegroundColor Green
cd $LOCAL_PATH

Remove-Item -Recurse -Force node_modules -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force vendor -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force storage\framework\cache\data\* -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force storage\framework\sessions\* -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force storage\framework\views\* -ErrorAction SilentlyContinue
Remove-Item -Recurse -Force storage\logs\* -ErrorAction SilentlyContinue

Write-Host "  ✓ Cleanup complete" -ForegroundColor Green

# Step 2: Create archive
Write-Host "`n[2/3] Creating deployment archive..." -ForegroundColor Green

$TIMESTAMP = Get-Date -Format "yyyyMMdd_HHmmss"
$ARCHIVE_NAME = "cmmseng-$TIMESTAMP.zip"
$ARCHIVE_PATH = "$BACKUP_DIR\$ARCHIVE_NAME"

Compress-Archive -Path "$LOCAL_PATH\*" -DestinationPath $ARCHIVE_PATH -Force

$size = (Get-Item $ARCHIVE_PATH).Length / 1MB
Write-Host "  ✓ Archive created: $([math]::Round($size, 2)) MB" -ForegroundColor Green

# Step 3: Instructions
Write-Host "`n[3/3] Archive ready for upload!" -ForegroundColor Green
Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "`n1. Upload archive to VPS:" -ForegroundColor White
Write-Host "   scp `"$ARCHIVE_PATH`" ubuntu@$VPS_IP`:/tmp/" -ForegroundColor Cyan
Write-Host "`n2. Connect to VPS:" -ForegroundColor White
Write-Host "   ssh ubuntu@$VPS_IP" -ForegroundColor Cyan
Write-Host "   Password: mYL94UZG~!x(d3x" -ForegroundColor Gray
Write-Host "`n3. Run setup commands (copy from setup-vps.txt)" -ForegroundColor White
Write-Host "`n========================================`n" -ForegroundColor Cyan

# Create VPS setup instructions
$setupInstructions = @"
# PEPSICO CMMS - VPS Setup Commands
# Run these commands on your VPS after uploading the archive

# 1. Update system
sudo apt update && sudo apt upgrade -y

# 2. Install required packages
sudo apt install -y curl wget git unzip software-properties-common nginx mysql-server

# 3. Install PHP 8.4
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl php8.4-readline

# 4. Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# 5. Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash -
sudo apt install -y nodejs

# 6. Setup MySQL database
sudo mysql <<EOF
CREATE DATABASE cmms_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cmms_user'@'localhost' IDENTIFIED BY 'Cmms@SecureDB2025!';
GRANT ALL PRIVILEGES ON cmms_production.* TO 'cmms_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# 7. Extract project
sudo mkdir -p /var/www
cd /tmp
sudo unzip $ARCHIVE_NAME -d /var/www/cmmseng
sudo chown -R www-data:www-data /var/www/cmmseng

# 8. Install dependencies
cd /var/www/cmmseng
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install
sudo -u www-data npm run build

# 9. Setup .env file
sudo -u www-data tee /var/www/cmmseng/.env > /dev/null <<'ENVEOF'
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

# 10. Run Laravel setup
sudo -u www-data php artisan key:generate --force
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --class=MasterDataSeeder --force
sudo -u www-data php artisan db:seed --class=BarcodeTokenSeeder --force
sudo -u www-data php artisan storage:link
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 11. Set permissions
sudo chown -R www-data:www-data /var/www/cmmseng
sudo chmod -R 755 /var/www/cmmseng
sudo chmod -R 775 /var/www/cmmseng/storage
sudo chmod -R 775 /var/www/cmmseng/bootstrap/cache

# 12. Configure Nginx
sudo tee /etc/nginx/sites-available/cmms > /dev/null <<'NGINXEOF'
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
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location = /service-worker.js {
        add_header Cache-Control "no-store, no-cache, must-revalidate";
        try_files \$uri =404;
    }
}
NGINXEOF

# 13. Enable site
sudo ln -sf /etc/nginx/sites-available/cmms /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx

# 14. Setup firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
echo "y" | sudo ufw enable

# 15. Setup cron for Laravel scheduler
(sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd /var/www/cmmseng && php artisan schedule:run >> /dev/null 2>&1") | sudo crontab -u www-data -

# 16. Import employees
cd /var/www/cmmseng
sudo -u www-data php artisan import:employees

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
echo "  Password: password"
echo ""
"@

$setupInstructions | Out-File -FilePath "$BACKUP_DIR\setup-vps.txt" -Encoding UTF8
Write-Host "VPS setup instructions saved to:" -ForegroundColor Green
Write-Host "$BACKUP_DIR\setup-vps.txt`n" -ForegroundColor Cyan
