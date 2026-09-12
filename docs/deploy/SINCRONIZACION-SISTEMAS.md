# Sincronización Laravel ↔ Claude — Arquitectura (12-sep-2026)

> Diseño de los **7 canales** que conectan el workspace Laravel (este repo) con
> Claude Desktop, claude.ai y la API de ImportnexCore. Incluye el mapa actual,
> sus debilidades, y la dirección correcta (de un solo comando y un solo
> manifiesto).

## 📐 Diagrama

```
┌─────────────────────────────────────────────────────────────────────┐
│ WORKSPACE LARAVEL (este repo)  · Copilot/VS Code                     │
│                                                                       │
│  .claude/skills/<skill>/   ─┐                                         │
│  docs/claude-desktop/      ─┤   másters (ver DOCS-INDEX)            │
│  docs/memoria-desktop/     ─┤                                         │
│  datos_mercado.json        ─┤                                         │
│                             │   build-skill-zips.ps1                 │
│                             ▼   sync-desktop.ps1                     │
│                                                                       │
│  .claude/skills/_dist/*.zip  · docs/SKILLS.md (versión + SHA)        │
│  sync.manifest.json          · single source of truth del cableado   │
└─────────────────────────────────────────────────────────────────────┘
            │                                    ▲
            │ 1. ZIP download                     │ 4. POST api/import-valuation
            │ 2. Drag-drop Desktop                │    + ImportValuationApiController
            │ 3. Másters a .claude/ de Desktop    │    devuelve car_id, car_url
            │                                    │
            ▼                                    │
┌─────────────────────────────────────────────────────────────────────┐
│ CLAUDE DESKTOP + claude.ai                                            │
│                                                                       │
│  .skill.zip instalados  · CLAUDE.md + 3 docs raíz  · MEMORIA.md      │
│  · .claude/memoria/*.md  · datos_mercado.json espejo                 │
│                                                                       │
│  ► Encargos nuevos → ZIP/JSON a Desktop/laravel/informes/  ◄─────────┘
└─────────────────────────────────────────────────────────────────────┘
```

## 🧭 Los 7 canales

| # | Dirección | Qué | Cómo | Disparador | Estado |
|---|---|---|---|---|---|
| 1 | L → C | Skills fuente → ZIPs | `scripts/build-skill-zips.ps1` | Manual, obligatorio tras editar skill | Regla dura: `.ai/rules/skills-sync.md` |
| 2 | L → C | ZIPs → Desktop / Cowork | Drag-drop en gestor de skills | Manual | Verificado en canal 4 |
| 3 | L → C | Másters docs → Desktop | `scripts/sync-desktop.ps1 -Apply` | Manual | Reemplaza al `.bat` del ZIP de Claude |
| 4 | C → L | `datos_mercado.json` dual | Política: repo canónico, Desktop espejo | Vía canal 3 | Idempotente |
| 5 | C → L | JSON/ZIP → API producción | `subir-informe.ps1` o `.bat` (drag) | Tras cerrar cada informe | Token en `$env:IMPORTNEX_TOKEN` (no commiteado) |
| 6 | L → L | Deploy Forge | `git push origin master` → Forge pull+build | Manual | — |
| 7 | L → C | Round-trip encargo | `subir-informe.ps1` anota en `encargos.md` del skill | Automático (si el .md existe) | PASO 0 cache retroalimentado |

## 🛠️ Herramientas (todo en el repo)

| Archivo | Función |
|---|---|
| `scripts/build-skill-zips.ps1` | Empaqueta skills + regenera `docs/SKILLS.md` con SHA/version |
| `scripts/sync-desktop.ps1` | Replica másters + datos_mercado.json + verifica versiones (canales 3+4+2) |
| `subir-informe.ps1` | Sube un JSON/folder a la API; round-trip en `encargos.md` (canal 5+7) |
| `subir-informe.bat` | Wrapper drag-drop para Windows |
| `sync.manifest.json` | Manifiesto único: qué archivo va a qué sitio, qué token usa qué script |
| `.env.gitignore` | Plantilla de ignores para `.env.local` / tokens locales |

## 🔐 Seguridad

- **Token API** (`IMPORTNEX_TOKEN`) → variable de entorno Windows (`User` scope), nunca commiteado.
- **Password BD Forge** → eliminada de `docs/deploy/HEIDISQL_FORGE.md` el 12-sep-2026. Pendiente rotar la original (sigue en historial git, se invalida al rotar).

## ⚠️ Trampas conocidas

- **Edición concurrente** (Claude Desktop + claude.ai + VS Code a la vez) → un agente puede tapar la edición de otro sobre `docs/DOCS-INDEX.md` o `MEMORIA.md`. Mitigado por la regla "máster = repo; copias solo de distribución" (ver `docs/DOCS-INDEX.md` y `docs/memoria-desktop/MEMORIA.md` banner).
- **PowerShell 5.1 manglea em-dashes en `git commit -m`** → ASCII puro en mensajes; el script usa `Write-Host` con UTF-8 explícito para evitarlo.
- **Skill.zip generado por `Compress-Archive` de PowerShell** falla la validación JS de Claude Skills ("path with invalid characters") → `build-skill-zips.ps1` envuelve al script Python `zipfile` (estándar PKZIP 2.0, separador `/`, filtra paths problemáticos).
- **`datos_mercado.json` ruta dual sin verificación**: hasta hoy era un convenio. Ahora `sync-desktop.ps1` reporta divergencias.

## ✅ Mejoras pendientes (no implementadas)

- **CI en GitHub Actions**: build de ZIPs + `docs/SKILLS.md` en cada push; artefactos descargables. Quita dependencia del local.
- **`dry_run` en la API** (`?dry=1`): valida JSON antes de crear coche real. (Comprobar si ya existe en `app/Http/Controllers/Api/ImportValuationApiController.php`.)
- **Webhook Laravel → Claude**: cuando se importa un ZIP, notificar al Desktop (push a una URL local) para que actualice `encargos.md` sin tener que esperar a `subir-informe.ps1`.

## 📜 Historial

- **2026-08-12** — Token en claro en `subir-informe.ps1` (commiteado por error).
- **2026-09-12** — Plan de sincronización + identificación de canales. Implementación inicial de `sync-desktop.ps1`, `sync.manifest.json`, token por env var, round-trip automático, `.env.gitignore`.
