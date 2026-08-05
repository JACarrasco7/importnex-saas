<!-- filepath: .github/copilot-instructions.md -->
# ImportnexCore — Instrucciones IA

> **Lee también:** `AGENTS.md` y `CLAUDE.md` (generados por Laravel Boost).
> Esos archivos tienen TODAS las guidelines oficiales de Laravel 11 / PHP / Inertia / Pint / PHPUnit.
> Este archivo sólo añade contexto específico de **este proyecto**.

---

## Stack

- **Backend:** Laravel 11.55 + PHP 8.5
- **Frontend:** Inertia 2 + Vue 3 + Vite 7 + Tailwind 3.4
- **DB:** MySQL (producción en Forge/Railway), SQLite (tests)
- **Auth:** Laravel Breeze + Spatie Laravel Permission + Cashier 16 (Stripe)
- **Extras:** Spatie Browsershot, maatwebsite/excel, simple-qrcode, ziggy

## Dominio del proyecto

**ImportnexCore** es una plataforma multi-tenant para **gestión de vehículos importados**.
Organización se llama **JJ Import Motors** (importadora de vehículos).

### Conceptos de negocio (NO confundir)

- **Plan / Subscription** → lo que el cliente PAGA (Cashier/Stripe). Tipos: `basic`, `pro`, `enterprise`.
- **Rutina** → entrenamiento/servicio recurrente asignado. (NO aplica a este proyecto).
- **Import CSV** → herramienta separada `app/Imports/` con maatwebsite/excel.
- **Tenant / Organization** → cada cliente es una organización; los datos están aislados por `organization_id`.

### Módulos principales

| Módulo | Ruta | Descripción |
|---|---|---|
| `billing` | `/billing/*` | Suscripciones, planes, webhooks Stripe |
| `vehicles` | `/vehicles/*` | CRUD vehículos importados |
| `imports` | `/imports/*` | Importar CSV desde dealers |
| `valuations` | `/valuations/*` | Paquete valoración con QR + PDF |
| `marketplace` | `/marketplace/*` | Marketplace B2B (planes/productos) |
| `public` | `/public/*` | Vistas públicas (formulario solicitud) |

## Convenciones del proyecto

### 🔴 Reglas críticas (NUNCA violar)

1. **Antes de cualquier INSERT/UPDATE/DELETE en BD producción:** hacer backup y pedir confirmación al usuario.
2. **NUNCA** marcar `old_task=1` en `tasks` visibles en calendario sin preguntar.
3. **NUNCA** ejecutar `npm run build` ni `npx vite build` — el usuario lo lanza él.
4. **NUNCA** hacer deploy/scp sin `git commit` previo.
5. **Nunca** añadir dependencias (`composer require` / `npm install`) sin preguntar.

### Frontend

- **Marca JJ Import Motors:** paleta `estoril` (azul oscuro) / `asphalt` / `platinum`. Guía completa en `docs/BRAND.md`.
- **Inertia v2** con deferred props, `WhenVisible`, infinite scroll.
- **Vue 3 Composition API** + `<script setup>`.
- **NO** añadir shadcn-vue / otro design system; usar Tailwind directo.

### Backend

- **Multi-tenancy** vía `organization_id` en todas las tablas de negocio. Middleware:
  - `organization` → valida acceso
  - `has.organization` → requiere organización activa
  - `plan.limit` → valida límites del plan
- **Casts** en método `casts()` del modelo (Laravel 11), no propiedad `$casts`.
- **Tests** PHPUnit (`php artisan make:test --phpunit`). Feature tests mayoritarios.
- **Pint** como formateador: `vendor/bin/pint --dirty --format agent` antes de finalizar cambios PHP.

### i18n

- Idiomas: `es` (defecto), `en` (también `resources/js/i18n/en.js` para frontend).
- Revisar `docs/i18n-espanol-2026-08-03.md` y `scripts/check-translations.cjs` antes de cambios i18n.

## Flujo de trabajo preferido

1. **Antes de editar** → leer AGENTS.md y abrir `.ai/rules/index.md` si vas a tocar dominio nuevo.
2. **Antes de tocar BD** → usar MCP tools: `database-schema`, `database-query` (read-only).
3. **Antes de tocar rutas/config** → usar `php artisan route:list`, `php artisan config:show key.path`.
4. **Documentar** Laravel version-specific con `search-docs` MCP tool.
5. **Tests** → escribir/actualizar test antes de cambiar funcionalidad. `php artisan test --compact`.
6. **Pint** → `vendor/bin/pint --dirty --format agent` antes de cerrar tarea.

## Documentación del proyecto

- `docs/ARQUITECTURA_VISTAS.md` — arquitectura frontend
- `docs/BRAND.md` — manual de marca JJ Import Motors
- `docs/PLAN_MARKETPLACE.md` — roadmap marketplace
- `docs/deploy/` — guías deploy Forge / Railway
- `docs/paquete-valoracion/` — spec del paquete valoración
- `docs/planes/` — planes y suscripciones
- `README.md` — setup del proyecto
- `JJ Import Motors/` — planificación negocio (No tocar)

## Despliegue

- **Producción:** Laravel Forge / Railway (ver `docs/deploy/`).
- **Sandbox local:** Laragon en Windows (PHP 8.5, MySQL 8.0).
- **SSH tunnel Forge:** `forge-mysql-tunnel.bat`.
- **Informe subir:** `subir-informe.bat` / `subir-informe.ps1`.

## Limitaciones

- **PowerShell 5.1** como terminal → usar `;` no `&&`, evitar `bash` syntax.
- **Windows paths** con espacios → entrecomillar.
- Composer genera **Deprecation Notice** por JsonSchema; ignorar excepto si exit code != 0 con error real.
