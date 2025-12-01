# 📜 Deployment Scripts

This directory contains essential scripts for deploying and maintaining the CMMS application in production.

---

## 📋 Script Overview

### 1. **optimize.sh** - Laravel Application Optimization
Optimizes the Laravel application for production performance.

**Features:**
- Clears all Laravel caches
- Rebuilds config, route, view, and icon caches
- Optimizes Composer autoloader
- Fixes file permissions
- Restarts all services (PHP-FPM, Nginx, Supervisor, Redis)

**Usage:**
```bash
sudo bash scripts/optimize.sh
```

**When to run:**
- After deploying new code
- After .env configuration changes
- When performance issues occur
- After Laravel updates

---

### 2. **backup-database.sh** - Automated Database Backup
Creates compressed MySQL backups with retention management.

**Features:**
- Automated MySQL dump with compression (gzip)
- 30-day retention policy (auto-delete old backups)
- File size reporting
- Telegram notifications (optional)
- Error handling with alerts

**Configuration:**
Edit the script and update:
```bash
DB_NAME="cmmseng"
DB_USER="cmmseng_user"
DB_PASS="your-secure-password"  # UPDATE THIS!

# Optional Telegram notifications
ENABLE_TELEGRAM=true
TELEGRAM_BOT_TOKEN="your-bot-token"
TELEGRAM_CHAT_ID="your-chat-id"
```

**Usage:**
```bash
# Manual backup
sudo bash scripts/backup-database.sh

# Schedule daily backup at 2 AM
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-cmms-db.sh
```

**Backup Location:** `/var/backups/cmmseng/database/`

---

### 3. **backup-files.sh** - Storage & File Backup
Backs up uploaded files and storage directory.

**Features:**
- Tar compression of storage directory
- Excludes cache/session/log files (optimization)
- 7-day retention policy
- Size optimization
- Telegram notifications (optional)

**Configuration:**
Same as backup-database.sh (Telegram settings).

**Usage:**
```bash
# Manual backup
sudo bash scripts/backup-files.sh

# Schedule weekly backup on Sunday at 3 AM
sudo crontab -e
# Add: 0 3 * * 0 /usr/local/bin/backup-cmms-files.sh
```

**Backup Location:** `/var/backups/cmmseng/files/`

---

### 4. **restore-database.sh** - Safe Database Restore
Interactive database restore with safety features.

**Features:**
- Lists all available backups with timestamps
- Interactive backup selection
- Creates safety backup before restore
- Automatic rollback on failure
- Confirmation prompts to prevent accidents

**Configuration:**
Update database credentials (same as backup-database.sh).

**Usage:**
```bash
sudo bash scripts/restore-database.sh

# Follow interactive prompts:
# 1. View available backups
# 2. Select backup number
# 3. Confirm restore operation
# 4. Safety backup created automatically
# 5. Restore executed
```

**Safety Features:**
- Always creates safety backup before restore
- Automatic rollback if restore fails
- Confirmation prompt before destructive action

---

### 5. **health-check.sh** - Application Monitoring
Comprehensive health monitoring with alerts.

**Features:**
- HTTP response time monitoring (< 5s threshold)
- Database connection verification
- Queue worker status check
- Disk usage monitoring (90% threshold)
- Memory usage monitoring (90% threshold)
- CPU usage monitoring (90% threshold)
- Laravel error log analysis
- Telegram alerts for failures
- Auto log rotation

**Configuration:**
```bash
APP_URL="https://your-domain.com"  # UPDATE THIS!

# Alert thresholds
DISK_THRESHOLD=90
MEMORY_THRESHOLD=90
CPU_THRESHOLD=90
RESPONSE_TIME_THRESHOLD=5

# Telegram notifications
ENABLE_TELEGRAM=true
TELEGRAM_BOT_TOKEN="your-bot-token"
TELEGRAM_CHAT_ID="your-chat-id"
```

**Usage:**
```bash
# Manual health check
sudo bash scripts/health-check.sh

# Schedule every 15 minutes
sudo crontab -e
# Add: */15 * * * * /var/www/cmmseng/scripts/health-check.sh
```

**Log Location:** `/var/www/cmmseng/storage/logs/health-check.log`

---

### 6. **supervisor-cmmseng.conf** - Queue Worker Configuration
Supervisor configuration for Laravel queue workers.

**Features:**
- Auto-start on system boot
- Auto-restart on failure
- 2 worker processes (configurable)
- Graceful shutdown
- Log rotation
- Complete troubleshooting guide

**Installation:**
```bash
# Copy to supervisor config directory
sudo cp scripts/supervisor-cmmseng.conf /etc/supervisor/conf.d/

# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start cmmseng-worker:*

# Check status
sudo supervisorctl status cmmseng-worker:*
```

**Management Commands:**
```bash
# View logs
sudo supervisorctl tail -f cmmseng-worker:cmmseng-worker_00 stdout

# Restart workers
sudo supervisorctl restart cmmseng-worker:*

# Stop workers
sudo supervisorctl stop cmmseng-worker:*
```

---

## 🚀 Quick Deployment Guide

### 1. Initial Setup
```bash
# Upload scripts to server
scp -r scripts/ user@server:/var/www/cmmseng/

# SSH to server
ssh user@server

# Make scripts executable
cd /var/www/cmmseng
chmod +x scripts/*.sh

# Copy scripts to system bin (optional)
sudo cp scripts/backup-database.sh /usr/local/bin/backup-cmms-db.sh
sudo cp scripts/backup-files.sh /usr/local/bin/backup-cmms-files.sh
sudo cp scripts/restore-database.sh /usr/local/bin/restore-cmms-db.sh
sudo chmod +x /usr/local/bin/backup-cmms-*.sh /usr/local/bin/restore-cmms-db.sh
```

### 2. Configure Scripts
```bash
# Edit backup scripts with production credentials
nano scripts/backup-database.sh
# Update: DB_PASS, TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID

nano scripts/backup-files.sh
# Update: TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID (if using)

nano scripts/health-check.sh
# Update: APP_URL, TELEGRAM_BOT_TOKEN, TELEGRAM_CHAT_ID (if using)

nano scripts/restore-database.sh
# Update: DB_PASS
```

### 3. Setup Supervisor
```bash
# Install supervisor
sudo apt install supervisor

# Copy configuration
sudo cp scripts/supervisor-cmmseng.conf /etc/supervisor/conf.d/

# Start workers
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cmmseng-worker:*

# Verify
sudo supervisorctl status
```

### 4. Schedule Automated Tasks
```bash
# Edit root crontab
sudo crontab -e

# Add these lines:

# Laravel scheduler (every minute)
* * * * * cd /var/www/cmmseng && php artisan schedule:run >> /dev/null 2>&1

# Daily database backup at 2 AM
0 2 * * * /usr/local/bin/backup-cmms-db.sh

# Weekly file backup on Sunday at 3 AM
0 3 * * 0 /usr/local/bin/backup-cmms-files.sh

# Health check every 15 minutes
*/15 * * * * /var/www/cmmseng/scripts/health-check.sh
```

### 5. Optimize Application
```bash
# Run optimization script
sudo bash scripts/optimize.sh

# Verify optimization
php artisan about
```

### 6. Test Monitoring
```bash
# Test health check
bash scripts/health-check.sh

# Test HTTP endpoint
curl https://your-domain.com/health

# Should return:
# {
#   "status": "healthy",
#   "timestamp": "2025-11-27T10:00:00+07:00",
#   "database": "connected",
#   "cache": "working",
#   "app_version": "1.0.0"
# }
```

### 7. Test Backups
```bash
# Test database backup
sudo bash scripts/backup-database.sh

# Test file backup
sudo bash scripts/backup-files.sh

# Verify backups created
ls -lh /var/backups/cmmseng/database/
ls -lh /var/backups/cmmseng/files/
```

---

## 🔔 Telegram Notifications Setup

All scripts support Telegram notifications for alerts and status updates.

### 1. Create Telegram Bot
1. Open Telegram and search for `@BotFather`
2. Send `/newbot` command
3. Follow instructions to create bot
4. Copy the bot token (format: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

### 2. Get Chat ID
1. Send a message to your bot
2. Visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
3. Look for `"chat":{"id":123456789}` in the response
4. Copy the chat ID

### 3. Update Scripts
```bash
# Edit each script and update:
ENABLE_TELEGRAM=true
TELEGRAM_BOT_TOKEN="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
TELEGRAM_CHAT_ID="123456789"
```

### 4. Test Notifications
```bash
# Test with health check
bash scripts/health-check.sh

# You should receive notification on Telegram
```

---

## 📊 Monitoring Dashboard

### Check Application Status
```bash
# Health check endpoint
curl https://your-domain.com/health

# Queue workers
sudo supervisorctl status cmmseng-worker:*

# Recent errors
tail -50 /var/www/cmmseng/storage/logs/laravel.log

# Health check log
tail -50 /var/www/cmmseng/storage/logs/health-check.log

# Worker log
tail -50 /var/www/cmmseng/storage/logs/worker.log
```

### System Resources
```bash
# Disk usage
df -h

# Memory usage
free -h

# CPU usage
htop

# MySQL processes
mysqladmin -u root -p processlist

# Nginx status
sudo systemctl status nginx

# PHP-FPM status
sudo systemctl status php8.4-fpm
```

---

## 🔧 Troubleshooting

### Scripts Won't Execute
```bash
# Fix permissions
chmod +x scripts/*.sh

# Check script syntax
bash -n scripts/optimize.sh
```

### Backup Fails
```bash
# Check disk space
df -h /var/backups

# Create backup directory
sudo mkdir -p /var/backups/cmmseng/{database,files}

# Check MySQL credentials
mysql -u cmmseng_user -p cmmseng
```

### Health Check Fails
```bash
# Check if app is running
curl -I https://your-domain.com

# Check logs
tail -50 /var/www/cmmseng/storage/logs/laravel.log

# Restart services
sudo systemctl restart php8.4-fpm nginx
```

### Queue Workers Not Processing
```bash
# Check supervisor status
sudo supervisorctl status

# Restart workers
sudo supervisorctl restart cmmseng-worker:*

# Check worker logs
tail -50 /var/www/cmmseng/storage/logs/worker.log
```

---

## 📞 Support

For complete deployment instructions, see:
- **DEPLOYMENT.md** - Full VPS deployment guide
- **.env.production.example** - Production environment template

**Developer:** Nandang Wijaya  
**Copyright:** © 2025 Nandang Wijaya. All Rights Reserved.
