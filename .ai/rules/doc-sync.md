---
glob: 'docs/**,.github/copilot-instructions.md,.ai/rules/index.md,AGENTS.md,CLAUDE.md,app/Http/Controllers/**,app/Models/**,app/Services/**,routes/**,config/**'
title: 'Sincronización de documentación tras cada cambio'
---

## Por qué existe esta regla (12-sep-2026)

El proyecto tiene **dos mundos de documentación** que no se sincronizan solos
y donde ya se ha perdido información por ignorarlos:

| Mundo | Ruta | Qué vive aquí | Quién lo lee |
|---|---|---|---|
| **`docs/`** (este repo) | `C:\laragon\www\importnexcore\docs\` | Toda la documentación humana, de negocio, contexto, contratos, planes, guías, informes | Cualquier IA (Copilot, Claude, Codex…) + humanos del equipo |
| **`.claude/`** (este repo) | `C:\laragon\www\importnexcore\.claude\` | Solo las skills Claude (`SKILL.md`, `memoria/`, scripts) + reglas zip | Solo Claude Desktop / Cowork |
| **Desktop** (`C:\Users\jacar\Desktop\JJImportMotors\`) | Carpeta fuera del repo | **Copia de distribución** de los másters de `docs/claude-desktop/` y `docs/memoria-desktop/` | Claude Desktop localmente |
| **claude.ai proyecto "JJ Import Motors"** | Nube | **Copia de distribución** del Contexto del proyecto | Claude.ai (este mismo chat) |

**Regla de oro:** los **másteres** son los del repo (`docs/claude-desktop/`,
`docs/memoria-desktop/`). Las copias en Desktop y claude.ai se actualizan a
mano después de tocar el máster, **nunca al revés**. Detalle completo:
[`docs/DOCS-INDEX.md`](../../docs/DOCS-INDEX.md) sección "🔗 Fuente única entre
sistemas".

## Qué actualizar según el cambio

| Si tocas… | Actualiza también (en este orden) |
|---|---|
| **Una skill** (`.claude/skills/<skill>/`) | 1) `SKILL.md` + `CHANGELOG.md` + `memoria/` de la skill. 2) `git commit`. 3) `.\scripts\build-skill-zips.ps1` (regenera `_dist/` + `docs/SKILLS.md` + commit + push). 4) Reimportar ZIP en Desktop + Cowork. Ver `.ai/rules/skills-sync.md` (regla dura ya existente). |
| **Sistema Laravel** (controller, modelo, ruta, config, servicio) | Si cambia el **contrato** con Claude → [`docs/claude/CONTRATO_JSON.md`](../../docs/claude/CONTRATO_JSON.md). Si cambia **flujo de trabajo del usuario** → [`docs/guias/`](../../docs/guias/) (01-primeros-pasos, 02-flujo-a-unidad…08-solucion-problemas). Si cambia un **módulo entero** → [`docs/ARQUITECTURA_VISTAS.md`](../../docs/ARQUITECTURA_VISTAS.md) si afecta a la UI. Si cambia **billing/Suscripciones/Stripe** → [`docs/planes/STRIPE_LOOKUP_KEYS.md`](../../docs/planes/STRIPE_LOOKUP_KEYS.md). |
| **Negocio / contexto del cliente** (flujo operativo, honorarios, fotos, comparativas) | Másters en [`docs/claude-desktop/`](../../docs/claude-desktop/) (CLAUDE.md, README, GUIA, INSTRUCCIONES) **y** [`docs/memoria-desktop/`](../../docs/memoria-desktop/) (MEMORIA.md, decisiones.md, errores-pasados.md, preferencias.md). Luego ejecutar `actualizar-memoria-desktop.bat` para replicar a Desktop. |
| **Memoria Desktop / transversal** | Másters en [`docs/memoria-desktop/`](../../docs/memoria-desktop/). **Revisar MEMORIA.md al final de cada sprint** (commit con tag) — está fechado 12-ago-2026 y la revisión estaba vencida al 12-sep-2026. Tras tocar memoria, replicar a Desktop con el `.bat`. |
| **Un plan** (nuevo, modificación, archivo) | Si es nuevo → entrada en [`docs/planes/README.md`](../../docs/planes/README.md) como Activo. Si termina o se sustituye → mover a [`docs/planes/archivo/`](../../docs/planes/archivo/) y referenciar el sucesor. **Nunca borrar.** |
| **Un informe nuevo entregado al cliente** | Crear `docs/informes/<marca>/<modelo>/informe_{unidad,busqueda}_YYYY-MM-DD.md` (+ .pdf si lo hay). Seguir [`docs/informes/README.md`](../../docs/informes/README.md). Si cambia el formato de entrega → [`docs/informes/README.md`](../../docs/informes/README.md) + [`docs/claude/CONTRATO_JSON.md`](../../docs/claude/CONTRATO_JSON.md). |
| **Contrato JSON Laravel ↔ Claude** | [`docs/claude/CONTRATO_JSON.md`](../../docs/claude/CONTRATO_JSON.md) **Y** versión en la skill correspondiente (`.claude/skills/<skill>/memoria/`). |
| **Marca / paleta / estilo frontend** | [`docs/BRAND.md`](../../docs/BRAND.md) (másters de paleta, tipografía, logos). Si es un componente nuevo → añadir ejemplo al final. |
| **Deploy / Forge / scripts** | [`docs/deploy/README.md`](../../docs/deploy/README.md). Subguías en `docs/deploy/` (`HEIDISQL_FORGE.md`, `DEPLOY_AI_MULTIPROVIDER.md`). Si cambia `.bat`/`.ps1` del repo (subir-informe, forge-mysql-tunnel) → actualizar aquí también. |
| **Reglas para coding agents** (`.ai/rules/`) | Esta misma tabla ya está en `.ai/rules/index.md`. Si añades una regla nueva → actualiza `index.md` con la fila del glob. Si cambia el `AGENTS.md` o `.github/copilot-instructions.md` → `git commit` con mensaje claro para que Codex/Copilot la recarguen. |

## Cadencia de revisión (12-sep-2026)

- **Tras cada sprint / commit con tag** → revisar `docs/memoria-desktop/MEMORIA.md` (5 min). Si aprendiste algo nuevo del negocio, actualízalo **en el mismo commit del sprint** — no en otro diferido.
- **Mensual** (día 1) → auditar `docs/DOCS-INDEX.md`: ¿sigue reflejando la realidad? ¿Algún plan en `archivo/` que ya no se consulte? ¿Algún README de subcarpeta desactualizado?
- **Tras incidente grave** → `docs/memoria-desktop/errores-pasados.md` con causa raíz + fix + cómo detectarlo la próxima vez.

## Protocolo HANDOFF (12-sep-2026)

Antes de trabajar en el repo, todo agente (Copilot/Claude Desktop) DEBE leer
la última entrada de [`docs/HANDOFF.md`](../../docs/HANDOFF.md) y escribir la
suya al terminar (qué hizo, qué dejó pendiente, commit). Es el canal de
comunicación directa entre agentes. Máx ~10 líneas por entrada, nunca editar
la entrada de otro. Guía operativa completa:
[`docs/COMUNICACION-CLAUDE-VSCODE.md`](../../docs/COMUNICACION-CLAUDE-VSCODE.md).

## Trampas conocidas

- **Editar copias en vez del másters** — Desktop y claude.ai son de distribución. Si tocas ahí, mañana divergen de nuevo.
- **Borrar planes en vez de archivar** — `docs/planes/archivo/` existe para eso.
- **Olvidar replicar a Desktop** tras tocar `docs/claude-desktop/` o `docs/memoria-desktop/` — sin el `.bat`, la sesión Desktop siguiente arrancará con datos viejos.
- **PowerShell 5.1 manglea em-dashes en `git commit -m`** — usar ASCII puro o commit y amend si hace falta (ver memoria `docs-reorganizacion-2026-09-12.md`).
- **Edición concurrente** desde Claude Desktop + Copilot + claude.ai → antes de editar `docs/DOCS-INDEX.md` o `docs/claude-desktop/*`, `git status` + `git pull` para no tapar lo del otro.
