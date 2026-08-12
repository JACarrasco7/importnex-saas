# Changelog

Todos los cambios notables en el skill `importacion-vehiculos` se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [2.4.1] - 2026-08-12 — Guía de uso para usuarios finales

### 📚 docs/guias/
- `README.md` — índice + diagrama de flujo del negocio (mermaid).
- `01-primeros-pasos.md` — arranque, verificación sync, token budget.
- `02-flujo-a-unidad.md` / `03-flujo-b-modelo.md` / `04-flujo-c-mercado.md`.
- `05-informes.md` — leer informes + briefing PDF.
- `06-cierre-venta.md` — registrar cierres (curl) + KPIs en `/kpis`.
- `07-solucion-problemas.md` — FAQ y troubleshooting.
- `ejemplos/` — casos reales Astra OPC + Tiguan (Flujo A), Golf GTI + scouting (B/C).

---

## [2.4.0] - 2026-08-12 — Auditoría 3 (16 hallazgos, 100% resueltos)

### 🟠 Alto
- **#1 Multi-tenant ScoutingMercado** — `scouting_id` ahora unique PER organización (migración `2026_08_12_110000_make_scouting_unique_per_organization`). `storeMercado()` hace upsert scoped por org. Test de colisión entre 2 orgs.
- **#2 Enlace dashboard KPIs** — `cars.show` ahora usa `car_id` (numérico) en vez de `coche_id` (slug), con fallback a texto plano.
- **#3 Fechas en cierres** — `storeCierre()` valida formato `YYYY-MM-DD` (422 en vez de 500). Tests de fechas inválidas.

### 🟡 Medio
- **#4 Validaciones negocio→422** — `RuntimeException` de `ValuationImporter::apply()` se responde como 422, no 500.
- **#6 attachBriefing org** — verifica que el coche pertenece a la org autenticada (404 si no).
- **#7 Flujo C lectura** — nuevo `GET /api/scouting` (listado por org con modelos).
- **#8 down() scouting** — migración original ahora dropea `modelos_mercado` antes que `scouting_mercado`.
- **#9 Tests huecos** — `KpiCalculatorTest` (5 tests), aislamiento multi-org en `/api/kpis`, fechas/veredicto inválidos en cierres, colisión scouting_id, `index_scouting`.
- **#10 golden-tests** — §5.1/§5.2 actualizados (B/C implementados, `co2_confirmado` validado).

### 🟢 Bajo
- **#11 DRY token** — nuevo middleware `import-token` centraliza auth (X-Import-Token + org) en los 9 endpoints del puente.
- **#12 Índice plataforma** — migración `2026_08_12_120000_add_plataforma_index_to_cierres_table`.
- **#13 Veredicto validado** — `storeCierre()` valida contra valores del contrato (422 si no).
- **#14 Throttle** — `POST /api/import-valuation` movido a `throttle:api-write`.
- **#15 Skill docs** — sección "KPIs en Laravel (endpoint + dashboard)" en operaciones_cierre.md.
- **#16 use Schema** — añadido import en `EnrichedValuationMigrationTest` (limpia linter).

---

## [2.3.0] - 2026-08-12 — Fixes auditoría integral (17 hallazgos)

### 🔴 Críticos corregidos
- **#1 IEDMT**: coeficientes Anexo IV extraídos a `config/iedmt.php` (single source of truth). Corregidos 5 valores incorrectos en `Car::calculateIEDMT()` (año 10: 14% → 17%, etc.). Añadido `tests/Unit/IedmtCalculationTest.php` (7 tests).
- **#2/#9 Relación Cierre↔Car**: `brand`/`model` denormalizados en tabla `cierres` (migración `2026_08_12_100000_add_brand_model_to_cierres_table`) + poblados en `storeCierre()`. Eliminada búsqueda por `slug` inexistente. El filtro por marca en dashboard ya funciona.

### 🟠 Rendimiento y mantenibilidad
- **#3 N+1**: eager load `with('car')` en KpiController.
- **#4 Bucle tendencia**: unificado en `KpiCalculator::historico()` (N meses, clamp 1-24).
- **#5 Duplicación KPIs**: nuevo `app/Services/KpiCalculator.php` — fuente única para `KpiController` (web) y `ImportValuationApiController::kpis()` (API).
- **#6 Test flaky**: fechas distintas en fixtures de `KpiControllerTest`.

### 🟡 Robustez
- **#8 schema_version**: `ValuationImporter` guarda la versión real del payload, no hardcodeada.
- **#11 Tests `attachBriefing`**: nuevo `BriefingPdfApiTest` (5 casos: PDF válido, no-PDF, >10MB, sin archivo, sin token).

### 🟢 Documentación
- **#12 Mapeo `valoracion`→`rating`**: documentado en contrato.md (intencional: Car normaliza, Cache guarda crudo).
- **#13 `boe_confirmed`**: documentado (no activo hoy; solo futuro BOE).
- **#15 ROADMAP**: sección "Lado Laravel (completado)" añadida.

### ⚪ Verificados (falsos positivos de auditoría)
- **#7 Throttles**: `api-read`/`api-write`/`api-heavy` YA definidos en `AppServiceProvider` (RateLimiter::for). Sin acción.
- **#10 CHANGELOG**: ya existe (este archivo). Sin acción.

---

## [2.2.0] - 2026-08-12 — Contador token budget y dashboard KPIs

### 🔢 §2.4 — Contador de peticiones (tracking manual)
- **Nueva sección** `### Contador de peticiones (§2.4)` en `SKILL.md` tras Token budget
- Contador por fuente: mobile.de X/45 (avisar a 35), AutoScout24 X/36, Coches.net X/35, resto X/20
- Reglas por flujo: A máx 70 (avisar 35/56), B máx 50 (avisar 25/40), C máx 100 (avisar 50/80)
- Regla dura: NUNCA >45 peticiones a mobile.de en una sesión
- Si se supera el budget sin veredicto → STOP + resumen parcial + decidir PARCIAL

### 📊 §3.8 — Dashboard KPIs en Laravel (frontend)
- **`app/Http/Controllers/KpiController.php`** (nuevo, invokable)
- **`resources/js/Pages/Kpis/Index.vue`** (nuevo)
- **Ruta** `GET /kpis` (`kpis.index`) bajo `['auth', 'verified', 'organization']`
- 4 KPIs por periodo con navegación mes a mes: precisión de veredictos (≥80%), tiempo hasta venta (≤15d), desviación de precio (≤5%), falsos positivos (≤20%)
- Tendencia de precisión últimos 6 meses + tabla de cierres con desviación y estado
- Cards con semáforo verde/ámbar/rojo según objetivo; colores marca (`estoril-700`)
- Enlace `KPIs` añadido al menú lateral (grupo Inventario) con clave i18n `nav.kpis` (es/en)
- Requiere la migración `2026_08_12_092939_create_cierres_table` aplicada en producción

---

## [2.1.0] - 2026-08-12 — Documentación §3.7 y refactor §2.3

### ✅ §3.7 — Documentación `verify_desktop_sync.py`
- **Nueva sección** `## ✅ Verificación de sincronización Desktop (ARRANQUE)` al inicio de `operaciones.md`
- Documenta comando (`py .claude/skills/.../verify_desktop_sync.py`), qué verifica (12 scripts + 2 datos), output exitoso y output con faltantes
- Integra con flujo Claude: ejecutar **siempre** al inicio de sesión antes de leer `indice.json` o invocar `franja.py`
- Exit code 0 = sesión OK; exit code ≠ 0 = NO arrancar

### 📐 §2.3 — Single source of truth IEDMT
- `contrato.md` §`costes`: el bloque IEDMT ahora referencia [`costes.md §IEDMT`](costes.md#-iedmt-orden-hac15012025-vigor-1-ene-2026) en lugar de duplicar la fórmula
- `iedmt_metodologia` redefinido: cadena corta con PVP/antigüedad/CO₂/cifras resultantes (ejemplo real), sin desglose de fórmula
- Single source of truth mantenida en `costes.md` (Orden HAC/1501/2025)

---

## [2.0.0] - 2026-08-12 — Auditoría completa y hardening del skill

### 🛡️ Seguridad multi-tenant (§10.3, §10.5)
- **Migración** `2026_08_12_090058_add_organization_and_soft_deletes_to_investigation_cache_table.php`:
  - Añade `organization_id` (foreign key) a `investigation_cache`
  - Añade `deleted_at` (soft deletes)
  - Cambia índice único a compuesto `(organization_id, clave_modelo)`
- **Modelo** `InvestigationCache`:
  - Trait `SoftDeletes`
  - Relación `organization(): BelongsTo`
  - Método `aspectosCaducados()`

### 🎯 Endpoints robustos (§10.1, §10.2, §10.6, §10.7)
- **`attachBriefing()`** implementado (antes la ruta existía pero el método no — 500 error)
- **`storeInvestigationCache()`** y **`getInvestigationCache()`** scoped por organización
- **`validate()`** refactorizado con nueva firma: `validate($payload, $requiredBlocks, $expectedFlujo)`
- Mensaje de error mejorado en GET sin parámetros (muestra qué parámetros faltan + ejemplo)
- Validación de schema_version + flujo + bloques mínimos en los 3 endpoints (A, B, C)

### ✅ Validaciones de negocio (§3.1, §3.2, §3.3)
- **`co2_confirmado: false`** → warning automático en avisos
- **Comparables sin URL** → filtrados con aviso "{N} comparables descartados"
- **`precio_objetivo` obligatorio** cuando recomendación es "Comprar si baja de precio"

### 📐 Consistencia del skill (§1.1)
- **Umbrales Nicho unificados**: 10% objetivo, 8% mínimo (EXIT 3)
- Comportamiento entre 8-10% documentado: "margen justo, posible si vendibilidad ≥70"

### 📊 Modelo InvestigationCache completado (§1.2)
- Añadidos aspectos `precio_mercado => 18 meses` y `otros => 24 meses`
- Total: 9 aspectos con caducidades definidas

### Tests
- **61 tests pasando (231 aserciones)**
- 3 nuevos tests de validación de negocio (3.1, 3.2, 3.3)
- 10 tests unitarios de UrlNormalizer (§2.1)
- 10 tests de CierreApi (§3.5)
- 3 tests de mapeo drivetrain (§3.4)
- Tests actualizados para reflejar validaciones multi-tenant

### 🚗 Mapeo `traccion` → `drivetrain` (§3.4)
- **Nueva columna** `drivetrain` en tabla `cars` (después de `transmission`)
- **Nuevo mapa** `DRIVETRAIN_MAP` en `ValuationImporter` con traducciones español/inglés/alemán → valores canónicos `FWD`/`RWD`/`AWD`
- **3 tests** cubriendo tracción delantera, total/AWD y ausencia del campo
- **Modelo Car**: `drivetrain` añadido a `$fillable`
- **Migración**: `2026_08_12_093456_add_drivetrain_to_cars_table.php`

### 📊 Endpoint `/api/cierres` (§3.5)
- **Nuevo modelo** `Cierre` con SoftDeletes, scopes (`periodo`, `vendidos`, `veredictoPositivo`) y helpers (`desviacionPorcentaje`, `calcularDiasHastaVenta`)
- **Nueva migración** `2026_08_12_092939_create_cierres_table.php` con índices para queries KPI
- **POST `/api/cierres`** — registra cierre de venta (o no-venta) con cálculo automático de días y desviación
- **GET `/api/cierres`** — lista cierres con filtros (periodo, estado, veredicto_positivo) y KPIs agregados (precisión, tiempo medio, desviación, falsos positivos)
- Prerrequisito completado para §3.8 (dashboard KPIs)

### 🔧 Optimización §2.1 — UrlNormalizer helper
- **Nueva clase** `app/Support/UrlNormalizer.php`
- Métodos: `normalize(?string $url): ?string` y `same(?string $url1, ?string $url2): bool`
- Extraída lógica duplicada de `ValuationImporter::resolveCar()`
- 10 tests unitarios cubriendo edge cases

### 📐 Documentación (§1.4, §1.5)
- **§1.4 — Single source of truth de costes fijos:** SKILL.md ahora referencia costes.md en lugar de duplicar valores
- **§1.5 — Filtro de competencia:** Documentado cuándo aplicar (Fase 2 de Flujo A, después de recolectar las 3 fuentes ES)

### Archivos modificados
- `database/migrations/2026_08_12_090058_*.php` (nueva — investigation_cache multi-tenant)
- `database/migrations/2026_08_12_092939_create_cierres_table.php` (nueva — §3.5)
- `database/migrations/2026_08_12_093456_add_drivetrain_to_cars_table.php` (nueva — §3.4)
- `app/Models/InvestigationCache.php`
- `app/Models/Cierre.php` (nuevo — §3.5)
- `app/Models/Car.php` (drivetrain añadido a fillable — §3.4)
- `app/Http/Controllers/Api/ImportValuationApiController.php`
- `app/Services/ValuationImporter.php`
- `app/Support/UrlNormalizer.php` (nuevo — §2.1)
- `routes/api.php`
- `.claude/skills/importacion-vehiculos/SKILL.md`
- `.claude/skills/importacion-vehiculos/comparables.md` (§1.5)
- `tests/Feature/ValuationImporterTest.php` (+3 tests §3.4)
- `tests/Feature/ModeloImportTest.php`
- `tests/Feature/ScoutingMercadoImportTest.php`
- `tests/Feature/InvestigationCacheTest.php`
- `tests/Feature/CierreApiTest.php` (nuevo — §3.5)
- `tests/Unit/UrlNormalizerTest.php` (nuevo — §2.1)
- `tests/Feature/fixtures/chat_report_example.json`

### Documentación
- `docs/auditoria-skill-2026-08-12.md` — 12 secciones con 43 items detectados + 11 críticos resueltos

## [1.13.0] - 2026-08-11

### Sprint G, H e I completados (25/26 mejoras, 96%)

#### Added
- **Registro de cierre** (#15): Estructura JSON para tracking de cierres reales vs estimados en `datos/registro_cierres.json`
- **KPIs del skill** (#16): 4 métricas clave (precisión de veredictos, tiempo hasta venta, desviación de precio, tasa de falsos positivos)
- **Changelog formal** (#17): Este archivo, versionado según Semantic Versioning
- **Sincronización Desktop** (#20): Script `verify_desktop_sync.py` para verificar que los scripts referenciados existen en Desktop

#### Fixed
- Documentación completa de las 5 mejoras pendientes de Sprints G, H e I
- SKILL.md ahora incluye secciones operativas para registro de cierres, KPIs y sincronización

### Notas
- Solo falta #21 (caché de investigación en Laravel) que requiere backend
- Progreso global: 25/26 mejoras (96%)
- SKILL.md: 485 líneas (+118 desde v1.12.0)

## [1.12.0] - 2026-08-11

### Sprint E completado (21/26 mejoras, 81%)

#### Added
- **Priorización por ROI** (#2): Scoring automático con fórmula `MargenEstimado × VendibilidadEstimada × Urgencia`
- **Comparable sin muestra** (#3): 3 métodos en cascada (Normal → Ampliado → Cualitativo)
- **Deduplicación** (#4): Huella normalizada `(año, km±2%, cv, precio±3%, combustible)` para no contar 2 veces el mismo coche

### Changed
- SKILL.md: 367 líneas (+47 desde v1.11.0)
- Sección "Fases" ahora incluye prioridad por ROI y deduplicación
- Sección "Comparable" ahora incluye método sin muestra

## [1.11.0] - 2026-08-11

### Fase 11: Endpoint B (MODELO) implementado

#### Added
- Método `storeModelo()` en `ImportValuationApiController`
- Ruta `POST /api/import-modelo` para Flujo B
- 5 tests con 13 aserciones para endpoint B
- Validación de `_meta.flujo = "B"` y eliminación automática de bloque `publicidad`

### Fixed
- Cierre completo de I14 (inconsistencia endpoints B/C Laravel)
- Todos los endpoints (A, B, C) ahora implementados y probados

### Changed
- docs/analisis-skill-importacion-vehiculos.md actualizado con changelog v1.6.0

## [1.10.0] - 2026-08-11

### Fase 10: Endpoint C (MERCADO) implementado

#### Added
- Migración `2026_08_11_205511_create_scouting_mercado_table.php`
- Tablas `scouting_mercado` y `modelos_mercado`
- Modelos `ScoutingMercado` y `ModeloMercado`
- Método `storeMercado()` en `ImportValuationApiController`
- Ruta `POST /api/import-mercado` para Flujo C
- 10 tests con 30 aserciones para endpoint C

### Fixed
- Cierre parcial de I14 (endpoint C implementado, B pendiente)

## [1.9.0] - 2026-08-11

### Fase 9: Mejoras #22 y #23

#### Added
- **Token budget consciente** (#22): Tabla de peticiones por flujo (A: 70, B: 50, C: 100)
- **Dimensiones de atractivo** (#23): 4 categorías para Flujo C (pasionales, premium, económicos, eco)

### Fixed
- Conversión de `EnrichedValuationTest.php` de Pest a PHPUnit (10 tests, 3 pasan, 7 skipped)

### Changed
- SKILL.md: 320 líneas (+18 desde v1.8.0)

## [1.8.0] - 2026-08-11

### Fase 8: Golden tests reales

#### Added
- `docs/golden-tests/README.md` con 6 informes reales (2 modelos, 3 veredictos)
- Casos: Astra OPC 2012/2013 (Comprar), Tiguan 1.4/1.5 TSI ×4 (Descartar)
- Validación de edge cases: <5 comparables ES, margen negativo

### Fixed
- Cierre de Mejora #19 (golden tests) y Deuda D3

## [1.7.0] - 2026-08-11

### Fase 7: Consolidación del documento de análisis

#### Changed
- docs/analisis-skill-importacion-vehiculos.md: 1299 → 741 líneas (-43%)
- Estructura: Histórico (§3-6, 10) + Activo (§11-12) + Referencia (§0-2, 7-9)
- Eliminadas redundancias entre secciones 10-14 y 15-17

## [1.6.0] - 2026-08-11

### Fase 6: Refinamiento final

#### Added
- anti_patrones.md separado (88 líneas) con detalle de las 6 reglas duras
- Criba diferenciada Fase 1 (soft) vs Fase 2 (dura)
- Scripts deprecados marcados con ❌
- Caché de investigación formalizado en operaciones.md
- Endpoints B/C avisados como pendientes

### Fixed
- I8 (contradicción hidratación), I9 (criba por fase), I10 (scripts deprecados), I12 (caché formalizado)

## [1.5.0] - 2026-08-11

### Fase 5: JSON distintos por flujo

#### Added
- Estructura JSON diferenciada para Flujos A, B y C en contrato.md
- Campo `iedmt_sin_minoracion` para verificación fiscal
- URLs obligatorias en comparables
- Endpoints separados (A implementado, B/C pendientes)

## [1.4.0] - 2026-08-11

### Fase 4: Operaciones refinadas

#### Added
- Google Drive movido a backup-only en operaciones.md
- Scripts organizados por flujo
- Carpetas de trabajo documentadas
- Flujo diario por flujo (A/B/C)

## [1.3.0] - 2026-08-11

### Fase 3: Extractores mejorados

#### Added
- extractores.md con cobertura por flujo/fase
- Fix bug `__S` (año 2026 hardcoded → `getFullYear()+1`)
- Edge cases documentados (mobile.de bloqueado, NL/BE/LU, checkpoint "no", CHF)

### Fixed
- I1 (extractores sin fases), I4 (año 2026), I5 (obligatoriedad mal), D1 (bug __S)

## [1.2.0] - 2026-08-11

### Fase 2: Contrato JSON formalizado

#### Added
- contrato.md con estructura completa del JSON
- Formato esqueleto [BLOQUE] documentado
- Datos de marca integrados
- Tabla de 9 aspectos de investigación

### Fixed
- I2 (IEDMT), I3 (cobertura), I6 (JSON Flujo C)

## [1.1.0] - 2026-08-11

### Fase 1: Refactorización inicial

#### Added
- 3 flujos (A/B/C) con detección automática
- 2 fases con 3 early exits
- 6 anti-patrones bloqueados (A1-A6)
- ZIP cristalino para Laravel
- Referencia rápida al inicio del SKILL.md

#### Changed
- SKILL.md: 827 → 320 líneas (-61%)
- 4 archivos modulares: extractores.md, contrato.md, operaciones.md, anti_patrones.md

### Fixed
- F1 (3 flujos), F2 (detección), F3 (3 informes), T1 (2 fases), T2 (delta updates)
- A1+A2 (anti-patrones 6), C2 (teléfono incorrecto 667→675)
- I2 (IEDMT), I3 (cobertura), I6 (JSON Flujo C), I7 (extraer anti-patrones)

## [1.0.0] - 2026-08-10

### Versión inicial

#### Added
- SKILL.md monolítico (827 líneas)
- Sistema de 7 fuentes (3 ES + 4 DE)
- Investigación de 9 aspectos
- Comparable con 9 claves y ajuste línea a línea
- IEDMT con minoración art.69
- Matriz de decisión (vendibilidad × margen)

### Known Issues
- Código JS inline (~100 líneas)
- Sin contrato JSON formal
- Sin formato esqueleto documentado
- Sin datos de marca
- Color inconsistente (#0B1F3A vs #1A306D)
- Teléfono incorrecto (667 vs 675)
