# Changelog

Todos los cambios notables en el skill `importacion-vehiculos` se documentarán en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [3.2.5] - 2026-08-18 — Contenedor de anuncios mobile.de ES (tarjeta real)

> **Origen:** el usuario facilitó el HTML del listado `div[data-testid="card-layout-wrapper"]` (`top-`/`base-`/`tic-result-listing-N`, con orden `sb=&od=` y paginación).

### 🇩🇪 Leer tarjeta mobile.de
- **`paginas_reales.md`** — sección "Contenedor de anuncios — selectores estables `data-testid`": **ID del href `detalles.html?id=<ID>` para deduplicar**, título `span[data-testid="listing-title-card-view"]` + versión `...__subTitle`, **precio `span[data-testid="price-label"]`** ("€¹"=bruto/"zzgl. MwSt."=neto), **bajada `span[data-testid="strike-through-price"]`**, **rating precio `div.PriceRatingBadge--label`** (Muy buen/Buen/Precio justo/Sin calificación → chollo), atributos `[data-testid="listing-details-attributes"]`, vendedor `[data-testid="seller-info"]`, **sello OEM `[data-testid="oem-seal-listing"]`**, orden `[data-testid="sorting-menu-dropdown"]` (`sb=p&od=up` = precio bajo), paginación `[data-testid="pagination:next"/:previous"]`.
- **`playbook_filtrado.md`** — tabla "mobile.de — leer la tarjeta".
- Claves: **`data-testid` + prefijos `*-module__` estables, sufijos `__xxxxx` con hash → NO usar**; tarjetas `top-*`/`tic-*` = patrocinadas, `base-*` = orgánicas; ads `SRP_TABLECELL_*`/`SRP_INPAGE_VIDEO` y carrusel `SimilarTopListings` ("Otros vehículos de este concesionario") → ignorar; rating precio = señal de chollo.

## [3.2.4] - 2026-08-18 — Contenedor de anuncios Coches.net (tarjeta real)

> **Origen:** el usuario facilitó el HTML del grid `section.mt-AdsList-content` (`div[data-ad-id]`, con paginación `pg=N`, skeletons lazy y ads intercalados).

### 🇪🇸 Leer tarjeta Coches.net
- **`paginas_reales.md`** — sección "Contenedor de anuncios — selectores estables": `div[data-ad-id]` (ID para **deduplicar**), título `h2[data-testid="card-ad-title"]`, **precio contado `p[data-testid="card-adPrice-price"]`** (SIEMPRE contado, NO financiado), **rating precio** `span.mt-CardAdPrice-cashLabel` ("Buen precio"=4/5·"Precio justo"=3/5 → chollo), **bajada** `mt-CardAdPrice-priceDropPercentage`+`...OriginalPrice`, atributos `ul.mt-CardAd-attr li`, etiqueta DGT `...EnvironmentalLabel img` (b/eco/c/0), vendedor `span.sui-AtomBadge-text`, paginación `/search/?Section1Id=2500&pg=N`.
- **`playbook_filtrado.md`** — tabla "Coches.net — leer la tarjeta".
- Claves: `data-testid`/prefijos `mt-*`/`sui-*` estables (sin hash); **skeletons `div.sui-PerfDynamicRendering-placeholder` = sin cargar** → scroll antes de leer; financiado (cuota `/mes*` + TAE) → ignorar; ads `--tallAd`(`#ad-right-*`)·`--native--mobile`(`#ad-inline-*`)·`#ad-textads` → ignorar; rating "Buen precio" ya valida el precio.

## [3.2.3] - 2026-08-18 — Contenedor de anuncios kleinanzeigen (tarjeta real)

> **Origen:** el usuario facilitó el HTML del listado `<ul id="srchrslt-adtable">` (`li[data-clickable="card"]` → `article[data-adid]`, con paginación `seite:N` y ads intercalados).

### 🇩🇪 Leer tarjeta kleinanzeigen
- **`paginas_reales.md`** — sección "Contenedor de anuncios — selectores estables": **`article[data-adid]`** (ID para **deduplicar**), enlace `a[href^="/s-anzeige/"]`, precio `p.font-strong.text-secondary`, **precio anterior tachado `p.line-through` = bajada/chollo**, atributos `span[data-dhl-promotion]` (km + EZ), ubicación/fecha por iconos, badges **TOP/PRO** (pago, NO señal), paginación `/s-autos/seite:N/c216`, **JSON de cada tarjeta** (`resultAds`: `price`/`startingPrice`/`posterType`/`topAd`/`priorityAd`).
- **`playbook_filtrado.md`** — tabla "kleinanzeigen — leer la tarjeta".
- Claves: clases Tailwind estáticas (sin hash) → estables; ads intercalados `#srpb-top-banner` / `[data-liberty-position-name^="srpb-result-list-"]` / `#srpb-middle` → ignorar; precio tachado = chollo; `posterType=PRIVATE` = particular sin IVA.

## [3.2.2] - 2026-08-18 — Contenedor de anuncios AutoUncle (tarjeta real)

> **Origen:** el usuario facilitó el HTML de la página de resultados completa (~20 tarjetas `article._qzVn4`, carrusel patrocinado, paginación).

### 🇩🇪 Leer tarjeta AutoUncle
- **`paginas_reales.md`** — sección "Contenedor de anuncios — selectores estables": enlace `a[href^="/es/d/"]` (ID para **deduplicar**), **rating precio (1-5)** en `aria-label` del enlace + `data-rating` ("Buen precio"/"Super precio"), precio actual `._i2QOc` vs referencia tachado `._O_cMy`, **"Por debajo del mercado X €"** (`span._2OgvT`) = chollo, cambio de precio `[data-testid="listing-item--price-history"]`, **días en venta** (rotación), atributos `ul._PuGQy > li._ZTpYr`, concesionario `[data-testid="source-label"]`, contador total `._oCbI6` (oferta total), paginación `?page=N&s[available_for_online_sales]=false`.
- **`playbook_filtrado.md`** — tabla "AutoUncle — leer la tarjeta".
- Claves: sufijos CSS Modules (`_qzVn4`, `_i2QOc`) NO estables → usar `aria-label`/`data-testid`/`data-rating` (sin hash); carrusel "Ofertas seleccionadas" (`a._DF8g3`) = patrocinado → NO contar; ads `#sr_N` → ignorar; A8: AutoUncle SOLO rotación/validación, NUNCA fuente de precio del estudio (rating y "por debajo del mercado" = señal de chollo).

## [3.2.1] - 2026-08-18 — Contenedor de anuncios Wallapop (tarjeta real)

> **Origen:** el usuario facilitó el HTML del grid `.item-card-grid_ItemCardGrid` (tarjetas `a.item-card_ItemCard--horizontal`). Complementa los filtros de v3.1.8 con la **lectura de la tarjeta**.

### 🇪🇸 Leer tarjeta Wallapop
- **`paginas_reales.md`** — sección "Contenedor de anuncios — selectores estables": precio `[aria-label="Item price"]` (sin hash, nunca falla), atributos en `label[class*=item-card_ItemCard__attributes]` (combustible·cambio·cv·año·km), título/descripción/vendedor por prefijo `item-card_*`, **ID del href para deduplicar**, rating en shadow DOM de `wallapop-rating-indicator`.
- **`playbook_filtrado.md`** — tabla "Wallapop — leer la tarjeta".
- Claves: sufijos CSS Modules cambian por build (usar prefijos + `aria-label`); landing SEO = Novedades, no búsqueda activa; equipamiento solo en descripción.

## [3.2.0] - 2026-08-18 — Contenedor de anuncios Milanuncios (tarjeta real)

> **Origen:** el usuario facilitó el HTML del listado `.ma-AdList`. Hasta ahora solo se documentaba el modal de filtros; falta la **lectura de la tarjeta** (precio contado, bajada, tags, dedup).

### 🇪🇸 Leer tarjeta Milanuncios
- **`paginas_reales.md`** — sección "Contenedor de anuncios — selectores estables": `.ma-AdCardV2` (`data-testid="AD_CARD"`), precio contado vs financiado (usar **contado**, IVA incl.), bajada de precio `.ma-AdPrice-iteration*` (chollo/negociable), tags `.ma-AdTag-label`, ID del href para **deduplicar**, skeletons/carrusel/ads a ignorar.
- **`playbook_filtrado.md`** — tabla "Milanuncios — leer la tarjeta".
- Claves: patrocinado "Destacado" NO cuenta como señal · tarjeta sin `href`/sin tiempo = `man` · carrusel = recomendaciones.

## [3.1.9] - 2026-08-18 — Selectores estables del modal de filtros Milanuncios

> **Origen:** el usuario facilitó el HTML del modal `.sui-MoleculeModal` (`ma-FormFiltersPopoverModal`). Campos desplegables = 3 pasos (clic campo → opción → "Aplicar filtro").

### 🇪🇸 Selectores Milanuncios
- **`paginas_reales.md`** — sección "Modal de filtros — selectores estables": categoría/marca/ubicación por `data-testid`, rangos directos `#price-from`/`#price-to`·`#kms-from`·`#year-from`·`#potencia-from`, switches `#isPriceDropped`·`#hasWarranty`·`#isCertified`, radios por `aria-label`, etiqueta DGT (`#0`/`#ECO`/`#C`/`#B`/`#NO_LABEL`), combustible, plazas (`#FOUR_SEATS`...), color, tipo anuncio.
- **`playbook_filtrado.md`** — tabla "Selectores ESTABLES del modal de filtros Milanuncios".
- ⚠️ **Milanuncios NO tiene filtro de equipamiento** → máximo equipamiento por `keywords=` o validando fichas. Negociable (chollos ES).

## [3.1.8] - 2026-08-18 — Selectores estables de filtros Wallapop

> **Origen:** el usuario facilitó el HTML del sidebar de Wallapop (`/coches-segunda-mano`). Usa **web components** `walla-*` con `name`/`id` estables.

### 🇪🇸 Selectores Wallapop
- **`paginas_reales.md`** — sección "Sidebar de filtros — sliders + radios/checkboxes estables": rangos con `<wallapop-range-selector>` (`#fromSelector`/`#toSelector`), fecha (`time_filter-radio-group-single-selection`), marca/modelo (radio por `id`), etiqueta DGT (`zero`/`eco`/`c`/`b`), carrocería, combustible, cambio, vendedor (`seller_type-radio-group-single-selection`).
- **`playbook_filtrado.md`** — tabla "Selectores ESTABLES de filtros en Wallapop".
- ⚠️ **Wallapop NO tiene filtro de equipamiento** → máximo equipamiento por `keywords=` o validando fichas.

## [3.1.7] - 2026-08-18 — Selectores estables de filtros AutoUncle (`name`)

> **Origen:** el usuario facilitó el HTML del sidebar de AutoUncle. Los `id` llevan hash (cambian), pero los **`name` son estables** → `[name="..."]`. AutoUncle sigue siendo solo rotación/validación (A8: nunca precio de referencia), pero aporta filtro de **equipamiento** (`popularOptions`).

### 🇩🇪 Selectores AutoUncle
- **`paginas_reales.md`** — sección "Sidebar de filtros — selects y checkboxes con `name` ESTABLE": `minPrice`/`maxPrice`, `minYear`/`maxYear`, `fuelTypes` (El/El_Hybrid/Benzin/Diesel), `minKm`/`maxKm`, `gear`, y **`popularOptions`** (hasAppleCarPlay, hasAndroidAuto, hasBluetooth, hasSeatHeat, hasParkingCamera, hasParking, hasDistanceControl, hasTowBar).
- **`playbook_filtrado.md`** — tabla "Selectores ESTABLES de filtros en AutoUncle" + regla de máximo equipamiento (CarPlay + Android Auto + asientos + cámara + ACC).

## [3.1.6] - 2026-08-18 — Selectores estables de filtros kleinanzeigen.de

> **Origen:** el usuario facilitó el HTML real del sidebar de kleinanzeigen (`/s-autos/c216`). Tiene **3 tipos de filtro** con selectores distintos.

### 🇩🇪 Selectores kleinanzeigen
- **`paginas_reales.md`** — sección kleinanzeigen reescrita: Tipo 1 (atributos por URL `+autos.<attr>_s:<valor>`), Tipo 2 (rangos con `id` `brwse-attr-*`), Tipo 3 (equipamiento con `checkbox-autos.*` + botón Übernehmen `[data-cy=clickable-options-apply-button]`).
- **`playbook_filtrado.md`** — tabla "Selectores ESTABLES de filtros en kleinanzeigen.de" + regla de máximo equipamiento (sunroof+seat_heating+xenon_led+navi+full_service_history → Übernehmen).
- ⚠️ Diferencia clave: kleinanzeigen SÍ tiene botón aplicar para equipamiento (Coches.net no).

## [3.1.5] - 2026-08-18 — Selectores estables de filtros Coches.net (acordeón)

> **Origen:** el usuario facilitó el HTML del sidebar de filtros de Coches.net. A diferencia de mobile.de, **NO hay página de filtros aparte**: el sidebar (`.mt-SearchSidebar-filters`) son acordeones en el listado con **`id` estable** por grupo, y los filtros **se aplican al marcar** (en vivo, sin botón).

### 🇪🇸 Selectores Coches.net
- **`playbook_filtrado.md`** — nueva tabla "Selectores ESTABLES de filtros en Coches.net": `vehicleTypeGroup`, `makeGroup`, `priceGroup`, `onlineServicesGroup`, `locationGroup`, `sellerGroup`, `yearGroup`, `kmsGroup`, `bodyTypeGroup`, `motorGroup`, `environmentalLabelGroup`, `electricGroup`, `equipmentGroup` (equipamiento), `colorGroup`.
- **`paginas_reales.md`** — sección "Sidebar de filtros — acordeón con IDs ESTABLES" con el método (clic `groupTrigger` → marcar → se aplica solo) y el proxy de máximo equipamiento (techo solar + cámara, SIN cuadro digital).

## [3.1.4] - 2026-08-18 — Selectores estables de filtros mobile.de ES (data-testid)

> **Origen:** el usuario facilitó el HTML real de la página de filtros de mobile.de ES (`/es/s/auto`). Los `data-testid` son selectores ESTABLES (no cambian con el CSS ofuscado) → Claude puede filtrar con `page.getByTestId()` / `[data-testid=...]` sin fallar.

### 🔬 Selectores estables
- **`playbook_filtrado.md`** — nueva tabla "Selectores ESTABLES de filtros en mobile.de ES": `make-incl-0`, `model-incl-0`, `model-description-incl-0`, `category-filter`, `seats-filter`, `door-filter`, `price-filter`, `first_registration-filter`, `mileage-filter`, `condition-filter`, `maintenance_features-filter`, `seller_type-filter`, `general_inspection-filter`, `previous_owners-filter`, `country-filter`, `location-filter`, `radius-filter`.
- **Filtros de EQUIPAMIENTO (máximo equipamiento):** en Interior → `Panel de instrumentos digital` (cuadro digital), `Pantalla Head-up`, `Calefacción de asiento`, Android Auto/CarPlay, navegación; en Extras → techo corredizo/panorámico, faros LED, portón eléctrico.
- **Regla full reproducible:** marcar los 5 checkboxes (cuadro digital + HUD + calefacción asiento + techo panorámico + faros LED) para comparar full vs full.
- **`paginas_reales.md`** — sección nueva "mobile.de ES — página de filtros completa" con la estructura de las 6 secciones y los data-testid.

## [3.1.3] - 2026-08-18 — Equipamiento máximo + corrección Seat/Cupra

> **Origen:** feedback del usuario sobre el estudio de mercado: (1) comparar SIEMPRE a máximo equipamiento (la unidad DE suele venir full: cuadro digital, techo, LED — lo más demandado entre jóvenes), y (2) la conclusión "Seat/Cupra → España más barata, nunca importar" era falsa (la banda ≥20k en ambos mercados recortó la cola barata DE; Cupra suelo ES 19.500 € vs DE 15-16k).

### 🛠️ Equipamiento máximo por defecto
- **`SKILL.md`** — referencia rápida: "comparar a MÁXIMO equipamiento por defecto; un ES más barato sin ese equipamiento NO es comparable → ajustar con primas".
- **`03-informes/comparables.md`** — prima nueva de **cuadro digital** (+500-1.000 €, la más demandada) + regla de equipamiento máximo antes de dar un hueco.
- **`../estudio-mercado/SKILL.md`** — sección "EQUIPAMIENTO" con el método por portal (mobile.de `Ausstattung` sí filtra cuadro digital; Coches.net solo techo solar → proxy + 1-2 fichas de muestra).

### 🚫 Corrección regla Seat/Cupra
- **`../estudio-mercado/SKILL.md`** — sección "REGLA SEAT/CUPRA — CORREGIDA": la nacionalidad de la marca NO es criterio de arbitraje; medir precio_desde SIN banda en ambos mercados.
- **`memoria/trampas-encontradas.md`** — nueva trampa "banda en ambos mercados aplasta el hueco".
- **`memoria/mejoras-aplicadas.md`** — Mejora #16.
- **`datos_mercado.json`** (Desktop) — Cupra León corregido: suelos reales, `pendiente_fase2=true`, nota de re-medición.
- **Volcado `modelos_medidos_2026-08-17_v2.md`** — regla 3 re-corregida.

### 🧹 Limpieza
- Eliminado `estudio-mercado.skill.zip` + `.bak` anidados en la carpeta fuente de la skill (se auto-incluían al re-empaquetar).

## [3.1.2] - 2026-08-17 — Regla A21 (enlaces SIEMPRE) + FASE 0 ENTENDER reforzada

> **Origen:** insistencia del usuario en dos frentes: (1) "todo lo que haga debe venir con link a anuncios y fuentes" (la regla que más repite) y (2) mejorar la comprensión de la petición antes de buscar (FASE 0 ENTENDER).

### 🔴 Regla A21 nueva (anti-patrón #21)
- **A21 — Entregar sin enlaces (anuncio + fuentes):** TODO lo que se entregue lleva el enlace directo al anuncio (ficha del vehículo) y las fuentes con su URL. Candidatos, comparables, comparativas, informes, dossier, JSON y ZIP. Un dato sin su enlace NO se entrega como concluido.
- **Al cerrar cualquier entrega, re-verificar con lupa:** si cualquier candidato/comparable/fuente carece de enlace, el trabajo está incompleto.

### 🧠 FASE 0 · ENTENDER reforzada (comprensión antes de ejecutar)
- **📥 ACK de 1 línea obligatorio en TODO encargo** (incluso sin ambigüedad): `ENTENDIDO — [QUÉ] · [PARA QUÉ] · [ENTREGABLE] · [FLUJO]`. El usuario corrige en 1 palabra o da OK. Ver `01-arranque/guia_prompts.md` §ACK.
- **Árbol de decisión de comprensión**: QUÉ → PARA QUÉ → ENTREGABLE → ALCANCE. Solo se pregunta lo que falta; si está claro, cero preguntas.
- **Tabla de colisiones de intención**: pares que se mezclan (búsqueda vs marketing, evaluar vs buscar, un modelo vs varios...) y cómo separarlos en fases.
- Integrado en `01-arranque/planificador.md` PASO 0, `01-arranque/guia_prompts.md` y línea de flujo del `SKILL.md`.

### 📄 Archivos tocados
- `SKILL.md` (frontmatter 3.1.2, referencia rápida, §ANTI-PATRONES resumen + nota A21, §FOTOS REALES punto 5, flujo de arranque, checklist ×2) · `06-reglas/anti_patrones.md` (tabla, detalle, verificación rápida, origen) · `01-arranque/guia_prompts.md` (ACK, árbol, colisiones) · `01-arranque/planificador.md` (PASO 0) · `CHANGELOG.md`.

## [3.1.1] - 2026-08-17 — Flujo E STOCK + anti-patrones A17-A20 + auditoría ampliada

> **Origen:** primer encargo real de prueba (stock recurrente de publicaciones). Reveló que la skill fallaba en casos fuera de los 4 flujos: entregó .docx, usó AutoScout24 para precio (violando su propia trampa A8), abrió fichas en exceso, ignoró la cache y no disparó la auditoría de cierre.

### 🆕 Flujo E · STOCK (búsqueda, NO marketing)
- Nuevo flujo para "stock recurrente / catálogo bajo pedido / busca coches por categorías".
- `02-flujos/stock-marketing.md` (NUEVO): reglas duras + 3 categorías + plantilla de ficha de BÚSQUEDA + proceso con checkpoint cada X.
- **Separación crítica**: búsqueda de coches = informe de búsqueda (Markdown + PDF de marca + JSON). Marketing/anuncios = flujo posterior SEPARADO, solo si se pide explícitamente. NUNCA .docx ni copy IG/FB en el flujo de búsqueda.
- Ejemplos del briefing = ilustrativos (A19), explorar todo el segmento.

### 🛡️ Anti-patrones nuevos
- **A17 Listado-first**: en comparativas/C/E, trabajar con listados; abrir ficha solo 2-3 ejemplos. Aprovechar sellos de precio del listado.
- **A18 Equipamiento no inventado**: solo publicar equipamiento verificado; marcar lo dudoso "por confirmar".
- **A19 Ejemplos = ilustrativos**: no acotarse a los ejemplos del usuario, explorar todo el segmento.
- **A20 No mezclar búsqueda con marketing**: copy IG/FB solo si se pide explícitamente después.

### 🔧 Fixes de la prueba
- **Auditoría de cierre ampliada**: trigger a "cualquier output final" (candidato, informe stock, informe mercado, aborto) — antes solo "elegir candidato único".
- **PASO 0 cache reforzado**: aplica a TODOS los flujos, incluido E.
- **Waypoint 📍 obligatorio en cada mensaje**, sin excepción.
- **Entregable Flujo E = informe de búsqueda** (no .docx): Markdown + PDF de investigación con plantilla de marca (`assets/plantilla_pdf_marca.html`) + JSON.
- **Regla dura universal**: todo encargo → UN flujo → su camino. Si no encaja en A/B/C/D/E → PREGUNTAR, nunca improvisar.
- Tabla de flujos 4 → 5, detección automática con Flujo E primero, mapa de caminos con Flujo E, conteo anti-patrones 16 → 20.

### 📄 Archivos tocados
- `SKILL.md` (tabla flujos, detección, PASO 0, Protocolo de Mando, EL CAMINO, auditoría de cierre, anti-patrones) · `02-flujos/stock-marketing.md` (NUEVO) · `06-reglas/anti_patrones.md` (A17, A18, A19, A20) · `CHANGELOG.md`.

## [3.1.0] - 2026-08-16 — Reorganización física, Protocolo de Mando, PASO 0 cache, planificador

### 🗂️ Reorganización física de la carpeta (FASE 0)
- Carpetas numeradas por orden de lectura: `01-arranque/` · `02-flujos/` · `03-informes/` · `04-negocio/` · `05-operaciones/` · `06-reglas/` · `memoria/` · `references/` · `scripts/` · `assets/`.
- Raíz limpia: solo `SKILL.md` + `CHANGELOG.md` + `ROADMAP.md`. El índice de memoria pasó de la raíz a `memoria/MEMORIA.md`.
- **Todas las referencias cruzadas** entre MD actualizadas a las rutas nuevas (grep validado; intrafolder siguen sin ruta).

### 🧠 Memoria (FASE A)
- **`memoria/encargos.md` (NUEVO)**: registro central de encargos (cliente → modalidad M1/M2/M3 → flujo → entregables → resultado → refrescar antes de). Backfill 9 encargos.
- **`memoria/filtros-portales.md` (NUEVO)**: filtros/URLs verificados por portal (qué aplica por URL vs clic, doble pasada kW).
- **`memoria/modelos-medidos.md`**: plantilla ampliada a 12 campos (+fuentes cubiertas, +peticiones, +refrescar antes de) + entradas incompletas completadas.
- **`memoria/MEMORIA.md`**: índice de 8 archivos (antes 5), métricas v3.1.0, protocolo con `indice.json` como paso 3bis.

### 📦 Reestructuración SKILL.md (FASE B) — 1.251 → ~987 líneas
- **`01-arranque/planificador.md` (NUEVO)**: fusiona Asistente de planificación + Plan de barrido + Prompt Improver + Asesor de filtros (elimina ~190 líneas duplicadas de SKILL.md).
- Secciones movidas a compañeros: Mapa de PDFs + Rutas de guardado → `05-operaciones/operaciones.md` · Priorización ROI + Deduplicación → `02-flujos/playbook_filtrado.md` · Vendibilidad + Matriz → `03-informes/comparables.md`.

### 🔑 Protocolo de Mando (FASE D) — filosofía del usuario
- **Sustituye a "Modo Automático" y a "Micro-plan con OK"**: el usuario aprueba CADA fase (plan de fase de 3-5 líneas); dentro de la fase la IA ejecuta TODO automáticamente; pausa solo por emergencia (presupuesto 80%, fuente bloqueada tras reintentos, hallazgo crítico, desviación A14).
- De 12+ interrupciones por encargo a **4-6** (plan de fase + checkpoints de decisión CP-D/CP1/CP3).

### 🗂️ PASO 0 — CHECK DE CACHE (FASE D)
- Antes de navegar se lee `memoria/encargos.md` + `memoria/modelos-medidos.md` + `indice.json` (Desktop). Si hay informe <3 semanas → mostrar resumen + preguntar ¿delta / refrescar / nuevo?. NO re-buscar lo ya hecho.

### 🔧 Fixes de contradicciones (FASE C)
- CP2 unificado ("tras comparables, antes de veredicto"). · JSON Flujo C con nombre único `scouting_<fecha>.json`. · Umbral tramo 8-14k mínimo 10% ≠ objetivo 12%. · Resumen anti-patrones completo hasta A16 (faltaban A15/A16). · Frontmatter con `version: 3.1.0`.

### 🧪 Auditoría de consistencia + mejoras adicionales (16-ago, segunda pasada)
- **38 referencias rotas corregidas** (rutas `../` mal formadas tras la reorganización) en `01-arranque/*`, `05-operaciones/*`, `memoria/*`, `SKILL.md` y `CHANGELOG.md`.
- **`scripts/verify_skill_refs.py` (NUEVO)**: valida que todas las referencias cruzadas entre `.md` apunten a archivos existentes (0 rotas tras las correcciones).
- **`scripts/sync_indice.py` (NUEVO)**: regenera `indice.json` del Desktop desde `memoria/encargos.md` (mantiene el PASO 0 cache en sync).
- **Trigger de `memoria/vendedores-confianza.md`**: al cerrar negociación, volcar resultado del vendedor (respuesta, fiabilidad, venta/ghosting). Sección nueva en `05-operaciones/operaciones_cierre.md`.
- **Caducidad en `memoria/encargos.md`**: regla "refrescar antes de" vencida → marcar re-investigar en PASO 0.
- **Eliminada duplicación del mapa de caminos** (estaba en EL CAMINO y repetido en PROTOCOLO DE MANDO).

### 📄 Archivos tocados
- `SKILL.md` (reestructuración + Protocolo de Mando + PASO 0 + fixes) · `01-arranque/planificador.md` (NUEVO) · `memoria/encargos.md` (NUEVO) · `memoria/filtros-portales.md` (NUEVO) · `memoria/modelos-medidos.md` · `memoria/MEMORIA.md` · `05-operaciones/operaciones.md` · `02-flujos/playbook_filtrado.md` · `03-informes/comparables.md` · `scripts/verify_skill_refs.py` (NUEVO) · `scripts/sync_indice.py` (NUEVO) · `05-operaciones/operaciones_cierre.md` · `01-arranque/briefing_encargo.md` · `01-arranque/guia_prompts.md` · `memoria/retrospectiva.md` · `memoria/mejoras-aplicadas.md` · `CHANGELOG.md`.

## [3.0.0] - 2026-08-16 — Asistente de planificación + Auditoría de cierre de conversación

### 💡 ASISTENTE DE PLANIFICACIÓN — encargos abiertos/vagos
- Protocolo de 4 pasos ANTES de cualquier búsqueda: **DETECTAR** flujo → **MEJORAR** prompt (solo si vago) → **PLANIFICAR** embudo (plan de barrido + presupuesto de tokens) → **EJECUTAR** en cascada con checkpoints.
- Prompt Improver integrado en el PASO 2 (referencia a `01-arranque/guia_prompts.md`); plan de barrido integrado en el PASO 3.
- Tabla "qué herramienta cuándo" (Prompt Improver / briefing / plan de barrido / Flujo D / Flujo A) con ahorro por herramienta.
- Embudo visualizado por niveles con coste: sondeo (8) → Flujo B (15-50) → Flujo A (35-70) → ~58% de ahorro teórico vs sin embudo.
- 6 reglas duras: detectar siempre antes de buscar, mejorar solo si vago, checkpoints explícitos, cascada no todo de golpe, opción "busca tú" siempre, plan documentado en cuaderno de sesión.

### 🏁 AUDITORÍA DE CIERRE — al elegir candidato único (nueva)
- **Trigger:** usuario elige UN candidato (fin de Flujo A + ZIP) o dice "este es"/"cerramos"; también en aborto sin elección.
- **5 dimensiones:** eficiencia (real vs plan) · embudo (niveles, descartes, Fase 2 mal gastada) · correcciones del usuario (causa raíz) · checkpoints respetados/saltados · resultado final.
- **3 salidas obligatorias:** entrada en `memoria/retrospectiva.md` (plantilla de cierre nueva) · modelo elegido en `memoria/modelos-medidos.md` · ≥1 trampa/anti-patrón por fallo detectado.
- Objetivo: verificar si el embudo ahorra de verdad (el 58% teórico pasa a medirse por encargo).

### 📄 Archivos tocados
- `SKILL.md`: §Asistente de planificación (16-ago) + §Auditoría de cierre tras §Aprendizaje continuo + línea en referencia rápida + item en checklist final.
- `memoria/retrospectiva.md`: plantilla de CIERRE (5 dimensiones) añadida.

## [2.9.3] - 2026-08-15 — Sondeo D1 blindado (navegación real, filtros no modelos)

### 🔴 Búsqueda web PROHIBIDA como sondeo (A15)
- El sondeo D1 del Flujo D se hace SIEMPRE con navegación real a Coches.net + mobile.de con los filtros del encargo. La búsqueda web (snippets/agregadores) queda **prohibida como método de sondeo** — da cifras inconsistentes y contradice lo verificado con navegación real.
- Caso real 15-ago (conversación nueva): Focus ES "~9.900 €" cuando la navegación real daba 3.000-6.990 €; 308 DE "10.980-12.600 €" sin confirmar. Degradado solo con portal bloqueado + reintentos (A2/A7).

### 🎯 Sondeo por FILTROS, no por modelo (A16)
- Una pasada con los filtros del encargo devuelve **TODOS** los modelos/motorizaciones que caben. Prohibido elegir 3-4 a mano y dejar "otros por explorar" sin sondear.
- **Potencia = filtro MÍNIMO (≥Xcv):** versiones 125/130/150 valen igual; nunca sondear solo la variante tope (caso León 150cv descartando el 125cv).

### ⚡ Eficiencia: D1 enumera, NO pagina (D1a + D1b)
- **D1a · ENUMERAR modelos** (solo nombres) con 2 lecturas por mercado: **asc** (suelo, página 1 → 🟢) + **desc** (techo, página 1 → 🟡). Con asc+desc se cubre TODO el rango en 2 páginas.
- Complemento sin paginar: **facetas de marca/modelo con conteo** (enumera el mercado completo) + **semilla de modelos** `memoria/modelos-medidos.md` (segmento finito conocido, no redescubrir).
- **D1b · PRECIO-DESDE diferido**: el precio exacto por modelo no hace falta en la primera pasada; 1 consulta por modelo solo si D1a lo dejó sin precio claro.
- El anuncio individual solo se investiga cuando el embudo es pequeño (Flujo A/B). Matiz en A12 para no confundir "cubrir todo el rango" (candidatos) con "pagar cada página del sondeo" (D1).

### 📅 Rango de año aprobado se respeta (A13 extendido)
- A13 cubre ahora también usar un rango MÁS RESTRICTIVO que el aprobado (se aprobó 2012+ y se filtró 2016+). Declarar cualquier cambio ANTES de navegar, en ambos sentidos.

### 📄 Archivos tocados
- `SKILL.md`: D1 reescrito (navegación real, filtros, cobertura total, potencia mínima, rango aprobado) + reglas duras 4-6 del Flujo D.
- `06-reglas/anti_patrones.md`: A15, A16 nuevos; A13 extendido; checklist y tabla de origen actualizados.

## [2.9.2] - 2026-08-15 — Contrato de datos completo para Laravel (descripción original, equipamiento, campos del anuncio)

### 🔴 Descripción original literal (punto 8 de la auditoría)
- `anuncio.descripcion_original` = **texto literal COMPLETO del anuncio** (pegado tal cual, sin resumir ni corregir) + `descripcion_traducida` completa. Regla dura en `03-informes/contrato.md`, `SKILL.md` (reglas del ZIP) y checklist. Antes la skill no forzaba el original → riesgo de que el ZIP llegara solo con traducción/resumen.

### 🎛️ Equipamiento COMPLETO del anuncio
- `vehiculo.equipamiento` = **lista COMPLETA** de la sección `Ausstattung`/features, no solo los 15 destacados del `03-informes/informe_tecnico.md` (esa lista es solo para el informe humano).
- Motivo: Laravel lo muestra en la ficha y lo usa para el ajuste de comparable y la ficha publicitaria. Un coche mal equipado en el JSON sale más pobre de lo que es.

### 📰 Campos extra del anuncio (nuevos en contrato.md)
- `anuncio.dias_publicado` (señal de demanda: muchos días = baja rotación DE), `anuncio.tuv_vigente_hasta` (TÜV/HU, clave en importación), `anuncio.precio_publicado` vs `precio_negociado` (pista de negociación), `vehiculo.carroceria`, `vehiculo.color_interior`.
- Laravel los persiste en `Car.notes` (sin migración) — ver `ValuationImporter::buildNotes()` (6º parámetro `$a`).

### 🔗 Comparables con URL directa (punto 6)
- Reforzado en checklist del ZIP: `mercado.comparables[].url` = ficha del anuncio, nunca búsqueda/filtro. Sin URL, el importer descarta la fila.

### 🖥️ Laravel (cierre del hueco de UI)
- `Show.vue`: nuevo bloque **Equipamiento** en la pestaña Resumen (chips) — el importer ya persistía `equipment` pero la UI no lo mostraba.
- i18n: clave `cars.equipment` añadida en es/en (paridad 1272/1272, 0 missing).

## [2.9.0] - 2026-08-15 — Camino fijo, micro-plans, cuaderno de sesión y auditoría de fase

### 🧭 EL CAMINO (SKILL.md) — desambiguación total de fases
- Mapa numerado de pasos por flujo (D/B/A) + **protocolo de waypoint**: cada mensaje declara `📍 Camino: Flujo X · paso N`.
- **Protocolo de desviación:** preguntas laterales = misiones laterales; se responden y se RETOMA el paso (`↩️ Vuelvo al paso N`). Cambio de destino se declara (`🔀 Cambio de camino`).
- Anti-patrón **A14**: abandonar el camino en silencio.

### 📋 Micro-plan antes de CADA búsqueda
- No solo el plan inicial: cada ronda de navegación lleva micro-plan de 3-5 líneas + OK del usuario. Lotes coherentes agrupados; cambio de objetivo/filtros → nuevo micro-plan. Preguntar mucho está BIEN.

### 📓 Cuaderno de sesión — aprendizaje en vivo
- `informes\_sesion\sesion_<fecha>_<encargo>.md`: parámetros fijados, correcciones del usuario con hora (se aplican YA), preferencias detectadas, pendiente al cierre.
- Se relee antes de cada micro-plan. Al cierre se vuelca a `memoria/` del skill.

### 🧐 Auditoría de fase
- Checklist interno de 4 líneas al completar CADA paso: entregable guardado · camino correcto · correcciones aplicadas · cobertura real declarada. Si falla algo, se corrige antes de avanzar.

## [2.9.1] - 2026-08-15 — Auditoría de integración con Laravel (contrato ↔ código)

### 📡 Contrato alineado con el importador real (contrato.md)
- **Fotos**: la ubicación canónica es `vehiculo.fotos` (el importador las lee de ahí). `anuncio.fotos` queda como retrocompatible.
- **Comando real**: `php artisan importnex:import-valuation` (no `jj:importar`). Modelo real: `Car` (no `Valoracion`).
- **RATING_MAP real**: `favorable→favorable`, `desfavorable→unfavorable` (no good/bad).
- **Endpoints**: el API acepta JSON (`/api/import-valuation` A, `/api/import-modelo` B, `/api/import-mercado` C); el ZIP con fotos va por la ruta web `POST /cars/import-valuation`.
- **briefing-pdf**: implementado (sube PDF real), no devuelve 410.
- **`semaforo` es informativo**: `CarObserver::saving()` recalcula `traffic_light` desde costes; el valor del chat no se persiste.

### 🛡️ Mejoras de robustez en Laravel (ValuationImporter)
- `validate()` exige `vehiculo.marca` + `modelo` (evita error SQL 500 → 422 limpio) y `costes.pvp_nuevo` en Flujo A (evita IEDMT = 0 silencioso).
- Persistencia nueva: `anuncio.pais_origen` → `Car.pais_origen`; `vehiculo.co2_confirmado` → `Car.co2_confirmado` (migración `add_pais_origen_and_co2_confirmado_to_cars`).
- Verificación de IEDMT: si el importe de Claude difiere >10% del recálculo de Laravel, se añade aviso a `notes`.
- Fotos: fallback `anuncio.fotos` si no vienen en `vehiculo.fotos`.
- `/api/cierres` idempotente: mismo coche + misma fecha no duplica (retry/doble clic actualiza el registro).

### 🧪 Tests
- +8 tests nuevos (validaciones, mapeos, IEDMT, idempotencia cierre). Área importación/cierres/KPIs: 85 passed.

## [2.9.9] - 2026-08-15 — Plantillas Blade de Laravel a nivel premium + mapa de PDFs

### 🎨 Plantillas Blade (Laravel) — nivel premium
- **`informe-interno.blade.php`**: KPI cards (score global, recomendación, mediana ES, cobertura), coverage grid de fuentes con estados (OK/degradada/omitida), tarjeta de candidato, margen vs mercado, barras de score por dimensiones (SCORE_DIM) y vendibilidad, predicción de venta (4 escenarios con fila RECOMENDADA), riesgos + banderas rojas/amarillas, acciones numeradas con plazo, tabla de comparables con badges DE/ES + fila ELEGIDO, verdict-card final. `h2` con barra accent.
- **`ficha-coche.blade.php`**: KPI cards (precio, ahorro, KM, año derivados de SPEC/PRECIO/AHORRO), badge de origen DE/ES desde `cars.pais_origen`, `h2` con barra accent.
- **`folleto.blade.php`**: cabecera de documentación (tipo/origen).
- **Controladores**: docblocks con el mapa de "qué PDF genera qué y de qué esqueleto" en `PaqueteValoracionController` y `JJImportFolletoController`.

### 📚 Mapa de PDFs documentado (7 PDFs, tipos y dónde se crean)
- **`SKILL.md`** §MAPA DE PDFs, **`03-informes/contrato.md`** §Mapa de PDFs, **`docs/MAPA_PDFS.md`** (repo) e **`INSTRUCCIONES_PROYECTO.md`** (Desktop): tabla de 7 PDFs (3 Claude investigación + 4 Laravel venta) con origen y ruta de creación.
- **Tests**: `tests/Feature/PlantillasValoracionRenderTest.php` (render de informe-interno y ficha-coche con secciones premium).
- Nota: briefing PDF ya eliminado (v2.9.8); status cliente 'Briefing' y `01-arranque/briefing_encargo.md` no son el PDF.

## [2.9.8] - 2026-08-15 — Briefing PDF eliminado del ecosistema

### 🗑️ Eliminación (decisión del usuario)
- **Laravel:** eliminadas la vista `jj-import/briefing.blade.php`, las rutas `/cars/{car}/marketing/briefing` y `/api/cars/{car}/briefing-pdf`, los métodos `CarMarketingController::briefing()` y `ImportValuationApiController::attachBriefing()`, la entrada 'briefing' del listado de PDFs (`CarController::laravelPdfs`) y `briefing_pdf` (`CarDocumentDefinitions`). Borrado `BriefingPdfApiTest`.
- **Nota:** el status de cliente 'Briefing' (pipeline de ventas) NO se toca — es un estado del cliente, no un informe. `01-arranque/briefing_encargo.md` (cuestionario previo) se mantiene — NO es el PDF briefing.
- **Resultado: 7 PDFs** — 3 Claude (búsqueda global, modelo, unidad) + 4 Laravel (dossier, ficha-coche, informe-interno, folleto).

## [2.9.9] - 2026-08-16 — Rediseño premium de plantillas Blade Laravel

### 🎨 Mejoras visuales en plantillas Laravel
- **`informe-interno.blade.php`**: Añadidas KPI cards premium (4 columnas), coverage grid de fuentes con estados OK/deg/omit, barras de progreso por dimensiones SCORE_DIM y VENDIBILIDAD_FACTOR, tarjeta verdict-card final con veredicto destacado, balance A_FAVOR/EN_CONTRA, tabla de comparables con badges DE/ES y fila Pick, y pie de página "CONFIDENCIAL".
- **`ficha-coche.blade.php`**: Añadidas KPI cards premium (3 columnas), badge de origen DE/ES con indicador Pick, y KPIs derivados del campo SPEC (KM, año).
- **`folleto.blade.php`**: Mejorada cabecera premium con brand-badge, sección KPI grid (3 columnas), headers h2 con barra accent, y mejoras de estilo consistentes con la marca JJ Import Motors (estoril/asphalt/platinum).
- **`CarDocumentDefinitions.php`**: Comentario actualizado para reflejar que los reports AI son generados por Laravel (no briefing).
- **`CarDocument.php`**: Comentario actualizado para aclarar que los reports AI no son briefing PDFs deprecated.

### 📦 Empaquetado
- ZIP skill re-empaquetado v2.9.9 con backup alfabético -n.
- Push origin master con commits de todas las mejoras de esta sesión.

## [2.9.7] - 2026-08-15 — Plantilla de PDF rediseñada (nivel premium)

### 🎨 `plantilla_pdf_marca.html` v2
- Header con lockup de marca + badge de flujo + nº de informe + fecha.
- Hero con título en acento naranja + claim.
- **KPI cards** (grid de 4 métricas clave: mejor DE, coste en Huelva, mejor ES, ahorro).
- **Cobertura de fuentes en grid de tarjetas** con estados (OK verde / degradado ámbar / omitido gris).
- **Tabla de candidatos premium**: badges DE/ES, semáforo, columna de enlace, fila del elegido destacada (`pick` con borde naranja).
- **Veredicto en tarjeta de recomendación** + chips de equipamiento + footer completo (contacto + legal).
- Coherente con la marca (estoril/asphalt/platinum + accent naranja) pero con estructura propia de informes (no es el folleto).
- Aplicada de ejemplo al `informe_busqueda_tiguan.html` (verificado visualmente).

## [2.9.6] - 2026-08-15 — Milanuncios resuelto: paginación por URL (confirmado)

### 🟢 Cobertura Milanuncios al 100%
- Confirmado navegando: **`&pagina=N`** carga el listado completo y **respeta los filtros** (contenedor `.ma-AdList`, parámetro `pagina`).
- `memoria/trampas-encontradas.md`: virtualización marcada **RESUELTA** — la paginación por URL es la vía principal; el scroll infinito NO es fiable.
- `02-flujos/paginas_reales.md`: URL de filtros reales (`anoh`, `cajacambio`, `engineHpTo`, `fuels`, `hasta`, `kilometersTo`, `puertas`, `orden`) + `pagina=N` + `nextToken` (degradado).

## [2.9.5] - 2026-08-15 — Limpieza de inconsistencias residuales

### 🧹 Coherencia contrato ↔ código
- contrato.md: "Adaptado a los 4 flujos" (incluye D). Bloques del dossier-cliente se renderizan en `ficha-coche.blade.php` (no `dossier.blade.php`).
- SKILL.md: comando real `importnex:import-valuation` (no `jj:importar`); mapa del ZIP corregido.

## [2.9.3] - 2026-08-15 — Diseño de PDF unificado (plantilla de marca única)
### 🎨 Plantilla única de marca (Claude ↔ Laravel idénticos)
- Nueva `assets/plantilla_pdf_marca.html`: copia fiel del CSS de `ficha-coche.blade.php` (Inter, fondo #0f1d42, gradientes, grid, price-band naranja, CTA+QR). Claude la usa OBLIGATORIAMENTE para los PDFs de investigación → visualmente idénticos a los de Laravel.
- Método en SKILL.md §Quién genera cada PDF: rellenar `{{marcadores}}` → HTML → Chrome headless → PDF.
- Corregido en contrato.md: `dossier.blade.php` NO existe en Laravel; el documento del cliente real es `ficha-coche` (desde `ficha-publicitaria.txt`).

## [2.9.4] - 2026-08-15 — Fotos reales del anuncio, enlaces de ficha y fuentes con URL

### 📸 Fotos = descargadas del ANUNCIO, nunca capturas
- Las fotos del candidato son las imágenes reales del anuncio (URLs jpg/png/webp de la ficha), descargadas a `<coche_id>_fotos\` y en `vehiculo.fotos` (Laravel las descarga al importar).
- PROHIBIDO subir capturas de pantalla ni screenshots del listado (fallo real 15-ago Tiguan).

### 🔗 Enlaces = ficha del anuncio individual, nunca genéricos
- Toda URL de candidato/comparable es la ficha del vehículo (mobile.de `details.html?id=`, slug Coches.net, `/app/item/<id>` Wallapop).
- PROHIBIDO URLs de búsqueda/filtro, listados o dominio raíz (A6).

### 🌐 Fuentes siempre documentadas con URL
- Todo informe (búsqueda y unidad) cierra con "Fuentes consultadas": estado por fuente + enlace.
- En el JSON van en el bloque `fuentes` (o `avisos` si alguna quedó bloqueada).

### 🗂️ Organización siempre por marca/modelo
- Todo en `informes\<marca>\<modelo>\` y `laravel\export\` — nunca suelto ni en AppData.

## [2.8.0] - 2026-08-15 — Flujo D · DESCUBRIMIENTO (cliente sin modelo)

### 🔍 Nuevo flujo D con embudo de 3 pasos
- **D1 sondeo de modelos** (4-8 peticiones): peinar ES+DE solo a nivel de modelo/motorización con los filtros del encargo. Sin fichas, sin anuncios individuales.
- **D2 INFORME DE MODELOS**: organizado por país × año × motorización, con veredicto de encaje (🟢 holgado / 🟡 justo / 🔴 no cabe) y mejor mercado por modelo. Plantilla añadida. CP-D: esperar a que el usuario elija 2-3 modelos.
- **D3 embudo**: cada modelo elegido → Flujo B (7 fuentes) → candidato → Flujo A. Las peticiones crecen al bajar de nivel: sondeo (8) → B (15-50) → A (35-70).
- Origen del cambio: análisis de la conversación "María" (9.000 €, sin modelo) — el usuario propuso particionar la búsqueda: primero modelos que caben, luego investigar los que él elija.
- Detección de flujo actualizada (4 flujos) + triggers nuevos + briefing: modelo no se pregunta si hay presupuesto+requisitos (va a Flujo D).

## [2.7.0] - 2026-08-15 — Encargos abiertos: modalidades honorarios, plan de barrido y bandas de precio

### 💶 Modalidades de honorarios M1/M2/M3 (briefing_encargo + costes)
- 3 fallos reales por ASUMIR el tratamiento de honorarios: 12-ago (techo corregido a mitad), 15-ago Tiguan (tarifa reducida ES), 15-ago María ("quita el coste del servicio" leído como "descuenta" cuando era "no se cobra").
- Ahora: M1 incluidos / M2 aparte / M3 no se cobran — se pregunta SIEMPRE o se reformula la interpretación en 1 línea antes de ejecutar.

### 📋 Plan de barrido previo para encargos ABIERTOS (SKILL.md)
- Cuando el usuario pide "revisa qué hay/modelos/mercado" sin URL, NO se navega directo: se muestra el plan (mercados, filtros, bandas de precio, cobertura, entregable esperado) en 5-8 líneas y se pide OK.
- Responde al fallo real María 15-ago: medio informe PARCIAL entregado antes de que el usuario preguntara "¿qué vas a hacer?".

### 🛡️ Anti-patrones A12 y A13 (anti_patrones.md, 11→13)
- **A12** — Página 1 ordenada por precio como "listado": cubre TODO el rango del presupuesto (bandas de precio o paginación completa). Caso María: 526 resultados, solo 8 enseñados.
- **A13** — Filtros del encargo alterados en silencio (año 2016→2012): se declara ANTES de navegar.

### 📊 Bandas de precio (playbook_filtrado.md)
- Técnica nueva: recorrer el rango por bandas (3-5k/5-7k/7k-techo); el objetivo es el mejor VALOR del rango, no el precio mínimo.

## [2.6.1] - 2026-08-15 — Corregida ambigüedad de rutas (JSON vs .md)

- **SKILL.md** §DÓNDE SE GUARDA CADA COSA reescrito con tabla única QUÉ archivo va DÓNDE: `.md` del usuario en `informes\<marca>\<modelo>\`; JSON de contrato (`flujo-a/b/c`) en `laravel\export\`; ZIP en `laravel\paquetes\`.
- **Aclaración explícita:** `informe.json` NO existe suelto — va DENTRO del ZIP y lo genera `empaquetar.py` desde `export\flujo-a-<coche_id>.json`. Confusión real detectada: se buscaba un "informe de JSON" en la carpeta del modelo.
- `05-operaciones/operaciones.md` y `README.md` del Desktop actualizados con la misma tabla.

## [2.6.0] - 2026-08-15 — Estructura de carpetas por marca/modelo en el Desktop

### 📁 Ruta de guardado obligatoria (15-ago-2026)
- **SKILL.md** §DÓNDE SE GUARDA TODO: todo se guarda en `C:\Users\jacar\Desktop\JJImportMotors\informes\<marca>\<modelo>\`.
- NUNCA en `AppData\Roaming\Claude\...\outputs\` (fallo real 15-ago Tiguan: el informe se escribió ahí y el usuario no lo veía).
- Estructura por marca/modelo: `informe_busqueda_<fecha>.md` · `informe_unidad_<fecha>.md` · `<coche_id>.json` · `<coche_id>_fotos/` · `<coche_id>.zip`.
- `README.md` creado en `Desktop\JJImportMotors\informes\` documentando la estructura.
- **operaciones.md** actualizado: rutas de informes .md en `informes/`, datos/export/paquetes siguen en `laravel/`.

## [2.5.0] - 2026-08-15 — Estructura de informes por fase + tarifa ES + anti-patrones A9-A11

### 📋 Estructura de informes obligatoria por fase (15-ago-2026)
- **SKILL.md** §ESTRUCTURA DE INFORMES: cada fase produce SU entregable, en orden, sin mezclarlos y sin esperar a que el usuario los pida.
  - Fase 1 (búsqueda) → INFORME DE BÚSQUEDA + candidatos (con cobertura por fuente, NO valoración).
  - Fase 2 (avance) → INFORME DE UNIDAD solo del candidato elegido.
  - Fase 3 (cierre) → ZIP Laravel obligatorio.
- Fallo real 15-ago (Tiguan cliente): se entregó un único `.md` de valoración al final y faltaron informe de búsqueda, informe de unidad y ZIP.

### 💶 Tarifa ES reducida (15-ago-2026)
- **costes.md** §Origen ES: si la unidad está en España se cobra tarifa de gestión reducida (~500 €, validar con el usuario), NO los 1.500 € de importación.
- Aviso Canarias/Baleares (IGIC + traslado extra).

### 🛡️ Anti-patrones A9, A10, A11 (15-ago-2026)
- **A9** — No afirmar haber visto/medido algo sin comprobarlo (caso Tiguan: "sí lo vi en mi barrido" sin verificar).
- **A10** — Precio financiado como gancho en portales ES (MUY CAR/Flexicar): confirmar contado antes de la tabla.
- **A11** — Paginación completa de Coches.net ordenando por precio (`pg=` + `pf=`), no muestrear 6 de muchas páginas.
- Actualizado en `06-reglas/anti_patrones.md` (8 → 11) y `SKILL.md` checklist.

## [2.4.2] - 2026-08-12 — Cascada de informes + checkpoints Flujo B

### 🏗️ División de trabajo definitiva (12-ago-2026)
- **Investigación → Claude (Desktop)** · **Almacenamiento, gestión y actualizaciones → Laravel (importnexcore)**.
- Laravel = **repositorio único y fuente de verdad** de informes PDF, imágenes, JSON, dossier, folleto.
- Flujo: Claude investiga → sube paquete ZIP a `/api/import-valuation` → **FIN**. Laravel gestiona ver/mostrar/actualizar/iterar.
- Claude **NO consulta** lo subido. Cada nuevo encargo = nuevo chat en Claude.
- Documentado en `05-operaciones/operaciones.md` §División de trabajo · Desktop `CLAUDE.md` · Laravel `copilot-instructions.md`.
- Tras la prueba VS Code: la investigación con filtros se hace en Claude Desktop (VS Code lee pero no filtra bien, ver `memoria/retrospectiva.md`).

### 🏢 Cambio de negocio (12-ago-2026)
- **Ampliación:** JJ Import Motors ya no solo importa desde Alemania — también ofrece servicios de búsqueda y gestión **dentro de España**.
- **Modelo sin compra (reforzado):** la empresa **NO compra coches ni mantiene stock**. Solo **oferta el servicio** de búsqueda, importación y gestión con honorarios fijos. El cliente es quien compra el coche.
- **🌍 Origen DE vs ES:** si el encargo no especifica origen, buscar el modelo en **ambos mercados** y comparar dónde sale mejor (coste total puesto en Huelva). `04-negocio/costes.md` §Origen con las dos fórmulas (DE con importación, ES sin importación) + comparativa.
- Reflejado en: `SKILL.md` (frontmatter + negocio + origen) · `04-negocio/costes.md` · `01-arranque/briefing_encargo.md` (parámetro origen) · `02-flujos/extractores.md` · Desktop `CLAUDE.md`/`INSTRUCCIONES_PROYECTO.md`/`README.md` · Laravel `copilot-instructions.md` · `docs/guias/README.md`.
### � Auditoría proactiva (revisión de consistencia)- **🛡️ Regla dura #5 — COBERTURA COMPLETA (12-ago-2026):** se intentan SIEMPRE las 7 fuentes (ni más ni menos). Nunca cifras/veredicto con <7 sin marcar informe PARCIAL + preguntar. Caso real: se dieron precios con 2-3 fuentes y AutoScout24.
- **Tabla de fiabilidad por fuente** en `SKILL.md`: mobile.de (precio DE 🟢) + Coches.net (precio ES 🟢) como únicas referencias de precio; AutoUncle solo rotación; AutoScout24 SOLO contar (🔴 nunca precio, agrega feeds sin cribar); kleinanzeigen/Wallapop/Milanuncios chollos particulares.
- **Anti-patrones A7 y A8** añadidos (cobertura incompleta + AutoScout24 como precio). Total: 8.
- **Trampa documentada** en `memoria/trampas-encontradas.md` (AS24 precio engañoso, caso real 12-ago).- **📊 Plantilla COMPARATIVA** nueva en `SKILL.md` — cuando el usuario pide investigar VARIOS candidatos, primero comparativa lado a lado (precio/año/km/estado/coste/ahorro/score + banderas 🟢🟡🔴 + enlaces), luego informes individuales.
- **briefing_encargo.md** — "Encargo completo (Flujo B)" renombrado a "Encargo INCOMPLETO" (contradicción corregida: un encargo completo NO pide confirmación). Regla dura #1 matizada con "salvo que ya vengan dados".
- **briefing_encargo.md** — Flujo B "Claude automáticamente" actualizado con el pipeline real (informe MODELO → esperar elección → automático).
- **guia_prompts.md** — Regla 2: año desactualizado ("últimos 5 años 2019-2024" → "recientes").
- **anti_patrones.md** — typo "Claudeestima" → "Claude estima".
- **SKILL.md** — referencia rápida de checkpoints actualizada (CP1 = esperar elección de candidato tras informe MODELO).
- **🔁 APRENDIZAJE CONTINUO:** regla en `SKILL.md` §Aprendizaje continuo + plantilla `memoria/retrospectiva.md`. Cada conversación produce ≥1 aprendizaje; los fallos del usuario se convierten en trampa/anti-patrón/regla documentada.
- **🚗 Motores gasolina 2016+** en `04-negocio/riesgos.md` · **⚠️ Regla IEDMT** en `04-negocio/costes.md` (no estimar de oído) · **🧠 preferencias de negocio** en proyecto (`preferencias.md`).

### �🔄 Cascada de informes (regla dura nueva)
- **SKILL.md** §CASCADA DE INFORMES: los informes NO salen todos a la vez. Flujo B entrega INFORME MODELO + top 5 con enlaces + CP1 → usuario elige → se convierte a Flujo A → informe UNIDAD → dossier → ZIP.
- **NUNCA** saltar del resumen informal al "¿evalúo el candidato X?" sin entregar el INFORME MODELO completo + enlaces + CP1 (caso real 12-ago: se saltó).
- **Operaciones.md** Flujo B actualizado: CP1 obligatorio entre Fase 1 y Fase 2.

### ⚡ MODO AUTOMÁTICO EN CASCADA
- **SKILL.md** §MODO AUTOMÁTICO: Fase 1 automática → INFORME MODELO + top 5 → **el USUARIO elige candidato** (no Claude) → resto automático (fotos + informe UNIDAD + dossier + ZIP). Si varios candidatos → comparativa antes.
- Solo pausa en: veredicto 🟡/🔴, bandera crítica de seguridad (VIN ausente / no declara accidentes), o encargo incompleto.
- NUNCA preguntar "¿qué candidato investigo?" — se entrega el informe MODELO y se espera la instrucción del usuario.
- **briefing_encargo.md** Paso 3: excepción de modo automático cuando no falta ningún crítico.
- **operaciones.md** Flujo B: Fase 1 termina en informe MODELO; tras elegir candidato todo es automático.

### 📋 Plantilla INFORME TIPO MODELO
- Nueva plantilla completa en `SKILL.md` (cobertura 7 fuentes + mediana/cuartil ES/DE + vendibilidad 5 factores + top 5 con enlaces + coste puesto en Huelva).

### 🏷️ Aclaración de quién genera cada PDF
- Claude genera esqueletos `.txt` [MARCADOR] en el ZIP. Los PDFs finales (`dossier`, `ficha-publicitaria`, `folleto`) los genera **Laravel** (Blade+Browsershot) cuando el coche está en inventario. Claude NO genera PDFs ni folleto durante la investigación.

### 🧹 Limpieza de empaquetado
- Excluidos del ZIP los builds (`\.(zip|skill)$`) y `lista.txt` — se auto-incluían duplicando el tamaño (252KB→503KB). ZIP ahora generado FUERA del directorio fuente.

---

## [2.4.1] - 2026-08-12 — Guía de uso para usuarios finales

### 📚 docs/guias/
- `README.md` — índice + diagrama de flujo del negocio (mermaid).
- `01-primeros-pasos.md` — arranque, verificación sync, token budget.
- `02-flujo-a-unidad.md` / `03-flujo-b-modelo.md` / `04-flujo-c-mercado.md`.
- `05-informes.md` — leer informes + dossier del cliente.
- `06-cierre-venta.md` — registrar cierres (curl) + KPIs en `/kpis`.
- `07-solucion-problemas.md` — FAQ y troubleshooting.
- `ejemplos/` — casos reales Astra OPC + Tiguan (Flujo A), Golf GTI + scouting (B/C).

---

## [2.4.0] - 2026-08-12 — Auditoría 3 (16 hallazgos, 100% resueltos)

### 🟠 Alto
- **#1 Multi-tenant ScoutingMercado** — `scouting_id` ahora unique PER organización (migración `2026_08_12_110000_make_scouting_unique_per_organization`). `storeMercado()` hace upsert scoped por org. Test de colisión entre 2 orgs.
- **#2 Enlace dashboard KPIs** — `cars.show` ahora usa `car_id` (numérico) en vez de `coche_id` (slug), con fallback a texto plano.
- **#3 Fechas en cierres** — `storeCierre()` valida formato `YYYY-MM-DD` (422 en vez de 500). Tests de fechas inválidas.

### 🟡 Medio
- **#4 Validaciones negocio→422** — `RuntimeException` de `ValuationImporter::apply()` se responde como 422, no 500.
- **#6 attachBriefing org** — verifica que el coche pertenece a la org autenticada (404 si no).
- **#7 Flujo C lectura** — nuevo `GET /api/scouting` (listado por org con modelos).
- **#8 down() scouting** — migración original ahora dropea `modelos_mercado` antes que `scouting_mercado`.
- **#9 Tests huecos** — `KpiCalculatorTest` (5 tests), aislamiento multi-org en `/api/kpis`, fechas/veredicto inválidos en cierres, colisión scouting_id, `index_scouting`.
- **#10 golden-tests** — §5.1/§5.2 actualizados (B/C implementados, `co2_confirmado` validado).

### 🟢 Bajo
- **#11 DRY token** — nuevo middleware `import-token` centraliza auth (X-Import-Token + org) en los 9 endpoints del puente.
- **#12 Índice plataforma** — migración `2026_08_12_120000_add_plataforma_index_to_cierres_table`.
- **#13 Veredicto validado** — `storeCierre()` valida contra valores del contrato (422 si no).
- **#14 Throttle** — `POST /api/import-valuation` movido a `throttle:api-write`.
- **#15 Skill docs** — sección "KPIs en Laravel (endpoint + dashboard)" en operaciones_cierre.md.
- **#16 use Schema** — añadido import en `EnrichedValuationMigrationTest` (limpia linter).

---

## [2.3.0] - 2026-08-12 — Fixes auditoría integral (17 hallazgos)

### 🔴 Críticos corregidos
- **#1 IEDMT**: coeficientes Anexo IV extraídos a `config/iedmt.php` (single source of truth). Corregidos 5 valores incorrectos en `Car::calculateIEDMT()` (año 10: 14% → 17%, etc.). Añadido `tests/Unit/IedmtCalculationTest.php` (7 tests).
- **#2/#9 Relación Cierre↔Car**: `brand`/`model` denormalizados en tabla `cierres` (migración `2026_08_12_100000_add_brand_model_to_cierres_table`) + poblados en `storeCierre()`. Eliminada búsqueda por `slug` inexistente. El filtro por marca en dashboard ya funciona.

### 🟠 Rendimiento y mantenibilidad
- **#3 N+1**: eager load `with('car')` en KpiController.
- **#4 Bucle tendencia**: unificado en `KpiCalculator::historico()` (N meses, clamp 1-24).
- **#5 Duplicación KPIs**: nuevo `app/Services/KpiCalculator.php` — fuente única para `KpiController` (web) y `ImportValuationApiController::kpis()` (API).
- **#6 Test flaky**: fechas distintas en fixtures de `KpiControllerTest`.

### 🟡 Robustez
- **#8 schema_version**: `ValuationImporter` guarda la versión real del payload, no hardcodeada.
- **#11 Tests `attachBriefing`**: nuevo `BriefingPdfApiTest` (5 casos: PDF válido, no-PDF, >10MB, sin archivo, sin token).

### 🟢 Documentación
- **#12 Mapeo `valoracion`→`rating`**: documentado en contrato.md (intencional: Car normaliza, Cache guarda crudo).
- **#13 `boe_confirmed`**: documentado (no activo hoy; solo futuro BOE).
- **#15 ROADMAP**: sección "Lado Laravel (completado)" añadida.

### ⚪ Verificados (falsos positivos de auditoría)
- **#7 Throttles**: `api-read`/`api-write`/`api-heavy` YA definidos en `AppServiceProvider` (RateLimiter::for). Sin acción.
- **#10 CHANGELOG**: ya existe (este archivo). Sin acción.

---

## [2.2.0] - 2026-08-12 — Contador token budget y dashboard KPIs

### 🔢 §2.4 — Contador de peticiones (tracking manual)
- **Nueva sección** `### Contador de peticiones (§2.4)` en `SKILL.md` tras Token budget
- Contador por fuente: mobile.de X/45 (avisar a 35), AutoScout24 X/36, Coches.net X/35, resto X/20
- Reglas por flujo: A máx 70 (avisar 35/56), B máx 50 (avisar 25/40), C máx 100 (avisar 50/80)
- Regla dura: NUNCA >45 peticiones a mobile.de en una sesión
- Si se supera el budget sin veredicto → STOP + resumen parcial + decidir PARCIAL

### 📊 §3.8 — Dashboard KPIs en Laravel (frontend)
- **`app/Http/Controllers/KpiController.php`** (nuevo, invokable)
- **`resources/js/Pages/Kpis/Index.vue`** (nuevo)
- **Ruta** `GET /kpis` (`kpis.index`) bajo `['auth', 'verified', 'organization']`
- 4 KPIs por periodo con navegación mes a mes: precisión de veredictos (≥80%), tiempo hasta venta (≤15d), desviación de precio (≤5%), falsos positivos (≤20%)
- Tendencia de precisión últimos 6 meses + tabla de cierres con desviación y estado
- Cards con semáforo verde/ámbar/rojo según objetivo; colores marca (`estoril-700`)
- Enlace `KPIs` añadido al menú lateral (grupo Inventario) con clave i18n `nav.kpis` (es/en)
- Requiere la migración `2026_08_12_092939_create_cierres_table` aplicada en producción

---

## [2.1.0] - 2026-08-12 — Documentación §3.7 y refactor §2.3

### ✅ §3.7 — Documentación `verify_desktop_sync.py`
- **Nueva sección** `## ✅ Verificación de sincronización Desktop (ARRANQUE)` al inicio de `05-operaciones/operaciones.md`
- Documenta comando (`py .claude/skills/.../verify_desktop_sync.py`), qué verifica (12 scripts + 2 datos), output exitoso y output con faltantes
- Integra con flujo Claude: ejecutar **siempre** al inicio de sesión antes de leer `indice.json` o invocar `franja.py`
- Exit code 0 = sesión OK; exit code ≠ 0 = NO arrancar

### 📐 §2.3 — Single source of truth IEDMT
- `03-informes/contrato.md` §`costes`: el bloque IEDMT ahora referencia [`04-negocio/costes.md §IEDMT`](04-negocio/costes.md#-iedmt-orden-hac15012025-vigor-1-ene-2026) en lugar de duplicar la fórmula
- `iedmt_metodologia` redefinido: cadena corta con PVP/antigüedad/CO₂/cifras resultantes (ejemplo real), sin desglose de fórmula
- Single source of truth mantenida en `04-negocio/costes.md` (Orden HAC/1501/2025)

---

## [2.0.0] - 2026-08-12 — Auditoría completa y hardening del skill

### 🛡️ Seguridad multi-tenant (§10.3, §10.5)
- **Migración** `2026_08_12_090058_add_organization_and_soft_deletes_to_investigation_cache_table.php`:
  - Añade `organization_id` (foreign key) a `investigation_cache`
  - Añade `deleted_at` (soft deletes)
  - Cambia índice único a compuesto `(organization_id, clave_modelo)`
- **Modelo** `InvestigationCache`:
  - Trait `SoftDeletes`
  - Relación `organization(): BelongsTo`
  - Método `aspectosCaducados()`

### 🎯 Endpoints robustos (§10.1, §10.2, §10.6, §10.7)
- **`attachBriefing()`** implementado (antes la ruta existía pero el método no — 500 error)
- **`storeInvestigationCache()`** y **`getInvestigationCache()`** scoped por organización
- **`validate()`** refactorizado con nueva firma: `validate($payload, $requiredBlocks, $expectedFlujo)`
- Mensaje de error mejorado en GET sin parámetros (muestra qué parámetros faltan + ejemplo)
- Validación de schema_version + flujo + bloques mínimos en los 3 endpoints (A, B, C)

### ✅ Validaciones de negocio (§3.1, §3.2, §3.3)
- **`co2_confirmado: false`** → warning automático en avisos
- **Comparables sin URL** → filtrados con aviso "{N} comparables descartados"
- **`precio_objetivo` obligatorio** cuando recomendación es "Comprar si baja de precio"

### 📐 Consistencia del skill (§1.1)
- **Umbrales Nicho unificados**: 10% objetivo, 8% mínimo (EXIT 3)
- Comportamiento entre 8-10% documentado: "margen justo, posible si vendibilidad ≥70"

### 📊 Modelo InvestigationCache completado (§1.2)
- Añadidos aspectos `precio_mercado => 18 meses` y `otros => 24 meses`
- Total: 9 aspectos con caducidades definidas

### Tests
- **61 tests pasando (231 aserciones)**
- 3 nuevos tests de validación de negocio (3.1, 3.2, 3.3)
- 10 tests unitarios de UrlNormalizer (§2.1)
- 10 tests de CierreApi (§3.5)
- 3 tests de mapeo drivetrain (§3.4)
- Tests actualizados para reflejar validaciones multi-tenant

### 🚗 Mapeo `traccion` → `drivetrain` (§3.4)
- **Nueva columna** `drivetrain` en tabla `cars` (después de `transmission`)
- **Nuevo mapa** `DRIVETRAIN_MAP` en `ValuationImporter` con traducciones español/inglés/alemán → valores canónicos `FWD`/`RWD`/`AWD`
- **3 tests** cubriendo tracción delantera, total/AWD y ausencia del campo
- **Modelo Car**: `drivetrain` añadido a `$fillable`
- **Migración**: `2026_08_12_093456_add_drivetrain_to_cars_table.php`

### 📊 Endpoint `/api/cierres` (§3.5)
- **Nuevo modelo** `Cierre` con SoftDeletes, scopes (`periodo`, `vendidos`, `veredictoPositivo`) y helpers (`desviacionPorcentaje`, `calcularDiasHastaVenta`)
- **Nueva migración** `2026_08_12_092939_create_cierres_table.php` con índices para queries KPI
- **POST `/api/cierres`** — registra cierre de venta (o no-venta) con cálculo automático de días y desviación
- **GET `/api/cierres`** — lista cierres con filtros (periodo, estado, veredicto_positivo) y KPIs agregados (precisión, tiempo medio, desviación, falsos positivos)
- Prerrequisito completado para §3.8 (dashboard KPIs)

### 🔧 Optimización §2.1 — UrlNormalizer helper
- **Nueva clase** `app/Support/UrlNormalizer.php`
- Métodos: `normalize(?string $url): ?string` y `same(?string $url1, ?string $url2): bool`
- Extraída lógica duplicada de `ValuationImporter::resolveCar()`
- 10 tests unitarios cubriendo edge cases

### 📐 Documentación (§1.4, §1.5)
- **§1.4 — Single source of truth de costes fijos:** SKILL.md ahora referencia costes.md en lugar de duplicar valores
- **§1.5 — Filtro de competencia:** Documentado cuándo aplicar (Fase 2 de Flujo A, después de recolectar las 3 fuentes ES)

### Archivos modificados
- `database/migrations/2026_08_12_090058_*.php` (nueva — investigation_cache multi-tenant)
- `database/migrations/2026_08_12_092939_create_cierres_table.php` (nueva — §3.5)
- `database/migrations/2026_08_12_093456_add_drivetrain_to_cars_table.php` (nueva — §3.4)
- `app/Models/InvestigationCache.php`
- `app/Models/Cierre.php` (nuevo — §3.5)
- `app/Models/Car.php` (drivetrain añadido a fillable — §3.4)
- `app/Http/Controllers/Api/ImportValuationApiController.php`
- `app/Services/ValuationImporter.php`
- `app/Support/UrlNormalizer.php` (nuevo — §2.1)
- `routes/api.php`
- `.claude/skills/importacion-vehiculos/SKILL.md`
- `.claude/skills/importacion-vehiculos/comparables.md` (§1.5)
- `tests/Feature/ValuationImporterTest.php` (+3 tests §3.4)
- `tests/Feature/ModeloImportTest.php`
- `tests/Feature/ScoutingMercadoImportTest.php`
- `tests/Feature/InvestigationCacheTest.php`
- `tests/Feature/CierreApiTest.php` (nuevo — §3.5)
- `tests/Unit/UrlNormalizerTest.php` (nuevo — §2.1)
- `tests/Feature/fixtures/chat_report_example.json`

### Documentación
- `docs/auditoria-skill-2026-08-12.md` — 12 secciones con 43 items detectados + 11 críticos resueltos

## [1.13.0] - 2026-08-11

### Sprint G, H e I completados (25/26 mejoras, 96%)

#### Added
- **Registro de cierre** (#15): Estructura JSON para tracking de cierres reales vs estimados en `datos/registro_cierres.json`
- **KPIs del skill** (#16): 4 métricas clave (precisión de veredictos, tiempo hasta venta, desviación de precio, tasa de falsos positivos)
- **Changelog formal** (#17): Este archivo, versionado según Semantic Versioning
- **Sincronización Desktop** (#20): Script `verify_desktop_sync.py` para verificar que los scripts referenciados existen en Desktop

#### Fixed
- Documentación completa de las 5 mejoras pendientes de Sprints G, H e I
- SKILL.md ahora incluye secciones operativas para registro de cierres, KPIs y sincronización

### Notas
- Solo falta #21 (caché de investigación en Laravel) que requiere backend
- Progreso global: 25/26 mejoras (96%)
- SKILL.md: 485 líneas (+118 desde v1.12.0)

## [1.12.0] - 2026-08-11

### Sprint E completado (21/26 mejoras, 81%)

#### Added
- **Priorización por ROI** (#2): Scoring automático con fórmula `MargenEstimado × VendibilidadEstimada × Urgencia`
- **Comparable sin muestra** (#3): 3 métodos en cascada (Normal → Ampliado → Cualitativo)
- **Deduplicación** (#4): Huella normalizada `(año, km±2%, cv, precio±3%, combustible)` para no contar 2 veces el mismo coche

### Changed
- SKILL.md: 367 líneas (+47 desde v1.11.0)
- Sección "Fases" ahora incluye prioridad por ROI y deduplicación
- Sección "Comparable" ahora incluye método sin muestra

## [1.11.0] - 2026-08-11

### Fase 11: Endpoint B (MODELO) implementado

#### Added
- Método `storeModelo()` en `ImportValuationApiController`
- Ruta `POST /api/import-modelo` para Flujo B
- 5 tests con 13 aserciones para endpoint B
- Validación de `_meta.flujo = "B"` y eliminación automática de bloque `publicidad`

### Fixed
- Cierre completo de I14 (inconsistencia endpoints B/C Laravel)
- Todos los endpoints (A, B, C) ahora implementados y probados

### Changed
- docs/analisis-skill-importacion-vehiculos.md actualizado con changelog v1.6.0

## [1.10.0] - 2026-08-11

### Fase 10: Endpoint C (MERCADO) implementado

#### Added
- Migración `2026_08_11_205511_create_scouting_mercado_table.php`
- Tablas `scouting_mercado` y `modelos_mercado`
- Modelos `ScoutingMercado` y `ModeloMercado`
- Método `storeMercado()` en `ImportValuationApiController`
- Ruta `POST /api/import-mercado` para Flujo C
- 10 tests con 30 aserciones para endpoint C

### Fixed
- Cierre parcial de I14 (endpoint C implementado, B pendiente)

## [1.9.0] - 2026-08-11

### Fase 9: Mejoras #22 y #23

#### Added
- **Token budget consciente** (#22): Tabla de peticiones por flujo (A: 70, B: 50, C: 100)
- **Dimensiones de atractivo** (#23): 4 categorías para Flujo C (pasionales, premium, económicos, eco)

### Fixed
- Conversión de `EnrichedValuationTest.php` de Pest a PHPUnit (10 tests, 3 pasan, 7 skipped)

### Changed
- SKILL.md: 320 líneas (+18 desde v1.8.0)

## [1.8.0] - 2026-08-11

### Fase 8: Golden tests reales

#### Added
- `docs/golden-tests/README.md` con 6 informes reales (2 modelos, 3 veredictos)
- Casos: Astra OPC 2012/2013 (Comprar), Tiguan 1.4/1.5 TSI ×4 (Descartar)
- Validación de edge cases: <5 comparables ES, margen negativo

### Fixed
- Cierre de Mejora #19 (golden tests) y Deuda D3

## [1.7.0] - 2026-08-11

### Fase 7: Consolidación del documento de análisis

#### Changed
- docs/analisis-skill-importacion-vehiculos.md: 1299 → 741 líneas (-43%)
- Estructura: Histórico (§3-6, 10) + Activo (§11-12) + Referencia (§0-2, 7-9)
- Eliminadas redundancias entre secciones 10-14 y 15-17

## [1.6.0] - 2026-08-11

### Fase 6: Refinamiento final

#### Added
- anti_patrones.md separado (88 líneas) con detalle de las 6 reglas duras
- Criba diferenciada Fase 1 (soft) vs Fase 2 (dura)
- Scripts deprecados marcados con ❌
- Caché de investigación formalizado en operaciones.md
- Endpoints B/C avisados como pendientes

### Fixed
- I8 (contradicción hidratación), I9 (criba por fase), I10 (scripts deprecados), I12 (caché formalizado)

## [1.5.0] - 2026-08-11

### Fase 5: JSON distintos por flujo

#### Added
- Estructura JSON diferenciada para Flujos A, B y C en contrato.md
- Campo `iedmt_sin_minoracion` para verificación fiscal
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
- Fix bug `__S` (año 2026 hardcoded → `getFullYear()+1`)
- Edge cases documentados (mobile.de bloqueado, NL/BE/LU, checkpoint "no", CHF)

### Fixed
- I1 (extractores sin fases), I4 (año 2026), I5 (obligatoriedad mal), D1 (bug __S)

## [1.2.0] - 2026-08-11

### Fase 2: Contrato JSON formalizado

#### Added
- contrato.md con estructura completa del JSON
- Formato esqueleto [BLOQUE] documentado
- Datos de marca integrados
- Tabla de 9 aspectos de investigación

### Fixed
- I2 (IEDMT), I3 (cobertura), I6 (JSON Flujo C)

## [1.1.0] - 2026-08-11

### Fase 1: Refactorización inicial

#### Added
- 3 flujos (A/B/C) con detección automática
- 2 fases con 3 early exits
- 6 anti-patrones bloqueados (A1-A6)
- ZIP cristalino para Laravel
- Referencia rápida al inicio del SKILL.md

#### Changed
- SKILL.md: 827 → 320 líneas (-61%)
- 4 archivos modulares: extractores.md, contrato.md, operaciones.md, anti_patrones.md

### Fixed
- F1 (3 flujos), F2 (detección), F3 (3 informes), T1 (2 fases), T2 (delta updates)
- A1+A2 (anti-patrones 6), C2 (teléfono incorrecto 667→675)
- I2 (IEDMT), I3 (cobertura), I6 (JSON Flujo C), I7 (extraer anti-patrones)

## [1.0.0] - 2026-08-10

### Versión inicial

#### Added
- SKILL.md monolítico (827 líneas)
- Sistema de 7 fuentes (3 ES + 4 DE)
- Investigación de 9 aspectos
- Comparable con 9 claves y ajuste línea a línea
- IEDMT con minoración art.69
- Matriz de decisión (vendibilidad × margen)

### Known Issues
- Código JS inline (~100 líneas)
- Sin contrato JSON formal
- Sin formato esqueleto documentado
- Sin datos de marca
- Color inconsistente (#0B1F3A vs #1A306D)
- Teléfono incorrecto (667 vs 675)
