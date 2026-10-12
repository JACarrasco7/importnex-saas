# Auditoría ronda 4 — 13-sep-2026

> Dos auditorías externas independientes (4A y 4B) con ángulos distintos sobre el
> sistema de sincronización y la app. Se aplicaron los fixes críticos e
> importantes. Estado final: **673 tests OK · 6 skipped · 0 failed · Pint limpio**.

## 🔴 Críticos arreglados

| ID | Bug | Fix aplicado |
|---|---|---|
| 4A/C1 | **Race condition en `encargos.md`**: `Add-Content` de PowerShell no es atómico; `subir-informe.ps1` + `import-notify-receiver.ps1` podían pisarse entradas | Nuevo `scripts/LockFile.ps1` con `Add-ContentLocked` (lockfile + 10 reintentos + fail-open). Usado en ambos scripts |
| 4A/C2 | **`build-skill-zips.ps1` podía hacer `git push` desde producción** (commitea con `--no-verify` y pushea el repo) | Guard al inicio: aborta si `APP_ENV=production` o si detecta variables de Forge |
| 4B/C2 | **Multi-tenant en comandos**: `alerts:generate` corre sin auth → el global scope `organization` de `Car`/`Client` no filtraba. Ahora el alcance es explícito con `withoutGlobalScope()` (determinista en cualquier contexto) | `GenerateAutomaticAlerts` con scope explícito + test de regresión multi-org |
| 4B/C1 | **`spatie/laravel-permission` instalado pero 0 usos** (~5MB muertos). Riesgo de que alguien llame `assignRole()` creyendo que funciona | Documentado en `.ai/rules/auth-roles.md`. **Desinstalación pendiente de tu OK** (regla: no tocar `composer.json` sin permiso) |

## 🟠 Importantes arreglados

| ID | Bug | Fix |
|---|---|---|
| 4A/I1 | `storeMercado` sin límite → 100k modelos = 100k INSERTs en transacción | Límite 1000 modelos (422) + tests |
| 4A/I4 | `indexCierres` con `?limit=99999` sin clamp | `max(1, min(500, limit))` |
| 4A/I5 | Rate-limit por IP: oficinas con NAT comparten bucket | Key por hash del `X-Import-Token` si existe |
| 4B/I4 | 4 comandos con `->get()` → OOM | `chunk(500)` / `lazy(500)` en MarketAlerts, MarketExport, SendWeeklyAlertDigest, GenerateAutomaticAlerts |
| 4B/I5 | `failed_jobs` crecía sin límite | `Schedule::command('queue:prune-failed --hours=72')->daily()` |
| 4B/I6 | Sin HSTS (SSL-stripping posible) | `Strict-Transport-Security: max-age=31536000; includeSubDomains` en `SecurityHeaders` |
| 4B/I8 | Backups JSON acumulándose (365/año) | Purga automática de >30 días + `withoutOverlapping()` |
| 4B/I11 | `/stripe/webhook` sin rate-limit | Limiter nombrado `stripe-webhook` (60/min) |
| 4B/M13 | `inspire` hourly ensuciaba logs | Eliminado |

## 🐛 Bug introducido y detectado por los propios tests

- Mi `withoutOverlapping()` en `Schedule::call()` **rompía la app** (`LogicException`: los CallbackEvent requieren `->name()` antes). Lo detectó `Round4GuardsTest` al correr. Arreglado con `->name('market:backup-and-prune')`.
- Mi `throttle:60,1` numérico **violaba la convención del proyecto** (solo aliases con nombre). Lo detectó `RateLimiterTest`. Arreglado con el limiter `stripe-webhook`.

**Lección:** los tests existentes del proyecto son la red de seguridad real. Correr la suite ANTES de commitear no es opcional.

## 🐛 Hallazgo colateral (no arreglado por diseño)

**`public/hot` ralentiza TODAS las peticiones ~2s.** Ese archivo lo crea `npm run dev` y hace que `Vite::prefetch()` intente alcanzar el dev server en cada request. No afecta a producción (el archivo está en `.gitignore`), pero:
- Hacía fallar `PerformanceAuditTest` (2 tests de tiempo) de forma engañosa.
- Arreglado: los tests de tiempo ahora **se saltan con un mensaje claro** si `public/hot` existe, en vez de fallar en falso.
- **Si notas las páginas lentas en desarrollo, es esto.** Parar `npm run dev` o borrar `public/hot` lo elimina.

## ✅ Verificado como correcto (no había bug)

- **SQL injection**: 0 casos. Eloquent parametrizado en todo el controller.
- **CSRF en API**: correcto (rutas `/api/*` fuera del grupo `web`; auth por token).
- **PII en logs**: limpio (solo `car_id`).
- **N+1 en `resolveCar()` / `apply()`**: limpio (`insert()` bulk).
- **Mass assignment de `organization_id`**: protegido (el importer resuelve la org desde el token, no del payload).

## 📋 Pendiente de tu decisión

1. **`spatie/laravel-permission`**: ¿implementar de verdad (2-3 días) o desinstalar? Documentado en `.ai/rules/auth-roles.md`.
2. **CORS**: no configurado. No rompe hoy (el chat usa `curl` server-side), pero si algún día se llama desde un navegador cross-origin, el preflight fallará.
3. **Backup de BD**: no hay `spatie/laravel-backup` ni cron `mysqldump`. Solo el backup del JSON de mercado.
4. **`kpis()` agrega en PHP**: con 100k cierres/mes → OOM. Mover a SQL (`SUM(CASE WHEN...)`) es ~1h.
5. **`savePhotos()` secuencial**: 30 fotos × ~1s = 30s de request. `Http::pool()` lo paraleliza.
