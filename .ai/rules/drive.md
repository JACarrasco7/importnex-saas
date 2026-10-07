---
paths:
  - 'docs/SYNC-DRIVE-PLAN.md'
  - 'app/Services/Google/**'
  - 'app/Jobs/UploadToDrive.php'
---

# Google Drive — sincronización transversal

> **Contrato de diseño:** `docs/SYNC-DRIVE-PLAN.md` (DRAFT 2026-10-07).
> Esta regla es el resumen ejecutivo; el plan es la fuente completa.

## Carpetas canónicas (NO crear nuevas sin avisar al usuario)

- `00 Estudios de mercado` ← skill A (estudio-mercado) — DRAFT
- `06 CRM y clientes` ← skill B (importacion-vehiculos) — YA EXISTE
- `07 Vehículos (operaciones)` ← skill B — YA EXISTE
- `08 E-commerce` ← skill C (ecommerce-tuning) — DRAFT, opcional
- `09 Reportes automáticos` ← Laravel — DRAFT

## Reglas duras

1. **Subir SIEMPRE con `base64Content`**, NUNCA con `content` (corrupto en
   archivos >1 KB). Verificado: `importacion-vehiculos/references/google_drive.md`.
2. `disableConversionToGoogleType: true` en los 3 maestros (Registro,
   Inventario, Clientes) y en ZIPs/plantillas que se reabran con `openpyxl`.
3. Protocolo "actualizar maestro": buscar → descargar con
   `download_file_content` (NUNCA de memoria) → fusionar → subir. La API
   de Drive no sobrescribe; descarga previa evita pisar ediciones del
   compañero.
4. Comprobar `fileSize` devuelto contra el local antes de dar por buena
   la subida.
5. **NUNCA** subir archivos a Drive desde una petición HTTP del panel:
   encolar `UploadToDrive` job y despachar con cron (5 min).
6. **NUNCA** crear carpetas nuevas en Drive sin confirmar con el
   usuario (el conector las crea, pero la convención numérica 00-09
   puede romper búsquedas del compañero).

## Credenciales

- Service Account JSON en `storage/app/google/service-account.json`
  (`.gitignore` + escáner de secretos en pre-commit).
- `config/google.php` con `enable_uploads = false` por defecto
  (cambiar a `true` SOLO tras validar en staging).
- **NUNCA** meter credenciales en `.env` (rotación cada deploy).

## Quién puede escribir

- ✅ IA (Claude Desktop) — vía MCP `Google Drive` connector (UUID dinámico).
- ✅ Cron Laravel `drive:flush-uploads` cada 5 min.
- ❌ Panel HTTP directo (latencia 200-800ms; encolar es 1 línea más).
- ❌ Otros agentes sin coordination.

## Lock

- `storage/app/locks/drive-<folder>.lock` (30s) cuando Laravel escribe
  Y la IA también puedan colisionar (reusar patrón de `LockFile.ps1`).

## Tests obligatorios antes de cerrar Fase 4

- `tests/Feature/Drive/UploadToDriveTest.php` — fake del cliente Google.
- `tests/Feature/Drive/DriveFlushUploadsTest.php` — pending → done,
  reintentos con backoff, `max_attempts=3`.
- `tests/Unit/Drive/DriveClientTest.php` — carga SA, excepción clara
  si JSON inválido, aborta si `enable_uploads=false`.
- E2E: subir + descargar + validar `SHA256(local) == SHA256(descargado)`.
