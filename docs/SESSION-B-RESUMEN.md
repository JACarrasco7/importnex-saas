# Resumen para otra sesión — Onboarding Sprint 2

**Fecha:** 2026-08-06
**Sesión actual:** Session B — Onboarding + Primer Usuario
**Estado:** 3/6 items completados (~9h de 19h)

---

## ✅ YA HECHO (por Session actual)

### 2.1 Backend — Migration + Model + Controller (3h)

**Migration creada:**
- `database/migrations/2026_08_06_204013_create_user_onboarding_progress_table.php`
- Tabla: `user_onboarding_progress`
- Campos: `user_id`, `organization_id`, 4 pasos booleanos, `current_step`, `completed_at`, `skipped_at`

**Model creado:**
- `app/Models/UserOnboardingProgress.php`
- Relaciones: `user()`, `organization()`
- Accessors: `progress`, `is_completed`, `can_advance`
- Métodos helper: `advanceTo()`, `completeStep*()`, `skip()`

**Controller creado:**
- `app/Http/Controllers/OnboardingController.php`
- `index()` → Muestra wizard (redirige a dashboard si completado)
- `update()` → Avanza paso actual
- `skip()` → Marca como saltado
- `getStepData()` → Datos del paso actual

**Modificaciones:**
- `app/Models/User.php` → Añadida relación `onboardingProgress()`
- `routes/web.php` → Añadidas rutas `/onboarding`, `/onboarding/skip`

### 2.2 Wizard Vue (6h)

**Archivo creado:**
- `resources/js/Pages/Onboarding/Wizard.vue`

**Características:**
- 4 pasos con timeline visual
- Progress bar porcentual (0-100%)
- Step 1: Organization (auto-completado)
- Step 2: First Vehicle (requiere añadir vehículo)
- Step 3: Team Invite (opcional)
- Step 4: Plan Selection (opcional)
- Botón "Saltar onboarding" con confirmación
- Validación: `stepData.canAdvance` para bloquear avance

### 2.3 Checklist (3h)

**Archivo creado:**
- `resources/js/Components/OnboardingChecklist.vue`

**Características:**
- 4 items con iconos (BuildingOffice, Car, UserGroup, CreditCard)
- Progress bar + % completado
- Enlaces directos a acciones cuando pendiente (route('cars.index'), etc)
- Botón "Continuar asistente" → `/onboarding`
- Botón "Saltar" con confirmación

---

## ⏳ PENDIENTE (para otra sesión continuar)

### 2.4 Empty states con doble CTA (2h)

**Archivos a modificar:**
- `resources/js/Pages/Cars/Index.vue`
- `resources/js/Pages/Clients/Index.vue`
- `resources/js/Pages/Contacts/Index.vue`

**Qué hacer:**
- Detectar cuando `items.data.length === 0`
- Mostrar empty state con 2 botones:
  - "Importar CSV" (ej: `route('cars.import')`)
  - "Crear manual" (ej: `route('cars.create')`)
- Usar diseño consistente (icono + título + subtítulo + 2 CTAs)
- Dark mode compatible

### 2.5 Email bienvenida + recordatorio D3, D7 (3h)

**Archivos a crear:**
- `resources/views/mail/onboarding/welcome.blade.php`
- `resources/views/mail/onboarding/reminder-d3.blade.php`
- `resources/views/mail/onboarding/reminder-d7.blade.php`

**Qué hacer:**
- **Welcome email** (D0):
  - Se envía tras completar registro/organization
  - CTA: "Completar onboarding" → `/onboarding`
  - Tips de inicio

- **Reminder D3**:
  - Si `onboarding_progress.completed_at` es NULL después de 3 días
  - CTA: "Retomar onboarding" → `/onboarding`
  - Estímulo: "Solo 4 pasos para empezar"

- **Reminder D7**:
  - Si onboarding NO completado después de 7 días
  - CTA: "Completar ahora" → `/onboarding`
  - Última llamada a la acción

**Backend pendiente:**
- Crear Job para enviar email D3 (ej: `SendOnboardingReminderD3Job`)
- Crear Job para enviar email D7 (ej: `SendOnboardingReminderD7Job`)
- Scheduling en `app/Console/Kernel.php` (o bootstrap/app.php en Laravel 11)
- Trigger: crear UserOnboardingProgress cuando usuario se registra

### 2.6 DatabaseSeeder datos ejemplo (2h)

**Archivo a crear:**
- `database/seeders/OnboardingSeeder.php`

**Qué hacer:**
- Crear 1-2 organizaciones de ejemplo
- Añadir 3-5 vehículos por organización (con datos realistas)
- Añadir 2-3 clientes por organización
- Añadir 1-2 contactos por organización
- Usar factories existentes (`CarFactory`, `ClientFactory`, `ContactFactory`)
- Solo en local/testing, NO en producción

**Uso:**
```bash
php artisan db:seed --class=OnboardingSeeder
```

### Integración Checklist en Dashboard

**Archivo a modificar:**
- `resources/js/Pages/Dashboard.vue`

**Qué hacer:**
- Importar `OnboardingChecklist` componente
- Comprobar si existe `page.props.onboardingProgress`
- Mostrar checklist en posición destacada (top o sidebar)
- Usar condicional `v-if="onboardingProgress && !onboardingProgress.is_completed"`

---

## 🔴 PARA OTRA SESIÓN — NO TOCAR

- ❌ **Session C** — Dark Mode + UX Premium (~20h)
- ❌ **Session D** — Performance + DX (~9.5h)
- ❌ **Session E** — Billing UX + Dunning (~16h)
- ❌ **Session F, G, H** — Marketplace Público (~50h)
- ❌ **Session I** — Notificaciones Avanzadas (~32h)

---

## 📊 Commit de Session B actual (pendiente)

```bash
git add database/migrations/ app/Models/ app/Http/Controllers/ routes/web.php resources/js/Pages/Onboarding/ resources/js/Components/OnboardingChecklist.vue
git commit -m "feat(Session B): onboarding backend + wizard + checklist (2.1-2.3 completados)"
```

**Archivos en commit:**
- `database/migrations/2026_08_06_204013_create_user_onboarding_progress_table.php`
- `app/Models/UserOnboardingProgress.php`
- `app/Http/Controllers/OnboardingController.php`
- `app/Models/User.php`
- `routes/web.php`
- `resources/js/Pages/Onboarding/Wizard.vue`
- `resources/js/Components/OnboardingChecklist.vue`

---

## 🚀 Para otra sesión continuar

**Orden recomendado:**
1. Commit items 2.1-2.3 (ya listos)
2. Implementar 2.4 Empty states (2h) — más rápido
3. Implementar 2.5 Emails (3h) — requiere backend + scheduling
4. Implementar 2.6 Seeder (2h) — más rápido
5. Integrar checklist en Dashboard
6. Commit final Session B completa

**Tiempo restante estimado:** ~9h (1.5 días)