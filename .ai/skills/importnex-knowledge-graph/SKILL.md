---
name: importnex-knowledge-graph
description: Knowledge Graph de ImportnexCore generado desde el código. Muestra relaciones entre módulos, hot spots, y dependencias. Activar para entender arquitectura, impacto de cambios, o navegar código.
---

# 🕸️ Knowledge Graph Skill

> **Motor:** `@modelcontextprotocol/server-memory` MCP
> **Formato:** Markdown con frontmatter, nodos, relaciones en grafo

---

## Cuándo se activa

- "¿Qué impacto tiene cambiar X?"
- "¿Quién llama a este controller?"
- "Dame un mapa de dependencias"
- "¿Qué módulos dependen del Billing?"
- "Hot spots del código"
- Automático: al inicio de sesión vía auto-context

---

## Nodos del grafo

### Stack tecnológico
```
Laravel 13.24 → PHP 8.5, Inertia v3, Vite 8.2, Cashier 16.7
Inertia v3 → Vue 3.5, Tailwind v4
Cashier 16.7 → Stripe API, Webhooks
Valuation → Mistral AI Bridge → Scraping
```

### Módulos de negocio
```
Organization → Cars, Clients, Subscriptions, Valuations
Cars → Imports, Marketplace, Valuations
Valuations → MistralBridge, QR, PDF Export
Subscriptions → Stripe Webhooks, Dunning
```

### Hot spots (áreas de alto riesgo)
| Archivo | Riesgo | Razón |
|---|---|---|
| `ValuationImporter.php` | ALTO | 0 tests, lógica compleja |
| `HandleInertiaRequests.php` | MEDIO | Touchpoint universal |
| `StripeWebhookController.php` | ALTO | Dinero real, idempotencia |
| `CarController.php` | MEDIO | Sin Form Request |

---

## Cómo consultar el grafo

```bash
# Relaciones entrantes a un archivo
php scripts/memory-manager.php relations --target=CarController

# Dependencias salientes
php scripts/memory-manager.php relations --source=StripeWebhook

# Hot spots
php scripts/memory-manager.php hot-spots

# Camino entre dos módulos
php scripts/memory-manager.php path --from=Cars --to=MistralAPI
```

---

## Regeneración automática

```bash
# Cada lunes a las 9am (GitHub Action)
# Cada vez que cambia composer.json o app/
php scripts/memory-manager.php build-graph
```
