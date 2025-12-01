# 🚀 CMMS Deployment Guide

**Project:** CMMS (Computerized Maintenance Management System)  
**Version:** 1.0.0  
**Date:** November 27, 2025  
**Copyright:** © 2025 Nandang Wijaya. All Rights Reserved.

---

## 📋 Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Server Requirements](#server-requirements)
3. [Production Environment Setup](#production-environment-setup)
4. [Database Migration](#database-migration)
5. [Application Configuration](#application-configuration)
6. [Security Hardening](#security-hardening)
7. [Performance Optimization](#performance-optimization)
8. [Backup Strategy](#backup-strategy)
9. [Monitoring & Maintenance](#monitoring--maintenance)
10. [Troubleshooting](#troubleshooting)

---

## ✅ Pre-Deployment Checklist

### Code Quality ✅
- [x] All 167 automated tests passing
- [x] Code quality verified (PHPStan level 5)
- [x] Security vulnerabilities checked
- [x] Performance optimized
- [x] Documentation complete

### Database ✅
- [x] 30 migrations verified
- [x] All relationships tested
- [x] Indexes optimized
- [x] Power BI views created
- [x] Sample data seeded

### Features ✅
- [x] 16 phases completed
- [x] Phase 17 documentation complete
- [x] All workflows tested (WO, PM, Inventory)
- [x] Role-based access control working
- [x] Notifications tested (Telegram)

---

## 🖥️ Server Requirements

### Minimum Requirements

**Server Specifications:**
- **CPU:** 4 cores (recommended: 8 cores)
- **RAM:** 8 GB (recommended: 16 GB)
- **Storage:** 50 GB SSD (recommended: 100 GB)
- **OS:** Ubuntu 22.04 LTS or higher

**Software Stack:**
- **Web Server:** Nginx 1.18+ or Apache 2.4+
- **PHP:** 8.4+ with required extensions
- **Database:** MySQL 8.0+ or MariaDB 10.6+
- **Process Manager:** Supervisor (for queue workers)
- **SSL:** Let's Encrypt or commercial certificate

### Required PHP Extensions

```bash
# Core extensions
php8.4-cli
php8.4-fpm
php8.4-mysql
php8.4-mbstring
php8.4-xml
php8.4-bcmath
php8.4-curl
php8.4-zip
php8.4-gd
php8.4-intl
php8.4-redis (optional, for caching)

# Additional extensions
php8.4-opcache (for performance)
php8.4-imagick (for image processing - optional)
```

### Composer

```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

---

## 🔧 Production Environment Setup

### 1. Server Preparation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required software
sudo apt install -y nginx mysql-server php8.4-fpm php8.4-mysql \
    php8.4-mbstring php8.4-xml php8.4-bcmath php8.4-curl \
    php8.4-zip php8.4-gd php8.4-intl php8.4-opcache \
    git curl unzip supervisor certbot python3-certbot-nginx

# Install Redis (optional for caching)
sudo apt install -y redis-server
```

### 2. Create Application Directory

```bash
# Create web directory
sudo mkdir -p /var/www/cmmseng
sudo chown -R $USER:www-data /var/www/cmmseng
cd /var/www/cmmseng

# Clone repository or upload files
# Option 1: Git clone
git clone <your-repo-url> .

# Option 2: Upload via SCP/SFTP
# scp -r /path/to/local/cmmseng user@server:/var/www/
```

### 3. Install PHP Dependencies

```bash
cd /var/www/cmmseng

# Install production dependencies only
composer install --optimize-autoloader --no-dev

# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4. Configure Nginx

**Create Nginx configuration:** `/etc/nginx/sites-available/cmmseng`

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/cmmseng/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    # Increase upload size for file uploads
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        
        # Increase timeout for long-running processes
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

**Enable site and restart Nginx:**

```bash
# Create symbolic link
sudo ln -s /etc/nginx/sites-available/cmmseng /etc/nginx/sites-enabled/

# Test Nginx configuration
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```

### 5. Configure PHP-FPM

**Edit PHP-FPM pool:** `/etc/php/8.4/fpm/pool.d/www.conf`

```ini
; Increase PM settings for production
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 500

; Increase upload limits
php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size] = 20M
php_admin_value[max_execution_time] = 300
php_admin_value[memory_limit] = 256M
```

**Restart PHP-FPM:**

```bash
sudo systemctl restart php8.4-fpm
```

---

## 🗄️ Database Migration

### 1. Create Production Database

```bash
# Login to MySQL
sudo mysql -u root -p

# Create database
CREATE DATABASE cmmseng CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user
CREATE USER 'cmmseng_user'@'localhost' IDENTIFIED BY 'your-secure-password';

# Grant privileges
GRANT ALL PRIVILEGES ON cmmseng.* TO 'cmmseng_user'@'localhost';

# Create Power BI read-only user (if needed)
CREATE USER 'powerbi_readonly'@'%' IDENTIFIED BY 'PowerBI@2025';
GRANT SELECT ON cmmseng.* TO 'powerbi_readonly'@'%';

# Flush privileges
FLUSH PRIVILEGES;
EXIT;
```

### 2. Optimize MySQL Configuration

**Edit MySQL config:** `/etc/mysql/mysql.conf.d/mysqld.cnf`

```ini
[mysqld]
# Performance tuning
innodb_buffer_pool_size = 4G
innodb_log_file_size = 512M
innodb_flush_log_at_trx_commit = 2
max_connections = 200

# Query cache (MySQL < 8.0)
# query_cache_size = 128M
# query_cache_limit = 2M

# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

**Restart MySQL:**

```bash
sudo systemctl restart mysql
```

### 3. Run Migrations

```bash
cd /var/www/cmmseng

# Run migrations
php artisan migrate --force

# Seed initial data (areas, parts, users)
php artisan db:seed --force
```

---

## ⚙️ Application Configuration

### 1. Create Production `.env`

```bash
cd /var/www/cmmseng
cp .env.example .env
nano .env
```

**Production `.env` template:**

```env
APP_NAME="PEPSICO ENGINEERING CMMS"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_APP_KEY
APP_DEBUG=false
APP_URL=https://your-domain.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_TIMEZONE=Asia/Jakarta

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=error
LOG_DAILY_DAYS=14

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cmmseng
DB_USERNAME=cmmseng_user
DB_PASSWORD=your-secure-password

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=your-domain.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Cache
CACHE_STORE=database
CACHE_PREFIX=cmms_

# Queue
QUEUE_CONNECTION=database

# Mail (if using SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# Filesystem
FILESYSTEM_DISK=local

# Telegram Bot (for notifications)
TELEGRAM_BOT_TOKEN=your-telegram-bot-token
TELEGRAM_CHAT_ID=your-chat-id

# CMMS Config
CMMS_LABOUR_RATE_PER_HOUR=50000
CMMS_DOWNTIME_COST_PER_HOUR=1000000
CMMS_PM_GRACE_PERIOD_DAYS=1
```

### 2. Generate Application Key

```bash
php artisan key:generate
```

### 3. Create Storage Symlink

```bash
php artisan storage:link
```

### 4. Optimize Application

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Cache icons
php artisan icons:cache

# Optimize Filament
php artisan filament:optimize
```

---

## 🔒 Security Hardening

### 1. File Permissions

```bash
cd /var/www/cmmseng

# Set proper ownership
sudo chown -R www-data:www-data .

# Set directory permissions
sudo find . -type d -exec chmod 755 {} \;

# Set file permissions
sudo find . -type f -exec chmod 644 {} \;

# Make artisan executable
sudo chmod +x artisan

# Secure storage and cache
sudo chmod -R 775 storage bootstrap/cache
```

### 2. Environment Security

```bash
# Secure .env file
sudo chmod 600 .env
sudo chown www-data:www-data .env

# Remove .env.example in production
sudo rm .env.example
```

### 3. Install SSL Certificate (Let's Encrypt)

```bash
# Install SSL certificate
sudo certbot --nginx -d your-domain.com

# Auto-renewal is configured by default
# Test renewal
sudo certbot renew --dry-run
```

### 4. Configure Firewall

```bash
# Allow SSH
sudo ufw allow OpenSSH

# Allow HTTP & HTTPS
sudo ufw allow 'Nginx Full'

# Allow MySQL (only if remote access needed)
# sudo ufw allow 3306/tcp

# Enable firewall
sudo ufw enable
```

### 5. Fail2Ban (Brute Force Protection)

```bash
# Install Fail2Ban
sudo apt install -y fail2ban

# Create custom config
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo nano /etc/fail2ban/jail.local

# Restart Fail2Ban
sudo systemctl restart fail2ban
```

---

## ⚡ Performance Optimization

### 1. Enable OPcache

**Edit PHP config:** `/etc/php/8.4/fpm/php.ini`

```ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
opcache.validate_timestamps=0
```

**Restart PHP-FPM:**

```bash
sudo systemctl restart php8.4-fpm
```

### 2. Setup Queue Workers with Supervisor

**Create Supervisor config:** `/etc/supervisor/conf.d/cmmseng-worker.conf`

```ini
[program:cmmseng-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/cmmseng/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/cmmseng/storage/logs/worker.log
stopwaitsecs=3600
```

**Start workers:**

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cmmseng-worker:*
```

### 3. Setup Task Scheduler

**Add to crontab:**

```bash
sudo crontab -e -u www-data

# Add this line
* * * * * cd /var/www/cmmseng && php artisan schedule:run >> /dev/null 2>&1
```

### 4. Enable Redis Cache (Optional)

**Update `.env`:**

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

**Clear and rebuild cache:**

```bash
php artisan cache:clear
php artisan config:cache
```

---

## 💾 Backup Strategy

### 1. Database Backup Script

**Create:** `/usr/local/bin/backup-cmms-db.sh`

```bash
#!/bin/bash

# Configuration
DB_NAME="cmmseng"
DB_USER="cmmseng_user"
DB_PASS="your-secure-password"
BACKUP_DIR="/var/backups/cmmseng/database"
DATE=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR

# Dump database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/cmmseng_$DATE.sql.gz

# Remove old backups
find $BACKUP_DIR -name "cmmseng_*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "Database backup completed: cmmseng_$DATE.sql.gz"
```

**Make executable:**

```bash
sudo chmod +x /usr/local/bin/backup-cmms-db.sh
```

### 2. File Backup Script

**Create:** `/usr/local/bin/backup-cmms-files.sh`

```bash
#!/bin/bash

# Configuration
SOURCE_DIR="/var/www/cmmseng"
BACKUP_DIR="/var/backups/cmmseng/files"
DATE=$(date +"%Y%m%d_%H%M%S")
RETENTION_DAYS=7

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup storage folder (uploaded files)
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz -C $SOURCE_DIR storage

# Remove old backups
find $BACKUP_DIR -name "storage_*.tar.gz" -mtime +$RETENTION_DAYS -delete

echo "File backup completed: storage_$DATE.tar.gz"
```

**Make executable:**

```bash
sudo chmod +x /usr/local/bin/backup-cmms-files.sh
```

### 3. Schedule Backups

**Add to root crontab:**

```bash
sudo crontab -e

# Daily database backup at 2 AM
0 2 * * * /usr/local/bin/backup-cmms-db.sh

# Weekly file backup on Sunday at 3 AM
0 3 * * 0 /usr/local/bin/backup-cmms-files.sh
```

### 4. Restore Procedure

**Restore database:**

```bash
# Decompress backup
gunzip /var/backups/cmmseng/database/cmmseng_YYYYMMDD_HHMMSS.sql.gz

# Restore to database
mysql -u cmmseng_user -p cmmseng < /var/backups/cmmseng/database/cmmseng_YYYYMMDD_HHMMSS.sql
```

**Restore files:**

```bash
# Extract backup
tar -xzf /var/backups/cmmseng/files/storage_YYYYMMDD_HHMMSS.tar.gz -C /var/www/cmmseng/

# Fix permissions
sudo chown -R www-data:www-data /var/www/cmmseng/storage
sudo chmod -R 775 /var/www/cmmseng/storage
```

---

## 📊 Monitoring & Maintenance

### 1. Error Logging

**Laravel logs location:**
```
/var/www/cmmseng/storage/logs/laravel.log
```

**Monitor logs in real-time:**

```bash
tail -f /var/www/cmmseng/storage/logs/laravel.log
```

### 2. Application Health Check

**Create health check endpoint** (Optional):

Add to `routes/web.php`:

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'cache' => Cache::has('health-check') ? 'working' : 'not working',
    ]);
});
```

### 3. Performance Monitoring

**Monitor system resources:**

```bash
# CPU and memory
htop

# Disk usage
df -h

# MySQL processes
mysqladmin -u root -p processlist

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### 4. Regular Maintenance Tasks

**Weekly:**
- Review error logs
- Check disk space
- Monitor database size
- Review backup logs

**Monthly:**
- Update system packages
- Review performance metrics
- Clean old logs and backups
- Database optimization

**Quarterly:**
- Security audit
- Performance tuning
- Dependency updates
- User feedback review

---

## 🔧 Troubleshooting

### Common Issues

**1. Permission Denied Errors**

```bash
sudo chown -R www-data:www-data /var/www/cmmseng
sudo chmod -R 775 storage bootstrap/cache
```

**2. 500 Internal Server Error**

```bash
# Check error logs
tail -50 /var/www/cmmseng/storage/logs/laravel.log

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**3. Database Connection Failed**

```bash
# Test MySQL connection
mysql -u cmmseng_user -p cmmseng

# Check .env database credentials
# Restart MySQL
sudo systemctl restart mysql
```

**4. Queue Jobs Not Processing**

```bash
# Check supervisor status
sudo supervisorctl status cmmseng-worker:*

# Restart workers
sudo supervisorctl restart cmmseng-worker:*

# Check worker logs
tail -50 /var/www/cmmseng/storage/logs/worker.log
```

**5. High Memory Usage**

```bash
# Restart PHP-FPM
sudo systemctl restart php8.4-fpm

# Restart Nginx
sudo systemctl restart nginx

# Clear OPcache
php artisan optimize:clear
```

---

## 📞 Support & Resources

**Documentation:**
- README.md - Project overview
- ARCHITECTURE.md - System architecture
- WORKFLOW.md - Business workflows
- POWERBI_INTEGRATION.md - Power BI setup

**Log Files:**
- Application: `/var/www/cmmseng/storage/logs/laravel.log`
- Nginx: `/var/log/nginx/error.log`
- PHP-FPM: `/var/log/php8.4-fpm.log`
- MySQL: `/var/log/mysql/error.log`

**Contact:**
- Developer: Nandang Wijaya
- Email: [contact email]
- Support Hours: [business hours]

---

**Version:** 1.0.0  
**Last Updated:** November 27, 2025  
**Copyright © 2025 Nandang Wijaya. All Rights Reserved.**
