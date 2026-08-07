---
description: Sistema de conocimientos en grafos de memoria para ImportnexCore. Patrón Basic Memory + MCP Memory Server. Actualizado automáticamente por memory-manager.php cada semana.
last_updated: 2026-08-07
nodes: 47
relationships: 89
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

- 🔴 `ValuationImporter` → sin tests de coverage (12 fallan)
- 🟠 `CarController` → sin Form Request (validación inline)
- 🟡 `i18n es.js/en.js` → 1250 claves desincronizadas

---

## Sync automático

```bash
# Regenerar grafo desde código
php scripts/memory-manager.php build-graph

# Ver hot spots
php scripts/memory-manager.php hot-spots

# Relaciones entre módulos
php scripts/memory-manager.php relations --source=Cars --depth=2
```
