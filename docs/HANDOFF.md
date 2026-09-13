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

## 2026-09-13 · Copilot-VSCode · enlaces de suelo corregidos al spec canónico

- Hice: el usuario pasó dos URLs de ejemplo (Arteon Shooting Brake R) y al contrastarlas con `playbook_filtrado.md` §"URL de resultados reales" aparecieron **dos bugs míos**: (1) `pw` de mobile.de va en **kW** (`cv × 0,7355 ±4`) y yo mandaba CV — un 290cv pedía 209:217 kW y yo pedía 261:319, que **excluía el propio coche**; (2) el `ms` son **cinco campos** `makeId;modelId;;;` y yo mandaba cuatro. Además mi mapa de makeIds (copiado de `empaquetar.py`) tenía IDs duplicados/inventados (Hyundai y Nissan = `21000`, Volvo = `25100`) → ahora solo los vigentes.
- Verificado en navegador: `coches.net/segunda-mano/?MakeIds[0]=47&Versions[0]=Golf&PowerHpFrom=285&PowerHpTo=295&fi=Price&or=1` → *"VOLKSWAGEN GOLF de segunda mano y ocasión"*. El enlace que genera Laravel para tu Golf TCR es **idéntico**.
- **Hallazgo sobre mobile.de:** su URL canónica del 24-ago abre hoy con **0 Angebote en modo formulario** incluso con `ms=25200;12603;;;` (el modelId verificado entonces). Los IDs de modelo **caducan**. Por eso el generador: reutiliza el `modelId` de `busquedas_realizadas` si existe, si no cae a texto libre (`q=`) — y **no inventa IDs**. Si abres el enlace de mobile.de y no ves tarjetas, es esto.
- Toqué: `app/Support/PortalSearchUrls.php` (reescrito), `tests/Feature/CarEnlacesSueloTest.php` (14 tests), `.ai/rules/support.md`, `docs/guias/02-flujo-a-unidad.md`.
- ⚠️ **NO usar `record-rule` en `app/Support/**`**: reescribe `.ai/rules/index.md` y borró 7 filas. Lo restauré y amplié a 23 filas; el aviso está dentro de `support.md`.
- ⚠️ PENDIENTE para ti: **sigue todo lo de las dos entradas anteriores** (spatie, rotar password BD, `$env:IMPORTNEX_TOKEN`, reimportar ZIPs en Cowork). Ojo: la nota anterior decía que el formato de coches.net del skill estaba mal — **era mi lectura, no su error**; el skill usa `MakeIds+Versions` y funciona. No toqué `.claude/`.
- Commit: ver `git log` (rama master)

---

## 2026-09-13 · Copilot-VSCode · enlaces "ver suelo" en la ficha del coche

- Hice: nueva sección **"Ver suelo en portales"** en la pestaña Mercado de la ficha del coche. Genera enlaces a `mobile.de` (DE) y `coches.net` (ES) desde los datos del coche, **ordenados por precio ascendente**, para comparar el suelo a mano. Cubre los coches sin `mercado.busquedas_realizadas[]` (importados antes del 09-sep).
- **Formatos de URL verificados en navegador hoy** (lo viejo del skill estaba mal): coches.net es `/<marca>/<modelo>/segunda-mano/?fi=Price&or=1&PowerHpFrom=…&MinYear=…` — **NO** `/segunda-mano/coches/<marca>-<modelo>/`. Con slug de modelo inexistente degrada a la página de marca (nunca 404); solo reconoce el primer token (`Golf 7.5 TCR` → `golf`). mobile.de sigue `suchen.mobile.de/fahrzeuge/search.html?…&sb=p`.
- Nuevo: `app/Support/PortalSearchUrls.php`, accessor `Car::enlacesSuelo`, prop `enlaces_suelo` en `CarController@show`, bloque en `MarketPanel.vue`, claves i18n `market_price_floor_*`.
- Tests: `tests/Feature/CarEnlacesSueloTest.php` (12). **Suite completa: 684 passed · 6 skipped · 0 failed.** Pint limpio · paridad i18n 1720/1720.
- Toqué: `app/Support/PortalSearchUrls.php`, `app/Models/Car.php`, `app/Http/Controllers/CarController.php`, `resources/js/Pages/Cars/Partials/MarketPanel.vue`, `resources/js/i18n/{es,en}.js`, `tests/Feature/CarEnlacesSueloTest.php`, `docs/guias/02-flujo-a-unidad.md`, `docs/ARQUITECTURA_VISTAS.md`.
- ⚠️ PENDIENTE para ti: ⚠️ **sigue todo lo de la entrada anterior** (spatie, rotar password BD, `$env:IMPORTNEX_TOKEN`, reimportar ZIPs en Cowork). Nota para el lado Claude: verifiqué en navegador que `/<marca>/<modelo>/segunda-mano/` devuelve la página de modelo (`coches.net/volkswagen/golf/segunda-mano/` → "VOLKSWAGEN Golf de segunda mano"); el skill documenta la forma `/segunda-mano/coches/<marca>-<modelo>` en `02-flujos/paginas_reales.md` y `memoria/trampas-encontradas.md` ya avisa de un cambio de URL. **No he tocado el skill** (no quería forzar otro reimport de ZIPs sin contrastarlo antes): conviene comparar las dos formas con una pasada real y unificar.
- ⚠️ **El equipo no se apaga**: cancelé el `shutdown` que había programado porque llegó este encargo. Dime si quieres que lo vuelva a programar.
- Commit: `73ee3df` (push a master OK)

---

## 2026-09-13 · Copilot-VSCode · auditoría ronda 4 (2 pasadas) + fixes

- Hice: dos auditorías externas independientes (4A sync/API, 4B app/seguridad) que destaparon 4 críticos + 9 importantes. Aplicados todos: `scripts/LockFile.ps1` (race en encargos.md), guard producción en `build-skill-zips.ps1`, multi-tenant explícito en `alerts:generate`, límite 1000 modelos, clamp `?limit`, rate-limit por token, chunk/lazy en 4 comandos, `queue:prune-failed`, HSTS, purga de backups, throttle Stripe, `inspire` fuera.
- **Los propios tests cazaron 2 bugs que introduje** (withoutOverlapping sin name → LogicException; throttle numérico → viola convención). Arreglados.
- **Suite completa: 673 passed · 6 skipped · 0 failed.** Pint limpio.
- Hallazgo colateral: **`public/hot` ralentiza cada request ~2s** (Vite::prefetch contra el dev server). No afecta a producción. `PerformanceAuditTest` ahora se salta esos tests con mensaje claro si el archivo existe.
- Toqué: `app/Console/Commands/*`, `app/Http/Middleware/SecurityHeaders.php`, `app/Providers/AppServiceProvider.php`, `app/Http/Controllers/Api/ImportValuationApiController.php`, `routes/console.php`, `routes/web.php`, `scripts/*`, `subir-informe.ps1`, `.ai/rules/{index,events,auth-roles}.md`, `tests/Feature/Round4GuardsTest.php`, `docs/AUDITORIA_ronda4_2026-09-13.md`
- ⚠️ PENDIENTE para ti (decisiones, no hay código bloqueado): (1) `spatie/laravel-permission` → implementar o desinstalar (`.ai/rules/auth-roles.md`); (2) rotar password BD Forge; (3) `$env:IMPORTNEX_TOKEN`; (4) reimportar ZIPs en Cowork. Opcionales documentados: CORS, backup de BD, `kpis()` a SQL, `savePhotos()` con `Http::pool()`.
- Commit: ver `git log` (rama master)

---

## 2026-09-12 22:30 · Copilot-VSCode · auditoria externa + opciones (M1-M7)

- Hice: auditoria con subagente Explore (16 chequeos, 4 criticos P1-P4 + 4 importantes P5-P8 + trampas B1-B10). Fix: NotifyImportWebhook implements ShouldQueue (tries=3, backoff=5) [P1], pre-commit usa $1 en vez de git log -1 [P2], footer SKILLS.md actualizado a v3.9.3/v0.4.0 [P3], MEMORIA.md skill v3.9.3 [P4], BOM CHANGELOG eliminado [P5], build-zips.py autodetecta skills (excluye las de Copilot/Claude Code) [P6], sync-desktop limpia ZIPs solo de la skill regenerada [M5], sync-desktop distingue NO INSTALADA vs DIFF [P10], DRY controller: 5 endpoints usan trait ParsesImportPayload [M1], try/catch en Stop/Close del listener receptor [M3], doc IMPORTNEX_TOKEN en .env.example [M4]. 9/9 tests verdes, Pint OK.
- Bumps: importacion-vehiculos 3.9.2 → 3.9.3 (frontmatter ya estaba bien, tabla metricas corregida); estudio-mercado 0.3.12 → 0.4.0.
- ⚠️ PENDIENTE para Claude-Desktop: nada urgente — todo verificado por tests + subagente auditor. Sigue: (1) reimportar ZIPs en Cowork, (2) rotar password BD Forge, (3) `$env:IMPORTNEX_TOKEN`.
- Commit: a850ae8 (auditoria) + pendientes (M1-M7) en el siguiente.

---

## 2026-09-12 21:10 · Copilot-VSCode · sistema de sincronización completo

- Hice: doc-sync (`scripts/sync-desktop.ps1` + `sync.manifest.json`), token API por env var, round-trip `encargos.md`, dry_run en API, CI build-skills (GHA), webhook Laravel→Desktop (`CarImported` + `NotifyImportWebhook` + receptor PS1). Fix bug crítico `build-zips.py` (versiones hardcodeadas). Arreglé event() muerto tras return en `ImportValuationApiController` (store + storeModelo). 9 tests nuevos en verde, 28 existentes OK, Pint OK.
- Toqué: `app/Http/Controllers/Api/*`, `app/Events/CarImported.php`, `app/Listeners/NotifyImportWebhook.php`, `scripts/*`, `subir-informe.*`, `.github/workflows/build-skills.yml`, `docs/deploy/SINCRONIZACION-SISTEMAS.md`
- ⚠️ PENDIENTE para Claude-Desktop: (1) reimportar skills ZIPs v3.9.3/v0.4.0 (Cowork sigue en 3.6.1/0.3.12); (2) al cerrar sesión, verificar `docs/memoria-desktop/MEMORIA.md` sigue siendo máster (regla `.ai/rules/doc-sync.md`); (3) si usas el webhook receptor, arranca `scripts/import-notify-receiver.ps1` con el secret.
- Commit: ver `git log` — rama master, docs+sync
