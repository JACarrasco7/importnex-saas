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
echo "DEBUG: MYSQLHOST=[$MYSQLHOST] MYSQLPORT=[$MYSQLPORT] REDISHOST=[$REDISHOST]"

if [ -n "$MYSQLHOST" ]; then
    # Use PHP for reliable substitution (single quotes so bash doesn't expand ${MYSQLHOST})
    php -r '
$content = file_get_contents(".env");
$mapping = [
    "\${MYSQLHOST}" => getenv("MYSQLHOST") ?: "",
    "\${MYSQLPORT}" => getenv("MYSQLPORT") ?: "3306",
    "\${MYSQL_DATABASE}" => getenv("MYSQL_DATABASE") ?: "railway",
    "\${MYSQLUSER}" => getenv("MYSQLUSER") ?: "",
    "\${MYSQLPASSWORD}" => getenv("MYSQLPASSWORD") ?: "",
    "\${REDISHOST}" => getenv("REDISHOST") ?: "127.0.0.1",
    "\${REDISPORT}" => getenv("REDISPORT") ?: "6379",
    "\${APP_URL}" => getenv("APP_URL") ?: "http://localhost",
];
foreach ($mapping as $k => $v) {
    $content = str_replace($k, $v, $content);
}
file_put_contents(".env", $content);
echo "DEBUG: .env substituted\n";
'
fi

# Show resulting DB_HOST for debugging
echo "DEBUG DB_HOST:"; grep DB_HOST .env

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
