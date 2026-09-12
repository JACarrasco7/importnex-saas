# Deployment Guide

## Prerequisites

- Docker 20+ and Docker Compose v2
- A registered domain with DNS pointing to your server
- SSH access to your VPS (Ubuntu 22.04 LTS recommended)
- Stripe account (test mode for staging)
- Anthropic API key

## Local development with Docker

```bash
# Clone the repository
git clone https://github.com/your-org/importnex-saas.git
cd importnex-saas

# Copy environment template
cp .env.example .env

# Start containers
docker-compose up -d

# Run migrations and seed
docker-compose exec app php artisan migrate --seed

# Visit
open http://localhost:8080
```

## Production deployment

### 1. Provision a VPS

Recommended providers:
- **Hetzner** — CX21 (2 vCPU, 4GB RAM, ~€5/mo)
- **DigitalOcean** — Basic Droplet ($6/mo)
- **AWS Lightsail** — $5/mo

Ubuntu 22.04 LTS, 2 vCPU, 4GB RAM minimum.

### 2. Server setup

```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sh get-docker.sh
usermod -aG docker $USER

# Install Docker Compose
sudo apt install docker-compose-plugin
```

### 3. Clone and configure

```bash
git clone https://github.com/your-org/importnex-saas.git /var/www/importnex
cd /var/www/importnex

# Production .env (NEVER commit this)
cat > .env <<'EOF'
APP_NAME="Importnex"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://app.importnex.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=importnex
DB_USERNAME=importnex
DB_PASSWORD=USE_STRONG_PASSWORD_HERE

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
FILESYSTEM_DISK=public
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="no-reply@importnex.com"
MAIL_FROM_NAME="${APP_NAME}"

STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

ANTHROPIC_API_KEY=sk-ant-...
EOF
```

### 4. First boot

```bash
# Build and start
docker-compose up -d --build

# Generate APP_KEY (it auto-generates if empty, but you can also do this)
docker-compose exec app php artisan key:generate --force

# Run migrations + seed templates
docker-compose exec app php artisan migrate --seed --force

# Import real cars (JJ Import Motors)
docker-compose exec app php artisan import:real-cars \
    --file="/var/www/importnex/data/coches.json" \
    --org="JJ Import Motors"
```

### 5. SSL with Let's Encrypt

```bash
# Install certbot
sudo apt install certbot

# Stop nginx/apache temporarily
docker-compose stop app

# Get certificate
sudo certbot certonly --standalone -d app.importnex.com

# Configure SSL in a reverse proxy (Caddy is easiest)
cat > /etc/caddy/Caddyfile <<'EOF'
app.importnex.com {
    reverse_proxy localhost:8080
    tls you@example.com
}
EOF

sudo systemctl reload caddy

# Restart app
docker-compose start app
```

### 6. Automated backups

```bash
# Database backup (daily at 3 AM)
cat > /etc/cron.daily/backup-importnex <<'EOF'
#!/bin/bash
BACKUP_DIR=/var/backups/importnex
mkdir -p $BACKUP_DIR
docker-compose exec -T mysql mysqldump -uimportnex -psecret importnex | gzip > $BACKUP_DIR/db-$(date +\%Y\%m\%d).sql.gz
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete
EOF
chmod +x /etc/cron.daily/backup-importnex

# Storage backup (weekly)
cat > /etc/cron.weekly/backup-storage <<'EOF'
#!/bin/bash
tar czf /var/backups/importnex/storage-$(date +\%Y\%m\%d).tar.gz /var/www/importnex/storage
find /var/backups/importnex -name "storage-*.tar.gz" -mtime +90 -delete
EOF
chmod +x /etc/cron.weekly/backup-storage
```

### 7. Scheduled tasks

The `alerts:generate` command should run daily:

```bash
# In the app container's crontab
docker-compose exec app crontab -e
# Add this line:
0 9 * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

Or run a supervisor process:

```bash
docker-compose exec app php artisan schedule:work
```

### 8. Monitoring

Install Sentry for error tracking:

```bash
docker-compose exec app composer require sentry/sentry-laravel
```

Add to `.env`:
```
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
```

### 9. Stripe webhook

In the Stripe dashboard, configure the webhook endpoint:
```
https://app.importnex.com/stripe/webhook
```

Subscribe to events:
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`

## Troubleshooting

### Container won't start

```bash
docker-compose logs app
docker-compose logs mysql
```

### Migration errors

```bash
docker-compose exec app php artisan migrate:fresh --force
```

### Permission issues

```bash
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Out of disk

```bash
docker system prune -a
```

## Security checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong database password
- [ ] APP_KEY generated and unique
- [ ] HTTPS enforced
- [ ] Firewall allows only 80/443
- [ ] Stripe webhooks signed
- [ ] Database backups encrypted
- [ ] Logs rotated and monitored
- [ ] Sentry or similar APM configured

## Scaling

For higher traffic:

1. **Database**: Move MySQL to managed service (RDS, Cloud SQL)
2. **Cache**: Move Redis to managed service (ElastiCache, Memorystore)
3. **Storage**: Switch `FILESYSTEM_DISK=s3` with proper AWS credentials
4. **Queue**: Run dedicated worker containers:
   ```yaml
   queue-worker:
     build: .
     command: php artisan queue:work --tries=3
     deploy:
       replicas: 3
   ```
5. **Load balancer**: Multiple app instances behind nginx/Caddy
