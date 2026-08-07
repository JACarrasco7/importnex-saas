# Project Context Snapshot
Generated: 2026-08-07 06:35:01

## Quickref

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

- **Boots

## Recent Findings

- **2026-08-06** [medium] Skills duplicadas (.github/skills/ + .claude/skills/) consumían doble contexto
- **2026-08-06** [low] laravel/boost en require rompía composer install --no-dev
- **2026-08-06** [high] PricingPublic.vue en Pages/ en lugar de Pages/Public/
- **2026-08-06** [high] SitemapController usaba campos published/published_at inexistentes
- **2026-08-06** [critical] schema-org.blade.php con @type en JSON-LD

