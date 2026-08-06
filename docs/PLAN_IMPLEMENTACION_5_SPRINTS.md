<!-- filepath: docs/PLAN_IMPLEMENTACION_5_SPRINTS.md -->
# Plan de Implementación — Programa de Mejora 2026-08-06

> **Operativo y ejecutable** para agente IA. 5 sprints, 30 tareas, 35 commit points, 5 hitos de deploy, ~78h estimadas.
> Tachar cada ítem conforme avance.

## Mapa de dependencias

```
S1 (quick wins) ──┬──► S2 (onboarding) ──► D1
                  ├──► S3 (dark/a11y) ───► D2
                  ├──► S4 (perf) ────────► D3
                  └──► S5 (billing UX) ──► D4
```

---

## Convenciones

- **ID tarea:** `S{Sprint}.E{Épica}.T{Tarea}` (ej. S1.E1.T1 = Sprint 1, Épica 1, Tarea 1)
- **Commit point:** `CP-S{Sprint}.{N}` — agrupa una o varias tareas en un commit atómico
- **Hito de deploy:** `D{N}` — parada explícita con `git push` + `forge pull`
- **Criterio "Hecho":** checkbox ✅ cuando cumple el criterio de aceptación
- **Usuario:** instrucciones que requieren acción humana (npm run build, aprobar deps)

---

## S1 — Quick wins de producto (1 semana)

### S1.E1 — PublicLayout.vue compartido

#### S1.E1.T1 — Crear `resources/js/Layouts/PublicLayout.vue`
- **Archivos:** crear `resources/js/Layouts/PublicLayout.vue`
- **Estructura:** `<header>` (logo + nav: Marketplace, Pricing, Login) + `<slot/>` + `<footer>` (3 columnas: producto, legal, contacto)
- **Marca:** estoril 700 en logo y CTAs, asphalt 900 textos, platinum 200 surfaces
- **i18n:** claves `public.nav.*`, `public.footer.*` en `es.js` + `en.js`
- **Responsive:** mobile-first, hamburger menu en < md
- **Criterio:** componente Vue puro, sin dependencias de auth, slot default
- **Test:** manual (verificar que renderiza header+slot+footer)
- **CP-S1.1**

#### S1.E1.T2 — Migrar `MarketplaceIndex.vue` al PublicLayout
- **Archivos:** `resources/js/Pages/Public/MarketplaceIndex.vue`
- **Cambio:** envolver `<template>` con `<PublicLayout>` y eliminar header/footer duplicados
- **Criterio:** MarketplaceIndex mantiene apariencia, código -30% líneas
- **Test:** visual, curl `GET /marketplace` debe seguir 200
- **CP-S1.2**

#### S1.E1.T3 — Migrar `MarketplaceShow.vue` al PublicLayout
- **Archivos:** `resources/js/Pages/Public/MarketplaceShow.vue`
- **Mismo patrón que T2**
- **CP-S1.2**

#### S1.E1.T4 — Migrar `CarRequestForm.vue` al PublicLayout
- **Archivos:** `resources/js/Pages/Public/CarRequestForm.vue`
- **Mismo patrón**
- **Verificar:** throttle:5,10 sigue activo
- **CP-S1.2**

#### S1.E1.T5 — Verificación S1.E1
- `php artisan route:list --name=public` debe listar 4 rutas
- Visual: `curl /marketplace` y `curl /request/jj-import-motors` retornan HTML sin duplicar header
- **CP-S1.2** (merge con T2/T3/T4)

---

### S1.E2 — Renombrar rutas y home pública

#### S1.E2.T1 — Renombrar `/admin` → `/` (landing real)
- **Archivos:** `routes/web.php`
- **Cambio:**
  - `Route::get('/', ...)` → redirect a `/marketplace` ACTUAL (lo dejo)
  - `Route::get('/admin', ...)` → renderiza `Welcome.vue` (mantener)
  - `Route::get('/', ...)` (nuevo) → devuelve `Inertia::render('Welcome')` para visitantes auth/visit
  - La `route::get('/admin')` se elimina; el dashboard pasa a `/dashboard` (ya existe)
- **Lógica:** si usuario autenticado → `/dashboard`, si guest → `Welcome`
- **Criterio:** URL `/` muestra Welcome.vue cuando guest; cuando authed redirige a `/dashboard`
- **CP-S1.3**

#### S1.E2.T2 — Actualizar links internos que apunten a `/admin`
- **Búsqueda:** `grep -r "/admin" resources/js/`
- **Archivos a tocar:** menu/sidebar/nav que enlacen a `/admin` → reemplazar por `/dashboard`
- **Criterio:** 0 referencias a `/admin` excepto en `routes/web.php` (legacy opcional)
- **CP-S1.3**

#### S1.E2.T3 — Verificar navegación hogar
- `curl /` (guest) → HTTP 200, HTML Welcome
- `curl /dashboard` (sin auth) → HTTP 302 → `/login`
- **CP-S1.3**

---

### S1.E3 — Pricing público

#### S1.E3.T1 — Crear `Pages/Public/PricingPublic.vue`
- **Archivos:** crear `resources/js/Pages/Public/PricingPublic.vue`
- **Datos:** recibe `plans` desde `Inertia::render(...)` (controlador nuevo o reusar `SubscriptionController@index` pasando `public=true`)
- **Estructura:** hero + tabla 3 columnas (Starter/Pro/Enterprise) + "Most popular" en Pro + CTAs a `/register?plan=pro`
- **Marca:** estoril 700 hero, gradiente estoril-600→estoril-800 en "Most popular"
- **i18n:** claves `public.pricing.*`
- **SEO:** `<Head title="Planes · JJ Import Motors">`
- **Criterio:** página renderiza sin auth, 3 cards, CTA a register
- **CP-S1.4**

#### S1.E3.T2 — Añadir ruta `/pricing` pública
- **Archivos:** `routes/web.php`
- **Cambio:** `Route::get('/pricing', [SubscriptionController::class, 'publicIndex'])->name('pricing');`
- **Método:** `SubscriptionController::publicIndex()` que devuelve `Inertia::render('Public/PricingPublic', ['plans' => config('subscription.plans')])`
- **Criterio:** `curl /pricing` HTTP 200 sin auth
- **CP-S1.4**

#### S1.E3.T3 — CTA de pricing en PublicLayout
- **Archivos:** `resources/js/Layouts/PublicLayout.vue`
- **Cambio:** añadir link "Precios" en nav pública
- **CP-S1.4**

#### S1.E3.T4 — Verificación S1.E3
- `curl /pricing` HTML contiene "From 29€" o similar
- `curl /pricing` sin cookies retorna 200
- **CP-S1.4**

---

### S1.E4 — Skeletons en listados

#### S1.E4.T1 — Crear componente `TableSkeleton.vue` reutilizable
- **Archivos:** crear `resources/js/Components/TableSkeleton.vue`
- **Props:** `rows` (default 5), `columns` (default 4)
- **Estructura:** replica header de tabla + N filas con `Skeleton` bars
- **Criterio:** componente sin props específicas, acepta cualquier estructura
- **CP-S1.5**

#### S1.E4.T2 — Usar TableSkeleton en `Cars/Index.vue`
- **Archivos:** `resources/js/Pages/Cars/Index.vue`
- **Cambio:** detectar loading (Inertia `processing` prop) y renderizar `<TableSkeleton :rows="10" />` antes de la tabla
- **Criterio:** visual, en click-throught se ve esqueleto antes de datos
- **CP-S1.5**

#### S1.E4.T3 — Idem `Clients/Index.vue`
- **CP-S1.5**

#### S1.E4.T4 — Idem `Contacts/Index.vue`
- **CP-S1.5**

#### S1.E4.T5 — Verificación S1.E4
- `php artisan test --filter=Cars` (no rompe tests existentes)
- Visual: navegación a `/cars` muestra skeleton ~150ms antes de datos
- **CP-S1.5**

---

### S1.E5 — i18n en Subscriptions

#### S1.E5.T1 — Auditar strings hardcoded en `Subscriptions/Index.vue`
- **Búsqueda:** textos en inglés ("Subscription actions", "Manage your recurring subscription", "Cancelar suscripción", "Resume subscription")
- **Categorías:** `subscription.actions.*`, `subscription.empty.*`, `subscription.help.*`
- **CP-S1.6**

#### S1.E5.T2 — Mismas claves en `Subscriptions/Show.vue`
- **CP-S1.6**

#### S1.E5.T3 — Añadir claves i18n en es.js y en.js
- **Archivos:** `resources/js/i18n/es.js` + `en.js`
- **Sección:** `subscription.actions.*`, `subscription.features.*`
- **CP-S1.6**

#### S1.E5.T4 — Reemplazar strings en Vue
- **Criterio:** `grep "Resume subscription\|Manage your recurring" resources/js/Pages/Subscriptions/` retorna 0
- **CP-S1.6**

---

### S1.E6 — Limpiar safelist de Tailwind

#### S1.E6.T1 — Auditar `tailwind.config.js` safelist
- **Archivos:** `tailwind.config.js`
- **Conservar:** `estoril-*`, `asphalt-*`, `platinum-*`, `amber-*` (semántico), `emerald-*`/`rose-*` (semántico OK)
- **Eliminar del safelist:** `indigo-*`, `purple-*`, `sky-*`, `blue-*` (excepto casos concretos), `cyan-*`
- **Criterio:** safelist -50% tamaño
- **CP-S1.7**

#### S1.E6.T2 — Verificar que no se usan esas clases en Pages
- **Búsqueda:** `grep -r "indigo-\|purple-\|sky-\|cyan-" resources/js/Pages/`
- Si 0 matches → commit
- Si hay usos → reemplazar por estoril/asphalt/platinum
- **CP-S1.7**

---

### 🎯 Hito D1 — Sprint 1 listo
- `git add -A && git commit -m "feat(sprint-1): PublicLayout + PricingPublic + skeletons + i18n"`
- `git push origin master`
- SSH: `git pull origin master && php artisan optimize:clear`
- **Usuario:** `npm run build` (NO lo lanzo yo)
- Verificar: `curl /pricing` y `/marketplace` retornan 200

---

## S2 — Onboarding + primer valor (1.5 semanas)

### S2.E1 — Tabla `user_onboarding_progress`

#### S2.E1.T1 — Crear migration
- **Comando:** `php artisan make:migration create_user_onboarding_progress_table --no-interaction`
- **Schema:** `id`, `user_id` (FK), `step` (string 32), `completed_at` (timestamp), `created_at`, `updated_at`
- **Index:** `user_id, step`
- **CP-S2.1**

#### S2.E1.T2 — Modelo `UserOnboardingProgress`
- **Comando:** `php artisan make:model UserOnboardingProgress --no-interaction`
- **Fillable:** `user_id`, `step`, `completed_at`
- **Cast:** `completed_at` → datetime
- **Relación:** `belongsTo(User::class)`
- **CP-S2.1**

#### S2.E1.T3 — Migrar local
- `php artisan migrate` (en local, no prod todavía)
- **CP-S2.1**

---

### S2.E2 — OnboardingController

#### S2.E2.T1 — Crear controlador
- **Comando:** `php artisan make:controller OnboardingController --no-interaction`
- **Métodos:**
  - `show()` → renderiza `Onboarding/Wizard` con `current_step` del modelo
  - `completeStep(Request $r)` → marca step y avanza
  - `finish()` → redirige a `/dashboard`
- **Auth:** middleware `auth`
- **CP-S2.2**

#### S2.E2.T2 — Ruta `/onboarding`
- `routes/web.php`: `Route::middleware(['auth', 'has.organization'])->group(...) { Route::get('/onboarding', ...); ... }`
- **CP-S2.2**

#### S2.E2.T3 — Test del controlador
- **Archivo:** `tests/Feature/OnboardingControllerTest.php`
- **Tests:** show renderiza wizard, completeStep persiste, finish redirige
- **Criterio:** 3 tests verde
- **Verificar:** `php artisan test --filter=OnboardingController`
- **CP-S2.2**

---

### S2.E3 — Componente `OnboardingWizard.vue`

#### S2.E3.T1 — Crear componente
- **Archivo:** `resources/js/Pages/Onboarding/Wizard.vue`
- **Estructura:** barra de progreso + 4 pasos (1) empresa, (2) primer vehículo, (3) invitar equipo, (4) plan
- **Marca:** estoril 600 primary, asphalt 800 textos
- **i18n:** claves `onboarding.*`
- **Criterio:** componente funcional, navegación entre pasos, validación inline
- **CP-S2.3**

#### S2.E3.T2 — Conectar con controlador
- **Formularios:** POST a `onboarding.step.{n}` con `useForm()`
- **Criterio:** cada step submit → `completeStep()` → siguiente render
- **CP-S2.3**

#### S2.E3.T3 — Verificación S2.E3
- Test manual: login nuevo → redirige a `/onboarding` → completa 4 pasos → `/dashboard`
- **CP-S2.3**

---

### S2.E4 — OnboardingChecklist.vue en dashboard

#### S2.E4.T1 — Componente persistente
- **Archivo:** `resources/js/Components/OnboardingChecklist.vue`
- **Props:** `progress` (array de steps con done/not done)
- **Estructura:** banner top-of-dashboard con 5 items tachables
- **Criterio:** visible solo si `!allComplete`, dismissable 14 días
- **CP-S2.4**

#### S2.E4.T2 — Compartir progreso via HandleInertiaRequests
- **Archivo:** `app/Http/Middleware/HandleInertiaRequests.php`
- **Cambio:** añadir `onboardingProgress` a `share()` basado en `user_onboarding_progress`
- **CP-S2.4**

#### S2.E4.T3 — Renderizar en `Dashboard.vue`
- **Archivo:** `resources/js/Pages/Dashboard.vue`
- **Cambio:** `<OnboardingChecklist :progress="$page.props.onboardingProgress" />` arriba del header
- **CP-S2.4**

---

### S2.E5 — Empty states con doble CTA

#### S2.E5.T1 — Auditar vacíos en Cars/Index, Clients/Index, Contacts/Index
- **Búsqueda:** cómo se renderiza "no hay coches"
- **CP-S2.5**

#### S2.E5.T2 — Mejorar cada EmptyState con 2 CTAs
- **Ejemplo Cars:** "Importa tu primer CSV" + "Crear vehículo manual"
- **Ejemplo Clients:** "Importar clientes" + "Nuevo cliente"
- **CP-S2.5**

---

### S2.E6 — Emails de bienvenida

#### S2.E6.T1 — Mail `WelcomeMail`
- **Comando:** `php artisan make:mail WelcomeMail --no-interaction`
- **Markup:** Blade `emails/welcome.blade.php` simple
- **CP-S2.6**

#### S2.E6.T2 — Listener `SendWelcomeEmail`
- **Comando:** `php artisan make:listener SendWelcomeEmail --no-interaction`
- **Trigger:** `Registered` event
- **Criterio:** solo si ambiente != testing
- **CP-S2.6**

#### S2.E6.T3 — Markdown email en es + en
- **Archivos:** `resources/views/emails/welcome.blade.php` con `@lang()`
- **CP-S2.6**

---

### S2.E7 — Seeder demo (CUIDADO: solo no-prod)

#### S2.E7.T1 — Crear `DemoDataSeeder.php`
- **Comando:** `php artisan make:seeder DemoDataSeeder --no-interaction`
- **Contenido:** 1 org demo, 5 cars, 3 clients, 2 contacts, 1 user owner
- **Guard:** `if (app()->environment('production')) { $this->command->warn('Skipped in prod'); return; }`
- **Criterio:** triple check `app()->environment('production')` antes de aplicar
- **CP-S2.7**

#### S2.E7.T2 — Botón "Cargar demo" en welcome o first login
- **Endpoint:** `POST /onboarding/demo` (solo desarrollo)
- **Test:** manual local con `APP_ENV=local`
- **CP-S2.7**

---

### 🎯 Hito D2 — Sprint 2 listo
- Backup prod antes de migration
- SSH: `php artisan migrate --force`
- Push + pull + clear caches
- **Usuario:** `npm run build`
- Verificar: nuevo user pasa por `/onboarding`

---

## S3 — Dark mode + accesibilidad (1.5 semanas)

### S3.E1 — Auditar y aplicar `dark:` variants

#### S3.E1.T1 — Crear PageHeader dark variant
- **Archivo:** `resources/js/Components/PageHeader.vue`
- Cambiar `text-gray-800` → `text-gray-800 dark:text-white`
- Cambiar `bg-white` → `bg-white dark:bg-asphalt-800`
- **CP-S3.1**

#### S3.E1.T2 — Idem `AuthenticatedLayout.vue`
- **CP-S3.1**

#### S3.E1.T3 — Idem `EmptyState.vue`
- **CP-S3.1**

#### S3.E1.T4 — Idem `SidebarGroup.vue`
- **CP-S3.1**

#### S3.E1.T5 — Idem `UpgradeBanner.vue`
- **CP-S3.1**

#### S3.E1.T6 — Auditar todas las Pages y aplicar dark: variants
- **Búsqueda:** `find resources/js/Pages -name "*.vue" | xargs grep -L "dark:" | head -50`
- Aplicar a cada uno
- **Criterio:** 100% Pages tienen al menos `.dark:` en container principal
- **CP-S3.2** (puede ser muchos CP si se agrupa por dominio)

#### S3.E1.T7 — Verificación S3.E1
- Visual: toggle dark mode → todas las pages se ven correctamente
- **CP-S3.2**

---

### S3.E2 — Eliminar `tailwind.config.js` v3 (CON CUIDADO)

#### S3.E2.T1 — Verificar coexistencia
- **Búsqueda:** `grep -r "darkMode" tailwind.config.js css/app.css`
- **Criterio:** si v4 CSS tiene `@custom-variant dark`, v3 está inerte
- **CP-S3.3**

#### S3.E2.T2 — Backup y borrar config
- **Backup:** `cp tailwind.config.js tailwind.config.js.bak`
- `rm tailwind.config.js`
- **CP-S3.3**

#### S3.E2.T3 — Tests
- `php artisan test --compact` (no rompe nada)
- **Usuario:** `npm run build` (verificar que no falla)
- Si falla → restaurar backup
- **CP-S3.3**

---

### S3.E3 — `@vueuse/motion` para microinteracciones (PREGUNTAR ANTES)

#### S3.E3.T1 — Preguntar a usuario antes de instalar
- **Regla del proyecto:** "Nunca añadir dependencias (composer require / npm install) sin preguntar"
- **Mensaje:** "¿Instalo @vueuse/motion (8KB) para microinteracciones? Fallback: CSS transitions ya definidas."
- **CP-S3.4** (solo si aprueba)

#### S3.E3.T2 — Si aprueba: instalar
- **Comando (usuario):** `npm install @vueuse/motion`
- **Yo:** crear `resources/js/Composables/useMotion.js`
- **CP-S3.4**

#### S3.E3.T3 — Usar en SidebarGroup collapse
- **Archivo:** `resources/js/Components/SidebarGroup.vue`
- **Cambio:** envolver children en `<motion.div>`
- **CP-S3.4**

---

### S3.E4 — Accesibilidad WCAG AA

#### S3.E4.T1 — Auditoría contraste
- **Tool:** Lighthouse CI o manual
- **Criterio:** body text contrast ≥ 4.5:1, large text ≥ 3:1
- **Fixes:** cambiar colores hardcoded fuera de paleta
- **CP-S3.5**

#### S3.E4.T2 — Focus visible global
- **CSS:** `@layer base { :focus-visible { @apply ring-2 ring-estoril-500 outline-none; } }` en `app.css`
- **CP-S3.5**

#### S3.E4.T3 — Keyboard nav sidebar
- **Archivo:** `SidebarGroup.vue`
- **Criterio:** `tabindex`, `aria-expanded`, `role="button"`
- **CP-S3.5**

#### S3.E4.T4 — ARIA labels en iconos solo
- **Búsqueda:** iconos sin texto (close, hamburger, etc.)
- **Cambio:** añadir `aria-label`
- **CP-S3.5**

---

### S3.E5 — Inertia WhenVisible + deferred props

#### S3.E5.T1 — Refactorizar `Cars/Index.vue` con deferred props
- **Archivo:** `app/Http/Controllers/CarController.php`
- **Cambio:** `Inertia::render('Cars/Index', [..., 'stats' => fn() => ...])` (deferred)
- **Archivo:** `resources/js/Pages/Cars/Index.vue`
- **Uso:** `Deferred` con fallback skeleton
- **CP-S3.6**

#### S3.E5.T2 — Idem `Clients/Index.vue`
- **CP-S3.6**

#### S3.E5.T3 — Idem `Contacts/Index.vue`
- **CP-S3.6**

---

### 🎯 Hito D3 — Sprint 3 listo
- Push + pull + clear caches
- **Usuario:** `npm run build`
- Visual: dark mode completo, Lighthouse > 90

---

## S4 — Performance + DX (1 semana)

### S4.E1 — Vite `manualChunks`

#### S4.E1.T1 — Modificar `vite.config.js`
- **Archivo:** `vite.config.js`
- **Añadir:**
  ```js
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', '@inertiajs/vue3', 'ziggy-js'],
          heroicons: ['@heroicons/vue/24/outline'],
        }
      }
    }
  }
  ```
- **Importante:** NO lanzo `npm run build` (lo hace el usuario)
- **CP-S4.1**

#### S4.E1.T2 — Verificación
- **Usuario:** `npm run build` → revisar output para confirmar chunks separados
- **Criterio:** `vendor-*.js` y `heroicons-*.js` chunks separados
- **CP-S4.1**

---

### S4.E2 — Inertia prefetching

#### S4.E2.T1 — Añadir prefetch on hover en Sidebar
- **Archivo:** `resources/js/Components/SidebarGroup.vue`
- **Cambio:** `<Link :href="route('cars.index')" prefetch="hover">`
- **CP-S4.2**

#### S4.E2.T2 — Idem en nav pública
- **Archivo:** `resources/js/Layouts/PublicLayout.vue`
- **CP-S4.2**

#### S4.E2.T3 — Verificación
- DevTools network tab: hover en link → request preflight
- **CP-S4.2**

---

### S4.E3 — Preload en `app.blade.php`

#### S4.E3.T1 — Añadir `<link rel="modulepreload">`
- **Archivo:** `resources/views/app.blade.php`
- **Cambio:** para `app.js` y chunks principales
- **CP-S4.3**

#### S4.E3.T2 — Verificar con curl
- `curl -s / | grep modulepreload` debe listar referencias
- **CP-S4.3**

---

### S4.E4 — Lazy load Heroicons por categoría

#### S4.E4.T1 — Categorizar imports
- **Búsqueda:** `find resources/js -name "*.vue" -exec grep -h "@heroicons" {} \;`
- **Cambio:** importar desde `@heroicons/vue/24/outline` agrupados
- **Criterio:** tree-shaking más efectivo
- **CP-S4.4**

#### S4.E4.T2 — Verificar bundle
- **Usuario:** `npm run build` → revisar `dist/assets/*.js` totales
- **Target:** < 250KB gzipped total
- **CP-S4.4**

---

### S4.E5 — Compresión brotli

#### S4.E5.T1 — Configurar nginx (vía Forge panel)
- **NO toco nginx directamente** (es server config)
- **Documentar:** `docs/PLAN_IMPLEMENTACION_5_SPRINTS.md` paso para usuario
- **CP-S4.5**

---

### 🎯 Hito D4 — Sprint 4 listo
- Push + pull + clear caches
- **Usuario:** `npm run build`
- **Usuario:** activar brotli en Forge panel
- Verificar: `time curl https://jjimportmotors.on-forge.com/marketplace` < 1s

---

## S5 — Billing UX + dunning (1 semana)

### S5.E1 — Tabla comparativa de planes

#### S5.E1.T1 — Componente `PlanComparisonTable.vue`
- **Archivo:** `resources/js/Components/PlanComparisonTable.vue`
- **Filas:** features cualitativas (cars_limit, clients_limit, contacts_limit, AI verification, kanban, mapa, valoración, etc.)
- **Columnas:** Starter / Pro / Enterprise
- **Marca:** estoril en "Most popular"
- **CP-S5.1**

#### S5.E1.T2 — Renderizar en `Subscriptions/Index.vue`
- **Cambio:** bajo el grid de cards, tabla de features
- **CP-S5.1**

#### S5.E1.T3 — Idem en `Public/PricingPublic.vue`
- **CP-S5.1**

---

### S5.E2 — Toggle mensual/anual

#### S5.E2.T1 — Estado y cálculo
- **Archivo:** `resources/js/Composables/usePricingPeriod.js`
- **Función:** toggle entre `monthly` y `yearly`, descuento 20% anual
- **CP-S5.2**

#### S5.E2.T2 — UI toggle
- **Archivos:** `Subscriptions/Index.vue`, `PricingPublic.vue`
- **Estructura:** segmented control "Mensual | Anual (-20%)"
- **CP-S5.2**

#### S5.E2.T3 — Config planes
- **Archivo:** `config/subscription.php`
- **Cambio:** añadir `period: 'month'` y `yearly_discount: 20` por plan
- **CP-S5.2**

---

### S5.E3 — Banner dunning en dashboard

#### S5.E3.T1 — Componente `DunningBanner.vue`
- **Archivo:** `resources/js/Components/DunningBanner.vue`
- **Props:** `payment_failed_at`, `grace_days_remaining`
- **Estructura:** banner rosa urgente con CTA
- **CP-S5.3**

#### S5.E3.T2 — Renderizar en `Dashboard.vue`
- **Cambio:** detectar `payment_failed_at` y mostrar
- **CP-S5.3**

#### S5.E3.T3 — Cálculo días restantes
- **Lógica:** `grace_days - (now - payment_failed_at)->days`
- **CP-S5.3**

---

### S5.E4 — Emails transaccionales

#### S5.E4.T1 — `TrialEndingMail`
- **Trigger:** webhook `customer.subscription.trial_will_end` (ya existe handler)
- **Markup:** `emails/trial-ending.blade.php`
- **CP-S5.4**

#### S5.E4.T2 — `PaymentFailedMail`
- **Trigger:** webhook `invoice.payment_failed` (ya existe handler)
- **CP-S5.4**

#### S5.E4.T3 — `SubscriptionReactivatedMail`
- **Trigger:** webhook `customer.subscription.updated` con status `active`
- **CP-S5.4**

#### S5.E4.T4 — Tests
- Envío en tests con `Mail::fake()`
- **Verificar:** `php artisan test --filter=Mail`
- **CP-S5.4**

---

### S5.E5 — Página `/billing/cancel-explained`

#### S5.E5.T1 — Crear `Billing/CancelExplained.vue`
- **Archivo:** `resources/js/Pages/Billing/CancelExplained.vue`
- **Contenido:** qué pierdes, datos conservados 90 días, export CSV gratis
- **URL:** `/billing/cancel-explained` (NO `/billing/cancel` para no romper acción destructiva)
- **CP-S5.5**

#### S5.E5.T2 — Ruta
- **Cambio:** `routes/web.php` añadir ruta dentro del grupo auth
- **CP-S5.5**

#### S5.E5.T3 — Link desde botón cancelar
- **Archivo:** `resources/js/Pages/Subscriptions/Index.vue`
- **Cambio:** botón "Cancelar" → `/billing/cancel-explained` (vista previa)
- **CP-S5.5**

---

### S5.E6 — UpgradePrompt contextual

#### S5.E6.T1 — Componente `UpgradePrompt.vue`
- **Archivo:** `resources/js/Components/UpgradePrompt.vue`
- **Props:** `resource` (cars|clients|contacts), `current`, `limit`, `percentage`
- **Estructura:** modal inline con CTA a `/subscriptions`
- **CP-S5.6**

#### S5.E6.T2 — Disparar en 90% desde UpgradeBanner
- **Archivo:** `resources/js/Components/UpgradeBanner.vue`
- **Cambio:** si `percentage >= 90`, mostrar `UpgradePrompt` en lugar de solo banner
- **CP-S5.6**

---

### 🎯 Hito D5 — Sprint 5 + fin del programa
- Push + pull + clear caches
- **Usuario:** `npm run build`
- Verificar: trial→paid funnel completo, dunning emails envían

---

## Métricas objetivo (6 meses)

| Métrica | Baseline | Target |
|---|---|---|
| TTV (D0) | n/a | < 10 min |
| Activation (D7) | 0% | > 60% |
| Trial→paid | 0% | > 25% |
| Churn mensual | n/a | < 5% |
| LCP mobile | n/a | < 2.5s |
| TTI desktop | n/a | < 1.5s |
| Bundle size | n/a | < 250 KB gz |
| Dark mode adoption | 0% | > 30% |
| Lighthouse | n/a | > 90 |
| i18n coverage | ~70% | 100% |

---

## Anti-overengineering (NO se hace)

- ❌ CMS headless (Blade/Inertia basta)
- ❌ A/B testing desde día 1 (esperar >1000 visits/mes)
- ❌ Usage-based billing real (no hay demanda)
- ❌ Migrar a Paddle/Lemon Squeezy (Stripe ya cobra IVA UE)
- ❌ PWA / Service Worker (no es core)
- ❌ Wizard > 5 pasos (abandono > 50%)
- ❌ AI suggest desde día 1 (sin datos, malinterpreta)

---

## Riesgos y rollback

| Sprint | Riesgo | Rollback |
|---|---|---|
| S1 | PublicLayout rompe navegación | `git revert` del CP-S1.2 |
| S2 | Migration falla en prod | `php artisan migrate:rollback` + restaurar backup |
| S3 | Dark mode CSS rompe paleta | restaurar `tailwind.config.js` desde backup |
| S4 | manualChunks no separa | revertir `vite.config.js` |
| S5 | Dunning banners falsos positivos | ajustar threshold |

---

## Comandos finales del programa

```bash
# Tras cada sprint
git push origin master
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" -o StrictHostKeyChecking=no forge@168.144.6.105 "cd /home/forge/jjimportmotors.on-forge.com/current && git pull origin master && php artisan optimize:clear"
# IMPORTANTE: el usuario lanza npm run build después de cada sprint
```

---

## Siguiente paso concreto

**S1.E1.T1 — Crear `PublicLayout.vue`** (primer commit point).

Arranco cuando confirmes.