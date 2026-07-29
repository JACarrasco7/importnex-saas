# Launch Checklist — Importnex SaaS

This document is the go-live checklist for the Importnex SaaS platform.

## Pre-launch checklist

- [ ] All environment variables configured in production `.env`
- [ ] `APP_DEBUG=false` set
- [ ] `APP_KEY` generated and unique per environment
- [ ] Database credentials rotated from defaults
- [ ] MySQL container running with persistent volume
- [ ] Redis container running with persistent volume
- [ ] All migrations executed (`php artisan migrate --force`)
- [ ] Template seeders executed (`php artisan db:seed`)
- [ ] Stripe live keys configured (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`)
- [ ] Stripe webhook endpoint registered in dashboard pointing to `https://<domain>/stripe/webhook`
- [ ] Anthropic API key configured (`ANTHROPIC_API_KEY`)
- [ ] Domain DNS pointing to server (`A` record + `CNAME` for `www`)
- [ ] SSL certificate installed (Let's Encrypt recommended)
- [ ] HTTPS enforced (HSTS headers, redirect 80→443)
- [ ] Automated database backups scheduled (daily, retained 30 days)
- [ ] Automated storage backups scheduled (weekly, retained 90 days)
- [ ] Sentry or alternative APM configured for error tracking
- [ ] Email SMTP configured (Mailgun, SES, or Postmark)
- [ ] Cron job for `schedule:run` installed (daily at 09:00)
- [ ] Queue worker process running (or `schedule:work` supervisor)
- [ ] Firewall allows only 80/443 inbound
- [ ] SSH key-only authentication enabled

## First tenant setup (JJ Import Motors)

### Step 1: Create the organization

```bash
docker-compose exec app php artisan tinker --execute='
$org = App\Models\Organization::firstOrCreate(
    ["name" => "JJ Import Motors"],
    ["plan" => "pro", "trial_ends_at" => now()->addDays(30)]
);
echo "Org ID: " . $org->id;
'
```

### Step 2: Create the owner user

```bash
docker-compose exec app php artisan tinker --execute='
$org = App\Models\Organization::where("name", "JJ Import Motors")->first();
$user = App\Models\User::firstOrCreate(
    ["email" => "joseantonio@jjimportmotors.com"],
    [
        "name" => "Jose Antonio",
        "password" => bcrypt("CHANGE_ME_SECURELY"),
        "organization_id" => $org->id,
        "role" => "owner",
        "email_verified_at" => now(),
    ]
);
echo "User ID: " . $user->id;
'
```

### Step 3: Import real cars

```bash
# Place the JSON file in a known location
docker-compose exec app mkdir -p /var/www/html/data
docker cp "JJ Import Motors/07 Vehículos (operaciones)/coches.json" \
    importnex_app:/var/www/html/data/coches.json

# Dry run first
docker-compose exec app php artisan import:real-cars \
    --file="/var/www/html/data/coches.json" \
    --org="JJ Import Motors" \
    --dry-run

# Real import
docker-compose exec app php artisan import:real-cars \
    --file="/var/www/html/data/coches.json" \
    --org="JJ Import Motors"
```

### Step 4: Verify dashboard

1. Log in at https://app.importnex.com/login as the owner user
2. Navigate to /dashboard
3. Verify KPIs show imported cars
4. Check that all 4 traffic lights are represented

### Step 5: Test AI verification

1. Navigate to a car's Show page
2. Click "Verify with AI"
3. Verify a Claude analysis completes and traffic light is updated
4. Apply the suggestions and confirm the status changes to "Valuing"

### Step 6: Test Stripe subscription (test mode)

1. Navigate to /subscriptions
2. Click "Subscribe" on the Pro plan
3. Use test card `4242 4242 4242 4242` with any future expiry and CVC
4. Verify the webhook updates the organization plan

## Monitoring & alerting

### Key metrics to watch

| Metric | Threshold | Tool |
|--------|-----------|------|
| CPU usage | >70% for 5min | Prometheus / htop |
| Memory usage | >80% | Prometheus / free -m |
| Disk space | <10% free | Prometheus / df -h |
| Database connections | >80% of max | SHOW STATUS |
| Queue backlog | >100 jobs | Horizon / queue:monitor |
| Response time p95 | >2s | APM (Sentry/New Relic) |

### Application alerts (auto-generated)

The `alerts:generate` command runs daily at 09:00 and creates `Alert` records for:

- **Cars parked**: Any car in `Located`, `Offered`, or `Reserved` status for more than 30 days
- **Clients without contact**: Any client in active status with no contact log in 14+ days

These appear at `/alerts` and should be reviewed weekly.

### Critical logs to monitor

- `storage/logs/laravel.log` — Application errors (INFO+)
- `/var/log/apache2/error.log` — Web server errors
- `/var/log/mysql/error.log` — Database errors

## Customer support

- **Email**: soporte@importnex.com
- **Response SLA**: 24h first response, 72h resolution
- **Escalation**: Tech lead → DevOps → CEO

### Rollback procedure

If a release causes critical issues:

```bash
# Roll back to previous version
cd /var/www/importnex
git pull origin main --rebase
git checkout <previous-tag>
docker-compose up -d --build

# Rollback database migration (if needed)
docker-compose exec app php artisan migrate:rollback --step=1
```

Database backups allow point-in-time recovery within 30 days. Contact DevOps immediately if data loss is suspected.

## Marketing & onboarding

- [ ] Landing page live at importnex.com
- [ ] Sign-up form integrated with Stripe Checkout
- [ ] Welcome email sent on registration (Mailgun template)
- [ ] 5-minute product tour (Loom video linked from email)
- [ ] Public documentation at docs.importnex.com (optional)
- [ ] Help center / FAQ page
- [ ] Pricing page with 3 tiers explained

## Known risks & mitigations

| Risk | Severity | Mitigation |
|------|----------|------------|
| Stripe webhook misconfigured | 🔴 Critical | Verify with test event before going live; monitor failed webhooks |
| Anthropic API key invalid | 🟠 High | Verify with a test verification before launch; queue failed jobs for retry |
| Database disk full | 🟠 High | Monitor disk alerts; backup retention policy auto-cleans old files |
| Migration failure mid-deploy | 🔴 Critical | Always backup DB before migrate; rollback procedure documented |
| Stripe outage | 🟡 Medium | Cashier handles gracefully; users retain access during outage |
| LDAP/SSO unavailable | 🟢 Low | Not currently used; local auth fallback works |

## Post-launch (first week)

- [ ] Monitor error rates hourly for first 24h
- [ ] Check Stripe dashboard for unexpected charges/refunds
- [ ] Verify scheduled `alerts:generate` ran successfully
- [ ] Review Sentry for any new errors not seen in staging
- [ ] Collect user feedback via email survey (Day 3, Day 7)
- [ ] Review database growth, plan next backup retention

## Contacts

| Role | Person | Email |
|------|--------|-------|
| DevOps Lead | _TBD_ | devops@importnex.com |
| Customer Success | _TBD_ | success@importnex.com |
| On-call Engineer | _TBD_ | oncall@importnex.com |
| CTO | _TBD_ | cto@importnex.com |

---

**Last updated:** 2026-07-25
**Status:** ✅ Ready for deployment
