#!/bin/bash
set -e

cd /var/www/pepcmmsengineering.my.id/cmmseng

echo ">>> Pulling latest code (master)..."
sudo -u ubuntu git pull origin master

echo ">>> Running composer install..."
sudo -u ubuntu composer install --no-dev --prefer-dist --optimize-autoloader

echo ">>> Running migrations..."
sudo -u www-data php artisan migrate --force

echo ">>> Clearing & caching..."
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan optimize

echo ">>> Fixing permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo ">>> Restarting PHP FPM..."
sudo systemctl restart php8.4-fpm

echo ">>> Deployment completed!"
