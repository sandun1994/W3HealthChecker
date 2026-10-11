#!/usr/bin/env bash
set -e

echo "Starting W3HealthChecker Production Container..."

# Ensure permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Run migrations if configured
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Executing database migrations..."
    php /var/www/html/artisan migrate --force
fi

# Cache configuration, routes, and views in production
if [ "${APP_ENV:-production}" = "production" ]; then
    echo "Caching Laravel configuration and routes..."
    php /var/www/html/artisan config:cache || true
    php /var/www/html/artisan route:cache || true
    php /var/www/html/artisan view:cache || true
fi

echo "Starting Supervisor process manager..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
