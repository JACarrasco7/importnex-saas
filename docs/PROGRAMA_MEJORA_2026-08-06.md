<!-- filepath: docs/PROGRAMA_MEJORA_2026-08-06.md -->
# Programa de Mejora Continua — ImportnexCore
**Fecha:** 2026-08-06
**Autor:** Auditoría de producto + frontend + tendencias SaaS 2025-2026
**Horizonte:** 3-6 meses

> El sistema está funcional y robusto (billing hardening Fases 1-3, vitalicio JJ, 53 tests verde). Pero todo está orientado a JJ Import Motors como cliente único. Para escalar el SaaS a múltiples tenants de pago, hay que **profesionalizar la capa de producto** sin sobredimensionar.

---

## 🎯 Núcleo del cambio

### Lo que NO debe cambiar (es el asset)

- **Multi-tenant por `organization_id`** — funciona, aislado, escalable.
- **Billing con Cashier** — robusto, idempotente, listo para Stripe real.
- **Vitalicio via `is_owner`** — patrón limpio, replicable.
- **Inertia + Vue 3 + Tailwind** — stack sano.
- **Branding JJ Import Motors** — paleta estoril/asphalt/platinum, consistente en privado.

### Lo que SÍ debe profesionalizarse

1. **Diferenciar público vs privado** (ahora conviven con naming confuso).
2. **Pricing público, no solo en `/subscriptions` autenticado**.
3. **UX de primer uso** (onboarding, empty states, comandos).
4. **Performance, accesibilidad, dark mode** (acabados).
5. **Soporte para i18n real** (no 70% traducido).

---

## 📐 Núcleo público vs privado

### Estado actual (confuso)

| Ruta | Qué es | Problema |
|---|---|---|
| `/` | redirect → `/marketplace` | "Landing" real no existe |
| `/admin` | `Welcome.vue` (marketing real) | Nombre mal puesto |
| `/marketplace` | público (catálogo) | OK pero sin layout compartido |
| `/request/{slug}` | público (form cliente) | Sin layout |
| `/dashboard` | privado (app) | OK |
| `/subscriptions` | privado (planes) | OK pero sin pricing público |

### Estado propuesto (limpio)

```
PÚBLICO (sin auth, con PublicLayout.vue nuevo)
├── /                              → Landing marketing (Welcome.vue renombrado)
├── /pricing                       → Página pública de planes (PricingPublic.vue)
├── /marketplace, /marketplace/{car} → Catálogo público (ya existe)
├── /request/{slug}                → Form cliente (ya existe)
├── /jj-import/folleto             → PDF (ya existe)
└── /login, /register              → Auth (GuestLayout.vue)

PRIVADO (auth + org, AuthenticatedLayout.vue)
├── /dashboard                     → App home
├── /onboarding                    → Wizard 4 pasos (NUEVO)
├── /cars, /clients, /contacts, /subscriptions, /billing, ...
└── /admin/*                       → Settings (legacy, restringido a owner)
```

---

## 🏗️ Programa en 5 sprints

### Sprint 1 — Quick wins de producto (1 semana)

| # | Acción | Esfuerzo | Impacto |
|---|---|---|---|
| 1.1 | Crear `PublicLayout.vue` (header + footer + nav) | 2h | Alto |
| 1.2 | Migrar `MarketplaceIndex`, `MarketplaceShow`, `CarRequestForm` al layout | 2h | Alto |
| 1.3 | Renombrar `/admin` → `/` (landing real) y `/welcome` legacy | 30min | Alto |
| 1.4 | Crear `PricingPublic.vue` accesible sin login (SEO) | 3h | Alto |
| 1.5 | Skeletons en `Cars/Index`, `Clients/Index`, `Contacts/Index` | 2h | Medio |
| 1.6 | Traducir strings hardcoded en `Subscriptions/{Index,Show}.vue` | 1h | Medio |
| 1.7 | Quitar `safelist` de colores prohibidos en `tailwind.config.js` | 30min | Bajo |

**Resultado:** home pública real, pricing visible, consistencia de marca, dark mode preparado.

### Sprint 2 — Onboarding + primer valor (1.5 semanas)

| # | Acción | Esfuerzo | Impacto |
|---|---|---|---|
| 2.1 | `OnboardingController` + tabla `user_onboarding_progress` (migration) | 3h | Alto |
| 2.2 | Componente `OnboardingWizard.vue` 4 pasos (org → vehicle → invite → plan) | 6h | Alto |
| 2.3 | `OnboardingChecklist.vue` persistente en dashboard (D7) | 3h | Alto |
| 2.4 | Empty states con doble CTA (importar CSV / crear manual) | 2h | Medio |
| 2.5 | Email de bienvenida + recordatorio D3, D7 | 3h | Medio |
| 2.6 | `DatabaseSeeder` con datos de ejemplo para trial inmediato | 2h | Medio |

**Resultado:** TTV < 10 min, activation rate > 60% en D7.

### Sprint 3 — Dark mode + accesibilidad (1.5 semanas)

| # | Acción | Esfuerzo | Impacto |
|---|---|---|---|
| 3.1 | Auditar y añadir `dark:` variants en 30+ Pages | 8h | Alto |
| 3.2 | Eliminar `tailwind.config.js` v3 (redundante con v4 CSS) | 2h | Medio |
| 3.3 | Migrar a `@vueuse/motion` para microinteracciones | 3h | Medio |
| 3.4 | WCAG AA: contraste, focus visible, keyboard nav | 4h | Alto |
| 3.5 | Skeleton loaders en `<Suspense>` con Inertia v2 `WhenVisible` | 3h | Medio |

**Resultado:** producto "a la última", accesible, sin parpadeos.

### Sprint 4 — Performance + DX (1 semana)

| # | Acción | Esfuerzo | Impacto |
|---|---|---|---|
| 4.1 | `vite.config.js`: `manualChunks` split vendor (vue, inertia, heroicons) | 1h | Alto |
| 4.2 | Inertia `deferred props` para listados largos (Cars, Clients) | 3h | Alto |
| 4.3 | Inertia `prefetching on hover` en sidebar | 2h | Medio |
| 4.4 | Preload `<link rel="modulepreload">` en `app.blade.php` | 1h | Medio |
| 4.5 | Compresión `brotli` en `.htaccess` / nginx config | 30min | Medio |
| 4.6 | Lazy load Heroicons por categoría (24-KB → 8-KB) | 2h | Medio |

**Resultado:** LCP < 2s, TTI < 1.5s, bundle -40%.

### Sprint 5 — Billing UX + Dunning (1 semana)

| # | Acción | Esfuerzo | Impacto |
|---|---|---|---|
| 5.1 | Tabla comparativa de planes (Features matrix) | 3h | Alto |
| 5.2 | Toggle mensual/anual con % descuento | 2h | Alto |
| 5.3 | Banner dunning en dashboard ("tu pago falló, X días restantes") | 2h | Alto |
| 5.4 | Emails transaccionales en español: trial_ending, payment_failed, reactivated | 4h | Alto |
| 5.5 | Página `/billing/cancel` honesta ("qué pasa si cancelas") | 2h | Medio |
| 5.6 | `UpgradePrompt` contextual cuando alcanzamos 90% de un límite | 3h | Medio |

**Resultado:** conversión trial→paid > 25%, churn < 5%.

---

## 📊 Top 5 quick wins (los más rápidos)

| # | Acción | Esfuerzo | Impacto |
|---|---|---|---|
| 1 | **Pricing público en `/pricing`** | 3h | Alto (SEO + conversión) |
| 2 | **PublicLayout.vue** unificando marketplace + form | 2h | Alto (consistencia) |
| 3 | **Renombrar `/admin` → `/`** | 30min | Alto (UX) |
| 4 | **Skeletons en listados** | 2h | Medio (percepción) |
| 5 | **Tabla comparativa de planes** | 3h | Alto (conversión) |

---

## 🚫 Lo que NO haremos (anti-overengineering)

- ❌ **CMS headless** para 4 páginas → Blade/Inertia basta.
- ❌ **A/B testing desde día 1** → esperar >1000 visitas/mes.
- ❌ **Usage-based billing real** → no hay demanda.
- ❌ **Migrar a Paddle/Lemon Squeezy** → Stripe ya cobra IVA en UE.
- ❌ **PWA / Service Worker** → no es core, mercado objetivo no lo pide.
- ❌ **Más de 5 pasos en wizard** → abandono >50%.
- ❌ **AI suggest desde día 1** → sin datos, malinterpreta.

---

## 📈 Métricas de éxito (a 6 meses)

| Métrica | Baseline | Target |
|---|---|---|
| Time-to-value (D0) | n/a | < 10 min |
| Activation rate (D7) | n/a | > 60% |
| Trial→paid conversion | 0% | > 25% |
| Churn mensual | n/a | < 5% |
| LCP (mobile) | n/a | < 2.5s |
| TTI (desktop) | n/a | < 1.5s |
| Bundle size | n/a | < 250 KB gzipped |
| Dark mode adoption | 0% | > 30% |
| Lighthouse score | n/a | > 90 |
| i18n coverage | ~70% | 100% |

---

## 🧭 Próximo paso recomendado

**Empezar Sprint 1** (1 semana). Empezar por 1.1+1.2+1.3 (público diferenciado) y 1.4 (pricing público). Es lo que más rápido diferenciará el producto a simple vista.

---

## 📚 Investigación de tendencias aplicada

### Landing pages (Linear, Vercel, Stripe, Framer, Resend)
- Hero con producto real, no stock-photos.
- Social proof por mercado (logos de dealers, testimonios).
- Pricing transparente con toggle anual y "Most popular".

### Dashboards (Linear, Vercel, Raycast, Notion)
- ⌘K omnipresente (ya está CommandPalette).
- Sidebar colapsable en desktop (hoy solo en mobile).
- Dark mode nativo (ya hay toggle, falta aplicar).
- Empty states con doble CTA.
- Skeleton loaders donde >300ms.

### Pricing (Linear, Vercel, Customer.io, Paddle, Lemon Squeezy)
- 3 planes + Enterprise custom.
- Toggle mensual/anual con descuento.
- Dunning en español con grace period 7 días.
- Página "qué pasa si cancelas" honesta.

### Onboarding (Linear, Notion, Airtable, Slack, Figma)
- Setup wizard 4 pasos post-registro.
- Empty workspace = checklist de 3 items.
- "Aha moment" = primera valoración con QR.
- Time-to-value < 10 min.

---

## ✅ Decisión

Esto **no se hace en un día**. Es un programa de 5 sprints. Recomiendo aprobar Sprint 1 (quick wins) y reevaluar tras su cierre antes de comprometer Sprint 2.

¿Apruebas Sprint 1 y empiezo con PublicLayout + PricingPublic?
