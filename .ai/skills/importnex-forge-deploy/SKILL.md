---
name: importnex-forge-deploy
description: Despliegue en Laravel Forge + producción. Aplica cuando se habla de deploy, Forge, forge-mysql-tunnel, ssh, producción, prod, Railway, subir-informe, push origin master, git pull origin master, npm run build en server, cache clear, optimize:clear, config:cache, route:cache, view:cache, queue:restart, migrate --force, health check post-deploy, restart php-fpm, opcache reset.
---

# Deploy Forge — ImportnexCore

## Topología

- **Repo:** `git@github.com:JACarrasco7/importnex-saas.git` (master).
- **Producción:** `forge@168.144.6.105` → `/home/forge/jjimportmotors.on-forge.com/current/`.
- **SSH key:** `C:\Users\jacar\.ssh\id_ed25519_nopass`.
- **PHP en server:** 8.5.5.
- **Node en server:** >=20.19.

## Regla de oro (NUNCA violar)

**`git commit` ANTES de cualquier deploy/scp.** Workflow: `git add` → `git commit -m "..."` → `git push` → deploy.

## Pipeline post-push (orden estricto)

```powershell
# 1. Push a origin
git push origin master

# 2. SSH + pull + build + caches (en una sola sesión)
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" -o StrictHostKeyChecking=no forge@168.144.6.105 "cd /home/forge/jjimportmotors.on-forge.com/current && git pull origin master 2>&1 && npm ci --no-audit --no-fund 2>&1 && npm run build 2>&1 && php artisan optimize:clear 2>&1 && php artisan config:cache 2>&1 && php artisan route:cache 2>&1 && php artisan view:cache 2>&1 && php artisan storage:link --force 2>&1 && echo '===HEALTH===' && curl -s -o /dev/null -w 'admin HTTP %{http_code} time=%{time_total}s\n' https://jjimportmotors.on-forge.com/admin -L --max-time 15"

# 3. Verificar migraciones pendientes (sin --force)
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" -o StrictHostKeyChecking=no forge@168.144.6.105 "cd /home/forge/jjimportmotors.on-forge.com/current && php artisan migrate --pretend --no-interaction 2>&1 | tail -10"
```

## Scripts locales (Windows)

| Script | Función |
|---|---|
| `forge-mysql-tunnel.bat` | SSH tunnel a MySQL Forge para HeidiSQL local |
| `subir-informe.bat` | Generar informe y subir a producción |
| `subir-informe.ps1` | Mismo, PowerShell |

## Health checks post-deploy

```bash
curl -I https://jjimportmotors.on-forge.com/         # debe 302
curl -I https://jjimportmotors.on-forge.com/admin   # debe 200
curl -I https://jjimportmotors.on-forge.com/up      # debe 200
```

## Rollback rápido

```bash
# En Forge:
cd /home/forge/jjimportmotors.on-forge.com
ls releases/  # ver releases anteriores
ln -sfn /home/forge/jjimportmotors.on-forge.com/releases/<timestamp> current
php artisan optimize:clear
```

O desde Git:

```bash
git revert HEAD
git push origin master
ssh ... 'cd current && git pull'
```

## Anti-patrones (NUNCA)

- ❌ Deploy sin commit previo (pérdida de cambios).
- ❌ `php artisan migrate --force` sin backup de BD.
- ❌ `composer install --no-dev` en producción con Boost instalado (rompe MCP server).
- ❌ Editar archivos directamente en `/home/forge/.../current/` (no persistirá tras redeploy).
- ❌ Cachear `php artisan config:cache` con cambios en `.env` sin recachear.
- ❌ Olvidar `npm run build` tras cambio en `resources/js/` o `resources/css/`.

## Variables de entorno críticas en producción

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...            # regenerado en cada deploy con secret change
DB_CONNECTION=mysql
STRIPE_KEY=pk_live_...        # cambia cuando activemos real Stripe
STRIPE_SECRET=sk_live_...
MISTRAL_API_KEY=...
```

## Troubleshooting común

| Error | Causa | Fix |
|---|---|---|
| `Vite manifest not found` | Falta `npm run build` | SSH + build |
| `Class "X" not found` | Falta `composer dump-autoload` | SSH + dump |
| `SQLSTATE[HY000] [2002]` | DB no accesible | `php artisan config:clear` |
| `403 Forbidden` en /admin | Middleware `has.organization` | Verificar usuario tiene org |
| `vite: command not found` en server | Node modules no instalados | `npm ci` |

## Monitoreo post-deploy

```bash
# Logs en tiempo real
ssh forge@168.144.6.105 'cd current && tail -f storage/logs/laravel.log'

# Queue jobs pendientes
ssh forge@168.144.6.105 'cd current && php artisan queue:monitor'

# Cache hit rate
ssh forge@168.144.6.105 'cd current && php artisan cache:table'
```