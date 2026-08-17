# Operaciones post-investigación

> Cargar cuando se necesite ejecutar scripts, generar archivos, o consultar la estructura de carpetas.
> **Google Drive ya NO es parte del flujo operativo** — solo backup externo opcional.

---

## 🗺️ MAPA DE PDFs — TIPOS y DÓNDE SE CREA CADA UNO (movido de SKILL.md, 15-ago-2026)

> **Hay 7 PDFs en total: 3 los genera CLAUDE (investigación) y 4 los genera LARAVEL (venta/documento).**
> **El briefing PDF ya NO existe** (eliminado 15-ago-2026). El status de cliente 'Briefing' (pipeline) y `../01-arranque/briefing_encargo.md` (cuestionario previo) NO son el PDF briefing y se mantienen.

| # | PDF | Tipo | Quién lo genera | De qué sale | Dónde se crea |
|---|---|---|---|---|---|
| 1 | `informe_busqueda_*.pdf` | Investigación (búsqueda) | **CLAUDE** | Markdown Fase 1 | HTML de marca → Chrome headless, plantilla `assets/plantilla_pdf_marca.html` |
| 2 | `informe_unidad_*.pdf` | Investigación (unidad) | **CLAUDE** | `../03-informes/informe_tecnico.md` (15 sec) | Idem plantilla de marca |
| 3 | informe técnico unidad (Flujo A) | Investigación (técnico) | **CLAUDE** | `../03-informes/informe_tecnico.md` | Idem plantilla de marca |
| 4 | Dossier cliente | Venta / cliente | **LARAVEL** | `contenido/dossier-cliente.txt` | Blade `ficha-coche.blade.php` (documento cliente) |
| 5 | Ficha del coche | Venta / cliente | **LARAVEL** | `contenido/ficha-publicitaria.txt` | Blade `ficha-coche.blade.php` · `PaqueteValoracionController@ficha` · ruta `cars.ficha` |
| 6 | Informe interno | Venta / equipo | **LARAVEL** | `contenido/informe-interno.txt` | Blade `informe-interno.blade.php` · `PaqueteValoracionController@interno` · ruta `cars.informe-interno` |
| 7 | **Folleto del coche** | Venta / cliente | **LARAVEL** | `contenido/ficha-publicitaria.txt` (mismo que ficha) | Blade `folleto-coche.blade.php` · `PaqueteValoracionController@folleto` · ruta `cars.folleto` |
| 8 | Folleto institucional | Marketing / público | **LARAVEL** | estático (sin esqueleto) | Blade `folleto.blade.php` · `JJImportFolletoController@download` · ruta `jj-import.folleto` |

**Reglas duras del mapa:**
1. **Claude NUNCA genera los PDFs de venta** (ficha, informe interno, folleto) — los hace Laravel con Blade + Browsershot tras recibir el ZIP.
2. **Laravel NUNCA genera los PDFs de investigación** — los hace Claude en el Desktop con la plantilla de marca.
3. El **informe interno** (margen, honorarios, URLs de comparables) es SOLO equipo; el **dossier/ficha/folleto** es para el cliente (sin margen).
4. Los esqueletos `.txt` (`contenido/*.txt`) son la ÚNICA entrada de Laravel: `ficha-publicitaria.txt`, `informe-interno.txt`, `dossier-cliente.txt`. El **folleto del coche reutiliza `ficha-publicitaria.txt`** — no requiere esqueleto propio.

---

## 📁 DÓNDE SE GUARDA CADA COSA — rutas por tipo de archivo (movido de SKILL.md, 15-ago-2026)

> **Regla 1: los `.md` que LEE EL USUARIO van al Desktop, organizados por marca/modelo.**
> **Regla 2: los JSON de contrato y los ZIPs van a `laravel/` (los procesan los scripts Python y Laravel).**
> NUNCA escribir nada en `AppData\Roaming\Claude\...\outputs\` (fallo real 15-ago, Tiguan: el informe se escribió ahí y el usuario no lo veía).

**Tabla única de rutas — QUÉ archivo va DÓNDE:**

| Archivo | Ruta | Quién lo usa | Cuándo |
|---|---|---|---|
| `informe_busqueda_<fecha>.md` | `informes\<marca>\<modelo>\` | El usuario (lectura) | Fin Fase 1 (Flujo B/C) |
| `informe_unidad_<fecha>.md` | `informes\<marca>\<modelo>\` | El usuario (lectura) | Fase 2, candidato elegido |
| `comparativa_<fecha>.md` | `informes\<marca>\<modelo>\` | El usuario (lectura) | Si compara varios candidatos |
| `export\flujo-a-<coche_id>.json` | `laravel\` | `empaquetar.py` | Flujo A (entrada del ZIP) |
| `export\flujo-b-<modelo>-<fecha>.json` | `laravel\` | Laravel (histórico cacheable) | Flujo B |
| `export\scouting_<fecha>.json` | `laravel\` | Laravel (scouting) | Flujo C |
| `<coche_id>.zip` | `laravel\paquetes\` | Subida a Laravel | Cierre Flujo A |
| `informe.json` | **SOLO dentro del ZIP** | Laravel | Lo genera `empaquetar.py` — NO existe suelto |
| Fotos del candidato | `<coche_id>_fotos\` junto al JSON de `export\` | `empaquetar.py` | Se descargan al elegir candidato |

**Aclaraciones que evitan confusión (15-ago-2026):**

1. **`informe.json` NO es un archivo suelto.** Es una entrada DENTRO del ZIP que
   genera `empaquetar.py` a partir de `export\flujo-a-<coche_id>.json`. Si estás
   buscando un "informe JSON" en la carpeta del modelo, no existe: existe el
   `flujo-a-*.json` en `laravel\export\` y el ZIP en `laravel\paquetes\`.
2. **Los `.md` de `informes\` son para el USUARIO.** No los procesa ningún script.
   Son lectura humana: búsqueda, unidad, comparativa.
3. **Los JSON de `laravel\export\` son para las MÁQUINAS.** Los lee `empaquetar.py`
   o Laravel (`php artisan importnex:import-valuation`). No hace falta abrirlos a mano.
4. **Los PDFs no los genera Claude** — salen de Laravel (Blade + Browsershot) tras
   subir el ZIP.

```
C:\Users\jacar\Desktop\JJImportMotors\
├── informes\                        ← SOLO .md para el USUARIO, por marca/modelo
│   └── <marca>\<modelo>\            ← ej. vw\tiguan
│       ├── informe_busqueda_<fecha>.md
│       ├── informe_unidad_<fecha>.md
│       └── comparativa_<fecha>.md
└── laravel\                         ← trabajo de scripts y contrato con Laravel
    ├── export\
    │   ├── flujo-a-<coche_id>.json  ← entrada de empaquetar.py (Flujo A)
    │   ├── flujo-b-<modelo>-<fecha>.json
    │   └── scouting_<fecha>.json
    └── paquetes\
        └── <coche_id>.zip           ← contiene informe.json + manifest + contenido\ + fotos\
```

**Reglas de guardado:**
1. Crear carpetas con `New-Item -ItemType Directory -Force`.
2. **Normalizar nombres:** marca y modelo en minúsculas, sin tildes, con guiones
   (`vw\tiguan`, `opel\astra`, `audi\a3`). Fecha en `YYYY-MM-DD`.
3. **Nunca fuera del Desktop.** Si un informe quedó en otra ruta (outputs de la
   sesión, AppData, temp), copiarlo a la estructura del Desktop.

---

## 🏗️ DIVISIÓN DE TRABAJO DEFINITIVA (12-ago-2026)

> **Investigación → Claude (Desktop). Almacenamiento y gestión → Laravel (importnexcore).**

```
1. 🔍 INVESTIGACIÓN → SOLO en Claude (Desktop). Navegación real, filtros, 7 fuentes.
   └─ genera: informe MODELO / UNIDAD (.md **+ PDF de investigación**) + esqueletos .txt [MARCADOR] + JSON

2. 📦 SUBIR AL SISTEMA → el JSON se sube vía API (`/api/import-valuation` con `X-Import-Token`); el ZIP con fotos se sube desde el panel web (`POST /cars/import-valuation`).
   └─ Laravel (importnexcore) = REPOSITORIO ÚNICO y FUENTE DE VERDAD de:
       ✓ PDFs de marketing (dossier, ficha, folleto — por Blade+Browsershot) · ✓ imágenes/fotos · ✓ JSON · ✓ dossier · ✓ folleto

3. 📊 VER / MOSTRAR / GESTIONAR / ACTUALIZAR → TODO desde el sistema Laravel.
   └─ El sistema se encarga de las actualizaciones, iteraciones, nuevas versiones, etc.
   └─ Claude NO consulta lo subido. Cuando el usuario quiere re-evaluar,
       lanza un NUEVO encargo desde Claude.
```

**Reglas duras:**
- La **investigación** (navegación/filtros) se hace en Claude Desktop — NO en VS Code (fricción con filtros, ver `../memoria/retrospectiva.md`).
- El **repositorio de PDFs de marketing/imágenes/JSON es Laravel**. Claude genera el paquete y lo SUBE; además entrega al usuario los **PDFs de investigación** (búsqueda/unidad). Los PDFs de marketing (dossier/ficha/folleto) solo salen de Laravel.
- Para **ver un informe/PDF/fotos** → consultar el sistema Laravel (nunca regenerar desde cero si ya está subido).
- Claude NO consulta Laravel para "revisar" o "iterar". Cada entrega al sistema es el final del ciclo para Claude.

---

## ✅ Verificación de sincronización Desktop (ARRANQUE)

> **Ejecutar SIEMPRE al inicio de cada sesión de investigación.** Detecta si faltan scripts críticos en `Desktop\JJImportMotors\laravel\` antes de que Claude invoque uno y falle a mitad de la sesión.

### Comando

```bash
py .claude/skills/importacion-vehiculos/scripts/verify_desktop_sync.py
```

El script verifica que los **12 scripts Python + 2 archivos de datos** referenciados por el skill existen en `C:\Users\jacar\Desktop\JJImportMotors\laravel\`.

### Qué verifica

**Scripts (12):**
- `franja.py` · `comparativa_cliente.py` · `cache_investigacion.py`
- `fill_template.py` · `fill_client_template.py` · `generate_summary_pdf.py`
- `generate_browser_dashboard.py` · `sync_web_data.py` · `update_master_list.py`
- `update_registro.py` · `check_avisos.py` · `pdf_kit.py` (legacy backup)

**Datos (2):**
- `marca.json` (datos JJ Import Motors — **obligatorio**)
- `datos_mercado.json` (caché — se crea automáticamente en primera investigación)

### Output exitoso

```
🔍 Verificando sincronización Desktop ↔ Skill

📁 Ruta base: C:\Users\jacar\Desktop\JJImportMotors\laravel

==========================================================
SCRIPTS REQUERIDOS
==========================================================
✅ Script: franja.py (21.0 KB)
✅ Script: comparativa_cliente.py (22.0 KB)
... (10 más) ...
📊 Scripts: 12/12 presentes

==========================================================
ARCHIVOS DE DATOS
==========================================================
✅ Datos: marca.json (3.2 KB)
ℹ️  datos_mercado.json se crea automáticamente en la primera investigación
📊 Datos: 1/2 presentes (datos_mercado.json se genera solo)

==========================================================
RESUMEN
==========================================================

✅ TODO OK: Todos los archivos están presentes en Desktop
   Puedes iniciar la sesión de investigación con confianza.
```

Exit code: `0` → sesión OK para arrancar.

### Output con faltantes

```
❌ FALTAN 2 archivos:

   Scripts faltantes (2):
     - franja.py
     - cache_investigacion.py

💡 SOLUCIÓN:
   1. Verifica que la carpeta Desktop/JJImportMotors/laravel/ esté completa
   2. Si falta algo, copia desde .claude/skills/importacion-vehiculos/scripts/
   3. Re-ejecuta este script para confirmar
```

Exit code: `1` → **NO arrancar**, copiar archivos faltantes primero.

### Integración con Claude

Cuando el usuario diga **"arrancamos sesión"** o cualquier trigger de investigación, Claude debe ejecutar este script **antes** de leer `indice.json` o invocar `franja.py`. Si exit code ≠ 0, mostrar el output al usuario y pedir acción correctiva.

> 📜 Cambiado 12-ago-2026 (§3.7 auditoría): antes el script existía pero no estaba documentado. Ahora es check de arranque obligatorio.

---

## 🧪 Verificación del skill — scripts de mantenimiento (16-ago-2026)

> Dos scripts nuevos para mantener la coherencia del skill entre ediciones. Ejecutarlos ANTES de re-empaquetar el ZIP y al detectar referencias rotas.

### 1. `verify_skill_refs.py` — valida referencias cruzadas

Recorre los 29 `.md` del skill, extrae las referencias entre backticks/links que apuntan a `.md` locales, y verifica que el archivo destino EXISTE. Ignora referencias externas (Desktop, repo Laravel) y variables `<...>`.

```bash
py scripts/verify_skill_refs.py
# Exit 0 = todas las referencias OK · Exit 1 = lista de rotas a corregir
```

### 2. `sync_indice.py` — regenera indice.json desde encargos.md

El PASO 0 del skill lee `indice.json` (Desktop) para decidir si un modelo/cliente ya se investigó. Este script mantiene ese índice en sync con `../memoria/encargos.md` (fuente de verdad del skill).

```bash
py scripts/sync_indice.py                  # escribe indice.json en Desktop\JJImportMotors\
py scripts/sync_indice.py --dry-run        # imprime el JSON sin escribir
```

**Regla de sync:** cada vez que se cierra un encargo (nueva entrada en `../memoria/encargos.md`), ejecutar `sync_indice.py` para que el PASO 0 de la próxima conversación vea la cache actualizada.

---

## 📂 Estructura de carpetas por flujo

> **RUTA BASE de informes (15-ago-2026):** `C:\Users\jacar\Desktop\JJImportMotors\informes\`
> organizada por **marca/modelo**. Todo lo que genera Claude va ahí — NUNCA en
> `AppData\Roaming\Claude\...\outputs\` ni en la carpeta de la sesión.

```
JJImportMotors/informes/                  ← SOLO .md para el USUARIO (por marca/modelo)
└── <marca>/
    └── <modelo>/
        ├── informe_busqueda_<fecha>.md   ← Fase 1: cobertura + candidatos
        ├── informe_unidad_<fecha>.md     ← Fase 2: informe del candidato elegido
        └── comparativa_<fecha>.md        ← si compara varios candidatos

JJImportMotors/laravel/                   ← scripts Python, JSON de contrato y ZIPs
├── export/                               ← JSON que leen los scripts y Laravel
│   ├── flujo-a-<coche_id>.json           ← entrada de empaquetar.py (Flujo A)
│   │                                        (el informe.json NO existe suelto: va DENTRO del ZIP)
│   ├── flujo-b-<modelo>-<fecha>.json     ← histórico cacheable (Flujo B)
│   └── flujo-c-<fecha>.json              ← scouting agregado (Flujo C)
├── paquetes/                             ← ZIPs generados (solo Flujo A)
│   └── <coche_id>.zip                    ← informe.json + manifest + contenido/ + fotos/
├── <coche_id>_fotos/                     ← fotos descargadas (las mete empaquetar.py en el ZIP)
├── informes/
│   ├── datos/
│   │   ├── indice.json                  ← UN archivo, resumen de todos los modelos
│   │   └── <marca>/<modelo-slug>/
│   │       └── mercado_<fecha>.json     ← Snapshot (se actualiza cada medición)
│   ├── pdf/                             ← PDFs finales (Blade + Browsershot) — los genera LARAVEL
│   │   └── <marca>/<modelo-slug>/
│   └── historial/
│       └── <marca>/<modelo-slug>/
│           └── informe_<fecha>.json     ← JSONs Flujo A y B completos
└── scouting/                            ← Solo Flujo C
    └── scouting_<fecha>.json            ← Tabla multi-modelo agregada
```

**Reglas de guardado (15-ago-2026):**
- **`.md` para el usuario** → `JJImportMotors/informes/<marca>/<modelo>/`. No los lee ningún script.
- **JSON de contrato** → `JJImportMotors/laravel/export/` (flujo-a/b/c). Los lee `empaquetar.py` o Laravel.
- **ZIP final** → `JJImportMotors/laravel/paquetes/<coche_id>.zip`. El `informe.json` va DENTRO, no suelto.
- **NUNCA** escribir informes en la carpeta de outputs de la sesión de Claude (`AppData\Roaming\Claude\...`).

### Por flujo

| Carpeta | Flujo A | Flujo B | Flujo C |
|---|---|---|---|
| `informes/datos/<marca>/<modelo>/` | ✅ (actualiza) | ✅ (actualiza) | ✅ (actualiza si pasa) |
| `informes/historial/` | ✅ | ✅ | ❌ |
| `informes/pdf/` | ✅ | ❌ (no se genera PDF) | ❌ |
| `scouting/` | ❌ | ❌ | ✅ |
| `export/` | ✅ (JSON individual) | ✅ (JSON individual) | ✅ (JSON agregado) |
| `paquetes/` | ✅ (ZIP) | ❌ | ❌ |

---

## 📇 `indice.json` — consultar SIEMPRE primero

Una entrada por marca+modelo+filtro con:

```json
{
  "marca": "Opel",
  "modelo": "Astra",
  "version_filtro": "OPC 280CV",
  "ultima_medicion": "2026-08-10",
  "proxima_revision": "2026-08-24",
  "candidatos_evaluados": 12,
  "mejor_candidato_id": "mobile-de-38347146649056",
  "mejor_precio_de": 13000,
  "mediana_es": 16400,
  "hueco_pct": 20.7,
  "vendibilidad_estimada": 72,
  "recomendacion_actual": "COMPRA_PRIORITARIA"
}
```

**Cómo decide Claude:**
1. ANTES de cualquier cosa, leer `indice.json`.
2. Si el modelo+versión tiene entrada de hace <3 semanas → mostrar delta o cerrar.
3. Si no existe o es antigua → medición completa (Fase 1 + Fase 2 si pasa).

---

## 🧠 Sistema de aprendizaje

Cada operación devuelve:
```json
"aprendizajes": [
  {"fuente":"cochesnet","tipo":"trampa","gravedad":"alta","texto":"..."}
]
```

**Cuándo un aprendizaje entra al skill:** cuando aparece 3 veces se sistematiza en `../02-flujos/extractores.md` §Trampas críticas.

### Caché de investigación (9 aspectos)

Archivo: `investigacion_modelos/<marca>-<cv>-<combustible>.json`

```json
{
  "_meta": {
    "modelo_id": "opel-astra-280cv-gasolina",
    "ultima_actualizacion": "2026-08-11",
    "version_skill": "1.1.0"
  },
  "problemas_comunes": {
    "hallazgo": "Cadena de distribución en motores 2.0T hasta 2014. Reemplazo ~1.500€.",
    "fuente": "https://clubopel.es/foros/astra-j-opc-cadena",
    "valoracion": "desfavorable",
    "fecha": "2026-08-11",
    "caducidad": "2027-08-11"
  },
  "recalls": {
    "hallazgo": "Campaña KBA 2023-001: software ECU. Solución sin coste en taller.",
    "fuente": "https://kfz-rueckrufe.de/recalls/opel-astra-j",
    "valoracion": "favorable",
    "fecha": "2026-08-11",
    "caducidad": "2026-11-11"
  },
  "fiabilidad": {
    "hallazgo": "Motor 2.0T robusto >180k km con mantenimiento. Turbo menos.",
    "fuente": "https://...",
    "valoracion": "favorable",
    "fecha": "2026-08-11",
    "caducidad": "2027-08-11"
  },
  "homologacion": {...},
  "etiqueta_ambiental": {...},
  "seguro": {...},
  "piezas": {...},
  "precio_mercado": {...},
  "otros": {...}
}
```

**Caducidades por aspecto:**
| Aspecto | Caducidad |
|---|---|
| recalls | 6 meses |
| seguro, piezas | 12 meses |
| averías, precio_mercado, fiabilidad | 18 meses |
| homologacion, etiqueta_ambiental, otros | 24 meses |

**Uso:** `cache_investigacion.py consultar <modelo_id>` devuelve los aspectos no caducados. Si un aspecto está caducado, se re-investiga en la siguiente sesión.

---

## 🐍 Scripts Python

Todos en `C:\Users\jacar\Desktop\JJImportMotors\laravel\`.

| Script | Uso | Tamaño | Cuándo ejecutar |
|---|---|---|---|
| `empaquetar.py` | Genera ZIP desde JSON Flujo A | 36KB | Tras dar OK al informe Flujo A |
| `franja.py` | Calcula franja precio + desgloses + lotes | 21KB | Antes del Flujo A (saber precio máximo) |
| `comparativa_cliente.py` | Flujo B con perfil de cliente | 22KB | Si Flujo B tiene cliente concreto |
| `cache_investigacion.py` | Caché de investigación 9 aspectos | 14KB | Automático al generar JSON |
| `comun.py` | Utilidades compartidas | 4KB | (importado por otros) |
| `fill_template.py` | Rellena plantilla Excel desde JSON | 9KB | Si quieres Excel (no Laravel) |
| `fill_client_template.py` | Rellena ficha cliente | 6KB | Tras captar cliente |
| `generate_summary_pdf.py` | Resumen ejecutivo desde xlsx | 9KB | Tras fill_template.py |
| `generate_browser_dashboard.py` | Dashboard HTML | 4KB | Bajo demanda |
| `update_master_list.py` | Actualiza lista maestra Excel | 6KB | Tras operaciones |
| `update_registro.py` | Actualiza registro operaciones | 5KB | Tras operaciones |
| `check_avisos.py` | Verifica avisos | 7KB | Periódico |
| `presentacion_empresa.py` | Presentación corporativa PDF | 18KB | Para cliente nuevo |

**NO usar (deprecados o sin función clara):**
- ❌ `pdf_kit.py` — PDF local con reportlab. Reemplazado por Laravel (Blade + Browsershot).
- ❌ `sync_web_data.py` — Sin función clara. Probablemente obsoleto.

### Comandos por flujo

#### Flujo A (UNIDAD)

```bash
# 1. Calcular franja para saber precio máximo de compra
python franja.py --mediana 16400 --anio 12/2017 --co2 145 --pvp-nuevo 32250 --zona suroeste

# 2. Desglose completo de un candidato concreto
python franja.py ... --precio-aleman 13000 --km 102000 --nombre "Opel Astra OPC 2014"

# 3. Confirmar OK y empaquetar
python empaquetar.py export/flujo-a-opel-astra-opc-2014-a1b2c3.json
# Genera: paquetes/opel-astra-opc-2014-a1b2c3.zip

# 4. Subir ZIP a Laravel
# POST https://dev.aktive.cloud/importnexcore/api/import-valuation
# Header: X-Import-Token: <token>
# Body: ZIP (multipart) o JSON del informe.json (extraer del ZIP)
```

#### Flujo B (MODELO)

```bash
# Cliente con preferencias
python comparativa_cliente.py cliente.json

# Refrescar y guardar snapshot
python cache_investigacion.py guardar export/flujo-b-golf-gti-2026-08-11.json
```

#### Flujo C (MERCADO)

```bash
# No hay script específico. Claude genera el JSON manualmente y se guarda.
# Tras validación, se mueve a export/flujo-c-<fecha>.json para histórico.
```

---

## 📊 Dashboard HTML

Archivo: `assets/dashboard_template.html` (69KB).

Panel operativo completo con:
- **Mapa Leaflet** interactivo con ubicaciones de coches
- **Gráficos Chart.js** (precios, distribución por modelo)
- **Kanban** por estado: pendiente · en curso · completada · cancelada
- Tarjetas resumen: margen medio · coches activos · clientes en espera
- Filtros por marca, modelo, rango de precio, estado
- Paleta: `#1A306D` (principal), `#38393D`, `#BEC0C3`, `#E8590C`

Para regenerar: `python generate_browser_dashboard.py`.

---

## ☁️ Google Drive — SOLO BACKUP EXTERNO

> ⚠️ Google Drive ya NO se usa operativamente. Solo es una copia de seguridad fuera del servidor de Laravel. El flujo principal es subir directamente a `dev.aktive.cloud/importnexcore`.

Si quieres hacer backup manual tras operaciones:

### Carpetas destino (ya existen, NO crear)

| Tipo archivo | Carpeta Drive |
|---|---|
| Plantillas Excel, PDFs, Inventario, Registro | `07 Vehiculos (operaciones)` |
| Clientes.xlsx, Ficha_Cliente_*.xlsx | `06 CRM y clientes` |

### Procedimiento (solo si quieres backup)

1. **Localizar carpeta** con `search_files`:
   ```
   title = '07 Vehiculos (operaciones)' and mimeType = 'application/vnd.google-apps.folder'
   ```
2. **Subir archivo** con `create_file`:
   ```json
   {
     "title": "<nombre>.xlsx",
     "parentId": "<id carpeta>",
     "contentMimeType": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     "base64Content": "<base64>",
     "disableConversionToGoogleType": true
   }
   ```

**Reglas críticas (si decides usarlo):**
- ⚠️ Usar `base64Content`, NUNCA `content` (deprecado, produce archivos corruptos).
- `disableConversionToGoogleType: true` para archivos que los scripts reabren con openpyxl.

---

## 📋 Plantillas Excel (uso local, NO Laravel)

### `Plantilla_Importacion_Vehiculos_master.xlsx`

Hojas: "Vehiculo y resumen" · "Numeros" · "Contactos clave" · "Anuncio de venta" · "Checklist inspeccion" · "Checklist documentacion" · "Cronograma logistico" · "Plantillas de mensaje".

`fill_template.py` rellena desde JSON con bloques `vehicle`, `investigacion`, `comparables`, `vendedor_contacto`, `anuncio`.

### `Ficha_Cliente_master.xlsx`

Hoja "Cliente y resumen": nombre, teléfono, email, cómo llegó, fecha alta.

`fill_client_template.py` rellena desde JSON.

### Mapa de celdas

Ver `../references/cell_map.md` para referencia rápida de dónde vive cada dato.

---

## 📅 Flujo diario del usuario

### Por flujo de trabajo

#### Flujo A — Tienes un coche concreto

1. Pegas URL del anuncio a Claude.
2. Claude detecta URL → Flujo A. Fase 1 (3 fuentes).
3. Si pasa filtro → Fase 2 (4 fuentes + fichas mobile.de + km77).
4. Claude muestra resumen y **espera OK**.
5. Con OK → `python empaquetar.py export/flujo-a-*.json` → genera `.zip`.
6. Subir ZIP a `dev.aktive.cloud/importnexcore/api/import-valuation`.

#### Flujo B — Buscar un modelo

1. Dices "busca Golf GTI para 25k".
2. Claude detecta modelo+presupuesto → Flujo B. Fase 1 (3 fuentes).
3. Si hueco ≥15% → **entregar INFORME TIPO MODELO (plantilla en SKILL.md) + top 5 con enlaces + checkpoint CP1**.
4. Solo con OK del usuario → Fase 2 (4 fuentes restantes) para completar las 7.
5. Claude muestra informe MODELO actualizado (7 fuentes) + top 5 con enlaces.
6. Si quieres profundizar en uno → convertir a Flujo A.
7. Si quieres publicar → guardar snapshot en `informes/datos/<marca>/<modelo>/`.

> **Regla (12-ago-2026):** NUNCA saltar del resumen informal al "¿evalúo el candidato X?" sin entregar antes el INFORME TIPO MODELO + top 5 con enlaces + CP1. El usuario decide qué candidato profundizar (Flujo A), no Claude.

> **🔑 PROTOCOLO DE MANDO (16-ago-2026):** el usuario aprueba CADA fase (plan de fase 3-5 líneas); dentro de la fase la ejecución es automática (Fase 1 = 3 fuentes → INFORME MODELO + top 5). El usuario elige candidato (NO Claude): "investiga el de X" → nueva fase aprobada → automático fotos + informe UNIDAD + dossier + ZIP. Si varios → comparativa antes. NO preguntar "¿qué candidato?", "¿continúo?", "¿descargo fotos?" dentro de una fase. Ver `../SKILL.md` §PROTOCOLO DE MANDO.

#### Flujo C — Escanear mercado

1. Dices "qué merece la pena" o "dame 10 modelos rentables".
2. Claude pregunta preferencias (pasionales / premium / económico / eco).
3. Claude prioriza por ROI y hace Fase 1 por cada modelo (3 fuentes × N modelos).
4. Claude muestra informe BUSQUEDA con tabla multi-modelo.
5. Tú curas: "¿profundizo en estos 3?" → pasa a Flujo A o B.
6. Tabla guardada en `scouting/scouting_<fecha>.json`.

### Honorarios por tramo

| Precio compra | Honorarios |
|---|---|
| Hasta 15.000 € | 1.500 € |
| 15.000-30.000 € | 2.000-2.500 € |
| >30.000 € | 2.500-3.500 € |

### Transporte

| Zona | Coste |
|---|---|
| Suroeste (Huelva) | 900 € |
| Oeste | 1.000 € |
| Centro | 1.100 € |
| Norte/Este | 1.250 € |

Agrupando 2-3 coches: ~400 €/coche.

---

## ⚠️ Riesgos mecánicos por modelo

| Componente | Modelos afectados | Coste reparación |
|---|---|---|
| DQ200 (DSG7 seco) | Golf/León/A3 <2015 | 1.400-2.000 € |
| HPFP N54 | BMW 335i | 800-1.200 € |
| Cadena EA888 gen2 | Golf GTI <2013 | 1.000-1.500 € |
| Correa 1.4 TSI | Golf/León 1.4 | 600-900 € |
| DPF diésel urbano | Todos | 1.200-2.000 € |
| Batería PHEV | GTE/330e/e-tron >8a | 3.000-6.000 € |

No restar del ahorro, comunicar.

### Señales buenas
ITV/TÜV nueva · `Scheckheftgepflegt` · `Batterie-Zertifikat` en PHEV · `unfallfrei, Serienzustand, ohne Umbauten` · vendedor que declara desperfecto.

### Señales de alerta
`Reparierter Unfallschaden` · `Stage`/chip · `Raucher-Paket` · `Export` · B2B sin garantía ni ITV · `im Kundenauftrag` · solo WhatsApp.

---

## 💸 Costes escondidos

- Correa 1.4 TSI: 600-900 €
- ITV a petición: 300-400 €
- Titularidad italiana (gestión adicional)
- GLP declarado: preguntar al gestor (homologación aparte)
