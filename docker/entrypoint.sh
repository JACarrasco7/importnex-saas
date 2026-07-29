#!/bin/bash
set -e

cd /var/www/html

echo "🚀 Starting Importnex SaaS..."

# Ensure .env exists
if [ ! -f .env ]; then
    echo "Creating .env from .env.production..."
    cp .env.production .env
fi

# Substitute Railway environment variables into .env
if [ -n "$MYSQLHOST" ]; then
    sed -i "s|\${MYSQLHOST}|$MYSQLHOST|g" .env
    sed -i "s|\${MYSQLPORT}|${MYSQLPORT:-3306}|g" .env
    sed -i "s|\${MYSQL_DATABASE}|${MYSQL_DATABASE:-railway}|g" .env
    sed -i "s|\${MYSQLUSER}|$MYSQLUSER|g" .env
    sed -i "s|\${MYSQLPASSWORD}|$MYSQLPASSWORD|g" .env
    sed -i "s|\${REDISHOST}|${REDISHOST:-127.0.0.1}|g" .env
    sed -i "s|\${REDISPORT}|${REDISPORT:-6379}|g" .env
    sed -i "s|\${APP_URL}|${APP_URL:-http://localhost}|g" .env
fi

# Wait for MySQL if configured
if [ -n "$MYSQLHOST" ]; then
    echo "Waiting for MySQL at $MYSQLHOST..."
    until php -r "new PDO('mysql:host=' . getenv('MYSQLHOST') . ';port=' . (getenv('MYSQLPORT') ?: 3306), getenv('MYSQLUSER'), getenv('MYSQLPASSWORD'));" 2>/dev/null; do
        sleep 2
    done
    echo "✅ MySQL ready"
fi

# Generate APP_KEY if missing
if [ -z "$APP_KEY" ]; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
fi

# Clear stale caches
php artisan config:clear
php artisan route:clear
php artisan view:clear 2>/dev/null || true

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache 2>/dev/null || true

# Run migrations (ignore errors to allow boot)
echo "Running migrations..."
php artisan migrate --force --no-interaction || echo "⚠️ Migrations failed, continuing..."

# Ensure permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Storage symlink
php artisan storage:link 2>/dev/null || true

echo "✅ Application ready!"

exec "$@"
