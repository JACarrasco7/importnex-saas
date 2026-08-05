# Arquitectura de Vistas — Público / Privado / Auth

> Última auditoría: **2026-08-05** · JJ Import Motors (ImportnexCore)
> Stack: Laravel 11 + Inertia.js 2 + Vue 3 + Tailwind 4 + Ziggy

Este documento describe cómo se reparte la aplicación entre **parte pública** (visitantes anónimos) y **parte privada** (panel autenticado), qué rutas las sirven, qué layouts usan, y cómo navega el usuario entre ambas.

---

## 1. Principios rectores

| # | Principio |
|---|---|
| 1 | **`/` siempre apunta a la parte pública.** El primer impacto del visitante es el marketplace, no el formulario de login. |
| 2 | **`/admin` es la puerta privada sin autenticación.** Equivale a la antigua landing de Laravel: muestra CTAs a login/registro y el nombre de la organización. |
| 3 | **Las páginas autenticadas viven tras `auth + verified + organization`.** Nadie sin sesión llega a `/dashboard`, `/cars`, etc. |
| 4 | **El logout siempre termina en `/admin`.** Al cerrar sesión el usuario aterriza en la landing privada, no en el marketplace público. |
| 5 | **El admin tiene un enlace "Marketplace" en su sidebar** que abre la parte pública en pestaña nueva (`target="_blank"`). No se mezcla contexto. |
| 6 | **La parte pública no expone UI de admin** salvo un enlace discreto "Acceder al panel" en el footer del marketplace. |

---

## 2. Mapa de rutas

### 2.1 Rutas públicas (sin middleware)

| URL | Nombre | Controlador | Vista Inertia |
|---|---|---|---|
| `/` | (ninguno) | cierre en `web.php` → `redirect('/marketplace')` | — |
| `/marketplace` | `marketplace.index` | `PublicMarketplaceController@index` | `Public/MarketplaceIndex.vue` |
| `/marketplace/{car}` | `marketplace.show` | `PublicMarketplaceController@show` | `Public/MarketplaceShow.vue` |
| `/request/{slug}` | `public.car-request.index` | `PublicCarRequestController@index` | `Public/CarRequestForm.vue` |
| `/request/{slug}` (POST) | `public.car-request.store` | `PublicCarRequestController@store` (throttle `5,10`) | — |
| `/request/{slug}/success` | `public.car-request.success` | `PublicCarRequestController@success` | `Public/CarRequestSuccess.vue` |
| `/admin` | `admin` | cierre en `web.php` | `Welcome.vue` (con `organizationName`) |
| `/login` | `login` | `Auth\AuthenticatedSessionController@create` | `Auth/Login.vue` |
| `/register` | `register` | `Auth\RegisteredUserController@create` | `Auth/Register.vue` |
| `/forgot-password` | `password.request` | `Auth\PasswordResetLinkController@create` | `Auth/ForgotPassword.vue` |
| `/reset-password/{token}` | `password.reset` | `Auth\NewPasswordController@create` | `Auth/ResetPassword.vue` |
| `/verify-email` | `verification.notice` | `Auth\VerifyEmailController` | `Auth/VerifyEmail.vue` |
| `/jj-import-folleto.pdf` | (asset) | cierre → asset en `public/` | PDF estático |
| `/stripe/webhook` (POST) | (ninguno) | `StripeWebhookController` | — (fuera de `web` middleware) |

### 2.2 Rutas autenticadas (middleware `auth + verified + organization`)

Todas cuelgan del grupo en [routes/web.php](../routes/web.php). Resumen:

| Prefijo | Names | Vista principal |
|---|---|---|
| `/dashboard` | `dashboard` | `Dashboard.vue` |
| `/profile` | `profile.*` | `Profile/Edit.vue` |
| `/organization/{id}` + `/edit` | `organization.*` | `Organization/Show.vue`, `Edit.vue` |
| `/cars` | `cars.*` | `Cars/Index.vue`, `Show.vue`, `Edit.vue`, `Create.vue`, `Kanban.vue`, `Map.vue` |
| `/cars/{car}/verify` | `cars.verify.*` | `Cars/Verify.vue` |
| `/cars/import-valuation` | `cars.import-valuation.*` | `Cars/ImportValuation.vue` |
| `/cars/{car}/checklists/{checklist}/toggle` | `cars.checklists.toggle` | (acción POST) |
| `/cars/{car}/photos` | `cars.photos.*` | (acciones) |
| `/cars/{car}/documents` | `cars.documents.*` | (acciones) |
| `/finance` | `finance.index` | `Finance/Index.vue` |
| `/trips` | `trips.index` | `Trips/Index.vue` |
| `/clients` | `clients.*` | `Clients/Index.vue`, `Show.vue` |
| `/contacts` | `contacts.*` | `Contacts/Index.vue` |
| `/car-requests` | `car-requests.*` | `CarRequests/Index.vue`, `Show.vue` |
| `/message-templates` | `message-templates.index` | `MessageTemplates/Index.vue` |
| `/alerts` | `alerts.index` | `Alerts/Index.vue` |
| `/marketing` | `marketing.index` | `Marketing/Index.vue` |
| `/ai/chat` | `ai.chat` | `AI/Chat.vue` |
| `/subscriptions` | `subscriptions.index` | `Subscriptions/Index.vue` |
| `/billing` | `billing.index` | `Billing/Index.vue` |
| `/logout` (POST) | `logout` | `Auth\AuthenticatedSessionController@destroy` → redirige a `/admin` |

### 2.3 Rutas de bootstrap (middleware `auth + has.organization`)

Solo se muestran si el usuario está autenticado pero no pertenece a ninguna organización todavía:

| URL | Nombre | Vista |
|---|---|---|
| `/organization/create` | `organization.create` | `Organization/Create.vue` |
| `/organization` (POST) | `organization.store` | — |

---

## 3. Layouts y componentes globales

### 3.1 Layouts principales

```
resources/js/Layouts/
├── AuthenticatedLayout.vue   ← usado por todas las páginas bajo auth
├── GuestLayout.vue           ← login / register / password reset
└── (otros layouts puntuales por página)
```

| Layout | Aplicado a | Notas |
|---|---|---|
| `AuthenticatedLayout.vue` | Dashboard, Cars, Clients, CarRequests, etc. | Sidebar izquierda + topbar. Color **estoril** para estado activo, **asphalt/platinum** para neutros. El sidebar se alimenta de `navGroups` (computed reactivo a `pending_alerts_count`, `pending_car_requests_count`). |
| `GuestLayout.vue` | Auth/Login, Auth/Register, Auth/ForgotPassword | Centrado, fondo estoril-50, logo arriba. |
| (sin layout, HTML propio) | `Public/MarketplaceIndex.vue`, `Public/MarketplaceShow.vue`, `Public/CarRequestForm.vue`, `Public/CarRequestSuccess.vue`, `Welcome.vue` (en `/admin`) | Cada vista pública lleva su propio header/footer porque Inertia renderiza la página completa sin un layout compartido. |

### 3.2 Componentes globales

| Componente | Ubicación | Usado por |
|---|---|---|
| `ApplicationLogo.vue` | `Components/` | AuthenticatedLayout (sidebar), emails |
| `FlashMessage.vue` | `Components/` | AuthenticatedLayout (topbar muestra `flash.success` / `flash.error`) |
| `SidebarGroup.vue` | `Components/` | AuthenticatedLayout (sidebar) |
| `LocaleSelector.vue` | `Components/` | AuthenticatedLayout (topbar) |
| `UpgradeBanner.vue` | `Components/` | AuthenticatedLayout (banner plan) |
| `StatCard.vue` | `Components/` | Dashboard |
| `Badge.vue` | `Components/` | Listas (Cars, Clients, CarRequests) |
| `useTranslations.js` | `Composables/` | Todas las vistas |
| `useFormat.js` | `Composables/` | AuthenticatedLayout (initials), Dashboard (formatos) |

---

## 4. Estructura de `resources/js/Pages`

```
Pages/
├── Welcome.vue                       ← landing privada en /admin
├── Auth/
│   ├── Login.vue
│   ├── Register.vue
│   ├── ForgotPassword.vue
│   ├── ResetPassword.vue
│   └── VerifyEmail.vue
├── Organization/
│   ├── Create.vue                    ← bootstrap (sin org)
│   ├── Show.vue
│   └── Edit.vue
├── Dashboard.vue
├── Cars/
│   ├── Index.vue
│   ├── Show.vue
│   ├── Edit.vue
│   ├── Create.vue
│   ├── Kanban.vue
│   ├── Map.vue
│   ├── Verify.vue
│   └── ImportValuation.vue
├── Finance/
│   └── Index.vue
├── Trips/
│   └── Index.vue
├── Clients/
│   ├── Index.vue
│   └── Show.vue
├── Contacts/
│   └── Index.vue
├── CarRequests/
│   ├── Index.vue
│   └── Show.vue
├── MessageTemplates/
│   └── Index.vue
├── Alerts/
│   └── Index.vue
├── Marketing/
│   └── Index.vue
├── AI/
│   └── Chat.vue
├── Profile/
│   └── Edit.vue
├── Subscriptions/
│   └── Index.vue
├── Billing/
│   └── Index.vue
├── Public/                           ← vistas sin autenticación
│   ├── MarketplaceIndex.vue
│   ├── MarketplaceShow.vue
│   ├── CarRequestForm.vue
│   └── CarRequestSuccess.vue
└── Error/
    └── NotFound.vue (si existe)
```

> **Regla mnemotécnica:** todo lo que está bajo `Public/` se sirve sin middleware y no debe colgar nada de `auth`, `usePage().props.auth.user` (excepto para mostrar un enlace sutil al admin). El resto de páginas asume `auth.user` siempre presente.

---

## 5. Layouts visuales por superficie

### 5.1 Marketplace público (`/marketplace`)

```
┌──────────────────────────────────────────────────────────────┐
│  Header sticky: Logo (Importnex) + nav (#catálogo #cómo) +   │
│  CTA "Contactar"                                              │
├──────────────────────────────────────────────────────────────┤
│  Hero gradient estoril-100 → estoril-50 → platinum-200        │
│  + blobs decorativos + dot grid                              │
│  "Tu próximo coche, ya verificado"                            │
│  CTAs: Ver coches disponibles · Habla con un asesor           │
│  Stats: 80% / 3x / 24/7                                       │
├──────────────────────────────────────────────────────────────┤
│  Sección "Cómo funciona" 3 pasos                              │
├──────────────────────────────────────────────────────────────┤
│  Filtros: search · presupuesto · km · marca chips + chip      │
│  "Muy buen precio"                                            │
├──────────────────────────────────────────────────────────────┤
│  Grid de coches premium (cards estoril con sombra)            │
│  Badge "Ahorro estimado" en cards con `estimated_saving > 0`  │
├──────────────────────────────────────────────────────────────┤
│  Sección "Cómo importar" (3 pasos)                            │
├──────────────────────────────────────────────────────────────┤
│  CTA final + contactos (WhatsApp, tel, email, folleto PDF)    │
├──────────────────────────────────────────────────────────────┤
│  Footer: Logo + "Acceder al panel" (→ /admin) + © year       │
└──────────────────────────────────────────────────────────────┘
```

Botón flotante fijo abajo-derecha: descarga del PDF folleto.

### 5.2 Formulario público (`/request/{slug}`)

```
┌──────────────────────────────────────────────────────────────┐
│  Header con logo + nombre de la organización                  │
│  Subtítulo "Encuentramos tu coche perfecto."                  │
├──────────────────────────────────────────────────────────────┤
│  Card blanca con secciones:                                   │
│   - Tus datos (nombre*, email, teléfono)                      │
│   - El coche que buscas (marca, modelo, año min/max,          │
│     presupuesto min/max, km máx, potencia min/max, motor)     │
│   - Preferencias técnicas (combustible, cambio, carrocería,   │
│     puertas, plazas, color)                                   │
│   - Información adicional (requisitos, notas)                 │
│   - Honeypot oculto `website` (anti-bots)                     │
│  CTA: "Enviar solicitud"                                      │
├──────────────────────────────────────────────────────────────┤
│  Footer con © year                                             │
└──────────────────────────────────────────────────────────────┘
```

Asterisco rojo `*` solo aparece en `Nombre` (único campo obligatorio en backend, `required` HTML + `required` Laravel).

### 5.3 Landing privada (`/admin`)

Renderiza `Welcome.vue` (la antigua landing de Laravel). Equivalente a una home de marketing orientada a "ya eres cliente, entra al panel":

- **Si NO autenticado:** botones "Iniciar sesión" + "Crear cuenta"
- **Si autenticado:** botón directo "Ir al panel" → `/dashboard`
- Logo → `route('admin')` (autorreferente, queda en `/admin`)
- Hero con CTA "Ver Marketplace" → `/marketplace`
- Sección de features (6 cards)
- CTA final "Empieza tu prueba"
- Footer con ©

### 5.4 Panel autenticado (`/dashboard`, `/cars`, etc.)

Layout `AuthenticatedLayout.vue`:

```
┌─────────────┬────────────────────────────────────────────────┐
│  Sidebar    │  Topbar: hamburger (móvil) · slot header        │
│  ┌───────┐  │           · LocaleSelector · 🔔 alertas        │
│  │Resumen│  │           · avatar + menú (Profile, Org,       │
│  │ Dash  │  │              Logout → /admin)                   │
│  │ Market│  ├────────────────────────────────────────────────┤
│  └───────┘  │  Flash messages (success / error)               │
│  ┌───────┐  ├────────────────────────────────────────────────┤
│  │Inventa│  │                                                │
│  │ Autos │  │  Contenido de la vista                         │
│  │ Kanba │  │                                                │
│  │ Mapa  │  │                                                │
│  │ Finan │  │                                                │
│  │ Viajes│  │                                                │
│  │ Market│  │                                                │
│  │  ↗   │  │                                                │
│  └───────┘  │                                                │
│  ┌───────┐  │                                                │
│  │  CRM  │  │                                                │
│  │Client.│  │                                                │
│  │Contac.│  │                                                │
│  │Solicit│  │                                                │
│  │Plantil│  │                                                │
│  │Alertas│  │                                                │
│  └───────┘  │                                                │
│  ┌───────┐  │                                                │
│  │Market.│  │                                                │
│  │Centro │  │                                                │
│  └───────┘  │                                                │
│  ┌───────┐  │                                                │
│  │Cuenta │  │                                                │
│  │AI Chat│  │                                                │
│  │ Plan  │  │                                                │
│  │Factur.│  │                                                │
│  │ Organ.│  │                                                │
│  └───────┘  │                                                │
│             │                                                │
│  Banner     │                                                │
│  "Upgrade"  │                                                │
└─────────────┴────────────────────────────────────────────────┘
```

---

## 6. Internacionalización (i18n)

Todas las vistas usan el composable `useTranslations` ([resources/js/Composables/useTranslations.js](../resources/js/Composables/useTranslations.js)) que carga perezosamente los diccionarios:

```
resources/js/i18n/
├── es.js   ← cargado por defecto (locale 'es')
└── en.js
```

Convenciones:

- Las claves son paths en dot-notation: `t('car_requests.status.pending')`
- `useTranslations()` devuelve `{ t, locale }`. `t()` acepta un segundo argumento para replacements: `t('marketplace.footer_copy').replace(':year', new Date().getFullYear())`.
- Cuando se añade una clave nueva, hay que añadirla en **ambos** archivos (`es.js` + `en.js`).

Cuando se añade un campo nuevo a un formulario público o a un mensaje flash, el patrón es:

1. Backend valida y devuelve mensaje en español.
2. Frontend pinta `form.errors.<campo>` debajo del input.
3. Si el campo es opcional no lleva asterisco. Si es obligatorio lleva `<span class="ml-0.5 text-rose-600">*</span>` justo después de la label.
4. El placeholder va en `t('xxx.placeholder_yyy')`.

---

## 7. Estilo visual y marca

Documento principal: [docs/BRAND.md](../docs/BRAND.md).

Reglas que se respetan en todas las vistas:

- **Paleta:** `estoril` (primario, navy #1A306D), `asphalt` (neutro, #38393D), `platinum` (acento, #BEC0C3).
- **Estados activos del sidebar:** fondo `bg-estoril-50`, texto `text-estoril-700`, icono `text-estoril-600`.
- **Badges:** `bg-rose-500` para alertas pendientes, `bg-estoril-500` para solicitudes pendientes.
- **Sombras:** `shadow-sm` por defecto en cards, `shadow-xl` en contenedores principales.
- **Tipografía:** `font-bold` para títulos principales, `font-semibold` para subtítulos, `font-medium` para labels.
- **No usar indigo/purple** (legado): la tarjeta "Need more features" del sidebar aún los usa por motivos históricos; pendiente de migrar.

---

## 8. Flujos de navegación

### 8.1 Visitante nuevo (anónimo)

```
/                → 302 → /marketplace
/marketplace     → ve coches, filtros, CTAs
  ├─ Click coche → /marketplace/{car}
  ├─ "Pedir que te avisen" → /request/{slug} (formulario)
  │    └─ POST → 302 → /request/{slug}/success
  └─ Footer "Acceder al panel" → /admin (landing privada)
       └─ Click "Iniciar sesión" → /login
            └─ POST → /dashboard
```

### 8.2 Usuario autenticado entrando por URL

```
/ (escribe)      → 302 → /marketplace (consistente con anónimos)
/admin           → 200 (Welcome.vue) sin requerir auth; si ya estás logueado el header muestra "Ir al panel"
/dashboard       → requiere auth; redirige a /login si no hay sesión
/dashboard       (autenticado) → muestra sidebar + topbar + contenido
```

### 8.3 Logout

```
Click "Log out" (topbar)
  → POST /logout
  → Auth\AuthenticatedSessionController@destroy
  → session invalidada + token regenerado
  → 302 → /admin   (NO a /, NO a /marketplace)
```

### 8.4 Cruce público ↔ admin

- **Admin quiere ver la parte pública:** sidebar → "Marketplace" (item con `external: true` → `target="_blank"`, pestaña nueva).
- **Anónimo quiere entrar al admin:** marketplace footer → "Acceder al panel" → `/admin` → "Iniciar sesión" → `/dashboard`.

---

## 9. Datos compartidos vía Inertia

El middleware `HandleInertiaRequests` (configurado en `app/Http/Middleware/HandleInertiaRequests.php`) propaga a **todas** las respuestas Inertia las claves:

| Clave | Tipo | Origen | Notas |
|---|---|---|---|
| `auth.user` | `object \| null` | `Auth::user()` con relaciones `organization` | Usado para mostrar nombre, plan, permisos |
| `csrfToken` | `string` | `csrf_token()` | Inertia lo usa en POST/PATCH/DELETE |
| `flash.success` / `flash.error` | `string \| null` | `session()->flash()` | Mostrados como `FlashMessage` |
| `errors` | `object` | `session('errors')` | Errores de validación Laravel |
| `pending_alerts_count` | `int` | Computado en middleware | Badge sidebar |
| `pending_car_requests_count` | `int` | Computado en middleware | Badge sidebar |
| `planUsage` | `object` | `SubscriptionHelper` | { cars, clients, contacts } con `current/limit/available/percentage/reached` |
| `currentPlan` | `object` | `SubscriptionHelper` | { name, has_active_subscription } |
| `locale` | `string` | `app()->getLocale()` | Usado por composables |
| `aiSettings` | `object` | `Organization.ai_provider/_model/_has_key` | Para UI de AI chat |

Vistas específicas pueden añadir props adicionales en su controlador (p. ej. `PublicMarketplaceController@index` añade `requestUrl`, `cars`, `brands`, `filters`).

---

## 10. Anti-bots y throttling

Solo se aplica a rutas públicas de envío:

| Ruta | Protección |
|---|---|
| `POST /request/{slug}` (store) | Laravel `throttle:5,10` (5 envíos cada 10 min por IP) + honeypot `website` (campo oculto; si viene relleno, rechazo silencioso) + double-submit guard en frontend (`submitting.value` ref). |
| `POST /cars/scrape-url` | `throttle:30,1` (30 por minuto). |

---

## 11. Checklist al añadir una vista nueva

- [ ] ¿Es pública? → va en `resources/js/Pages/Public/` y no usa `auth.user`.
- [ ] ¿Es autenticada? → va en `resources/js/Pages/<Módulo>/` y hereda `AuthenticatedLayout.vue` con `<AuthenticatedLayout>...contenido...</AuthenticatedLayout>`.
- [ ] ¿Bootstrap? → ruta protegida con `auth + has.organization` (no `verified` ni `organization`).
- [ ] ¿Traducciones? → añadir clave en `es.js` **y** `en.js`. Si la clave referencia `:placeholder`, añadir `placeholder_yyy`.
- [ ] ¿Backend valida un campo nuevo? → mensaje de error en español dentro del closure del validador.
- [ ] ¿Campo obligatorio? → añadir asterisco rojo en la label.
- [ ] ¿Color? → usa `estoril-*`, `asphalt-*`, `platinum-*`. No indigo/purple.
- [ ] ¿Está el `route()` disponible? → añadir en `routes/web.php` con el `name` correspondiente. Si usas `route('xxx.yyy')` en JS, Ziggy lo recoge automáticamente en build.

---

## 12. Cómo regenerar los assets

Tras cambiar rutas, props, composables o componentes globales:

```bash
npm run build
```

Vite genera los bundles en `public/build/assets/` con hashes. Forge los sirve desde ahí. **Importante:** no ejecutar `npm run build` manualmente en el servidor Forge (causa OOM); dejar que Forge lo haga en su pipeline de deploy.

Para forzar cache-refresh de un bundle específico (ej. tras cambiar `useTranslations.js`), basta con pushear — Vite le asigna un hash nuevo y el navegador lo descarga.

---

## 13. Resumen rápido (TL;DR)

| Si quieres... | Ve a... |
|---|---|
| Ver el marketplace público | `/marketplace` (sin auth) |
| Ver la landing privada | `/admin` (sin auth) |
| Entrar al panel | `/login` → `/dashboard` |
| Salir del panel | botón logout → vuelve a `/admin` |
| Salto rápido a público desde admin | sidebar "Marketplace" (pestaña nueva) |
| Salto rápido a admin desde público | footer "Acceder al panel" |

---

**Mantenedor:** ver [docs/BRAND.md](../docs/BRAND.md) para paleta, [docs/PLAN_MARKETPLACE.md](../docs/PLAN_MARKETPLACE.md) para lógica del marketplace, [docs/deploy/README.md](deploy/README.md) para despliegue.
