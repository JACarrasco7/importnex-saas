# Changelog

Todos los cambios notables en el skill `estudio-mercado` se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [0.3.4] - 2026-08-23 — Plantilla informe: suelo de listado vs verificado

- **`informe_mercado.md`**: nueva nota "🏷️ Fiabilidad de cada suelo" — cada precio lleva ✅ (verificado en ficha) o 👁️ (de listado, pendiente). El suelo oficial es el ✅ más bajo; los 👁️ se anotan aparte como posibles suelos inferiores.
- Encaja con el método oficial de Coches.net por URL (importacion-vehiculos v3.3.4).

## [0.3.3] - 2026-08-23 — Plantilla de informe de mercado al grano + regla MD vs PDF

> **Motivo:** el informe de estudio (ej. Golf 7.5) se generaba con estructura ad-hoc: 10 secciones metodológicas, la conclusión al final, candidatos "a ver" escondidos en tablas largas y duplicación MD+PDF (enlaces muertos en PDF).

### 📄 Informe
- **NUEVO `informe_mercado.md`** — plantilla obligatoria del informe de estudio, estructurada como documento de decisión (1 minuto de lectura):
  1. 🏁 CONCLUSIÓN (resumen + tabla por variante con hueco bruto/neto + veredicto) — lo primero
  2. 🎯 CANDIDATOS A VER (1-2 por variante, URL visible completa, por qué merece la pena)
  3. 📊 POR VARIANTE (3-5 líneas c/u; la segmentación solo si afecta al precio)
  4. ⚠️ AVISOS/TRAMPAS (solo las que cambian la decisión)
  5. 📋 COBERTURA/METODOLOGÍA (al final, no al principio)
- **Regla MD vs PDF:** SIEMPRE Markdown (fuente, enlaces clicables). PDF solo si el usuario lo pide explícitamente; en el PDF la URL va visible porque los enlaces no funcionan. NUNCA MD+PDF por defecto.
- **SKILL.md**: §Output referencia la plantilla obligatoria + §MEJORAS #1 actualizada.

## [0.3.2] - 2026-08-21 — Fixes de auditoría (C1-C4 + medios)

- **schema**: estado `estudiando` añadido al enum (sesión corta/interrumpida) · regla 6 de merge de la cola entre sesiones (E10, solo tocar lo de esta sesión) · `estado_cola` degradado a ⚠️ (fuente de verdad = `cola_trabajo.estados`) · ejemplo de `cola_trabajo` corregido (`cupra-leon=pendiente_estudio`).
- **SKILL.md**: L3 ampliado (medianas las escriben estudio/flujo_b/**mini_estudio**) · §Métricas #13 enum con `mini_estudio`.

## [0.3.1] - 2026-08-21 — Refinado tras dry-run: transiciones por fuente + enum mini_estudio

- **schema_datos_mercado.md**: `fuente_medicion` añade `mini_estudio` (medición inline del Flujo B, confianza 2-3) · nueva tabla "Estado resultante según quién mide" (estudio→estudiado · mini_estudio→estudiado · flujo_b→buscado · flujo_a→buscado/pendiente_estudio · flujo_e_delta→estudiado).
- **`datos_mercado.json`**: `cola_trabajo` inicializada con 35 modelos de los 6 segmentos y `siguiente_estudio=vw-golf-75-tcr`.

## [0.3.0] - 2026-08-21 — Pipeline conjunto con importacion-vehiculos (modelo por modelo)

> **Motivo:** la búsqueda por segmentos de golpe ("Compactos deportivos") durante 3 días no dio resultado (límite 5h, unidades que no encajan). El estudio debe ir modelo por modelo.

### 🔄 Pipeline conjunto
- **SKILL.md §PIPELINE CONJUNTO**: 1 modelo por pasada (ES + DE + cruce), PARADA obligatoria entre modelos, feedback bidireccional con `importacion-vehiculos` (vuelca mediciones reales con `fuente_medicion: flujo_b`).
- **schema_datos_mercado.md §Cola de trabajo**: campo `cola_trabajo` en el JSON (estados + enrutador `siguiente_*`) y campo `estado_cola` en cada modelo. Estados: `pendiente_estudio` → `estudiado` → `pendiente_busqueda` → `buscado` → `descartado`.
- **Referencia al MD maestro** `../importacion-vehiculos/02-flujos/como_deben_ser_las_sesiones.md` (formato de sesión obligatorio).
- **SKILL.md**: bump 0.2.1 → **0.3.0**.

## [0.2.1] - 2026-08-18 — Regla Seat/Cupra corregida + equipamiento máximo
- Regla Seat/Cupra: la nacionalidad NO es criterio; mirar suelo sin banda (Cupra DE 15.500 vs ES 19.500).
- Equipamiento máximo por defecto (5 checkboxes full en mobile.de ES, proxy techo en Coches.net).
- Selectores data-testid estables de mobile.de ES documentados.
