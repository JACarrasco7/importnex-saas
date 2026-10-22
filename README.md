# Importnex

Plataforma SaaS para empresas de **búsqueda e importación de vehículos**: cada
empresa (organización) gestiona sus coches, clientes, solicitudes, encargos con
contrato firmado online y un portal privado para su cliente final.

Laravel 13 · PHP 8.5 · Inertia v3 + Vue 3 · Tailwind v4 · Cashier (Stripe).

## Módulos

| Módulo | Qué hace |
| --- | --- |
| Cuentas y planes | Alta con prueba gratuita, planes Starter/Pro/Enterprise (Stripe), límites por plan, equipo |
| Coches | Ficha, kanban, mapa, fotos, documentos, gastos, checklist, valoración |
| Clientes y solicitudes | CRM ligero, formulario público de solicitud por empresa (`/request/{slug}`) |
| Encargos | Flujo cliente → contrato (firma con código por email) → pagos → seguimiento |
| Portal del cliente | Enlace privado `/mi-encargo/{token}`: fases, contrato, pagos, aprobaciones, mensajes |
| Marketplace y dossier | Web pública de coches y dossier compartible `/c/{token}` |
| Mercado y valoraciones | Importación de informes (ZIP/JSON) y API para herramientas externas |
| Marketing | Contenidos por canal y PDF de ficha/folleto |

## Instalación

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate
# Crea la organización operadora y su administrador (SEED_ADMIN_* en .env)
php artisan db:seed
php artisan storage:link
npm run build
```

Datos de demostración (nunca en producción): `php artisan db:seed --class=DemoDataSeeder`.

Desarrollo: `composer run dev` · Tests: `php artisan test --compact`.

## Configuración

| Variable | Para qué |
| --- | --- |
| `APP_NAME` | Nombre del producto (emails, títulos) |
| `MAIL_*` | Envío de emails (contratos, códigos de firma, invitaciones) |
| `STRIPE_*` | Cobro de suscripciones; `STRIPE_WEBHOOK_SECRET` para `/stripe/webhook` |
| `SUBSCRIPTION_ENFORCE` | `true` bloquea la app (salvo planes) cuando acaba la prueba sin pagar |
| `IMPORTNEX_CHAT_IMPORT_TOKEN` | Token global de la plataforma para la API (uso interno) |
| `COMPANY_*` | Datos de respaldo de la organización operadora |
| `ORDERS_*` | Prefijo de referencias, email del equipo |

Los datos de **cada empresa** (razón social, CIF, dirección, teléfono, IBAN, logo)
se rellenan en **Ajustes → Datos de empresa** y salen en contratos, portal,
emails, fichas públicas y aviso legal. Cada empresa genera su propio **token de
API** en Ajustes (cabecera `X-Import-Token`).

## Producción

- **Scheduler** (obligatorio): `* * * * * php artisan schedule:run` — recordatorios
  de firma, caducidad de contratos, alertas, degradación de planes impagados.
- **Cola**: `php artisan queue:work` (o `QUEUE_CONNECTION=sync`).
- **PDF**: Chrome/Chromium en el servidor para contratos firmados y fichas (Browsershot).
- Copia de seguridad de la base de datos antes de cada `migrate`.

## Aislamiento entre empresas

Todo modelo con `organization_id` se filtra por la organización del usuario
(scope global o filtro explícito). La organización marcada `is_owner` es la
operadora de la plataforma: sin límites de plan ni bloqueo por suscripción.
