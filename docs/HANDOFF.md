# HANDOFF — Cuaderno de turno entre agentes

> **Protocolo de comunicación directa Copilot (VS Code) ↔ Claude Desktop.**
> Cualquier agente que vaya a tocar este repo DEBE leer primero la última entrada
> y escribir la suya al terminar. Es el único sitio donde un agente le habla a otro.

**Reglas:**

1. **Antes de trabajar:** lee las entradas marcadas ⚠️ PENDIENTE y la última del otro agente.
2. **Al terminar:** añade tu entrada ARRIBA (más nueva primero) con el formato de abajo.
3. **Nunca edites la entrada de otro agente** — responde con una nueva.
4. Máx ~10 líneas por entrada. El detalle va en commits/docs, no aquí.
5. Cuando algo quede resuelto, el agente que lo resuelve cambia ⚠️ PENDIENTE por ✅ HECHO **solo en su propia entrada nueva** que cierre el tema.

**Formato:**

```markdown
## YYYY-MM-DD HH:mm · AGENTE (Copilot-VSCode | Claude-Desktop) · tema corto
- Hice: ...
- Toqué: rutas/archivos clave
- ⚠️ PENDIENTE para el otro: ... / ✅ nada pendiente
- Commit: hash (si aplica)
```

---

## 2026-09-12 21:10 · Copilot-VSCode · sistema de sincronización completo

- Hice: doc-sync (`scripts/sync-desktop.ps1` + `sync.manifest.json`), token API por env var, round-trip `encargos.md`, dry_run en API, CI build-skills (GHA), webhook Laravel→Desktop (`CarImported` + `NotifyImportWebhook` + receptor PS1). Fix bug crítico `build-zips.py` (versiones hardcodeadas). Arreglé event() muerto tras return en `ImportValuationApiController` (store + storeModelo). 9 tests nuevos en verde, 28 existentes OK, Pint OK.
- Toqué: `app/Http/Controllers/Api/*`, `app/Events/CarImported.php`, `app/Listeners/NotifyImportWebhook.php`, `scripts/*`, `subir-informe.*`, `.github/workflows/build-skills.yml`, `docs/deploy/SINCRONIZACION-SISTEMAS.md`
- ⚠️ PENDIENTE para Claude-Desktop: (1) reimportar skills ZIPs v3.9.3/v0.4.0 (Cowork sigue en 3.6.1/0.3.12); (2) al cerrar sesión, verificar `docs/memoria-desktop/MEMORIA.md` sigue siendo máster (regla `.ai/rules/doc-sync.md`); (3) si usas el webhook receptor, arranca `scripts/import-notify-receiver.ps1` con el secret.
- Commit: ver `git log` — rama master, docs+sync
