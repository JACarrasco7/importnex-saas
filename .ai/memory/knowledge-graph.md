---
description: Sistema de conocimientos en grafos de memoria para ImportnexCore. Patrón Basic Memory + MCP Memory Server. Actualizado automáticamente por memory-manager.php cada semana.
last_updated: 2026-08-08
nodes: 58
relationships: 112
---

# 🕸️ Knowledge Graph — ImportnexCore

> **Motor:** `@modelcontextprotocol/server-memory` (MCP)
> **Formato:** Markdown con frontmatter, bi-direccional sync LLM

---

## Nodes primarios

### Tecnologías
```mermaid
graph LR
    A[Laravel 13.24] --> B[PHP 8.5]
    A --> C[Inertia v3]
    C --> D[Vue 3.5]
    C --> E[Vite 8.2]
    A --> F[Cashier 16.7]
    F --> G[Stripe API]
    A --> H[Spatie Permission 8.3]
    A --> I[Boost 2.5.2]
    I --> J[MCP Servers 5]
```

### Módulos de negocio
```mermaid
graph TB
    K[Organization] --> L[Cars]
    K --> M[Clients]
    K --> N[Subscriptions]
    N --> O[Stripe Webhooks]
    K --> P[Valuations]
    P --> Q[Mistral AI Bridge]
    Q --> R[Scraping Service]
```

### Skills activas (15)
```mermaid
graph TD
    S1[quickref] --> S2[multitenancy]
    S1 --> S3[design-system]
    S1 --> S4[billing]
    S4 --> S5[cashier-billing]
    S2 --> S6[bridge-mistral]
    S3 --> S7[i18n]
    S4 --> S8[forge-deploy]
    S6 --> S9[ai-chat]
    S7 --> S10[tests-phpunit]
    S3 --> S11[self-audit]
    S11 --> S12[debug-rca]
    S12 --> S13[auto-context]
    S13 --> S14[auto-learner]
    S14 --> S15[auto-documentation]
```

---

## Relaciones clave

| Origen | Relación | Destino | Peso |
|---|---|---|---|
| Cars | `belongs_to` | Organization | 1.0 |
| Cars | `has_many` | Valuations | 0.8 |
| Organization | `has_one` | Subscription | 1.0 |
| MistralBridge | `calls` | Mistral API | 0.9 |
| StripeWebhookController | `processes` | PaymentFailed | 0.7 |
| HandleInertiaRequests | `shares` | i18n locale | 0.5 |

---

## Hot spots (alta complejidad)

| Archivo | Complejidad | Llamadas entrantes |
|---|---|---|
| `app/Services/ValuationImporter.php` | 🔴 32 | 4 |
| `app/Http/Controllers/Cars/CarController.php` | 🟠 18 | 12 |
| `app/Http/Middleware/HandleInertiaRequests.php` | 🟡 14 | ∞ |
| `resources/js/Pages/Cars/Show.vue` | 🟡 22 | 3 |

---

## Anti-patrones del grafo

- 🔴 `ValuationImporter` → sin tests de coverage (12 fallan pre-existentes)
- 🟠 `CarController` → sin Form Request (validación inline)
- 🟡 `i18n es.js/en.js` → ahora 92% sincronizadas (era 47% antes de auditoría)

## Sistema de Notificaciones (nuevo 2026-08-07)

```mermaid
graph LR
    A[Alert Model] --> B[AlertObserver]
    B --> C[AlertWebhookDispatcher]
    B --> D[AlertEmailDispatcher]
    B --> E[PushNotificationDispatcher]
    C --> F[Slack/Discord/Teams]
    D --> G[Laravel Mail]
    E --> H[OneSignal API]
    B -.check.-> I[Organization::isAlertTypeEnabled]
    B -.check.-> J[Organization::webhookEnabledFor]
    D -.check.-> K[User::isChannelEnabled]
    D -.check.-> L[User::isAlertTypeEnabled]
```

## Marketplace Engagement (nuevo 2026-08-07)

```mermaid
graph LR
    A[MarketplaceIndex] --> B[WishlistButton]
    A --> C[CompareBar]
    A --> D[FilterBar]
    B -.localStorage.-> E[useWishlist composable]
    C -.router.visit.-> F[MarketplaceCompare]
    G[MarketplaceShow] --> H[FinancingCalculator]
    G --> B
    G --> I[ShareCar]
    D -.server-side.-> J[PublicMarketplaceController]
    J -.whitelist.-> K[FILTER_RULES]
    J -.paginate(12).-> L[MarketplaceIndex]
```

---

## Auditoría 2026-08-07 (13 bugs corregidos)

| ID | Tipo | Estado |
|---|---|---|
| C1 | Icono Vue no importado | ✅ |
| C2 | Import duplicado | ✅ |
| C3 | Helper no exportado | ✅ |
| C4 | Backend ignora frontend | ✅ |
| C5 | Prefs usuario muertas | ✅ |
| H1 | i18n desincronizado | ✅ |
| H2 | Pluralización no implementada | ✅ |
| H3 | Import onMounted faltante | ✅ |
| H5 | Color email no brand | ✅ |
| H6 | Filtros client-side | ✅ |
| H7 | SSR guard | ✅ |
| M2 | Global scope multi-tenant | ✅ |
| M6 | Test débil | ✅ |

---

## Hot spots actualizados (2026-08-07)

| Archivo | Complejidad | Notas |
|---|---|---|
| `app/Services/ValuationImporter.php` | 🔴 32 | 12 tests fallan pre-existentes |
| `app/Http/Controllers/Cars/CarController.php` | 🟠 18 | Sin Form Request |
| `app/Http/Middleware/HandleInertiaRequests.php` | 🟡 14 | Inertia shared props |
| `resources/js/Pages/Cars/Show.vue` | 🟡 22 | +i18n strings |
| `app/Observers/AlertObserver.php` | 🟢 8 | 3 dispatchers independientes |
| `app/Services/AlertEmailDispatcher.php` | 🟢 6 | N8 prefs usuario |
| `app/Http/Controllers/PublicMarketplaceController.php` | 🟡 12 | 12 filtros whitelist |

## Sync automático

```bash
# Regenerar grafo desde código
php scripts/memory-manager.php build-graph

# Ver hot spots
php scripts/memory-manager.php hot-spots

# Relaciones entre módulos
php scripts/memory-manager.php relations --source=Cars --depth=2
```
