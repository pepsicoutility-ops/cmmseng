#!/bin/bash

# ============================================
# CMMS Laravel Optimization Script
# ============================================
# This script optimizes the Laravel application
# for production performance
# ============================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Application directory
APP_DIR="/var/www/cmmseng"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}CMMS Laravel Optimization Script${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root (sudo)${NC}"
    exit 1
fi

# Check if app directory exists
if [ ! -d "$APP_DIR" ]; then
    echo -e "${RED}Application directory not found: $APP_DIR${NC}"
    exit 1
fi

cd $APP_DIR

echo -e "${YELLOW}1. Clearing all caches...${NC}"
php artisan optimize:clear
echo -e "${GREEN}✓ All caches cleared${NC}"
echo ""

echo -e "${YELLOW}2. Caching configuration...${NC}"
php artisan config:cache
echo -e "${GREEN}✓ Configuration cached${NC}"
echo ""

echo -e "${YELLOW}3. Caching routes...${NC}"
php artisan route:cache
echo -e "${GREEN}✓ Routes cached${NC}"
echo ""

echo -e "${YELLOW}4. Caching views...${NC}"
php artisan view:cache
echo -e "${GREEN}✓ Views cached${NC}"
echo ""

echo -e "${YELLOW}5. Caching icons...${NC}"
php artisan icons:cache
echo -e "${GREEN}✓ Icons cached${NC}"
echo ""

echo -e "${YELLOW}6. Optimizing Filament...${NC}"
php artisan filament:optimize
echo -e "${GREEN}✓ Filament optimized${NC}"
echo ""

echo -e "${YELLOW}7. Optimizing Composer autoloader...${NC}"
composer install --optimize-autoloader --no-dev --no-interaction
echo -e "${GREEN}✓ Composer autoloader optimized${NC}"
echo ""

echo -e "${YELLOW}8. Fixing file permissions...${NC}"
chown -R www-data:www-data $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache
chmod 600 $APP_DIR/.env
echo -e "${GREEN}✓ File permissions fixed${NC}"
echo ""

echo -e "${YELLOW}9. Restarting services...${NC}"

# Restart PHP-FPM
if systemctl is-active --quiet php8.4-fpm; then
    systemctl restart php8.4-fpm
    echo -e "${GREEN}✓ PHP-FPM restarted${NC}"
fi

# Restart Nginx
if systemctl is-active --quiet nginx; then
    systemctl restart nginx
    echo -e "${GREEN}✓ Nginx restarted${NC}"
fi

# Restart Supervisor (queue workers)
if systemctl is-active --quiet supervisor; then
    supervisorctl restart cmmseng-worker:*
    echo -e "${GREEN}✓ Queue workers restarted${NC}"
fi

# Restart Redis (if installed)
if systemctl is-active --quiet redis-server; then
    systemctl restart redis-server
    echo -e "${GREEN}✓ Redis restarted${NC}"
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Optimization Complete!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "Application Status:"
php artisan about --only=environment,cache,config

echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "1. Test application: https://your-domain.com"
echo "2. Monitor logs: tail -f storage/logs/laravel.log"
echo "3. Check queue workers: supervisorctl status"
echo ""
