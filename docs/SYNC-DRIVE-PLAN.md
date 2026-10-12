# Plan de sincronización con Google Drive (DRAFT 2026-10-07)

> **Estado:** borrador. La auditoría profesional del 2026-10-07 lo tendrá
> en cuenta como "contrato a verificar" en la dimensión **D11** del prompt
> [`prompts/auditoria-skills-2026-10-07.md`](prompts/auditoria-skills-2026-10-07.md).
>
> **Por qué este plan:** hoy solo `importacion-vehiculos` tiene flujo Drive
> ([`references/google_drive.md`](../.claude/skills/importacion-vehiculos/references/google_drive.md)),
> y el panel Laravel NO tiene cliente Google todavía. Si queremos que
> "toda la cartera" (informes de estudio de mercado, de e-commerce, ZIPs
> de clientes, reportes semanales, etc.) esté sincronizada con Drive, hay
> que cerrar 3 frentes a la vez: 2 skills nuevas + 1 integración Laravel.

---

## 0. Estado actual (lo que YA funciona)

| Cosa | Estado | Fuente |
|---|---|---|
| `importacion-vehiculos` → Drive (xlsx/json) | ✅ Maduro | `references/google_drive.md` |
| Conector MCP `Google Drive` en Claude/Cowork | ✅ Activo (vía UUID dinámico) | idem |
| Subida con `base64Content` (no `content` corrupto) | ✅ Regla dura verificada | idem |
| 2 carpetas canónicas Drive ("07 Vehículos", "06 CRM") | ✅ Pactadas, NO recrear | idem |
| Protocolo "actualizar maestro" (descargar → fusionar → subir) | ✅ Documentado | idem |
| Estudio-mercado → Drive | ❌ No existe | gap |
| Ecommerce-tuning → Drive | ❌ No existe (fuera de alcance inmediato) | gap |
| Laravel → Drive (escritura) | ❌ Sin cliente Google en composer | gap |
| Laravel ← Drive (webhook Drive → re-leer) | ❌ No existe | gap |
| Regla `.ai/rules/drive.md` que documente TODO lo anterior | ❌ No existe | gap |

---

## 1. Diseño objetivo (cómo debería quedar)

### 1.1 Estructura de carpetas Drive (canónica, NO cambiar)

```
📁 Drive JJ Import Motors
├── 📁 00 Estudios de mercado        ← NUEVA (skill A)
│   ├── 📁 <marca>
│   │   ├── 📄 informe-<marca>_<segmento>_<enfoque>_<YYYY-MM-DD>.md
│   │   ├── 📄 guia-<marca>_<YYYY-MM-DD>.md
│   │   └── 📄 guia-<marca>_<YYYY-MM-DD>.pdf
│   └── 📄 datos_mercado.json        ← espejo de la ruta dual
│
├── 📁 06 CRM y clientes              ← YA EXISTE (skill B)
│   ├── 📄 Clientes.xlsx
│   └── 📄 Ficha_Cliente_NOMBRE.xlsx (uno por cliente)
│
├── 📁 07 Vehículos (operaciones)     ← YA EXISTE (skill B)
│   ├── 📄 Registro_Operaciones_Importacion.xlsx
│   ├── 📄 Inventario_Coches_Oferta.xlsx
│   ├── 📄 coches.json
│   └── 📄 Plantilla_Importacion_MARCA_MODELO_AAAA-MM.xlsx
│
├── 📁 08 E-commerce                  ← NUEVA (skill C, opcional)
│   ├── 📁 <slug-producto>
│   │   ├── 📄 informe-viabilidad-<slug>.md
│   │   ├── 📄 informe-senales-<YYYY-MM>.md
│   │   └── 📄 borrador-shopify-<slug>.csv
│   └── 📄 catalogo-productos.json
│
└── 📁 09 Reportes automáticos        ← NUEVA (Laravel)
    ├── 📄 reporte-semanal-<YYYY-WW>.pdf
    ├── 📄 alertas-mercado-<YYYY-MM>.csv
    └── 📄 kpis-mensuales-<YYYY-MM>.xlsx
```

> Regla: las 2 carpetas existentes NO se renombran (otros agentes/servicios
> pueden tener enlaces a ellas). Las 3 nuevas siguen el patrón numérico
> correlativo.

### 1.2 Sentido de la sincronización

```
                        ┌─────────────┐
   Claude Desktop ──→   │  Drive MCP  │   ──→ Drive (carpetas 00/06/07/08)
   (skills A/B/C)       └─────────────┘
                                ▲
                                │ (webhook Drive → API Laravel)
                                │
   ┌──────────────┐              │
   │  Laravel     │ ────────────→│  (Service Account OAuth2)
   │  (panel)     │   escribir
   └──────────────┘
```

**Reglas de oro:**

1. **IA → Drive (escritura alta frecuencia):** solo la IA sube
   (claude-desktop). El panel Laravel NO sube directamente: encola un
   job `UploadToDrive` que un cron ejecuta cada 5 min.
2. **Drive → IA (lectura puntual):** solo el `sync_web_data.py` descarga
   las 3 listas maestras (`coches.json`, `clientes.json`,
   `Inventario_Coches_Oferta.xlsx`) cuando la IA va a escribir en
   ellas (protocolo "actualizar maestro" que ya existe).
3. **Laravel ← Drive (webhook opcional):** si Drive notifica cambios en
   `coches.json` o `clientes.json`, Laravel re-lee y refresca su caché.
   Esto SOLO se monta si se observa divergencia IA↔panel en producción
   (hoy no se ha visto → no implementarlo hasta que haga falta).

### 1.3 Composición técnica (mínima, no sobre-ingeniería)

| Capa | Tecnología | Justificación |
|---|---|---|
| Skill A/B/C (Claude Desktop) | MCP `Google Drive` ya conectado | Cero instalación nueva |
| Laravel escritura | `google/apiclient` v2.18+ (Composer) | Cliente oficial; OAuth2 service account |
| Laravel cola | `queue:work database` (ya existe driver) | Ya en uso, no añadir Redis |
| Laravel cron | `Schedule::command('drive:flush-uploads')->everyFiveMinutes()` | Acumular subidas, no 1 por 1 |
| Auth | Service Account JSON en `storage/app/google/service-account.json` | Sin OAuth de usuario, automatizable |
| Auditoría | `storage/logs/laravel-*.log` con tag `drive.upload` | Trazabilidad, 0 info sensible |

> **PROHIBIDO:** meter credenciales en `.env` (rotación cada deploy).
> Service account en `storage/app/` con `.gitignore`.

---

## 2. Plan de implementación por fases

### Fase 1 — Regla fundacional (30 min) — **HACER PRIMERO**

Crear `.ai/rules/drive.md` con:

- Carpetas canónicas Drive (00/06/07/08/09) y protocolo "no crear nuevas
  sin avisar".
- Regla `base64Content` vs `content` (la corrupta, ya documentada).
- Protocolo "actualizar maestro" (descargar → fusionar → subir).
- Quién PUEDE escribir (solo IA + cron Laravel dedicado).
- Quién NO debe escribir (panel en petición HTTP, otros agentes sin
  coordination).

**Por qué primero:** el resto del plan hereda esta regla. Sin ella, cada
script sube como quiere y se rompe lo verificado en la skill B.

### Fase 2 — Skill A estudio-mercado → Drive (2-3 h)

Subir a "00 Estudios de mercado":

- `datos_mercado.json` (cuando cambia, vía `mapa:empujar` artisan
  command o al final de cada estudio).
- `informe-<marca>_<segmento>_<enfoque>_<YYYY-MM-DD>.md` (el de la
  plantilla `informe_mercado.md`).
- `guia-<marca>_<YYYY-MM-DD>.{md,pdf}` (las generadas por
  `scripts/generar_guia_marcas.py`).

**Decisión:** ¿se sube también el PDF? El PDF es "bonito" pero el MD
tiene enlaces vivos. **Por defecto, subir SOLO el MD** y dejar el PDF
como entrega local. Solo si el usuario lo pide explícito en el chat
("sube también el PDF a Drive"), se sube.

Nuevo script: `scripts/subir_drive.py` (mismo patrón que el Drive actual
de la skill B, pero apuntando a "00 Estudios de mercado"). Reutilizar
`mcp__<uuid>__search_files` + `mcp__<uuid>__create_file`.

### Fase 3 — Skill C ecommerce-tuning → Drive (1-2 h, opcional)

**Decisión de alcance:** esta skill NO genera ZIPs para Laravel, pero
sus informes (viabilidad, señales) sí son "cartera" del usuario. Propuesta:

- Subir `informe-viabilidad-<slug>.md` y `catalogo-productos.json` a
  "08 E-commerce".
- **NO** subir los borradores de Shopify (contienen claves API del
  proveedor en algunos casos; si no, sí).

**Bloqueante:** esta skill aún no está en producción (su CHANGELOG va
por v0.1.0 y no tiene referencias/ completa). Dejarla para cuando
empiece a entregar.

### Fase 4 — Laravel → Drive (4-6 h) — **LA MÁS CARA**

Módulos nuevos:

1. `app/Services/Google/DriveClient.php` — wrapper de `google/apiclient`,
   carga credenciales de `storage/app/google/service-account.json`,
   método único `upload(string $path, string $content, string
   $mimeType): string` (devuelve `viewUrl`).
2. `app/Jobs/UploadToDrive.php` — job cola que serializa la subida (id
   por archivo, no duplica si ya existe en Drive).
3. `app/Console/Commands/DriveFlushUploads.php` — corre cada 5 min,
   despacha los jobs pendientes de la tabla `drive_uploads` (migración
   nueva).
4. `app/Console/Commands/DrivePruneDuplicates.php` — manual (no
   automático): busca duplicados en carpetas 06/07 y los mueve a
   `papelera/duplicados-YYYY-MM-DD/` con log. La API de Drive no borra
   "duplicados" automáticamente: hay que hacerlo a mano y dejar log.
5. `config/google.php` — `service_account_path`, `folder_ids` (00/06/
   07/08/09), `enable_uploads` (boolean, default false hasta validar
   en staging).

**Migración nueva:** `database/migrations/2026_XX_XX_create_drive_uploads_table.php`
con columnas: `id`, `local_path`, `drive_folder_id`, `drive_filename`,
`status` (pending/uploading/done/failed), `attempts`, `last_error`,
`uploaded_at`, `drive_file_id`, `view_url`.

**Tests:**

- `tests/Feature/Drive/UploadToDriveTest.php` — fake del cliente
  Google, verifica que el job llama `upload()` con los argumentos
  correctos.
- `tests/Feature/Drive/DriveFlushUploadsTest.php` — verifica que
  pendientes → done, fallidos se reintentan con backoff, y los
  `max_attempts=3` van a `failed_jobs`.
- `tests/Unit/Drive/DriveClientTest.php` — verifica que carga
  credenciales, que lanza excepción clara si el JSON es inválido, y
  que `enable_uploads=false` aborta sin subir.

### Fase 5 — Documentación + auditoría (1 h)

- `docs/SYNC-DRIVE-PLAN.md` (este doc) → marcar cada fase con ✅/🟡.
- `.claude/skills/importacion-vehiculos/references/google_drive.md` →
  añadir nota: "Adicionalmente, las skills A y C tienen su propio
  protocolo en este mismo plan: `docs/SYNC-DRIVE-PLAN.md`".
- AUDITORIA_DRIVE_<YYYY-MM-DD>.md — pasar el prompt de auditoría
  profesional sobre las 3 skills + Laravel; D11 validará este plan
  contra la realidad.
- `docs/HANDOFF.md` — entrada con la fase completada.

---

## 3. Decisiones pendientes (requieren tu OK)

> Estas NO son técnicas: son de producto. Claude Desktop te las
> preguntará al implementar.

1. **¿Crear la carpeta "00 Estudios de mercado" en Drive YA?**
   - Sí (recomendado): desde el primer estudio, queda organizado.
   - No: esperar al primer "sube el informe a Drive" del usuario.
2. **¿Subir PDF además de MD a "00 Estudios de mercado"?**
   - Solo MD (recomendado): enlaces vivos, búsqueda, ~10x más pequeño.
   - MD + PDF: cómodo para imprimir, dobla almacenamiento.
3. **¿Implementar la skill C ecommerce-tuning → Drive en esta ronda?**
   - No (recomendado): la skill no está madura aún.
   - Sí: dejar Drive transversal completo desde el día 1.
4. **Service Account Google: ¿dónde la creo?**
   - Crear un proyecto GCP nuevo `jjimportmotors-drive` con SA
     `jjimportmotors-drive@...iam.gserviceaccount.com` (recomendado:
     limpio, sin contaminar otros proyectos del usuario).
   - Reusar un proyecto GCP que ya tengas.
5. **¿Compartimos la Service Account con el compañero?**
   - Sí: solo lectura para su cuenta personal.
   - No: solo tú (más seguro, pero limita trabajo paralelo).

---

## 4. Riesgos conocidos

| Riesgo | Mitigación |
|---|---|
| Service account JSON se sube al repo por error | `.gitignore` en `storage/app/google/`, scanner `scripts/escanear_secretos.py` (ya existe) en pre-commit |
| Cuota de API de Drive excedida (subidas masivas) | Throttle: 1 subida cada 2s, batch por carpeta, log de cuota diaria |
| Archivo subido corrupto (caso `content` vs `base64Content`) | Regla D11 + test E2E: subir + descargar + validar hash SHA256 = original |
| Duplicados en "actualizar maestro" | DriveFlushUploads detecta por nombre+mtime, marca error, no sube |
| Laravel escribe y la IA también, race condition | Lock de 30s en `storage/app/locks/drive-<folder>.lock` (similar a `LockFile.ps1` ya existente) |
| Drive cambia la API / permisos | Wrapper `DriveClient` único, actualizar ahí, no en cada callsite |
| GDPR: informes Drive tienen datos personales (clientes.xlsx) | Service account con acceso solo a las 5 carpetas; logs sin nombres propios; "Ficha_Cliente_NOMBRE.xlsx" redactable a "Ficha_Cliente_id.xlsx" si se quiere |

---

## 5. Métricas de éxito (post-implementación)

- **Tasa de éxito de subidas** (status=`done` / total): objetivo ≥ 99%.
- **Tiempo medio de subida** (job dispatch → `view_url` listo): objetivo
  ≤ 30s en el 95% de los casos.
- **Duplicados por mes** en carpetas 06/07: objetivo 0 (revisar log
  `storage/logs/drive-*.log`).
- **Tiempo de auditoría D11** (volver a pasar el prompt de auditoría):
  sin hallazgos críticos nuevos en D11.

---

## 6. NO-objetivos (fuera de alcance)

- **Sincronización en tiempo real** (<5s IA→panel vía Drive): NO.
  Hoy basta con un ciclo de 5 min del cron.
- **Panel Laravel como "escritor principal"** de Drive: NO. El panel
  encola, no escribe directamente. Razón: latencia HTTP al API de
  Drive es 200-800ms — meterlo en cola es 1 línea más y no degrada
  UX.
- **Sustituir el sync-desktop.ps1** (Desktop→repo) por Drive: NO.
  El sync-desktop es local, instantáneo, y no falla. Drive es
  complementario.
- **Convertir todos los artefactos a Google Sheets nativo** (sin
  `.xlsx`): NO. La skill B ya documenta que la conversión reordena
  fórmulas y rompe los scripts `openpyxl`. Mantener `.xlsx` real.

---

## 7. Referencias cruzadas

- Skill B (Drive ya implementado):
  [`importacion-vehiculos/references/google_drive.md`](../.claude/skills/importacion-vehiculos/references/google_drive.md)
- Script de Drive en skill B (referencia para subir a "00/06/07"):
  [`importacion-vehiculos/scripts/sync_web_data.py`](../.claude/skills/importacion-vehiculos/scripts/sync_web_data.py)
- Prompt de auditoría que valida este plan (D11):
  [`prompts/auditoria-skills-2026-10-07.md`](prompts/auditoria-skills-2026-10-07.md)
- Lockfile PowerShell ya existente (patrón a reusar para Laravel):
  [`scripts/LockFile.ps1`](../scripts/LockFile.ps1)
- Escáner de secretos (pre-commit):
  [`_tmp/escanear_secretos.py`](../_tmp/escanear_secretos.py) →
  promover a `scripts/escanear_secretos.py` si se mete SA JSON en
  el repo.