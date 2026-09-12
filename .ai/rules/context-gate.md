---
glob: '**'
title: 'Regla del context-gate — todo cambio en docs/contexto se refleja en AMBOS agentes'
---

## Por qué esta regla existe (12-sep-2026)

Tienes **cuatro agentes** que comparten el mismo proyecto, cada uno con su contexto
auto-cargado:

| Agente | Carga auto | Carga bajo demanda |
|---|---|---|
| **Copilot (VS Code)** | `.github/copilot-instructions.md`, `AGENTS.md`, `CLAUDE.md` | `docs/HANDOFF.md`, `docs/COMUNICACION-CLAUDE-VSCODE.md`, `.ai/rules/index.md` |
| **Claude Desktop** (Carpeta `C:\Users\jacar\Desktop\JJImportMotors\`) | `CLAUDE.md`, `README.md`, `GUIA_INICIO_RAPIDO.md`, `INSTRUCCIONES_PROYECTO.md` | `HANDOFF.md`, `.claude\MEMORIA.md`, `.claude\memoria\` |
| **claude.ai** (proyecto "JJ Import Motors") | Contexto del proyecto (= CLAUDE.md vía copia) | Mismos 7 docs de memoria |
| **Cowork** | Skills de la cuenta | ZIPs importados manualmente |

Si **un agente** modifica un doc másters y **el otro no se entera**, divergen.
Esto ya pasó: el 12-sep `docs/DOCS-INDEX.md` se quedó con la versión vieja porque
Claude Desktop editó sobre una copia obsoleta de lo que yo había escrito.

## La regla dura: TODO cambio en contexto se propaga

1. **Editas un doc canónico de contexto** (ver lista en `sync.manifest.json` sección `agents.*.context_files_*`)
2. **Inmediatamente**, sin esperar a recordarlo:
   - Actualiza el contexto del **otro agente** en su máster (`docs/claude-desktop/*` o `docs/memoria-desktop/*` → `sync-desktop.ps1 -Apply` para replicar a Desktop).
   - Si es arquitectura del sync → `docs/deploy/SINCRONIZACION-SISTEMAS.md`.
   - Si cambia un doc raíz (`AGENTS.md`, `CLAUDE.md`, `.github/copilot-instructions.md`) → replica a `docs/claude-desktop/CLAUDE.md` másters si es relevante.
3. **Anota la entrada en `docs/HANDOFF.md`** (≤10 líneas, no edites la del otro agente).
4. **Commit y push**: el hook `pre-commit` (sección 5 "context gate") avisa si tocaste algo de contexto sin actualizar HANDOFF/SINCRONIZACION/SYNC.MANIFEST. Soft warning por defecto — `[skip context-gate]` en el mensaje lo salta solo si ya lo cubriste.

## Trampas conocidas

- **Editar copias (Desktop/claude.ai) en lugar del másters del repo** = divergencia. Siempre editar en repo, replicar después.
- **Olvidar replicar `docs/claude-desktop/*` y `docs/memoria-desktop/*` a Desktop** tras editar los originales. Sin `sync-desktop.ps1 -Apply`, la sesión Desktop siguiente arranca con datos viejos.
- **Olvidar reimportar ZIPs en Cowork/Desktop** tras editar una skill. El CI de `build-skills.yml` regenera y publica artefactos, pero Cowork no los importa solo.
- **Subir al Contexto del proyecto claude.ai** no es automatizable desde el repo. Es la única ruta que requiere acción manual del usuario (pegar los 7 docs en el editor del proyecto).
- **PowerShell 5.1 manglea em-dashes en `git commit -m`** — usa ASCII puro en mensajes. Ver memoria `docs-reorganizacion-2026-09-12.md`.
- **Edición concurrente** desde Claude Desktop + claude.ai + Copilot → antes de editar `docs/DOCS-INDEX.md`, `docs/HANDOFF.md` o `docs/claude-desktop/*`, `git pull` para no tapar lo del otro.
