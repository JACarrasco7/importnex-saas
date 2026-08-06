---
name: importnex-quickref
description: Cheatsheet 1-página de ImportnexCore. Resumen ejecutivo de todas las reglas críticas. Activar PRIMERO para tener contexto mínimo antes de cargar skills específicas. Útil cuando el agente toca código nuevo y necesita "vista de pájaro" del proyecto.
---

# 🚀 ImportnexCore — Cheatsheet (1 página)

> Vista rápida. Para detalles, cargar skill específica.

---

## Stack

| Capa | Versión | Versión actual (2026-08) |
|---|---|---|
| Laravel | 13.24 | latest |
| PHP | 8.5.5+ | local 8.5.8, prod 8.5.5 |
| Inertia | 3.3.1 + Vue 3.6.1 | latest |
| Vite | 8.2.1 | latest |
| Tailwind | 4.3.3 (CSS-first) | latest |
| Cashier | 16.6.0 | latest |
| Spatie Perm | 8.3.0 | latest |
| Sanctum | 4.3.3 | latest |
| PHPUnit | 13.2.6 | latest |
| Boost | 2.5.0 | latest |

## Multi-tenancy

- **Toda tabla de negocio:** `organization_id` (FK + soft delete + index).
- **Toda query:** `->where('organization_id', auth()->user()->organization_id)`.
- **Toda validación `exists`:** scoped a la org.
- **Toda factory:** `for($org)`.
- **Tests:** mínimo 2 orgs para verificar aislamiento.

## Rutas críticas (producción)

| Ruta | Retorno |
|---|---|
| `/` | 200 (guest) / 302 (auth) |
| `/admin` | 302 redirect |
| `/marketplace` | 200 |
| `/pricing` | 200 (Inertia Public/PricingPublic) |
| `/login`, `/register` | 200 |
| `/sitemap.xml` | 200 |

## Design system

- **Paleta:** `estoril-700` (#1A306D), `asphalt-900`, `platinum-400`.
- **Tokens en `app.css @theme`** — NUNCA `tailwind.config.js`.
- **Dark mode:** clase `dark` en `<html>`, variante `dark:` en cada elemento.
- **Iconos:** Heroicons 24/outline.
- **WCAG AA:** contraste 4.5:1 + focus ring + aria-label.

## i18n

- **Backend:** `resources/lang/{es,en}/` (PHP arrays).
- **Frontend:** `resources/js/i18n/{es,en}.js`.
- **Composable:** `useTranslations().t('clave')`.
- **Validar:** `node scripts/check-translations.cjs`.

## Billing

- **Vitalicio:** `organizations.is_owner = true`.
- **Webhook idempotente:** `WebhookEvent::firstOrCreate(['stripe_id' => $event->id])`.
- **Verificar firma** SIEMPRE antes de procesar.
- **Dunning ES** con grace period 7 días.
- **Downgrade seguro:** `swap()` no borra datos.

## Deploy

- **Commit ANTES de deploy** (sin excepción).
- **Pipeline:** push → SSH → pull → `npm ci` → `npm run build` → caches.
- **Health check post-deploy:** curl 4+ rutas (200 esperado).
- **Backup antes de migrate** en producción.
- **Rollback:** `ln -sfn releases/<anterior> current`.

## Tests

- **PHPUnit 13** (no Pest).
- **SQLite `:memory:`** en `phpunit.xml`.
- **RefreshDatabase** en cada test con BD.
- **Mocks** solo servicios externos (Stripe, Mistral).
- **Asserciones:** `assertTrue` > `assertEquals(true)`.

## Frontend

- **Vue 3 `<script setup>`** obligatorio (NO options API).
- **Single root** por componente.
- **Inertia v3:** `Deferred`, `WhenVisible`, `Link prefetch`.
- **Tailwind v4:** CSS-first con `@theme`.

## Backend

- **Bootstrap/app.php** para middleware + routes.
- **Casts** en método `casts()` (no propiedad).
- **Form Requests** para validación >3 reglas.
- **Eager load** SIEMPRE con `with()`.
- **Sin `dd()` / `dump()`** en código.

## Comandos frecuentes

```bash
# Local
php artisan test --compact
php artisan test --filter=Billing
npm run build    # USER lanza

# Deploy
git add -A; git commit -m "..."; git push origin master
ssh -i "$sshkey" forge@168.144.6.105 'cd /home/forge/jjimportmotors.on-forge.com/current && git pull && npm ci && npm run build && php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache'

# Verificar
curl.exe -s -o /dev/null -w "%{http_code}\n" https://jjimportmotors.on-forge.com/ --max-time 15
```

## Anti-patrones (NUNCA)

- ❌ `Model::all()` en producción.
- ❌ `dd()` / `dump()` / `var_dump()`.
- ❌ Tailwind v3 `tailwind.config.js` con theme custom.
- ❌ Strings hardcoded visibles.
- ❌ `@type` en JSON dentro de `.blade.php` (escapar como `@@type`).
- ❌ Deploy sin commit previo.
- ❌ `migrate --force` sin backup.
- ❌ `npm run build` lanzado por IA (USER lanza).

---

**Cargar skill específica** si necesitas más detalle:

| Skill | Cuándo |
|---|---|
| `importnex-multitenancy` | Organización, scoping, factories multi-tenant |
| `importnex-cashier-billing` | Stripe, webhooks, planes, dunning |
| `importnex-i18n` | Traducciones es/en, `useTranslations` |
| `importnex-bridge-mistral` | Mistral API, cache, retry, fallback |
| `importnex-forge-deploy` | Pipeline completo Forge + SSH |
| `importnex-ai-chat` | SSE streaming, providers, rate limit |
| `importnex-design-system` | Tokens, dark mode, animaciones, WCAG |
| `importnex-tests-phpunit` | Tests, RefreshDatabase, Mockery |
