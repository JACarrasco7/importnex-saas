# Comunicación Claude Desktop ↔ VS Code Laravel — Guía operativa

> **Cómo trabajan juntos los dos mundos.** Guía de USO diario: qué comando
> correr, cuándo, y quién hace qué. La arquitectura completa de los 7 canales
> está en [`deploy/SINCRONIZACION-SISTEMAS.md`](deploy/SINCRONIZACION-SISTEMAS.md).
> El cuaderno de turno entre agentes es [`HANDOFF.md`](HANDOFF.md).

---

## Los dos mundos

| | **Claude Desktop** (investigación) | **VS Code / Laravel** (desarrollo) |
|---|---|---|
| Dónde | `C:\Users\jacar\Desktop\JJImportMotors\` | `C:\laragon\www\importnexcore\` |
| Qué hace | Encargos, flujos A/B/C/D/M, informes, skills | App Laravel, BD, API, deploy, tests |
| Su agente | Claude Desktop + claude.ai + Cowork | Copilot (VS Code) |
| Lee del otro | ZIPs de skills + másters replicados + HANDOFF | JSONs/ZIPs de informes vía API + HANDOFF |

**Regla de oro del máster:** el repo Laravel SIEMPRE es la fuente de verdad.
Desktop y claude.ai son copias de distribución. Editar allí = divergencia.

---

## Rutina diaria (cheat sheet)

### Al empezar cualquier sesión (ambos lados)
```powershell
cd C:\laragon\www\importnexcore
git pull            # ver qué hizo el otro
```
+ Leer la última entrada de [`HANDOFF.md`](HANDOFF.md).

### Claude Desktop genera un informe (Flujo A) → subirlo a Laravel
```powershell
# 1. Validar sin ensuciar BD (nada se crea):
.\subir-informe.ps1 -Archivo "C:\...\informe.json" -DryRun
#    → status: dry_run_ok

# 2. Subir de verdad (crea el coche + anota encargos.md del skill):
.\subir-informe.ps1 -Archivo "C:\...\informe.json"
#    → LISTO car_id=123 + car_url
```
Alternativa drag&drop: arrastrar el `.json` sobre `subir-informe.bat`.

### Copilot edita una skill → publicarla
```powershell
# 1. Editar .claude/skills/<skill>/...
# 2. Regenerar ZIPs + docs/SKILLS.md + commit + push (un comando):
.\scripts\build-skill-zips.ps1
# 3. Reimportar el ZIP en Claude Desktop y Cowork (gestor de skills)
```

### Replicar másters docs → Desktop (tras editar memoria/contexto)
```powershell
.\scripts\sync-desktop.ps1        # ver qué difiere (WhatIf)
.\scripts\sync-desktop.ps1 -Apply # replicar + verificar hashes
```

### Activar el webhook Laravel → Desktop (opcional)
```powershell
# En .env de Forge:
# IMPORTNEX_CHAT_WEBHOOK_URL=http://<tu-pc>:8765/   (necesita tunnel si Forge)
# IMPORTNEX_CHAT_WEBHOOK_SECRET=<clave-compartida>
# En local:
$env:IMPORTNEX_CHAT_WEBHOOK_SECRET = '<clave-compartida>'
.\scripts\import-notify-receiver.ps1   # escucha 127.0.0.1:8765 → anota encargos.md
```

---

## Quién toca qué (matriz de responsabilidad)

| Tarea | Responsable | Herramienta |
|---|---|---|
| Investigar encargos, informes, mercado | **Claude Desktop** | skills importacion-vehiculos + estudio-mercado |
| Subir informe JSON a Laravel | Claude Desktop (o usuario) | `subir-informe.ps1/.bat` |
| Editar skills (fuente) | **Copilot** en el repo | `.claude/skills/**` |
| Empaquetar/publicar skills | Copilot | `build-skill-zips.ps1` (+ CI en push) |
| Reimportar skills en Desktop/Cowork | **Usuario** (manual, 2 clics) | gestor de skills |
| Memoria transversal (`MEMORIA.md` + 6) | Copilot edita máster → replica | `sync-desktop.ps1` |
| Contrato JSON Laravel↔Claude | Copilot (backend) + Desktop lo respeta | `docs/claude/CONTRATO_JSON.md` |
| Deploy a Forge | Copilot commitea/pushea → Forge auto | `git push origin master` |
| Avisar "coche importado" al Desktop | Laravel automático | webhook `CarImported` |

---

## Detección de divergencia (el fallo histórico nº1)

```powershell
.\scripts\sync-desktop.ps1
```
Reporta por fichero: `[OK]` idéntico · `[NEW]` falta en Desktop · `[DIFF]` divergió.
En skills compara la versión instalada en Desktop vs `_dist/` del repo.
**Si algo va raro entre los dos mundos, este comando es el primer diagnóstico.**

CI de respaldo: cada push a master regenera ZIPs y `docs/SKILLS.md`
(`.github/workflows/build-skills.yml`) — artefactos descargables desde la pestaña
Actions si el local no está disponible.

---

## Seguridad

- **Nunca** commitear tokens/passwords. El token API vive en `$env:IMPORTNEX_TOKEN`
  (Windows user scope) y en `.env` de Forge (`IMPORTNEX_CHAT_IMPORT_TOKEN`).
- El secret del webhook (`IMPORTNEX_CHAT_WEBHOOK_SECRET`) firma HMAC-SHA256;
  el receptor local rechaza firmas inválidas (401).
- `dry_run` antes de subir algo delicado: no toca BD.

---

## Qué leer para profundizar

| Doc | Qué cubre |
|---|---|
| [`deploy/SINCRONIZACION-SISTEMAS.md`](deploy/SINCRONIZACION-SISTEMAS.md) | Arquitectura de los 7 canales, trampas, historial |
| [`HANDOFF.md`](HANDOFF.md) | Cuaderno de turno entre agentes (leer SIEMPRE al empezar) |
| [`claude/CONTRATO_JSON.md`](claude/CONTRATO_JSON.md) | Formato exacto del JSON que Desktop entrega a Laravel |
| [`SKILLS.md`](SKILLS.md) | Versiones/SHA de los ZIPs de skills publicados |
| [`.ai/rules/doc-sync.md`](../.ai/rules/doc-sync.md) | Regla dura: qué doc tocar tras cada cambio |
| [`.ai/rules/skills-sync.md`](../.ai/rules/skills-sync.md) | Regla dura: ciclo de vida de skills |
| [`sync.manifest.json`](../sync.manifest.json) | Cableado declarativo: qué archivo va a qué sitio |
