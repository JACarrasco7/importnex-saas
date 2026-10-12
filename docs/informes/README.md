# 📁 Informes — JJ Import Motors

> **Regla simple:** los `.md` que lees tú van AQUÍ (por marca/modelo). Los JSON y ZIPs van a `..\laravel\` (los procesan los scripts y Laravel).

## QUÉ archivo va DÓNDE

| Archivo | Ruta | Qué es |
|---|---|---|
| `informe_busqueda_<marca>-<modelo>_<YYYY-MM-DD>.md` | `informes\<marca>\<modelo>\` | Fase 1: cobertura de fuentes + candidatos |
| `informe_unidad_<marca>-<modelo>_<YYYY-MM-DD>.md` | `informes\<marca>\<modelo>\` | Fase 2: informe del candidato elegido |
| `comparativa_<YYYY-MM-DD>.md` | `informes\<marca>\<modelo>\` | Comparación de varios candidatos |
| `<marca>_<segmento>_<enfoque>_<YYYY-MM-DD>.md` | `informes\<marca>\` | Estudio de MERCADO (skill `estudio-mercado`): sondeo conciso, **sin** subcarpeta de modelo |
| `<cliente>_<fecha>.md` | `informes\descubrimiento\` | Flujo D: informe de MODELOS que caben en presupuesto (país × año × motorización) |
| `sesion_<fecha>_<encargo>.md` | `informes\_sesion\` | Cuaderno de sesión: parámetros fijados, correcciones con hora, preferencias detectadas |
| `export\flujo-a-<coche_id>.json` | `..\laravel\` | Entrada de `empaquetar.py` (Flujo A) |
| `export\flujo-b-<modelo>-<fecha>.json` | `..\laravel\` | Histórico cacheable (Flujo B) |
| `export\flujo-c-<fecha>.json` | `..\laravel\` | Scouting (Flujo C) |
| `<coche_id>.zip` | `..\laravel\paquetes\` | ZIP final → se sube a Laravel |
| `informe.json` | **SOLO dentro del ZIP** | No existe suelto — lo genera `empaquetar.py` |

## Estructura

```
JJImportMotors\
├── informes\                        ← SOLO .md (lectura humana)
│   ├── vw\
│   │   ├── vw_compacto_deportivo_2026-09-27.md   ← estudio de MERCADO (por marca)
│   │   └── tiguan\
│   │       ├── informe_busqueda_vw-tiguan_2026-08-15.md
│   │       └── informe_unidad_vw-tiguan_2026-08-15.md
└── laravel\
    ├── export\flujo-a-vw-tiguan-...json
    └── paquetes\vw-tiguan-....zip  ← contiene informe.json + manifest + contenido\ + fotos\
```

## Reglas

1. Marca y modelo en minúsculas, sin tildes, con guiones: `vw\tiguan`, `opel\astra`, `audi\a3`.
2. Fecha en formato `YYYY-MM-DD`.
3. NUNCA guardar informes fuera del Desktop (ni en `AppData\Roaming\Claude\...\outputs`).
4. Los PDFs (dossier, ficha, folleto) los genera **Laravel** tras subir el ZIP — aquí no hay PDFs.
5. **Estudio de mercado:** el fichero va directo en `informes\<marca>\` (sin modelo), con
   `<segmento>` ∈ `compacto|suv|berlina|deportivo|familiar|urbano` y `<enfoque>` ∈
   `deportivo|generalista|accesible|premium|mixto`. Ej: `informes\vw\vw_compacto_deportivo_2026-09-27.md`.
   Fuente: `.claude/skills/estudio-mercado/informe_mercado.md` (v0.5.0).

## Carpeta nueva de modelo

```powershell
New-Item -ItemType Directory -Force "C:\Users\jacar\Desktop\JJImportMotors\informes\<marca>\<modelo>"
```
