# PACKAGES INSTALLED - CMMS Engineering

**Last Updated**: 25 December 2025  
**Laravel Version**: 12.x  
**PHP Version**: ^8.2  
**Filament Version**: 4.x

---

## 📦 PHP Packages (Composer)

### Production Dependencies (`require`)

| Package | Version | Description |
|---------|---------|-------------|
| `laravel/framework` | ^12.0 | Laravel Framework Core |
| `laravel/tinker` | ^2.10.1 | REPL untuk Laravel |
| `filament/filament` | ^4.0 | Admin Panel Framework (Filament v4) |
| `spatie/laravel-permission` | ^6.23 | Role & Permission Management |
| `openai-php/client` | ^0.18.0 | OpenAI API Client |
| `openai-php/laravel` | ^0.18.0 | OpenAI Laravel Integration |
| `maatwebsite/excel` | ^3.1 | Excel Import/Export |
| `pxlrbt/filament-excel` | ^3.2 | Filament Excel Export Actions |
| `barryvdh/laravel-dompdf` | ^3.1 | PDF Generation |
| `simplesoftwareio/simple-qrcode` | ^4.2 | QR Code Generation |
| `intervention/image` | ^3.11 | Image Processing |
| `irazasyed/telegram-bot-sdk` | ^3.15 | Telegram Bot Integration |
| `amenadiel/jpgraph` | ^4.1 | Graph/Chart Generation |
| `markrogoyski/math-php` | ^2.13 | Mathematical Functions Library |
| `rubix/ml` | ^2.5 | Machine Learning Library |

### Development Dependencies (`require-dev`)

| Package | Version | Description |
|---------|---------|-------------|
| `barryvdh/laravel-ide-helper` | ^3.6 | IDE Autocompletion Helper |
| `fakerphp/faker` | ^1.23 | Fake Data Generator |
| `filament/upgrade` | 4.0 | Filament Upgrade Tool |
| `laravel/boost` | ^1.8 | Laravel Development Tools |
| `laravel/dusk` | ^8.3 | Browser Testing |
| `laravel/pail` | ^1.2.2 | Log Viewer |
| `laravel/pint` | ^1.24 | Code Style Fixer |
| `laravel/sail` | ^1.41 | Docker Development Environment |
| `mockery/mockery` | ^1.6 | Mocking Library |
| `nunomaduro/collision` | ^8.6 | Error Reporting |
| `pestphp/pest` | ^4.1 | Testing Framework |
| `pestphp/pest-plugin-laravel` | ^4.0 | Pest Laravel Plugin |

---

## 🎨 NPM Packages (Node.js)

### Development Dependencies (`devDependencies`)

| Package | Version | Description |
|---------|---------|-------------|
| `vite` | ^7.0.7 | Build Tool |
| `laravel-vite-plugin` | ^2.0.0 | Laravel Vite Integration |
| `tailwindcss` | ^4.0.0 | CSS Framework |
| `@tailwindcss/vite` | ^4.0.0 | Tailwind Vite Plugin |
| `axios` | ^1.11.0 | HTTP Client |
| `concurrently` | ^9.0.1 | Run Multiple Commands |
| `highlight.js` | ^11.9.0 | Syntax Highlighting |

---

## 🚀 VPS Deployment Commands

### 1. Install PHP Dependencies
```bash
# Production only (no dev packages)
composer install --no-dev --optimize-autoloader

# If need dev packages (for testing)
composer install --optimize-autoloader
```

### 2. Install Node.js Dependencies
```bash
npm install
npm run build
```

### 3. Required PHP Extensions
Make sure these PHP extensions are installed on VPS:
```bash
# Check installed extensions
php -m

# Required extensions:
- bcmath
- ctype
- curl
- dom
- fileinfo
- gd (for intervention/image)
- json
- mbstring
- openssl
- pdo
- pdo_mysql
- tokenizer
- xml
- zip
- gmp (for rubix/ml)
- intl (recommended)
```

### 4. Install Missing PHP Extensions (Ubuntu/Debian)
```bash
sudo apt update
sudo apt install php8.2-bcmath php8.2-gd php8.2-zip php8.2-gmp php8.2-intl php8.2-xml php8.2-mbstring php8.2-curl
sudo systemctl restart php8.2-fpm
```

### 5. Install Missing PHP Extensions (CentOS/RHEL)
```bash
sudo dnf install php-bcmath php-gd php-zip php-gmp php-intl php-xml php-mbstring php-curl
sudo systemctl restart php-fpm
```

---

## 📝 Package Categories

### AI & Machine Learning
- `openai-php/client` - OpenAI GPT Integration
- `openai-php/laravel` - Laravel OpenAI Service
- `rubix/ml` - Machine Learning (Anomaly Detection, Predictions)
- `markrogoyski/math-php` - Statistical Analysis

### Admin Panel & UI
- `filament/filament` - Admin Panel Framework
- `pxlrbt/filament-excel` - Excel Export for Filament
- `tailwindcss` - CSS Framework

### Export & Reporting
- `maatwebsite/excel` - Excel Import/Export
- `barryvdh/laravel-dompdf` - PDF Generation
- `amenadiel/jpgraph` - Graph/Chart Generation
- `simplesoftwareio/simple-qrcode` - QR Codes

### Media & Files
- `intervention/image` - Image Processing

### Integrations
- `irazasyed/telegram-bot-sdk` - Telegram Bot
- WhatsApp Integration via WAHA API (no composer package, uses HTTP client)

### Security & Authorization
- `spatie/laravel-permission` - Roles & Permissions

---

## ⚠️ Important Notes

1. **OpenAI API Key**: Set `OPENAI_API_KEY` in `.env`
2. **WhatsApp WAHA**: Configure WAHA API settings in `.env`:
   ```env
   WAHA_API_URL=http://your-waha-server:3000
   WAHA_API_TOKEN=your-token
   WAHA_SESSION=default
   WAHA_GROUP_ID=your-group-id
   ```
3. **Telegram Bot**: Set `TELEGRAM_BOT_TOKEN` in `.env`
4. **File Permissions**: Ensure `storage/` and `bootstrap/cache/` are writable
5. **Queue Worker**: This app uses queues for background jobs:
   ```bash
   php artisan queue:work --daemon
   ```

---

## 🔄 Post-Deployment Commands

```bash
# Clear all caches
php artisan optimize:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

# Publish Filament assets
php artisan filament:assets

# Create storage link
php artisan storage:link
```
