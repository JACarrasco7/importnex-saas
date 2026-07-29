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
echo "DEBUG: MYSQL_URL=[$MYSQL_URL] REDIS_URL=[$REDIS_URL]"

if [ -n "$MYSQL_URL" ]; then
    # Parse MYSQL_URL (format: mysql://user:pass@host:port/db) and substitute
    php -r '
$url = getenv("MYSQL_URL");
$parts = parse_url($url);
$host = $parts["host"] ?? "";
$port = $parts["port"] ?? 3306;
$user = $parts["user"] ?? "";
$pass = $parts["pass"] ?? "";
$path = ltrim($parts["path"] ?? "/railway", "/");

$content = file_get_contents(".env");
$mapping = [
    "\${MYSQLHOST}" => $host,
    "\${MYSQLPORT}" => $port,
    "\${MYSQL_DATABASE}" => $path,
    "\${MYSQLUSER}" => $user,
    "\${MYSQLPASSWORD}" => $pass,
];
foreach ($mapping as $k => $v) {
    $content = str_replace($k, $v, $content);
}
file_put_contents(".env", $content);
echo "DEBUG: parsed MYSQL_URL host=$host db=$path\n";
'
fi

if [ -n "$REDIS_URL" ]; then
    php -r '
$url = getenv("REDIS_URL");
$parts = parse_url($url);
$host = $parts["host"] ?? "127.0.0.1";
$port = $parts["port"] ?? 6379;

$content = file_get_contents(".env");
$mapping = [
    "\${REDISHOST}" => $host,
    "\${REDISPORT}" => $port,
];
foreach ($mapping as $k => $v) {
    $content = str_replace($k, $v, $content);
}
file_put_contents(".env", $content);
echo "DEBUG: parsed REDIS_URL host=$host\n";
'
fi

# APP_URL fallback
if [ -n "$APP_URL" ]; then
    sed -i "s|\${APP_URL}|$APP_URL|g" .env
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

# Start nginx + php-fpm via supervisord
exec /usr/bin/supervisord -c /etc/supervisord.conf
