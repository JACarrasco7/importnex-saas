# Playbook de filtrado — técnicas para Claude Desktop / VS Code

> Cargar cuando Claude tenga que **filtrar y buscar coches** en los portales con
> la extensión de navegador (Claude Desktop) o el **navegador integrado de VS Code
> (Playwright)**. Son técnicas concretas para ir rápido y no perder
> tokens en navegación inútil. Basado en `paginas_reales.md`.

---

## ⚡ Reglas de oro (leer PRIMERO)

1. **Empieza por el final:** antes de navegar, sabe qué dato necesitas (precio DE, precio ES, N anuncios, días publicado, CO₂). Cada captura cuesta tokens.
2. **Una URL bien construida ahorra 5 clics:** usa los parámetros de la URL cuando sea posible (ver tabla por fuente).
3. **Filtra en la página, no en la URL:** los filtros nativos (combobox Preis, Kilometer, Erstzulassung) son más fiables que los parámetros de URL y se aplican en vivo.
4. **Después de cada filtro → screenshot:** confirma que el contador cambió. Si no cambió, el filtro no se aplicó.
5. **Pareto:** 1 captura de página entera lee 10-20 tarjetas → no capturees tarjeta a tarjeta salvo fichas clave.
6. **Captcha/cookies:** si los ves, UN clic (Aceptar/Einverstanden) y seguir. Si persiste → fuente bloqueada, siguiente.
7. **DOBLE PASADA por potencia (CRÍTICO · 12-ago-2026):** el filtro por variante de texto (`OPC`, `GTI`, `M`...) se pierde coches genuinos mal etiquetados. SIEMPRE cruzar con una búsqueda por **kW/CV** (campo estructurado fiable). Detalle en §Doble pasada.
8. **Unión, no intersección:** al cruzar búsquedas, unir listas — pero NO por ID de anuncio entre portales distintos (los IDs no son comparables): usar la **clave fuzzy** de §TRATAMIENTO DE DATOS 1️⃣. Los que solo salen en la 2ª pasada son los chollos escondidos.

---

## 🔲 MATRIZ FILTRO × PORTAL (referencia rápida transversal — 24-ago-2026)

> El mismo sondeo (año/km/precio/potencia/cambio/puertas) en cada portal usa parámetros distintos. Esta matriz evita cruzar 6 secciones y equivocarse de parámetro. ⬜ = no documentado todavía en este skill (si lo necesitas y lo verificas, añádelo aquí).

| Filtro | mobile.de (DE) | Coches.net (ES) | AutoScout24 (DE) | AutoUncle | kleinanzeigen (DE) | Wallapop (ES) |
|---|---|---|---|---|---|---|
| Marca+modelo | `ms=<makeId>;<modelId>;;;;` | `MakeIds[0]=`+`ModelIds[0]=` | ruta `/lst/<marca>/<modelo>/` | combobox | `autos.marke_s:` | radio `brand`→`model` |
| Año desde | `fr=<y>:` | `MinYear=` | `fregfrom=` | `[name="minYear"]` | `brwse-attr-autos.ez_i-min` | range selector |
| Año hasta | `fr=:<y>` | `MaxYear=` | `fregto=` | `[name="maxYear"]` | `brwse-attr-autos.ez_i-max` | range selector |
| Km máx | `ml=:<km>` | `MaxKms=` | `kmto=` | `[name="maxKm"]` | `brwse-attr-autos.km_i-max` | range |
| Potencia | `pw=<kWd>%3A<kWh>` | `PowerHpFrom/To=` (cv) | `powerfrom/powerto=`+`powertype=kw` | ⬜ | `brwse-attr-autos.power_i` | ⬜ (sin filtro) |
| Precio máx | ⬜ (usar filtro página) | grupo `priceGroup` | `priceto=` | `[name="maxPrice"]` | `srchrslt-brwse-price-max` | `#toSelector` |
| Cambio | `tr=MANUAL_GEAR` \| `AUTOMATIC_GEAR` | `TransmissionTypeId=1\|2` ⚠️ trampa Golf R | ⬜ | `[name="gear"]` radio | `autos.gearbox_s:` | `[id="manual"]`/`[id="automatic"]` |
| Puertas | ⚠️ NO verificable (limitación) | `minDoors=` | ⬜ | ⬜ | `autos.doors_s:` ⬜ | ⬜ |
| Combustible | ⬜ (chips Kraftstoffart) | `Fueltype2List=` | `fuel=` | `[name="fuelTypes"]` | `autos.fuel_s:` | `[id="gasoline"]` etc. |
| Solo particulares | `seller_type` / `dam=0` no confundir | grupo `sellerGroup` | ⬜ | ⬜ | `autos.anbieter_s:privat` | `[name="seller_type..."]` |
| Orden precio asc | `sb=p&od=up` | `fi=Price&or=1` | `sort=price` ⬜ | ⬜ | ⬜ | `order_by=price_asc` |
| Página N | `pageNumber=N` | `pg=N` | `page=N` ⬜ | ⬜ | `/seite:N/` | ⬜ |
| Equipamiento | ⚠️ solo constructor `/es/s/auto` (ver §flujo correcto) | `equipmentGroup` (solo techo/cámara/GPS) | ⬜ | `[name="popularOptions"]` | `checkbox-autos.*` + Übernehmen | ⬜ (keywords=) |

> **Regla:** si un parámetro de la matriz está ⬜ y el sondeo lo necesita, verificarlo en vivo UNA vez y actualizar esta tabla (sección del portal correspondiente para el detalle de selectores).

---

## 🎯 Playbook por tipo de búsqueda

### Búsqueda A: "¿hay hueco para este coche?" (test rápido Fase 1)

**Objetivo:** en 6-8 capturas, saber mediana ES, mediana DE y días publicado.

```
1. mobile.de (1 navegación + 2 capturas)
   - URL = plantilla canónica §"URL de resultados reales" (`suchen.mobile.de/fahrzeuge/search.html?...&ms=<makeId>;<modelId>;;;;&sb=p`) — NUNCA `/es/s/auto` (modo formulario sin tarjetas)
   - Aceptar cookies → screenshot
   - Filtro Kilometerstand bis + Erstzulassung von (clic combobox) → screenshot
   - Anotar: <h1> "X Angebote" + 5-8 precios bajos + lista (kW/PS, km, año)

2. Coches.net (1 navegación + 2 capturas)
   - URL /segunda-mano/coches/<slug>
   - Filtro precio + provincia si aplica → screenshot
   - Anotar: contador tras filtro + 5-8 precios + etiquetas "Buen precio"

3. AutoUncle (1 navegación + 1 captura)
   - URL /es/coches-segunda-mano/<marca>/<modelo>
   - Anotar: "X coches" + días en venta de 3-4 anuncios
```

→ **Total: 5-6 capturas.** Tienes hueco (mediana ES vs DE) + rotación.

### Búsqueda B: "encuentra los 3 mejores candidatos DE" (top fichas)

```
1. mobile.de con filtros duros (construidos por URL con la plantilla canónica `suchen...&sb=p`, no a clics):
   - Erstzulassung von: año mín + 2 años del candidato (`fr=`)
   - Kilometerstand bis: +40% del km del candidato
   - Preis bis: precio máximo de compra + 10%
   - Getriebe: si candidato es manual → Schaltgetriebe
   - Sort: "Preis (niedrigster zuerst)"
   → screenshot

2. Descartar rápido (visible en listado):
   - "NUR AN AUTOHÄNDLER" (si buscas particular)
   - "Unfallschaden" / "Nicht unfallfrei"
   - País no DE (NL/BE/LU → marcar, no descartar salvo cliente específico)
   - Modelo equivocado (validar vs título)

3. De los 15-25 primeros, clica 3 mejores → 1 captura por ficha:
   - Ficha: leer Fahrzeugdaten + Ausstattung + precio bruto/neto
   - Anotar: CO₂ si aparece, propietarios, historial

→ Total: 1 (filtros) + 1 (listado) + 3 (fichas) = 5 capturas.
```

### Búsqueda C: "modelo X, top 5 globales" (Flujo B)

```
1. mobile.de (URL canónica suchen + sb=p + pw= por variante) = 2 capturas → top 10 DE
2. AutoScout24.de (URL va_<version>) = 2 capturas → validar N total DE
3. AutoUncle = 1 captura → días publicado + bajadas precio
4. Coches.net (filtros + listado) = 2 capturas → mediana ES + priceRankIndicator

→ 7 capturas para Fase 1.
```

---

## 🧮 TRATAMIENTO DE DATOS — cómo procesar lo extraído (24-ago-2026)

> Reglas duras para la fase posterior a la extracción: dedup, formato de ficha, persistencia de la query y reconciliación de conteos. Sin esto, los datos no son auditables ni re-ejecutables (lección Golf 7.5: `query_reejecutable=[]` en el JSON hizo imposible explicar por qué se medían 717 GTI vivos vs 13 guardados).

### 1️⃣ Dedup ENTRE portales (clave fuzzy)

Los IDs de anuncio NO son comparables entre portales (el `id=40947884798464` de mobile.de no existe en Coches.net). Deduplicar por **clave fuzzy**:

```
clave_dedup = marca + modelo_normalizado + año_matriculación + km (±2%) + potencia_kW (±3)
```

- 2 fichas con la misma clave = mismo coche en 2 portales → contar 1 vez, guardar ambas URLs.
- Para candidato concreto (Flujo A/B): añadir CO₂ o color si disponibles (más precisión).
- **Regla dura A8 reforzada:** AutoUncle es AGREGADOR de mobile.de/AS24 → su conteo NUNCA se suma al total DE (doble conteo sistemático). Solo rotación + validación.

### 2️⃣ Ficha normalizada (formato destino único)

Toda ficha procesada (para informes y para volcar al mapa) usa ESTE esquema, venga del portal que venga:

```json
{
  "id_portal": "mobile.de|coches.net|as24|kleinanzeigen|wallapop|milanuncios|autouncle",
  "id_anuncio": "<ID nativo del portal>",
  "url": "<URL ficha>",
  "titulo": "<título literal>",
  "precio_contado_eur": 0,
  "precio_bruto_flag": false,
  "anio": 0, "km": 0, "kW": 0, "cv": 0,
  "combustible": "gasolina|diesel|hibrido|electrico",
  "cambio": "manual|dsg|automatico|null",
  "puertas": "3p|5p|null",
  "co2_gkm": null, "propietarios": null,
  "equipamiento": ["techo","cuadro_digital","led","hud"],
  "vendedor": "particular|concesionario|null",
  "fiabilidad": "verificado_ficha|listado",
  "fecha_lectura": "YYYY-MM-DD"
}
```

- `precio_contado_eur` SIEMPRE contado (nunca financiado); `precio_bruto_flag: true` si "zzgl. MwSt." (neto, IVA aparte).
- Campos que el portal no expone → `null` (NUNCA inventar — regla anti-bot ya existente).
- `fiabilidad`: "verificado_ficha" (se abrió) vs "listado" (solo tarjeta) — regla suelo de listado vs verificado.

### 3️⃣ Persistir la query (regla dura — bloqueante en cierre)

**Toda medición que se vuelque a `datos_mercado.json` DEBE guardar en `query_reejecutable` la URL final con TODOS los parámetros** (no `[]`). Sin ella el sondeo no es re-ejecutable ni auditable:

```
query_reejecutable: ["https://suchen.mobile.de/fahrzeuge/search.html?dam=0&fr=2017%3A2020&...&pw=224%3A232",
                     "https://www.coches.net/segunda-mano/?MakeIds[0]=47&PowerHpFrom=305&PowerHpTo=315&fi=Price&or=1"]
```

+ `fecha_medicion` y `contador_resultados` de cada URL. El checklist de cierre lo verifica (campo NO vacío).

### 4️⃣ Reconciliar conteos (portal vivo vs mapa guardado)

Cuando el conteo en vivo difiere del guardado en el JSON (caso real: GTI 717 vivo vs 13 guardado):

1. **Fuente de verdad = portal primario leído en la MISMA sesión** (mobile.de para DE, Coches.net para ES).
2. **Discrepancia >10% vs mapa** → re-medir con la `query_reejecutable` guardada (si existe) para comparar MISMOS filtros. Si no existe, es imposible comparar → persistir la nueva query ya (regla 3️⃣).
3. Si con la MISMA query el conteo difiere → el mercado se movió: actualizar el mapa con ambos valores + fechas.
4. Si la query guardada usa filtros distintos a los actuales → documentar en `nota` qué filtro aplicaba ("filtro estrecho no documentado"), NO sobrescribir en silencio.

---

## � Doble pasada por potencia — NO perder coches mal etiquetados (CRÍTICO)

> **Falló en real 12-ago-2026 (Opel Astra OPC):** filtrar solo por variante `OPC` dio 72 anuncios pero un OPC genuino de 8.999 € NO salió porque su título era genérico "Opel Astra". El campo "variante" es texto libre del vendedor → no fiable. La potencia (kW) viene del permiso → campo estructurado fiable.

### Cuándo aplicarla
Siempre que la versión buscada sea un **tope de gama / acabado especial** que pueda estar mal etiquetado:
`OPC`, `GTI`, `GTD`, `R`, `M`, `AMG`, `RS`, `Type R`, `N`, `GTE`, `RS Line`, `Performance`, etc.

### Método (2 búsquedas + cruce)

```
PASO 1 — Búsqueda por variante de texto (la normal)
  URL/filtro: <marca>-<modelo> + variante "OPC"
  Resultado: 72 anuncios (muchos son "OPC-Line" o mal etiquetados)

PASO 2 — Búsqueda por MODELO BASE + potencia (kW)
  URL: plantilla canónica suchen con el modelo base (ms=<makeId>;<modelId>;;;;)
  + pw=<kWdesde>%3A<kWhasta> derivado del cv EXACTO de la variante (ver tabla abajo)
    · Ej OPC 280 CV = 206 kW → pw=196:211
  + fr=<año mínimo> (Erstzulassung)
  + ml=:<km máximo>
  + sb=p&od=up (precio ascendente)
  Resultado: los OPC "disfrazados" de Astra normal

PASO 3 — CRUCE (unión, NO intersección)
  Unir ambas listas por ID de anuncio
  Eliminar duplicados
  Los que están SOLO en la búsqueda 2 = chollos escondidos
```

### Tabla de potencias para topes de gama habituales (pw= por variante EXACTA)

> **Regla de derivación (24-ago):** SIEMPRE derivar `pw=` del **cv exacto de la variante**, no de un rango amplio. Fórmula: kW = cv × 0,7355. Plantilla: `pw=<cv×0,7355−4kW>%3A<cv×0,7355+4kW>` (±4 kW cubre redondeos del permiso). Usar el rango amplio de la tabla vieja mezcla generaciones (caso Golf R: 212-240 kW mete pre-FL 300cv y Mk8 320cv en la misma búsqueda).

| Modelo (variante) | CV | kW | `pw=` plantilla | Notas |
|---|---|---|---|---|
| Opel Astra J OPC | 280 | 206 | `pw=196:211` | |
| VW Golf GTI Mk7.5 | 230 | 169 | `pw=165:173` | |
| VW Golf GTI Perf Mk7.5 | 245 | 180 | `pw=176:184` | GTI Mk8 = mismos 245cv, acotar `fr=` |
| VW Golf R Mk7.5 | 310 | 228 | `pw=224:232` | ✅ validada 24-ago |
| VW Golf R pre-FL Mk7 | 300 | 221 | `pw=217:225` | ⚠️ descartar activamente |
| VW Golf 8 GTI | 245 | 180 | `pw=176:184` | + `fr=2021:2024` |
| Audi S3 8V | 300 | 221 | `pw=217:225` | pre-FL 286cv → `pw=207:214` |
| Audi RS3 8V | 400 | 294 | `pw=290:298` | |
| BMW M135 F20 | 306 | 225 | `pw=221:229` | M140i 340cv → `pw=246:254` |
| BMW M240i | 340 | 250 | `pw=246:254` | |
| Mercedes A45 AMG | 381 | 280 | `pw=276:285` | S-Version 421cv → `pw=306:314` |
| Hyundai i30N | 250 | 184 | `pw=180:188` | Perf 280cv → `pw=202:210` |
| Cupra León VZ | 290 | 213 | `pw=209:217` | 300cv (2021+) → `pw=217:225` |
| Ford Focus ST Mk3 | 250 | 184 | `pw=180:188` | |
| Honda Civic Type R FK8 | 320 | 235 | `pw=231:239` | |
| Renault Mégane RS | 280 | 206 | `pw=202:210` | Trophy 300cv → `pw=217:225` |

> ⚠️ Si no estás seguro de la potencia del modelo, búscala primero (km77/BOE o spec oficial) ANTES de construir la URL. No inventes el rango.
> ⚠️ Variante rechipada (stage 1): el vendedor declara potencia ALTERADA → el filtro pw= genuino NO la captura. La doble pasada es unión: la búsqueda 1 (texto) complementa. Comparar SIEMPRE potencia declarada vs catálogo (trampa Clubsport "450cv").

### Aplicación por portal
- **mobile.de:** parámetro `pw=<desde>%3A<hasta>` en la URL canónica (NO usar "ps", no existe en la plantilla suchen)
- **AutoScout24.de:** filtro `Leistung` / `potencia`
- **Coches.net:** filtro `Potencia CV` (aunque el título diga otra cosa)
- **Resto:** aplicar misma lógica si el filtro de CV existe

### Coste extra
+2-3 capturas por portal (búsqueda 2 + cruce). **Vale la pena:** cada chollo escondido puede ser miles de € de margen.

---

## �🔧 Filtros potentes por fuente (qué clicar para acotar)

### 🇩🇪 mobile.de — URL de resultados reales que SÍ funciona (24-ago-2026)

> **Hallazgo crítico:** la URL que la IA solía usar por defecto (`www.mobile.de/es/s/auto?s=Car&vc=Car&ms=...`) entra en **modo "formulario avanzado"** y NO muestra las tarjetas de resultados. Muestra el conteo y los filtros pero NO las fichas orgánicas. El botón "Ofertas" de ese modo no navega a resultados reales vía click. **La URL que SÍ devuelve resultados reales** es la del buscador clásico en `suchen.mobile.de`.

**URL base confirmada (orden por precio ascendente, filtros estructurados):**

```
https://suchen.mobile.de/fahrzeuge/search.html
  ?dam=0
  &fr=<añoDesde>%3A<añoHasta>
  &isSearchRequest=true
  &ml=%3A<kmMax>
  &ms=<makeId>%3B<modelId>%3B%3B%3B
  &od=up
  &s=Car
  &sb=p
  &vc=Car
  &pw=<kWdesde>%3A<kWhasta>
  &tr=MANUAL_GEAR|AUTOMATIC_GEAR
```

**Claves (24-ago-2026):**
- `ms=` va como `makeId;modelId;;;;` (make;model;vacío;vacío;vacío). **NO** como `make;;variante` (esa sintaxis probada al principio **fallaba** y devolvía 0 resultados).
- `sb=p` (precio asc) **SÍ funciona** combinado con `ms` en esta URL — sin él, la página cae en modo formulario sin resultados.
- `tr=MANUAL_GEAR` o `tr=AUTOMATIC_GEAR` para filtrar cambio individual (omitir `tr=` para ambos).
- `pw=<kWdesde>%3A<kWhasta>` para filtrar por rango de potencia en kW (clave para la doble pasada).
- `dam=0` (NO dañados), `isSearchRequest=true` (mantiene resultados aunque cambies filtros), `od=up` (orden: precio ascendente).

**Plantilla Golf R 310cv Mk7.5 2017-2020, ≤180k km:**
```
https://suchen.mobile.de/fahrzeuge/search.html?dam=0&fr=2017%3A2020&isSearchRequest=true&ml=%3A180000&ms=25200%3B12603%3B%3B%3B&od=up&s=Car&sb=p&vc=Car&pw=224%3A232
```
(`makeId` VW=25200, `modelId` Golf Mk7.5=12603 — verificar IDs actualizados en mobile.de para cada modelo.)

### 🇩🇪 mobile.de — extracción de tarjetas virtualizadas (24-ago-2026)

> **Hallazgo crítico:** la página de resultados de mobile.de está **virtualizada**. `get_page_text` sobre la URL anterior solo devuelve el **panel de filtros** (nunca las tarjetas de coches). Para leer precios/títulos/km/año de los resultados hay que usar el panel de accesibilidad:

```
PASO 1 — Identificar el contenedor de resultados
  find(query="container/list element that holds all the vehicle result cards")
  → devuelve el ref_id del contenedor de la lista de resultados

PASO 2 — Leer las tarjetas visibles dentro del contenedor
  read_page(ref_id=<ref_id_contenedor>)
  → devuelve el árbol accesible del contenedor: suele incluir
    - 1 tarjeta patrocinada (arriba)
    - 1-2 tarjetas orgánicas montadas (las que están en viewport)

PASO 3 — Para ver más tarjetas
  computer scroll(direction="down", amount=800) → re-leer con read_page
  O screenshot de la página → lectura visual rápida de precio+título+km+año
    (cuando el screenshot funcione)
```

**Limitaciones del método:**
- Solo se leen las tarjetas **montadas en el viewport** (≈2-3 a la vez). Para contar todos los resultados hay que sumar leyendo por bloques tras scroll, **o** fiarse del contador del `<h1>` ("X Angebote").
- El contador en `<h1>` puede oscilar entre consultas consecutivas por alta rotación de inventario (caso Golf R: osciló 142 ↔ 144). Anotar el rango, no el número exacto si hay duda.
- **Patrocinadas** vs **orgánicas**: las patrocinadas están en `div[data-testid="top-*"]` o `tic-*`; las orgánicas en `base-*`. Solo las orgánicas cuentan para el estudio.

### 🇩🇪 mobile.de — limitaciones de filtros (24-ago-2026)

> **Filtros pendientes / no confirmados** — declarar como limitación abierta en el informe en vez de inventarse datos:

| Filtro | Estado | Cómo afecta |
|---|---|---|
| **Número de puertas** (`door-filter` `TWO_OR_THREE` / `FOUR_OR_FIVE` / `SIX_OR_SEVEN`) | ⚠️ **No se ha podido aplicar como filtro verificable** ni por URL directa ni por clic+selección esta sesión | La segmentación por puertas en DE queda como limitación abierta. En ES (Coches.net) sí funciona (`minDoors=`) |
| **Panel de instrumentos digital** (cuadro digital / Active Info Display) | ⚠️ Vive detrás de un enlace "Más..." en Conjuntos de funciones que no respondió a intentos de expansión | Sin filtro agregado fiable en ningún portal. Verificar ficha a ficha en Flujo B |

### 🆔 Tabla de IDs mobile.de (makeId;modelId) — 24-ago-2026

> Los `makeId` siguen vigentes; los `modelId` NUEVOS (formato `12603`) son distintos de los modelGroup viejos (`29`). Verificados = probados con la URL canónica. El resto: descubrir con el procedimiento de abajo y AÑADIR a esta tabla al verificar.

| Marca | makeId | Modelos (modelId) |
|---|---|---|
| VW | 25200 | Golf Mk7.5 = **12603** ✅ · Golf 8, Arteon, Tiguan = ⬜ por verificar |
| Audi | 1900 | A3, S3, TT, RS3 = ⬜ por verificar |
| BMW | 3500 | Serie 1, M135, M240i = ⬜ por verificar |
| Mercedes | 17200 | Clase A, A45, CLA = ⬜ por verificar |
| Seat | 22500 | León = ⬜ por verificar |
| Cupra | 3 | León, Formentor = ⬜ por verificar |
| Opel | 29000* | Astra J = ⬜ por verificar (*por confirmar) |
| Ford | 24500* | Focus = ⬜ por verificar (*por confirmar) |
| Hyundai | 35500* | i30N = ⬜ por verificar (*por confirmar) |

**Procedimiento para descubrir makeId/modelId nuevos (3 pasos, ~2 capturas):**
1. Navegar a `https://www.mobile.de/es/s/auto?s=Car&vc=Car` (la página SIRVE para esto: construir queries, no para ver tarjetas).
2. Leer los `options value` del select `[data-testid="make-incl-0"]` → makeId de la marca. Elegir marca → el select `[data-testid="model-incl-0"]` se rellena → leer su `value` → modelId.
3. Construir `ms=<makeId>;<modelId>;;;;` en la URL canónica suchen y **validar contra el `<h1>`** (debe decir el modelo correcto + "X Angebote" > 0). Si el `<h1>` muestra otra cosa → modelId mal.

### 📄 Paginación con la URL canónica (24-ago-2026)

- Parámetro de página: `&pageNumber=<N>` (2, 3, ...) sobre la URL suchen con todos los filtros. El botón siguiente es `[data-testid="pagination:next"]` (ya documentado en §leer la tarjeta).
- **Protocolo por bloques (integrado con A12 bandas de precio):** página 1 → leer patrocinada + 2-3 orgánicas montadas → `computer scroll` 800 → re-lectura → repetir hasta fin de página → `pageNumber=2`. Para conteos grandes (>100) NO hace falta leer todas las tarjetas: contador del `<h1>` + suelo (orden asc) + 2-3 páginas de tarjetas bastan para el estudio.
- Si solo interesa el SUELO (estudio de mercado): orden `sb=p&od=up` + página 1-2 → las más baratas ya están arriba (patrocinados aparte).

### 🎛️ Flujo correcto: checkboxes "full" + resultados reales (24-ago-2026)

**Contradicción resuelta:** los 5 checkboxes full (cuadro digital, HUD, calefacción, techo, LED) están documentados sobre `/es/s/auto` — la página que NO muestra tarjetas. El flujo correcto:

1. **`/es/s/auto` es el CONSTRUCTOR de queries**: marcar checkboxes con `data-testid` (§Selectores estables) → leer el contador en vivo → sirve para saber cuántas unidades full hay.
2. **Para ver TARJETAS con equipamiento filtrado**: dos vías:
   a. Reconstruir la query en `suchen.mobile.de` (los checkboxes de `/es/s/auto` generan parámetros: techo panorámico → `acc=SUNROOF` etc. — probar y validar contra `<h1>`), o
   b. Filtrar solo por variables estructuradas seguras (`ms`, `pw`, `fr`, `ml`, `tr`) y **verificar equipamiento ficha a ficha** en los 3-5 candidatos finales (más fiable y menos tokens).
3. **Por defecto: vía (b)** — el estudio de mercado filtra por potencia/año/km; el equipamiento full se confirma en ficha (regla de máximo equipamiento ya existente).

### 🇩🇪 mobile.de — filtros que valen oro
| Filtro | Cuándo | Cómo |
|---|---|---|
| **Preis bis** | Acotar a tu presupuesto | Combobox "bis" → preset o escribir |
| **Erstzulassung von** | Edad máxima | Combobox "von" → año |
| **Kilometerstand bis** | Km máx | Combobox "bis" |
| **Anbieter → Privatanbieter** | Solo particulares (chollos) | Radio en sección Anbieter |
| **Ausstattung → Schiebedach/Sitzheizung/Head-Up** | Equipamiento premium | Checkbox en sección Ausstattung |
| **Sortieren → Preis (niedrigster zuerst)** | Ver la base | Combobox arriba del listado |

**Quita el filtro "Beschädigte Fahrzeuge: Nicht anzeigen"** solo si buscas siniestros baratos para reexportar.

### 🔬 Selectores ESTABLES de filtros en mobile.de ES (`data-testid` · 18-ago-2026)

> El HTML de mobile.de ES (`/es/s/auto?s=Car&vc=Car`) usa **`data-testid` estables** para los filtros. A diferencia de las clases CSS ofuscadas, estos selectores no cambian con el build → **Claude los usa directamente con `page.getByTestId()` o selector `[data-testid="..."]`**. Son la forma más fiable de filtrar.

| Filtro | `data-testid` | Valores/uso |
|---|---|---|
| Fabricante | `make-incl-0` | select: value = id marca (VW 25200, Audi 1900, Cupra 3, Seat 22500...) |
| Modelo | `model-incl-0` | select (se rellena al elegir marca) |
| Variante (texto libre) | `model-description-incl-0` | input, ej "GTI" |
| Tipo de vehículo | `category-filter` | checkboxes: Cabrio, OffRoad, SmallCar, EstateCar, Limousine, SportsCar, Van, OtherCar |
| Nº asientos | `seats-filter` | min/max (2-10) |
| Nº puertas | `door-filter` | TWO_OR_THREE / FOUR_OR_FIVE / SIX_OR_SEVEN |
| **Precio** | `price-filter` | min/max (presets 500€…90.000€) |
| **Primera matriculación** | `first_registration-filter` | min/max por año (1970-2026) |
| **Kilometraje** | `mileage-filter` | min/max (5.000…200.000 km) |
| Estado | `condition-filter` | NEW / USED |
| Mantenimiento | `maintenance_features-filter` | WARRANTY, FULL_SERVICE_HISTORY, NEW_SERVICE, NONSMOKER_VEHICLE |
| Vendedor | `seller_type-filter` | DEALER / FSBO (particular) / COMM_FSBO (empresa) |
| ITV válida | `general_inspection-filter` | 0-18 meses |
| Propietarios | `previous_owners-filter` | Hasta 1/2/3/4 |
| País | `country-filter` | DE por defecto (importación) |
| Ciudad/CP | `location-filter` | autocompletar |
| Radio | `radius-filter` | 10-500 km |

**Filtros de EQUIPAMIENTO (la clave para máximo equipamiento):**
- En la sección **Extras** (checkbox): `Techo corredizo`, `Techo panorámico`, `Faros LED`, `Llantas de aleación`, `Portón eléctrico`, `Paquete de invierno`, `Suspensión deportiva`, `Luces adaptativas`...
- En la sección **Interior** (checkbox): **`Panel de instrumentos digital`** (= cuadro digital, el más demandado), **`Pantalla Head-up`**, **`Calefacción de asiento`**, **`Android Auto`**, **`Apple CarPlay`**, **`Sistema de navegación`**, **`Asientos deportivos`**, **`Carga de inducción`**, **`Volante multifunción`**, **`Control de crucero adaptativo`**, **`Cámara de 360°`** (en Sensores).
- En **Datos técnicos**: combustible (checkboxes Gasolina/Diesel/Híbrido...), **Potencia** (min/max cv o kW), cilindrada, tracción (4x4/delantera/trasera), transmisión (Automático/Semiautomático/Manual).

> **Regla máxima equipamiento (18-ago):** para comparar full vs full, marcar en Interior `Panel de instrumentos digital` + `Pantalla Head-up` + `Calefacción de asiento`, y en Extras `Techo panorámico` + `Faros LED`. Esos 5 checkboxes definen una unidad "full" reproducible.

### �🇪 mobile.de — leer la tarjeta (18-ago-2026)

| Dato | Selector | Notas |
|---|---|---|
| **ID (dedup)** | `a[data-testid$="-link"]` href `id=<ID>` | `456860545` → mismo coche en otros portales |
| Título | `span[data-testid="listing-title-card-view"]` | marca+modelo; versión en `...__subTitle` |
| **Precio** | `span[data-testid="price-label"]` | "€¹"=bruto(IVA incl.) · "zzgl. MwSt."=neto |
| **Bajada (tachado)** | `span[data-testid="strike-through-price"]` | 🎯 chollo |
| **Rating precio** | `div.PriceRatingBadge--label` | Muy buen/Buen/Precio justo/Sin calificación → chollo |
| Atributos | `div[data-testid="listing-details-attributes"]` | "Sin accidentes • PR 04/2016 • km • kW(cv) • Combustible" |
| Vendedor | `div[data-testid="seller-info"]` | nombre + "DE-#### Ciudad" + "X estrellas (N)" |
| **Sello OEM** | `div[data-testid="oem-seal-listing"]` | concesionario oficial certificado |
| Orden | `[data-testid="sorting-menu-dropdown"]` | `sb=p&od=up` = precio más bajo |
| Paginación | `[data-testid="pagination:next"/:previous"]` | actual=`[aria-label="Página N"][disabled]` |

> ⚠️ `data-testid` + prefijos `*-module__` estables (sufijos `__xxxxx` hash → NO). Tarjetas `top-*`/`tic-*` = patrocinadas; `base-*` = orgánicas. Ads `SRP_TABLECELL_*`/`SRP_INPAGE_VIDEO` y carrusel "Otros vehículos de este concesionario" (`SimilarTopListings`) → ignorar.

### �🇪🇸 Selectores ESTABLES de filtros en Coches.net (`id` de acordeón · 18-ago-2026)

> Coches.net **NO tiene página de filtros aparte**: el sidebar (`.mt-SearchSidebar-filters`) son **acordeones** en el propio listado, cada uno con un **`id` estable**. **Los filtros se aplican al marcar** (en vivo, sin botón). Claude expande el grupo clicando su `groupTrigger` y marca los checkboxes.

| Grupo | `id` | Para qué sirve |
|---|---|---|
| Tipo de coche | `vehicleTypeGroup` | Nuevo/Km0/Usado |
| Marca y modelo | `makeGroup` | marca → modelo anidado |
| Precio | `priceGroup` | desde/hasta |
| Servicios online | `onlineServicesGroup` | financiación/garantía |
| Ubicación | `locationGroup` | provincia |
| Vendedores | `sellerGroup` | particulares/profesionales |
| Año | `yearGroup` | desde/hasta |
| Kilómetros | `kmsGroup` | desde/hasta |
| Carrocería | `bodyTypeGroup` | berlina/SUV/familiar/coupé |
| Motor | `motorGroup` | combustible + potencia CV |
| Etiqueta DGT | `environmentalLabelGroup` | CERO/ECO/C/B |
| Eléctricos | `electricGroup` | autonomía |
| **Equipamiento** | `equipmentGroup` | **techo solar, cámara, GPS** (el único de equipamiento; SIN cuadro digital) |
| Color | `colorGroup` | colores |

**Máximo equipamiento en ES (proxy):** marcar en `equipmentGroup` techo solar + cámara (no hay cuadro digital en Coches.net); el nivel full real se confirma en 1-2 fichas (excepción puntual a A17).

### 🇪🇸 Coches.net — MÉTODO OFICIAL: filtros individuales por URL (23-ago-2026)

> **REGLA (23-ago-2026, validada con el usuario):** en Coches.net **NUNCA** filtrar por el campo de texto libre `Versions[]`/`Version=` (depende del etiquetado del vendedor, "funciona con IA y puede fallar", mezcla generaciones: captura "GTI" de Mk7/Mk7.5/Mk8, "Clubsport" de Mk7/Mk8, "R" de pre-FL/Mk8). **El método oficial es: 1) marca + modelo, 2) filtros individuales estructurados por URL** (potencia/combustible/año/km/carrocería/puertas/cambio). Orden por URL `fi=Price&or=1` **SÍ funciona de forma fiable** (corrige lo documentado antes).

**Parámetros de URL confirmados (Coches.net):**

| Filtro | Parámetro | Valores confirmados |
|---|---|---|
| Marca | `MakeIds[0]=` | Volkswagen = 47 |
| Modelo | `ModelIds[0]=` | Golf = 89 |
| Año desde | `MinYear=` | ej. 2017 |
| Año hasta | `MaxYear=` | ej. 2019 |
| Km máximo | `MaxKms=` | ej. 160000 |
| Combustible | `Fueltype2List=` | Gasolina = 2 · Diésel = 1 |
| Potencia mínima (CV) | `PowerHpFrom=` | ej. 200 |
| Potencia máxima (CV) | `PowerHpTo=` | ej. 295 |
| Carrocería | `ArrBodyType=` | Berlina = 1 |
| Puertas mínimas | `minDoors=` | ej. 5 |
| Cambio | `TransmissionTypeId=` | Automático = 1 · Manual = 2 (probar) · ⚠️ Golf R: las fichas devueltas bajo `=2` pueden llevar "DSG" en el título (mapeo no fiable en ese modelo específico) |
| Orden | `fi=Price&or=1` | Precio contado ascendente (fiable) |
| Página | `pg=` | ej. 2 · `Section1Id=2500` fijo |

**Plantilla base (Golf gasolina ≥200cv ≤160k km ≥2017, precio asc):**
```
https://www.coches.net/segunda-mano/?MakeIds[0]=47&ModelIds[0]=89&Fueltype2List=2&PowerHpFrom=200&MaxKms=160000&MinYear=2017&fi=Price&or=1
```
**Con carrocería+puertas+cambio (para aislar "5p automático", la config de importación):**
```
https://www.coches.net/segunda-mano/?ArrBodyType=1&minDoors=5&Fueltype2List=2&PowerHpFrom=200&MaxKms=160000&TransmissionTypeId=1&MakeIds[0]=47&ModelIds[0]=89&MinYear=2017&fi=Price&or=1
```

**🔬 Aislar variantes por POTENCIA (rango exacto CV) — la clave para no usar texto libre:**

| Variante | Rango `PowerHpFrom-To` | Notas |
|---|---|---|
| Golf GTI 230cv (Mk7.5) | 228-232 | 230cv |
| Golf GTI 245cv Performance (Mk7.5) | 243-247 | 245cv |
| Golf GTI TCR 290cv | 285-295 | Solo 5p DSG |
| Golf GTI Clubsport 265cv (Mk7 pre-FL) | 260-270 | **Solo Mk7 pre-facelift** (2016-2017); con `MaxYear=2017` |
| Golf R 310cv (Mk7.5) | 305-315 | Facelift 2017-2019 |
| Golf R 300cv (pre-FL) | 297-303 | Descartar activamente (Mk7 pre-facelift) |

**⚠️ Trampas al leer los listados (detectadas 23-ago):**
1. **"GTI 210cv" NO es GTI** — el GTI Mk7.5 es 230/245cv. 210cv = 2.0 TSI normal (o ficha corrupta, ver caso 13.000€ con slug año 2011 vs ficha 2020).
2. **"GTI 220cv" = Mk7 PRE-facelift** matriculado tarde → descartar como variante Mk7.5 (el pre-FL era 220cv exacto).
3. **"245cv 2023" = Mk8** fuera de alcance (estudio Mk7.5 ≤2020); además etiqueta ECO en GTI gasolina = imposible (señal de ficha mal rellenada).
4. **Precio financiado < contado:** anclar SIEMPRE el contado de la ficha (hasta -2.000€ en 3 casos).
5. **Coche publicado en ES pero físicamente en DE sin matricular** → no es suelo ES genuino.
6. **Kilometraje y año inconsistentes** (150.000km en 2 años, año slug≠ficha) → descartar sin verificar.

> ⚠️ **Distinguir SIEMPRE "suelo de listado" (no verificado) vs "suelo verificado en ficha".** El anti-bot corta tras 5-6 fichas → los candidatos restantes quedan "de listado" (solo precio/año/km), nunca inventar puertas/cambio/techo/cuadro digital. En el informe, marcar ambos suelos con su fiabilidad.

> ⚠️ **Trampa `TransmissionTypeId=2` en Golf R (24-ago-2026):** el filtro "Manual" de Coches.net (`TransmissionTypeId=2`) devolvió 3 fichas en Golf R (310cv) cuyo propio título dice "DSG". O el vendedor las etiquetó mal al publicar, o el mapeo del filtro no es fiable en el extremo "Manual" para este modelo. Para GTI y Clubsport el mapeo 1=Automático/2=Manual sí coincidió con los títulos. **Tratar `TransmissionTypeId=2` con cautela en Golf R específicamente** — verificar ficha individual antes de presentar un "Golf R manual español" al cliente.

### �🇸 Coches.net — leer la tarjeta (18-ago-2026)

| Dato | Selector | Notas |
|---|---|---|
| **ID (dedup)** | `div[data-ad-id]` | `70666366` → mismo coche en otros portales |
| Título | `h2[data-testid="card-ad-title"] a` | |
| **Precio contado** | `p[data-testid="card-adPrice-price"]` | 🎯 SIEMPRE contado, no financiado |
| **Rating precio** | `span.mt-CardAdPrice-cashLabel` | "Buen precio"(4/5)·"Precio justo"(3/5) → chollo |
| **Bajada** | `span.mt-CardAdPrice-priceDropPercentage` + `...OriginalPrice` | "-22%" + "16.000 €" → chollo |
| Atributos | `ul.mt-CardAd-attr li.mt-CardAd-attrItem` | combustible·año·km·cv·ciudad |
| Etiqueta DGT | `li.mt-CardAd-attrItemEnvironmentalLabel img` | `b.svg`(B)·`eco.svg`(ECO)·`c.svg`(C)·`0.svg`(CERO) |
| Vendedor | `span.sui-AtomBadge-text` | "Profesional 4.2" |
| Paginación | `nav[aria-label="Paginación"]` | `/search/?Section1Id=2500&pg=N` |

> ⚠️ **Skeletons** `div.sui-PerfDynamicRendering-placeholder` = sin cargar → scroll primero. **Financiado** (`div.mt-CardAdPrice-financed`, cuota `/mes*` + TAE) → ignorar. Ads: `--tallAd`(`#ad-right-*`)·`--native--mobile`(`#ad-inline-*`)·`#ad-textads` → ignorar. Rating "Buen precio" ya valida el precio del anuncio.

### �🇩🇪 Selectores ESTABLES de filtros en kleinanzeigen.de (18-ago-2026)

> kleinanzeigen (URL `https://www.kleinanzeigen.de/s-autos/c216`) tiene **3 tipos de filtro** con selectores distintos:

| Tipo | Selector | Ejemplo |
|---|---|---|
| **Atributos (marca/fuel/cambio/tipo/puertas/estado/anbieter)** | **URL directa** `+autos.<attr>_s:<valor>` | `autos.marke_s:seat+autos.fuel_s:benzin` → Seat gasolina. Combinables con `+` |
| **Rango (precio/km/año/potencia/HU)** | Inputs con **`id` estable** `brwse-attr-*` | Precio `srchrslt-brwse-price-min/-max` · Km `brwse-attr-autos.km_i-min/-max` · Año `brwse-attr-autos.ez_i-min/-max` · Potencia `brwse-attr-autos.power_i-min/-max` |
| **Equipamiento (checkboxes)** | Inputs con **`id`** `checkbox-autos.*` + botón **"Übernehmen"** `[data-cy="clickable-options-apply-button"]` | Exterior `trailer_coupling_b`/`xenon_led_light_b` · Interior **`sunroof_b` (techo)**/`seat_heating_b`/`navi_b`/`bluetooth_b` · Seguridad `full_service_history_b` |

> ⚠️ **En kleinanzeigen SÍ hay botón aplicar** (Übernehmen) para los checkboxes de equipamiento — a diferencia de Coches.net. Marcar checkboxes → clic en `[data-cy="clickable-options-apply-button"]`.
> **Máximo equipamiento:** `sunroof_b` + `seat_heating_b` + `xenon_led_light_b` + `navi_b` + `full_service_history_b` → Übernehmen.

### 🇩🇪 kleinanzeigen — leer la tarjeta (18-ago-2026)

| Dato | Selector | Notas |
|---|---|---|
| **ID (dedup)** | `article[data-adid]` | `3483153805` → mismo coche en otros portales |
| Enlace/Título | `h3 a[href^="/s-anzeige/"]` | `/s-anzeige/<slug>-<ID>-216-<sub>` |
| **Precio** | `p.font-strong.text-secondary` | "2.500 €" · "26.000 € VB" (VB=negociable) |
| **Bajada (tachado)** | `p.line-through.text-onSurfaceNonessential` | 🎯 chollo (JSON `startingPrice` vs `price`) |
| Atributos | `span[data-dhl-promotion]` | km + "EZ MM/AAAA" |
| **TOP / PRO** | `div.bg-accent` · `a[href^="/pro/"]` | ⚠️ TOP/PRO = pago, NO señal de chollo |
| Paginación | `#pagination-container` | `/s-autos/seite:N/c216` |

> ⚠️ Clases Tailwind estáticas (sin hash) → estables. Ads intercalados: `#srpb-top-banner`, `[data-liberty-position-name^="srpb-result-list-"]`, `#srpb-middle` → ignorar. JSON de cada tarjeta (`resultAds`): `price`/`startingPrice`/`posterType`/`topAd`/`priorityAd`.

### 🇩🇪 Selectores ESTABLES de filtros en AutoUncle (`name` · 18-ago-2026)

> Los `id` de AutoUncle llevan hash (cambian), pero los **`name` son estables** → `[name="..."]`. Agregador: SOLO rotación + validación (A8, NUNCA precio de referencia).

| Filtro | Selector | Valores |
|---|---|---|
| Precio | `[name="minPrice"]` / `[name="maxPrice"]` | select 200-100k |
| Año | `[name="minYear"]` / `[name="maxYear"]` | select 1950-2026 |
| Combustible | `[name="fuelTypes"]` | `El`·`El_Hybrid`·`Benzin`·`Diesel` |
| Kilometraje | `[name="minKm"]` / `[name="maxKm"]` | select 0-400k |
| Cambio | `[name="gear"]` | radio Cualquier/Auto/Manual |
| **Equipamiento** | `[name="popularOptions"]` | `hasAppleCarPlay`·`hasAndroidAuto`·`hasBluetooth`·`hasSeatHeat`·`hasParkingCamera`·`hasParking`·`hasDistanceControl`·`hasTowBar` |

> **Máximo equipamiento AutoUncle:** `hasAppleCarPlay` + `hasAndroidAuto` + `hasSeatHeat` + `hasParkingCamera` + `hasDistanceControl`. Marca/Modelo/Versión = combobox autocompletado (placeholder "Elija marca").

### AutoScout24.de — filtros útiles
- `fregfrom` (URL) o filtro "Erstzulassung von"
- Potencia: km/Leistung (kW) → acota para no ver versiones base
- **Sort: "Neueste zuerst"** → ver anuncios frescos (mejor negociables)
- "Bajada de precio reciente" si estuviera disponible

### Coches.net — atajos que merecen
- Rangos de precio预设: "hasta 10.000 €", "hasta 20.000 €" (clic directo)
- "Segunda mano particulares" → excluye concesionarios (chollos)
- "Etiqueta CERO/ECO" → filtra por DGT

### AutoUncle — sus superpoderes
- **Sort "Bajada de precio reciente"** → chollos negociables
- **Sort "En venta - Más antiguo"** → anuncios estancados (margen para regatear)
- Filtros: combustible, km, año, potencia

### �🇪 AutoUncle — leer la tarjeta (18-ago-2026)

| Dato | Selector | Notas |
|---|---|---|
| Enlace ficha | `a[href^="/es/d/"]` | `/es/d/222400250-usado-...` → **ID** para deduplicar |
| **Rating precio (1-5)** | `aria-label` enlace: "\| Buen precio" | Super(5) · Buen(4) · Justo(3) · Un poco caro(2) · Caro(1) |
| Atributos | `ul._PuGQy > li._ZTpYr` | año/km·acabado·combustible·carrocería·cambio·CV·CO2·consumo |
| Badge cambio | `.x4zE1._hbiHx[data-type]` | `positive`="-5%" · `neutral`="Nuevo" |
| **Precio actual** | `._i2QOc` | vs `._O_cMy` (referencia, tachado) |
| **Por debajo del mercado** | botón `._CikIC` `span._2OgvT` | 🎯 ahorro € → chollo |
| **Cambio de precio** | `[data-testid="listing-item--price-history"]` | "↓ -X%" |
| **Días en venta** | botón `._CikIC` (Clock) | 🎯 rotación |
| Concesionario/Ubicación | `[data-testid="source-label"]` + `div[data-font="body-small"]` | verificado = escudo |
| Contador total | `._oCbI6` | "1 - 25 de 1.974.089" → oferta total |

> ⚠️ Sufijos CSS (`_i2QOc`) cambian por build → usar `data-*`/`aria-label`/`data-testid` (sin hash). Carrusel "Ofertas seleccionadas" (`a._DF8g3`) = patrocinado → NO contar. Ads `#sr_N` → ignorar. A8: AutoUncle SOLO rotación/validación.

### �🇪🇸 Selectores ESTABLES de filtros en Wallapop (18-ago-2026)

> Wallapop (`https://es.wallapop.com/coches-segunda-mano`) usa **web components** `walla-*` con `name`/`id` estables. Los rangos son `<wallapop-range-selector>` con sliders `#fromSelector`/`#toSelector`.

| Filtro | Selector | Valores |
|---|---|---|
| Fecha | `[name="time_filter-radio-group-single-selection"]` | `today`·`lastWeek`·`lastMonth` |
| **Precio** | `<wallapop-range-selector>` `#fromSelector`/`#toSelector` | slider 0-100k |
| **Marca** | `[name="brand-radio-group-single-selection-regular"]` | radio por marca: `id="Audi"`, `id="SEAT"`, `id="CUPRA"`... (+ `#search-input`) |
| **Modelo** | `[name="model-radio-group-single-selection-regular"]` | al elegir marca |
| **Km** | `<wallapop-range-selector>` | 0-250k |
| **Año** | `<wallapop-range-selector>` | 1980-2026 |
| Etiqueta DGT | `[id="zero"][id="eco"][id="c"][id="b"]` | checkboxes |
| Carrocería | `[id="sedan"][id="family_car"][id="4X4"][id="coupe_cabrio"]` | checkboxes |
| Combustible | `[id="gasoline"][id="gasoil"][id="hybride"][id="hybride_plugin"]` | checkboxes |
| Cambio | `[id="manual"][id="automatic"][id="semiautomatic"]` | checkboxes |
| Vendedor | `[name="seller_type-radio-group-single-selection"]` | `private`·`professional` |

> ⚠️ **Wallapop NO tiene filtro de equipamiento** → máximo equipamiento por `keywords=` (ej. "techo", "virtual cockpit") o validando fichas.
> **Suelo:** `order_by=price_asc` en URL (abajo).

### 🇪🇸 Wallapop — leer la tarjeta (18-ago-2026)

| Dato | Selector | Notas |
|---|---|---|
| Enlace ficha | `a[href^="/item/"]` | `/item/seat-leon-2000-1292407193` → **ID** para deduplicar |
| **Precio** | `strong[aria-label="Item price"]` | 🎯 sin hash, nunca falla |
| Título | `h3[class*="item-card_ItemCard__title"]` | |
| **Atributos** | `label[class*="item-card_ItemCard__attributes"]` | "Diésel · Manual · 90 caballos · 2000 · 305.000 km" |
| Descripción | `p[class*="item-card_ItemCard__description"]` | equipamiento aquí |
| Fotos | `span[class*="imageCounter"]` | "1 / 9" |
| Vendedor | `span[class*="item-card-seller_ItemCardSeller__sellerName"]` | particulares = chollos |
| Rating | `<wallapop-rating-indicator>` (shadow) | leer `aria-label="5/5 stars.23 'reviews'"` |

> ⚠️ Sufijos CSS Modules (`__gajNu`) cambian por build → usar prefijos `item-card_*` + `[aria-label]`. Web components `walla-*` = shadow DOM.
> ⚠️ Landing SEO (`seo-landing_*`, "Ver N productos más") = Novedades, NO búsqueda activa → usar `/search?keywords=`.

### Wallapop — maximizar muestra
- `order_by=price_asc` en URL (chollos arriba)
- `Page Down` 5-8 veces hasta agotar scroll
- Filtra por provincia si el cliente es local

### 🇪🇸 Selectores ESTABLES del modal de filtros Milanuncios (18-ago-2026)

> Modal `.sui-MoleculeModal` (`.ma-FormFiltersPopoverModal`). Los campos desplegables (marca, etiqueta, combustible, plazas, color) requieren **3 pasos**: clic en el campo (`[data-testid=...]`) → seleccionar opción → botón **"Aplicar filtro"** (`.ma-FormSearchButtonBar-button`). Confirmar con el botón inferior `[data-testid="FORM_LIST_FILTERS_V2_SEARCH_BUTTON"]` ("Ver +N anuncios").

| Filtro | Selector | Valores |
|---|---|---|
| Categoría | `[data-testid="cat"]` + buscador `#categories-category-tree-picker-suggester-input` | `[data-value="Coches"]` |
| **Marca** | `[data-testid="carMake"]` + buscador `input[placeholder="Buscar marca"]` | option `[data-value="VOLKSWAGEN"]` `[data-value="CUPRA"]` `[data-value="SEAT"]` `[data-value="AUDI"]` (MAYÚSCULAS) |
| Ubicación | `[data-testid="location"]` | "Toda España" / provincia |
| Contado/Financiado | radio `role="radiogroup" aria-labelledby="financedPrice"` | botón `[aria-label="Contado"]` / `[aria-label="Financiado"]` |
| **Precio** | `#price-from` / `#price-to` (type=number) | rango directo € |
| Anuncios con rebaja | switch `#isPriceDropped` (`role="switch"`) | bajadas de precio |
| **Km** | `#kms-from` / `#kms-to` | rango directo |
| **Año** | `#year-from` / `#year-to` | rango directo |
| Potencia | `#potencia-from` / `#potencia-to` | rango directo (CV) |
| Cambio | radio `aria-labelledby="cajacambio"` | `[aria-label="Manual"]` / `[aria-label="Automático"]` |
| Etiqueta ambiental | `[data-testid="environmentalLabel"]` + checkbox `#0`(CERO) `#ECO` `#C` `#B` `#NO_LABEL`(A) | multi + "Aplicar filtro" |
| Combustible | `[data-testid="fuels"]` + checkbox `#diesel` `#gasoline` `#electric` `#hybrid` `#plug_in_hybrid` `#glp` `#other` | multi + "Aplicar filtro" |
| Tipo vendedor | radio `aria-labelledby="vendedor"` | `[aria-label="Particular"]` / `[aria-label="Profesional"]` |
| Con garantía | switch `#hasWarranty` | |
| Certificado marca | switch `#isCertified` | |
| Puertas | radio `aria-labelledby="numpuertas"` | `[aria-label="2"]`..`[aria-label="5"]` |
| Plazas | `[data-testid="seats"]` + checkbox `#FOUR_SEATS` `#FIVE_SEATS`... | multi + "Aplicar filtro" |
| Color | `[data-testid="color"]` | option `[data-value="Negro"]` `[data-value="Blanco"]`... |
| Tipo anuncio | radio `aria-labelledby="demanda"` | `[aria-label="Oferta"]` (defecto) / `[aria-label="Demanda"]` |

> **Botones:** limpiar `.ma-FormListFiltersV2-cleanFilters` · aceptar `[data-testid="FORM_LIST_FILTERS_V2_SEARCH_BUTTON"]` · cerrar `.sui-MoleculeModal-close`.
> ⚠️ **Milanuncios NO tiene filtro de equipamiento** → máximo equipamiento por `keywords=` o validando fichas.
> **Suelo:** los filtros también funcionan en URL (`hasta`/`desde`, `anoh`, `kilometersTo`, `fuels`, `cajacambio`, `engineHpTo`, `puertas`) — ver `paginas_reales.md`.

### 🇪🇸 Milanuncios — leer la tarjeta (18-ago-2026)

| Dato | Selector | Notas |
|---|---|---|
| Contenedor | `.ma-AdList` (`data-testid="AD_LIST"`) | tarjetas `article.ma-AdCardV2` (`data-testid="AD_CARD"`) |
| Título | `h2.ma-AdCardV2-title` | en `.ma-AdCardListingV2-TitleLink` |
| Enlace ficha | `.ma-AdCardListingV2-TitleLink[href]` | `/marca-de-segunda-mano/modelo-ID.htm` → ID para **deduplicar** |
| Patrocinado | `.ma-AdCardV2-headerListing-caption--highlighted` | "Destacado" → NO contar como señal de precio |
| **Precio contado** | `.ma-AdMultiplePrice-cashPriceTitle` → `.ma-AdPrice-value` | ⚠️ usar SIEMPRE contado (IVA incl) |
| Precio financiado | `.ma-AdMultiplePrice-financedPriceTitle` → `.ma-AdPrice-value` | ignorar (infla) |
| **Bajada** | `.ma-AdPrice-iterationPreviousValue` → `.ma-AdPrice-iterationNewValue` | 🎯 chollo/negociable |
| Km/año/comb. | `ul.ma-AdTagList li .ma-AdTag-label[title]` | 3 tags: km · año · combustible |
| Extras | `.ma-AdCardListingV2Extras-item` | ej. "Garantía 12 meses" |
| Ubicación | `address.ma-AdLocation` `.ma-AdLocation-text` | |
| Descripción | `p.ma-AdCardV2-description` | |
| Tiempo | `p.ma-AdCardV2-time` | "Hace N días" |
| Skeletons | `.sui-PerfDynamicRendering-placeholder` `[data-testid="cardSkeleton"]` | scroll infinito → ignorar |
| Carrusel | `.ma-ContentListingCarousel` `.ma-AdCardCarousel` | recomendaciones → NO contar |
| Ads | `.ma-ContentListing-advertising-*` (`#ad-inline-*`) | ignorar siempre |

> Tarjeta sin `href` + sin `.ma-AdCardV2-time` = anuncio incompleto → `man`. One-tap: `.ma-FormOneTapFilter-tag` (garantías, financiación, revisados).

### kleinanzeigen — descubrir chollos
- **Sort "Niedrigster Preis"** → chollos arriba
- Mirar **precio actual vs precio anterior** (bajada visible en tarjeta)
- Filtrar "Privat" en sidebar (solo particulares)

---

## 🔬 FILTRADO FINO Y LISTADOS ENGAÑOSOS (12-ago-2026)

> Lecciones del caso real (encargo 9.000 € · 2016+ · ≤150k · +120cv · gasolina · 5p):

### Conversión CV ↔ kW (mobile.de usa kW, España usa CV)
```
kW = CV × 0,7355   (también 1 PS ≈ 0,7355 kW)

Referencias rápidas:
  120 cv ≈ 88 kW     125 cv ≈ 92 kW     130 cv ≈ 96 kW
  140 cv ≈ 103 kW    150 cv ≈ 110 kW    200 cv ≈ 147 kW
```
- Si el cliente pide "+120 cv" → filtrar `Leistung von ≥ 88 kW`.
- Redondear SIEMPRE hacia abajo en el límite inferior (120 PS = 88,26 kW → 88).

### Filtro "5 puertas" (no siempre es directo)
- **mobile.de:** filtrar por carrocería: `Limousine` (berlina), `Kombi` (familiar), `Schrägheck` (5 puertas compacto). Descartar `Coupé`, `Cabrio`, `3-Türer`.
- **Coches.net:** checkbox carrocería (Berlina/Familiar/Compacto).
- Si el portal NO tiene filtro de puertas → filtrar por carrocería + validar en fichas (mirar "Türen" en mobile.de).
- **Nunca asumir 5 puertas por el nombre del modelo** (ej. un Coupé no lo es).

### Ordenar por precio → ignorar patrocinados
- Aplicar `Sortieren → Preis (niedrigster zuerst)`.
- Los **anuncios patrocinados** (suelen ser los primeros, caros o no reales) se IGNORAN — mirar los primeros resultados orgánicos.

### Bandas de precio — el listado NO es solo el más barato (15-ago-2026)
- **Fallo real (María, 9.000 €):** 526 resultados ordenados por precio ascendente, se leyó SOLO la página 1 → se enseñaron 8 coches de 3.000-4.200 € y se perdieron DS4, 308, Astra... que TAMBIÉN entraban en presupuesto.
- **Regla:** el listado para el cliente cubre TODO el rango válido (suelo → techo), no el extremo barato. Con muchos resultados, recorrer por **bandas** (ej. 3-5k / 5-7k / 7k-techo) o paginar hasta el techo (A12).
- Un coche de 7.500 € en presupuesto con mejor equipamiento puede ser MEJOR candidato que el de 3.750 €: el objetivo es el mejor **valor** del rango, no el precio mínimo.
- **Distinción D1 vs Flujo B (15-ago-2026):** en el sondeo D1 (enumerar qué modelos caben) NO se pagan todas las páginas — 2 lecturas por mercado: **asc** (suelo, página 1) + **desc** (techo, página 1), más facetas de marca con conteo y semilla `../memoria/modelos-medidos.md`. La paginación/bandas completas son para Flujo B, donde se entregan candidatos con enlaces. El precio-desde de cada modelo sale de su primera aparición en asc/desc, no de paginar.
- **Año ensanchado (2016→2012) u otro filtro relajado:** declararlo ANTES de navegar y marcarlo en el informe (A13) — el usuario lo tolera, pero no en silencio.

### Anuncios engañosos (CRÍTICO · 12-ago-2026)
- **Síntoma:** precio anómalamente bajo en el listado (ej. 2.499 € para un coche de 2016+).
- **Causas:** coche siniestrado, fechas mal etiquetadas, error del vendedor, enganche.
- **Detección:** antes de dar un "precio desde", verificar que el anuncio tiene año/cv/km correctos y no está marcado como siniestrado. Si el mínimo es sospechoso, usar el 2º/3º orgánico.
- **Regla:** el "precio desde" de un modelo SIEMPRE sale de mobile.de (DE) o Coches.net (ES) verificado — NUNCA de AutoScout24 (A8) ni de un anuncio sin validar.

---

## ⏱️ Atajos de teclado (Claude los usa bien)

| Atajo | Para qué |
|---|---|
| `ctrl+l` (Win) / `cmd+l` (Mac) | Focus en barra de URL → pegar nueva URL |
| `Tab` | Moverse entre campos de filtro sin clic |
| `Enter` | Aplicar búsqueda/seleccionar opción combobox |
| `Page Down` / `End` | Scroll en listados infinitos (Wallapop) |
| `Escape` | Cerrar modales (cookies, popups) |
| `ctrl+f` | Buscar texto en la página (ej: "CO₂", "Unfallfrei") |

**Para dropdowns difíciles:** clic en el campo + `Tab` + flechas + `Enter` (más fiable que clic en opción).

---

## 🚦 Cuándo parar de navegar (anti-desperdicio)

| Señal | Acción |
|---|---|
| Captchas repetidos en misma fuente | Marcar bloqueada, siguiente fuente |
| Página no carga tras 2 intentos | Marcar caída, reintentar al final |
| Filtro no aplica tras 2 clics | Re-navegar con URL alternativa |
| <3 anuncios tras filtros duros | Aflojar filtros (km +20%, año -1) |
| Muestra ES <5 coches | No hay comparable sólido → EXIT 1 |
| Hueco DE-ES <8% | EXIT 1 (no sale) |

---

## 🎯 Detección de chollos (señales combinadas + selectores por portal)

Un coche es **chollo priorizable** si tiene ≥3 señales. Cada señal ya tiene **selector estable** (ver "leer la tarjeta" de cada portal):

| # | Señal | mobile.de | Coches.net | kleinanzeigen | AutoUncle | Wallapop | Milanuncios |
|---|---|---|---|---|---|---|---|
| 1 | **Rating precio del portal** | `PriceRatingBadge--label`: "Muy buen precio"(VERY_GOOD)·"Buen precio"(GOOD) | `mt-CardAdPrice-cashLabel`: "Buen precio"(4/5)·"Precio justo"(3/5) | — | `aria-label` del enlace "\| Buen precio" (4=Buen, 5=Super) | — | — |
| 2 | **Días en venta >60** (rotación) | — | — | — | botón Clock `._CikIC` "49" | — | `.ma-AdCardV2-time` |
| 3 | **Bajada reciente** | `strike-through-price` (tachado) | `mt-CardAdPrice-priceDropPercentage` "-22%" + `...OriginalPrice` | `p.line-through` (tachado) | `[data-testid="listing-item--price-history"]` "↓ -X%" | — | `.ma-AdPrice--iterationInline` + `[aria-label="Bajada de precio"]` |
| 4 | **Negociable (VB)** | — | — | "26.000 € VB" (VB=Verhandlungsbasis) | — | "Negociable" | "Precio a consultar" |
| 5 | **Privado sin concesionario** | `seller-info`: "Privatanbieter" | — | `posterType=PRIVATE` (JSON) | — | `seller_type=private` | — |
| 6 | **1 dueño / 2. Hand** | `listing-details-attributes` `<strong>Sin accidentes</strong>`/2. Hand | tags | — | — | — | tags "1 dueño" |
| 7 | **TÜV NEU / ITV nueva** | `maintenance_features-filter` `NEW_SERVICE` | tags "En stock" | badge "TÜV NEU" | — | — | `.ma-AdCardListingV2Extras-item` (garantía) |
| 8 | **Por debajo del mercado X €** | — | — | — | `._CikIC` → `span._2OgvT` "2.330 €" | — | — |

> **Lectura rápida de chollo en 1 vistazo por portal:** mobile.de = rating `PriceRatingBadge` + tachado · Coches.net = "Buen precio" + "-%" · kleinanzeigen = VB + tachado · AutoUncle = rating 4-5 + "Por debajo del mercado" + días · Milanuncios = "Bajada de precio" · Wallapop = negociable (poco fiable, validar por keywords).

**Combinación ganadora:** rating buen precio + días >60 + privado + VB → **CONTACTAR YA**.

---

## 🧠 Pensamiento antes de cada búsqueda

Antes de abrir el navegador, plantéate y di en voz alta:

```
1. ¿Qué flujo? (A/B/C/D/E) → define profundidad
2. ¿Qué fuentes tocan? (3 Fase 1 ó 7 Fase 2)
3. ¿Qué datos mínimos necesito? (precio, km, año, días publicado, CO₂)
4. ¿Cuál es mi budget de capturas? (A=70, B=50, C=100, D=8+embudo)
5. ¿Qué filtros aplicar primero para acotar?
```

Esto evita navegar a ciegas y gastar tokens de más.

---

## 📊 Plantilla de captura mental (lo que verás)

Cuando hagas screenshot, espera ver (móvil vs desktop):

| Tipo | Lo que ves en 1 captura |
|---|---|
| **Listado mobile.de** | 4-6 tarjetas con título + precio + sello + datos + vendedor |
| **Listado Coches.net** | 6-10 tarjetas con etiqueta precio + precio + datos + vendedor |
| **Listado AutoUncle** | 3-5 tarjetas con datos completos + días en venta + cambio precio |
| **Listado Wallapop** | 6-9 tarjetas limpias (título + año + km + cv + precio) |
| **Listado AS24.de** | 4-6 tarjetas con equipamiento listado + días publicado |
| **Listado kleinanzeigen** | 8-12 tarjetas con precio actual+anterior + km + EZ |
| **Ficha mobile.de** | Sección Fahrzeugdaten + Ausstattung + fotos |

Si una captura tiene menos datos de los esperados → probablemente la página no cargó bien. Recarga 1 vez.
---

## 📊 PRIORIZACIÓN POR ROI (movido de SKILL.md — Flujo B y C)

Cuando hay >3 modelos sin medir, aplicar scoring automático **antes** de empezar:

```
PRIORIDAD = MargenEstimado × VendibilidadEstimada × Urgencia
```

| Factor | Cálculo | Ejemplo |
|---|---|---|
| **MargenEstimado** | Ratio histórico del segmento | Nicho: 18%, Rotación: 10% |
| **VendibilidadEstimada** | Atractivo del modelo | Deportivo: 80, Premium: 60, Utilitario: 40 |
| **Urgencia** | ¿Hay cliente esperando? | Cliente concreto: 100, Sin cliente: 30, >1 mes sin medir: +20 |

**Tabla de priorización:**

| Modelo | Segmento | Margen est. | Vend. est. | Urgencia | PRIORIDAD |
|---|---|---:|---:|---:|---:|
| Golf 8 GTI CS | Nicho | 18% | 90 | 50 | **8.100** |
| Mercedes CLA | Rotación | 10% | 65 | 110 | **7.150** |
| BMW M240i | Nicho | 18% | 85 | 30 | **4.590** |
| Volvo XC60 T8 | Nicho | 15% | 70 | 30 | **3.150** |

**Regla:** Antes de cada sesión, puntuar los "sin medir" y proponer el top 3 al usuario.

---

## 🔗 DEDUPLICACIÓN ENTRE FUENTES (movido de SKILL.md)

**Problema:** Mismo coche en Wallapop y Milanuncios cuenta 2 veces. Infla la muestra.

**Solución:** Normalizar antes de contar:

```
Para cada coche en el pool:
  huella = (año, km_redondeado(±2%), cv, precio_redondeado(±3%), combustible)

Si huella ya existe → es duplicado → contar 1 vez, anotar fuentes
```

**Output:** "8 coches únicos en España (12 anuncios contando duplicados: 4 en Wallapop, 5 en Milanuncios, 3 en Coches.net)"

### Tabla maestra — ID estable por portal (18-ago-2026)

> Los IDs NO son comunes entre portales (cada uno publica el suyo). Esta tabla sirve para **identificar de forma estable cada anuncio dentro de su portal** (evitar contar 2x al mezclar listados/dobles pasadas) y para localizar el mismo coche al cruzar por huella.

| Portal | Cómo extraer el ID | Formato |
|---|---|---|
| mobile.de | `a[data-testid$="-link"]` href | `detalles.html?id=<ID>` (numérico) |
| Coches.net | `div[data-ad-id]` | numérico (`70666366`) |
| kleinanzeigen | `article[data-adid]` | numérico (`3483153805`) |
| AutoUncle | `a[href^="/es/d/"]` | `/es/d/<ID>-slug-...` (primer segmento) |
| Wallapop | `a[href^="/item/"]` | `/item/slug-<ID>` (último segmento) |
| Milanuncios | `.ma-AdCardListingV2-TitleLink[href]` | `/marca-modelo-<ID>.htm` (número del slug) |
| AutoScout24 | — | (portal NO prioritario, fuera del estudio) |

**Cuándo usar ID vs huella:**
- **ID** → mismo anuncio visto 2 veces en un portal (mismo concesionario, listados mezclados, doble pasada kW) → dedup inmediato.
- **Huella** → mismo coche en portales DISTINTOS (los IDs no coinciden) → el cruce real.
- Regla extra: el mismo concesionario suele usar la misma foto en varios portales → foto + huella exacta = duplicado seguro.

**Cuándo aplicar:**
- **Fase 1:** Después de recolectar Coches.net + mobile.de + AutoUncle
- **Fase 2:** Después de recolectar las 4 fuentes restantes
- **Flujo C:** Después de cada modelo escaneado

**Regla:** Si la huella coincide pero el precio difiere >10%, NO es duplicado (puede ser versión distinta).
