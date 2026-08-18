# Implementado — Ecosistema de Mercado (skills + SaaS)

> **Fecha:** 17-ago-2026
> **Alcance:** todo lo nuevo construido desde que se empezó la **skill `estudio-mercado`** y su integración con el SaaS Laravel (mapa de mercado persistente + catálogo bajo pedido + panel admin + leads).
> **Estado:** ✅ implementado y testeado (`tests/Feature/MarketMercadoTest` **10/10 verdes**).

---

## 1. Skills (Claude Desktop)

### 1.1 Nueva skill `estudio-mercado` — `.claude/skills/estudio-mercado/`

Estudio profundo del mercado de coches de 2ª mano en España y Alemania para JJ Import Motors. Genera un **mapa de mercado persistente** (`datos_mercado.json`) con estadísticas reales por categoría y modelo.

| Archivo | Contenido |
|---|---|
| `SKILL.md` | Qué resuelve, 3 capas de datos, criterios por categoría, segmentación ×4, estudios dirigidos, aprendizaje automático del mapa, bucle humano, mejoras v2 |
| `schema_datos_mercado.md` | `schema_version` 1.2, campos `slug` + `alias`, índice `marcas`, `ruta_canonica`, campos de negocio (`veredicto_fuente`, `vendibilidad`, `publicar_en_catalogo`, `foto_url`, `historial`) que el SaaS devuelve y hay que RESPETAR |
| `fuentes_datos.md` | Capa 1 pública (DGT, KBA, GANVAM, Google Trends) · Capa 2 portales (mobile.de / Coches.net) · Capa 3 pago (DAT/Schwacke, Eurotax) |

### 1.2 Endurecimiento de la skill `importacion-vehiculos`

Comunicación **bidireccional** con el mapa:
- **LEER:** todos los flujos. Flujo A (URL) usa el mapa como contexto del modelo antes de medir; Flujo B chequea el veredicto ANTES del barrido (si 🔴 hueco neto <0 avisa antes de gastar 15–50 peticiones); C/D/E en PASO 0 + FIJAR MODELOS.
- **ESCRIBIR (feedback):** al cerrar un Flujo A/B con medición real se vuelca al `datos_mercado.json` (medianas frescas + `refrescar_antes_de` +3 sem). El mapa aprende de CADA encargo.
- **FASE 0 ENTENDER** (ACK 1 línea) + dimensión intención/entregable.
- **PASO 3b FIJAR MODELOS** (lookup por slug/alias).
- **PASO 4 PLAN DE BÚSQUEDA OBLIGATORIO** (filtros, bandas, segmentación, lotes — "nunca abrir el primer portal sin el plan aprobado").
- **Criterio por categoría** (regla 1c).
- Sección **🗺️ MAPA DE MERCADO** y **auditoría de cierre** (feedback con `fuente_medicion`).
- ZIPs en `Desktop/JJImportMotors`: `importacion-vehiculos.skill.zip` + `estudio-mercado.skill.zip` (ZipArchive manual, 0 backslashes, excluye `\.(zip|skill|bak)`).

---

## 2. Base de datos (migraciones)

| Migración | Contenido |
|---|---|
| `2026_08_17_100000_create_market_models_table` | Tabla `market_models` (slug único, categoria, segmento, rango_precio, tipo_cliente, medianas DE/ES, hueco_pct/neto, coste importación, rotación, demanda, veredicto, mejor_mercado, fuente_medicion, oportunidad, publicar_en_catalogo, foto_url, vendibilidad, refrescar_antes_de, schema_version, organization_id, timestamps) |
| `2026_08_17_110000_add_segmento_to_market_models_table` | Campo `segmento` |
| `2026_08_17_120000_add_tipo_cliente_to_market_models_table` | Campo `tipo_cliente` + `tipos_cliente_secundarios` |
| `2026_08_17_130000_market_leads_and_history` | Tablas `market_leads` y `market_model_history` |
| `2026_08_17_140000_market_v2_features` | Campos v2: `veredicto_fuente` (ia/humano), `iedmt_estimado`, `confianza_precio`, `precio_desde_*`, `pendiente_fase2`, etc. |

---

## 3. Backend Laravel

### 3.1 Modelos

- **`app/Models/MarketModel.php`** — tabla `market_models`. Constantes: `CATEGORIAS`, `SEGMENTOS`, `RANGOS_PRECIO`, `TIPOS_CLIENTE` (7: impacto_showstopper, deporte_ocio, primer_coche, familia, diario_eficiencia, premium_imagen, negocio_reventa), `VEREDICTOS`, `MEJORES_MERCADOS`, `FUENTES_MEDICION`. Scopes: `verdes()`, `porCategoria()`, `porSegmento()`, `porRango()`, `porTipoCliente()` (principal + secundarios), `oportunidades()`, `publicos()`, `caducados()`, `vigentes()`, `conNegocio()`, **`deOrganizacion(?int)`** (aislamiento multi-tenant: org + globales null). Métodos: `costePuestoEnHuelva(?float)` (transporte 900 + ausfuhr 114 + itv 115 + iedmt + honorarios 1500 M2), `tendencia()` (vs último historial), `calcularVendibilidad()` (hueco 45% + rotación 20% + demanda 20% + confianza 15%).
- **`app/Models/MarketLead.php`** — tabla `market_leads` (market_model_id, organization_id, nombre, contacto, presupuesto, mensaje, nota, estado [nuevo/contactado/cerrado/perdido], origen).
- **`app/Models/MarketModelHistory.php`** — tabla `market_model_history` (mediana_de/es, hueco_pct, hueco_neto_pct, fuente_medicion, medido_el).

### 3.2 Comandos artisan

| Comando | Descripción |
|---|---|
| `market:import {--file=} {--org=} {--dry-run}` | Upsert por `slug`; **respeta `veredicto_fuente=humano`** (no lo pisa); historial solo si cambia vs última del día; vendibilidad fallback; **valida categoria/segmento/tipo_cliente** contra constantes; **dedup de alias** en `mapRow` |
| `market:export {--file=} {--org=}` | Exporta a `%USERPROFILE%\Desktop\JJImportMotors\datos_mercado.json` por defecto; incluye `historial` (últimos 5) y `veredicto_fuente` |
| `market:alerts` | Outliers (>15 pts delta vs historial) + chollos (oportunidad+verde); crea `Alert` + push OneSignal (sin N+1, usa relación cargada) |
| `market:freshness` | Reporte diario de modelos caducados |

### 3.3 Controladores y APIs

- **`app/Http/Controllers/MercadoController.php`** — `index` (catálogo público, solo `publicar_en_catalogo=true`, global o orgs públicas), `admin` (panel multi-tenant con KPIs, leads count, historial, tendencia), `update` (authorizeAccess + `veredicto_fuente=humano` al cambiar veredicto), `storeLead` (crea lead + Alert `market_lead` + push), `leads` (scoped), `updateLead` (scoped), `coste` (JSON), `reportes` (porCategoria, porSegmento, topOportunidades, evolución — scoped). Helpers `orgId()` + `authorizeAccess()`.
- **`app/Http/Controllers/Api/MarketApiController.php`** — `index` (filtros: categoria/segmento/tipo_cliente/veredicto/mejor_mercado/con_negocio/min_hueco, org vía `import_org`), `stats` (**cache 1800s** keyed `market:stats:{org}`).
- **`app/Http/Controllers/Api/PublicMarketController.php`** — `index` (catálogo público API), `stats` (**cache 1800s** `market:public-stats`).

### 3.4 Rutas

- **web** (públicas): `GET /mercado`, `POST /mercado/{model}/interes` (throttle), `GET /mercado/{model}/coste`, `GET /api/public/market`, `GET /api/public/market/stats`.
- **web** (admin, autenticadas): `GET /mercado/admin`, `GET /mercado/admin/leads`, `GET /mercado/admin/reportes`, `PATCH /mercado/admin/{model}`, `PATCH /mercado/admin/leads/{lead}`.
- **api** (bajo `import-token`): `GET /market`, `GET /market/stats`.

### 3.5 Cron (routes/console.php)

- `market:freshness` diario 06:00
- `market:alerts` diario 07:00
- `market:export` (backup diario a `storage/app/importnex/market/backup-YYYY-MM-DD.json`) diario 06:30

### 3.6 Seeder

- **`database/seeders/MarketModelSeeder.php`** — 5 modelos de muestra (Astra OPC, Golf GTI, BMW Serie 3, Fiesta, Cupra Ateca) con segmento/rango/tipo_cliente, `publicar_en_catalogo=true`, `organization_id`, vendibilidad calculada. Evita arrancar el dashboard vacío.

---

## 4. Frontend (Vue 3 + Inertia 2)

| Vista | Ruta | Contenido |
|---|---|---|
| `resources/js/Pages/Public/MercadoIndex.vue` | `/mercado` | Catálogo público "bajo pedido": filtros (categoria chips, segmento/cliente, con_negocio, comparar), cards (foto, badges, precio/mediana/hueco), modales (formulario lead "Me interesa", calculadora de coste "Puesto en Huelva", comparador), CTA a marketplace. Con i18n |
| `resources/js/Pages/Mercado/Admin.vue` | `/mercado/admin` | Panel admin: 8 KPI cards, tabla (modelo, categoría, segmento, cliente, medianas, hueco, neto, mercado, veredicto+fuente, tendencia, vendibilidad, publicar, oportunidad, refresh, form). Usa `MarketRowForm` |
| `resources/js/Pages/Mercado/Leads.vue` | `/mercado/admin/leads` | Pipeline de leads con edición de estado/nota. Usa `MarketRowForm` |
| `resources/js/Pages/Mercado/MarketRowForm.vue` | — | Componente genérico de edición en fila (props `routeName`, `routeParam`, `fields`; `useForm` + `form.patch`) |
| `resources/js/Pages/Mercado/Reportes.vue` | `/mercado/admin/reportes` | Tablas porCategoria, porSegmento, topOportunidades, evolución |

**i18n:** claves `mercado.*` añadidas en `resources/js/i18n/es.js` y `en.js`.

---

## 5. Tests

- **`tests/Feature/MarketMercadoTest.php`** — **10/10 verdes**: catálogo público solo publicados, lead se crea, lead requiere contacto (422), coste puesto en Huelva (10000 → 13129), scopes verdes/caducados, vendibilidad fallback, **aislamiento multi-tenant** (scope `deOrganizacion`), update marca veredicto humano, stats públicas, reporte respeta organización.
- **Lección clave (Inertia v3.3.1):** el trait `InteractsWithInertia` ya no existe; `assertInertia` renderiza y da 500 sin `npm run build`. Los tests de lógica se hacen vía scopes del modelo, no renderizando la vista.

---

## 6. Correcciones / decisiones clave

1. **Fórmula de hueco:** `hueco_pct` bruto `(ES−DE)/ES` (compatible con el histórico: Astra 30,9%) + `hueco_neto_pct` neto con costes (para veredicto de negocio).
2. **Multi-tenancy:** aislamiento por `organization_id` en `MercadoController` (admin/KPIs/reportes) + scope `deOrganizacion` (org + globales null). Corregido un leak de datos entre organizaciones.
3. **Multi-tenant corregido también en `storeLead`/`leads`/`updateLead`.**
4. **Historial sin duplicados:** en `market:import` solo se crea historial si cambió respecto a la última entrada del mismo día.
5. **`veredicto_fuente`:** cuando un humano edita el veredicto, el import NO lo pisa (respeto al bucle humano).
6. **Cache de stats** en ambas APIs (1800s) para no recargar la BD en el catálogo público.
7. **Zip de skills:** ZipArchive manual con `\.Replace('\','/')` + `UTF8Encoding($false)`; excluir `\.(zip|skill|bak)$`; verificar 0 backslashes y SKILL.md en raíz.

---

## 7. Estado del proyecto (17-ago-2026)

- ✅ Módulo mercado completo y testeado.
- ✅ `MarketMercadoTest` 14/14 (era 10/10; +4 de la pasada 2 de auditoría).
- ✅ Pasada 2 de auditoría aplicada (ver `AUDITORIA_MERCADO_2026-08-17_PASADA2.md`): P0 endpoints rotos, blindaje de visibilidad, dedupe de alertas, upsert scoped, validación de enums, i18n, casts, índices, cache.
- ✅ **Suite completa 100% verde: 512 passed · 2 skipped · 0 failed.** Los 11 fails que se citaban en pasadas anteriores ya no existen (arreglados en pasadas intermedias).
- ⏳ Backlog de ideas en `AUDITORIA_MERCADO_2026-08-17_PASADA2.md` §Nuevas ideas.
