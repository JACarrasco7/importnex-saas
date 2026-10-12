# Skill `importacion-vehiculos` v3.6.1 — ZIP portable

> **Para Claude Desktop / Claude.ai**: este ZIP contiene la skill completa, lista para descomprimir y cargar.

## 📦 Archivo

- **Nombre**: `skills-importacion-vehiculos-v3.6.1-20260905.zip`
- **Tamaño**: 407 KB (416.779 bytes)
- **SHA256**: `e3bb037e03a4c95585487c0c5cdb2c79a9c3e646182966334841ea66180ce1cf`
- **Fecha**: 2026-09-05
- **Origen**: `c:\laragon\www\importnexcore\.claude\skills\importacion-vehiculos\`
- **Producción**: ya desplegado en `jjimportmotors.on-forge.com` (release `77029810`, commit `501ec89`).

## 🚀 Instalación en Claude Desktop

### Opción A — Reemplazar skill existente

1. Cierra Claude Desktop.
2. Ve a `C:\Users\<TU_USUARIO>\.claude\skills\` (Windows) o `~/.claude/skills/` (macOS/Linux).
3. **Respalda** la versión actual: renombra `importacion-vehiculos/` a `importacion-vehiculos.bak/`.
4. Descomprime este ZIP en la misma carpeta `skills/` (se creará `importacion-vehiculos/`).
5. Reinicia Claude Desktop.
6. Verifica que el skill carga preguntando: `qué versión de la skill de importación tienes?` → debe responder `3.6.1`.

### Opción B — Instalación paralela

Descomprime en `skills/` con nombre distinto (`importacion-vehiculos-v3/`). Claude Desktop detecta múltiples skills si están en subcarpetas, pero **solo la primera por nombre** se activa. Para v3.6.1 necesitas reemplazar.

### Opción C — Vía git

Si prefieres versionado limpio:

```bash
cd ~/.claude/skills/importacion-vehiculos
git init  # solo la primera vez
git remote add origin <repo-skill>  # opcional
# Desde el ZIP:
unzip skills-importacion-vehiculos-v3.6.1-20260905.zip -d .
```

## 📂 Qué hay dentro

```
importacion-vehiculos/
├── SKILL.md             (82 KB) — entrypoint, frontmatter v3.6.1
├── CHANGELOG.md         (94 KB) — historial v1 → v3.6.1
├── ROADMAP.md
├── 01-arranque/         (3 archivos) — briefing, prompts, planificador
├── 02-flujos/           (7 archivos) — UNIDAD/MODELO/MERCADO/DESCUBRIMIENTO + extractores + navegación + páginas reales + playbook
├── 03-informes/         (4 archivos) — contrato, dossier, informe técnico, comparables
├── 04-negocio/          (2 archivos) — costes, riesgos
├── 05-operaciones/      (2 archivos) — operaciones, cierre
├── 06-reglas/           (1 archivo) — anti-patrones (A22: ZIP sin fotos/marketing inválido)
├── memoria/             (8 archivos) — MEMORIA.md, encargos, modelos-medidos, vendedores-confianza, trampas, mejoras
├── references/          (2 archivos) — cell_map, google_drive
├── scripts/             (11 .py + 1 fixture .json) — empaquetar.py (44 KB) + utilidades
└── assets/              (8 archivos) — 5 .xlsx + 2 .html + plantilla PDF marca
```

**Total**: 51 archivos, 938 KB descomprimido, 407 KB comprimido.

## 🆕 Novedades v3.6.1 (vs v3.5.1)

| Pieza | Antes | Ahora |
|---|---|---|
| `scripts/empaquetar.py` | ❌ referenciado 17 veces, no existía | ✅ ZIP reproducible con descarga validada de fotos + 5 esqueletos + manifest v2 |
| `scripts/fixtures/flujo-a-bmw-320d-2020-test.json` | ❌ | ✅ ejemplo real para pruebas |
| Reglas marketing en SKILL.md | dispersas | §ZIP con reglas duras (fotos obligatorias, marketing obligatorio) |
| Anti-patrón A22 | ❌ | ✅ "ZIP sin fotos validadas o sin marketing = entrega INVÁLIDA" |
| `contrato.md` | sin nota Laravel | ✅ nota: Laravel importa .txt → `CarMarketingContent` (status draft) |
| Esqueletos `redes-sociales.txt` / `anuncio-portales.txt` | 1 bloque genérico | 3 redes (tiktok/instagram/facebook) × 3 posts + 3 stories + 4 portales |

## ✅ Compatibilidad backend Laravel

Esta skill produce ZIPs que **Laravel 13.24.0 + ImportnexCore v3.6.1** sabe leer:

- Tabla `car_marketing_contents` con campos `kind`, `slot`, `subir_pasos` (migración `2026_09_05_150815`).
- 13 entradas de marketing por coche (3 redes × 3 posts + 3 stories + 4 portales).
- Endpoint de import: `POST /api/import-valuation` (local) o formulario web `/imports`.

## 🔍 Verificación rápida tras instalar

En Claude Desktop, escribe:

```
qué versión de la skill de importación tienes?
```

Debe responder `3.6.1`. Si dice una versión anterior, el ZIP no se cargó — repite paso 4.

## 📞 Soporte

- **Repo Laravel**: <https://github.com/JACarrasco7/importnex-saas>
- **Producción**: <https://jjimportmotors.on-forge.com>
- **Plan original**: `c:\laragon\www\importnexcore\docs\PLAN_MARKETING_ZIP_2026-09-03.md`
