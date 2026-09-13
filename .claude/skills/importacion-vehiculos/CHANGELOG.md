## [3.9.7] - 2026-09-13

**Catálogo ampliado a 14 marcas: entran Toyota, Citroën y Dacia. Y metadatos corregidos.**

Al revisar el catálogo vi que sus notas decían *"añadir al mapa SOLO lo verificado"* pero
**37 de sus 53 marcas nunca se habían comprobado** (venían del payload, sin más). Como el
refactor de 3.9.6 hizo que `empaquetar.py` leyera de ahí, esas 37 marcas estaban entrando
en juego sin verificar. Resuelto:

- **Comprobadas en vivo las 53 marcas** el 13-sep-2026 (pidiendo `ms=<makeId>;;;` y
  leyendo el `<h1>`): **53/53 correctas**. Un ID del payload oficial es autoritativo;
  lo prohibido es **inventarlo**.
- **Modelos nuevos**, extraídos de `modelsCache` y confirmados con conteo:
  **Toyota** (55 modelos: `corolla=9` → 2.108 anuncios, `rav4=28` → 1.445),
  **Citroën** (53: `c3=11` → 6.399, `c5aircross=44` → 3.192) y
  **Dacia** (11: `duster=2` → 4.867, `sandero=24` → 4.897). Las tres caían a `q=`
  (texto libre) y ahora filtran por modelo de verdad.
- Metadatos del catálogo reescritos: procedencia, qué se verificó y cómo, y el aviso del
  `ms` de 6 campos (devuelve 1.524.511 anuncios sin avisar).
- Truco apuntado: la extracción va con `fetch` **same-origin** desde suchen.mobile.de
  (`page.request` no está disponible en ese entorno).

## [3.9.6] - 2026-09-13

**Los IDs de coches.net pasan al catálogo compartido (una sola fuente).**

`empaquetar.py` y el PHP de Laravel tenían **cada uno su copia** del mapa de
`MakeIds[]` de coches.net, y divergieron: la copia de Laravel se quedó con los
valores viejos inventados (`bmw=11` → CITROEN, `mercedes=12` → DAEWOO,
`opel=7` → BMW, `toyota=10` → CHRYSLER, `volvo=26` → MASERATI) y generaba
enlaces a **la marca equivocada**. Solo acertaban `VW=47` y `Audi=4`.

- El mapa vive ahora en `references/mobile-de-ids.json`, sección `cochesnet`
  (**134 marcas**, extraídas del payload de coches.net) + `cochesnet.modelos`.
- `empaquetar.py` lo lee con `_make_id_coches_net()` / `_modelo_id_coches_net()`.
- Es el **mismo archivo** que usa Laravel (`app/Support/PortalSearchUrls.php`).
- Recordatorio: **los IDs no van por orden alfabético** (Mercedes=28, VW=47,
  Cupra=1400, Seat=39). No inventar: consultar el catálogo.

**Misma limpieza en mobile.de:** se ha **eliminado** el mapa hardcodeado
`MARCA_ID_MOBILE_DE` (16 marcas). Ahora `_make_id_mobile_de()` lo lee también del
catálogo, así que el script conoce las **53 marcas** en vez de 16 (las 38 que
faltaban caían a `q=` sin necesidad). Comprobado antes de borrarlo: los 15
valores reales coincidían con el catálogo, no había divergencia.

**Regla que sale de esto:** *ningún ID de portal se escribe a mano en el script.*
Si falta una marca, se añade al catálogo (con el procedimiento de refresco), no
al código. El bug de hoy nació exactamente de tener el mismo mapa en dos sitios.

## [3.9.5] - 2026-09-13

**Documentación: un `;` de más en `ms=` rompía el filtro por completo.**

Reconfirmado en vivo (navegando de verdad, 13-sep-2026) el punto que quedó abierto en la
sesión anterior:

| `ms=` | `;` | Campos | Resultado |
|---|---|---|---|
| `25200;14;;;` | 4 | 5 | "Volkswagen Golf" · **57.731 ofertas** ✅ |
| `25200;14;;;;` | 5 | 6 | "Todo" · **1.524.511 ofertas** ❌ (el catálogo entero) |

El formato correcto **siempre fue el de 5 campos** y el código (`empaquetar.py`,
`PortalSearchUrls.php`) ya lo generaba bien — pero la **documentación** tenía el de 6 en
**5 sitios de `02-flujos/playbook_filtrado.md`**, 1 en `02-flujos/paginas_reales.md` y 1 en
`02-flujos/extractores.md`. El error venía del 24-ago y era incoherente consigo mismo:
decía "5 campos" mientras escribía 5 `;`.

Es un bug traicionero porque **nunca da 0 resultados**: parece que funciona y te devuelve
medio millón de coches sin filtrar.

Corregidos los 7 sitios + añadido un aviso explícito en `playbook_filtrado.md` §Claves
("Ojo con un `;` de más... contar los `;` a mano").

> ⚠️ Las entradas de este CHANGELOG fechadas el **24-ago-2026** citan
> `ms=makeId;modelId;;;;` — **están mal**, se dejan como registro histórico. El formato
> bueno es `makeId;modelId;;;` (5 campos, 4 `;`).

---

## [3.9.4] - 2026-09-13

**Corrección de datos: la mayoría de los IDs de portal que usaba la skill estaban
INVENTADOS y devolvían OTRA MARCA.**

Sesión de verificación cruzada con Laravel. Se comprobó **cada ID abriendo la URL y
mirando el contador real de anuncios**, no fiándose de ninguna tabla.

**mobile.de — mapa de makeIds corregido:**

| Antes | Real | Marca |
|---|---|---|
| `opel=47000` | **19000** | Opel |
| `ford=24500` | **9000** (24500 es en realidad **TVR**) | Ford |
| `seat=12200` | **22500** | Seat |
| `skoda=2400` | **22900** | Skoda |
| `citroen=5000` | **5900** | Citroën |
| `cupra=25900` | **3** | Cupra |
| `nissan=21000` y `hyundai=21000` | mismo ID para dos marcas | — |

Se han **quitado** las marcas cuyo ID no se pudo verificar por conteo: sin ID la URL cae a
búsqueda por texto (`q=`), que es bastante mejor que filtrar por la marca equivocada.

**coches.net — mapa de MakeIds corregido:** 18 de 20 IDs apuntaban a otra marca
(`bmw=11` → CITROEN · `mercedes=12` → DAEWOO · `opel=7` → BMW · `toyota=10` → CHRYSLER ·
`volvo=26` → MASERATI). Solo acertaban `vw=47` y `audi=4`. Los IDs NO son alfabéticos
simples: **BMW=7, Mercedes-Benz=28, Opel=32, Seat=39, Skoda=40, Toyota=46, Volvo=48,
Cupra=1400**. Valores reales extraídos del payload de coches.net.

**`pw` va en kW, no en cv.** `_url_mobile_de` mandaba los cv crudos: para un Golf R de
310 cv pedía `pw=310:310`, que mobile.de lee como **421 cv** → **excluía el propio coche**.
Ahora `kW = cv × 0,7355 ±4 kW`.

**`ms` son CINCO campos.** `makeId;;;` (4 campos) dejaba la página en modo formulario con
0 tarjetas; ahora `makeId;modelId;;;` con el modelId resuelto desde el catálogo.

**Nuevo `references/mobile-de-ids.json`** — catálogo verificado por conteo el 13-sep-2026:
marcas + modelIds de VW, Audi, BMW, Mercedes-Benz, Porsche, Ford, Seat, Cupra, Skoda, Opel
y Volvo (el **mismo fichero que usa Laravel**, para que las URLs del ZIP y las del panel
admin coincidan). Orden de resolución: `mercado.busquedas_realizadas` manual → catálogo →
`q=`. **Nunca inventar un ID.**

**Los IDs CADUCAN.** `Golf Mk7.5 = 12603` (la tabla que circulaba en el playbook) devolvía
**0 anuncios** el 13-sep-2026; el vigente es **`14`** = **57.717** anuncios.

**Método de descubrimiento documentado** en `02-flujos/playbook_filtrado.md`: el HTML
**servido** de mobile.de embebe el catálogo completo (buscar `modelsCache`, o la lista de
marcas tras `"Alle Marken"`); en coches.net está en `window.__INITIAL_PROPS__` →
`listFiltersOptions.makeId.options`. Sustituye al método antiguo de leer selects
(`[data-testid="make-incl-0"]`), mucho más lento y frágil.

**`autoscout24.es`:** quitado `cy=<año>,<año>` — `cy` es el país, no el año.

**Mantenimiento:** el CHANGELOG tenía **2 bytes NUL** (de una sesión anterior en
PowerShell 5.1) que hacían que VS Code lo leyera como binario. Limpiados.

---

## [3.9.3] - 2026-09-12

Sesión de sincronización entre Claude (este workspace) y el repo Laravel real. Esta versión
refleja lo que está en producción HOY — no lo que el ZIP decía. Cambios que ya estaban
commiteados en master (commits 1c1c724..7596f6) y que la skill v3.9.2 no conocía.

**Panel admin /cars/{id} troceado (era el único fichero gigante del front):**

esources/js/Pages/Cars/Show.vue pasó de **1548 → 889 líneas** en commit 1c1c724 (Fase 1)
y a **717 líneas** en commit 4d946c (Fase 2). 11 partials nuevos en

esources/js/Pages/Cars/Partials/ y Modals/:

  | Partial | Líneas | Qué cubre |
  |---|---|---|
  | HeaderBar.vue | 85 | PageHeader + status bar + banner cliente |
  | OverviewPanel.vue | 93 | IEDMT + Location + Specs + Costs + Descripción + Equipment |
  | InvestigationPanel.vue | 191 | Veredicto + análisis IA + pros/cons + 9 research aspects |
  | MarketPanel.vue | 195 | Chips país + sub-stats + búsquedas realizadas + comparables |
  | ChecklistPanel.vue | 98 | Milestones + Inspections (	oggleChecklist event) |
  | AssignRequestPanel.vue | 49 | Vincular solicitud compatible + slot #messages |
  | NotesPanel.vue | 17 | Notas |
  | ExpensesPanel.vue | 39 | Tabla gastos estimados vs reales |
  | PhotosPanel.vue | 95 | Upload + grid + lightbox (state local en el partial) |
  | Modals/ShareTrackingModal.vue | 78 | Modal tracking con Teleport + Transition |

Quedan en Show.vue: tabs navigation, IEDMT warning siempre visible, **Documents** (form
upload + PDFs Laravel + public link + lista docs — alto acoplamiento con useForm de
Inertia), **ClientPanel** (assigned + solicitud vinculada + tracking + contrato — múltiples
useForm con state compartido), ConfirmDialog modals. Decisión documentada: extraerlos
duplicaría código.

**Bug detectado y corregido en auditoría del dossier (10-12-sep-2026):**
Show.vue leía $car->origin_country pero **esa columna no existe** en el schema (la real es
cars.pais_origen). El KPI Origen nunca aparecía y el Trust Bar siempre caía al genérico
"Origen verificado". Fix: leer pais_origen. Mismo patrón en el Blade del dossier público:
fuel llega del scraping en inglés (Gasoline, Diesel), ahora se traduce al español
(Gasolina, Diésel, Híbrido, Eléctrico, GLP) en el @php del hero y en el KPI.

**Dossier público reorganizado y limpio (commits 4a00f95, ea9b49b, 1908e5):**
- FAQ y CTA Final extraídos a 
esources/views/public/dossier/partials/{faq,cta-final}.blade.php.
- Spacing entre secciones: .container > section + section { margin-top: 80px } +
  display: flow-root (el gap: 60px del flex anterior colapsaba con el margin-bottom
  del <ul> interno y dejaba las secciones pegadas).
- Galería subida a posición 2 (el cliente quiere ver fotos antes de leer cifras).
- "Por qué destaca este coche" → "Lo mejor de esta unidad" (sin redundancia con "¿Por qué
  este coche?").
- Label precio: "Precio del vehículo" + "+ gastos gestión de compra" (antes contradictorio
  "Precio total cliente + gastos").
- Trust Bar arreglado: muestra "Importado desde Alemania" / "Localizado en España" /
  "Origen verificado" según pais_origen.

**Tests actualizados al contrato v3.9.2 (commit 7596f6):**
	ests/Unit/Support/PrecioClienteCalculatorTest.php reescrito entero (17 tests, 61
assertions) cubriendo: origen snake_case precio_origen, [PRECIO_ORIGEN] (no [PRECIO]),
gastos de skill como [{concepto, importe}], desglose con clave Precio del coche,
horquilla 	otal_min/	otal_max con redondeo a centenas, banda_motivo legible, CO₂
confirmado vs sin confirmar, coche ES sin IEDMT, parseEur con 6 formatos europeos,
guardarraíl anti-duplicado, FIX 21% IVA fantasma, filtro de conceptos prohibidos
(honorarios/comisión) del desglose de skill.

	ests/Feature/PublicCarFolletoTest::test_price_caption_says_gestion_y_matriculacion
actualizado al caption v3.9.2. Comentario CSS hueco → margen (se renderizaba y
rompía 	est_dossier_no_contiene_fugas_del_informe_arteon).

**Suite final:** Tests: 2 skipped, 660 passed (2391 assertions) — vs 644 antes de la sesión.
Los 2 skipped son PerformanceAudit pre-existentes.

**Commits en origin/master tras la sesión:**
`
07596f6  test(precio+dossier): actualizar al contrato v3.9.2          ← ÚLTIMO
cb5e01a  feat(marketing+precio): skill v3.9.2 + horquilla + canales + guía
a1908e5  fix(dossier): origen real, fuel en espanol, galeria arriba y titulos
ea9b49b  fix(dossier): margin-top en sections para evitar margin-collapse
4a00f95  fix(dossier): separar FAQ y CTA + spacing entre secciones
a586d20  chore(cars): eliminar handlePhotoFiles muerto
a4d946c  refactor(cars): continuar troceando Show.vue (889 a 717 lineas)
1c1c724  refactor(cars): trocear Show.vue (1548→889 lineas) con Partials/
`

**Pendientes resueltos del PENDIENTES-v3.9.2.md original:**
- P1 (ejecutar aplicar-v3.9.2.bat): hecho → commit cb5e01a.
- P2 (utf8mb4 en Forge): ya estaba. SHOW CREATE TABLE car_marketing_contents
  devuelve DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci.
- P3 (matriculación Arteon 01 vs 06/2023): ya resuelto — la barra KPI duplicada
  era el único sitio donde se veía "01/2023", y se eliminó en commit 1908e5.

**Notas para futuras sesiones de Claude:**
1. La skill ya NO necesita saber el troceo de Show.vue: cada vez que generes un ZIP con
   [VALORACION], [POR_QUE], etc., no toques nada del panel admin — Laravel los lee
   del JSON del ZIP y los pinta correctamente en OverviewPanel/InvestigationPanel.
2. Si una sesión futura ve la ficha del cliente en móvil con el caption "+ gastos gestión
   de compra", **es correcto** — ese es el caption canónico v3.9.0/v3.9.2. El caption
   "+ gastos de gestión de compra e importación" del commit v3.9.0 fue sustituido en
   v3.9.2 por este, más conciso.
3. cambioRaw puede traer literales del anuncio ("Automático (DSG 7v)") que el Blade
   normaliza a "Automático" o "Manual" según heurística de palabras clave (auto/DSG/
   Tiptronic). No tocar.
4. Los partials Vue **NO son responsabilidad de esta skill** — sólo se documentan en
   docs/ARQUITECTURA_VISTAS.md (a actualizar). La skill trata el ZIP, no el panel.
## [3.9.2] - 2026-09-12

Dos tandas de trabajo que quedaron sin aplicar al repo real (se prepararon en sesiÃ³n pero
nunca se copiaron a `.claude/skills/`): el rediseÃ±o mÃ³vil de la ficha del cliente + precio
en horquilla, y las correcciones de marketing pedidas despuÃ©s ("revisa e implementa todo").
Este es el primer commit real de ambas.

**Precio en horquilla (no una cifra cerrada):**
- `PrecioClienteCalculator.php` (Laravel) calcula ahora un rango `total_min`/`total_max` en
  vez de una cifra Ãºnica, con el ancho ligado a la incertidumbre real de cada partida
  (transporte Â±15 %, IEDMT Â±10 % con COâ‚‚ confirmado / Â±60 % sin confirmar).
- Se eliminÃ³ un 21 % de IVA que `gastosEstimados()` sumaba sobre el precio del coche y que
  `04-negocio/costes.md` no contempla â€” un usado de la UE no vuelve a tributar IVA en EspaÃ±a.
  Eran ~6.700 â‚¬ inventados en el caso que lo destapÃ³ (Arteon).
- `empaquetar.py` gana `gastos_cliente()`: agrupa `payload.costes` en las 4 lÃ­neas que puede
  ver el cliente (transporte / trÃ¡mites de exportaciÃ³n e ITV / IEDMT / gestiÃ³n y
  matriculaciÃ³n â€” honorarios SIEMPRE fundidos ahÃ­, nunca en lÃ­nea propia, regla dura nÂº3) y
  las emite como `[GASTO]`/`[FC_GASTO] concepto | importe` en `ficha-publicitaria.txt` y
  `ficha-cliente.txt`. `PrecioClienteCalculator::desgloseDeSkill()` las lee con prioridad
  sobre su propia estimaciÃ³n.
- `07-marketing/ficha_cliente.md` Â§3.5 reescrito: resuelve la contradicciÃ³n con el mockup
  `mockup_ficha_cliente_v3.html` (proponÃ­a "precio final, impuestos incluidos") a favor de la
  horquilla â€” el mockup es referencia de estructura, no de precio.
- Nuevo Â§3bis: `VALORACION` (quÃ© ES la unidad) y `POR_QUE` (por quÃ© te encaja) no pueden
  repetir ninguna cifra entre sÃ­ â€” antes decÃ­an lo mismo con otras palabras.

**RediseÃ±o mÃ³vil del dossier (`car-dossier.blade.php`):**
De 11.658 px (14,4 pantallas) a 6.582 px (8,1). GalerÃ­a en carrusel justo despuÃ©s de las
insignias de confianza (2.222 â†’ 411 px), "Nuestra valoraciÃ³n" y "Â¿Por quÃ© este coche?"
fusionados en un solo bloque, barra KPI eliminada (duplicaba 4 datos de la ficha tÃ©cnica con
valores que no coincidÃ­an â€” "01/2023" vs "2023"), equipamiento en chips plegables, insignias
de confianza alineadas en 2Ã—2, CTA fijo inferior (antes el primer botÃ³n de contacto al hacer
scroll aparecÃ­a en la pantalla 13 de 14), sin nombre del cliente en la pÃ¡gina. El bloque legal
A31 sigue completo y visible antes del CTA â€” no se toca ni una coma de su texto.

**A11 â€” los 4 canales de portal recibÃ­an el mismo texto (bug reportado 12-sep-2026):**
`ValuationPackageIngestor::attachMarketing()` solo leÃ­a el vocabulario v1
(`anuncio-portales.txt` â†’ `[TITULO]`/`[DESCRIPCION]`) y copiaba literalmente el mismo texto a
los 4 canales de `CarMarketingContent::PORTAL_CHANNELS` (milanuncios, coches_net, wallapop,
facebook-ad) â€” la skill llevaba desde el 05-sep generando contenido v2 diferenciado
(`PT_TITULO_A/B`, `FBMP_*`) que el panel nunca leÃ­a. Nuevo `ingestarPortalesV2()`:
- Milanuncios / Coches.net: texto base completo, tÃ­tulo A.
- Wallapop: tÃ­tulo B + el mismo cuerpo recortado a 600-900 caracteres (copy_engine.md Â§5:
  "un recorte del base, nunca una reescritura") â€” el recorte protege siempre completos la
  pega honesta (A28) y el aviso legal (A26/A27); si hace falta espacio se acorta antes el
  resto (ficha, equipamiento).
- Facebook Marketplace: sus propios bloques `FBMP_*` (que existÃ­an en el ZIP pero no tenÃ­an
  canal asignado en `esqueleto_a_json.py` â€” se aÃ±adiÃ³ `canales.fb_marketplace`).
- `copy_engine.md` Â§7 aclara que la banda 600-900 de Wallapop la aplica Laravel al montar el
  anuncio, no la skill (de ahÃ­ que `check_marketing.py` no tenga una banda "Wallapop" propia).

**La guÃ­a de lÃ­mites del editor de Marketing.vue nunca llegaba al panel (hallazgo de la
revisiÃ³n de cierre, 12-sep-2026):** `Marketing.vue` tiene un comentario fechado 09-sep-2026 que
da por hecho que `config/marketing_limits.php` existe y que `CarMarketingController` envÃ­a
`props.limits` â€” ninguna de las dos cosas era cierta. El editor llevaba usando siempre la guÃ­a
genÃ©rica (100 tÃ­tulo / 2200 descripciÃ³n / 5 hashtags) para los 6 canales, asÃ­ que el recorte de
Wallapop a 600-900 nunca se veÃ­a reflejado en su propio contador de caracteres. Se creÃ³
`config/marketing_limits.php` (mismos nÃºmeros que `MAX_HASHTAGS`/`bandas` de
`check_marketing.py` â€” una sola fuente) y `CarMarketingController::show()` ahora sÃ­ pasa
`limits`, con Wallapop diferenciado (600-900) del resto de portales (800-3000). Mismo patrÃ³n de
fallo que el punto anterior: un comentario "ya verificado" que nadie habÃ­a verificado de
verdad.

**MigraciÃ³n a utf8mb4 (revisado, no aplicado):** un comentario en el propio cÃ³digo
(`ValuationPackageIngestor::sanitizeForMysql()`, fechado 09-sep-2026) afirma que la BD de
Forge ya se verificÃ³ en utf8mb4, lo que contradice que siguiera en la lista de pendientes del
12-sep. No hay forma de comprobar el estado real de producciÃ³n desde aquÃ­ (sin acceso al
servidor Forge) â€” puede que ya estÃ© resuelto. Ver aviso en el paquete de sincronizaciÃ³n antes
de tocar nada en producciÃ³n.

## [09-sep-2026] â€” Cadena cerrada: empaquetar â†’ ZIP â†’ panel

La skill y el panel ya hablaban el mismo idioma en la documentaciÃ³n, pero no en el cÃ³digo.
Al probar el pipeline con el fixture saltaron tres roturas que nadie habrÃ­a visto hasta un
import real:

1. **`empaquetar.py` no generaba la ficha del cliente ni el JSON.** Ahora emite
   `contenido/ficha-cliente.txt` (31 bloques `[FC_*]`, solo con veredicto Comprar*),
   genera `contenido/json/*.json` con `esqueleto_a_json.py` y los declara en el manifest.
2. **Los bloques de marketing no coincidÃ­an.** `empaquetar.py` escribÃ­a el vocabulario v1.5
   (`TIKTOK_POST_n`â€¦) y el mÃ³dulo esperaba el v2 (`IG_`, `VT_`, `FB_`, `FBMP_`, `PT_`): el
   validador respondÃ­a "archivo sin bloques reconocibles" a TODO lo generado. Ahora se
   emiten los dos, como puente, hasta que el panel migre.
3. **Los parsers no seguÃ­an el contrato.** `check_marketing.py` solo aceptaba el marcador en
   lÃ­nea propia y `esqueleto_a_json.py` descartaba la continuaciÃ³n multilÃ­nea. Los dos
   interpretan ya el formato igual que `App\Support\Esqueleto`.

**AdemÃ¡s:** `[IG_GANCHO_B]` en la plantilla de redes (el A/B que prometÃ­a copy_engine Â§9.3),
superlativos comprobados por palabra completa ("corre" ya no salta dentro de "corresponde") y
el patrÃ³n de "concesionario" solo marca cuando se refiere a nosotros, no cuando es el taller
donde se sellÃ³ el libro.

**En el panel:** el ingestor acepta `.json` en `contenido/`, el controlador busca la ficha en
las dos rutas posibles y `FiltroPublico` aplica el mismo criterio de "concesionario".

**Prueba de humo:** ZIP del fixture â†’ `check_marketing.py` ðŸ”´ 0 Â· `check_ficha_cliente.py` sin
hallazgos salvo las fotos (que `--no-photos` no descarga).

## [07-sep-2026] â€” Ficha del cliente v2, A22b/A31 y entrega en JSON al panel

FusiÃ³n de la lÃ­nea de trabajo del Desktop sobre esta base (v3.7.1). Se conserva todo
lo que ya habÃ­a en `07-marketing/` y se aÃ±ade lo que faltaba:

**Nuevo**
- `07-marketing/ficha_cliente.md` â€” la pÃ¡gina que se manda al cliente por enlace
  (`/c/<token>`): 14 secciones, orden, quÃ© sale y quÃ© entra, y el diagnÃ³stico de la
  ficha real en producciÃ³n.
- `07-marketing/handoff_laravel.md` â€” entrega tÃ©cnica: **Laravel lee JSON, no `.txt`**,
  mapa bloque â†’ componente Blade con fallback, Open Graph para la previsualizaciÃ³n de
  WhatsApp, animaciones en tres capas y modo PDF, checklist de aceptaciÃ³n.
- `07-marketing/evidencia_externa.md` â€” de dÃ³nde sale cada regla importada de fuera
  (normas de Coches.net y Milanuncios, art. 20 TRLGDCU y VO expuesto, hashtags y
  *sends per reach* de Instagram, vÃ­deo corto, Open Graph). No confundir con
  `fuentes_y_evidencia.md`, que son las reglas de citaciÃ³n de la investigaciÃ³n.
- `07-marketing/plantillas/ficha-cliente.txt` + `plantillas/ejemplo/` (patrÃ³n y fixture).
- `scripts/esqueleto_a_json.py` â€” conversor de esqueletos a JSON tipado para el panel.
- `scripts/check_ficha_cliente.py` â€” validador propio de la ficha (severidad ðŸ”´ðŸŸ ðŸŸ¡);
  no toca los 30 checks de `check_marketing.py`.
- `memoria/marketing-resultados.md` â€” quÃ© gancho funcionÃ³, por coche y canal.

**Reglas**
- **A22b** â€” el enlace del cliente es PÃšBLICO. Fugas reales cortadas: Â«hueco de 8.969 â‚¬
  antes de costesÂ», Â«solo 11 unidades en toda EspaÃ±aÂ», Â«vendedor profesional 4.5â˜…Â»,
  Â«vendibilidad (84/100)Â», Â«ahorro estimadoÂ» y el veredicto interno.
- **A31** â€” gestor, no vendedor: **no vendemos coches y no damos garantÃ­a** (supera a A30).
  Prohibido Â«IVA incluidoÂ», Â«precio finalÂ» y Â«llave en manoÂ»; el caption del precio sigue
  siendo `+ gastos gestiÃ³n de compra` (`.ai/rules/business-model.md`).
- Â§12 del dossier reescrita como "QuÃ© hacemos y quÃ© NO hacemos".
- `copy_engine.md` Â§9: addendum con send-ask, tono por situaciÃ³n, A/B de ganchos,
  nÃºmeros de vÃ­deo y mÃ©tricas.

**En el panel (mismo commit)**: `App\Support\FiltroPublico` corta las fugas aunque vengan
de datos antiguos, el dossier pÃºblico gana las secciones que faltaban y las fotos dejan de
ir en base64 (og:image ya es una URL, que es lo que WhatsApp necesita).

# Changelog

Todos los cambios notables en el skill `importacion-vehiculos` se documentarÃ¡n en este archivo.

El formato estÃ¡ basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [3.7.2] - 2026-09-09 â€” `--laravel-storage` en `empaquetar.py` (A31)

> **Motivo:** el ZIP del coche estaba bajando a `C:\Users\jacar\Downloads\` cuando se descargaba por el navegador (caso real: `vw-arteon-r-sb-2023-447819922.zip`). Eso lo deja muerto y obliga a copiarlo a mano. La ruta correcta desde el repo es `<root>/storage/app/private/investigaciones/<marca>/<modelo>/<coche>-<fecha>.zip`.

### âœ¨ Nuevo
- **`empaquetar.py --laravel-storage`**: nueva opciÃ³n que guarda el ZIP en `<raÃ­z-del-proyecto>/storage/app/private/investigaciones/<marca>/<modelo>/<coche>-<fecha>.zip`. La raÃ­z se detecta automÃ¡ticamente buscando `artisan` + `composer.json` desde cwd (sube hasta 10 niveles).
- Ayudante `find_project_root()` en `empaquetar.py`.
- Ayudante `derive_laravel_storage_path()` que normaliza marca/modelo a slug.

### ðŸ›¡ï¸ Reglas actualizadas
- **A31** (nueva): "ZIP del coche guardado en `C:\Users\jacar\Downloads\`". NUNCA debe quedar ahÃ­. Caso real documentado.
- **A32** (renumerada desde A31): "Presentarnos como vendedor o insinuar garantÃ­a" (sin cambios de fondo, solo nÃºmero).

### ðŸ“š DocumentaciÃ³n
- `05-operaciones/operaciones.md` Â§ Comandos por flujo: tabla de las 4 rutas vÃ¡lidas (`--laravel-storage`, `--auto-path`, `--out`, default).
- `SKILL.md` Â§ RUTAS DE GUARDADO: aÃ±adido el punto 5 con la regla.

## [3.7.1] - 2026-09-06 â€” Flujo M: marketing multicanal (implementaciÃ³n Fase 1)

> **Motivo:** el plan multicanal `docs/PLAN_MARKETING_MULTICANAL_2026-09-06.md` proponÃ­a una Fase 1 implementada pero la skill no la tenÃ­a commiteada. Esta versiÃ³n la reconstruye dentro del repo y la protege con un validador de 30 checks.

### âœ¨ Nuevo
- **MÃ³dulo `07-marketing/`** (5 archivos): motor central + spec por canal + plantillas v2 + biblioteca de ganchos + fuentes y evidencia.
  - `copy_engine.md` â€” voz de marca, lÃ©xico cerrado (iconos + hashtags), matriz de canales, 11 reglas duras (A23-A30).
  - `redes_sociales.md` â€” spec IG feed/stories/Reel/TikTok/Shorts, FB pÃ¡gina, FB Marketplace.
  - `portales_anuncio.md` â€” spec Coches.net/Milanuncios/Wallapop (texto base + 3 deltas) + aviso legal completo.
  - `biblioteca_ganchos.md` â€” 8 Ã¡ngulos de venta con proof point + aperturas prohibidas + fÃ³rmula de la pega honesta.
  - `fuentes_y_evidencia.md` â€” reglas de citaciÃ³n + catÃ¡logo de fuentes + plantilla de cita + auditorÃ­a mensual.
  - `plantillas/redes-sociales.txt` + `plantillas/anuncio-portales.txt` â€” v2 con placeholders.
  - `plantillas/ejemplo/*` â€” ejemplo relleno que valida verde (0 rojos, 0 naranjas, 0 amarillos).
- **`scripts/check_marketing.py`** â€” validador Python 3.13 (sin dependencias externas, stdlib pura) que ejecuta los 30 checks con severidad ðŸ”´/ðŸŸ /ðŸŸ¡ y exit codes `1` (ðŸ”´), `2` (ðŸŸ ), `0` (verde). CLI: `python scripts/check_marketing.py <archivo.txt> [archivo2.txt ...]` o `python scripts/check_marketing.py -r contenido/`. Soporta UTF-8 en consola Windows.
- **Anti-patrones A24-A30** en `06-reglas/anti_patrones.md`: superlativos/ganchos vacÃ­os, emoji decorativo, aviso legal incompleto, fecha de matriculaciÃ³n, pega honesta, icono âš ï¸ sin pega, "garantÃ­a" sin detalle.
- **Regla M-12** en `copy_engine.md`: el link original del anuncio SIEMPRE acompaÃ±a al copy (bloques `[XX_FUENTES]`). El validador exige el link en cada canal (check C21). ExcepciÃ³n: `[XX_FUENTES]` no dispara C07 (enlaces prohibidos en portales) ni C09 (URL interna prohibida).
- **Flujo M** aÃ±adido a SKILL.md (tabla de 6 flujos + Ã¡rbol de detecciÃ³n automÃ¡tica). Se activa solo tras Flujo A con veredicto ðŸŸ¢/ðŸ”µ.
- **`docs/PLAN_MARKETING_MULTICANAL_2026-09-06.md`** â€” copia canÃ³nica del plan en `docs/` (referencia, no fuente Ãºnica).

### ðŸ§ª CÃ³mo verificar

```powershell
# Verde con el ejemplo
py .claude/skills/importacion-vehiculos/scripts/check_marketing.py `
   .claude/skills/importacion-vehiculos/07-marketing/plantillas/ejemplo/redes-sociales-ejemplo.txt `
   .claude/skills/importacion-vehiculos/07-marketing/plantillas/ejemplo/anuncio-portales-ejemplo.txt
# Salida: ðŸ”´ 0  ðŸŸ  0  ðŸŸ¡ 0  EXIT 0

# Validar todo un directorio de contenido
py .claude/skills/importacion-vehiculos/scripts/check_marketing.py -r contenido/

# Salida JSON (para CI)
py .claude/skills/importacion-vehiculos/scripts/check_marketing.py --json <archivo>
```

### âš ï¸ Decisiones de diseÃ±o (no obvias)

- **A26 (sin iconos en portales) admite excepciÃ³n en `[PT_ESTADO]`**: âš ï¸ y âœ… son parte de la fÃ³rmula de la pega honesta (A28) y de "verificado", no decoraciÃ³n. El validador los permite solo ahÃ­.
- **"Vendedor original" del aviso legal NO dispara C09**: refiere al vendedor del coche en origen (alemÃ¡n), no a JJ Import Motors. Es legÃ­timo y necesario para que la garantÃ­a legal quede explicada.
- **Plantillas con placeholders `<...>` fallan C19 por diseÃ±o**: son plantillas vacÃ­as, no copy final. El validador comprueba copy final; el ejemplo relleno `plantillas/ejemplo/` pasa verde.

## [3.7.0] - 2026-09-06 â€” GuÃ­a de copywriting + fix charset marketing

> **Motivo:** auditorÃ­a de calidad de los textos generados (GANCHO/POST/STORY/TITULO/DESCRIPCION) tras detectar copy con placeholders sueltos ("! Mercedes-AMG...", "? ConsÃºltanos") en producciÃ³n, causado por: (1) emoji de 4 bytes que la BD utf8mb3 no admite y el ingestor sustituÃ­a por sÃ­mbolos ASCII feos, y (2) un bug de parsing en `Esqueleto::desde()` (Laravel) que corrompÃ­a cualquier caracter UTF-8 cuyo byte de continuaciÃ³n fuera 0x85.

### âœ¨ Nuevo
- **`06-reglas/copywriting_marketing.md`**: guÃ­a de redacciÃ³n con lÃ­mites por canal (TikTok/Instagram/Facebook/portales), fÃ³rmula del GANCHO, tono por red social, estructura de DESCRIPCION de portales y anti-patrones de copy (A-COPY1..5).
- **Regla de oro nueva:** NO usar emoji de color (ðŸ”¥ðŸš€ðŸ’¯â­ðŸ‘ðŸ’°) en ningÃºn bloque de marketing â€” la BD Forge (utf8mb3) los elimina al importar. Usar texto/mayÃºscula/exclamaciÃ³n para Ã©nfasis, `â€¢` o `-` para viÃ±etas.

### ðŸ› Corregido (lado Laravel, referencia)
- `App\Support\Esqueleto::desde()` corrompÃ­a caracteres UTF-8 multibyte cuyo byte de continuaciÃ³n era `0x85` (p.ej. âœ… = `E2 9C 85`) por falta del modificador `/u` en `preg_split('/\R/', ...)`.
- El ingestor ahora crea el marketing importado como `status=published` (no `draft`) y el reimport PRESERVA el status existente en vez de resetear a draft.

## [3.6.1] - 2026-09-05 â€” AuditorÃ­a v2: 3 bugs crÃ­ticos en generate/publish/UI resueltos

> **Motivo:** auditorÃ­a integral del flujo v2 detectÃ³ que los endpoints `generate` y `publish` seguÃ­an usando el unique viejo `(car_id, channel)` â€” con hasta 6 filas por canal actualizaban una fila indeterminada o creaban filas espurias. AdemÃ¡s el template de `Marketing.vue` no tenÃ­a las tabs de Posts/Stories (solo el script las soportaba).

### ðŸ› Bugs crÃ­ticos resueltos
- **`CarMarketingController::generate()`**: ahora fija `kind`+`slot` segÃºn tipo de canal (social â†’ `post`/`1`; portal â†’ `ad`/`1`) en el `updateOrCreate`. Antes podÃ­a crear filas `ad` espurias en instagram/tiktok.
- **`CarMarketingController::publish()`**: acepta `kind`+`slot` opcionales. Si llegan â†’ publica SOLO esa pieza; si no â†’ publica TODAS las piezas del canal (el botÃ³n del panel es "canal publicado"). Antes publicaba solo la primera fila (indeterminada).
- **`Marketing.vue` template**: aÃ±adidas las tabs v2 â€” selector de tipo (Publicaciones / Stories / Marketplace para facebook) + selector de slot (1-3) + panel "Pasos para subir" editable + History con etiquetas Post N / Story N / Ficha y origen ZIP/IA.

### ðŸŽ¨ Frontend
- **`Marketing.vue`**: `socialKinds` computado â€” facebook es hÃ­brido (posts + stories + ficha Marketplace); tiktok/instagram solo posts+stories. `publish()` a nivel canal (backend publica todas las piezas).
- **`Marketing/Index.vue`**: `channelsOf()` agrupa las 22 filas por canal (1 chip por canal con "Ã—N" piezas y estado agregado). `channelSummary()` muestra Publicado/Borrador/Parcial (n/N). Antes un coche con ZIP completo mostraba 22 chips repetidos.

### ðŸ§¹ Limpieza
- Eliminado `socialChannelUpper()` (cÃ³digo muerto â€” la closure ya inlineaba `strtoupper`).
- Docblock de `attachMarketing()` actualizado al mapeo v2.
- MigraciÃ³n: quitado `orderBy('id')` redundante en `chunkById` (ya ordena internamente).

### ðŸ§ª Tests PHPUnit
- `26 passed (161 assertions)` â€” 3 nuevos: publish todas las piezas, publish pieza Ãºnica (kind+slot), generate social â†’ (post, 1) exacto.

---

## [3.6.0] - 2026-09-05 â€” Marketing v2: 3 posts + 3 stories por red + ficha base portales

> **Motivo:** el usuario pidiÃ³ que las redes sociales (TikTok, Instagram, Facebook)
> tengan **3 publicaciones + 3 stories cada una** con tono distinto, y que los
> portales web (Wallapop, Coches.net, Milanuncios, Facebook Marketplace) compartan
> **una sola ficha base**. Esto requiriÃ³ ampliar el esquema a `kind + slot`.

### ðŸ†• Esquema v2: `kind` + `slot`
- **MigraciÃ³n nueva** `add_slot_and_subir_pasos_to_car_marketing_contents`:
  aÃ±ade `kind` ('post'|'story'|'ad'), `slot` (1..3), `subir_pasos` (texto).
  Backfill de filas existentes: `kind='ad' slot=1` (portales) o `kind='post' slot=1`
  (instagram/tiktok legacy). **Unique viejo** (`car_id`, `channel`) **eliminado**;
  **unique nuevo** (`car_id`, `channel`, `kind`, `slot`) creado.

### ðŸ“± Redes sociales: 3 redes Ã— (3 posts + 3 stories)
- **TikTok** â€” viral 15-30s, hook en el primer segundo, hashtag trending + nicho.
- **Instagram** â€” visual, storytelling, hashtags nichos (15-20), estÃ©tica cuidada.
- **Facebook** â€” informativo masivo, datos y precio visibles, hashtags mÃ­nimos (3-5).
- Cada red: `[RED]_POST_1..3` (3 captions), `[RED]_STORY_1..3` (3 textos cortos),
  `[RED]_HASHTAGS` (mÃºltiples), `[RED]_SUBIR_PASOS` (instrucciones para esa red).
- Total filas por coche: **18** (3 redes Ã— 6 piezas).

### ðŸŒ Portales web: 1 ficha base reutilizada
- **Misma ficha** (TITULO + DESCRIPCION + FICHA_RAPIDA + QUE_INCLUYE + AVISO_LEGAL
  + SUBIR_PASOS) para milanuncios, coches_net, wallapop, facebook marketplace.
- `kind='ad'`, `slot=1`, mismo contenido en los 4 portales.
- Total filas por coche: **4**.

### ðŸ“Š Total por coche con ZIP completo
- 18 redes + 4 portales = **22 filas en `car_marketing_contents`**.

### ðŸ”§ Cambios tÃ©cnicos
- `CarMarketingContent`: aÃ±adido `$fillable` (`kind`, `slot`, `subir_pasos`),
  `$casts` (`slot` â†’ integer), `KINDS` (`post|story|ad`),
  `SOCIAL_CHANNELS = [instagram, tiktok, facebook]`, `SLOTS_PER_SOCIAL = 3`,
  scopes `social()` y `portals()`.
- `ValuationPackageIngestor::attachMarketing()`: closure helper para 3+3
  filas por red. Posts vacÃ­os â†’ warning explÃ­cito. Stories vacÃ­os â†’ silencioso.
- `ValuationPackageIngestor::upsertMarketing()`: unique compuesto
  `(car_id, channel, kind, slot)`.
- `CarMarketingController::save()`: valida `kind` + `slot`. Portales fuerzan
  `kind=ad, slot=1`.
- `empaquetar.py`: `generar_redes_sociales()` genera los 3 posts + 3 stories
  por red con tono y `[RED]_SUBIR_PASOS`. `generar_anuncio_portales()` aÃ±ade
  `SUBIR_PASOS` con instrucciones para los 4 portales.
- `Marketing.vue`: `activeSlot` (1..3) + `activeKind` ('post'|'story') + tabs
  en redes sociales. Pasa `kind` + `slot` al endpoint save. Muestra `subir_pasos`.

### ðŸ§ª Tests PHPUnit
- `23 passed (150 assertions)`: 12 marketing ZIP + 11 CarMarketing.
- MigraciÃ³n nueva testeable cuando MySQL estÃ© disponible (sandbox).

### âœ… ValidaciÃ³n T5
- `python scripts/empaquetar.py` con JSON completo genera ZIP con 7 archivos.
- `redes-sociales.txt` incluye las 3 redes con 3 posts + 3 stories cada una.

---

## [3.4.1] - 2026-08-24 â€” AuditorÃ­a bÃºsqueda/filtrado/tratamiento: 15 gaps resueltos

> **Motivo:** auditorÃ­a enfocada en los 3 pilares del skill (bÃºsqueda, filtrado, tratamiento de datos) detectÃ³ 15 gaps. PatrÃ³n dominante: **fragmentaciÃ³n por fecha** (3 generaciones de URLs mobile.de conviviendo en 3 archivos) + **persistencia incompleta** (query no guardada, sin dedup cross-portal, sin esquema de ficha).

### ðŸ”´ URLs contradictorias purgadas (GAP-01/02/13 â€” crÃ­tico)
- **`extractores.md`**: tabla `ms` vieja (`25200;;29;GTI`) marcada **DEPRECADA** â€” esa sintaxis FALLA (0 resultados, hallazgo 24-ago). MakeIds migrados a tabla nueva; modelGroup viejos NO vÃ¡lidos.
- **`paginas_reales.md`**: URL `www.mobile.de/...lang=de` marcada deprecada con puntero a la canÃ³nica.
- **`navegacion_real.md`**: truco "si suchen falla â†’ usa www" **CORREGIDO** (era al revÃ©s: www/es = modo formulario sin tarjetas).
- **BÃºsquedas A/B/C del playbook**: ahora citan la plantilla canÃ³nica suchen+sb=p explÃ­citamente.

### ðŸ†” IDs mobile.de + descubrimiento (GAP-03/04)
- Nueva **tabla makeId;modelId** en playbook (VW=25200, Golf Mk7.5=12603 verificados; resto â¬œ).
- **Procedimiento de 3 pasos** para descubrir IDs nuevos vÃ­a `/es/s/auto` (constructor de queries) + validaciÃ³n contra `<h1>`.

### ðŸ“„ PaginaciÃ³n + checkboxes full resueltos (GAP-05/14)
- ParÃ¡metro `pageNumber=N` documentado + protocolo por bloques (scrollâ†’readâ†’pÃ¡gina sig.) integrado con A12.
- **ContradicciÃ³n resuelta**: `/es/s/auto` = CONSTRUCTOR de queries (conteo); tarjetas reales = suchen. Por defecto: filtrar estructurado + verificar equipamiento ficha a ficha.

### ðŸ§® NUEVA secciÃ³n TRATAMIENTO DE DATOS (GAP-09/10/11/12)
- **Dedup cross-portal por clave fuzzy** (marca+modelo+aÃ±o+kmÂ±2%+kWÂ±3) â€” los IDs NO son comparables entre portales. Regla A8 reforzada: AutoUncle NUNCA suma al conteo DE (agregador).
- **Esquema de ficha normalizada** (JSON destino Ãºnico: precio_contado_eur, fiabilidad, equipamiento, fecha_lectura...).
- **REGLA DURA `query_reejecutable` NUNCA vacÃ­o** (+ fecha_medicion + contador) â€” bloqueante en cierre. LecciÃ³n Golf 7.5 (717 vs 13 inexplicable).
- **ReconciliaciÃ³n de conteos**: fuente de verdad = portal primario en vivo; discrepancia >10% â†’ re-medir con query guardada; nunca sobrescribir en silencio.

### ðŸ”² Matriz filtro Ã— portal (GAP-08)
- Tabla Ãºnica filas=filtro, columnas=6 portales, celdas=parÃ¡metro URL. Huecos marcados â¬œ (no inventados).

### âš¡ Tabla potencias ampliada (GAP-07/15)
- De 7 a 17 variantes (aÃ±adidos S3, M135/M140i, i30N, Cupra VZ, Focus ST, MÃ©gane RS, Golf 8 GTI, A45 S...).
- Nueva columna **`pw=` plantilla por variante EXACTA** + regla de derivaciÃ³n (kW = cvÃ—0,7355 Â±4kW) â€” el rango amplio viejo mezclaba generaciones (Golf R 212-240 metÃ­a pre-FL+Mk8).
- Doble pasada PASO 2 reescrito con `pw=` real (eliminada referencia a "ps" inexistente).

### Otros
- Regla 8 de oro corregida: uniÃ³n por clave fuzzy, no por ID cross-portal.
- `como_deben_ser_las_sesiones.md`: regla dura query_reejecutable en el output por modelo.

---

## [3.4.0] - 2026-08-24 â€” URL mobile.de que SÃ funciona + extracciÃ³n de tarjetas virtualizadas

> **Motivo:** la pasada en vivo del 23-24 ago del Golf 7.5 (estudio de mercado multi-variante) descubriÃ³ que la URL clÃ¡sica de mobile.de (`www.mobile.de/es/s/auto?s=Car&vc=Car&ms=...`) entra en **modo formulario avanzado** y NO muestra las tarjetas de resultados. Solo la URL `suchen.mobile.de/fahrzeuge/search.html?...` con `ms=makeId;modelId;;;;` (⚠️ **formato ERRÓNEO: son CINCO campos, `ms=makeId;modelId;;;` — ver 3.9.5**) y `sb=p` devuelve resultados reales. AdemÃ¡s, la pÃ¡gina es **virtualizada** (`get_page_text` solo lee el panel de filtros), asÃ­ que la extracciÃ³n de tarjetas necesita `find()` + `read_page()` + `computer scroll`. Se documenta tambiÃ©n una nueva trampa de Coches.net: `TransmissionTypeId=2` (Manual) en Golf R devuelve fichas etiquetadas como "DSG" en el propio tÃ­tulo.

### ðŸ”¬ `playbook_filtrado.md` â€” nueva subsecciÃ³n mobile.de
- **NUEVA Â§"ðŸ‡©ðŸ‡ª mobile.de â€” URL de resultados reales que SÃ funciona (24-ago-2026)"**: URL `https://suchen.mobile.de/fahrzeuge/search.html?dam=0&fr=<aÃ±oDesde>%3A<aÃ±oHasta>&isSearchRequest=true&ml=%3A<kmMax>&ms=<makeId>%3B<modelId>%3B%3B%3B&od=up&s=Car&sb=p&vc=Car&pw=<kWdesde>%3A<kWhasta>&tr=MANUAL_GEAR|AUTOMATIC_GEAR`. Claves: `ms=makeId;modelId;;;;` (⚠️ **ERRÓNEO: CINCO campos, `ms=makeId;modelId;;;` — ver 3.9.5**) (NO `make;;variante`), `sb=p` (precio) + `ms` juntos. La URL `/es/s/auto` (sin pasar por `suchen.mobile.de`) es el modo formulario â†’ muestra conteo pero no tarjetas, tiene botÃ³n "Ofertas" que no navega a resultados reales.
- **NUEVA Â§"ðŸ“œ mobile.de â€” extracciÃ³n de tarjetas virtualizadas"**: `get_page_text` solo devuelve el panel de filtros. Para leer tarjetas hay que `find()` el contenedor ("container/list element that holds all the vehicle result cards") â†’ `read_page(ref_id=...)`. Virtualizado: solo lee patrocinada + primera orgÃ¡nica montada; para mÃ¡s, `computer scroll` + re-leer o `screenshot` (cuando funciona) para lectura visual rÃ¡pida de precio+tÃ­tulo+km+aÃ±o.

### âš ï¸ Limitaciones documentadas (24-ago-2026)
- **mobile.de â€” combobox "NÃºmero de puertas" (`TWO_OR_THREE`/`FOUR_OR_FIVE`/`SIX_OR_SEVEN`) NO se ha conseguido aplicar como filtro verificable** ni por URL ni por clic. Sigue como limitaciÃ³n abierta en el playbook.
- **mobile.de â€” checkbox "Panel de instrumentos digital" (cuadro digital)**: vive detrÃ¡s de un enlace "MÃ¡s..." en Conjuntos de funciones que no respondiÃ³ a intentos de expansiÃ³n. Sigue sin filtro fiable en ningÃºn portal para este equipamiento.
- **Coches.net â€” `TransmissionTypeId=2` (Manual) NO fiable en Golf R**: las 3 fichas devueltas llevan "DSG" en el propio tÃ­tulo. Para GTI y Clubsport el mapeo sÃ­ coincide. **Tratar `TransmissionTypeId=2` con cautela en Golf R especÃ­ficamente** â€” verificar ficha antes de presentar un "Golf R manual" al cliente.

### ðŸ”§ SKILL.md
- Bump versiÃ³n 3.3.9 â†’ **3.4.0** en frontmatter.

---

## [3.3.9] - 2026-08-23 â€” Ruta dual del mapa (Desktop + workspace)

> **Motivo:** el usuario quiere que `datos_mercado.json` viva en 2 rutas para tener copia accesible a diario sin abrir VS Code. Mismo cambio que `estudio-mercado` v0.3.11.

### ðŸ“ SKILL.md Â§RUTA PACTADA dual
- El mapa vive en AMBAS rutas:
  1. `C:/Users/jacar/Desktop/JJImportMotors/datos_mercado.json` (principal)
  2. `C:/laragon/www/importnexcore/.claude/skills/datos_mercado.json` (espejo)
- Campo `ruta_canonica` lista ambas separadas por `|`.
- Si no existe al cerrar â†’ crear con `schema_version` y `ruta_canonica` en AMBAS.
- Si existe â†’ releer y MERGE por `slug`. La IA escribe en 1 y Copilot espeja. NUNCA divergir.

---

## [3.3.8] - 2026-08-23 â€” Filtros estructurados SIEMPRE + cobertura mÃ­nima

> **Motivo:** el usuario recordÃ³ que el campo de versiÃ³n de texto de Coches.net es trampa conocida. Reforzar como regla dura. AdemÃ¡s, evitar veredictos con muestra insuficiente.

### ðŸ”´ SKILL.md â€” Regla dura de filtrado (nueva)
- En Coches.net **NUNCA** filtrar por `Versions[]`/`Version=` (mezcla generaciones).
- **SIEMPRE** filtros individuales estructurados por URL (`PowerHpFrom-To` / `MinYear` / `MaxKms` / `Fueltype2List` / `ArrBodyType` / `minDoors` / `TransmissionTypeId` + `fi=Price&or=1`).
- En mobile.de: filtros estructurados + doble pasada por kW.
- **Si la IA usa el campo de versiÃ³n de texto, el sondeo entero es INVÃLIDO y hay que rehacerlo.**

### ðŸ”´ SKILL.md â€” Regla dura de cobertura mÃ­nima (nueva)
- Para veredicto de negocio en Flujo A o B: **â‰¥3 candidatos verificados** entre las 7 fuentes.
- **â‰¥1 fuente por mercado** (1 DE y 1 ES, o solo DE si el usuario eligiÃ³ solo DE).
- **Score cobertura â‰¥ 4/10**.
- Si no se llega al mÃ­nimo â†’ **NO dar veredicto**. Poner "Cobertura insuficiente (X/Y)" y PARAR.
- **NUNCA inventar veredicto con muestra <3.**

---

## [3.3.7] - 2026-08-23 â€” "Mejor preguntar 1 vez que inventar 1 dato" + checklist por flujo

> **Motivo:** la IA recibÃ­a mandatos vagos ("estudia X", "busca X", "evalÃºa X") y decidÃ­a por su cuenta. Mismo problema que en `estudio-mercado` v0.3.9. Se aplica el mismo patrÃ³n de rigor + preguntar primero.

### â“ SKILL.md Â§CUÃNDO PREGUNTAR (nueva secciÃ³n)
- **SIEMPRE preguntar** en 10 situaciones: "evalÃºa este coche" sin URL, "busca Golf" sin versiÃ³n, "busca para cliente" sin presupuesto, "IEDMT", "hazme un ZIP" sin contexto, origen DE/ES ambiguo, perfil familiar vago, etc.
- **NUNCA preguntar** lo mecÃ¡nico: flujo (A/B/C/D/E) auto-detectado, estructura del informe tÃ©cnico (15 secciones fijas), 7 fuentes fijas, listado-first (A17), top 5 siempre.
- **PREGUNTAR si hay 2+ opciones razonables**: 2 versiones encajan, 3+ motorizaciones, 2 carrocerÃ­as, presupuesto "alrededor de 20k", color, marca descartada, margen objetivo revendedor.
- **Formato literal** de la pregunta + recordatorio "(una vez me respondas, lanzo el flujo sin mÃ¡s paradas hasta el checkpoint)".

### ðŸ“‹ Checklist por flujo (nuevo)
- 5 bloques de checklist obligatorios antes de entregar: **Flujo A** (informe tÃ©cnico 15 sec + dossier + ficha + ZIP), **Flujo B** (top 5 + cobertura 7 fuentes + CP1), **Flujo C** (N modelos + comparativa + CP-C), **Flujo D** (segmentaciÃ³n paÃ­sÃ—aÃ±oÃ—motor + embudo 3 pasos + CP-D), **Flujo E** (catÃ¡logo + JSON + market:import).
- Cada bloque con âœ… que la nube autocompleta + mensaje de cierre literal especÃ­fico del flujo.

### ðŸ“š `como_deben_ser_las_sesiones.md`
- **NUEVO Â§Principio v3.3.7** al inicio: "mejor preguntar 1 vez que inventar 1 dato". Distingue decisiones de negocio (SE PREGUNTAN) de mecÃ¡nicas (NO SE PREGUNTAN).

---

## [3.3.5] - 2026-08-23 â€” Reglas de entrega anti-duplicaciÃ³n + Golf R cerrado (conf 4)

> **Motivo:** el usuario recibiÃ³ 8+ archivos por el mismo trabajo: 3 MD del mismo estudio (la nube sin persistencia regenera todo) + PDF por cada MD + un informe MODELO del R separado cuando solo se pidiÃ³ completar cobertura. Nada quedÃ³ mÃ¡s resumido/legible.

### ðŸ“„ Reglas de entrega (en `como_deben_ser_las_sesiones.md`)
- NUNCA regenerar un estudio/encargo ya cerrado desde cero (delta o preguntar).
- UN solo formato: Markdown. NO PDF (enlaces muertos) ni copias mÃºltiples.
- Completar una variante = ACTUALIZAR el informe existente, NO crear otro.
- Informe MODELO (Flujo B) solo si el usuario pide explÃ­citamente "busca unidades".
- En la nube: entregar `.md` + avisar que la fusiÃ³n al JSON la hace Copilot en VS Code.
- Resumen final de 1 pÃ¡rrafo con datos clave.

### ðŸ Golf R cerrado
- `datos_mercado.json`: vw-golf-75-r â†’ veredicto verde, confianza 4 (sube de amarillo/2), `pendiente_fase2=false`, suelo limpio DE 16.500â‚¬, cobertura DE 7/7 (129 anuncios), hueco +27,9/+23,0. Mejor hueco neto de la lÃ­nea. `siguiente_busqueda=vw-golf-75-r`.
- `modelos-medidos.md`: entrada Flujo B 7/7 con mejor candidato (16.500â‚¬, id=461349083).

## [3.3.4] - 2026-08-23 â€” Coches.net: mÃ©todo oficial de filtros individuales por URL

> **Motivo:** auditorÃ­a con el usuario detectÃ³ que el estudio Golf 7.5 dejÃ³ fuera 3 GTI genuinos de listado (19.990/20.200/20.490â‚¬) porque el sondeo usaba texto libre y el anti-bot cortÃ³ la verificaciÃ³n. El usuario confirmÃ³ el mÃ©todo correcto.

### ðŸ”¬ Coches.net por URL estructurada
- **NUEVA secciÃ³n en `playbook_filtrado.md`**: "Coches.net â€” MÃ‰TODO OFICIAL: filtros individuales por URL". Regla: NUNCA usar `Versions[]` texto libre; SIEMPRE marca+modelo + filtros individuales (`MakeIds`/`ModelIds`/`MinYear`/`MaxKms`/`Fueltype2List`/`PowerHpFrom-To`/`ArrBodyType`/`minDoors`/`TransmissionTypeId` + `fi=Price&or=1`).
- **Tabla de rangos de potencia por variante** para aislar sin texto libre: GTI 230 (228-232) Â· GTI Perf 245 (243-247) Â· TCR 290 (285-295) Â· Clubsport 265 (260-270 + MaxYear=2017) Â· R 310 (305-315) Â· R 300 pre-FL (297-303, descartar).
- **Trampas de listado**: 210cv NO es GTI Â· 220cv = Mk7 pre-FL matriculado tarde Â· 245cv 2023 = Mk8 (fuera) Â· financiado<contado Â· "ES" fÃ­sicamente en DE Â· km/aÃ±o inconsistentes.
- **Regla suelo de listado vs verificado**: el anti-bot corta en 5-6 fichas â†’ los que quedan son "de listado" (solo precio/aÃ±o/km), nunca inventar datos. Distinguir SIEMPRE en el informe.
- `datos_mercado.json`: nota GTI con los 3 candidatos de listado a verificar.

## [3.3.3] - 2026-08-21 â€” Ejemplo real plan+conversaciÃ³n en el MD de sesiones

> **Motivo:** el usuario pidiÃ³ ejemplos concretos para que las sesiones sean eficientes en tokens.

- **`02-flujos/como_deben_ser_las_sesiones.md`**: nueva Â§"ðŸ’¬ EJEMPLO REAL â€” plan + conversaciÃ³n completa (Golf 7.5 TCR)" con: 1) plan compacto (~120 tokens) 2) conversaciÃ³n completa paso a paso con las paradas 3) tabla de eficiencia de tokens (quÃ© NO hacer vs hacer) 4) mÃ©trica objetivo ~450-550 tokens por modelo.
- Checklist de inicio referencia al ejemplo.

## [3.3.2] - 2026-08-21 â€” Fixes de auditorÃ­a (C1-C4 + contadores)

> **Motivo:** auditorÃ­a independiente del pipeline detectÃ³ 4 crÃ­ticos + medios. Aplicados:

- **C1/C3 (JSON)**: `hyundai-i30n`â†’`hyundai-i30-n`; aÃ±adidos a la cola los modelos medidos ausentes (`mercedes-a45-amg`/`bmw-serie-1-m135`/`toyota-auris-hibrido-2016` â†’ `pendiente_busqueda`; el resto medido â†’ `estudiado`). `siguiente_busqueda=bmw-serie-1-m135` (hueco +12,7).
- **C2 (JSON)**: `vw-golf-r`/`audi-s3`/`ford-focus-st` (ya medidos) pasan de `pendiente_estudio` a `estudiado`.
- **C4 (PASO 0b)**: el mini-estudio inline ahora exige **SIN BANDA** (regla Seat/Cupra + Hallazgo 2) y **NO limpia `pendiente_fase2`** ni cierra entradas con nota de re-mediciÃ³n.
- **cupra-leon (JSON)**: veredicto `verde`â†’`amarillo` (verde con confianza 2 violaba la propia regla; pendiente de re-mediciÃ³n completa).
- **AuditorÃ­a de cierre**: "5 dimensiones"â†’"6" (la tabla ya tenÃ­a 6 filas) y "Salidas obligatorias (3)"â†’"(5)" (la lista ya tenÃ­a 5).
- **MD sesiones**: reglas anti-bucle + sesiÃ³n corta (`estudiando`) + merge de cola + alias de modelo (dry-run 1).

## [3.3.1] - 2026-08-21 â€” PASO 0b refinado tras dry-run (matriz por flujo + mini-estudio inline)

> **Motivo:** dry-run de 6 escenarios detectÃ³: el 0b bloqueaba absurdamente el Flujo C (que ES un estudio), dejaba pasar mediciones no fiables (verde con confianza 2), y contradecÃ­a la regla L6 (el usuario manda).

### ðŸ›‘ PASO 0b v2
- **CondiciÃ³n de "mercado verificado" completa:** veredicto ðŸŸ¢/ðŸŸ¡ **Y** confianza_precio â‰¥3 **Y** pendiente_fase2=false **Y** no caducado. Caso real: cupra-leon verde con confianza 2 â†’ NO cuenta.
- **Matriz por flujo:** A exento Â· B requiere â†’ **mini-estudio inline** (1 listado ES + 1 DE + cruce = 4-6 peticiones, vuelca `fuente_medicion: mini_estudio`, NUNCA aborta) Â· C/E son estudios de facto (sin bloqueo, vuelcan al mapa) Â· D registra propuestos como `pendiente_estudio`.
- **ClÃ¡usula "el usuario manda" (L6):** si insiste en buscar sin estudio/ðŸ”´ â†’ aviso 1 lÃ­nea + proceder + nota en el mapa. El pipeline es el camino por defecto, no camisa de fuerza.
- **`como_deben_ser_las_sesiones.md`**: nueva Â§"Excepciones y atajos por flujo" con la matriz + plantilla del mini-estudio inline.
- **`schema_datos_mercado.md`**: enum `fuente_medicion` + `mini_estudio` Â· tabla de transiciones de `estado_cola` por fuente.
- **`datos_mercado.json`**: `cola_trabajo` INICIALIZADA (35 modelos de los 6 segmentos, estados reales, `siguiente_estudio=vw-golf-75-tcr`; `seat-leon-cupra` Mk3 separado de `cupra-leon` Mk4). Backup `.bak-21ago`.

## [3.3.0] - 2026-08-21 â€” Pipeline conjunto con estudio-mercado (modelo por modelo)

> **Motivo:** se buscaron "Compactos deportivos" durante 3 dÃ­as sin mercado verificado â†’ unidades que no encajaban y lÃ­mite de 5h. La bÃºsqueda a ciegas estÃ¡ PROHIBIDA.

### ðŸ”„ Pipeline conjunto (MD maestro + cola de trabajo)
- **NUEVO `02-flujos/como_deben_ser_las_sesiones.md`** â€” MD maestro de todas las sesiones: 1 modelo por pasada (nunca segmento de golpe), 5 fases con PARADA obligatoria (preparaciÃ³n â†’ estudio â†’ encaje â†’ bÃºsqueda â†’ cierre), reglas anti-bucle, checklist de inicio de sesiÃ³n y formato de salida estÃ¡ndar.
- **PASO 0b â€” CHECK DE MERCADO OBLIGATORIO** en SKILL.md: antes de Flujo B/C/D/E, el modelo debe tener `veredicto` ðŸŸ¢/ðŸŸ¡ y `estado_cola`=estudiado/pendiente_busqueda en `datos_mercado.json`. Si no â†’ NO buscar (mercado primero o descartar). ExcepciÃ³n: Flujo A (URL concreta).
- **PASO 3b ampliado** (`01-arranque/planificador.md`): usa `cola_trabajo.estados` + enrutador `siguiente_*`; checkpoint modelo a modelo, no segmento a segmento.
- **Cola de trabajo compartida** (en `estudio-mercado/schema_datos_mercado.md` Â§Cola de trabajo): estados `pendiente_estudio` â†’ `estudiado` â†’ `pendiente_busqueda` â†’ `buscado` â†’ `descartado` + punteros `siguiente_estudio`/`siguiente_busqueda`.
- **SKILL.md**: bump 3.2.7 â†’ **3.3.0** + Â§PIPELINE CONJUNTO en planificaciÃ³n.

## [3.2.7] - 2026-08-18 â€” DetecciÃ³n de chollos con selectores por portal

> **Motivo:** la secciÃ³n "ðŸŽ¯ DetecciÃ³n de chollos" era texto genÃ©rico; ahora cada seÃ±al lleva su selector estable (cierra el ciclo filtros â†’ tarjeta â†’ chollo).

### ðŸŽ¯ Chollos
- Tabla seÃ±ales Ã— portal con selectores reales: **rating precio** (mobile.de `PriceRatingBadge--label` VERY_GOOD/GOOD Â· Coches.net `mt-CardAdPrice-cashLabel` "Buen precio" 4/5 Â· AutoUncle `aria-label` 4-5) Â· **dÃ­as >60** (AutoUncle Clock `._CikIC` Â· Milanuncios `.ma-AdCardV2-time`) Â· **bajada** (mobile.de `strike-through-price` Â· Coches.net `-22%` Â· kleinanzeigen `p.line-through` Â· AutoUncle `price-history` Â· Milanuncios `--iterationInline`) Â· **VB** (kleinanzeigen Â· Wallapop "Negociable") Â· **privado** (mobile.de Privatanbieter Â· kleinanzeigen `posterType=PRIVATE` Â· Wallapop private) Â· **1 dueÃ±o** Â· **TÃœV NEU** Â· **"Por debajo del mercado"** (AutoUncle `._2OgvT`).
- Lectura rÃ¡pida de chollo en 1 vistazo por portal + combinaciÃ³n ganadora (rating buen precio + dÃ­as >60 + privado + VB â†’ CONTACTAR YA).

## [3.2.6] - 2026-08-18 â€” Tabla maestra de IDs para deduplicaciÃ³n

> **Motivo:** consolidar en `playbook_filtrado.md` Â§DEDUPLICACIÃ“N el ID estable de cada portal (antes disperso en cada "leer la tarjeta").

### ðŸ”— Dedup
- **Tabla maestra ID por portal**: mobile.de `a[data-testid$="-link"]` href `id=<ID>` Â· Coches.net `div[data-ad-id]` Â· kleinanzeigen `article[data-adid]` Â· AutoUncle `/es/d/<ID>-...` Â· Wallapop `/item/slug-<ID>` Â· Milanuncios `/marca-modelo-<ID>.htm`.
- Regla: **ID** = mismo anuncio 2x en un portal (dedup inmediato, dobles pasadas kW); **huella** (aÃ±o/kmÂ±2%/cv/precioÂ±3%/combustible) = mismo coche entre portales distintos (los IDs no coinciden). Foto + huella exacta = duplicado seguro (mismo concesionario).
- **AutoScout24 queda FUERA del estudio** (portal no prioritario para el usuario).

## [3.2.5] - 2026-08-18 â€” Contenedor de anuncios mobile.de ES (tarjeta real)

> **Origen:** el usuario facilitÃ³ el HTML del listado `div[data-testid="card-layout-wrapper"]` (`top-`/`base-`/`tic-result-listing-N`, con orden `sb=&od=` y paginaciÃ³n).

### ðŸ‡©ðŸ‡ª Leer tarjeta mobile.de
- **`paginas_reales.md`** â€” secciÃ³n "Contenedor de anuncios â€” selectores estables `data-testid`": **ID del href `detalles.html?id=<ID>` para deduplicar**, tÃ­tulo `span[data-testid="listing-title-card-view"]` + versiÃ³n `...__subTitle`, **precio `span[data-testid="price-label"]`** ("â‚¬Â¹"=bruto/"zzgl. MwSt."=neto), **bajada `span[data-testid="strike-through-price"]`**, **rating precio `div.PriceRatingBadge--label`** (Muy buen/Buen/Precio justo/Sin calificaciÃ³n â†’ chollo), atributos `[data-testid="listing-details-attributes"]`, vendedor `[data-testid="seller-info"]`, **sello OEM `[data-testid="oem-seal-listing"]`**, orden `[data-testid="sorting-menu-dropdown"]` (`sb=p&od=up` = precio bajo), paginaciÃ³n `[data-testid="pagination:next"/:previous"]`.
- **`playbook_filtrado.md`** â€” tabla "mobile.de â€” leer la tarjeta".
- Claves: **`data-testid` + prefijos `*-module__` estables, sufijos `__xxxxx` con hash â†’ NO usar**; tarjetas `top-*`/`tic-*` = patrocinadas, `base-*` = orgÃ¡nicas; ads `SRP_TABLECELL_*`/`SRP_INPAGE_VIDEO` y carrusel `SimilarTopListings` ("Otros vehÃ­culos de este concesionario") â†’ ignorar; rating precio = seÃ±al de chollo.

## [3.2.4] - 2026-08-18 â€” Contenedor de anuncios Coches.net (tarjeta real)

> **Origen:** el usuario facilitÃ³ el HTML del grid `section.mt-AdsList-content` (`div[data-ad-id]`, con paginaciÃ³n `pg=N`, skeletons lazy y ads intercalados).

### ðŸ‡ªðŸ‡¸ Leer tarjeta Coches.net
- **`paginas_reales.md`** â€” secciÃ³n "Contenedor de anuncios â€” selectores estables": `div[data-ad-id]` (ID para **deduplicar**), tÃ­tulo `h2[data-testid="card-ad-title"]`, **precio contado `p[data-testid="card-adPrice-price"]`** (SIEMPRE contado, NO financiado), **rating precio** `span.mt-CardAdPrice-cashLabel` ("Buen precio"=4/5Â·"Precio justo"=3/5 â†’ chollo), **bajada** `mt-CardAdPrice-priceDropPercentage`+`...OriginalPrice`, atributos `ul.mt-CardAd-attr li`, etiqueta DGT `...EnvironmentalLabel img` (b/eco/c/0), vendedor `span.sui-AtomBadge-text`, paginaciÃ³n `/search/?Section1Id=2500&pg=N`.
- **`playbook_filtrado.md`** â€” tabla "Coches.net â€” leer la tarjeta".
- Claves: `data-testid`/prefijos `mt-*`/`sui-*` estables (sin hash); **skeletons `div.sui-PerfDynamicRendering-placeholder` = sin cargar** â†’ scroll antes de leer; financiado (cuota `/mes*` + TAE) â†’ ignorar; ads `--tallAd`(`#ad-right-*`)Â·`--native--mobile`(`#ad-inline-*`)Â·`#ad-textads` â†’ ignorar; rating "Buen precio" ya valida el precio.

## [3.2.3] - 2026-08-18 â€” Contenedor de anuncios kleinanzeigen (tarjeta real)

> **Origen:** el usuario facilitÃ³ el HTML del listado `<ul id="srchrslt-adtable">` (`li[data-clickable="card"]` â†’ `article[data-adid]`, con paginaciÃ³n `seite:N` y ads intercalados).

### ðŸ‡©ðŸ‡ª Leer tarjeta kleinanzeigen
- **`paginas_reales.md`** â€” secciÃ³n "Contenedor de anuncios â€” selectores estables": **`article[data-adid]`** (ID para **deduplicar**), enlace `a[href^="/s-anzeige/"]`, precio `p.font-strong.text-secondary`, **precio anterior tachado `p.line-through` = bajada/chollo**, atributos `span[data-dhl-promotion]` (km + EZ), ubicaciÃ³n/fecha por iconos, badges **TOP/PRO** (pago, NO seÃ±al), paginaciÃ³n `/s-autos/seite:N/c216`, **JSON de cada tarjeta** (`resultAds`: `price`/`startingPrice`/`posterType`/`topAd`/`priorityAd`).
- **`playbook_filtrado.md`** â€” tabla "kleinanzeigen â€” leer la tarjeta".
- Claves: clases Tailwind estÃ¡ticas (sin hash) â†’ estables; ads intercalados `#srpb-top-banner` / `[data-liberty-position-name^="srpb-result-list-"]` / `#srpb-middle` â†’ ignorar; precio tachado = chollo; `posterType=PRIVATE` = particular sin IVA.

## [3.2.2] - 2026-08-18 â€” Contenedor de anuncios AutoUncle (tarjeta real)

> **Origen:** el usuario facilitÃ³ el HTML de la pÃ¡gina de resultados completa (~20 tarjetas `article._qzVn4`, carrusel patrocinado, paginaciÃ³n).

### ðŸ‡©ðŸ‡ª Leer tarjeta AutoUncle
- **`paginas_reales.md`** â€” secciÃ³n "Contenedor de anuncios â€” selectores estables": enlace `a[href^="/es/d/"]` (ID para **deduplicar**), **rating precio (1-5)** en `aria-label` del enlace + `data-rating` ("Buen precio"/"Super precio"), precio actual `._i2QOc` vs referencia tachado `._O_cMy`, **"Por debajo del mercado X â‚¬"** (`span._2OgvT`) = chollo, cambio de precio `[data-testid="listing-item--price-history"]`, **dÃ­as en venta** (rotaciÃ³n), atributos `ul._PuGQy > li._ZTpYr`, concesionario `[data-testid="source-label"]`, contador total `._oCbI6` (oferta total), paginaciÃ³n `?page=N&s[available_for_online_sales]=false`.
- **`playbook_filtrado.md`** â€” tabla "AutoUncle â€” leer la tarjeta".
- Claves: sufijos CSS Modules (`_qzVn4`, `_i2QOc`) NO estables â†’ usar `aria-label`/`data-testid`/`data-rating` (sin hash); carrusel "Ofertas seleccionadas" (`a._DF8g3`) = patrocinado â†’ NO contar; ads `#sr_N` â†’ ignorar; A8: AutoUncle SOLO rotaciÃ³n/validaciÃ³n, NUNCA fuente de precio del estudio (rating y "por debajo del mercado" = seÃ±al de chollo).

## [3.2.1] - 2026-08-18 â€” Contenedor de anuncios Wallapop (tarjeta real)

> **Origen:** el usuario facilitÃ³ el HTML del grid `.item-card-grid_ItemCardGrid` (tarjetas `a.item-card_ItemCard--horizontal`). Complementa los filtros de v3.1.8 con la **lectura de la tarjeta**.

### ðŸ‡ªðŸ‡¸ Leer tarjeta Wallapop
- **`paginas_reales.md`** â€” secciÃ³n "Contenedor de anuncios â€” selectores estables": precio `[aria-label="Item price"]` (sin hash, nunca falla), atributos en `label[class*=item-card_ItemCard__attributes]` (combustibleÂ·cambioÂ·cvÂ·aÃ±oÂ·km), tÃ­tulo/descripciÃ³n/vendedor por prefijo `item-card_*`, **ID del href para deduplicar**, rating en shadow DOM de `wallapop-rating-indicator`.
- **`playbook_filtrado.md`** â€” tabla "Wallapop â€” leer la tarjeta".
- Claves: sufijos CSS Modules cambian por build (usar prefijos + `aria-label`); landing SEO = Novedades, no bÃºsqueda activa; equipamiento solo en descripciÃ³n.

## [3.2.0] - 2026-08-18 â€” Contenedor de anuncios Milanuncios (tarjeta real)

> **Origen:** el usuario facilitÃ³ el HTML del listado `.ma-AdList`. Hasta ahora solo se documentaba el modal de filtros; falta la **lectura de la tarjeta** (precio contado, bajada, tags, dedup).

### ðŸ‡ªðŸ‡¸ Leer tarjeta Milanuncios
- **`paginas_reales.md`** â€” secciÃ³n "Contenedor de anuncios â€” selectores estables": `.ma-AdCardV2` (`data-testid="AD_CARD"`), precio contado vs financiado (usar **contado**, IVA incl.), bajada de precio `.ma-AdPrice-iteration*` (chollo/negociable), tags `.ma-AdTag-label`, ID del href para **deduplicar**, skeletons/carrusel/ads a ignorar.
- **`playbook_filtrado.md`** â€” tabla "Milanuncios â€” leer la tarjeta".
- Claves: patrocinado "Destacado" NO cuenta como seÃ±al Â· tarjeta sin `href`/sin tiempo = `man` Â· carrusel = recomendaciones.

## [3.1.9] - 2026-08-18 â€” Selectores estables del modal de filtros Milanuncios

> **Origen:** el usuario facilitÃ³ el HTML del modal `.sui-MoleculeModal` (`ma-FormFiltersPopoverModal`). Campos desplegables = 3 pasos (clic campo â†’ opciÃ³n â†’ "Aplicar filtro").

### ðŸ‡ªðŸ‡¸ Selectores Milanuncios
- **`paginas_reales.md`** â€” secciÃ³n "Modal de filtros â€” selectores estables": categorÃ­a/marca/ubicaciÃ³n por `data-testid`, rangos directos `#price-from`/`#price-to`Â·`#kms-from`Â·`#year-from`Â·`#potencia-from`, switches `#isPriceDropped`Â·`#hasWarranty`Â·`#isCertified`, radios por `aria-label`, etiqueta DGT (`#0`/`#ECO`/`#C`/`#B`/`#NO_LABEL`), combustible, plazas (`#FOUR_SEATS`...), color, tipo anuncio.
- **`playbook_filtrado.md`** â€” tabla "Selectores ESTABLES del modal de filtros Milanuncios".
- âš ï¸ **Milanuncios NO tiene filtro de equipamiento** â†’ mÃ¡ximo equipamiento por `keywords=` o validando fichas. Negociable (chollos ES).

## [3.1.8] - 2026-08-18 â€” Selectores estables de filtros Wallapop

> **Origen:** el usuario facilitÃ³ el HTML del sidebar de Wallapop (`/coches-segunda-mano`). Usa **web components** `walla-*` con `name`/`id` estables.

### ðŸ‡ªðŸ‡¸ Selectores Wallapop
- **`paginas_reales.md`** â€” secciÃ³n "Sidebar de filtros â€” sliders + radios/checkboxes estables": rangos con `<wallapop-range-selector>` (`#fromSelector`/`#toSelector`), fecha (`time_filter-radio-group-single-selection`), marca/modelo (radio por `id`), etiqueta DGT (`zero`/`eco`/`c`/`b`), carrocerÃ­a, combustible, cambio, vendedor (`seller_type-radio-group-single-selection`).
- **`playbook_filtrado.md`** â€” tabla "Selectores ESTABLES de filtros en Wallapop".
- âš ï¸ **Wallapop NO tiene filtro de equipamiento** â†’ mÃ¡ximo equipamiento por `keywords=` o validando fichas.

## [3.1.7] - 2026-08-18 â€” Selectores estables de filtros AutoUncle (`name`)

> **Origen:** el usuario facilitÃ³ el HTML del sidebar de AutoUncle. Los `id` llevan hash (cambian), pero los **`name` son estables** â†’ `[name="..."]`. AutoUncle sigue siendo solo rotaciÃ³n/validaciÃ³n (A8: nunca precio de referencia), pero aporta filtro de **equipamiento** (`popularOptions`).

### ðŸ‡©ðŸ‡ª Selectores AutoUncle
- **`paginas_reales.md`** â€” secciÃ³n "Sidebar de filtros â€” selects y checkboxes con `name` ESTABLE": `minPrice`/`maxPrice`, `minYear`/`maxYear`, `fuelTypes` (El/El_Hybrid/Benzin/Diesel), `minKm`/`maxKm`, `gear`, y **`popularOptions`** (hasAppleCarPlay, hasAndroidAuto, hasBluetooth, hasSeatHeat, hasParkingCamera, hasParking, hasDistanceControl, hasTowBar).
- **`playbook_filtrado.md`** â€” tabla "Selectores ESTABLES de filtros en AutoUncle" + regla de mÃ¡ximo equipamiento (CarPlay + Android Auto + asientos + cÃ¡mara + ACC).

## [3.1.6] - 2026-08-18 â€” Selectores estables de filtros kleinanzeigen.de

> **Origen:** el usuario facilitÃ³ el HTML real del sidebar de kleinanzeigen (`/s-autos/c216`). Tiene **3 tipos de filtro** con selectores distintos.

### ðŸ‡©ðŸ‡ª Selectores kleinanzeigen
- **`paginas_reales.md`** â€” secciÃ³n kleinanzeigen reescrita: Tipo 1 (atributos por URL `+autos.<attr>_s:<valor>`), Tipo 2 (rangos con `id` `brwse-attr-*`), Tipo 3 (equipamiento con `checkbox-autos.*` + botÃ³n Ãœbernehmen `[data-cy=clickable-options-apply-button]`).
- **`playbook_filtrado.md`** â€” tabla "Selectores ESTABLES de filtros en kleinanzeigen.de" + regla de mÃ¡ximo equipamiento (sunroof+seat_heating+xenon_led+navi+full_service_history â†’ Ãœbernehmen).
- âš ï¸ Diferencia clave: kleinanzeigen SÃ tiene botÃ³n aplicar para equipamiento (Coches.net no).

## [3.1.5] - 2026-08-18 â€” Selectores estables de filtros Coches.net (acordeÃ³n)

> **Origen:** el usuario facilitÃ³ el HTML del sidebar de filtros de Coches.net. A diferencia de mobile.de, **NO hay pÃ¡gina de filtros aparte**: el sidebar (`.mt-SearchSidebar-filters`) son acordeones en el listado con **`id` estable** por grupo, y los filtros **se aplican al marcar** (en vivo, sin botÃ³n).

### ðŸ‡ªðŸ‡¸ Selectores Coches.net
- **`playbook_filtrado.md`** â€” nueva tabla "Selectores ESTABLES de filtros en Coches.net": `vehicleTypeGroup`, `makeGroup`, `priceGroup`, `onlineServicesGroup`, `locationGroup`, `sellerGroup`, `yearGroup`, `kmsGroup`, `bodyTypeGroup`, `motorGroup`, `environmentalLabelGroup`, `electricGroup`, `equipmentGroup` (equipamiento), `colorGroup`.
- **`paginas_reales.md`** â€” secciÃ³n "Sidebar de filtros â€” acordeÃ³n con IDs ESTABLES" con el mÃ©todo (clic `groupTrigger` â†’ marcar â†’ se aplica solo) y el proxy de mÃ¡ximo equipamiento (techo solar + cÃ¡mara, SIN cuadro digital).

## [3.1.4] - 2026-08-18 â€” Selectores estables de filtros mobile.de ES (data-testid)

> **Origen:** el usuario facilitÃ³ el HTML real de la pÃ¡gina de filtros de mobile.de ES (`/es/s/auto`). Los `data-testid` son selectores ESTABLES (no cambian con el CSS ofuscado) â†’ Claude puede filtrar con `page.getByTestId()` / `[data-testid=...]` sin fallar.

### ðŸ”¬ Selectores estables
- **`playbook_filtrado.md`** â€” nueva tabla "Selectores ESTABLES de filtros en mobile.de ES": `make-incl-0`, `model-incl-0`, `model-description-incl-0`, `category-filter`, `seats-filter`, `door-filter`, `price-filter`, `first_registration-filter`, `mileage-filter`, `condition-filter`, `maintenance_features-filter`, `seller_type-filter`, `general_inspection-filter`, `previous_owners-filter`, `country-filter`, `location-filter`, `radius-filter`.
- **Filtros de EQUIPAMIENTO (mÃ¡ximo equipamiento):** en Interior â†’ `Panel de instrumentos digital` (cuadro digital), `Pantalla Head-up`, `CalefacciÃ³n de asiento`, Android Auto/CarPlay, navegaciÃ³n; en Extras â†’ techo corredizo/panorÃ¡mico, faros LED, portÃ³n elÃ©ctrico.
- **Regla full reproducible:** marcar los 5 checkboxes (cuadro digital + HUD + calefacciÃ³n asiento + techo panorÃ¡mico + faros LED) para comparar full vs full.
- **`paginas_reales.md`** â€” secciÃ³n nueva "mobile.de ES â€” pÃ¡gina de filtros completa" con la estructura de las 6 secciones y los data-testid.

## [3.1.3] - 2026-08-18 â€” Equipamiento mÃ¡ximo + correcciÃ³n Seat/Cupra

> **Origen:** feedback del usuario sobre el estudio de mercado: (1) comparar SIEMPRE a mÃ¡ximo equipamiento (la unidad DE suele venir full: cuadro digital, techo, LED â€” lo mÃ¡s demandado entre jÃ³venes), y (2) la conclusiÃ³n "Seat/Cupra â†’ EspaÃ±a mÃ¡s barata, nunca importar" era falsa (la banda â‰¥20k en ambos mercados recortÃ³ la cola barata DE; Cupra suelo ES 19.500 â‚¬ vs DE 15-16k).

### ðŸ› ï¸ Equipamiento mÃ¡ximo por defecto
- **`SKILL.md`** â€” referencia rÃ¡pida: "comparar a MÃXIMO equipamiento por defecto; un ES mÃ¡s barato sin ese equipamiento NO es comparable â†’ ajustar con primas".
- **`03-informes/comparables.md`** â€” prima nueva de **cuadro digital** (+500-1.000 â‚¬, la mÃ¡s demandada) + regla de equipamiento mÃ¡ximo antes de dar un hueco.
- **`../estudio-mercado/SKILL.md`** â€” secciÃ³n "EQUIPAMIENTO" con el mÃ©todo por portal (mobile.de `Ausstattung` sÃ­ filtra cuadro digital; Coches.net solo techo solar â†’ proxy + 1-2 fichas de muestra).

### ðŸš« CorrecciÃ³n regla Seat/Cupra
- **`../estudio-mercado/SKILL.md`** â€” secciÃ³n "REGLA SEAT/CUPRA â€” CORREGIDA": la nacionalidad de la marca NO es criterio de arbitraje; medir precio_desde SIN banda en ambos mercados.
- **`memoria/trampas-encontradas.md`** â€” nueva trampa "banda en ambos mercados aplasta el hueco".
- **`memoria/mejoras-aplicadas.md`** â€” Mejora #16.
- **`datos_mercado.json`** (Desktop) â€” Cupra LeÃ³n corregido: suelos reales, `pendiente_fase2=true`, nota de re-mediciÃ³n.
- **Volcado `modelos_medidos_2026-08-17_v2.md`** â€” regla 3 re-corregida.

### ðŸ§¹ Limpieza
- Eliminado `estudio-mercado.skill.zip` + `.bak` anidados en la carpeta fuente de la skill (se auto-incluÃ­an al re-empaquetar).

## [3.1.2] - 2026-08-17 â€” Regla A21 (enlaces SIEMPRE) + FASE 0 ENTENDER reforzada

> **Origen:** insistencia del usuario en dos frentes: (1) "todo lo que haga debe venir con link a anuncios y fuentes" (la regla que mÃ¡s repite) y (2) mejorar la comprensiÃ³n de la peticiÃ³n antes de buscar (FASE 0 ENTENDER).

### ðŸ”´ Regla A21 nueva (anti-patrÃ³n #21)
- **A21 â€” Entregar sin enlaces (anuncio + fuentes):** TODO lo que se entregue lleva el enlace directo al anuncio (ficha del vehÃ­culo) y las fuentes con su URL. Candidatos, comparables, comparativas, informes, dossier, JSON y ZIP. Un dato sin su enlace NO se entrega como concluido.
- **Al cerrar cualquier entrega, re-verificar con lupa:** si cualquier candidato/comparable/fuente carece de enlace, el trabajo estÃ¡ incompleto.

### ðŸ§  FASE 0 Â· ENTENDER reforzada (comprensiÃ³n antes de ejecutar)
- **ðŸ“¥ ACK de 1 lÃ­nea obligatorio en TODO encargo** (incluso sin ambigÃ¼edad): `ENTENDIDO â€” [QUÃ‰] Â· [PARA QUÃ‰] Â· [ENTREGABLE] Â· [FLUJO]`. El usuario corrige en 1 palabra o da OK. Ver `01-arranque/guia_prompts.md` Â§ACK.
- **Ãrbol de decisiÃ³n de comprensiÃ³n**: QUÃ‰ â†’ PARA QUÃ‰ â†’ ENTREGABLE â†’ ALCANCE. Solo se pregunta lo que falta; si estÃ¡ claro, cero preguntas.
- **Tabla de colisiones de intenciÃ³n**: pares que se mezclan (bÃºsqueda vs marketing, evaluar vs buscar, un modelo vs varios...) y cÃ³mo separarlos en fases.
- Integrado en `01-arranque/planificador.md` PASO 0, `01-arranque/guia_prompts.md` y lÃ­nea de flujo del `SKILL.md`.

### ðŸ“„ Archivos tocados
- `SKILL.md` (frontmatter 3.1.2, referencia rÃ¡pida, Â§ANTI-PATRONES resumen + nota A21, Â§FOTOS REALES punto 5, flujo de arranque, checklist Ã—2) Â· `06-reglas/anti_patrones.md` (tabla, detalle, verificaciÃ³n rÃ¡pida, origen) Â· `01-arranque/guia_prompts.md` (ACK, Ã¡rbol, colisiones) Â· `01-arranque/planificador.md` (PASO 0) Â· `CHANGELOG.md`.

## [3.1.1] - 2026-08-17 â€” Flujo E STOCK + anti-patrones A17-A20 + auditorÃ­a ampliada

> **Origen:** primer encargo real de prueba (stock recurrente de publicaciones). RevelÃ³ que la skill fallaba en casos fuera de los 4 flujos: entregÃ³ .docx, usÃ³ AutoScout24 para precio (violando su propia trampa A8), abriÃ³ fichas en exceso, ignorÃ³ la cache y no disparÃ³ la auditorÃ­a de cierre.

### ðŸ†• Flujo E Â· STOCK (bÃºsqueda, NO marketing)
- Nuevo flujo para "stock recurrente / catÃ¡logo bajo pedido / busca coches por categorÃ­as".
- `02-flujos/stock-marketing.md` (NUEVO): reglas duras + 3 categorÃ­as + plantilla de ficha de BÃšSQUEDA + proceso con checkpoint cada X.
- **SeparaciÃ³n crÃ­tica**: bÃºsqueda de coches = informe de bÃºsqueda (Markdown + PDF de marca + JSON). Marketing/anuncios = flujo posterior SEPARADO, solo si se pide explÃ­citamente. NUNCA .docx ni copy IG/FB en el flujo de bÃºsqueda.
- Ejemplos del briefing = ilustrativos (A19), explorar todo el segmento.

### ðŸ›¡ï¸ Anti-patrones nuevos
- **A17 Listado-first**: en comparativas/C/E, trabajar con listados; abrir ficha solo 2-3 ejemplos. Aprovechar sellos de precio del listado.
- **A18 Equipamiento no inventado**: solo publicar equipamiento verificado; marcar lo dudoso "por confirmar".
- **A19 Ejemplos = ilustrativos**: no acotarse a los ejemplos del usuario, explorar todo el segmento.
- **A20 No mezclar bÃºsqueda con marketing**: copy IG/FB solo si se pide explÃ­citamente despuÃ©s.

### ðŸ”§ Fixes de la prueba
- **AuditorÃ­a de cierre ampliada**: trigger a "cualquier output final" (candidato, informe stock, informe mercado, aborto) â€” antes solo "elegir candidato Ãºnico".
- **PASO 0 cache reforzado**: aplica a TODOS los flujos, incluido E.
- **Waypoint ðŸ“ obligatorio en cada mensaje**, sin excepciÃ³n.
- **Entregable Flujo E = informe de bÃºsqueda** (no .docx): Markdown + PDF de investigaciÃ³n con plantilla de marca (`assets/plantilla_pdf_marca.html`) + JSON.
- **Regla dura universal**: todo encargo â†’ UN flujo â†’ su camino. Si no encaja en A/B/C/D/E â†’ PREGUNTAR, nunca improvisar.
- Tabla de flujos 4 â†’ 5, detecciÃ³n automÃ¡tica con Flujo E primero, mapa de caminos con Flujo E, conteo anti-patrones 16 â†’ 20.

### ðŸ“„ Archivos tocados
- `SKILL.md` (tabla flujos, detecciÃ³n, PASO 0, Protocolo de Mando, EL CAMINO, auditorÃ­a de cierre, anti-patrones) Â· `02-flujos/stock-marketing.md` (NUEVO) Â· `06-reglas/anti_patrones.md` (A17, A18, A19, A20) Â· `CHANGELOG.md`.

## [3.1.0] - 2026-08-16 â€” ReorganizaciÃ³n fÃ­sica, Protocolo de Mando, PASO 0 cache, planificador

### ðŸ—‚ï¸ ReorganizaciÃ³n fÃ­sica de la carpeta (FASE 0)
- Carpetas numeradas por orden de lectura: `01-arranque/` Â· `02-flujos/` Â· `03-informes/` Â· `04-negocio/` Â· `05-operaciones/` Â· `06-reglas/` Â· `memoria/` Â· `references/` Â· `scripts/` Â· `assets/`.
- RaÃ­z limpia: solo `SKILL.md` + `CHANGELOG.md` + `ROADMAP.md`. El Ã­ndice de memoria pasÃ³ de la raÃ­z a `memoria/MEMORIA.md`.
- **Todas las referencias cruzadas** entre MD actualizadas a las rutas nuevas (grep validado; intrafolder siguen sin ruta).

### ðŸ§  Memoria (FASE A)
- **`memoria/encargos.md` (NUEVO)**: registro central de encargos (cliente â†’ modalidad M1/M2/M3 â†’ flujo â†’ entregables â†’ resultado â†’ refrescar antes de). Backfill 9 encargos.
- **`memoria/filtros-portales.md` (NUEVO)**: filtros/URLs verificados por portal (quÃ© aplica por URL vs clic, doble pasada kW).
- **`memoria/modelos-medidos.md`**: plantilla ampliada a 12 campos (+fuentes cubiertas, +peticiones, +refrescar antes de) + entradas incompletas completadas.
- **`memoria/MEMORIA.md`**: Ã­ndice de 8 archivos (antes 5), mÃ©tricas v3.1.0, protocolo con `indice.json` como paso 3bis.

### ðŸ“¦ ReestructuraciÃ³n SKILL.md (FASE B) â€” 1.251 â†’ ~987 lÃ­neas
- **`01-arranque/planificador.md` (NUEVO)**: fusiona Asistente de planificaciÃ³n + Plan de barrido + Prompt Improver + Asesor de filtros (elimina ~190 lÃ­neas duplicadas de SKILL.md).
- Secciones movidas a compaÃ±eros: Mapa de PDFs + Rutas de guardado â†’ `05-operaciones/operaciones.md` Â· PriorizaciÃ³n ROI + DeduplicaciÃ³n â†’ `02-flujos/playbook_filtrado.md` Â· Vendibilidad + Matriz â†’ `03-informes/comparables.md`.

### ðŸ”‘ Protocolo de Mando (FASE D) â€” filosofÃ­a del usuario
- **Sustituye a "Modo AutomÃ¡tico" y a "Micro-plan con OK"**: el usuario aprueba CADA fase (plan de fase de 3-5 lÃ­neas); dentro de la fase la IA ejecuta TODO automÃ¡ticamente; pausa solo por emergencia (presupuesto 80%, fuente bloqueada tras reintentos, hallazgo crÃ­tico, desviaciÃ³n A14).
- De 12+ interrupciones por encargo a **4-6** (plan de fase + checkpoints de decisiÃ³n CP-D/CP1/CP3).

### ðŸ—‚ï¸ PASO 0 â€” CHECK DE CACHE (FASE D)
- Antes de navegar se lee `memoria/encargos.md` + `memoria/modelos-medidos.md` + `indice.json` (Desktop). Si hay informe <3 semanas â†’ mostrar resumen + preguntar Â¿delta / refrescar / nuevo?. NO re-buscar lo ya hecho.

### ðŸ”§ Fixes de contradicciones (FASE C)
- CP2 unificado ("tras comparables, antes de veredicto"). Â· JSON Flujo C con nombre Ãºnico `scouting_<fecha>.json`. Â· Umbral tramo 8-14k mÃ­nimo 10% â‰  objetivo 12%. Â· Resumen anti-patrones completo hasta A16 (faltaban A15/A16). Â· Frontmatter con `version: 3.1.0`.

### ðŸ§ª AuditorÃ­a de consistencia + mejoras adicionales (16-ago, segunda pasada)
- **38 referencias rotas corregidas** (rutas `../` mal formadas tras la reorganizaciÃ³n) en `01-arranque/*`, `05-operaciones/*`, `memoria/*`, `SKILL.md` y `CHANGELOG.md`.
- **`scripts/verify_skill_refs.py` (NUEVO)**: valida que todas las referencias cruzadas entre `.md` apunten a archivos existentes (0 rotas tras las correcciones).
- **`scripts/sync_indice.py` (NUEVO)**: regenera `indice.json` del Desktop desde `memoria/encargos.md` (mantiene el PASO 0 cache en sync).
- **Trigger de `memoria/vendedores-confianza.md`**: al cerrar negociaciÃ³n, volcar resultado del vendedor (respuesta, fiabilidad, venta/ghosting). SecciÃ³n nueva en `05-operaciones/operaciones_cierre.md`.
- **Caducidad en `memoria/encargos.md`**: regla "refrescar antes de" vencida â†’ marcar re-investigar en PASO 0.
- **Eliminada duplicaciÃ³n del mapa de caminos** (estaba en EL CAMINO y repetido en PROTOCOLO DE MANDO).

### ðŸ“„ Archivos tocados
- `SKILL.md` (reestructuraciÃ³n + Protocolo de Mando + PASO 0 + fixes) Â· `01-arranque/planificador.md` (NUEVO) Â· `memoria/encargos.md` (NUEVO) Â· `memoria/filtros-portales.md` (NUEVO) Â· `memoria/modelos-medidos.md` Â· `memoria/MEMORIA.md` Â· `05-operaciones/operaciones.md` Â· `02-flujos/playbook_filtrado.md` Â· `03-informes/comparables.md` Â· `scripts/verify_skill_refs.py` (NUEVO) Â· `scripts/sync_indice.py` (NUEVO) Â· `05-operaciones/operaciones_cierre.md` Â· `01-arranque/briefing_encargo.md` Â· `01-arranque/guia_prompts.md` Â· `memoria/retrospectiva.md` Â· `memoria/mejoras-aplicadas.md` Â· `CHANGELOG.md`.

## [3.0.0] - 2026-08-16 â€” Asistente de planificaciÃ³n + AuditorÃ­a de cierre de conversaciÃ³n

### ðŸ’¡ ASISTENTE DE PLANIFICACIÃ“N â€” encargos abiertos/vagos
- Protocolo de 4 pasos ANTES de cualquier bÃºsqueda: **DETECTAR** flujo â†’ **MEJORAR** prompt (solo si vago) â†’ **PLANIFICAR** embudo (plan de barrido + presupuesto de tokens) â†’ **EJECUTAR** en cascada con checkpoints.
- Prompt Improver integrado en el PASO 2 (referencia a `01-arranque/guia_prompts.md`); plan de barrido integrado en el PASO 3.
- Tabla "quÃ© herramienta cuÃ¡ndo" (Prompt Improver / briefing / plan de barrido / Flujo D / Flujo A) con ahorro por herramienta.
- Embudo visualizado por niveles con coste: sondeo (8) â†’ Flujo B (15-50) â†’ Flujo A (35-70) â†’ ~58% de ahorro teÃ³rico vs sin embudo.
- 6 reglas duras: detectar siempre antes de buscar, mejorar solo si vago, checkpoints explÃ­citos, cascada no todo de golpe, opciÃ³n "busca tÃº" siempre, plan documentado en cuaderno de sesiÃ³n.

### ðŸ AUDITORÃA DE CIERRE â€” al elegir candidato Ãºnico (nueva)
- **Trigger:** usuario elige UN candidato (fin de Flujo A + ZIP) o dice "este es"/"cerramos"; tambiÃ©n en aborto sin elecciÃ³n.
- **5 dimensiones:** eficiencia (real vs plan) Â· embudo (niveles, descartes, Fase 2 mal gastada) Â· correcciones del usuario (causa raÃ­z) Â· checkpoints respetados/saltados Â· resultado final.
- **3 salidas obligatorias:** entrada en `memoria/retrospectiva.md` (plantilla de cierre nueva) Â· modelo elegido en `memoria/modelos-medidos.md` Â· â‰¥1 trampa/anti-patrÃ³n por fallo detectado.
- Objetivo: verificar si el embudo ahorra de verdad (el 58% teÃ³rico pasa a medirse por encargo).

### ðŸ“„ Archivos tocados
- `SKILL.md`: Â§Asistente de planificaciÃ³n (16-ago) + Â§AuditorÃ­a de cierre tras Â§Aprendizaje continuo + lÃ­nea en referencia rÃ¡pida + item en checklist final.
- `memoria/retrospectiva.md`: plantilla de CIERRE (5 dimensiones) aÃ±adida.

## [2.9.3] - 2026-08-15 â€” Sondeo D1 blindado (navegaciÃ³n real, filtros no modelos)

### ðŸ”´ BÃºsqueda web PROHIBIDA como sondeo (A15)
- El sondeo D1 del Flujo D se hace SIEMPRE con navegaciÃ³n real a Coches.net + mobile.de con los filtros del encargo. La bÃºsqueda web (snippets/agregadores) queda **prohibida como mÃ©todo de sondeo** â€” da cifras inconsistentes y contradice lo verificado con navegaciÃ³n real.
- Caso real 15-ago (conversaciÃ³n nueva): Focus ES "~9.900 â‚¬" cuando la navegaciÃ³n real daba 3.000-6.990 â‚¬; 308 DE "10.980-12.600 â‚¬" sin confirmar. Degradado solo con portal bloqueado + reintentos (A2/A7).

### ðŸŽ¯ Sondeo por FILTROS, no por modelo (A16)
- Una pasada con los filtros del encargo devuelve **TODOS** los modelos/motorizaciones que caben. Prohibido elegir 3-4 a mano y dejar "otros por explorar" sin sondear.
- **Potencia = filtro MÃNIMO (â‰¥Xcv):** versiones 125/130/150 valen igual; nunca sondear solo la variante tope (caso LeÃ³n 150cv descartando el 125cv).

### âš¡ Eficiencia: D1 enumera, NO pagina (D1a + D1b)
- **D1a Â· ENUMERAR modelos** (solo nombres) con 2 lecturas por mercado: **asc** (suelo, pÃ¡gina 1 â†’ ðŸŸ¢) + **desc** (techo, pÃ¡gina 1 â†’ ðŸŸ¡). Con asc+desc se cubre TODO el rango en 2 pÃ¡ginas.
- Complemento sin paginar: **facetas de marca/modelo con conteo** (enumera el mercado completo) + **semilla de modelos** `memoria/modelos-medidos.md` (segmento finito conocido, no redescubrir).
- **D1b Â· PRECIO-DESDE diferido**: el precio exacto por modelo no hace falta en la primera pasada; 1 consulta por modelo solo si D1a lo dejÃ³ sin precio claro.
- El anuncio individual solo se investiga cuando el embudo es pequeÃ±o (Flujo A/B). Matiz en A12 para no confundir "cubrir todo el rango" (candidatos) con "pagar cada pÃ¡gina del sondeo" (D1).

### ðŸ“… Rango de aÃ±o aprobado se respeta (A13 extendido)
- A13 cubre ahora tambiÃ©n usar un rango MÃS RESTRICTIVO que el aprobado (se aprobÃ³ 2012+ y se filtrÃ³ 2016+). Declarar cualquier cambio ANTES de navegar, en ambos sentidos.

### ðŸ“„ Archivos tocados
- `SKILL.md`: D1 reescrito (navegaciÃ³n real, filtros, cobertura total, potencia mÃ­nima, rango aprobado) + reglas duras 4-6 del Flujo D.
- `06-reglas/anti_patrones.md`: A15, A16 nuevos; A13 extendido; checklist y tabla de origen actualizados.

## [2.9.2] - 2026-08-15 â€” Contrato de datos completo para Laravel (descripciÃ³n original, equipamiento, campos del anuncio)

### ðŸ”´ DescripciÃ³n original literal (punto 8 de la auditorÃ­a)
- `anuncio.descripcion_original` = **texto literal COMPLETO del anuncio** (pegado tal cual, sin resumir ni corregir) + `descripcion_traducida` completa. Regla dura en `03-informes/contrato.md`, `SKILL.md` (reglas del ZIP) y checklist. Antes la skill no forzaba el original â†’ riesgo de que el ZIP llegara solo con traducciÃ³n/resumen.

### ðŸŽ›ï¸ Equipamiento COMPLETO del anuncio
- `vehiculo.equipamiento` = **lista COMPLETA** de la secciÃ³n `Ausstattung`/features, no solo los 15 destacados del `03-informes/informe_tecnico.md` (esa lista es solo para el informe humano).
- Motivo: Laravel lo muestra en la ficha y lo usa para el ajuste de comparable y la ficha publicitaria. Un coche mal equipado en el JSON sale mÃ¡s pobre de lo que es.

### ðŸ“° Campos extra del anuncio (nuevos en contrato.md)
- `anuncio.dias_publicado` (seÃ±al de demanda: muchos dÃ­as = baja rotaciÃ³n DE), `anuncio.tuv_vigente_hasta` (TÃœV/HU, clave en importaciÃ³n), `anuncio.precio_publicado` vs `precio_negociado` (pista de negociaciÃ³n), `vehiculo.carroceria`, `vehiculo.color_interior`.
- Laravel los persiste en `Car.notes` (sin migraciÃ³n) â€” ver `ValuationImporter::buildNotes()` (6Âº parÃ¡metro `$a`).

### ðŸ”— Comparables con URL directa (punto 6)
- Reforzado en checklist del ZIP: `mercado.comparables[].url` = ficha del anuncio, nunca bÃºsqueda/filtro. Sin URL, el importer descarta la fila.

### ðŸ–¥ï¸ Laravel (cierre del hueco de UI)
- `Show.vue`: nuevo bloque **Equipamiento** en la pestaÃ±a Resumen (chips) â€” el importer ya persistÃ­a `equipment` pero la UI no lo mostraba.
- i18n: clave `cars.equipment` aÃ±adida en es/en (paridad 1272/1272, 0 missing).

## [2.9.0] - 2026-08-15 â€” Camino fijo, micro-plans, cuaderno de sesiÃ³n y auditorÃ­a de fase

### ðŸ§­ EL CAMINO (SKILL.md) â€” desambiguaciÃ³n total de fases
- Mapa numerado de pasos por flujo (D/B/A) + **protocolo de waypoint**: cada mensaje declara `ðŸ“ Camino: Flujo X Â· paso N`.
- **Protocolo de desviaciÃ³n:** preguntas laterales = misiones laterales; se responden y se RETOMA el paso (`â†©ï¸ Vuelvo al paso N`). Cambio de destino se declara (`ðŸ”€ Cambio de camino`).
- Anti-patrÃ³n **A14**: abandonar el camino en silencio.

### ðŸ“‹ Micro-plan antes de CADA bÃºsqueda
- No solo el plan inicial: cada ronda de navegaciÃ³n lleva micro-plan de 3-5 lÃ­neas + OK del usuario. Lotes coherentes agrupados; cambio de objetivo/filtros â†’ nuevo micro-plan. Preguntar mucho estÃ¡ BIEN.

### ðŸ““ Cuaderno de sesiÃ³n â€” aprendizaje en vivo
- `informes\_sesion\sesion_<fecha>_<encargo>.md`: parÃ¡metros fijados, correcciones del usuario con hora (se aplican YA), preferencias detectadas, pendiente al cierre.
- Se relee antes de cada micro-plan. Al cierre se vuelca a `memoria/` del skill.

### ðŸ§ AuditorÃ­a de fase
- Checklist interno de 4 lÃ­neas al completar CADA paso: entregable guardado Â· camino correcto Â· correcciones aplicadas Â· cobertura real declarada. Si falla algo, se corrige antes de avanzar.

## [2.9.1] - 2026-08-15 â€” AuditorÃ­a de integraciÃ³n con Laravel (contrato â†” cÃ³digo)

### ðŸ“¡ Contrato alineado con el importador real (contrato.md)
- **Fotos**: la ubicaciÃ³n canÃ³nica es `vehiculo.fotos` (el importador las lee de ahÃ­). `anuncio.fotos` queda como retrocompatible.
- **Comando real**: `php artisan importnex:import-valuation` (no `jj:importar`). Modelo real: `Car` (no `Valoracion`).
- **RATING_MAP real**: `favorableâ†’favorable`, `desfavorableâ†’unfavorable` (no good/bad).
- **Endpoints**: el API acepta JSON (`/api/import-valuation` A, `/api/import-modelo` B, `/api/import-mercado` C); el ZIP con fotos va por la ruta web `POST /cars/import-valuation`.
- **briefing-pdf**: implementado (sube PDF real), no devuelve 410.
- **`semaforo` es informativo**: `CarObserver::saving()` recalcula `traffic_light` desde costes; el valor del chat no se persiste.

### ðŸ›¡ï¸ Mejoras de robustez en Laravel (ValuationImporter)
- `validate()` exige `vehiculo.marca` + `modelo` (evita error SQL 500 â†’ 422 limpio) y `costes.pvp_nuevo` en Flujo A (evita IEDMT = 0 silencioso).
- Persistencia nueva: `anuncio.pais_origen` â†’ `Car.pais_origen`; `vehiculo.co2_confirmado` â†’ `Car.co2_confirmado` (migraciÃ³n `add_pais_origen_and_co2_confirmado_to_cars`).
- VerificaciÃ³n de IEDMT: si el importe de Claude difiere >10% del recÃ¡lculo de Laravel, se aÃ±ade aviso a `notes`.
- Fotos: fallback `anuncio.fotos` si no vienen en `vehiculo.fotos`.
- `/api/cierres` idempotente: mismo coche + misma fecha no duplica (retry/doble clic actualiza el registro).

### ðŸ§ª Tests
- +8 tests nuevos (validaciones, mapeos, IEDMT, idempotencia cierre). Ãrea importaciÃ³n/cierres/KPIs: 85 passed.

## [2.9.9] - 2026-08-15 â€” Plantillas Blade de Laravel a nivel premium + mapa de PDFs

### ðŸŽ¨ Plantillas Blade (Laravel) â€” nivel premium
- **`informe-interno.blade.php`**: KPI cards (score global, recomendaciÃ³n, mediana ES, cobertura), coverage grid de fuentes con estados (OK/degradada/omitida), tarjeta de candidato, margen vs mercado, barras de score por dimensiones (SCORE_DIM) y vendibilidad, predicciÃ³n de venta (4 escenarios con fila RECOMENDADA), riesgos + banderas rojas/amarillas, acciones numeradas con plazo, tabla de comparables con badges DE/ES + fila ELEGIDO, verdict-card final. `h2` con barra accent.
- **`ficha-coche.blade.php`**: KPI cards (precio, ahorro, KM, aÃ±o derivados de SPEC/PRECIO/AHORRO), badge de origen DE/ES desde `cars.pais_origen`, `h2` con barra accent.
- **`folleto.blade.php`**: cabecera de documentaciÃ³n (tipo/origen).
- **Controladores**: docblocks con el mapa de "quÃ© PDF genera quÃ© y de quÃ© esqueleto" en `PaqueteValoracionController` y `JJImportFolletoController`.

### ðŸ“š Mapa de PDFs documentado (7 PDFs, tipos y dÃ³nde se crean)
- **`SKILL.md`** Â§MAPA DE PDFs, **`03-informes/contrato.md`** Â§Mapa de PDFs, **`docs/MAPA_PDFS.md`** (repo) e **`INSTRUCCIONES_PROYECTO.md`** (Desktop): tabla de 7 PDFs (3 Claude investigaciÃ³n + 4 Laravel venta) con origen y ruta de creaciÃ³n.
- **Tests**: `tests/Feature/PlantillasValoracionRenderTest.php` (render de informe-interno y ficha-coche con secciones premium).
- Nota: briefing PDF ya eliminado (v2.9.8); status cliente 'Briefing' y `01-arranque/briefing_encargo.md` no son el PDF.

## [2.9.8] - 2026-08-15 â€” Briefing PDF eliminado del ecosistema

### ðŸ—‘ï¸ EliminaciÃ³n (decisiÃ³n del usuario)
- **Laravel:** eliminadas la vista `jj-import/briefing.blade.php`, las rutas `/cars/{car}/marketing/briefing` y `/api/cars/{car}/briefing-pdf`, los mÃ©todos `CarMarketingController::briefing()` y `ImportValuationApiController::attachBriefing()`, la entrada 'briefing' del listado de PDFs (`CarController::laravelPdfs`) y `briefing_pdf` (`CarDocumentDefinitions`). Borrado `BriefingPdfApiTest`.
- **Nota:** el status de cliente 'Briefing' (pipeline de ventas) NO se toca â€” es un estado del cliente, no un informe. `01-arranque/briefing_encargo.md` (cuestionario previo) se mantiene â€” NO es el PDF briefing.
- **Resultado: 7 PDFs** â€” 3 Claude (bÃºsqueda global, modelo, unidad) + 4 Laravel (dossier, ficha-coche, informe-interno, folleto).

## [2.9.9] - 2026-08-16 â€” RediseÃ±o premium de plantillas Blade Laravel

### ðŸŽ¨ Mejoras visuales en plantillas Laravel
- **`informe-interno.blade.php`**: AÃ±adidas KPI cards premium (4 columnas), coverage grid de fuentes con estados OK/deg/omit, barras de progreso por dimensiones SCORE_DIM y VENDIBILIDAD_FACTOR, tarjeta verdict-card final con veredicto destacado, balance A_FAVOR/EN_CONTRA, tabla de comparables con badges DE/ES y fila Pick, y pie de pÃ¡gina "CONFIDENCIAL".
- **`ficha-coche.blade.php`**: AÃ±adidas KPI cards premium (3 columnas), badge de origen DE/ES con indicador Pick, y KPIs derivados del campo SPEC (KM, aÃ±o).
- **`folleto.blade.php`**: Mejorada cabecera premium con brand-badge, secciÃ³n KPI grid (3 columnas), headers h2 con barra accent, y mejoras de estilo consistentes con la marca JJ Import Motors (estoril/asphalt/platinum).
- **`CarDocumentDefinitions.php`**: Comentario actualizado para reflejar que los reports AI son generados por Laravel (no briefing).
- **`CarDocument.php`**: Comentario actualizado para aclarar que los reports AI no son briefing PDFs deprecated.

### ðŸ“¦ Empaquetado
- ZIP skill re-empaquetado v2.9.9 con backup alfabÃ©tico -n.
- Push origin master con commits de todas las mejoras de esta sesiÃ³n.

## [2.9.7] - 2026-08-15 â€” Plantilla de PDF rediseÃ±ada (nivel premium)

### ðŸŽ¨ `plantilla_pdf_marca.html` v2
- Header con lockup de marca + badge de flujo + nÂº de informe + fecha.
- Hero con tÃ­tulo en acento naranja + claim.
- **KPI cards** (grid de 4 mÃ©tricas clave: mejor DE, coste en Huelva, mejor ES, ahorro).
- **Cobertura de fuentes en grid de tarjetas** con estados (OK verde / degradado Ã¡mbar / omitido gris).
- **Tabla de candidatos premium**: badges DE/ES, semÃ¡foro, columna de enlace, fila del elegido destacada (`pick` con borde naranja).
- **Veredicto en tarjeta de recomendaciÃ³n** + chips de equipamiento + footer completo (contacto + legal).
- Coherente con la marca (estoril/asphalt/platinum + accent naranja) pero con estructura propia de informes (no es el folleto).
- Aplicada de ejemplo al `informe_busqueda_tiguan.html` (verificado visualmente).

## [2.9.6] - 2026-08-15 â€” Milanuncios resuelto: paginaciÃ³n por URL (confirmado)

### ðŸŸ¢ Cobertura Milanuncios al 100%
- Confirmado navegando: **`&pagina=N`** carga el listado completo y **respeta los filtros** (contenedor `.ma-AdList`, parÃ¡metro `pagina`).
- `memoria/trampas-encontradas.md`: virtualizaciÃ³n marcada **RESUELTA** â€” la paginaciÃ³n por URL es la vÃ­a principal; el scroll infinito NO es fiable.
- `02-flujos/paginas_reales.md`: URL de filtros reales (`anoh`, `cajacambio`, `engineHpTo`, `fuels`, `hasta`, `kilometersTo`, `puertas`, `orden`) + `pagina=N` + `nextToken` (degradado).

## [2.9.5] - 2026-08-15 â€” Limpieza de inconsistencias residuales

### ðŸ§¹ Coherencia contrato â†” cÃ³digo
- contrato.md: "Adaptado a los 4 flujos" (incluye D). Bloques del dossier-cliente se renderizan en `ficha-coche.blade.php` (no `dossier.blade.php`).
- SKILL.md: comando real `importnex:import-valuation` (no `jj:importar`); mapa del ZIP corregido.

## [2.9.3] - 2026-08-15 â€” DiseÃ±o de PDF unificado (plantilla de marca Ãºnica)
### ðŸŽ¨ Plantilla Ãºnica de marca (Claude â†” Laravel idÃ©nticos)
- Nueva `assets/plantilla_pdf_marca.html`: copia fiel del CSS de `ficha-coche.blade.php` (Inter, fondo #0f1d42, gradientes, grid, price-band naranja, CTA+QR). Claude la usa OBLIGATORIAMENTE para los PDFs de investigaciÃ³n â†’ visualmente idÃ©nticos a los de Laravel.
- MÃ©todo en SKILL.md Â§QuiÃ©n genera cada PDF: rellenar `{{marcadores}}` â†’ HTML â†’ Chrome headless â†’ PDF.
- Corregido en contrato.md: `dossier.blade.php` NO existe en Laravel; el documento del cliente real es `ficha-coche` (desde `ficha-publicitaria.txt`).

## [2.9.4] - 2026-08-15 â€” Fotos reales del anuncio, enlaces de ficha y fuentes con URL

### ðŸ“¸ Fotos = descargadas del ANUNCIO, nunca capturas
- Las fotos del candidato son las imÃ¡genes reales del anuncio (URLs jpg/png/webp de la ficha), descargadas a `<coche_id>_fotos\` y en `vehiculo.fotos` (Laravel las descarga al importar).
- PROHIBIDO subir capturas de pantalla ni screenshots del listado (fallo real 15-ago Tiguan).

### ðŸ”— Enlaces = ficha del anuncio individual, nunca genÃ©ricos
- Toda URL de candidato/comparable es la ficha del vehÃ­culo (mobile.de `details.html?id=`, slug Coches.net, `/app/item/<id>` Wallapop).
- PROHIBIDO URLs de bÃºsqueda/filtro, listados o dominio raÃ­z (A6).

### ðŸŒ Fuentes siempre documentadas con URL
- Todo informe (bÃºsqueda y unidad) cierra con "Fuentes consultadas": estado por fuente + enlace.
- En el JSON van en el bloque `fuentes` (o `avisos` si alguna quedÃ³ bloqueada).

### ðŸ—‚ï¸ OrganizaciÃ³n siempre por marca/modelo
- Todo en `informes\<marca>\<modelo>\` y `laravel\export\` â€” nunca suelto ni en AppData.

## [2.8.0] - 2026-08-15 â€” Flujo D Â· DESCUBRIMIENTO (cliente sin modelo)

### ðŸ” Nuevo flujo D con embudo de 3 pasos
- **D1 sondeo de modelos** (4-8 peticiones): peinar ES+DE solo a nivel de modelo/motorizaciÃ³n con los filtros del encargo. Sin fichas, sin anuncios individuales.
- **D2 INFORME DE MODELOS**: organizado por paÃ­s Ã— aÃ±o Ã— motorizaciÃ³n, con veredicto de encaje (ðŸŸ¢ holgado / ðŸŸ¡ justo / ðŸ”´ no cabe) y mejor mercado por modelo. Plantilla aÃ±adida. CP-D: esperar a que el usuario elija 2-3 modelos.
- **D3 embudo**: cada modelo elegido â†’ Flujo B (7 fuentes) â†’ candidato â†’ Flujo A. Las peticiones crecen al bajar de nivel: sondeo (8) â†’ B (15-50) â†’ A (35-70).
- Origen del cambio: anÃ¡lisis de la conversaciÃ³n "MarÃ­a" (9.000 â‚¬, sin modelo) â€” el usuario propuso particionar la bÃºsqueda: primero modelos que caben, luego investigar los que Ã©l elija.
- DetecciÃ³n de flujo actualizada (4 flujos) + triggers nuevos + briefing: modelo no se pregunta si hay presupuesto+requisitos (va a Flujo D).

## [2.7.0] - 2026-08-15 â€” Encargos abiertos: modalidades honorarios, plan de barrido y bandas de precio

### ðŸ’¶ Modalidades de honorarios M1/M2/M3 (briefing_encargo + costes)
- 3 fallos reales por ASUMIR el tratamiento de honorarios: 12-ago (techo corregido a mitad), 15-ago Tiguan (tarifa reducida ES), 15-ago MarÃ­a ("quita el coste del servicio" leÃ­do como "descuenta" cuando era "no se cobra").
- Ahora: M1 incluidos / M2 aparte / M3 no se cobran â€” se pregunta SIEMPRE o se reformula la interpretaciÃ³n en 1 lÃ­nea antes de ejecutar.

### ðŸ“‹ Plan de barrido previo para encargos ABIERTOS (SKILL.md)
- Cuando el usuario pide "revisa quÃ© hay/modelos/mercado" sin URL, NO se navega directo: se muestra el plan (mercados, filtros, bandas de precio, cobertura, entregable esperado) en 5-8 lÃ­neas y se pide OK.
- Responde al fallo real MarÃ­a 15-ago: medio informe PARCIAL entregado antes de que el usuario preguntara "Â¿quÃ© vas a hacer?".

### ðŸ›¡ï¸ Anti-patrones A12 y A13 (anti_patrones.md, 11â†’13)
- **A12** â€” PÃ¡gina 1 ordenada por precio como "listado": cubre TODO el rango del presupuesto (bandas de precio o paginaciÃ³n completa). Caso MarÃ­a: 526 resultados, solo 8 enseÃ±ados.
- **A13** â€” Filtros del encargo alterados en silencio (aÃ±o 2016â†’2012): se declara ANTES de navegar.

### ðŸ“Š Bandas de precio (playbook_filtrado.md)
- TÃ©cnica nueva: recorrer el rango por bandas (3-5k/5-7k/7k-techo); el objetivo es el mejor VALOR del rango, no el precio mÃ­nimo.

## [2.6.1] - 2026-08-15 â€” Corregida ambigÃ¼edad de rutas (JSON vs .md)

- **SKILL.md** Â§DÃ“NDE SE GUARDA CADA COSA reescrito con tabla Ãºnica QUÃ‰ archivo va DÃ“NDE: `.md` del usuario en `informes\<marca>\<modelo>\`; JSON de contrato (`flujo-a/b/c`) en `laravel\export\`; ZIP en `laravel\paquetes\`.
- **AclaraciÃ³n explÃ­cita:** `informe.json` NO existe suelto â€” va DENTRO del ZIP y lo genera `empaquetar.py` desde `export\flujo-a-<coche_id>.json`. ConfusiÃ³n real detectada: se buscaba un "informe de JSON" en la carpeta del modelo.
- `05-operaciones/operaciones.md` y `README.md` del Desktop actualizados con la misma tabla.

## [2.6.0] - 2026-08-15 â€” Estructura de carpetas por marca/modelo en el Desktop

### ðŸ“ Ruta de guardado obligatoria (15-ago-2026)
- **SKILL.md** Â§DÃ“NDE SE GUARDA TODO: todo se guarda en `C:\Users\jacar\Desktop\JJImportMotors\informes\<marca>\<modelo>\`.
- NUNCA en `AppData\Roaming\Claude\...\outputs\` (fallo real 15-ago Tiguan: el informe se escribiÃ³ ahÃ­ y el usuario no lo veÃ­a).
- Estructura por marca/modelo: `informe_busqueda_<fecha>.md` Â· `informe_unidad_<fecha>.md` Â· `<coche_id>.json` Â· `<coche_id>_fotos/` Â· `<coche_id>.zip`.
- `README.md` creado en `Desktop\JJImportMotors\informes\` documentando la estructura.
- **operaciones.md** actualizado: rutas de informes .md en `informes/`, datos/export/paquetes siguen en `laravel/`.

## [2.5.0] - 2026-08-15 â€” Estructura de informes por fase + tarifa ES + anti-patrones A9-A11

### ðŸ“‹ Estructura de informes obligatoria por fase (15-ago-2026)
- **SKILL.md** Â§ESTRUCTURA DE INFORMES: cada fase produce SU entregable, en orden, sin mezclarlos y sin esperar a que el usuario los pida.
  - Fase 1 (bÃºsqueda) â†’ INFORME DE BÃšSQUEDA + candidatos (con cobertura por fuente, NO valoraciÃ³n).
  - Fase 2 (avance) â†’ INFORME DE UNIDAD solo del candidato elegido.
  - Fase 3 (cierre) â†’ ZIP Laravel obligatorio.
- Fallo real 15-ago (Tiguan cliente): se entregÃ³ un Ãºnico `.md` de valoraciÃ³n al final y faltaron informe de bÃºsqueda, informe de unidad y ZIP.

### ðŸ’¶ Tarifa ES reducida (15-ago-2026)
- **costes.md** Â§Origen ES: si la unidad estÃ¡ en EspaÃ±a se cobra tarifa de gestiÃ³n reducida (~500 â‚¬, validar con el usuario), NO los 1.500 â‚¬ de importaciÃ³n.
- Aviso Canarias/Baleares (IGIC + traslado extra).

### ðŸ›¡ï¸ Anti-patrones A9, A10, A11 (15-ago-2026)
- **A9** â€” No afirmar haber visto/medido algo sin comprobarlo (caso Tiguan: "sÃ­ lo vi en mi barrido" sin verificar).
- **A10** â€” Precio financiado como gancho en portales ES (MUY CAR/Flexicar): confirmar contado antes de la tabla.
- **A11** â€” PaginaciÃ³n completa de Coches.net ordenando por precio (`pg=` + `pf=`), no muestrear 6 de muchas pÃ¡ginas.
- Actualizado en `06-reglas/anti_patrones.md` (8 â†’ 11) y `SKILL.md` checklist.

## [2.4.2] - 2026-08-12 â€” Cascada de informes + checkpoints Flujo B

### ðŸ—ï¸ DivisiÃ³n de trabajo definitiva (12-ago-2026)
- **InvestigaciÃ³n â†’ Claude (Desktop)** Â· **Almacenamiento, gestiÃ³n y actualizaciones â†’ Laravel (importnexcore)**.
- Laravel = **repositorio Ãºnico y fuente de verdad** de informes PDF, imÃ¡genes, JSON, dossier, folleto.
- Flujo: Claude investiga â†’ sube paquete ZIP a `/api/import-valuation` â†’ **FIN**. Laravel gestiona ver/mostrar/actualizar/iterar.
- Claude **NO consulta** lo subido. Cada nuevo encargo = nuevo chat en Claude.
- Documentado en `05-operaciones/operaciones.md` Â§DivisiÃ³n de trabajo Â· Desktop `CLAUDE.md` Â· Laravel `copilot-instructions.md`.
- Tras la prueba VS Code: la investigaciÃ³n con filtros se hace en Claude Desktop (VS Code lee pero no filtra bien, ver `memoria/retrospectiva.md`).

### ðŸ¢ Cambio de negocio (12-ago-2026)
- **AmpliaciÃ³n:** JJ Import Motors ya no solo importa desde Alemania â€” tambiÃ©n ofrece servicios de bÃºsqueda y gestiÃ³n **dentro de EspaÃ±a**.
- **Modelo sin compra (reforzado):** la empresa **NO compra coches ni mantiene stock**. Solo **oferta el servicio** de bÃºsqueda, importaciÃ³n y gestiÃ³n con honorarios fijos. El cliente es quien compra el coche.
- **ðŸŒ Origen DE vs ES:** si el encargo no especifica origen, buscar el modelo en **ambos mercados** y comparar dÃ³nde sale mejor (coste total puesto en Huelva). `04-negocio/costes.md` Â§Origen con las dos fÃ³rmulas (DE con importaciÃ³n, ES sin importaciÃ³n) + comparativa.
- Reflejado en: `SKILL.md` (frontmatter + negocio + origen) Â· `04-negocio/costes.md` Â· `01-arranque/briefing_encargo.md` (parÃ¡metro origen) Â· `02-flujos/extractores.md` Â· Desktop `CLAUDE.md`/`INSTRUCCIONES_PROYECTO.md`/`README.md` Â· Laravel `copilot-instructions.md` Â· `docs/guias/README.md`.
### ï¿½ AuditorÃ­a proactiva (revisiÃ³n de consistencia)- **ðŸ›¡ï¸ Regla dura #5 â€” COBERTURA COMPLETA (12-ago-2026):** se intentan SIEMPRE las 7 fuentes (ni mÃ¡s ni menos). Nunca cifras/veredicto con <7 sin marcar informe PARCIAL + preguntar. Caso real: se dieron precios con 2-3 fuentes y AutoScout24.
- **Tabla de fiabilidad por fuente** en `SKILL.md`: mobile.de (precio DE ðŸŸ¢) + Coches.net (precio ES ðŸŸ¢) como Ãºnicas referencias de precio; AutoUncle solo rotaciÃ³n; AutoScout24 SOLO contar (ðŸ”´ nunca precio, agrega feeds sin cribar); kleinanzeigen/Wallapop/Milanuncios chollos particulares.
- **Anti-patrones A7 y A8** aÃ±adidos (cobertura incompleta + AutoScout24 como precio). Total: 8.
- **Trampa documentada** en `memoria/trampas-encontradas.md` (AS24 precio engaÃ±oso, caso real 12-ago).- **ðŸ“Š Plantilla COMPARATIVA** nueva en `SKILL.md` â€” cuando el usuario pide investigar VARIOS candidatos, primero comparativa lado a lado (precio/aÃ±o/km/estado/coste/ahorro/score + banderas ðŸŸ¢ðŸŸ¡ðŸ”´ + enlaces), luego informes individuales.
- **briefing_encargo.md** â€” "Encargo completo (Flujo B)" renombrado a "Encargo INCOMPLETO" (contradicciÃ³n corregida: un encargo completo NO pide confirmaciÃ³n). Regla dura #1 matizada con "salvo que ya vengan dados".
- **briefing_encargo.md** â€” Flujo B "Claude automÃ¡ticamente" actualizado con el pipeline real (informe MODELO â†’ esperar elecciÃ³n â†’ automÃ¡tico).
- **guia_prompts.md** â€” Regla 2: aÃ±o desactualizado ("Ãºltimos 5 aÃ±os 2019-2024" â†’ "recientes").
- **anti_patrones.md** â€” typo "Claudeestima" â†’ "Claude estima".
- **SKILL.md** â€” referencia rÃ¡pida de checkpoints actualizada (CP1 = esperar elecciÃ³n de candidato tras informe MODELO).
- **ðŸ” APRENDIZAJE CONTINUO:** regla en `SKILL.md` Â§Aprendizaje continuo + plantilla `memoria/retrospectiva.md`. Cada conversaciÃ³n produce â‰¥1 aprendizaje; los fallos del usuario se convierten en trampa/anti-patrÃ³n/regla documentada.
- **ðŸš— Motores gasolina 2016+** en `04-negocio/riesgos.md` Â· **âš ï¸ Regla IEDMT** en `04-negocio/costes.md` (no estimar de oÃ­do) Â· **ðŸ§  preferencias de negocio** en proyecto (`preferencias.md`).

### ï¿½ðŸ”„ Cascada de informes (regla dura nueva)
- **SKILL.md** Â§CASCADA DE INFORMES: los informes NO salen todos a la vez. Flujo B entrega INFORME MODELO + top 5 con enlaces + CP1 â†’ usuario elige â†’ se convierte a Flujo A â†’ informe UNIDAD â†’ dossier â†’ ZIP.
- **NUNCA** saltar del resumen informal al "Â¿evalÃºo el candidato X?" sin entregar el INFORME MODELO completo + enlaces + CP1 (caso real 12-ago: se saltÃ³).
- **Operaciones.md** Flujo B actualizado: CP1 obligatorio entre Fase 1 y Fase 2.

### âš¡ MODO AUTOMÃTICO EN CASCADA
- **SKILL.md** Â§MODO AUTOMÃTICO: Fase 1 automÃ¡tica â†’ INFORME MODELO + top 5 â†’ **el USUARIO elige candidato** (no Claude) â†’ resto automÃ¡tico (fotos + informe UNIDAD + dossier + ZIP). Si varios candidatos â†’ comparativa antes.
- Solo pausa en: veredicto ðŸŸ¡/ðŸ”´, bandera crÃ­tica de seguridad (VIN ausente / no declara accidentes), o encargo incompleto.
- NUNCA preguntar "Â¿quÃ© candidato investigo?" â€” se entrega el informe MODELO y se espera la instrucciÃ³n del usuario.
- **briefing_encargo.md** Paso 3: excepciÃ³n de modo automÃ¡tico cuando no falta ningÃºn crÃ­tico.
- **operaciones.md** Flujo B: Fase 1 termina en informe MODELO; tras elegir candidato todo es automÃ¡tico.

### ðŸ“‹ Plantilla INFORME TIPO MODELO
- Nueva plantilla completa en `SKILL.md` (cobertura 7 fuentes + mediana/cuartil ES/DE + vendibilidad 5 factores + top 5 con enlaces + coste puesto en Huelva).

### ðŸ·ï¸ AclaraciÃ³n de quiÃ©n genera cada PDF
- Claude genera esqueletos `.txt` [MARCADOR] en el ZIP. Los PDFs finales (`dossier`, `ficha-publicitaria`, `folleto`) los genera **Laravel** (Blade+Browsershot) cuando el coche estÃ¡ en inventario. Claude NO genera PDFs ni folleto durante la investigaciÃ³n.

### ðŸ§¹ Limpieza de empaquetado
- Excluidos del ZIP los builds (`\.(zip|skill)$`) y `lista.txt` â€” se auto-incluÃ­an duplicando el tamaÃ±o (252KBâ†’503KB). ZIP ahora generado FUERA del directorio fuente.

---

## [2.4.1] - 2026-08-12 â€” GuÃ­a de uso para usuarios finales

### ðŸ“š docs/guias/
- `README.md` â€” Ã­ndice + diagrama de flujo del negocio (mermaid).
- `01-primeros-pasos.md` â€” arranque, verificaciÃ³n sync, token budget.
- `02-flujo-a-unidad.md` / `03-flujo-b-modelo.md` / `04-flujo-c-mercado.md`.
- `05-informes.md` â€” leer informes + dossier del cliente.
- `06-cierre-venta.md` â€” registrar cierres (curl) + KPIs en `/kpis`.
- `07-solucion-problemas.md` â€” FAQ y troubleshooting.
- `ejemplos/` â€” casos reales Astra OPC + Tiguan (Flujo A), Golf GTI + scouting (B/C).

---

## [2.4.0] - 2026-08-12 â€” AuditorÃ­a 3 (16 hallazgos, 100% resueltos)

### ðŸŸ  Alto
- **#1 Multi-tenant ScoutingMercado** â€” `scouting_id` ahora unique PER organizaciÃ³n (migraciÃ³n `2026_08_12_110000_make_scouting_unique_per_organization`). `storeMercado()` hace upsert scoped por org. Test de colisiÃ³n entre 2 orgs.
- **#2 Enlace dashboard KPIs** â€” `cars.show` ahora usa `car_id` (numÃ©rico) en vez de `coche_id` (slug), con fallback a texto plano.
- **#3 Fechas en cierres** â€” `storeCierre()` valida formato `YYYY-MM-DD` (422 en vez de 500). Tests de fechas invÃ¡lidas.

### ðŸŸ¡ Medio
- **#4 Validaciones negocioâ†’422** â€” `RuntimeException` de `ValuationImporter::apply()` se responde como 422, no 500.
- **#6 attachBriefing org** â€” verifica que el coche pertenece a la org autenticada (404 si no).
- **#7 Flujo C lectura** â€” nuevo `GET /api/scouting` (listado por org con modelos).
- **#8 down() scouting** â€” migraciÃ³n original ahora dropea `modelos_mercado` antes que `scouting_mercado`.
- **#9 Tests huecos** â€” `KpiCalculatorTest` (5 tests), aislamiento multi-org en `/api/kpis`, fechas/veredicto invÃ¡lidos en cierres, colisiÃ³n scouting_id, `index_scouting`.
- **#10 golden-tests** â€” Â§5.1/Â§5.2 actualizados (B/C implementados, `co2_confirmado` validado).

### ðŸŸ¢ Bajo
- **#11 DRY token** â€” nuevo middleware `import-token` centraliza auth (X-Import-Token + org) en los 9 endpoints del puente.
- **#12 Ãndice plataforma** â€” migraciÃ³n `2026_08_12_120000_add_plataforma_index_to_cierres_table`.
- **#13 Veredicto validado** â€” `storeCierre()` valida contra valores del contrato (422 si no).
- **#14 Throttle** â€” `POST /api/import-valuation` movido a `throttle:api-write`.
- **#15 Skill docs** â€” secciÃ³n "KPIs en Laravel (endpoint + dashboard)" en operaciones_cierre.md.
- **#16 use Schema** â€” aÃ±adido import en `EnrichedValuationMigrationTest` (limpia linter).

---

## [2.3.0] - 2026-08-12 â€” Fixes auditorÃ­a integral (17 hallazgos)

### ðŸ”´ CrÃ­ticos corregidos
- **#1 IEDMT**: coeficientes Anexo IV extraÃ­dos a `config/iedmt.php` (single source of truth). Corregidos 5 valores incorrectos en `Car::calculateIEDMT()` (aÃ±o 10: 14% â†’ 17%, etc.). AÃ±adido `tests/Unit/IedmtCalculationTest.php` (7 tests).
- **#2/#9 RelaciÃ³n Cierreâ†”Car**: `brand`/`model` denormalizados en tabla `cierres` (migraciÃ³n `2026_08_12_100000_add_brand_model_to_cierres_table`) + poblados en `storeCierre()`. Eliminada bÃºsqueda por `slug` inexistente. El filtro por marca en dashboard ya funciona.

### ðŸŸ  Rendimiento y mantenibilidad
- **#3 N+1**: eager load `with('car')` en KpiController.
- **#4 Bucle tendencia**: unificado en `KpiCalculator::historico()` (N meses, clamp 1-24).
- **#5 DuplicaciÃ³n KPIs**: nuevo `app/Services/KpiCalculator.php` â€” fuente Ãºnica para `KpiController` (web) y `ImportValuationApiController::kpis()` (API).
- **#6 Test flaky**: fechas distintas en fixtures de `KpiControllerTest`.

### ðŸŸ¡ Robustez
- **#8 schema_version**: `ValuationImporter` guarda la versiÃ³n real del payload, no hardcodeada.
- **#11 Tests `attachBriefing`**: nuevo `BriefingPdfApiTest` (5 casos: PDF vÃ¡lido, no-PDF, >10MB, sin archivo, sin token).

### ðŸŸ¢ DocumentaciÃ³n
- **#12 Mapeo `valoracion`â†’`rating`**: documentado en contrato.md (intencional: Car normaliza, Cache guarda crudo).
- **#13 `boe_confirmed`**: documentado (no activo hoy; solo futuro BOE).
- **#15 ROADMAP**: secciÃ³n "Lado Laravel (completado)" aÃ±adida.

### âšª Verificados (falsos positivos de auditorÃ­a)
- **#7 Throttles**: `api-read`/`api-write`/`api-heavy` YA definidos en `AppServiceProvider` (RateLimiter::for). Sin acciÃ³n.
- **#10 CHANGELOG**: ya existe (este archivo). Sin acciÃ³n.

---

## [2.2.0] - 2026-08-12 â€” Contador token budget y dashboard KPIs

### ðŸ”¢ Â§2.4 â€” Contador de peticiones (tracking manual)
- **Nueva secciÃ³n** `### Contador de peticiones (Â§2.4)` en `SKILL.md` tras Token budget
- Contador por fuente: mobile.de X/45 (avisar a 35), AutoScout24 X/36, Coches.net X/35, resto X/20
- Reglas por flujo: A mÃ¡x 70 (avisar 35/56), B mÃ¡x 50 (avisar 25/40), C mÃ¡x 100 (avisar 50/80)
- Regla dura: NUNCA >45 peticiones a mobile.de en una sesiÃ³n
- Si se supera el budget sin veredicto â†’ STOP + resumen parcial + decidir PARCIAL

### ðŸ“Š Â§3.8 â€” Dashboard KPIs en Laravel (frontend)
- **`app/Http/Controllers/KpiController.php`** (nuevo, invokable)
- **`resources/js/Pages/Kpis/Index.vue`** (nuevo)
- **Ruta** `GET /kpis` (`kpis.index`) bajo `['auth', 'verified', 'organization']`
- 4 KPIs por periodo con navegaciÃ³n mes a mes: precisiÃ³n de veredictos (â‰¥80%), tiempo hasta venta (â‰¤15d), desviaciÃ³n de precio (â‰¤5%), falsos positivos (â‰¤20%)
- Tendencia de precisiÃ³n Ãºltimos 6 meses + tabla de cierres con desviaciÃ³n y estado
- Cards con semÃ¡foro verde/Ã¡mbar/rojo segÃºn objetivo; colores marca (`estoril-700`)
- Enlace `KPIs` aÃ±adido al menÃº lateral (grupo Inventario) con clave i18n `nav.kpis` (es/en)
- Requiere la migraciÃ³n `2026_08_12_092939_create_cierres_table` aplicada en producciÃ³n

---

## [2.1.0] - 2026-08-12 â€” DocumentaciÃ³n Â§3.7 y refactor Â§2.3

### âœ… Â§3.7 â€” DocumentaciÃ³n `verify_desktop_sync.py`
- **Nueva secciÃ³n** `## âœ… VerificaciÃ³n de sincronizaciÃ³n Desktop (ARRANQUE)` al inicio de `05-operaciones/operaciones.md`
- Documenta comando (`py .claude/skills/.../verify_desktop_sync.py`), quÃ© verifica (12 scripts + 2 datos), output exitoso y output con faltantes
- Integra con flujo Claude: ejecutar **siempre** al inicio de sesiÃ³n antes de leer `indice.json` o invocar `franja.py`
- Exit code 0 = sesiÃ³n OK; exit code â‰  0 = NO arrancar

### ðŸ“ Â§2.3 â€” Single source of truth IEDMT
- `03-informes/contrato.md` Â§`costes`: el bloque IEDMT ahora referencia [`04-negocio/costes.md Â§IEDMT`](04-negocio/costes.md#-iedmt-orden-hac15012025-vigor-1-ene-2026) en lugar de duplicar la fÃ³rmula
- `iedmt_metodologia` redefinido: cadena corta con PVP/antigÃ¼edad/COâ‚‚/cifras resultantes (ejemplo real), sin desglose de fÃ³rmula
- Single source of truth mantenida en `04-negocio/costes.md` (Orden HAC/1501/2025)

---

## [2.0.0] - 2026-08-12 â€” AuditorÃ­a completa y hardening del skill

### ðŸ›¡ï¸ Seguridad multi-tenant (Â§10.3, Â§10.5)
- **MigraciÃ³n** `2026_08_12_090058_add_organization_and_soft_deletes_to_investigation_cache_table.php`:
  - AÃ±ade `organization_id` (foreign key) a `investigation_cache`
  - AÃ±ade `deleted_at` (soft deletes)
  - Cambia Ã­ndice Ãºnico a compuesto `(organization_id, clave_modelo)`
- **Modelo** `InvestigationCache`:
  - Trait `SoftDeletes`
  - RelaciÃ³n `organization(): BelongsTo`
  - MÃ©todo `aspectosCaducados()`

### ðŸŽ¯ Endpoints robustos (Â§10.1, Â§10.2, Â§10.6, Â§10.7)
- **`attachBriefing()`** implementado (antes la ruta existÃ­a pero el mÃ©todo no â€” 500 error)
- **`storeInvestigationCache()`** y **`getInvestigationCache()`** scoped por organizaciÃ³n
- **`validate()`** refactorizado con nueva firma: `validate($payload, $requiredBlocks, $expectedFlujo)`
- Mensaje de error mejorado en GET sin parÃ¡metros (muestra quÃ© parÃ¡metros faltan + ejemplo)
- ValidaciÃ³n de schema_version + flujo + bloques mÃ­nimos en los 3 endpoints (A, B, C)

### âœ… Validaciones de negocio (Â§3.1, Â§3.2, Â§3.3)
- **`co2_confirmado: false`** â†’ warning automÃ¡tico en avisos
- **Comparables sin URL** â†’ filtrados con aviso "{N} comparables descartados"
- **`precio_objetivo` obligatorio** cuando recomendaciÃ³n es "Comprar si baja de precio"

### ðŸ“ Consistencia del skill (Â§1.1)
- **Umbrales Nicho unificados**: 10% objetivo, 8% mÃ­nimo (EXIT 3)
- Comportamiento entre 8-10% documentado: "margen justo, posible si vendibilidad â‰¥70"

### ðŸ“Š Modelo InvestigationCache completado (Â§1.2)
- AÃ±adidos aspectos `precio_mercado => 18 meses` y `otros => 24 meses`
- Total: 9 aspectos con caducidades definidas

### Tests
- **61 tests pasando (231 aserciones)**
- 3 nuevos tests de validaciÃ³n de negocio (3.1, 3.2, 3.3)
- 10 tests unitarios de UrlNormalizer (Â§2.1)
- 10 tests de CierreApi (Â§3.5)
- 3 tests de mapeo drivetrain (Â§3.4)
- Tests actualizados para reflejar validaciones multi-tenant

### ðŸš— Mapeo `traccion` â†’ `drivetrain` (Â§3.4)
- **Nueva columna** `drivetrain` en tabla `cars` (despuÃ©s de `transmission`)
- **Nuevo mapa** `DRIVETRAIN_MAP` en `ValuationImporter` con traducciones espaÃ±ol/inglÃ©s/alemÃ¡n â†’ valores canÃ³nicos `FWD`/`RWD`/`AWD`
- **3 tests** cubriendo tracciÃ³n delantera, total/AWD y ausencia del campo
- **Modelo Car**: `drivetrain` aÃ±adido a `$fillable`
- **MigraciÃ³n**: `2026_08_12_093456_add_drivetrain_to_cars_table.php`

### ðŸ“Š Endpoint `/api/cierres` (Â§3.5)
- **Nuevo modelo** `Cierre` con SoftDeletes, scopes (`periodo`, `vendidos`, `veredictoPositivo`) y helpers (`desviacionPorcentaje`, `calcularDiasHastaVenta`)
- **Nueva migraciÃ³n** `2026_08_12_092939_create_cierres_table.php` con Ã­ndices para queries KPI
- **POST `/api/cierres`** â€” registra cierre de venta (o no-venta) con cÃ¡lculo automÃ¡tico de dÃ­as y desviaciÃ³n
- **GET `/api/cierres`** â€” lista cierres con filtros (periodo, estado, veredicto_positivo) y KPIs agregados (precisiÃ³n, tiempo medio, desviaciÃ³n, falsos positivos)
- Prerrequisito completado para Â§3.8 (dashboard KPIs)

### ðŸ”§ OptimizaciÃ³n Â§2.1 â€” UrlNormalizer helper
- **Nueva clase** `app/Support/UrlNormalizer.php`
- MÃ©todos: `normalize(?string $url): ?string` y `same(?string $url1, ?string $url2): bool`
- ExtraÃ­da lÃ³gica duplicada de `ValuationImporter::resolveCar()`
- 10 tests unitarios cubriendo edge cases

### ðŸ“ DocumentaciÃ³n (Â§1.4, Â§1.5)
- **Â§1.4 â€” Single source of truth de costes fijos:** SKILL.md ahora referencia costes.md en lugar de duplicar valores
- **Â§1.5 â€” Filtro de competencia:** Documentado cuÃ¡ndo aplicar (Fase 2 de Flujo A, despuÃ©s de recolectar las 3 fuentes ES)

### Archivos modificados
- `database/migrations/2026_08_12_090058_*.php` (nueva â€” investigation_cache multi-tenant)
- `database/migrations/2026_08_12_092939_create_cierres_table.php` (nueva â€” Â§3.5)
- `database/migrations/2026_08_12_093456_add_drivetrain_to_cars_table.php` (nueva â€” Â§3.4)
- `app/Models/InvestigationCache.php`
- `app/Models/Cierre.php` (nuevo â€” Â§3.5)
- `app/Models/Car.php` (drivetrain aÃ±adido a fillable â€” Â§3.4)
- `app/Http/Controllers/Api/ImportValuationApiController.php`
- `app/Services/ValuationImporter.php`
- `app/Support/UrlNormalizer.php` (nuevo â€” Â§2.1)
- `routes/api.php`
- `.claude/skills/importacion-vehiculos/SKILL.md`
- `.claude/skills/importacion-vehiculos/comparables.md` (Â§1.5)
- `tests/Feature/ValuationImporterTest.php` (+3 tests Â§3.4)
- `tests/Feature/ModeloImportTest.php`
- `tests/Feature/ScoutingMercadoImportTest.php`
- `tests/Feature/InvestigationCacheTest.php`
- `tests/Feature/CierreApiTest.php` (nuevo â€” Â§3.5)
- `tests/Unit/UrlNormalizerTest.php` (nuevo â€” Â§2.1)
- `tests/Feature/fixtures/chat_report_example.json`

### DocumentaciÃ³n
- `docs/auditoria-skill-2026-08-12.md` â€” 12 secciones con 43 items detectados + 11 crÃ­ticos resueltos

## [1.13.0] - 2026-08-11

### Sprint G, H e I completados (25/26 mejoras, 96%)

#### Added
- **Registro de cierre** (#15): Estructura JSON para tracking de cierres reales vs estimados en `datos/registro_cierres.json`
- **KPIs del skill** (#16): 4 mÃ©tricas clave (precisiÃ³n de veredictos, tiempo hasta venta, desviaciÃ³n de precio, tasa de falsos positivos)
- **Changelog formal** (#17): Este archivo, versionado segÃºn Semantic Versioning
- **SincronizaciÃ³n Desktop** (#20): Script `verify_desktop_sync.py` para verificar que los scripts referenciados existen en Desktop

#### Fixed
- DocumentaciÃ³n completa de las 5 mejoras pendientes de Sprints G, H e I
- SKILL.md ahora incluye secciones operativas para registro de cierres, KPIs y sincronizaciÃ³n

### Notas
- Solo falta #21 (cachÃ© de investigaciÃ³n en Laravel) que requiere backend
- Progreso global: 25/26 mejoras (96%)
- SKILL.md: 485 lÃ­neas (+118 desde v1.12.0)

## [1.12.0] - 2026-08-11

### Sprint E completado (21/26 mejoras, 81%)

#### Added
- **PriorizaciÃ³n por ROI** (#2): Scoring automÃ¡tico con fÃ³rmula `MargenEstimado Ã— VendibilidadEstimada Ã— Urgencia`
- **Comparable sin muestra** (#3): 3 mÃ©todos en cascada (Normal â†’ Ampliado â†’ Cualitativo)
- **DeduplicaciÃ³n** (#4): Huella normalizada `(aÃ±o, kmÂ±2%, cv, precioÂ±3%, combustible)` para no contar 2 veces el mismo coche

### Changed
- SKILL.md: 367 lÃ­neas (+47 desde v1.11.0)
- SecciÃ³n "Fases" ahora incluye prioridad por ROI y deduplicaciÃ³n
- SecciÃ³n "Comparable" ahora incluye mÃ©todo sin muestra

## [1.11.0] - 2026-08-11

### Fase 11: Endpoint B (MODELO) implementado

#### Added
- MÃ©todo `storeModelo()` en `ImportValuationApiController`
- Ruta `POST /api/import-modelo` para Flujo B
- 5 tests con 13 aserciones para endpoint B
- ValidaciÃ³n de `_meta.flujo = "B"` y eliminaciÃ³n automÃ¡tica de bloque `publicidad`

### Fixed
- Cierre completo de I14 (inconsistencia endpoints B/C Laravel)
- Todos los endpoints (A, B, C) ahora implementados y probados

### Changed
- docs/analisis-skill-importacion-vehiculos.md actualizado con changelog v1.6.0

## [1.10.0] - 2026-08-11

### Fase 10: Endpoint C (MERCADO) implementado

#### Added
- MigraciÃ³n `2026_08_11_205511_create_scouting_mercado_table.php`
- Tablas `scouting_mercado` y `modelos_mercado`
- Modelos `ScoutingMercado` y `ModeloMercado`
- MÃ©todo `storeMercado()` en `ImportValuationApiController`
- Ruta `POST /api/import-mercado` para Flujo C
- 10 tests con 30 aserciones para endpoint C

### Fixed
- Cierre parcial de I14 (endpoint C implementado, B pendiente)

## [1.9.0] - 2026-08-11

### Fase 9: Mejoras #22 y #23

#### Added
- **Token budget consciente** (#22): Tabla de peticiones por flujo (A: 70, B: 50, C: 100)
- **Dimensiones de atractivo** (#23): 4 categorÃ­as para Flujo C (pasionales, premium, econÃ³micos, eco)

### Fixed
- ConversiÃ³n de `EnrichedValuationTest.php` de Pest a PHPUnit (10 tests, 3 pasan, 7 skipped)

### Changed
- SKILL.md: 320 lÃ­neas (+18 desde v1.8.0)

## [1.8.0] - 2026-08-11

### Fase 8: Golden tests reales

#### Added
- `docs/golden-tests/README.md` con 6 informes reales (2 modelos, 3 veredictos)
- Casos: Astra OPC 2012/2013 (Comprar), Tiguan 1.4/1.5 TSI Ã—4 (Descartar)
- ValidaciÃ³n de edge cases: <5 comparables ES, margen negativo

### Fixed
- Cierre de Mejora #19 (golden tests) y Deuda D3

## [1.7.0] - 2026-08-11

### Fase 7: ConsolidaciÃ³n del documento de anÃ¡lisis

#### Changed
- docs/analisis-skill-importacion-vehiculos.md: 1299 â†’ 741 lÃ­neas (-43%)
- Estructura: HistÃ³rico (Â§3-6, 10) + Activo (Â§11-12) + Referencia (Â§0-2, 7-9)
- Eliminadas redundancias entre secciones 10-14 y 15-17

## [1.6.0] - 2026-08-11

### Fase 6: Refinamiento final

#### Added
- anti_patrones.md separado (88 lÃ­neas) con detalle de las 6 reglas duras
- Criba diferenciada Fase 1 (soft) vs Fase 2 (dura)
- Scripts deprecados marcados con âŒ
- CachÃ© de investigaciÃ³n formalizado en operaciones.md
- Endpoints B/C avisados como pendientes

### Fixed
- I8 (contradicciÃ³n hidrataciÃ³n), I9 (criba por fase), I10 (scripts deprecados), I12 (cachÃ© formalizado)

## [1.5.0] - 2026-08-11

### Fase 5: JSON distintos por flujo

#### Added
- Estructura JSON diferenciada para Flujos A, B y C en contrato.md
- Campo `iedmt_sin_minoracion` para verificaciÃ³n fiscal
- URLs obligatorias en comparables
- Endpoints separados (A implementado, B/C pendientes)

## [1.4.0] - 2026-08-11

### Fase 4: Operaciones refinadas

#### Added
- Google Drive movido a backup-only en operaciones.md
- Scripts organizados por flujo
- Carpetas de trabajo documentadas
- Flujo diario por flujo (A/B/C)

## [1.3.0] - 2026-08-11

### Fase 3: Extractores mejorados

#### Added
- extractores.md con cobertura por flujo/fase
- Fix bug `__S` (aÃ±o 2026 hardcoded â†’ `getFullYear()+1`)
- Edge cases documentados (mobile.de bloqueado, NL/BE/LU, checkpoint "no", CHF)

### Fixed
- I1 (extractores sin fases), I4 (aÃ±o 2026), I5 (obligatoriedad mal), D1 (bug __S)

## [1.2.0] - 2026-08-11

### Fase 2: Contrato JSON formalizado

#### Added
- contrato.md con estructura completa del JSON
- Formato esqueleto [BLOQUE] documentado
- Datos de marca integrados
- Tabla de 9 aspectos de investigaciÃ³n

### Fixed
- I2 (IEDMT), I3 (cobertura), I6 (JSON Flujo C)

## [1.1.0] - 2026-08-11

### Fase 1: RefactorizaciÃ³n inicial

#### Added
- 3 flujos (A/B/C) con detecciÃ³n automÃ¡tica
- 2 fases con 3 early exits
- 6 anti-patrones bloqueados (A1-A6)
- ZIP cristalino para Laravel
- Referencia rÃ¡pida al inicio del SKILL.md

#### Changed
- SKILL.md: 827 â†’ 320 lÃ­neas (-61%)
- 4 archivos modulares: extractores.md, contrato.md, operaciones.md, anti_patrones.md

### Fixed
- F1 (3 flujos), F2 (detecciÃ³n), F3 (3 informes), T1 (2 fases), T2 (delta updates)
- A1+A2 (anti-patrones 6), C2 (telÃ©fono incorrecto 667â†’675)
- I2 (IEDMT), I3 (cobertura), I6 (JSON Flujo C), I7 (extraer anti-patrones)

## [1.0.0] - 2026-08-10

### VersiÃ³n inicial

#### Added
- SKILL.md monolÃ­tico (827 lÃ­neas)
- Sistema de 7 fuentes (3 ES + 4 DE)
- InvestigaciÃ³n de 9 aspectos
- Comparable con 9 claves y ajuste lÃ­nea a lÃ­nea
- IEDMT con minoraciÃ³n art.69
- Matriz de decisiÃ³n (vendibilidad Ã— margen)

### Known Issues
- CÃ³digo JS inline (~100 lÃ­neas)
- Sin contrato JSON formal
- Sin formato esqueleto documentado
- Sin datos de marca
- Color inconsistente (#0B1F3A vs #1A306D)
- TelÃ©fono incorrecto (667 vs 675)

