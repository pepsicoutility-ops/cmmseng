# VPS Complete Cleanup Guide
## ⚠️ WARNING: This will DELETE EVERYTHING on your VPS

**This process is IRREVERSIBLE. All data, files, and configurations will be permanently deleted.**

---

## Step-by-Step Cleanup Process

### Step 1: Connect to VPS via PuTTY
1. Open PuTTY
2. Enter your VPS IP address
3. Click "Open"
4. Login with your username and password

### Step 2: Stop All Running Services

```bash
# Stop Nginx web server
sudo systemctl stop nginx

# Stop PHP-FPM
sudo systemctl stop php8.4-fpm
# Or if using PHP 8.1 or 8.3:
# sudo systemctl stop php8.1-fpm
# sudo systemctl stop php8.3-fpm

# Stop Supervisor (queue workers)
sudo systemctl stop supervisor

# Stop MySQL/MariaDB (if you want to keep database running, skip this)
sudo systemctl stop mysql
# Or: sudo systemctl stop mariadb
```

### Step 3: Remove Application Files

```bash
# Navigate to web root
cd /var/www

# List what's there (to confirm location)
ls -la

# Remove the entire application directory
# Replace 'cmmseng' with your actual directory name if different
sudo rm -rf /var/www/cmmseng
sudo rm -rf /var/www/html/cmmseng

# Check if there are any other application directories
ls -la /var/www
```

### Step 4: Remove Nginx Configuration

```bash
# Remove site configuration from sites-available
sudo rm -f /etc/nginx/sites-available/cmmseng
sudo rm -f /etc/nginx/sites-available/cmmseng.conf
sudo rm -f /etc/nginx/sites-available/default

# Remove symbolic link from sites-enabled
sudo rm -f /etc/nginx/sites-enabled/cmmseng
sudo rm -f /etc/nginx/sites-enabled/cmmseng.conf
sudo rm -f /etc/nginx/sites-enabled/default

# List remaining configurations
ls -la /etc/nginx/sites-available/
ls -la /etc/nginx/sites-enabled/
```

### Step 5: Remove Supervisor Configuration

```bash
# Remove supervisor configuration files
sudo rm -f /etc/supervisor/conf.d/cmmseng.conf
sudo rm -f /etc/supervisor/conf.d/cmmseng-worker.conf
sudo rm -f /etc/supervisor/conf.d/supervisor-cmmseng.conf

# List remaining supervisor configs
ls -la /etc/supervisor/conf.d/
```

### Step 6: Remove SSL Certificates (if any)

```bash
# Remove Let's Encrypt certificates
sudo rm -rf /etc/letsencrypt/live/yourdomain.com
sudo rm -rf /etc/letsencrypt/archive/yourdomain.com
sudo rm -rf /etc/letsencrypt/renewal/yourdomain.com.conf

# Or remove all Let's Encrypt data
sudo rm -rf /etc/letsencrypt/*
```

### Step 7: Clean Database

**Option A: Drop the entire database**
```bash
# Login to MySQL
sudo mysql -u root -p

# Inside MySQL prompt:
DROP DATABASE IF EXISTS cmmseng;
DROP DATABASE IF EXISTS cmmseng_production;
DROP USER IF EXISTS 'cmmseng'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Option B: Drop specific user only (keep database)**
```bash
sudo mysql -u root -p

# Inside MySQL prompt:
DROP USER IF EXISTS 'cmmseng'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**Option C: Keep everything** (skip this step)

### Step 8: Remove Log Files

```bash
# Remove application logs
sudo rm -rf /var/www/cmmseng/storage/logs/*

# Remove Nginx logs
sudo rm -f /var/log/nginx/cmmseng-access.log
sudo rm -f /var/log/nginx/cmmseng-error.log
sudo rm -f /var/log/nginx/access.log
sudo rm -f /var/log/nginx/error.log

# Remove Supervisor logs
sudo rm -rf /var/log/supervisor/*

# Remove PHP-FPM logs (optional)
sudo rm -f /var/log/php8.2-fpm.log
```

### Step 9: Remove Cron Jobs

```bash
# Edit crontab for www-data user
sudo crontab -u www-data -l

# If there are Laravel scheduler entries, remove them
sudo crontab -u www-data -r

# Check root crontab as well
sudo crontab -l
# Remove any CMMS-related entries:
sudo crontab -e
# Delete the lines related to your application
```

### Step 10: Clean Composer Cache (Optional)

```bash
# If you installed Composer globally
sudo rm -rf /root/.composer
sudo rm -rf /home/yourusername/.composer
```

### Step 11: Remove Any Uploaded Files/Storage

```bash
# Remove any files in storage that weren't in the app directory
sudo rm -rf /var/www/storage/cmmseng
```

### Step 12: Restart Services (Clean State)

```bash
# Restart Nginx (with no site configuration)
sudo systemctl restart nginx

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Restart Supervisor (with no workers)
sudo systemctl restart supervisor

# Check status
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status supervisor
```

### Step 13: Verify Complete Cleanup

```bash
# Check if any application files remain
find /var/www -name "*cmms*"
find /var/www -name "*cmmseng*"

# Check Nginx configs
ls -la /etc/nginx/sites-available/
ls -la /etc/nginx/sites-enabled/

# Check Supervisor configs
ls -la /etc/supervisor/conf.d/

# Check running processes
ps aux | grep cmms
ps aux | grep supervisor

# Check Nginx is serving default page
curl localhost
```

---

## Quick Nuclear Option (Delete Everything at Once)

**⚠️ EXTREME CAUTION: This removes EVERYTHING**

```bash
# Stop all services
sudo systemctl stop nginx php8.2-fpm supervisor

# Remove application files
sudo rm -rf /var/www/cmmseng
sudo rm -rf /var/www/html/cmmseng
sudo rm -rf /var/www/html/*

# Remove all configurations
sudo rm -rf /etc/nginx/sites-available/*
sudo rm -rf /etc/nginx/sites-enabled/*
sudo rm -rf /etc/supervisor/conf.d/*

# Remove SSL certificates
sudo rm -rf /etc/letsencrypt/*

# Remove logs
sudo rm -rf /var/log/nginx/*
sudo rm -rf /var/log/supervisor/*

# Drop database
sudo mysql -u root -p -e "DROP DATABASE IF EXISTS cmmseng; DROP USER IF EXISTS 'cmmseng'@'localhost'; FLUSH PRIVILEGES;"

# Remove cron jobs
sudo crontab -u www-data -r 2>/dev/null || true

# Restart services
sudo systemctl restart nginx php8.2-fpm supervisor

echo "VPS completely cleaned!"
```

---

## Post-Cleanup Verification

After cleanup, verify:

1. **Browse to your VPS IP** - Should show Nginx default page or 404
2. **No application files**: `ls /var/www/`
3. **No configs**: `ls /etc/nginx/sites-enabled/`
4. **Database gone**: `sudo mysql -e "SHOW DATABASES;"`
5. **No processes**: `ps aux | grep cmms`

---

## What Happens After This?

Your VPS will be in a clean state with:
- ✅ Nginx installed and running (default config)
- ✅ PHP-FPM installed and running
- ✅ MySQL/MariaDB installed and running
- ✅ Supervisor installed and running
- ❌ No application files
- ❌ No custom configurations
- ❌ No database data
- ❌ No SSL certificates

You can now start fresh deployment from scratch.

---

## Files to Delete from Your Local Machine (Optional)

After VPS cleanup, consider removing these deployment-related files from your local project:

```powershell
# From c:\laragon\www\cmmseng\
rm deploy.ps1
rm deploy-simple.ps1
rm DEPLOYMENT*.md
rm VPS_DEPLOYMENT_GUIDE.md
rm setup-vps-part1.sh
rm setup-vps-part2.sh
rm fix-vps-errors.sh
```

---

## Ready to Start Fresh?

Once cleanup is complete, you can:
1. Start a new deployment from scratch
2. Use a different deployment method
3. Keep the VPS ready for a different project
4. Cancel the VPS service if no longer needed
