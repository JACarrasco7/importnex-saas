# Extractores, URLs y navegación con computer use

> Cargar cuando se necesite navegar un portal, construir URLs o extraer datos.
> **Método:** computer use de Claude (extensión de navegador). NO hay `fetch`,
> `Runtime.evaluate`, `web_fetch` ni `browser_batch`. Lo único que existe es:
> **ver (screenshot), clicar, escribir, tecla, scroll, esperar, zoom**.
> Todo dato que no aparezca en la captura NO se puede leer → método degradado.
> Adaptado a los **3 flujos** (A/B/C) y al **sistema de fases** (1=sondeo, 2=profunda) del SKILL.md.

---

## 🛠️ Herramientas reales de computer use

| Acción | Uso | Truco |
|---|---|---|
| `screenshot` | Ver la página actual | **Siempre tras cada paso** (regla Anthropic) |
| `left_click [x,y]` | Clic en coordenadas | Describir el elemento posicionalmente para precisión |
| `type "texto"` | Escribir en un campo | Tras clic en el campo; Enter para buscar |
| `key "ctrl+l"` | Atajos: focus URL, Tab, Enter, Escape | **Dropdowns y scroll: usa teclado** |
| `scroll` | Bajar/subir con cantidad | Si no funciona → `Page Down` |
| `wait` | Pausa entre acciones | Tras navegar (2-3 s) y tras clic en filtros |
| `double_click` / `right_click` | Casos puntuales | — |
| `zoom` | Ver región pequeña a resolución completa | Para precios/km diminutos o texto denso |

**Regla de oro (documentada por Anthropic):** después de cada paso, captura y
 evalúa si conseguiste el resultado. "¿Se aplicó el filtro? ¿Cargó la ficha?"
 Si no → reintenta. Solo avanza cuando confirmas. Esto evita inventarse resultados.

**Clics:** si fallan, di el elemento de forma posicional y/o haz zoom antes.

---

## 🧭 Patrón de trabajo (todas las fuentes)

```
1. Navegar a la URL (key ctrl+l → type → Enter)
2. wait 2-3 s → screenshot (¿cargó? ¿cookie banner?)
3. Aceptar cookies si aparecen (click posicional)
4. Aplicar filtros con clics nativos → screenshot verificar
5. Ordenar por precio → screenshot
6. Leer tarjetas visibles (precio, año, km, CV, versión)
7. Scroll / Page Down → leer más (hasta muestra suficiente)
8. Abrir fichas top → screenshot por ficha → leer secciones
9. Verificar con screenshot antes de registrar cualquier dato
```

NUNCA registres un dato que no hayas visto en una captura.

---

## 📋 Resumen por fuente

| Fuente | País | Dato clave | A: F1 | A: F2 | B: F1 | B: F2 | C: F1 |
|---|---|---|---|---|---|---|---|
| Coches.net | ES | Mediana ES + tasación (visible en ficha) | ✅ | ✅ | ✅ | ✅ | ✅ |
| mobile.de | DE | Mediana DE + N uds + fichas | ✅ | ✅ | ✅ | ✅ | ✅ |
| AutoUncle | DE | Días publicado + portal origen | ✅ | ✅ | ✅ | ✅ | ✅ |
| Wallapop | ES | Chollos + rotación | ❌ | ✅ | ❌ | ✅ | ❌ |
| Milanuncios | ES | Contado vs financiado | ❌ | ✅ | ❌ | ✅ | ❌ |
| AutoScout24.de | DE | Solo contar. NUNCA referencia precio | ❌ | ✅ | ❌ | ✅ | ❌ |
| kleinanzeigen.de | DE | Chollos particulares (`VB`) | ❌ | ✅ | ❌ | ✅ | ❌ |
| km77 | ES | PVP, CO₂, tipo IEDMT, etiqueta DGT | Solo Flujo A | Solo Flujo A | — | — | — |

> **Regla:** Las 3 obligatorias en Fase 1 son Coches.net, mobile.de, AutoUncle. Las 4 restantes (Wallapop, Milanuncios, AS24, kleinanzeigen) entran en Fase 2. **En Flujo C NO hay Fase 2** — solo las 3 de Fase 1 por modelo.

---

## 🔗 URLs por fuente

```
Coches.net   /segunda-mano/coches/<marca>-<modelo>?pg=<pagina>   ← formato REAL (verificado 12-ago)
Milanuncios  /coches-de-segunda-mano/?s=<marca>%20<modelo>%20<version>
Wallapop     /app/search?keywords=<marca>%20<modelo>%20<version>&category_ids=100
AutoUncle    /es/coches-segunda-mano/<Marca>/<Modelo>/f-gasolina/g-manual
                ?s[min_year]=&s[max_km]=&s[min_hp]=
AutoScout24  .es /lst/<marca>/<modelo>?atype=C&desde=<anio>&powerfrom=&powerto=&powertype=kw
             .de /lst/<marca>/<modelo>?atype=C&fregfrom=<anio>...  ← fregfrom, no desde
km77         /coches/<marca>/<modelo>/<anio-gama>/<carroceria>/<acabado>/<version>/datos
mobile.de    /fahrzeuge/search.html?...&lang=de  ← siempre lang=de
```

**⚠️ Coches.net:** la URL antigua `/<marca>-<modelo>-segunda-mano/` **redirige a
noticias**. Usa `/segunda-mano/coches/<marca>-<modelo>`.
**📖 Estructura real de cada página (qué ver en la captura): ver `paginas_reales.md`**

**Tabla maestra de marcas:** se obtiene navegando el buscador de Coches.net
(campo marca → desplegable). No es JSON: es lo que ves al teclear.

---

## 🧭 Dónde están los datos VISIBLES por portal

> Con computer use solo lees lo que el usuario vería. Mapa visual por fuente.

### 🇪🇸 Coches.net

- **Listado:** tarjetas con título, etiqueta de precio, precio, año, km, cv, ciudad. Orden por precio (selector). Paginación clicable abajo.
- **Tasación:** en la ficha del anuncio hay un bloque "Precio tasado" visible → léelo en Flujo A cuando haga falta.
- **`priceRankIndicator` ES VISIBLE** en la tarjeta: "Buen precio" / "Precio justo" / etc. (verificado 12-ago).
- **`publicationDate` / `priceDrop`:** no siempre visibles → si no se ven en la ficha, no se fuerzan (método degradado declarado). Rotación se infiere de los días visibles.

### 🇪🇸 Wallapop

- Búsqueda con keywords. Tarjetas: precio, año, km, CV, descripción.
- **Scroll infinito:** `Page Down` varias veces hasta agotar o ~20-25 anuncios.
- Anuncios sin año/km completos → `man`, no descartar.

### 🇪🇸 Milanuncios

- Tarjetas: precio, año, km, **"financiado"/"contado"**, fecha, descripción.
- Financiados inflan el precio → descuéntalo mentalmente. Paginación abajo.

### 🇩🇪 AutoScout24.de

- Contador arriba ("X Anzeigen"). Tarjetas: precio, km, año, CV, cambio.
- Orden por precio con el selector. **Solo contar y validar hueco. NUNCA fijar
  precio de referencia.**
- Consentimiento cookies → clic "Accept"/"Zustimmen".

### 🇩🇪 AutoUncle (es)

- Tabla de anuncios: precio, año, km y **"publicado hace X días"** + portal origen.
- Filtros laterales: año, km, potencia. Agrega varios portales → joya para días
  publicado. **Nunca fuente única DE** (cruza con mobile.de/AS24).

### 🇩🇪 kleinanzeigen.de

- `https://www.kleinanzeigen.de/s-<marca>-<modelo>/k0` + filtros.
- Tarjetas: precio, km, año, "Privat"/"Gewerblich". Precio `VB` = negociable.
- Pesado con bots: navega lento, 1 búsqueda por sesión máx.

### 🏁 km77 (solo Flujo A)

- Ficha de datos del modelo: **PVP**, **CO₂ g/km**, tipo IEDMT, etiqueta DGT.
- Si no está la versión exacta → la más cercana, anotar "versión aproximada".
- CO₂ faltante en mobile.de → estimar y DECIRLO (anti-patrón A3).

---

## 📡 Cobertura por flujo y fase — QUÉ MEDIR Y QUÉ NO

### Flujo A (UNIDAD) — coche concreto
- **Fase 1:** Coches.net (mediana ES + tasación) + mobile.de (mediana DE + N uds) + AutoUncle (ratio días publicado)
- **Fase 2:** Añadir Wallapop, Milanuncios, AS24.de, kleinanzeigen.de + fichas individuales de los top 15-25 candidatos en mobile.de + km77 para CO₂/PVP
- **Output:** 7 fuentes completas + informe UNIDAD + ZIP

### Flujo B (MODELO) — peinar un modelo
- **Fase 1:** Mismas 3 fuentes que A (Coches.net + mobile.de + AutoUncle)
- **Fase 2:** Las 4 restantes + fichas top 15-25 + km77 si hay versión conocida
- **Output:** 7 fuentes + informe MODELO + top 5

> **🌍 Origen DE vs ES (12-ago-2026):** las fuentes ES (Coches.net, Wallapop, Milanuncios) ya NO son solo comparables de venta — también son fuentes de **compra** (coche nacional). Si el origen no está especificado, medir ambos mercados y comparar dónde sale mejor. Ver `costes.md` §Origen.

### Flujo C (MERCADO) — escanear N modelos
- **Solo Fase 1** por modelo (3 fuentes). No hay Fase 2.
- **Output:** Tabla BUSQUEDA con N filas (uno por modelo)

---

## 🚗 mobile.de — navegación

### Pasada 1 — listado

URL: `/fahrzeuge/search.html?dam=false&isSearchRequest=true&ms=<make>;<model>;<modelGroup>;<desc>&p=<min>:<max>&ml=:<kmMax>&fr=<anio>:&pw=<minKW>:<maxKW>&s=Car&vc=Car&lang=de`

**⚠️ IMPORTANTE:** La URL base usa `suchen.mobile.de`, pero ese subdominio puede bloquearse. Si falla, re-navega a `www.mobile.de/fahrzeuge/search.html?...`. Orden de reintento: `www.mobile.de` → recarga + espera → `bloqueada (captcha/denegado, N intentos)`.

**En el screenshot del listado lee:**
- **Contador de resultados** (arriba: "X Ergebnisse") → es el N de muestra.
- **Tarjetas:** título, precio (gross; si comercio, `zzgl. MwSt.`/neto), km, año (Erstzulassung MM/JJ), CV (PS), cambio, ciudad, `Fahrzeughalter` a veces.
- **Filtros columna izquierda:** clic en "Erstzulassung von", "Kilometer", "Preis", cambio (Schaltgetriebe/Automatik), "Nur gewerbliche Anbieter".
- **Orden:** selector "Sortieren" → "Preis aufsteigend" (para ver la base, como hacía `sb=p&od=up`).
- **Patrocinados:** suelen llevar "Anzeige"/"Sponsored" → no contarlos en la muestra.

**Sesgo de versión base:** ordenar por precio sube la versión base. Acota con `pw=<minKW>:<maxKW>` (potencia kW) en la URL o el filtro de potencia.

### Pasada 2 — fichas (solo Flujo A y B en Fase 2)

Abre en pestaña/URL la ficha del anuncio (`https://www.mobile.de/fahrzeuge/details.html?id=<id>`).

**En el screenshot de la ficha lee:**
- **"Fahrzeugdaten"** → km, Erstzulassung, Leistung (kW/CV), Getriebe, Farbe, Schadstoffklasse, Anzahl der Fahrzeughalter, **CO₂** si existe.
- **"Ausstattung"** → lista de equipamiento (techo, cuero, ACC, LED, asientos calefactables, audio premium, enganche, AWD, garantía…).
- **Precio:** arriba a la derecha. `zzgl. MwSt.` = neto → anota IVA aparte.
- **Advertencias visibles:** "Unfallschaden", "Nicht unfallfrei", "NUR AN AUTOHÄNDLER" → descartar o marcar según criba.

Avisos: CO₂ falta a menudo → estimar y decirlo · <15 features = anuncio pobre (salvo topes de gama) · no hay VIN ni fecha de publicación.

### `ms` validados

| Modelo | `ms` | | Modelo | `ms` |
|---|---|---|---|---|
| VW Golf | `25200;;29;` | | Audi S3 | `1900;19;;` |
| VW Golf GTI | `25200;;29;GTI` | | Audi RS3 | `1900;36;;` |
| VW Golf R | `25200;;29;R 4MOTION` | | Audi TT | `1900;23;;` |
| VW Arteon | `25200;64;;` | | Audi A3 | `1900;8;;` |
| CUPRA Formentor | `3;5;;` | | Seat León | `22500;9;;` |
| CUPRA León | `3;6;;` | | Mercedes Clase A | `17200;;4;` |
| BMW Serie 1 | `3500;;20;` | | Mercedes CLA | `17200;;45;` |
| BMW M2 | `3500;117;;` | | Mercedes A45 AMG | `17200;229;;` |

Trampas: `/auto/volkswagen-golf-gti.html` y `-golf-r.html` devuelven el Golf entero · `-cla.html` y `-1er.html` devuelven la marca entera. Validar el `ms` contra el **título visible** de la página.

### Orden de reintento mobile.de bloqueado

`www.mobile.de` (recarga + espera 2-3 s) → `suchen.mobile.de` → marcar PARCIAL con aviso y seguir con AS24 + AutoUncle.

---

## 💰 Presupuesto de tokens (cada screenshot ≈ 1.000-1.800 tokens)

> El "conteo de peticiones" de antes ahora es **conteo de screenshots/acciones**.
> Cada captura cuesta tokens: sé frugal. Una acción = navegar, clic, scroll, screenshot…

| Fuente | Acciones/screenshots típicos |
|---|---:|
| mobile.de listado | búsqueda + 3-4 filtros + 2 páginas ≈ 8-12 capturas |
| mobile.de fichas | 15-25 fichas top, 1 captura cada una (verificar antes de registrar) |
| AutoScout24 | 1 búsqueda + 1 página ≈ 4-6 |
| AutoUncle | 1 búsqueda + 1 página ≈ 4-6 |
| kleinanzeigen | 1 búsqueda + 1 página ≈ 4-6 |
| Coches.net | búsqueda + 2-3 páginas ≈ 6-10 |
| Wallapop | búsqueda + scroll ≈ 5-8 |
| Milanuncios | búsqueda + 2 páginas ≈ 5-8 |
| km77 | 1-2 fichas ≈ 3-4 |

**Límites por sesión (mantener de SKILL.md):** mobile.de NUNCA >45 acciones ·
Flujo A ≤70 · Flujo B ≤50 · Flujo C ≤100. Avisar al 50% y al 80%.

**Frugalidad:**
- No capturear la misma página 2 veces sin necesidad.
- En listados, una captura de pantalla completa lee muchas tarjetas → 1 captura
  por página suele bastar; usa `zoom` solo para datos pequeños puntuales.
- Verifica con screenshot SOLO cuando vayas a registrar un dato importante
  (precio de candidato, CO₂, conteo total), no en cada micro-paso.

---

## ⚠️ Trampas críticas (adaptadas a computer use)

| Trampa | Consecuencia | Solución |
|---|---|---|
| **Clic falla por mala coordenada** | No se aplica el filtro | Describir posicionalmente ("el selector abajo a la izquierda"), zoom, o atajo de teclado |
| **Dropdown no se abre** | No se puede elegir opción | Clic en el campo + `key` flechas + Enter (teclado, no ratón) |
| **Scroll no funciona** | No llega más muestra | `Page Down` / `End` |
| **Datos solo en JSON invisible** | `__INITIAL_PROPS__` no accesible | **Método degradado:** leer DOM visible. En Fase 1 aceptar tras 1 intento |
| **Skeleton / carga lenta** | Se lee página vacía | `wait` 2-3 s, recargar 1 vez, luego screenshot |
| **Cookie banner tapa contenido** | Datos ocultos | Clic aceptar primero (posicional) |
| **Captcha** | Fuente inaccesible | Recargar 1-2 veces con pausa → si sigue, `bloqueada (captcha)` y seguir |

**Regla de los 2 intentos:** navegación humana (screenshot + clic) → 2 intentos →
método degradado (leer visible) → solo entonces marcar la fuente. NUNCA
obsesionarse con una fuente.

> ⚠️ **Clave:** `__INITIAL_PROPS__` / `__INITIAL_STATE__` / `__NEXT_DATA__` eran
> trucos de inyección JS que **NO existen en computer use**. Cuando el portal
> tenga el dato solo en su estado JS (tasación Coches.net, `publicationDate`,
> `priceRankIndicator`, `priceDrop`), se lee el equivalente VISIBLE de la página
> o se declara no disponible. No gastes tiempo buscándolos.

---

## 📚 Slugs AutoScout24

`volkswagen` golf golf-gti golf-r arteon passat t-roc · `cupra` leon formentor · `audi` a3 a4 a5 s3 s4 rs3 tt · `seat` leon · `bmw` serie-1 serie-2 serie-3 serie-4 m2 · `mercedes-benz` cla a-180 a-45-amg cla-45-amg · `skoda` octavia superb kodiaq · `volvo` v40 v60 xc60 · `ford` focus kuga · `honda` civic · `hyundai` i30 · `opel` astra · `kia` niro · `mazda` 3 · `porsche` cayman · `peugeot` 308.

En **.de** los BMW son `1er 2er 3er 4er`. Dan 404: `mercedes-benz/clase-a`, `/clase-c`, `bmw/m140i`, `/m135i`, `/m240i`, `/330e`, `toyota/gr-yaris`.

---

## 🇩🇪 Diccionario alemán

`AHK` enganche · `SHZ` calefactables · `PANO`/`Schiebedach` techo · `RFK`/`KAM` cámara · `ACC` · `HUD` · `NAVI` · `VIRTUAL` · `LED`/`MATRIX`/`IQ.LIGHT` · `LEDER` cuero · `DCC` · `STANDHZ` · `SCHECKHEFTGEPFLEGT` libro sellado · `1.HAND` · `TÜV NEU` · `UNFALLFREI` · `VB` negociable · `Schaltgetriebe` **manual** · `Automatik` · `NUR AN AUTOHÄNDLER` · `Auffahrunfall` golpe por alcance · `Leistungssteigerung` aumento potencia · `Zahnriemen NEU` · `Batterie-Zertifikat`.

### Criba nivel 1 (mobile.de) — qué fase la aplica

**Fase 1:** Aplica **soft** — solo se descartan los motivos que se ven directamente en el listado (siniestro/título, modelo equivocado, país, `NUR AN AUTOHÄNDLER`). Los demás (km/año, 6/6.000, modificados) se marcan `man` para Fase 2.

**Fase 2:** Aplica **dura** — descarta todos los motivos abajo. Solo pasan a fichas los 15-25 mejores.

| Motivo | Fase 1 | Fase 2 |
|---|---|---|
| Regla 6/6.000 | `man` | ❌ |
| Siniestro declarado | ❌ | ❌ |
| Modificado declarado | `man` | ❌ |
| Uso intensivo (>30k km/año) | `man` | ❌ |
| País imprevisto | ❌ | ❌ |
| Solo profesionales | ❌ | ❌ |
| Modelo equivocado | ❌ | ❌ |

**Razón:** En Fase 1 no siempre se tiene `kmAnio` calculado (necesita antigüedad). En Fase 2 sí. La criba dura solo se justifica cuando se tienen todos los datos.

### Detecta competencia en anuncios españoles

Regex: `/import(ad|ación)|traído de|comprado en (alemania\|opel alemania)|nacionalizado\|procedente de alemania\|matriculado en españa en \d{4}/i`

---

## 🎯 Calibraciones

**`priceRankIndicator` Coches.net:** visible en la tarjeta — "Buen precio" =
`4`, "Precio justo" = `3` (verificado 12-ago). Úsalo como señal de precio.

**`publicationDate` Coches.net:** mediana de días publicados = rotación (factor 1 vendibilidad) — leerlo en la ficha cuando sea visible; si no, inferir de los días publicados mostrados en las tarjetas.

**Descuento por días publicado (PENDIENTE calibrar):**

| Días publicado | Descuento |
|---|---|
| < 15 | 0% |
| 15-45 | −3% |
| 46-90 | −6% |
| > 90 | −10% |

> ⚠️ No aplicar sin decirlo. Impacto esperado: 3-8 puntos de margen en nuestra contra.

---

## 🚨 Edge cases — qué hacer

| Caso | Procedimiento |
|---|---|
| **mobile.de completamente bloqueado** | Marcar PARCIAL con aviso. Usar solo AS24+AutoUncle. Veredicto tiene menor confianza. |
| **Candidato en NL/BE/LU** | Si es la única opción, medir con disclaimer "mercado no alemán, transporte distinto". Se cuentan, no se miden por defecto. |
| **Anuncio en CHF (Suiza)** | Convertir a EUR al cambio del día. Anotar "precio en CHF, convertido a X EUR". |
| **Comparable español sin muestra** | 3 métodos: normal (±40% km), ampliado (±3a ±60% km) o cualitativo. Ver SKILL.md §Filtro de admisión. |
| **Usuario dice "no" en checkpoint** | CP1: volver al pool. CP2: rehacer ajuste. CP3: no generar informe. |

---

## 🆚 Diferencias Fase 1 vs Fase 2

| Aspecto | Fase 1 (sondeo) | Fase 2 (profunda) |
|---|---|---|
| **Fuentes** | 3 (Coches.net, mobile.de, AutoUncle) | 7 (añade Wallapop, Milanuncios, AS24, kleinanzeigen) |
| **Datos por fuente** | Precio, año, km, versión (listado) | Añade descripción entera, features, equipamiento, CO₂, propietarios |
| **Fichas mobile.de** | No (solo listado) | Sí (15-25 top candidatos) |
| **km77** | No (solo si Flujo A se va a profundizar) | Sí (PVP, CO₂, etiqueta) |
| **Acciones/screenshots típicos** | 12-18 | 25-40 |
| **Output** | Foto general (mediana, hueco, N) | Detalle por unidad + veredicto |
