# Changelog

Todos los cambios notables en el skill `estudio-mercado` se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

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
