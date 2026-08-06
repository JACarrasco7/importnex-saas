# Deployment Rules — Forge + SSH

> Activar por glob: `routes/web.php` (deploy hooks), `.forge/**`, `scripts/**`, `subir-*.{bat,ps1}`.

---

## Regla de oro

**`git commit` ANTES de cualquier deploy/scp.** Sin excepción. Workflow: `add` → `commit` → `push` → deploy.

## Pipeline post-push

```powershell
# 1. Commit + push
git add -A
git commit -m "feat: descripción"
git push origin master

# 2. SSH + pull + build + caches (en una sola sesión)
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" -o StrictHostKeyChecking=no forge@168.144.6.105 "cd /home/forge/jjimportmotors.on-forge.com/current && git pull origin master && npm ci --no-audit --no-fund && npm run build && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan storage:link --force"

# 3. Health check
curl.exe -s -o /dev/null -w "HTTP / %{http_code} time=%{time_total}s\n" https://jjimportmotors.on-forge.com/ --max-time 15
curl.exe -s -o /dev/null -w "HTTP /admin %{http_code} time=%{time_total}s\n" https://jjimportmotors.on-forge.com/admin --max-time 15
```

## Health checks (rutas críticas)

| Ruta | Esperado |
|---|---|
| `/` | 200 (guest) o 302 (auth) |
| `/admin` | 200 o 302 |
| `/marketplace` | 200 |
| `/pricing` | 200 |
| `/login`, `/register` | 200 |
| `/sitemap.xml` | 200 |
| `/robots.txt` | 200 |

## Variables críticas (producción)

```
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
STRIPE_KEY=pk_live_... (cuando activemos real)
STRIPE_SECRET=sk_live_...
MISTRAL_API_KEY=...
```

## Backup antes de migrate

```bash
ssh forge@168.144.6.105 'mysqldump jjimportmotors_prod > /tmp/backup-$(date +%Y%m%d-%H%M%S).sql'
ssh forge@168.144.6.105 'cd current && php artisan migrate --force'
```

## Migración dry-run

```bash
ssh forge@... 'cd current && php artisan migrate --pretend --no-interaction | tail -10'
```

## Rollback

```bash
ssh forge@168.144.6.105 'cd /home/forge/jjimportmotors.on-forge.com && ln -sfn releases/<anterior> current && cd current && php artisan optimize:clear'
```

O desde Git:

```bash
git revert HEAD
git push origin master
ssh forge@... 'cd current && git pull'
```

## Troubleshooting

| Error | Causa | Fix |
|---|---|---|
| `Vite manifest not found` | Falta `npm run build` | SSH + build |
| `Class "X" not found` | Falta `composer dump-autoload` | SSH + dump |
| `SQLSTATE[HY000] [2002]` | DB no accesible | `php artisan config:clear` |
| `403 Forbidden` en /admin | Middleware `has.organization` | Verificar user tiene org |
| `vite: command not found` | Node modules no instalados | `npm ci` |

## Scripts locales

- `forge-mysql-tunnel.bat` → tunnel MySQL Forge para HeidiSQL.
- `subir-informe.bat` / `subir-informe.ps1` → generar informe y subir.

## NO HACER

- ❌ Deploy sin commit previo.
- ❌ `php artisan migrate --force` sin backup.
- ❌ `composer install --no-dev` con laravel/boost instalado (rompe MCP).
- ❌ Editar archivos en `/home/forge/.../current/` directo (no persiste).
- ❌ Olvidar `npm run build` tras cambio en `resources/js/` o `resources/css/`.
- ❌ Cambiar `.env` sin recachear `php artisan config:cache`.
