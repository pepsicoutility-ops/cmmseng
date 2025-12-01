# 🚀 CMMS VPS Deployment Checklist

**Quick reference checklist for deploying CMMS to production VPS**

---

## ⏰ Pre-Deployment (1-2 days before)

### Documentation Review
- [ ] Read `DEPLOYMENT.md` completely
- [ ] Review `.env.production.example`
- [ ] Read `scripts/README.md`
- [ ] Prepare server credentials
- [ ] Prepare domain name & DNS records

### Server Preparation
- [ ] VPS account created
- [ ] SSH access configured
- [ ] Root or sudo access verified
- [ ] Firewall rules planned
- [ ] SSL certificate plan (Let's Encrypt recommended)

### Database Preparation
- [ ] Export production data if migrating
- [ ] Backup current database
- [ ] Plan database credentials (strong passwords)
- [ ] Document database connection details

### Third-Party Services
- [ ] SMTP credentials ready (Gmail/SES/Mailgun)
- [ ] Telegram bot created (@BotFather)
- [ ] Telegram chat ID obtained
- [ ] S3 bucket created (if using cloud storage)
- [ ] Error tracking service setup (Sentry/Bugsnag - optional)

---

## 📦 Day 1: Server Setup (Estimated: 2-3 hours)

### Step 1: System Update (10 min)
```bash
ssh user@your-server-ip
sudo apt update && sudo apt upgrade -y
```
- [ ] System packages updated
- [ ] Server rebooted if kernel updated: `sudo reboot`

### Step 2: Install Required Software (20 min)
```bash
# Install everything in one command
sudo apt install -y nginx mysql-server php8.4-fpm php8.4-mysql \
    php8.4-mbstring php8.4-xml php8.4-bcmath php8.4-curl \
    php8.4-zip php8.4-gd php8.4-intl php8.4-opcache \
    git curl unzip supervisor certbot python3-certbot-nginx \
    redis-server
```
- [ ] Nginx installed: `nginx -v`
- [ ] MySQL installed: `mysql --version`
- [ ] PHP 8.4 installed: `php -v`
- [ ] Composer installed: `composer --version`
- [ ] Supervisor installed: `supervisorctl version`
- [ ] Redis installed (optional): `redis-cli --version`

### Step 3: Configure MySQL (15 min)
```bash
# Secure MySQL installation
sudo mysql_secure_installation

# Create database
sudo mysql -u root -p
```
SQL commands:
```sql
CREATE DATABASE cmmseng CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cmmseng_user'@'localhost' IDENTIFIED BY 'your-strong-password';
GRANT ALL PRIVILEGES ON cmmseng.* TO 'cmmseng_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
- [ ] MySQL secured
- [ ] Database `cmmseng` created
- [ ] User `cmmseng_user` created
- [ ] Test connection: `mysql -u cmmseng_user -p cmmseng`

### Step 4: Create Application Directory (5 min)
```bash
sudo mkdir -p /var/www/cmmseng
sudo chown -R $USER:www-data /var/www/cmmseng
cd /var/www/cmmseng
```
- [ ] Directory created: `/var/www/cmmseng`
- [ ] Ownership set to `www-data`

### Step 5: Upload Application Files (15 min)

**Option A: Git Clone**
```bash
cd /var/www/cmmseng
git clone <your-repo-url> .
```

**Option B: Upload via SCP** (from local machine)
```bash
# Compress project (exclude vendor, node_modules)
tar -czf cmmseng.tar.gz --exclude='vendor' --exclude='node_modules' \
    --exclude='.git' --exclude='storage/logs/*' \
    -C /path/to/local cmmseng

# Upload to server
scp cmmseng.tar.gz user@server:/tmp/

# On server: Extract
cd /var/www
sudo tar -xzf /tmp/cmmseng.tar.gz
sudo chown -R www-data:www-data cmmseng
```
- [ ] Application files uploaded
- [ ] Files extracted to `/var/www/cmmseng`

### Step 6: Install PHP Dependencies (10 min)
```bash
cd /var/www/cmmseng
composer install --optimize-autoloader --no-dev --no-interaction
```
- [ ] Composer dependencies installed
- [ ] `vendor/` directory created

### Step 7: Configure Environment (20 min)
```bash
cd /var/www/cmmseng
cp .env.production.example .env
nano .env
```

**Edit `.env` with production values:**
- [ ] `APP_URL=https://your-domain.com`
- [ ] `DB_DATABASE=cmmseng`
- [ ] `DB_USERNAME=cmmseng_user`
- [ ] `DB_PASSWORD=your-strong-password`
- [ ] Mail settings (SMTP_HOST, MAIL_USERNAME, MAIL_PASSWORD)
- [ ] Telegram bot token & chat ID
- [ ] Generate app key: `php artisan key:generate`

### Step 8: Run Migrations (5 min)
```bash
php artisan migrate --force
php artisan db:seed --force
```
- [ ] All 30 migrations executed
- [ ] Initial data seeded (users, areas, parts)
- [ ] Verify: `mysql -u cmmseng_user -p -e "USE cmmseng; SHOW TABLES;"`

### Step 9: Storage & Permissions (10 min)
```bash
cd /var/www/cmmseng

# Create storage symlink
php artisan storage:link

# Set permissions
sudo chown -R www-data:www-data .
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;
sudo chmod +x artisan
sudo chmod -R 775 storage bootstrap/cache
sudo chmod 600 .env
```
- [ ] Storage symlink created
- [ ] File permissions set (755/644)
- [ ] Storage writable (775)
- [ ] `.env` secured (600)

### Step 10: Configure Nginx (15 min)
```bash
sudo nano /etc/nginx/sites-available/cmmseng
```
Copy Nginx config from `DEPLOYMENT.md`, then:
```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/cmmseng /etc/nginx/sites-enabled/

# Remove default site
sudo rm /etc/nginx/sites-enabled/default

# Test config
sudo nginx -t

# Restart Nginx
sudo systemctl restart nginx
```
- [ ] Nginx config created
- [ ] Site enabled
- [ ] Nginx restarted
- [ ] Test: `curl http://your-domain.com`

### Step 11: Configure PHP-FPM (10 min)
```bash
sudo nano /etc/php/8.4/fpm/pool.d/www.conf
```
Edit settings (see `DEPLOYMENT.md`), then:
```bash
sudo systemctl restart php8.4-fpm
```
- [ ] PHP-FPM configured
- [ ] Service restarted
- [ ] Check status: `sudo systemctl status php8.4-fpm`

### Step 12: Setup SSL Certificate (15 min)
```bash
# Install SSL certificate
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Test auto-renewal
sudo certbot renew --dry-run
```
- [ ] SSL certificate installed
- [ ] HTTPS enabled
- [ ] Auto-renewal configured
- [ ] Test: `curl https://your-domain.com`

**✅ Day 1 Complete! Application accessible at https://your-domain.com**

---

## 🔧 Day 2: Optimization & Monitoring (Estimated: 2-3 hours)

### Step 13: Optimize Application (10 min)
```bash
cd /var/www/cmmseng

# Make scripts executable
chmod +x scripts/*.sh

# Run optimization
sudo bash scripts/optimize.sh
```
- [ ] All caches built (config, routes, views, icons)
- [ ] Composer autoloader optimized
- [ ] Services restarted

### Step 14: Setup Queue Workers (15 min)
```bash
# Copy supervisor config
sudo cp scripts/supervisor-cmmseng.conf /etc/supervisor/conf.d/

# Start workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cmmseng-worker:*

# Verify
sudo supervisorctl status cmmseng-worker:*
```
- [ ] Supervisor configured
- [ ] 2 workers running
- [ ] Check logs: `tail -f storage/logs/worker.log`

### Step 15: Setup Backups (20 min)

**Configure backup scripts:**
```bash
nano scripts/backup-database.sh
# Update: DB_PASS, TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID

nano scripts/backup-files.sh
# Update: TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID

nano scripts/health-check.sh
# Update: APP_URL, TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID
```

**Copy to system bin:**
```bash
sudo cp scripts/backup-database.sh /usr/local/bin/backup-cmms-db.sh
sudo cp scripts/backup-files.sh /usr/local/bin/backup-cmms-files.sh
sudo cp scripts/restore-database.sh /usr/local/bin/restore-cmms-db.sh
sudo chmod +x /usr/local/bin/backup-cmms-*.sh /usr/local/bin/restore-cmms-db.sh
```

**Test backups:**
```bash
# Test database backup
sudo bash scripts/backup-database.sh

# Test file backup
sudo bash scripts/backup-files.sh

# Verify backups created
ls -lh /var/backups/cmmseng/database/
ls -lh /var/backups/cmmseng/files/
```
- [ ] Backup scripts configured
- [ ] Database backup tested
- [ ] File backup tested
- [ ] Backups verified in `/var/backups/cmmseng/`

### Step 16: Schedule Automated Tasks (10 min)
```bash
sudo crontab -e
```
Add these lines:
```cron
# Laravel scheduler
* * * * * cd /var/www/cmmseng && php artisan schedule:run >> /dev/null 2>&1

# Daily database backup at 2 AM
0 2 * * * /usr/local/bin/backup-cmms-db.sh

# Weekly file backup on Sunday at 3 AM
0 3 * * 0 /usr/local/bin/backup-cmms-files.sh

# Health check every 15 minutes
*/15 * * * * /var/www/cmmseng/scripts/health-check.sh
```
- [ ] Laravel scheduler configured
- [ ] Daily database backup scheduled
- [ ] Weekly file backup scheduled
- [ ] Health check scheduled

### Step 17: Setup Firewall (10 min)
```bash
# Allow SSH
sudo ufw allow OpenSSH

# Allow HTTP & HTTPS
sudo ufw allow 'Nginx Full'

# Enable firewall
sudo ufw enable

# Verify rules
sudo ufw status
```
- [ ] Firewall enabled
- [ ] SSH allowed
- [ ] HTTP/HTTPS allowed
- [ ] Test: Can still access website

### Step 18: Install Fail2Ban (Optional - 10 min)
```bash
sudo apt install -y fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```
- [ ] Fail2Ban installed
- [ ] Service enabled
- [ ] Brute force protection active

**✅ Day 2 Complete! Production environment optimized & monitored**

---

## 🧪 Testing & Verification (1-2 hours)

### Functional Testing
- [ ] Login as super admin: `super_admin@cmms.com` / `password`
- [ ] Create test Work Order
- [ ] Create test PM Schedule
- [ ] Add test inventory transaction
- [ ] Generate QR code for equipment
- [ ] Test barcode WO form (public URL)
- [ ] Upload file (test storage)
- [ ] Export report to Excel
- [ ] Check activity logs

### Performance Testing
- [ ] Page load time < 2 seconds
- [ ] Dashboard widgets load quickly
- [ ] Real-time polling works
- [ ] No console errors (F12 browser console)
- [ ] Mobile responsive (test on phone)

### Notification Testing
- [ ] Create WO → Telegram notification sent
- [ ] Assign WO → Telegram notification sent
- [ ] Complete PM → Telegram notification sent
- [ ] Low stock alert → Telegram notification sent

### Monitoring Testing
- [ ] Health check endpoint: `curl https://your-domain.com/health`
- [ ] Queue workers processing: `sudo supervisorctl status`
- [ ] Logs clean: `tail -50 storage/logs/laravel.log`
- [ ] No errors in health check log

### Backup Testing
- [ ] Run manual database backup
- [ ] Run manual file backup
- [ ] Test restore procedure (on test database)
- [ ] Verify backups in `/var/backups/cmmseng/`

**✅ All Tests Passed! Ready for Production Use**

---

## 📊 Post-Deployment (Week 1)

### Daily Monitoring (First Week)
- [ ] Check health check log: `tail -50 storage/logs/health-check.log`
- [ ] Check application log: `tail -50 storage/logs/laravel.log`
- [ ] Check queue workers: `sudo supervisorctl status`
- [ ] Check Telegram alerts (if any)
- [ ] Monitor server resources: `htop`, `df -h`

### User Feedback
- [ ] Collect user feedback
- [ ] Document issues
- [ ] Prioritize fixes
- [ ] Plan updates

### Performance Optimization
- [ ] Analyze slow queries (MySQL slow query log)
- [ ] Review Nginx access logs
- [ ] Check PHP-FPM logs
- [ ] Optimize database indexes if needed

**✅ Week 1 Complete! Application stable in production**

---

## 🚨 Emergency Contacts & Procedures

### Critical Issues
**Database Down:**
```bash
sudo systemctl restart mysql
php artisan db:monitor
```

**Application Error 500:**
```bash
tail -50 /var/www/cmmseng/storage/logs/laravel.log
sudo bash /var/www/cmmseng/scripts/optimize.sh
```

**Queue Workers Stuck:**
```bash
sudo supervisorctl restart cmmseng-worker:*
```

**Disk Full:**
```bash
# Clean old logs
sudo find /var/www/cmmseng/storage/logs -name "*.log" -mtime +30 -delete

# Clean old backups
sudo find /var/backups/cmmseng -name "*.gz" -mtime +30 -delete
```

### Rollback Procedure
```bash
# Restore previous database backup
sudo bash /usr/local/bin/restore-cmms-db.sh

# Restore previous code (if using git)
cd /var/www/cmmseng
git checkout previous-tag

# Clear caches
sudo bash scripts/optimize.sh
```

---

## 📞 Support Resources

**Documentation:**
- `DEPLOYMENT.md` - Full deployment guide
- `scripts/README.md` - Script documentation
- `ARCHITECTURE.md` - System architecture
- `WORKFLOW.md` - Business workflows

**Logs:**
- Application: `/var/www/cmmseng/storage/logs/laravel.log`
- Worker: `/var/www/cmmseng/storage/logs/worker.log`
- Health: `/var/www/cmmseng/storage/logs/health-check.log`
- Nginx: `/var/log/nginx/error.log`
- PHP-FPM: `/var/log/php8.4-fpm.log`

**Developer:** Nandang Wijaya  
**Copyright:** © 2025 Nandang Wijaya. All Rights Reserved.

---

**Total Deployment Time:** 4-6 hours (over 2 days)  
**Status:** Production Ready ✅
