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

## 2026-09-16 19:00 · Copilot-VSCode · e-commerce tuning: guía v2 (tramitador puro) + skill ecommerce-tuning

- **Pedido:** adaptar el plan de agente IA de e-commerce al modelo "tramitador puro" (sin almacén), añadir plan de marketing multicanal Meta/TikTok y crear la skill de automatización con el navegador de Claude.
- Hecho: `docs/ecommerce.md` v2 — §2 tramitador puro (fuera el mini-stock), §7 escenario proveedor almacén UE, §10 plan Meta/TikTok por fases con umbrales de corte, §14 agente IA (captura de señales + fórmulas Score/margen + pipeline), §15 skill; plan 90 días sin lote de stock.
- Hecho: skill nueva `.claude/skills/ecommerce-tuning/` v0.1.0 (flujos S/P/F/M, reglas E1-E12, memoria propia). Registrada en `build-skill-zips.ps1`; ZIP generado y pusheado (c0086e0).
- ⚠️ PENDIENTE para Claude Desktop: importar `skills-ecommerce-tuning-v0.1.0-20260916.zip` (en `_dist/`) en Desktop + Cowork y descomprimir en `%USERPROFILE%\.claude\skills\`. Es de OTRO negocio (tienda tuning): NO mezclar criterios/costes con `importacion-vehiculos`.
- ⚠️ Sin commitear (cambio del usuario, no mío): `app/Services/ValuationImporter.php`.
- Commit: ver `git log` (rama master)

---

## 2026-09-13 19:43 · Copilot-VSCode · sección "Guía" dentro de la app (menú de la organización)

- **Lo que pedía el usuario:** ver las guías **dentro de la web**, no solo en markdown suelto. Ya había un intento a medias: el sidebar traía la sección preparada pero **comentada** ("la ruta `guide.index` aún no existe y Ziggy lanza errores") y existía un `Pages/Guide/Index.vue` con una guía **escrita a mano en HTML**.
- Hecho: ruta `guide.index` (`/guias`) + `guide.show` (`/guias/{slug}`), sección **Guía** activada en el menú, y `GuideController` que **convierte los markdown del repo** (`docs/guias/*.md` + la guía rápida) a HTML. Ahora el contenido vive en **un solo sitio**: el markdown.
- **No se perdió nada:** la guía que estaba hardcodeada en el `.vue` se pasó a `docs/guias/00-guia-de-uso.md` (mismo texto, en markdown) antes de sustituir la página.
- Seguridad: el slug se traduce con **whitelist** (nunca se lee una ruta de la URL) y el markdown se convierte con `html_input=strip`. Cubierto por test (incluye un intento de path traversal).
- ⚠️ Para añadir una guía nueva: fichero en `docs/guias/` **y** registrarlo en el whitelist de `GuideController`.
- Verificado: **699 tests** OK, Pint limpio, `npm run build` hecho y manifest con `Guide/Index.vue`.
- Toqué: `GuideController`, `routes/web.php`, `Pages/Guide/Index.vue`, `AuthenticatedLayout.vue`, `docs/guias/00-guia-de-uso.md`, `GuideControllerTest`.
- Commit: ver `git log` (rama master)

---

## 2026-09-13 19:24 · Copilot-VSCode · guías de uso: ahora dicen QUÉ PEDIR (y el caso "revisar un segmento")

- Las guías explicaban cada flujo, pero **no decían cómo pedirlo**. Añadida al índice una tabla **"Cómo pedirlo — ejemplos reales"** con la frase que dispara cada uno de los **6 flujos** (A/B/C/D/E + M automático).
- **Caso que faltaba y es el más habitual**: revisar **un segmento partiendo de modelos conocidos** — *"quiero revisar SUVs deportivas: Audi RSQ3, VW Tiguan R y similares, con un plan de búsqueda por marca"*. Es **FLUJO C**, documentado en `docs/guias/04-flujo-c-mercado.md` §1b: qué devuelve (oferta · suelo · mediana · hueco · veredicto por modelo + comparativa) y 3 avisos al leerlo (suelo ≠ mediana; hay que controlar el año o el hueco cambia de signo; oferta escasa = mediana poco fiable).
- Corregido de paso: el índice decía "skill v2.9.0" (real: **3.9.7**) y el resumen de 30 s mencionaba solo 3 de los 6 flujos.
- ⚠️ Para el lado Claude: si el encargo es *"revisar una categoría/familia de coches"* → **Flujo C**, NO D (D es cuando **no** hay modelo, solo presupuesto). Y si algo no encaja en ningún flujo, **preguntar**, no improvisar (regla dura del 17-ago).
- Toqué: `docs/guias/README.md`, `docs/guias/04-flujo-c-mercado.md`, `docs/DOCS-INDEX.md`.
- Commit: ver `git log` (rama master)

---

## 2026-09-13 18:33 · Copilot-VSCode · 🔴 INCIDENTE: credencial de la BD de Forge publicada en el repo

- **Qué pasó:** revisando por qué el token no me servía, miré `forge-mysql-tunnel.bat` y tenía la **password de la BD de producción en texto plano**. Ese archivo **está rastreado por git**, está en **2 commits** del historial, y el repo `github.com/JACarrasco7/importnex-saas` es **PÚBLICO**. Es decir: credencial de producción visible para cualquiera. Queda también expuesto el host SSH y el usuario.
- **Escaneo completo:** pasé un buscador de secretos por los **995 archivos rastreados**. Resultado: **1 único secreto real** (ese). El resto eran falsos positivos (nombres de ruta `PASSWORD_RESET`, etiquetas i18n, props de Vue). Comprobado además que la credencial **no está en ningún otro archivo**.
- **Qué he hecho yo:** quitada del `.bat` (no rompe nada: el túnel SSH autentica con la clave de `~/.ssh`, la password solo se *mostraba* para configurar HeidiSQL). Ahora se lee de `FORGE_DB_PASSWORD` y el archivo avisa de que es público. Documentada la variable en `.env.example`.
- ⚠️ **LO QUE HAY QUE HACER (solo tú, es una credencial):** **rotar la password de la BD en Forge YA**. Borrarla del `.bat` no basta: sigue en el historial de un repo público. La rotación es lo único que la invalida.
- ⚠️ Nota para el próximo agente: **no repitas la credencial en comandos** (`git log -S '<valor>'` la mete en el transcript). Usa ficheros o variables.
- Commit: ver `git log` (rama master)

---

## 2026-09-13 18:14 · Copilot-VSCode · deudas saldadas: build + refresco real de oferta del mapa de mercado

- **`npm run build`** lanzado (por petición expresa del usuario, que anula su propia regla de que lo lanza él). El bloque "Ver suelo en portales" **ya está en el bundle** (`MarketPanel-*.js`, manifest 18:02).
- **`datos_mercado.json` refrescado de verdad**, no de palabra: reejecutada la consulta propia de cada una de las **9 showstoppers** (las caducadas el 31-ago) y actualizados los recuentos de oferta medidos en vivo. Golf GTI ES 494→**507** / DE 3479→**3294**; Golf R 161→162 / 623→623; Audi S3 66→**64** / 828→**791**; A45 AMG 139→**149** / 156→**170**; M135 13→12 / 513→508; i30 N 195→193 / 463→**491**; Focus ST 68→**70** / 567→**577**; Audi TT 86→85 / 460→**453**; Cupra Leon 655→**697** / 5321→**5505**. `refrescar_antes_de_categoria` → 2026-09-27. Espejo del Escritorio sincronizado.
- ⚠️ **Bug encontrado en el propio mapa:** las `query_reejecutable.de` grabadas estaban **incompletas** — les faltaba `isSearchRequest=true&s=Car&vc=Car`. Usarlas tal cual hace que mobile.de **ignore los filtros** y devuelva el catálogo entero (664.331 / 728.899 anuncios) **sin avisar**, dando a entender que es el modelo. Corregidas a la URL completa.
- **Lo que NO se ha tocado, a propósito:** las **medianas** siguen del estudio completo (17-ago) → anotado con `oferta_medida` por entrada para no mezclar fechas. Y **las 14 entradas sin `query_reejecutable`** no se han refrescado: sin la consulta original no se mide lo mismo, y reconstruirla a ojo sería inventar (misma regla que con los IDs de portal).
- ⚠️ **PENDIENTE (decisiones tuyas, no código):** (1) **spatie/laravel-permission** — la regla dice "2-3 días, riesgo alto, esperar decisión"; hay que elegir implementar o desinstalar; (2) **rotar password de BD Forge** — requiere el panel de Forge y es un secreto, no pasa por el asistente; (3) **`$env:IMPORTNEX_TOKEN`** — igual, el token no debe pasar por el asistente: se pega en la terminal y luego se verifica sin verlo.
- Commit: ver `git log` (rama master)

---

## 2026-09-13 17:52 · Copilot-VSCode · catálogo a 14 marcas (+Toyota/Citroën/Dacia) y las 53 marcas verificadas

- **Revisión con hallazgo:** las notas del catálogo decían *"añadir al mapa SOLO lo verificado"* pero **37 de sus 53 marcas nunca se habían comprobado** (venían del payload sin más). Como el refactor de 3.9.6 hizo que `empaquetar.py` leyera de ahí, esas 37 entraban en juego sin verificar. **Comprobadas las 53 en vivo** (pidiendo `ms=<makeId>;;;` y leyendo el `<h1>`): **53/53 correctas** — `alpina=1100`→"624 ALPINA", `abarth=140`→"1.905 Abarth", `chevrolet=5600`, `byd=31953`… Un ID del payload es **autoritativo**; lo prohibido es **inventarlo**.
- **Ampliado el catálogo a 14 marcas con modelos.** Faltaban las 3 que tenían makeId pero caían a `q=` (texto libre). Extraídos de `modelsCache` y confirmados con el `<h1>`: **Toyota** (55 modelos, `corolla=9`→2.108, `rav4=28`→1.445, `yaris=36`), **Citroën** (53, `c3=11`→6.399, `c5aircross=44`→3.192) y **Dacia** (11, `duster=2`→4.867, `sandero=24`→4.897). Ahora filtran por modelo de verdad.
- Truco para la próxima: la extracción va con `fetch` **same-origin** desde suchen.mobile.de (`page.request` **no** funciona en este entorno, y `require`/`fs` tampoco → el JSON hay que sacarlo por el resultado). El `<h1>` está en el HTML crudo, así que se puede verificar sin navegar.
- Toqué: `app/Support/data/mobile-de-catalogo.json` (metadatos reescritos: procedencia + qué se verificó), `tests/Feature/CarEnlacesSueloTest.php` (20 tests), `.ai/rules/support.md` (política de IDs), `.claude/skills/**`.
- ⚠️ **Ojo:** el test `test_mobile_de_cae_a_texto_libre` usaba Toyota Corolla como ejemplo "sin modelId" — ya no sirve, ahora usa **Alfa Romeo Giulia** (makeId 900, sin catálogo de modelos).
- Skills regeneradas: **importacion-vehiculos v3.9.7** / **estudio-mercado v0.4.4**, instaladas en el perfil y copiadas al Escritorio. Verificado: 693 tests, Pint limpio, auditoría 0 fallos.
- ⚠️ PENDIENTE para ti: **`npm run build`** (el bundle del 12-sep NO tiene el bloque "Ver suelo en portales"), reimportar los 2 ZIP en Cowork, refrescar `datos_mercado.json` (17-ago) y lo de siempre.
- Commit: ver `git log` (rama master)

---

## 2026-09-13 17:42 · Copilot-VSCode · VERIFICADO EN VIVO: el bug de coches.net era real y el `ms` de 5 campos es correcto

- **Cerrado el punto que quedaba abierto.** Con las páginas del navegador del usuario pude navegar de verdad:
  - `ms=25200;14;;;` (5 campos) → **57.731 ofertas** ✅ (lo que genera el código) · `ms=25200;14;;;;` (6) → **1.524.511** ❌ (todo mobile.de). mobile.de además **tolera** el de 4 campos (`25200;14;;` → 57.726). **El código PHP ya estaba bien: no hay que tocarlo.** Lo que rompe es añadir un `;` de más.
  - coches.net, leyendo `<title>`/`<h1>`: `MakeIds[0]=7` → **"BMW"** (2.228) · `28` → **"MERCEDES-BENZ"** (996) · `11` → **"CITROEN"** (13.128) — y `11` es exactamente lo que el código enviaba **para BMW** antes del arreglo. **Bug probado, no solo razonado.**
- Truco para futuras verificaciones: los **MakeIds se comprueban navegando**, no con `fetch` en bucle (coches.net devuelve 403 anti-bot y una página "Ups! Parece que algo no va bien...").
- mobile.de **bloquea la navegación automatizada** (`ERR_BLOCKED_BY_RESPONSE`) tras muchas peticiones: si hay que verificar `ms` en vivo, hacerlo **al principio** de la sesión, no al final.
- Toqué: `.ai/rules/support.md` (números verificados + el truco de navegar vs `fetch`), `docs/HANDOFF.md`. **Sin cambios de código ni de ZIP.**
- ⚠️ PENDIENTE para ti: sigue igual — reimportar los 2 ZIP en Cowork, refrescar `datos_mercado.json` (17-ago), `npm run build` para ver el bloque "Ver suelo en portales", y lo de siempre (spatie, password BD, `$env:IMPORTNEX_TOKEN`).
- Commit: ver `git log` (rama master)

---

## 2026-09-13 17:28 · Copilot-VSCode · IDs de coches.net al catálogo compartido (bug: marca equivocada)

- Hice (auditoría, pasada 2): el mapa de `MakeIds[]` de coches.net estaba **DUPLICADO** en `empaquetar.py` y en `PortalSearchUrls.php`, y **habían divergido**. El PHP se quedó con los valores inventados y generaba enlaces a **OTRA MARCA**: `bmw=11`→CITROEN, `mercedes=12`→DAEWOO, `opel=7`→BMW, `seat=9`→CHEVROLET, `toyota=10`→CHRYSLER, `volvo=26`→MASERATI. Solo acertaban `VW=47` y `Audi=4`: **18 de 20 marcas estaban mal**.
- Arreglado de raíz (una sola fuente): los IDs viven ahora solo en el catálogo (`app/Support/data/mobile-de-catalogo.json`, clave `cochesnet`, **134 marcas** del payload + `cochesnet.modelos`). Laravel y `empaquetar.py` lo leen de ahí. Tests: `test_coches_net_usa_los_make_ids_verificados_del_catalogo` (22 marcas) + `test_coches_net_no_vuelve_a_los_make_ids_inventados` (regresión).
- Doc con datos caducados corregida: `.ai/rules/support.md` decía "el resto de MakeIds sin verificar" (ya no), `extractores.md` citaba `12603` como válido (devuelve **0**; el bueno es **14**).
- Skills regeneradas: **importacion-vehiculos 3.9.6** / **estudio-mercado 0.4.3**, instaladas en `%USERPROFILE%\.claude\skills\` y copiadas al Escritorio. Borrados los backups `.old-*` del perfil (contenían `SKILL.md` y podían cargar versiones viejas).
- Verificado: suite **692 pasan / 6 skip / 0 fallan**; Pint limpio; las 3 copias del catálogo idénticas (`DDBC318D…`); ZIP sin backslashes ni `__pycache__`.
- Toqué: `app/Support/PortalSearchUrls.php`, `app/Support/data/mobile-de-catalogo.json`, `tests/Feature/CarEnlacesSueloTest.php`, `.claude/skills/**`, `.ai/rules/support.md`, `docs/SKILLS.md`.
- ⚠️ PENDIENTE para ti: reconfirmar el `ms` de 5 campos en vivo cuando mobile.de levante el rate-limit; refrescar `datos_mercado.json` (datos del 17-ago, showstoppers caducados el 31-ago); reimportar los 2 ZIP en Cowork. Sigue lo de siempre (spatie, password BD, `$env:IMPORTNEX_TOKEN`).
- Commit: ver `git log` (rama master)

---

## 2026-09-13 · Copilot-VSCode · catálogo de IDs de mobile.de extraído y verificado

- Hice: descubrí que **el HTML de mobile.de embebe su catálogo completo de marcas y modelos con IDs** (`{"label":"Golf","value":"14"}`). Extraje y **verifiqué por conteo de anuncios**: VW Golf=**14** (57.717), Arteon=**64** (2.007), Tiguan=54 (21.259), Audi A3=**8** (17.868), BMW 320=**10** (11.351), Ford Focus=20 (17.721). Guardado en `app/Support/data/mobile-de-catalogo.json` (fechado) con marcas + modelos de VW, Audi, BMW, Ford y Porsche completos.
- ⚠️ **El `12603` del skill estaba caducado**: devuelve **0** anuncios; el bueno es `14`. Y la tabla de makeIds de `empaquetar.py` tenía valores inventados — `Ford=24500` es en realidad **TVR**, `Opel=29000` no existe (es **19000**), `Skoda` es **22900**. Esos IDs devolvían la marca equivocada.
- coches.net: `ModelIds[0]` es preferible a `Versions[0]`. Verificado `MakeIds[0]=47&ModelIds[0]=89&Versions` → *"VOLKSWAGEN Golf de segunda mano"*. Golf=89 mapeado; el resto cae a `Versions[0]` (fallback sancionado).
- Generado para tus 3 coches reales: BMW 320d → `ms=3500;10;;;` · Audi A3 → `ms=1900;8;;;` · VW Golf 7.5 TCR → `ms=25200;14;;;` + `pw=209:217`.
- Toqué: `app/Support/PortalSearchUrls.php`, `app/Support/data/mobile-de-catalogo.json` (nuevo), `tests/Feature/CarEnlacesSueloTest.php` (16 tests), `.ai/rules/support.md` (con el **procedimiento de refresco** del catálogo), `docs/guias/02-flujo-a-unidad.md`.
- ⚠️ PENDIENTE para ti: falta el catálogo de modelos de **Mercedes/Seat/Cupra/Skoda/Opel/Volvo** (mobile.de respondió flaky en esas descargas). Esos modelos caen a `q=` por ahora. El procedimiento para añadirlos está en `.ai/rules/support.md`. Sigue pendiente también lo de siempre (spatie, password BD, `$env:IMPORTNEX_TOKEN`, reimportar ZIPs).
- Commit: ver `git log` (rama master)

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
