# PLAN — Ecosistema de mercado JJ Import Motors (skills + SaaS Laravel)

> **Fecha:** 17-ago-2026 · **Alcance:** comunicación entre las skills `estudio-mercado` y `importacion-vehiculos` + su materialización en el SaaS Laravel.
> **Estado:** ✅ **IMPLEMENTADO (17-ago tarde)** — LOTE 1+2 de skills aplicados y empaquetados · backend Laravel operativo (tabla `market_models`, `market:import`, `GET /api/market`, `/mercado` + `/mercado/admin`, cron `market:freshness`, seeder de muestra).

---

## 1. Visión

Tener un **mapa de mercado persistente** (qué modelos merecen la pena importar DE→ES, con datos reales: oferta, precios, hueco, rotación, demanda) que:
1. Lo genera la skill `estudio-mercado` (periódico) y lo enriquece cada encargo de `importacion-vehiculos` (feedback).
2. Se consulta en Laravel para mostrar el **catálogo bajo pedido** y un **dashboard de mercado** (SaaS vendible).
3. Da **criterio de selección** a las búsquedas ambiguas (stock/marketing), que hoy era el punto ciego.

---

## 2. Estado actual (qué hay ya)

- `estudio-mercado/` — SKILL.md, schema_datos_mercado.md, fuentes_datos.md. Genera `datos_mercado.json`.
- `importacion-vehiculos/` — PASO 0 (cache), FIJAR MODELOS (PASO 3b), sección MAPA DE MERCADO (leer/escribir), checklist.
- Comunicación **bidireccional** documentada en ambas skills.
- **Falta:** endurecimiento (slugs, ruta pactada, reglas de feedback) y el **backend Laravel** (no existe aún).

---

## 3. Arquitectura de comunicación entre skills

```
┌───────────────────┐   genera/refresca   ┌──────────────────────┐
│  estudio-mercado   │ ─────────────────▶ │  datos_mercado.json  │
│  (periódico 3 sem) │                    │  (mapa persistente)   │
└───────────────────┘                    └──────────┬───────────┘
                                                    │ leer (PASO 0 / FIJAR MODELOS)
┌───────────────────┐    feedback al cerrar        │
│ importacion-      │ ◀────────────────────────────┘
│ vehiculos (A..E)  │  (Flujo A/B vuelcan medición)
└─────────┬─────────┘
          │ exportar
          ▼
┌──────────────────────────────────────────────────┐
│  SaaS Laravel (importnexcore)                     │
│  · tabla market_models · dashboard · catálogo     │
└──────────────────────────────────────────────────┘
```

**Reglas de oro:**
- `datos_mercado.json` es la **fuente de verdad de criterio** (Claude Desktop).
- Laravel es el **repositorio de visualización** (misma división de trabajo que ya usa la skill de importación: investiga Claude, muestra Laravel).

---

## 4. Plan de endurecimiento de las skills

### LOTE 1 — lo que rompe en la primera semana (prioridad ALTA)

| # | Problema real | Solución a implementar |
|---|---|---|
| **L1** | **Nombres de modelo inconsistentes** → lookup del mapa falla ("Golf GTI" vs "VW Golf 7 GTI / R" vs "golf 7 gti") | Campo `slug` canónico + array `alias` en cada entrada. Normalización unificada: minúsculas, sin tildes, `golf-7`≡`golf-vii`, quitar "vw/seat/audi" solo como alias no como clave. |
| **L2** | **Ruta ambigua del JSON** → contrato roto en silencio | Ruta pactada explícita en ambas skills (`C:\Users\jacar\Desktop\JJImportMotors\datos_mercado.json`). Si no existe → avisar "mapa no encontrado; ejecuta estudio-mercado", NO fallback silencioso. |
| **L3** | **Flujo A corrompe el mapa** (vuelca el precio de 1 unidad como mediana del modelo) | Regla de feedback diferenciado: Flujo A solo añade entrada nueva o actualiza `nota`/`enlaces`; las **medianas solo las escribe Flujo B o el estudio**. Campo `fuente_medicion` (`estudio`\|`flujo_b`\|`flujo_a`\|`flujo_e_delta`). |
| **L4** | **Feedback que nunca se ejecuta** (cierra 10 encargos y no vuelca al mapa) | Meterlo en la **auditoría de cierre** (dimensión resultado) + checklist final: "¿volqué la medición al mapa? ¿con qué fuente_medicion?" |

### LOTE 2 — lo que degrada (prioridad MEDIA)

| # | Problema real | Solución |
|---|---|---|
| **L5** | **Modelo en 2 categorías** → entrada duplicada | Un modelo vive en UNA categoría principal + `categorias_secundarias: []`. |
| **L6** | **Mapa vs usuario** (mapa dice 🔴, usuario lo quiere igual) | Regla "el mapa asesora, el usuario decide": avisar en 1 línea y ejecutar. Nunca bloquear. |
| **L7** | **Mapa parcial** (3 categorías pedidas, 2 estudiadas) | `refrescar_antes_de` **por categoría** (no solo global) + regla "categoría sin datos → declarar y ofrecer delta". |
| **L8** | **Fuentes prometidas sin campo en el esquema** (DGT/KBA no se guardan nunca) | Añadir `transferencias_mes_dgt`, `matriculaciones_kba`, `fuente_estadistica` + `schema_version` en el JSON. |
| **L9** | **Rotación ES difícil de medir** (AutoUncle solo DE; Coches.net no la muestra clara) | `rotacion_dias_de` / `rotacion_dias_es` separados + `rotacion_fuente`; null documentado es válido. |

### EVOLUCIÓN (cuando lo anterior funcione)

| # | Idea | Detalle |
|---|---|---|
| **E10** | Sesiones paralelas escribiendo el JSON | Regla: "releer el JSON justo antes de escribir" (merge por `slug`, no sobrescribir todo). |
| **E11** | Estudio partido por sesiones | 60-80 peticiones no caben en una sesión → FASE 0 propone partir por categoría con checkpoint (como Flujo E). |
| **E12** | Mini-refresco pragmático | Si en un Flujo E un modelo clave caducó, importación refresca SOLO ese modelo (1-2 lecturas) y marca `fuente_medicion: flujo_e_delta`. |
| **E13** | Exportar mapa a Laravel | `laravel/export/datos_mercado.json` → consumo por el SaaS. |
| **E14** | Informe de mercado con plantilla | Secciones: resumen por categoría, top 🟢, movimientos vs estudio anterior. |

---

## 5. Plan SaaS Laravel (backend)

### 5.1 Modelo de datos — tabla `market_models`

| Campo | Tipo | Nota |
|---|---|---|
| `id` | bigint PK | |
| `slug` | string unique | clave canónica (L1) |
| `categoria` | enum | `showstoppers` \| `alta_rotacion` \| `gemas_economicas` |
| `categorias_secundarias` | json | L5 |
| `modelo` / `version` | string | nombre de mercado |
| `aliases` | json | array de alias (L1) |
| `oferta_de` / `oferta_es` | int nullable | |
| `mediana_de` / `mediana_es` | int nullable | |
| `precio_desde_de` / `precio_desde_es` | int nullable | |
| `sello_precio_de` / `sello_precio_es` | string nullable | |
| `hueco_pct` / `hueco_neto_pct` | float nullable | bruto / neto |
| `coste_importacion_estimado` | int nullable | |
| `rotacion_dias_de` / `rotacion_dias_es` | int nullable | L9 |
| `demanda_trends` | string nullable | creciente/estable/decreciente |
| `transferencias_mes_dgt` / `matriculaciones_kba` | int nullable | L8 |
| `veredicto` | enum | verde/amarillo/rojo |
| `mejor_mercado` | enum | DE/ES/paridad |
| `fuente_medicion` | enum | estudio/flujo_b/flujo_a/flujo_e_delta |
| `nota` | text nullable | |
| `tasacion_pro` | int nullable | Capa 3 |
| `refrescar_antes_de` | date | caducidad |
| `schema_version` | string | evolucionar sin romper (L8) |
| `timestamps` | | |

### 5.2 Importación del JSON

- Comando artisan: `php artisan market:import {ruta.json}` → valida contra el schema, upsert por `slug`, guarda `schema_version`.
- Opcional endpoint interno `POST /api/market/import` (con token) para automatizar desde Claude Desktop.
- Validación: rechazar entradas sin `slug` o sin `hueco_pct` numérico; loguear las que no pasan.

### 5.3 Consulta / API

- `GET /api/market` → listado con filtros: `categoria`, `veredicto`, `mejor_mercado`, `min_hueco`, orden por `hueco_pct`.
- Recurso JSON con los mismos campos que el mapa (sin traducción, para coherencia).
- Scope de modelo: `verdes()`, `porCategoria()`, `caducados()`.

### 5.4 Dashboard de mercado (vista web)

- Vista pública `/marketplace` o `/mercado`: catálogo bajo pedido que consume `market_models` (hoy está estático — pasa a ser dinámico).
- Tarjetas por modelo: mediana, hueco, veredicto (badge 🟢/🟡/🔴), "bajo pedido desde X €", sin enlaces públicos (los enlaces van en anexo interno, NO públicos — regla de negocio).
- Panel admin: tabla completa + edición de `nota`/`veredicto` manual.

### 5.5 Cadencia / cron

- `market:check-freshness` diario → marca modelos con `refrescar_antes_de` vencido (alerta visual "estudio caducado").
- Notificación opcional: cuando un modelo pasa a 🟢 o aparece un chollo (precio_desde muy por debajo de la mediana) → email/panel (futuro, Onesignal ya está en el stack).

---

## 6. Ideas adicionales detectadas (detalladas)

1. **Trazabilidad/auditoría del mapa.** Guardar historial de cambios por slug (`market_model_logs`: fecha, qué campo cambió, valor viejo→nuevo, fuente). Permite ver tendencias y detectar datos raros.
2. **IEDMT estimado por segmento en el mapa.** El estudio puede calcular un IEDMT estimado por tramo de CO₂ y guardarlo (`iedmt_estimado`), para que el `hueco_neto_pct` sea automático sin depender de la unidad concreta (hoy se hace "estimado por segmento" a mano).
3. **Confianza del dato.** Distinguir precio de anuncio (baja confianza) vs tasación pro Capa 3 (alta) con un campo `confianza_precio` (1-5). El veredicto puede exigir confianza mínima para 🟢.
4. **Cadencia variable por categoría.** Showstoppers rotan rápido (refrescar 2 sem); gemas más estables (4 sem). `refrescar_antes_de` por modelo, no una cadencia global.
5. **Integración con el panel web existente** (`sync_web_data.py`, `coches.json`). El mapa podría alimentar una sección "Mercado" del panel actual, no una vista nueva aislada.
6. **Alertas de oportunidad.** Regla: si `precio_desde_de` está >15% por debajo de `mediana_de` y `veredicto` verde → marcar `oportunidad: true` y avisar. Es el "chollo escondido" automatizado.
7. **Modelo de búsqueda sugerida.** El mapa puede guardar la **query re-ejecutable** por portal (la que usó el estudio) para que el refresco delta no tenga que redescubrir filtros.
8. **Comparativa ES vs DE en el dashboard.** Columna visual del hueco (barra) + toggle "solo con negocio (neto >0)" para que el usuario filtre rápido.
9. **Internacionalización.** El catálogo público debe salir en es/en (el SaaS ya tiene `es` defecto + `en`). Las etiquetas de categoría y veredicto se traducen.
10. **Seed inicial del mapa.** Un seeder que cargue un `datos_mercado.json` de muestra para no arrancar el dashboard vacío y poder desarrollar la vista sin depender del primer estudio real.

---

## 7. Orden de ejecución sugerido

1. **LOTE 1 de skills** (L1-L4): slugs, ruta pactada, reglas de feedback, auditoría de cierre.
2. **LOTE 2 de skills** (L5-L9): categorías únicas, mapa-asistencia-no-bloqueo, refresco por categoría, campos DGT/KBA + schema_version, rotación separada.
3. **Backend mínimo Laravel**: migración `market_models` + comando `market:import` + recurso API.
4. **Dashboard**: vista de catálogo dinámico + panel admin.
5. **Cron + logs + seed**.
6. **Evolución** (E10-E14 + ideas 6.1-6.10) a demanda.

---

## 8. Decisiones (confirmadas 17-ago-2026)

- [x] **Ruta de `datos_mercado.json`**: `C:\Users\jacar\Desktop\JJImportMotors\datos_mercado.json` (ruta canónica en ambas skills, L2).
- [x] **Dashboard**: vista nueva `/mercado` (catálogo público) + `/mercado/admin` (panel admin) con modelo nuevo `MarketModel` (no duplica `scouting_mercado`/`modelos_mercado`, que quedan como histórico del chat).
- [x] **Importación**: manual con `php artisan market:import --file=...` (igual que los zips de unidad de vehículo). Sin endpoint con token por ahora.
- [x] **Cadencia variable por categoría**: showstoppers +2 sem · rotación +3 · gemas +4 (campo `refrescar_antes_de_categoria`).

## 9. Re-auditoría posterior (por hacer)

- [ ] Validar `/mercado` en navegador (diseño + datos del seeder).
- [ ] `php artisan test` de los flujos nuevos.
- [ ] Probar `market:import` con el primer `datos_mercado.json` real de Claude Desktop.
- [ ] Revisar el `Admin.vue` (el patrón de edición en tabla admite refactor a componente).
