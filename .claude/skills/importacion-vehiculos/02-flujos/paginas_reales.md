# Páginas reales — estructura capturada (2026-08-12)

> Estructura REAL de los 7 portales, capturada navegando el 12-ago-2026.
> Para que Claude vaya directo a los datos sin explorar. Formato: qué ve en cada
> captura y dónde exactamente. URLs verificadas funcionando.

---

## 🇩🇪 mobile.de — rey alemán ✅

### URL verificada
```
https://www.mobile.de/fahrzeuge/search.html?dam=false&isSearchRequest=true&ms=<make>;<model>;<grp>;<desc>&s=Car&vc=Car&lang=de
```

### Al abrir (SIEMPRE)
- **Modal cookies:** botón "Einverstanden" (aceptar todo) / "Ablehnen" (rechazar). Clic cualquiera y seguir.

### Cabecera del listado
- **Breadcrumb:** "1.519.613 Angebote" = total global (NO usar).
- **`<h1>`:** "**X Angebote**" = total de la búsqueda (este SÍ).
- **Filtros (cabecera, no columna):**
  - Zahlungsart: **Kaufen** (dejar) / Leasen
  - **Preis** (combobox von/bis, presets 500€…90.000€)
  - **Erstzulassung** (combobox von/bis por año)
  - **Kilometerstand** (combobox von/bis, 5.000…200.000 km)
  - Botón "**N Angebote**" aplica y muestra recuento en vivo
  - Chips expandibles: Standort · Kraftstoffart · Leistung · Fahrzeugtyp · Getriebe · E-Autos
  - Secciones: Außenfarbe (checkboxes colores) · Qualitätssiegel · Klimatisierung · Ausstattung (Bluetooth/Bordcomputer/Navi/Schiebedach/HUD/Sitzheizung…) · **Anbieter** (Händler/Privatanbieter/Firmenfahrzeuge) · Händlerbewertung
- **Filtro activo por defecto:** "Beschädigte Fahrzeuge: Nicht anzeigen" (oculta dañados). Para ver siniestros: botón "Entfernen".
- **Chip "Leistung" (kW) — CRÍTICO para doble pasada:** expande el chip `Leistung` para filtrar por potencia (combobox von/bis en kW). Este campo es estructurado (del permiso de circulación) y NO falla como la variante de texto. Uso: búsqueda 2 por kW para encontrar topes de gama mal etiquetados (OPC/GTI/R/M...) → ver `playbook_filtrado.md` §Doble pasada.

### Orden "Sortieren nach" (combobox)
- **Preis (niedrigster zuerst)** ← el que usar para base
- Preis (höchster zuerst) · Kilometerstand · Erstzulassung · Inserate (älteste/neueste zuerst = días publicado)

### Tarjeta de anuncio
```
[NEU / Gesponsert / Top]              ← etiquetas (Gesponsert NO contar)
TÍTULO: "Volkswagen Golf 7 GTI Clubsport..."
PRECIO: "24.900 €" + sello: "Sehr guter Preis" / "Guter Preis" / "Hoher Preis"
   · "€¹" = bruto (IVA incl) · "zzgl. MwSt." = neto (IVA aparte)
   · "ggf. zzgl. Lieferkosten", "Lieferung möglich"
DATOS: "[Unfallfrei • ] EZ MM/AAAA • km • kW (PS) • Combustible"
   Ej: "EZ 04/2016 • 84.900 km • 265 kW (360 PS) • Benzin"
TAGS (imagen+texto): "380PS Software", "2. Hand", "LED,RKam,PANO,Virtual", "TÜV&INSP. NEU+GARANTIE"
VENDEDOR:
   · Händler: nombre + "4.9 Sterne (96)" + "31275 Lehrte"
   · Privatanbieter: "47805 Krefeld, Privatanbieter" (sin estrellas)
BOTONES: Kontakt / Parken
ENLACE ficha: /fahrzeuge/details.html?id=<id>
```

### Ficha (details.html?id=)
Secciones a leer: **"Fahrzeugdaten"** (km, Erstzulassung, Leistung, Getriebe, Farbe, Schadstoffklasse, Anzahl Fahrzeughalter, **CO₂** si existe) · **"Ausstattung"** (equipamiento) · precio (bruto/neto) · advertencias "Unfallschaden", "Nicht unfallfrei", "NUR AN AUTOHÄNDLER".

**Truco:** la URL con `ms=` NO filtra siempre (a veces muestra total). Más fiable usar el **buscador superior** o los filtros de cabecera.

### 🇪🇸 mobile.de ES — página de filtros completa (18-ago-2026)

> Versión en español de la **búsqueda avanzada**: `https://www.mobile.de/es/s/auto?s=Car&vc=Car&ref=quickSearch`
> En esta página se ven TODOS los filtros (botón "Más filtros"). Es la **fuente de selectores `data-testid` estables** (ver `playbook_filtrado.md` §Selectores ESTABLES).
> Para filtrar por URL igual funciona: `ms=<make>;<model>;;` · `fr=<año>:` · `p=<min>:<max>` · `fu=PETROL|DIESEL|HYBRID` · `pw=<kW>:` · `q=<texto>` · `sb=p&od=up` (precio asc).

**Estructura de la página (por secciones):**
1. **Datos básicos:** Fabricante (`make-incl-0`) → Modelo (`model-incl-0`) → Variante (texto, ej "GTI") · Tipo de vehículo (checkboxes: Cabrio, OffRoad, SmallCar, EstateCar, Limousine, SportsCar, Van) · Asientos · Puertas · **Precio** (desde/hasta) · **Primera matriculación** · **Kilometraje** · Estado (Nuevo/Usado) · Mantenimiento.
2. **Vendedor y datos técnicos:** Vendedor (concesionario/particular/empresa) · ITV válida · Propietarios · País · Ciudad/CP · **Combustible** (checkboxes) · **Potencia** (cv o kW, min/max) · Cilindrada · Tracción · Transmisión (Automático/Semiautomático/Manual).
3. **Consumo y exterior:** consumo combinado · pegatina emisiones · color exterior · acoplamiento remolque · sensores de aparcamiento · cámara / **cámara 360°** · control de crucero (adaptativo).
4. **Extras (equipamiento):** techo corredizo/panorámico, faros LED/xenón/láser, llantas, portón eléctrico, paquete invierno, suspensión deportiva, luces adaptativas, etc.
5. **Interior:** color interior · material (Alcantara/cuero) · **`Panel de instrumentos digital`** (cuadro digital) · **`Pantalla Head-up`** · **`Calefacción de asiento`** · Android Auto · Apple CarPlay · navegación · asientos deportivos/ventilados/masaje · carga inducción · volante multifunción.
6. **Detalles de la oferta:** valoración concesionario · anuncio online desde · garantía · descuentos · comercial/exportación · dañados · programa usados certificados.

> **Regla:** al buscar "máximo equipamiento", marcar en Interior `Panel de instrumentos digital` + `Pantalla Head-up` + `Calefacción de asiento` y en Extras `Techo panorámico` + `Faros LED` (los 5 = unidad full reproducible).

---

## 🇩🇪 AutoScout24.de — verificación cruzada ✅

### URL verificada
```
https://www.autoscout24.de/lst/volkswagen/golf/va_gti?fregfrom=<año>&atype=C
```
⚠️ **El slug `golf-gti` directo NO existe.** Para versiones: `/lst/<marca>/<modelo>/va_<version>`. Para BMW en .de: `1er 2er 3er 4er` (no `serie-1`).

### Cabecera
- **`<h1>`:** "**X Angebote für Volkswagen Golf Gti**" = total búsqueda.

### Tarjeta de anuncio
```
TÍTULO: "Volkswagen Golf GTI | TCR DSG ACC Pano RearView..."
[Merken]              ← favorito
<nº días>             ← ¡días publicado visible! (28, 24, 9...)
€ <precio>            ← "€ 27.840"
Sello: "Sehr guter Preis" / "Fairer Preis" / "Ohne Preisbewertung"
"Ab 397 € mtl. finanzieren"  ← financiación opcional
"12/2019"             ← EZ MM/AAAA
"38.359 km"
"Benzin"
"213 kW (290 PS)"
Equipamiento (lista): Sitzheizung, Schiebedach, Panoramadach, Sportsitze...
Vendedor: "Autohaus Steinböhmer GmbH" | "DE-33613 Bielefeld"
```

### Orden (combobox)
Beste Ergebnisse · **Preis aufsteigend** (asc) · Preis absteigend · Neueste zuerst · Kilometerstand · Leistung · Erstzulassung.

**Regla skill:** SOLO contar y validar hueco. NUNCA fijar precio de referencia.

---

## 🇩🇪 AutoUncle — joya días publicado ✅

### URL verificada
```
https://www.autouncle.es/es/coches-segunda-mano/<Marca>/<Modelo>
```
⚠️ La URL con modelo incorrecto redirige (ej: "Golf GTI" cayó a "Golf I"). Si el H1 no cuadra, re-navega con el modelo exacto.

### Cabecera
- **`<h1>`:** "**<marca> <modelo>: resumen de N coches de ocasión en venta**" = total.
- "**Mostrando 1 - N de N resultados**" bajo el listado.

### 🃏 Contenedor de anuncios — selectores estables (18-ago-2026)

> Grid de resultados: `article._qzVn4` (cada tarjeta). Los sufijos CSS (`_qzVn4`, `_i2QOc`) cambian por build; usar **aria-labels + `data-testid` + `data-rating` + `[data-font]`** (estables). `data-*` y `aria-*` NO tienen hash.

| Dato | Selector | Notas |
|---|---|---|
| **Enlace ficha** | `a._p9jqN[href^="/es/d/"]` | `/es/d/222400250-usado-2025-skoda-kamiq-116-cv` → **ID AutoUncle** para deduplicar |
| **Rating precio (1-5)** | `aria-label` del enlace: "Ver detalles de ... \| Buen precio" | categoría: Super precio(5) · Buen precio(4) · Precio justo(3) · Un poco caro(2) · Caro(1) |
| Título | `h3[data-font="body-base"]` | "Usado (2025) Skoda Kamiq 116 CV \| Buen precio" |
| Versión/equipamiento | `p[data-font="body-small"]` | "Kamiq Select 85 KW LED KAMERA PDC" |
| **Atributos** | `ul._PuGQy > li._ZTpYr` | año/km · acabado · combustible · carrocería · cambio · CV(kW) · Clase CO2 · emisiones · L/100km |
| Badge cambio | `.x4zE1._hbiHx[data-type]` | `positive` = "-5%" (bajada) · `neutral` = "Nuevo" |
| Fotos | `.yMhZe` | "1 / 4" |
| **Precio actual** | `._i2QOc` | "20.770 €" |
| Precio referencia (tachado) | `._O_cMy` | "21.870 €" (valor de mercado AutoUncle) |
| **Por debajo del mercado** | botón `._CikIC` → `span.__1SuX`="Por debajo del mercado" + `span._2OgvT`="2.330 €" | 🎯 cuánto ahorra → chollo |
| **Cambio de precio** | botón `[data-testid="listing-item--price-history"]` | "↓ -5%" (historial) |
| **Días en venta** | botón `._CikIC` (icono Clock) | "49" → 🎯 ROTACIÓN directa |
| Concesionario | botón `[data-testid="source-label"]` | nombre (verificado = escudo) |
| Ubicación | `div[data-font="body-small"][data-weight="regular"]` | "45892 Gelsenkirchen, Nordrhein-Westfalen" |

> **Paginación:** `?page=N&s[available_for_online_sales]=false` (nav `._glhdq`). **Contador total:** `._oCbI6` "Mostrando 1 - 25 de 1.974.089 resultados" → oferta total del mercado.
> ⚠️ **Rating AutoUncle** (1-5) ya valida el precio del coche → úsalo como señal de chollo, pero A8: AutoUncle es SOLO rotación/validación, NUNCA fuente de precio del estudio.
> ⚠️ **Carrusel "Ofertas seleccionadas"** (`section._j4UAK`, "Concesionarios verificados", `a._DF8g3`) = **patrocinado** → NO contar como búsqueda activa.
> ⚠️ **Ads:** `.DRMsM` (`#sr_1`..`#sr_4`) → ignorar.

### Orden (combobox, 15 opciones)
Ofertas · **Precio nominal - Más barato** · Precio - Más alto · **Bajada de precio reciente** · **En venta - Más reciente/antiguo** (= días publicado) · Año · Kilometraje · Autonomía (EV).

**Joyas únicas:** "Días en venta" (rotación directa), "Cambio de precio" (negociabilidad). Úsalo como **fuente primaria para rotación**.

### 🧭 Sidebar de filtros — selects y checkboxes con `name` ESTABLE (18-ago-2026)

> Los `id` de AutoUncle llevan hash (cambian), pero los **`name` son estables** → Claude los usa con selector `[name="..."]`. Es un **agregador** (A8: NUNCA referencia de precio; SOLO rotación + validación de hueco).

| Filtro | Selector (`name`) | Valores |
|---|---|---|
| Precio min/max | `[name="minPrice"]` / `[name="maxPrice"]` | select (200…100.000 €) |
| Año min/max | `[name="minYear"]` / `[name="maxYear"]` | select (1950-2026) |
| Combustible | `[name="fuelTypes"]` | checkboxes: `El` (Eléctrico), `El_Hybrid` (Híbrido), `Benzin` (Gasolina), `Diesel` (Diésel) |
| Kilometraje min/max | `[name="minKm"]` / `[name="maxKm"]` | select (0…400.000) |
| Cambio | `[name="gear"]` | radio: Cualquier / Automático / Manual |
| **Opciones populares (equipamiento)** | `[name="popularOptions"]` | checkboxes: `hasAppleCarPlay`, `hasAndroidAuto`, `hasBluetooth`, `hasAutoGear`, `hasSeatHeat` (asientos calefactados), `hasParkingCamera` (cámara trasera), `hasParking` (sensores), `hasDistanceControl` (ACC), `hasTowBar` (enganche) |

> **Máximo equipamiento en AutoUncle:** marcar en `popularOptions` `hasAppleCarPlay` + `hasAndroidAuto` + `hasSeatHeat` + `hasParkingCamera` + `hasDistanceControl`. Marca/Modelo/Versión son combobox de autocompletado (placeholder "Elija marca") → escribir texto + elegir sugerencia.

---

## 🇩🇪 kleinanzeigen.de — chollos particulares ✅

### URL verificada
```
https://www.kleinanzeigen.de/s-<marca>-<modelo>/k0
```

### Al abrir
- **Modal cookies:** "Alle akzeptieren" / "Datenschutzeinstellungen". Clic "Alle akzeptieren".

### Cabecera
- **`<h1>`:** "**1 - 25 von N Ergebnissen für "<búsqueda>" in Deutschland**" = total.
- Subfiltro: "Angebote (4.138)" vs "Gesuche (43)" → dejar en Angebote.

### 🧭 Sidebar de filtros — 3 tipos distintos (18-ago-2026)

> El sidebar de kleinanzeigen (URL `https://www.kleinanzeigen.de/s-autos/c216`) tiene **3 tipos de filtro con selectores distintos**:

**Tipo 1 · Enlaces de atributo (marca, combustible, cambio, carrocería, puertas, estado, anbieter...) — se navega directo por URL, sin clic:**
- Cada opción es un `<a href=".../c216+autos.marca_s:valor">` → el filtro se aplica al navegar.
- Marca: `autos.marke_s:volkswagen` · Combustible: `autos.fuel_s:benzin|diesel|hybrid|elektro` · Cambio: `autos.shift_s:automatik|manuell` · Carrocería: `autos.typ_s:limousine|kombi|suv|cabrio|coupe|kleinwagen` · Puertas: `autos.anzahl_tueren_s:4_5` · Estado: `autos.schaden_s:nein|ja` · Anbieter: `anbieter:privat|gewerblich`.
- **Combinables en la URL** con `+`: `.../c216+autos.marke_s:seat+autos.fuel_s:benzin` (marca Seat + gasolina).

**Tipo 2 · Rango numérico (precio, km, año, potencia, HU) — inputs con ID estable `brwse-attr-*`:**
- Precio: inputs `srchrslt-brwse-price-min` / `srchrslt-brwse-price-max` (placeholder "Von"/"Bis") + botón aplicar.
- Kilometraje: `brwse-attr-autos.km_i-min` / `-max` (presets 5.000…150.000).
- Año (Erstzulassungsjahr): `brwse-attr-autos.ez_i-min` / `-max` (1960-2026).
- Potencia (Leistung): `brwse-attr-autos.power_i-min` / `-max` (34…252 PS).
- HU válida: `brwse-attr-autos.tuevy_i-min`.
- **Al rellenar + clic en el botón flecha (submit) → se aplica.**

**Tipo 3 · Checkboxes de equipamiento (Außenausstattung / Innenausstattung / Sicherheit) — con ID estable `checkbox-autos.*` + botón "Übernehmen":**
- Exterior: `checkbox-autos.trailer_coupling_b` (enganche) · `park_assistant_b` (Einparkhilfe) · `alluminium_rims_b` (llantas) · `xenon_led_light_b` (faros Xenon/LED).
- Interior: `air_conditioning_b` (clima) · `navi_b` · `radio_tuner_b` · `bluetooth_b` · `handsfree_speaker_b` · **`sunroof_b` (techo)** · **`seat_heating_b` (calefacción asientos)** · `speed_control_b` (Tempomat) · `non_smoking_b`.
- Seguridad: `abs_b` · `full_service_history_b` (Scheckheft).
- ⚠️ **A diferencia de Coches.net, aquí SÍ hay botón aplicar**: tras marcar checkboxes → clic en `[data-cy="clickable-options-apply-button"]` ("Übernehmen").

> **Máximo equipamiento en kleinanzeigen:** marcar `checkbox-autos.sunroof_b` + `checkbox-autos.seat_heating_b` + `checkbox-autos.xenon_led_light_b` + `checkbox-autos.navi_b` + `checkbox-autos.full_service_history_b` → Übernehmen.

### Tarjeta de anuncio
```
<edad días>           ← días publicado (al inicio)
<CP> <ciudad>
TÍTULO: "VW Golf 7 GTI Clubsport | Schalensitze..."
DESCRIPCIÓN (preview)
<precio actual> € | <precio anterior> €    ← ¡BAJADA DE PRECIO visible!
<km> km | EZ MM/AAAA
"VB"                  ← Verhandlungsbasis = negociable
"Heute, 16:30"        ← fecha si reciente
```

### Orden (selector)
**Neueste** · **Niedrigster Preis** · Höchster Preis.

**Joyas:** precio actual vs anterior (bajada = margen negociable) + edad anuncio en días. Chollos de particulares (sin IVA recuperable).

---

## 🇪🇸 Coches.net — comparable español ✅

### URL verificada (la antigua redirige a NOTICIAS)
```
✅ https://www.coches.net/segunda-mano/coches/<marca>-<modelo>
❌ /<marca>-<modelo>-segunda-mano/ → REDIRIGE a /noticias/t/...
```

### Al abrir
- **`<h1>`:** "259.015 Coches de segunda mano y ocasión" = total global (NO usar).
- **Para recuento búsqueda:** filtrar y mirar contador tras aplicar.
- **Errores React en consola:** ruido normal, la UI funciona.

### Filtros (accesos rápidos)
- **Combustible:** Diésel · Eléctrico · Gas · GLP · Gas natural · Gasolina · Híbrido · Híbrido enchufable.
- **Carrocería:** 4x4 · Berlina · Cabrio · Coupé · Familiar · Monovolumen · Pick Up.
- Atajos: particulares · 7 plazas · automáticos · hasta 1.000/2.000/.../10.000 € · colores · regiones.

### 🧭 Sidebar de filtros — acordeón con IDs ESTABLES (18-ago-2026)

> Coches.net **NO tiene página de filtros aparte**: el sidebar se abre en el propio listado (`.mt-SearchSidebar-filters`) con **acordeones** que se expanden al hacer clic en su `groupTrigger`. Cada grupo tiene un **`id` estable** (no cambia con el CSS) → Claude los usa con selector `#<id>` o por el texto del grupo.
> ⚠️ **"Filtros se aplican al marcarlos"**: en Coches.net los checkboxes se aplican **en vivo al marcar** (no hay botón "aplicar"). Tras cada marcado, el contador se actualiza solo.

| Grupo (sidebar) | ID estable (`id="..."`) | Contenido |
|---|---|---|
| Tipo de coche | `vehicleTypeGroup` | Nuevos/Km0/Usados, tipo |
| Marca y modelo | `makeGroup` | marcas → modelos (anidado) |
| Precio | `priceGroup` | desde/hasta |
| Servicios online | `onlineServicesGroup` | financiación, garantía... |
| Ubicación | `locationGroup` | provincia/región |
| Vendedores | `sellerGroup` | particulares / profesionales |
| Año | `yearGroup` | desde/hasta |
| Kilómetros | `kmsGroup` | desde/hasta |
| Carrocería | `bodyTypeGroup` | berlina/SUV/familiar/coupé... |
| Motor | `motorGroup` | combustible + potencia (CV) |
| Etiqueta DGT | `environmentalLabelGroup` | CERO/ECO/C/B |
| Eléctricos | `electricGroup` | autonomía/carga |
| **Equipamiento** | `equipmentGroup` | techo solar, cámara, GPS, etc. (el único para equipamiento) |
| Color | `colorGroup` | colores exteriores |

**Cómo expandir un grupo:** clic en el `button.mt-FiltersGroupedByAccordion-groupTrigger` dentro del grupo con ese `id`. Al expandirse se ven los checkboxes/inputs. Marcar → se aplica solo (recuento en vivo).

> **Equipamiento en Coches.net es LIMITADO** (solo `equipmentGroup`: techo solar, cámara, GPS, climatizador... **NO hay cuadro digital**). Para máximo equipamiento: marcar techo solar + cámara como proxy, o validar el nivel en 1-2 fichas (excepción puntual a A17).

### Tarjeta de anuncio
```
TÍTULO: "AUDI Q2 S line 30 TDI"
ETIQUETA PRECIO: "Buen precio" / "Precio justo"   ← priceRankIndicator VISIBLE
PRECIO: "21.990 €"
   · Financiado: "Financiado: 18.990 € · 251,90 €/mes*" → usar el CONTADO
EXTRAS: "Garantía 1 año" · "IVA incluido" · "Reservable"
DATOS: "Diesel | 2021 | 90.507 km | 116 cv | Madrid"
VENDEDOR: "Profesional 4.2" (valoración) · "1/17" (foto/total) · botón "Comparar"
```

**Clave:** "Buen precio" = `4`, "Precio justo" = `3` (priceRankIndicator). Úsalo como señal. CV directo (no kW).
**Doble pasada:** Coches.net muestra CV en la tarjeta (`116 cv`). Para topes de gama mal etiquetados, usar el filtro **"Potencia"** (en CV) además de la búsqueda por texto → ver `playbook_filtrado.md` §Doble pasada.

---

## 🇪🇸 Wallapop — chollos + scroll ✅

### URL verificada
```
https://es.wallapop.com/search?category_id=100&keywords=<marca>%20<modelo>&order_by=<orden>
```
⚠️ `/app/search` redirige a `/search`. Órdenes URL: `most_relevance` · `price_asc` · `price_desc` · `newest` · `closest`.

### 🃏 Contenedor de anuncios — selectores estables (18-ago-2026)

> Cada tarjeta = `a.item-card_ItemCard--horizontal__gajNu` (`href="/item/<slug>-<ID>"`). Grid = `.item-card-grid_ItemCardGrid__Rd15w` (`aria-label="Items list"`). Los sufijos `__XXXXX` (CSS Modules) cambian por build; usar los `aria-label`/**prefijos** `item-card_*` (estables) + el `href`.

| Dato | Selector | Notas |
|---|---|---|
| **Enlace ficha** | `a[href^="/item/"]` | `/item/seat-leon-2000-1292407193` → **ID numérico** para deduplicar |
| **Precio** | `strong[aria-label="Item price"]` | 🎯 muy estable (sin hash): "1.700 €" |
| Título | `h3[class*="item-card_ItemCard__title"]` | "SEAT Leon 2000" |
| **Atributos** | `label[class*="item-card_ItemCard__attributes"]` | texto plano: "Diésel · Manual · 90 caballos · 2000 · 305.000 km" (combustible · cambio · cv · año · km) |
| Descripción | `p[class*="item-card_ItemCard__description"]` | texto del vendedor (equipamiento aquí) |
| Fotos | `span[class*="imageCounter"]` | "1 / 9" (nº de fotos) |
| Vendedor | `span[class*="item-card-seller_ItemCardSeller__sellerName"]` | nombre (particulares = chollos) |
| Rating vendedor | `<wallapop-rating-indicator>` | shadow DOM: `aria-label="5/5 stars.23 'reviews'"` → score + nº reviews |

> ⚠️ **Selectores CSS Modules**: el sufijo `__gajNu`/`__pVpdc` cambia en cada build, pero el **prefijo** (`item-card_ItemCard__price`, `item-card_ItemCard__title`...) es estable. Para el precio NUNCA falla `[aria-label="Item price"]`.
> ⚠️ **Web components `walla-*`**: `<wallapop-rating-indicator>`, `<walla-icon>` tienen shadow DOM — el rating se lee del `aria-label` del contenedor interno.
> ⚠️ Este HTML es de la **landing SEO** (`seo-landing_SeoLandingPage__main`, botón "Ver 728.154 productos más") — es "Novedades", NO la búsqueda activa. Usar `/search?keywords=` para medir.
> **Equipamiento** (techo, cuadro, cámara...) solo aparece en la descripción → validar fichas o `keywords=`.

### Filtros
Botón "Filtros" abre modal. Acepta cookies al cargar.

### 🧭 Sidebar de filtros — sliders + radios/checkboxes estables (18-ago-2026)

> URL del sidebar: `https://es.wallapop.com/coches-segunda-mano`. Los filtros son **web components** (`walla-*`) con `name`/`id` **estables**. Los rangos usan `<wallapop-range-selector>` con inputs `#fromSelector` / `#toSelector` (arrastrar los sliders).

| Filtro | Selector | Valores/uso |
|---|---|---|
| Fecha publicación | `name="time_filter-radio-group-single-selection"` | radio: `today` (Hoy) · `lastWeek` (7 días) · `lastMonth` (30 días) |
| **Precio** | `<wallapop-range-selector>` + `#fromSelector`/`#toSelector` | slider 0-100.000 € |
| **Marca** | radio `name="brand-radio-group-single-selection-regular"` | un radio por marca: `id="Audi"`, `id="BMW"`, `id="SEAT"`, `id="CUPRA"`, `id="Volkswagen"`... (con buscador `#search-input`) |
| **Modelo** | radio `name="model-radio-group-single-selection-regular"` | se rellena al elegir marca |
| **Kilometraje** | `<wallapop-range-selector>` + `#fromSelector`/`#toSelector` | slider 0-250.000 km |
| **Año** | `<wallapop-range-selector>` | slider 1980-2026 |
| Garantía incluida | `#toggle-filter` (checkbox `name="toggle-filter"`) | toggle garantía ≥1 año |
| Distintivo ambiental | checkboxes `id="zero"` (CERO) · `id="eco"` (ECO) · `id="c"` (C) · `id="b"` (B) · `id="not_available"` | etiqueta DGT |
| Color | checkboxes `id="black"` · `gray` · `white` · `blue` · `red` · `green` · `beige`... | colores |
| Plazas | `<wallapop-range-selector>` | slider 1-8 |
| Puertas | `<wallapop-range-selector>` | slider 2-6 |
| Caballos | `<wallapop-range-selector>` | slider 0-450 cv |
| Carrocería | checkboxes `id="small_car"` · `coupe_cabrio` · `sedan` · `family_car` · `mini_van` · `4X4` · `van` · `convertible_car` | tipo |
| Combustible | checkboxes `id="gasoline"` · `gasoil` · `electric-hybrid` · `hybride` · `hybride_plugin` · `lpg` · `cng` · `others` | |
| Cambio | checkboxes `id="manual"` · `automatic` · `semiautomatic` | |
| Tipo vendedor | radio `name="seller_type-radio-group-single-selection"` | `private` (Particular) · `professional` (Profesional) |

> ⚠️ **Equipamiento en Wallapop NO existe como filtro** — no hay checkboxes de techo/cuadro digital (solo los listados lo muestran en el texto). Para máximo equipamiento, buscar por palabras clave en `keywords=` (ej. `techo`, `virtual cockpit`) o filtrar por marca/modelo y validar en las fichas.
> **Orden URL:** `order_by=price_asc` para ver el suelo (ya documentado arriba).

**Patrón:** scroll infinito (`Page Down` hasta ~20-25 anuncios). Sin contador global visible. Anuncios incompletos → `man`.

---

## 🇪🇸 Milanuncios — contado/financiado ✅

### URL verificada
```
https://www.milanuncios.com/coches-de-segunda-mano/?s=<marca>+<modelo>
```

### URL con filtros + paginación (verificada 15-ago-2026)
```
https://www.milanuncios.com/coches-de-segunda-mano/?anoh=2013&cajacambio=manual&engineHpTo=200&fuels=gasoline&hasta=30000&kilometersTo=30000&puertas=5&orden=relevance&pagina=2
```
- **Paginación = `pagina=N`** (confirmado navegando: `&pagina=2` devuelve página 2).
- Filtros en URL: `anoh` (año desde) · `cajacambio` (cambio) · `engineHpTo` (CV máx) · `fuels` (gasoline/diesel) · `hasta`/`desde` (precio) · `kilometersTo` (km máx) · `puertas` · `orden` (relevance/precio).
- El listado también trae `nextToken=` (cursor de la API interna de búsqueda): **método técnico degradado** (ver `extractores.md`) — permite pedir la página siguiente vía la API del portal si la navegación real se bloquea. Declararlo siempre si se usa.

### Cabecera
- Contador visible: "**8.991 anuncios**" + "Ordenado por relevancia".

### 🧭 Modal de filtros — selectores estables (18-ago-2026)

> Botón "**Filtros**" abre el modal `.sui-MoleculeModal` (`.ma-FormFiltersPopoverModal`, título `#ma-FormFiltersPopover-title` "Filtros"). Los campos desplegables (marca, etiqueta, combustible, plazas, color) requieren **3 pasos**: clic en el campo (`[data-testid=...]`) → seleccionar opción/checkbox → botón "**Aplicar filtro**" (`.ma-FormSearchButtonBar-button`). Al final confirmar con el botón grande `[data-testid="FORM_LIST_FILTERS_V2_SEARCH_BUTTON"]` ("Ver +10.000 anuncios").

| Filtro | Selector | Valores/uso |
|---|---|---|
| Categoría | `[data-testid="cat"]` · buscador `#categories-category-tree-picker-suggester-input` | `[data-value="Coches"]` (default) |
| **Marca** | `[data-testid="carMake"]` · buscador `input[placeholder="Buscar marca"]` | option `[data-value="VOLKSWAGEN"]`, `[data-value="CUPRA"]`, `[data-value="SEAT"]`, `[data-value="AUDI"]`... (MAYÚSCULAS) |
| Ubicación | `[data-testid="location"]` | "Toda España" o provincia |
| Contado/Financiado | radio `aria-labelledby="financedPrice"` | `[aria-label="Contado"]` (defecto) / `[aria-label="Financiado"]` |
| **Precio** | `#price-from` / `#price-to` | inputs `type=number` directos (€) |
| Anuncios con rebaja | switch `#isPriceDropped` | solo anuncios que bajaron de precio |
| **Kilómetros** | `#kms-from` / `#kms-to` | inputs directos (km) |
| **Año** | `#year-from` / `#year-to` | inputs directos |
| Potencia | `#potencia-from` / `#potencia-to` | inputs directos (CV) |
| Cambio | radio `aria-labelledby="cajacambio"` | `[aria-label="Manual"]` / `[aria-label="Automático"]` |
| Etiqueta ambiental | `[data-testid="environmentalLabel"]` | checkboxes `#0`(CERO) · `#ECO` · `#C` · `#B` · `#NO_LABEL`(Etiqueta A/sin) |
| Combustible | `[data-testid="fuels"]` | checkboxes `#diesel` · `#gasoline` · `#electric` · `#hybrid` · `#plug_in_hybrid` · `#glp` · `#other` |
| Tipo vendedor | radio `aria-labelledby="vendedor"` | `[aria-label="Particular"]` / `[aria-label="Profesional"]` |
| Con garantía | switch `#hasWarranty` | garantía 1-2 años |
| Certificado marca | switch `#isCertified` | revisado por expertos |
| Nº puertas | radio `aria-labelledby="numpuertas"` | `[aria-label="2"]`-`[aria-label="5"]` |
| Plazas | `[data-testid="seats"]` | checkboxes `#FOUR_SEATS` · `#FIVE_SEATS`... |
| Color | `[data-testid="color"]` | option `[data-value="Negro"]`, `[data-value="Blanco"]`... |
| Tipo anuncio | radio `aria-labelledby="demanda"` | `[aria-label="Oferta"]` (defecto) / `[aria-label="Demanda"]` |

> **Botones:** limpiar `.ma-FormListFiltersV2-cleanFilters` · aceptar `[data-testid="FORM_LIST_FILTERS_V2_SEARCH_BUTTON"]` · cerrar `.sui-MoleculeModal-close`.
> ⚠️ **Milanuncios NO tiene filtro de equipamiento** → máximo equipamiento por `keywords=` o validando fichas. Suele ser **negociable** (chollos ES).

### 🃏 Contenedor de anuncios — selectores estables (18-ago-2026)

> Listado = `.ma-AdList` (`data-testid="AD_LIST"`). Cada tarjeta = `article.ma-AdCardV2` (`data-testid="AD_CARD"`). Scroll infinito: los huecos vacíos son skeletons `.sui-PerfDynamicRendering-placeholder` (`[data-testid="cardSkeleton"]`) → **ignorar**.

| Dato | Selector | Notas |
|---|---|---|
| Título | `h2.ma-AdCardV2-title` (en `.ma-AdCardListingV2-TitleLink`) | marca modelo (ej. "NISSAN JUKE...") |
| Enlace ficha | `.ma-AdCardListingV2-TitleLink[href]` | `/marca-de-segunda-mano/modelo-ID.htm` → el **ID numérico** sirve para deduplicar |
| Fotos | `figcaption.ma-AdCardV2-photoCaption` | nº de fotos (número) |
| Badge patrocinado | `.ma-AdCardV2-headerListing-caption--highlighted` ("Destacado") | ⚠️ **NO contar como señal de precio** |
| **Precio contado** | `.ma-AdMultiplePrice` → bloque `.ma-AdMultiplePrice-cashPriceTitle` "Precio al contado" → `.ma-AdPrice-value` | **usar SIEMPRE el contado** (IVA incluido) |
| Precio financiado | `.ma-AdMultiplePrice-financedPriceTitle` → su `.ma-AdPrice-value` | ignorar (infla el precio) |
| **Bajada de precio** | `.ma-AdPrice--iterationInline`: `ma-AdPrice-iterationPreviousValue` (tachado) → `ma-AdPrice-iterationNewValue` (nuevo) + `[role="img"][aria-label="Bajada de precio"]` | 🎯 **señal de chollo/negociable** |
| Ubicación | `address.ma-AdLocation` `.ma-AdLocation-text` | ej. "Betera (Valencia)" |
| Extras | `.ma-AdCardListingV2Extras-item` | ej. "Garantía 12 meses (1 año)" |
| **Km/año/combustible** | `ul.ma-AdTagList li .ma-AdTag-label` (`title`) | 3 tags: "97.000 kms" · "2017" · "gasolina" |
| Descripción | `p.ma-AdCardV2-description` | texto del vendedor |
| Tiempo | `p.ma-AdCardV2-time` | ej. "Hace 3 días" |
| Favorito | `button[data-testid="AD_CARD_FAVORITE_BUTTON"]` | ignorar |

> ⚠️ **Tarjeta incompleta** (ej. NISSAN JUKE del HTML: sin `href` en el título, sin `.ma-AdCardV2-time`, sin tags): anuncio aún sin montar o placeholder → `man`.
> ⚠️ **Carrusel `.ma-ContentListingCarousel`** (tarjetas `.ma-AdCardCarousel`, "Anuncios recientes de particulares"): son **recomendaciones, NO parte de la búsqueda** → no contar.
> ⚠️ **Anuncios publicitarios**: `.ma-ContentListing-advertising-banner` / `-native` (`#ad-inline-*`) → ignorar siempre.
> 🎯 **Filtros one-tap** `.ma-FormOneTapFilter-tag`: "Con garantía" · "Con garantía de 1 año" · "Con garantía de 2 años" · "Con posibilidad de financiación" · "Coches revisados por expertos".

### Filtros
Barra: Coches · Toda España · Precio · Todas las marcas. Botón "Filtros" abre modal.

**Clave:** los financiados inflan el precio de catálogo → **usar siempre el contado**.

### ⚠️ Virtualización del listado (15-ago-2026) ✅ RESUELTA con paginación por URL
- El **scroll infinito NO fiable**: solo monta la tarjeta patrocinada. La paginación por URL SÍ carga el listado completo y **respeta los filtros** (confirmado 15-ago).
- **Vía principal → paginación por URL:** `&pagina=1`, `&pagina=2`... (parámetro `pagina`). Contenedor del listado: `.ma-AdList`.
- **Bandas de precio:** `&hasta=...&desde=...` para reducir resultados.
- Si falla → marcar `bloqueada (virtualización)` en la cobertura y seguir (A7); el informe queda PARCIAL declarado.

---

## 🏁 km77 — PVP y CO₂ ⚠️ INESTABLE

### URL (cuando responde)
```
https://www.km77.com/coches/<marca>/<modelo>/<gama>/<version>/datos-tecnicos
```

### Estado 12-ago-2026
- Devolvió **503 Backend fetch failed** y **504 Gateway timeout** (Varnish/Cloudflare). Servicio intermitente.

### Si responde, leer
- **PVP** (precio oficial)
- **CO₂ (g/km)** → entrada IEDMT
- **Consumo** (l/100 km o kWh/100 km)
- **Etiqueta DGT** (CERO / ECO / C / B)
- Tipo IEDMT (híbrido enchufable / eléctrico / gasolina)

### Fallback si caído
1. Reintentar 1-2 veces con pausa.
2. **BOE Orden HAC/1501/2025** para CO₂ y tipo IEDMT oficial.
3. **Estimación declarada:** marcar `co2_confirmado: false` y decirlo (anti-patrón A3).

**NUNCA:** usar CO₂ de "otro modelo similar" o PVP de cabeza.

---

## 🎯 Mapa rápido de JOYAS por fuente

| Joya | Fuente | Dónde |
|---|---|---|
| **Días publicado** | AutoUncle · AutoScout24 · kleinanzeigen | Tarjeta (número inicial / "Días en venta") |
| **Cambio/bajada de precio** | AutoUncle (%) · kleinanzeigen (€ anterior) | Tarjeta |
| **Etiqueta calidad precio** | mobile.de · AutoScout24 · Coches.net | "Sehr guter/Guter/Hoher Preis", "Buen precio/Precio justo" |
| **Rotación histórica** | AutoUncle | "Días en venta" → mejor que `publicationDate` Coches.net |
| **IVA (bruto/neto)** | mobile.de · Coches.net | "zzgl. MwSt." / "IVA incluido" / "Financiado vs contado" |
| **Contado vs financiado** | Milanuncios · Coches.net | "Precio al contado" / "Financiado: X" |

## 🚦 Orden de ataque por flujo

**Flujo A (UNIDAD) y B (MODELO), Fase 1 — las 3 obligatorias:**
1. **mobile.de** (buscador + filtro Preis/Kilometer) → base DE.
2. **Coches.net** (`/segunda-mano/coches/<slug>`) → base ES + priceRankIndicator.
3. **AutoUncle** → días publicado + bajada de precio (rotación/negociabilidad).

**Fase 2 — las 4 restantes:**
4. **AutoScout24.de** (`/lst/.../va_<version>`) → validar hueco DE (no fijar precio).
5. **Wallapop** (`/search?category_id=100&keywords=...`) → chollos ES.
6. **Milanuncios** (`?s=<modelo>`) → contado.
7. **kleinanzeigen** (`/s-<slug>/k0`) → chollos particulares (precio anterior).

**Flujo C (MERCADO):** solo las 3 de Fase 1 por modelo.
