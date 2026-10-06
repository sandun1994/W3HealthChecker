# Production Deployment Guide

## 1. Prerequisites
- PHP 8.2+ with `pdo`, `mbstring`, `curl`, `dom`, `libxml`, `openssl`.
- Node.js 18+ & NPM.
- SQLite or MySQL 8.0+ / PostgreSQL 15+.
- Redis or database queue driver.

## 2. Zero-Downtime Deployment Steps
```bash
# 1. Fetch latest release
git pull origin main

# 2. Install PHP production dependencies
composer install --no-dev --optimize-autoloader

# 3. Build optimized frontend bundle
npm ci
npm run build

# 4. Run database migrations safely
php artisan migrate --force

# 5. Clear and prime production caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Restart queue workers gracefully
php artisan queue:restart

# 7. Verify system health
curl -f http://127.0.0.1:8000/health
```

## 3. Rollback Procedure
If `/health` reports failure or error logs spike:
1. Revert to previous release tag.
2. Run `php artisan migrate:rollback` if database schema changed.
3. Prime caches: `php artisan config:cache && php artisan route:cache`.
4. Restart workers: `php artisan queue:restart`.
