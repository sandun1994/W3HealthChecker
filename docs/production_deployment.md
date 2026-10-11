# W3HealthChecker — Production Deployment Guide

## 1. Prerequisites
- Clean Ubuntu 22.04 LTS or 24.04 LTS server.
- Non-root user with `sudo` privileges.
- Domain `w3healthchecker.com` pointing to the server IP via Cloudflare (Orange Cloud / Proxied enabled).

---

## 2. Server Provisioning Steps

### Step 1: Install System Packages
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server redis-server supervisor curl git unzip
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-curl php8.2-mbstring \
                    php8.2-xml php8.2-zip php8.2-bcmath php8.2-opcache php8.2-redis
```

### Step 2: Install Composer & Node.js
```bash
# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### Step 3: Secure MySQL & Create Database
```sql
CREATE DATABASE w3healthchecker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'w3_user'@'localhost' IDENTIFIED BY 'STRONG_RANDOM_PASSWORD';
GRANT ALL PRIVILEGES ON w3healthchecker.* TO 'w3_user'@'localhost';
FLUSH PRIVILEGES;
```

---

## 3. Application Deployment Procedure

### Step 4: Clone Repository & Set Permissions
```bash
sudo mkdir -p /var/www/w3healthchecker
sudo chown -R $USER:www-data /var/www/w3healthchecker
git clone https://github.com/sandun1994/W3HealthChecker.git /var/www/w3healthchecker
cd /var/www/w3healthchecker

# Set directory permissions for Laravel
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Step 5: Configure Environment
```bash
cp .env.example .env
nano .env
```
Ensure the following production settings are in place:
```ini
APP_NAME=W3HealthChecker
APP_ENV=production
APP_KEY= # Generate using php artisan key:generate
APP_DEBUG=false
APP_URL=https://w3healthchecker.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=w3healthchecker
DB_USERNAME=w3_user
DB_PASSWORD=STRONG_RANDOM_PASSWORD

QUEUE_CONNECTION=database # or redis
CACHE_STORE=database     # or redis
SESSION_DRIVER=database  # or redis
SESSION_SECURE_COOKIE=true
```

```bash
# Generate application key
php artisan key:generate --force

# Install dependencies (production only)
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Run database migrations
php artisan migrate --force

# Optimize Laravel for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 4. Web Server (Nginx) Configuration

Create `/etc/nginx/sites-available/w3healthchecker.com`:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name w3healthchecker.com www.w3healthchecker.com;
    root /var/www/w3healthchecker/public;

    # Canonical domain redirect (redirect www to non-www)
    if ($host = www.w3healthchecker.com) {
        return 301 https://w3healthchecker.com$request_uri;
    }

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php;
    charset utf-8;

    # Trust Cloudflare Proxies to preserve real client IP
    set_real_ip_from 173.245.48.0/20;
    set_real_ip_from 103.21.244.0/22;
    set_real_ip_from 103.22.200.0/22;
    set_real_ip_from 103.31.4.0/22;
    set_real_ip_from 141.101.64.0/18;
    set_real_ip_from 108.162.192.0/18;
    set_real_ip_from 190.93.240.0/20;
    set_real_ip_from 188.114.96.0/20;
    set_real_ip_from 197.234.240.0/22;
    set_real_ip_from 198.41.128.0/17;
    set_real_ip_from 162.158.0.0/15;
    set_real_ip_from 104.16.0.0/13;
    set_real_ip_from 104.24.0.0/14;
    set_real_ip_from 172.64.0.0/13;
    set_real_ip_from 131.0.72.0/22;
    real_ip_header CF-Connecting-IP;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2|woff|ttf|svg)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site and restart Nginx:
```bash
sudo ln -s /etc/nginx/sites-available/w3healthchecker.com /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 5. Background Queue & Scheduler Setup

### Configure Supervisor for Queue Workers
Create `/etc/supervisor/conf.d/w3-worker.conf`:
```ini
[program:w3-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/w3healthchecker/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --timeout=60
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/w3healthchecker/storage/logs/worker.log
stopwaitsecs=3600
```
Update supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start w3-worker:*
```

### Configure Crontab for Laravel Scheduler
Run `sudo crontab -u www-data -e` and add:
```cron
* * * * * cd /var/www/w3healthchecker && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Zero-Downtime Deployment Script (`deploy.sh`)

Create `/var/www/w3healthchecker/deploy.sh`:
```bash
#!/usr/bin/env bash
set -e

echo "Deploying W3HealthChecker..."

# Put application into maintenance mode
php artisan down || true

# Pull latest release
git pull origin main

# Install PHP and Node dependencies
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run build

# Run database migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers gracefully
php artisan queue:restart

# Bring application out of maintenance mode
php artisan up

echo "Deployment finished successfully!"
```
Make executable: `chmod +x deploy.sh`.
