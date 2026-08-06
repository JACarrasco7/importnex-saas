# Organización por Sesiones — ImportnexCore 2026-08-06

## 🎯 Principio de diseño

**Cada sesión trabaja en un dominio distinto sin solapamiento.**
- ✅ Máxima claridad de responsabilidades
- ✅ Minimizar conflictos en `git`
- ✅ Paralelización posible (sesiones A y B pueden trabajar simultáneamente)
- ✅ Entregables claros por sesión

---

## 📋 Asignación de Sprints por Sesión

### Session A — Fundamentos Públicos (SEO + Estructura) ✅ **COMPLETADO 2026-08-06**
**Duración:** ~14 horas (2 días)
**Prioridad:** ⭐⭐⭐ Máxima (bloquea el resto)

| Sprint Item | Esfuerzo | Estado | Archivos clave |
|---|---|---|---|
| 1.1 `PublicLayout.vue` (header + footer + nav) | 2h | ✅ | `resources/js/Layouts/PublicLayout.vue` (ya existía) |
| 1.2 Migrar `MarketplaceIndex`, `MarketplaceShow`, `CarRequestForm` | 2h | ✅ | 3 páginas (ya usaban PublicLayout) |
| 1.3 Renombrar `/admin` → `/` (landing real) | 30min | ✅ | `routes/web.php` (ya configurado) |
| 1.4 `PricingPublic.vue` accesible sin login | 3h | ✅ | `resources/js/Pages/Public/PricingPublic.vue` |
| SEO.1 `sitemap.xml` (dinámico) | 1h | ✅ | `app/Http/Controllers/SitemapController.php` |
| SEO.2 `robots.txt` actualizado | 30min | ✅ | `public/robots.txt` |
| SEO.3 OG/Twitter tags globales en `app.blade.php` | 1h | ✅ | `resources/views/app.blade.php` |
| SEO.4 Schema.org `AutoDealer` global | 2h | ✅ | `resources/views/partials/schema-org.blade.php` |
| M.1 WhatsApp flotante en `PublicLayout` | 1.5h | ✅ | `resources/js/Components/WhatsAppFloat.vue` |

**Commit:** `d32a3f5`, `91c71d3`

**Archivos modificados:**
- `routes/web.php`
- `resources/views/app.blade.php`
- `public/robots.txt`
- `resources/js/Layouts/PublicLayout.vue` (nuevo)
- `resources/js/Pages/PricingPublic.vue` (nuevo)
- `resources/js/Pages/MarketplaceIndex.vue`
- `resources/js/Pages/MarketplaceShow.vue`
- `resources/js/Pages/CarRequestForm.vue`
- `app/Http/Controllers/SitemapController.php` (nuevo)
- `resources/js/Components/WhatsAppFloat.vue` (nuevo)
- `resources/views/partials/schema-org.blade.php` (nuevo)

**Entregable:** Landing público completo con SEO técnico básico, pricing público, y conversión vía WhatsApp.

---

### Session B — Onboarding + Experiencia Primer Usuario 🚧 **EN PROGRESO 2026-08-06**
**Duración:** ~19 horas (2.5 días)
**Prioridad:** ⭐⭐ Alta (afecta activation rate D0-D7)
**Estado actual:** 3/6 items completados (~9h de 19h)

| Sprint Item | Esfuerzo | Estado | Archivos clave |
|---|---|---|---|
| 2.1 `OnboardingController` + migration | 3h | ✅ **HECHO** | `database/migrations/*_user_onboarding_progress.php`, `app/Http/Controllers/OnboardingController.php` |
| 2.2 `OnboardingWizard.vue` 4 pasos | 6h | ✅ **HECHO** | `resources/js/Pages/Onboarding/Wizard.vue` |
| 2.3 `OnboardingChecklist.vue` en dashboard | 3h | ✅ **HECHO** | `resources/js/Components/OnboardingChecklist.vue` |
| 2.4 Empty states con doble CTA | 2h | **Pendiente** | `Cars/Index`, `Clients/Index`, `Contacts/Index` |
| 2.5 Email bienvenida + recordatorio D3, D7 | 3h | **Pendiente** | `resources/views/mail/onboarding/*` |
| 2.6 `DatabaseSeeder` datos ejemplo | 2h | **Pendiente** | `database/seeders/OnboardingSeeder.php` |

**Archivos ya creados/modificados:**
- `database/migrations/2026_08_06_204013_create_user_onboarding_progress_table.php` ✅
- `app/Models/UserOnboardingProgress.php` ✅
- `app/Http/Controllers/OnboardingController.php` ✅
- `app/Models/User.php` (relación onboardingProgress) ✅
- `routes/web.php` (rutas /onboarding) ✅
- `resources/js/Pages/Onboarding/Wizard.vue` ✅
- `resources/js/Components/OnboardingChecklist.vue` ✅

**Archivos pendientes de crear:**
- `resources/views/mail/onboarding/welcome.blade.php`
- `resources/views/mail/onboarding/reminder-d3.blade.php`
- `resources/views/mail/onboarding/reminder-d7.blade.php`
- `database/seeders/OnboardingSeeder.php`

**Archivos pendientes de modificar:**
- `resources/js/Pages/Cars/Index.vue` (añadir empty state)
- `resources/js/Pages/Clients/Index.vue` (añadir empty state)
- `resources/js/Pages/Contacts/Index.vue` (añadir empty state)
- `resources/js/Pages/Dashboard.vue` (integrar OnboardingChecklist)

**Para otra sesión:**
- ❌ NO tocar Session C (Dark Mode + UX Premium) — ~20h
- ❌ NO tocar Session D (Performance + DX) — ~9.5h
- ❌ NO tocar Session E (Billing UX + Dunning) — ~16h
- ❌ NO tocar Session F, G, H (Marketplace) — ~50h
- ❌ NO tocar Session I (Notificaciones avanzadas) — ~32h

**Entregable:** Flujo completo de onboarding 4 pasos + checklist persistente + seeders para trial inmediato.

---

### Session C — Dark Mode + UX Premium
**Duración:** ~20 horas (2.5 días)
**Prioridad:** ⭐⭐ Alta (experiencia de usuario premium)

| Sprint Item | Esfuerzo | Estado | Archivos clave |
|---|---|---|---|
| 3.1 Auditar y añadir `dark:` en 30+ Pages | 8h | Pendiente | Todas las Pages en `resources/js/Pages/` |
| 3.2 Migrar a `@vueuse/motion` | 3h | Pendiente | `package.json` + componentes clave |
| 3.3 WCAG AA: contraste, focus, keyboard nav | 4h | Pendiente | Audit global de contrastes |
| 3.4 Skeleton `<Suspense>` con `WhenVisible` | 3h | Pendiente | `Cars/Index`, `Clients/Index` |
| 3.5 Eliminar `tailwind.config.js` v3 | 2h | Pendiente | `tailwind.config.js` |

**Archivos modificados:**
- `package.json` (añadir `@vueuse/motion`)
- `resources/js/Pages/` (todas las Pages con dark variants)
- `resources/js/Pages/Cars/Index.vue`
- `resources/js/Pages/Clients/Index.vue`
- `tailwind.config.js`

**Entregable:** Dark mode completo en toda la app + microinteracciones + WCAG AA baseline.

---

### Session D — Performance + DX
**Duración:** ~9.5 horas (1.5 días)
**Prioridad:** ⭐ Media (mejora percepción, no es bloqueante)

| Sprint Item | Esfuerzo | Estado | Archivos clave |
|---|---|---|---|
| 4.1 `vite.config.js`: `manualChunks` split | 1h | Pendiente | `vite.config.js` |
| 4.2 Inertia `deferred props` listados largos | 3h | Pendiente | `Cars/Index`, `Clients/Index` controllers |
| 4.3 Inertia `prefetching on hover` en sidebar | 2h | Pendiente | `resources/js/Layouts/AuthenticatedLayout.vue` |
| 4.4 Preload `<link rel="modulepreload">` | 1h | Pendiente | `resources/views/app.blade.php` |
| 4.5 Compresión `brotli` en config | 30min | Pendiente | `.htaccess` / nginx |
| 4.6 Lazy load Heroicons por categoría | 2h | Pendiente | `resources/js/icons.js` refactor |

**Archivos modificados:**
- `vite.config.js`
- `app/Http/Controllers/Cars/IndexController.php`
- `app/Http/Controllers/Clients/IndexController.php`
- `resources/js/Layouts/AuthenticatedLayout.vue`
- `resources/views/app.blade.php`
- `.htaccess` o nginx config
- `resources/js/icons.js`

**Entregable:** Bundle optimizado (<250KB gzipped) + lazy loading inteligente.

---

### Session E — Billing UX + Dunning
**Duración:** ~16 horas (2 días)
**Prioridad:** ⭐⭐ Alta (directamente afecta revenue)

| Sprint Item | Esfuerzo | Estado | Archivos clave |
|---|---|---|---|
| 5.1 Tabla comparativa de planes | 3h | Pendiente | `resources/js/Pages/Subscriptions/Index.vue` |
| 5.2 Toggle mensual/anual con % descuento | 2h | Pendiente | `PricingPublic.vue` + `Subscriptions/Index.vue` |
| 5.3 Banner dunning en dashboard | 2h | Pendiente | `resources/js/Layouts/AuthenticatedLayout.vue` |
| 5.4 Emails transaccionales en español | 4h | Pendiente | `resources/views/mail/billing/*` |
| 5.5 Página `/billing/cancel` honesta | 2h | Pendiente | `resources/js/Pages/Billing/Cancel.vue` |
| 5.6 `UpgradePrompt` contextual (90% límite) | 3h | Pendiente | `resources/js/Components/UpgradePrompt.vue` |

**Archivos modificados:**
- `resources/js/Pages/Subscriptions/Index.vue`
- `resources/js/Pages/PricingPublic.vue`
- `resources/js/Layouts/AuthenticatedLayout.vue`
- `resources/views/mail/billing/trial_ending.blade.php` (nuevo)
- `resources/views/mail/billing/payment_failed.blade.php` (nuevo)
- `resources/views/mail/billing/reactivated.blade.php` (nuevo)
- `resources/js/Pages/Billing/Cancel.vue` (nuevo)
- `resources/js/Components/UpgradePrompt.vue` (nuevo)
- `routes/web.php` (añadir `/billing/cancel`)

**Entregable:** UX de billing completa con tabla comparativa, dunning gestionado, y prompts contextuales.

---

### Session F — Marketplace Público (Iteración 1: Quick Wins)
**Duración:** ~12 horas (1.5 días)
**Prioridad:** ⭐⭐ Alta (conversión anónima)

| Marketplace Item | Esfuerzo | Estado | Archivos clave |
|---|---|---|---|
| 1 Alinear obligatoriedad backend ↔ frontend | 2h | Pendiente | `MarketplaceIndex.vue` + controller |
| 4 Botón WhatsApp flotante | 1.5h | Pendiente | **YA HECHO en Session A** |
| 5 Compartir coche (WA/email/enlace) | 1.5h | Pendiente | `MarketplaceShow.vue` |
| 6 Galería / lightbox de fotos | 3h | Pendiente | `MarketplaceShow.vue` + componente lightbox |
| 13 Schema.org `Vehicle` + `Offer` | 2h | Pendiente | `MarketplaceShow.vue` |
| 14 OG meta tags dinámicos por coche | 2h | Pendiente | `MarketplaceController` + `app.blade.php` |

**Archivos modificados:**
- `app/Http/Controllers/MarketplaceController.php`
- `resources/js/Pages/Marketplace/Index.vue`
- `resources/js/Pages/Marketplace/Show.vue`
- `resources/js/Components/LightboxGallery.vue` (nuevo)
- `resources/js/Components/ShareButton.vue` (nuevo)
- `resources/views/partials/vehicle-schema.blade.php` (nuevo)

**Entregable:** Marketplace público con conversión básica optimizada (compartir + lightbox + SEO por coche).

---

### Session G — Marketplace Público (Iteración 2: Engagement)
**Duración:** ~17 horas (2 días)
**Prioridad:** ⭐ Media (engagement + retención)

| Marketplace Item | Esfuerzo | Estado | Archivos clave |
|---|---|---|---|
| 2 Filtros extendidos (combustible, cambio, etc) | 3h | Pendiente | `MarketplaceIndex.vue` + controller |
| 3 Sticky filter bar en scroll | 2h | Pendiente | `MarketplaceIndex.vue` |
| 7 Contador de visitas en ficha + card | 2h | Pendiente | `Car` model + migration |
| 11 Testimonios reales (sección + CRUD admin) | 8h | Pendiente | `Testimonial` model + admin |
| 12 Newsletter popup suave + lead magnet | 2h | Pendiente | `NewsletterPopup.vue` |

**Archivos modificados:**
- `app/Http/Controllers/MarketplaceController.php`
- `resources/js/Pages/Marketplace/Index.vue`
- `app/Models/Car.php`
- `database/migrations/*_cars_view_count.php` (nuevo)
- `app/Models/Testimonial.php` (nuevo)
- `app/Http/Controllers/TestimonialController.php` (nuevo)
- `resources/js/Pages/Admin/Testimonials/Index.vue` (nuevo)
- `resources/js/Components/NewsletterPopup.vue` (nuevo)

**Entregable:** Marketplace con filtros avanzados, social proof (testimonios), y captura de leads (newsletter).

---

### Session H — Marketplace Público (Iteración 3: Viralidad)
**Duración:** ~21 horas (3 días)
**Prioridad:** ⭐ Baja-Media (nice-to-have)

| Marketplace Item | Esfuerzo | Estado | Archivos clave |
|---|---|---|---|
| 8 Vista comparativa (checkbox → modal) | 4h | Pendiente | `MarketplaceIndex.vue` + modal |
| 9 Wishlist con `localStorage` | 4h | Pendiente | `WishlistManager.js` composable |
| 10 Búsqueda server-side URL compartible | 6h | Pendiente | `MarketplaceController` + router |
| 15 Calculadora de financiación en ficha | 4h | Pendiente | `FinanceCalculator.vue` + lógica |
| 3.7 Contador de visitas en la ficha + mostrar en card | **YA HECHO en Session G** | ✅ | |

**Archivos modificados:**
- `app/Http/Controllers/MarketplaceController.php`
- `resources/js/Pages/Marketplace/Index.vue`
- `resources/js/Pages/Marketplace/Show.vue`
- `resources/js/Components/ComparisonModal.vue` (nuevo)
- `resources/js/Composables/useWishlist.js` (nuevo)
- `resources/js/Components/FinanceCalculator.vue` (nuevo)

**Entregable:** Marketplace viral con wishlist, comparador, y calculadora de financiación.

---

### Session I — Sistema de Notificaciones (Avanzado)
**Duración:** ~32 horas (4 días)
**Prioridad:** ⭐ Baja (core ya está hecho: polling + toasts)

| Notification Item | Esfuerzo | Estado | Archivos clave |
|---|---|---|---|
| N1 Filtros por tipo en `/alerts` | 2h | Pendiente | `Alerts/Index.vue` |
| N2 Acciones inline según `alert_type` | 3h | Pendiente | `Alerts/Index.vue` + jobs |
| N3 Snooze (migration BD) | 4h | Pendiente | `Alert` model + migration |
| N4 Group by type (acordeón) | 2h | Pendiente | `Alerts/Index.vue` |
| N5 Email digest semanal | 4h | Pendiente | `AlertDigestJob.php` |
| N6 Push notifications (Web Push API) | 8h | Pendiente | Service worker + manifest |
| N7 Slack/Discord webhook opcional | 3h | Pendiente | `Alert` model + webhook |
| N8 Preferencias por usuario | 6h | Pendiente | `notification_preferences` table + CRUD |

**Archivos modificados:**
- `resources/js/Pages/Alerts/Index.vue`
- `app/Models/Alert.php`
- `database/migrations/*_alerts_snooze.php` (nuevo)
- `app/Jobs/AlertDigestJob.php` (nuevo)
- `app/Jobs/WebhookNotificationJob.php` (nuevo)
- `public/sw.js` (nuevo)
- `public/manifest.json` (nuevo)
- `app/Models/NotificationPreference.php` (nuevo)
- `resources/js/Pages/Settings/Notifications/Index.vue` (nuevo)

**Entregable:** Sistema de notificaciones enterprise-grade con email digest, push, y preferencias granulares.

---

## 🔒 Reglas de conflicto

### Archivos compartidos entre sesiones

| Archivo | Sesiones que lo tocan | Resolución |
|---|---|---|
| `routes/web.php` | A, B, E, F, G, H | Session A primero (estructura base), resto añaden rutas específicas |
| `resources/views/app.blade.php` | A, D | Session A primero (OG tags), D añade preload links |
| `resources/js/Layouts/AuthenticatedLayout.vue` | D, E | D primero (prefetching), E añade banner dunning |
| `resources/js/Pages/Marketplace/Index.vue` | A, F, G, H | A refactoriza a PublicLayout, F/G/H añaden features |
| `resources/js/Pages/Marketplace/Show.vue` | A, F, H | A refactoriza a PublicLayout, F/H añaden features |
| `app/Http/Controllers/MarketplaceController.php` | F, G, H | Iteración por iteración (F → G → H) |
| `app/Models/Car.php` | F, G, H | F añade view_count, G/H no tocan model |

**Proceso de merge:**
1. Session A primero (dependencia crítica)
2. Session B después (independiente)
3. Session D y E pueden ser paralelas (archivos distintos)
4. Session F → G → H en orden iterativo
5. Session I al final (nice-to-have)

---

## 🎯 Orden recomendado de ejecución

### Fase 1 — Fundamentos (1 semana)
**Ordén:** A → B
**Por qué:** Session A crea la estructura pública que Session B puede reutilizar (onboarding sin login para trial).

### Fase 2 — UX + Billing (1.5 semanas)
**Ordén:** D || E (paralelo si 2 sesiones), luego C
**Por qué:** D y E tocan dominios distintos, C depende de que existan las Pages completas.

### Fase 3 — Marketplace (3 semanas)
**Ordén:** F → G → H (iterativo)
**Por qué:** Cada iteración depende de la anterior. Se pueden hacer en sesiones consecutivas.

### Fase 4 — Nice-to-have (1 semana)
**Ordén:** I (opcional)
**Por qué:** Core de notificaciones ya está hecho, esto es refinamiento.

---

## ✅ Checklist de inicio de sesión

Antes de empezar una sesión, verificar:

- [ ] `git status` — working tree limpia
- [ ] `git pull origin master` — último código
- [ ] Leer el archivo de la sesión correspondiente en `docs/PLAN_IA_2026-08-06.md`
- [ ] Revisar "Lo que ya está hecho" para NO duplicar
- [ ] `php artisan test --compact` — tests verdes
- [ ] Crear rama `feature/session-{X}-{nombre}` (ej: `feature/session-A-fundamentos-publicos`)
- [ ] Documentar en `/memories/session/` el progreso al terminar cada item

---

## 📝 Template de reporte de sesión

```markdown
## Session {X} — {Nombre}

**Fecha:** 2026-08-XX
**Rama:** `feature/session-{X}-{nombre}`
**Items completados:** N/M

### Completados
- [x] Item 1.1 (2h) — descripción corta
- [x] Item 1.2 (2h) — descripción corta

### Pendientes
- [ ] Item 1.3 (1h) — descripción corta

### Bloqueadores
- Bloqueador 1

### Commits
- `abc1234` feat: descripción
- `def5678` fix: descripción

### Next steps
- Qué hacer en la siguiente sesión
```
