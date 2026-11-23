# Auditoría de la integración con Google Drive (D11)

**Fecha:** 7-oct-2026 · **Versión auditada:** estado real de tu Drive (jjimportmotors@gmail.com) a 7-oct-2026 + `references/google_drive.md` y `05-operaciones/operaciones.md` de importacion-vehiculos 3.10.5 · **Versión corregida propuesta:** `google_drive.md` v2 + política de Drive por flujo (ver "Protocolo propuesto")

> **Qué he podido mirar y qué no:**
> - ✅ **Drive real**, con el conector (solo lectura): estructura de carpetas, ficheros, fechas y tamaños, permisos de la carpeta `auditoria`.
> - ✅ Lo que dicen las skills sobre Drive (`google_drive.md`, `operaciones.md`, `SKILL.md`) y tu `HANDOFF.md`.
> - ❌ **NO VERIFICABLE desde esta sesión:** `docs/SYNC-DRIVE-PLAN.md` y la ronda 4 (están en el repo), `.ai/rules/drive.md`, `scripts/sync-desktop.ps1` y cualquier cliente Google en el panel Laravel. Por eso **no puedo decir qué fases del plan están hechas**; solo lo que se ve en Drive.
> - No he creado, movido, renombrado ni borrado nada en Drive salvo subir estos tres informes a la carpeta `auditoria`. No se ha hecho "limpieza".

---

## 🔴 Hallazgos críticos

### DR-C1 · Hay tres políticas contradictorias sobre el papel de Drive

**En cristiano:** según qué fichero lea Claude, Drive es "el canal operativo", "solo un backup" o "el único canal de salida de las propuestas". Con eso no puede saber dónde dejar un informe, y tú no sabes dónde buscarlo.

- **Evidencia:**
  1. `05-operaciones/operaciones.md` l.4: "**Google Drive ya NO es parte del flujo operativo** — solo backup externo opcional" y l.508: "Google Drive ya NO se usa operativamente. Solo es una copia de seguridad…".
  2. `CLAUDE.md` de tu Desktop: "**Google Drive NO es el flujo operativo** (arquitectura legacy, solo backup externo). Todo vive en Desktop\JJImportMotors\ y Laravel".
  3. El encargo de auditoría de hoy: "Drive es el único canal de salida para tus propuestas de informe", y existe una estructura numerada `01 Estrategia y plan … 07 Vehículos (operaciones)` creada el 16-jul, más una carpeta `Infomes` creada el 30-sep.
  4. `references/google_drive.md` sigue describiendo los flujos "legacy" 1/2 (ver DR-I4).
- **Impacto:** cada sesión improvisa. Resultado visible hoy: Drive tiene una copia congelada del 30-sep (DR-C2) y **ningún fichero de octubre** (búsqueda `title contains '2026-10'` → 0 resultados), mientras en tu Desktop hay informes del 6 y 7-oct.
- **Fix concreto (pendiente de tu decisión, D-D1):** declarar **una** política y copiarla literal a las 4 ubicaciones (skill, `CLAUDE.md`, proyecto de claude.ai, `.ai/rules/drive.md`). Propuesta, en una línea:

  > "Desktop\JJImportMotors es la fuente de verdad. Drive es el **canal de entrega y de lectura desde móvil**: al cerrar un entregable (estudio, informe, auditoría) Claude lo sube a su carpeta de Drive, sin duplicar formatos. Lo operativo (ZIP, JSON) va al panel Laravel."

- **Test de regresión:** `grep -rn -i "drive" <skill> <Desktop>/CLAUDE.md` → ninguna frase que diga "ya NO" o "solo backup" si eliges la política anterior.

---

### DR-C2 · `datos_mercado.json` ha divergido entre Drive y Desktop (riesgo de sobrescribir lo nuevo con lo viejo)

- **Evidencia:**
  | Copia | Tamaño | Fecha de modificación |
  |---|---:|---|
  | Drive: `Infomes/datos_mercado.json` | **100.527 B** | 28-sep-2026 |
  | Desktop: `datos_mercado.json` | **71.131 B** | 13-sep-2026 |
  | Drive: `limpieza-2026-09-06/backups-datos_mercado/` (al menos 7 copias `.bak-…`) | 67.758 – 139.427 B | 18-23 ago |
  Hay una tercera copia en el repo (EM-I6 del doc 1; no accesible).
- **Impacto:** la copia **más nueva** (Drive, 28-sep) pesa un 41 % más que la de Desktop (13-sep). Si un script o una sesión "sincroniza" de Desktop a Drive, **pisa 15 días de datos**; si lo hace al revés, el mapa de Desktop pasa a ser el nuevo y las caducidades (`refrescar_antes_de`) cambian. Además el mapa del 28-sep está **caducado** a fecha de hoy (doc 1, EM-I6).
- **Fix concreto:**
  1. **Antes de nada**, copiar a mano el de Drive (28-sep) a `Desktop\JJImportMotors\_archive\datos_mercado_2026-09-28.json` (no sobrescribir nada).
  2. Decidir el máster (recomendado: el del repo tras diff con ambos; **NO VERIFICABLE** desde aquí) y marcar los otros como copia.
  3. Que `sync.manifest.json` incluya `datos_mercado.json` con hash y fecha, y que cualquier sync compruebe `mtime`: **nunca copiar un fichero más viejo sobre uno más nuevo**.
- **Test de regresión:** `sha256sum` de las tres copias debe coincidir tras la reconciliación; script de sync con prueba "origen más antiguo que destino → aborta".

---

## 🟠 Hallazgos importantes

### DR-I1 · `Infomes` (sic) es una copia masiva del 30-sep con basura dentro

- **Evidencia (carpetas de Drive, todas creadas el 30-sep a las 18:11 en la misma operación):**
  - Nombre con errata: `Infomes` (debería ser `Informes`).
  - Basura técnica: `.tmp_opel-astra-opc-2013-455420293` (con `fotos`, `publicidad`, `documentos`), `__pycache__`, `.legacy`, `paquetes`, `investigacion_modelos`, `export`, `datos`, `marca` (nombre de plantilla sin rellenar), `scripts`, `07-marketing` (carpeta de la skill, no de negocio).
  - Histórico mezclado con lo vigente: `_archive/limpieza-2026-09-06` (`duplicados-raiz`, `skills-superados`, `skill-v3.1.0-planning`, `backups-datos_mercado`) y `_archive/laravel-pre-v3-2026-09-06`.
  - Carpetas de ejemplo/pruebas con datos de coches (`informes/vw/tiguan`, `arteon`, `golf-variant`, `golf-r-variant`, `audi/q2|q3|q5|sq2|sq5|rs-q3`).
  - `informes/auditoria/` contiene auditorías antiguas (`auditoria_sistema_2026-09-09.md`, etc.) **y** `sync-skill-v3.9.0.zip` (110.917 B), una skill vieja.
- **Impacto:** Drive no sirve como biblioteca: es un volcado del disco. `.tmp_` y `__pycache__` nunca deberían salir de Desktop; los zips de skills viejas invitan a reinstalar una versión superada. **Contenido de `documentos`/`fotos`: no abierto** (podría tener datos de un coche concreto; el acceso es solo tuyo, ver ✅).
- **Fix:** (a) reglas de exclusión en el sync: `.tmp*`, `__pycache__`, `.legacy`, `*.bak*`, `*.zip` de skills, `_archive`; (b) renombrar `Infomes` → `Informes` **a mano** (1 clic, pendiente de tu OK); (c) mover `_archive` a una carpeta `99 Archivo` fuera del árbol vivo; (d) **no borrar nada automáticamente**; mandar a la papelera con tu visto bueno carpeta a carpeta.
- **Test:** tras el sync, `search_files` con `title contains '.tmp' or title contains '__pycache__'` → 0.

### DR-I2 · Cada estudio está duplicado en `.md` y `.pdf`

- **Evidencia:** en Drive, por cada marca hay pares como `vw_compacto_deportivo_2026-09-27.md` (9.539 B) + `vw_compacto_deportivo_2026-09-27.pdf` (154.914 B), `audi_…_2026-09-27.md/.pdf`, `seat_…`, `mercedes_…`; y una versión posterior `vw_compacto_deportivo_2026-09-30.md` (22.085 B) **sin** su PDF y sin que la del 27 se haya retirado.
- **Impacto:** viola la regla "UN solo fichero" (`entregables.md` l.93) y mezcla versiones: hay dos VW compacto con 3 días de diferencia y 2,3× de tamaño. No hay forma de saber cuál manda.
- **Fix:** una versión por estudio y marca en la carpeta viva; la anterior pasa a `99 Archivo`. Formato según la decisión D-1 del doc 2 (solo `.md`, PDF a petición).
- **Test:** `search_files` por `title contains 'compacto_deportivo'` agrupado por marca → máx. 1 `.md` vigente por marca.

### DR-I3 · No hay carpeta destino para estudios de mercado ni para e-commerce

- **Evidencia:** el árbol numerado llega de `01 Estrategia y plan` a `07 Vehículos (operaciones)`; **no existe** `00 Estudios de mercado` ni `08 E-commerce`. Los estudios están dentro de `Infomes/informes/<marca>/` y el índice del segmento en `Infomes/informes/mercado/00_indice_compacto_deportivo_2026-09-27.md`.
- **Impacto:** el flujo F-EM (doc 1) y el de e-commerce (doc 1, EC-1…EC-5) no tienen sitio definido; el índice de segmento existe (confirma el flujo que falta nº 1 del doc 2) pero en una ruta que la skill no conoce.
- **Fix:** crear (a mano o con `create_file` + `application/vnd.google-apps.folder`, **pendiente de tu OK**) `00 Estudios de mercado/<segmento>/<marca>/` y `08 E-commerce/`. Estructura propuesta abajo.

### DR-I4 · `references/google_drive.md` está desfasado respecto al conector

- **Evidencia:** el fichero documenta solo `create_file` (con `base64Content` y `disableConversionToGoogleType: true`, l.58-80, bien) y los flujos "legacy" 1/2. Para modificar un fichero manda (l.156-161): "Descárgalo… edita… Sube el resultado con `create_file`… mismo `title`", es decir, **crea otro fichero con el mismo nombre**. No recoge que el conector tiene **`update_file`** (herramienta `mcp__Google_Drive__update_file`).
- **Impacto:** cada subida crea un fichero nuevo (duplicados, DR-I2) en lugar de actualizar el existente.
- **Fix:** reescribir `google_drive.md` con las reglas del "Protocolo propuesto". Probar `update_file` antes con un fichero de prueba (**pendiente de tu OK**, no lo he ejecutado).
- **Test:** subir dos veces el mismo informe → 1 solo fichero en Drive, con `modifiedTime` nuevo.

### DR-I5 · La parte "operativa" de Drive se quedó en julio

- **Evidencia:** las carpetas `01…07` se crearon el 16-jul y se abrieron por última vez entre el 17 y el 30-jul (`viewedByMeTime`); antes, en esta misma auditoría, vi `clientes.json` vacío. No he abierto el contenido de `06 CRM y clientes` ni `07 Vehículos (operaciones)`.
- **Impacto:** si algún documento (ficha, contrato, CRM) depende de ese árbol, lleva 10 semanas sin tocarse. **NO VERIFICABLE** si Laravel lo usa.
- **Fix:** decidir en D-D1 si el árbol `01-07` sigue vivo o se archiva.

### DR-I6 · No se puede comprobar el plan de sincronización (SYNC-DRIVE-PLAN) ni el cliente Laravel

- **Evidencia:** el repo no está montado; tu prompt indica que el panel **no tiene cliente Google**.
- **Impacto:** no puedo decir qué fases están hechas. Lo único medible desde fuera: el sync del 30-sep es **una operación única** (todo creado a las 18:11 del mismo día), no un sync incremental.
- **Fix:** que Copilot devuelva, tabla en mano, "fase | hecha sí/no | evidencia" de SYNC-DRIVE-PLAN. **NO VERIFICABLE aquí.**

### DR-I7 · No hay métricas de Drive

- **Evidencia:** ni `HANDOFF.md` ni la memoria registran subidas fallidas, duplicados o tiempos. Con una sola operación masiva, no hay serie.
- **Fix:** una línea en `HANDOFF.md` por subida: `fecha | fichero | carpeta | creado/actualizado | OK/error`. Dos números a vigilar: **% de subidas OK** y **duplicados detectados** (objetivo 0).

---

## 🟡 Hallazgos menores

- **DR-M1 · Backups con nombre de incidente:** `datos_mercado.json.bak-23ago-mojibake` (79.574 B) indica que el 23-ago hubo un problema de codificación (acentos). Tipo MIME `application/octet-stream`. Conservar uno solo con el nombre canónico `datos_mercado_<fecha>.json`.
- **DR-M2 · Fecha de `HANDOFF.md` en Drive:** 26-sep (9.827 B); misma fecha que la última entrada real. No hay entradas posteriores (doc 2, IV-I6).
- **DR-M3 · Carpeta `marca` sin rellenar** aparece dos veces (hijas de `laravel-pre-v3` y de `marketing`): nombre de plantilla; vaciar o renombrar.

---

## ✅ Verificado correcto

- **Permisos:** la carpeta `auditoria` (`1DtnlnV3dC9x98dOvNJMXt-bGG6Tc5iwu`) tiene un único permiso: **propietario jjimportmotors@gmail.com**, sin compartir. (Solo he comprobado esa carpeta, no el resto del árbol.)
- **Subida de `.md` sin conversión:** `google_drive.md` manda `disableConversionToGoogleType: true` y los `.md` que hay en Drive conservan `fileExtension: md` y tipo `text/markdown` (no se convirtieron a Google Doc).
- **`base64Content`** para binarios está documentado y es el correcto para PDF/ZIP.
- **Fechas en nombre de fichero** (`_2026-09-27`) sistemáticas: facilita detectar duplicados.
- **No hay datos de clientes reales a la vista** en los nombres de fichero (los ejemplos son coches de prueba); contenido interno no abierto.

---

## 🧭 Protocolo propuesto (pendiente de tu OK)

**Estructura:**

```
Drive raíz (1Yto4AwN…)
├── 00 Estudios de mercado/<segmento>/<marca>/     ← F-EM, índice de segmento, datos_mercado_<fecha>.json
├── 01-07 (se mantienen si D-D1 los conserva)
├── 08 E-commerce/                                  ← ecommerce-tuning (hoy sin destino)
├── Informes/<marca>/<modelo>/                      ← A, B (renombrar "Infomes")
├── Informes/auditoria/                             ← auditorías (esta carpeta ya existe)
└── 99 Archivo/                                     ← _archive, versiones anteriores
```

**Reglas de subida (para `google_drive.md` v2):**
1. Buscar primero por nombre exacto en la carpeta destino. Si existe → **`update_file`** (probarlo antes); si no → `create_file`.
2. `.md` siempre con `disableConversionToGoogleType: true`; nunca convertir a Google Doc sin que lo pidas.
3. Nada de `.tmp*`, `__pycache__`, `.legacy`, `*.bak*`, zips de skills.
4. Una versión vigente por estudio y marca; la anterior se mueve a `99 Archivo` (a mano o con tu OK, no borrar).
5. Formato: según la decisión de PDF (D-1 del doc 2); nunca el mismo trabajo en `.md` y `.pdf` a la vez.
6. Tras cada subida, una línea en `HANDOFF.md` (métrica DR-I7).

**Política de Drive por flujo** (para cerrar la columna "Drive OK" del mapa del doc 2):

| Flujo | Qué sube a Drive | Carpeta | ¿Se sube el ZIP? |
|---|---|---|:---:|
| A Unidad | `informe_unidad_*.md` | `Informes/<marca>/<modelo>/` | No (va al panel) |
| B Modelo | `informe_busqueda_*.md` | `Informes/<marca>/<modelo>/` | No |
| C Mercado | `informe_busqueda_<segmento>_*.md` | `00 Estudios de mercado/<segmento>/` | No |
| D Descubrimiento | `informe_modelos_*.md` | `Informes/descubrimiento/` | No |
| E Catálogo | `informe_busqueda_<categoria>_*.md` | `Informes/catalogo/` (nombre pendiente, D-7 del doc 2) | No |
| M Marketing | nada (vive en el panel) | — | No |
| F-EM Estudio | `<marca>_<segmento>_<enfoque>_*.md` + `00_indice_*.md` | `00 Estudios de mercado/<segmento>/<marca>/` | No |
| F-GM Guía de marcas | **NO VERIFICABLE** (generador en el repo) | `00 Estudios de mercado/guias/` (propuesta) | — |
| Auditorías | `AUDITORIA_*.md` | `Informes/auditoria/` | No |

---

## 📋 Pendiente de tu OK

| # | Decisión | Mi recomendación |
|---|---|---|
| **D-D1** | Política de Drive: ¿canal de entrega (propuesta) o solo backup? ¿Se mantiene el árbol 01-07? | Canal de entrega y lectura móvil; Desktop sigue siendo la fuente de verdad. |
| **D-D2** | Reconciliar `datos_mercado.json` (Drive 28-sep, 100.527 B / Desktop 13-sep, 71.131 B / repo): archivar el de Drive y fijar un máster. | Sí, antes de cualquier otro sync. |
| **D-D3** | Probar `update_file` con un fichero de prueba y, si funciona, adoptarlo como regla. | Sí. |
| **D-D4** | Renombrar `Infomes` → `Informes` y crear `00 Estudios de mercado` y `08 E-commerce`. | Sí, manual o con tu OK explícito. |
| **D-D5** | Retirar de Drive `.tmp*`, `__pycache__`, `.legacy`, zips de skills viejas y duplicados `.md`/`.pdf`: **a la papelera, carpeta a carpeta, con tu visto bueno**. | Sí, nunca en bloque. |
| **D-D6** | Que Copilot entregue el estado de SYNC-DRIVE-PLAN fase a fase y confirme si hay cliente Google en Laravel. | Sí. |

**Paquetes a instalar o desinstalar: ninguno.**

---

## 🎯 Resumen ejecutivo (para WhatsApp)

Drive hoy es una **foto del 30-sep**: un volcado de tu disco (con `.tmp`, `__pycache__` y una carpeta llamada "Infomes"), cada estudio duplicado en `.md` y `.pdf`, y **nada de octubre**. Las instrucciones se contradicen: unas dicen "Drive ya no es operativo", otras lo usan como canal. Lo más peligroso: `datos_mercado.json` tiene tres copias distintas (Drive 28-sep, 100 KB; Desktop 13-sep, 71 KB) y un sync mal hecho pisaría lo nuevo con lo viejo. Propongo una política única (Desktop manda, Drive entrega), carpetas `00 Estudios` y `08 E-commerce`, y subir con `update_file` en lugar de crear duplicados. No he movido ni borrado nada.

---

## Tabla final

| Hallazgo | Skill / Componente | Severidad | Fix |
|---|---|:---:|---|
| DR-C1 Tres políticas de Drive contradictorias | `operaciones.md`, `CLAUDE.md`, `google_drive.md`, proyecto | 🔴 | Una política en 4 sitios (D-D1) |
| DR-C2 `datos_mercado.json` divergente (100.527 B vs 71.131 B) | Drive / Desktop / repo | 🔴 | Archivar, fijar máster, sync por `mtime` (D-D2) |
| DR-I1 `Infomes` con basura (.tmp, `__pycache__`, zips viejos) | Drive | 🟠 | Exclusiones + renombrar + papelera manual (D-D4/5) |
| DR-I2 Duplicados `.md` + `.pdf` y versiones mezcladas | Drive | 🟠 | Una versión vigente por marca |
| DR-I3 Sin destino para estudios ni e-commerce | Drive | 🟠 | Crear `00` y `08` (D-D4) |
| DR-I4 `google_drive.md` sin `update_file` | skill | 🟠 | Reescribir v2 (D-D3) |
| DR-I5 Árbol 01-07 parado desde julio | Drive | 🟠 | Decidir en D-D1 |
| DR-I6 SYNC-DRIVE-PLAN y cliente Laravel no verificables | repo | 🟠 | Informe de Copilot (D-D6) |
| DR-I7 Sin métricas de Drive | HANDOFF | 🟠 | Línea por subida |
| DR-M1…M3 | Drive | 🟡 | Ver menores |
