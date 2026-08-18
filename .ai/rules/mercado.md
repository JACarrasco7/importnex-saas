---
glob: 'app/Models/MarketModel.php,app/Models/MarketLead.php,app/Models/MarketModelHistory.php,app/Console/Commands/Market*.php,app/Http/Controllers/MercadoController.php,app/Http/Controllers/Api/MarketApiController.php,app/Http/Controllers/Api/PublicMarketController.php,resources/js/Pages/Mercado/**,resources/js/Pages/Public/MercadoIndex.vue,tests/Feature/MarketMercadoTest.php'
title: 'Módulo Mercado — mapa de mercado, catálogo y leads'
---

## Arquitectura (2026-08-17)

Mapa de mercado persistente (`market_models`) generado por la skill `estudio-mercado` (Claude Desktop) y visualizado en Laravel.

## Reglas críticas

1. **Multi-tenant SIEMPRE**: toda query de admin/kpis/reportes/leads usa `MarketModel::deOrganizacion($orgId)` (org + globales null). No hacer filtros inline.
2. **`veredicto_fuente=humano`**: cuando un humano edita el veredicto en admin, `market:import` NO debe pisarlo. Respeta el bucle humano.
3. **Historial sin duplicados**: `market:import` solo crea entrada en `market_model_history` si cambió vs la última del mismo día.
4. **Validación de import**: `market:import` valida `categoria`/`segmento`/`tipo_cliente` contra las constantes del modelo y hace dedup de `alias`. Mantener sincronizadas constantes ↔ validación.
5. **Fórmula de hueco**: `hueco_pct` bruto `(ES−DE)/ES`; `hueco_neto_pct` neto con costes (para veredicto de negocio). NO cambiar la base sin actualizar `calcularVendibilidad()`.
6. **Cache de stats**: `MarketApiController@stats` y `PublicMarketController@stats` usan `Cache::remember(1800s)` keyed por org. Invalidar si cambia la fuente de datos.

## Frontend

- Catálogo público `/mercado` (MercadoIndex.vue) solo muestra `publicar_en_catalogo=true`, org pública o global. Con i18n (`mercado.*` en es/en).
- Admin/Leads usan el componente genérico `MarketRowForm.vue` (props `routeName`/`routeParam`/`fields` + `form.patch`). No duplicar formularios en fila.
- Los enlaces de fuentes (mobile.de/Coches.net) van en anexo interno, NUNCA públicos (regla de negocio).

## Tests

- `MarketMercadoTest`: probar lógica vía scopes del modelo (NO `assertInertia` — en Inertia v3.3.1 renderiza y da 500 sin `npm run build`).
