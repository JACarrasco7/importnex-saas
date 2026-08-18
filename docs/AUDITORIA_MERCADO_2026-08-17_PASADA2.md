# Plan de auditoría — Ecosistema Mercado/Scouting (17-ago-2026)

> **Segunda pasada de auditoría** tras la primera implementación. Detecta bugs, inconsistencias, optimizaciones y nuevas ideas. Priorizado por ROI.

---

## Resumen ejecutivo

El módulo mercado estaba bien estructurado y testeado a nivel de modelo, pero tenía **dos bugs bloqueantes** (endpoints del puente chat→SaaS rotos por pasar un objeto `Organization` donde se espera `int`, y fuga de pricing/leads en rutas públicas) más varias inconsistencias y optimizaciones.

---

## ✅ Aplicado en esta pasada

### P0 — Bloqueante

1. **`MarketApiController` — 500 en `/api/market` y `/api/market/stats`**
   - `ImportToken` inyecta `import_org` como objeto `Organization`, pero el controlador lo usaba como `int`.
   - Fix: `$request->attributes->get('import_org')?->id`.
   - Tests añadidos: `test_api_market_lista_modelos_de_la_org`, `test_api_market_stats_devuelven_agregados_de_la_org`.

2. **Fuga de pricing/leads en rutas públicas**
   - `GET /mercado/{id}/coste` y `POST /mercado/{id}/interes` aceptaban modelos no publicados u ocultos.
   - Fix: nuevo helper `abortIfOculto()` → 404 si `publicar_en_catalogo=false` o la org no es pública/global.
   - Tests: `test_coste_oculto_para_modelo_no_publicado`, `test_lead_rechazado_para_modelo_no_publicado`.

### P1 — Importante

3. **`market:alerts` re-alertaba chollos a diario** sin deduplicar.
   - Fix: dedupe en `notify()` — si ya existe un `Alert` activo (mismo tipo+referencia, `resolved_at` null), no se repite.

4. **Upsert de `market:import` por `slug` global** (reasignaba modelos cross-org).
   - Fix: si el slug ya existe en OTRA org (no global), se salta con warning, no se reasigna.

5. **`veredicto`/`mejor_mercado`/`rango_precio` sin validar** en el import.
   - Fix: validación contra `MarketModel::VEREDICTOS`/`MEJORES_MERCADOS`/`RANGOS_PRECIO` en el bucle, como ya se hacía con categoria/segmento/tipo_cliente.

6. **i18n muerto en modales de `MercadoIndex.vue`** (texto español hardcodeado).
   - Fix: `$t('mercado.lead_*')`, `$t('mercado.enviar')`, `$t('mercado.cancelar')`, `$t('mercado.coste_title')`, `$t('mercado.calculando')`.

7. **Casts legacy `$casts`** en `ScoutingMercado` y `ModeloMercado`.
   - Fix: unificado a método `casts()` (convención Laravel 11 del proyecto).

### P2 — Optimizaciones

8. **`withoutOverlapping()`** en `market:freshness` y `market:alerts` (routes/console.php).
9. **Cache de stats invalidada** al importar (`market:import` hace `Cache::forget` de `market:public-stats` y `market:stats:*`).
10. **`storeMercado` devuelve 201/200** según `wasRecentlyCreated` (antes siempre 201).
11. **`indexScouting` reporta `total` real** (antes era el tamaño de la página limitada).
12. **Migración de índices** `2026_08_17_150000_market_models_indexes.php` para `(organization_id, categoria)`, `segmento`, `rango_precio`, `tipo_cliente`, `veredicto`.
13. **Tests de comandos/endpoints** añadidos en `MarketMercadoTest` (14/14 verdes) y `ScoutingMercadoImportTest` actualizado (12/12 verdes).

---

## 💡 Nuevas ideas propuestas (no implementadas — backlog)

1. **Detalle SEO por slug** (`/mercado/{slug}`): página de detalle con galería, historial de hueco y CTA. Mejora SEO y conversión.
2. **Alertas de precio-objetivo para leads**: cruzar leads con `market:import`; al bajar de precio o pasar a `verde`, notificar automáticamente.
3. **Export CSV de leads** (reutilizar `maatwebsite/excel` ya instalado) para CRM externo.
4. **Feed del catálogo** (JSON/XML) para Google Merchant o agregadores.
5. **Mapa visual de hueco por segmento** (heatmap categoría × segmento) en `Reportes.vue`.
6. **Comparador persistente en URL** (`?compare=slug1,slug2`).
7. **Webhook del chat para import automático**: endpoint que reciba `datos_mercado.json` y dispare `market:import` sin ejecutar el comando manualmente.
8. **Paginación en `MarketApiController`** y caché del listado público (hoy solo `stats` cachea).

---

## Estado final

- `MarketMercadoTest`: **14/14 verdes** (era 10/10).
- `ScoutingMercadoImportTest`: **12/12 verdes**.
- Suite completa: **512 passed · 2 skipped · 0 failed** (los 11 fails que se citaban en pasadas anteriores ya no existen — arreglados en pasadas intermedias).
