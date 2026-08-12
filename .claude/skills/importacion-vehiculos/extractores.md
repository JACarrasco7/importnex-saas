# Extractores, URLs y trampas técnicas

> Cargar cuando se necesite scrapear, debuggear un portal o construir URLs.
> Adaptado a los **3 flujos** (A/B/C) y al **sistema de fases** (1=sondeo, 2=profunda) del SKILL.md.

---

## 📋 Resumen por fuente, por flujo y por fase

| Fuente | País | Método | Por pág | Joya oculta | A: Fase 1 | A: Fase 2 | B: Fase 1 | B: Fase 2 | C: Fase 1 |
|---|---|---|---|---|---|---|---|---|---|
| Coches.net | ES | `__INITIAL_PROPS__` | 35 | Tasación €, `publicationDate`, DGT, `priceDrop` | ✅ | ✅ | ✅ | ✅ | ✅ |
| mobile.de | DE | `__INITIAL_STATE__['srp']` | var | Fichas con features, CO₂, propietarios | ✅ | ✅ | ✅ | ✅ | ✅ |
| AutoUncle | DE | DOM `article` | 25 | Días publicado, portal origen, enlace real | ✅ | ✅ | ✅ | ✅ | ✅ |
| Wallapop | ES | DOM `[class*="RetrievalItemCard"]` | ~50 | Año, km, CV, descripción en tarjeta | ❌ | ✅ | ❌ | ✅ | ❌ |
| Milanuncios | ES | `__INITIAL_PROPS__` | 41 | Contado vs financiado, fecha, descripción | ❌ | ✅ | ❌ | ✅ | ❌ |
| AutoScout24.de | DE | `__NEXT_DATA__` | 20 | Solo contar. NUNCA referencia precio | ❌ | ✅ | ❌ | ✅ | ❌ |
| kleinanzeigen.de | DE | (pendiente extractor propio) | — | Mezclado en AutoUncle | ❌ | ✅ | ❌ | ✅ | ❌ |
| km77 | ES | `web_fetch` (sin navegador) | — | PVP, CO₂, tipo IEDMT, etiqueta DGT | Solo Flujo A | Solo Flujo A | — | — | — |

> **Regla:** Las 3 fuentes obligatorias en Fase 1 son Coches.net, mobile.de, AutoUncle. Las 4 restantes (Wallapop, Milanuncios, AS24, kleinanzeigen) entran en Fase 2. **En Flujo C NO hay Fase 2** — solo las 3 de Fase 1 por modelo.

---

## 🔗 URLs por fuente

```
Coches.net   /<marca-slug>/<modelo-slug>/segunda-mano/?fr=<anio>&pf=<precioMin>&pg=<pagina>
Milanuncios  /coches-de-segunda-mano/?s=<marca>%20<modelo>%20<version>
Wallapop     /app/search?keywords=<marca>%20<modelo>%20<version>&category_ids=100
AutoUncle    /es/coches-segunda-mano/<Marca>/<Modelo>/f-gasolina/g-manual
                ?s[min_year]=&s[max_km]=&s[min_hp]=
AutoScout24  .es /lst/<marca>/<modelo>?atype=C&desde=<anio>&powerfrom=&powerto=&powertype=kw
             .de /lst/<marca>/<modelo>?atype=C&fregfrom=<anio>...  ← fregfrom, no desde
km77         /coches/<marca>/<modelo>/<anio-gama>/<carroceria>/<acabado>/<version>/datos
mobile.de    /fahrzeuge/search.html?...&lang=de  ← siempre lang=de
```

**Tabla maestra de marcas:** `listFiltersOptions.vehicles` de Coches.net → 165 marcas con IDs y slugs.

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

### Flujo C (MERCADO) — escanear N modelos
- **Solo Fase 1** por modelo (3 fuentes). No hay Fase 2.
- **Output:** Tabla BUSQUEDA con N filas (uno por modelo)

---

## 🚗 mobile.de — extractores

### Pasada 1 — listado

URL: `/fahrzeuge/search.html?dam=false&isSearchRequest=true&ms=<make>;<model>;<modelGroup>;<desc>&p=<min>:<max>&ml=:<kmMax>&fr=<anio>:&pw=<minKW>:<maxKW>&tr=AUTOMATIC_GEAR&fe=SUNROOF&s=Car&vc=Car&sb=p&od=up&lang=de`

**⚠️ IMPORTANTE:** La URL base usa `suchen.mobile.de`, pero ese subdominio puede bloquearse. Orden de reintento: `www.mobile.de` → `web_fetch` ficha → `web_fetch` listado → marcar bloqueada. **Genera la URL con `suchen.` primero y reescribe a `www.` si falla.**

Ruta JSON: `window.__INITIAL_STATE__.search['srp'].data.searchResults` (clave literal `'srp'`).

```js
window.__S=function(html){
  if(/Zugriff verweigert|Access denied/i.test(html)) return {__bloq:true};
  const m=html.match(/window\.__INITIAL_STATE__\s*=\s*/); if(!m) return null;
  let i=m.index+m[0].length,d=0,ini=i,str=false,esc=false;
  for(;i<html.length;i++){ const c=html[i];
    if(str){ if(esc)esc=false; else if(c==='\\')esc=true; else if(c==='"')str=false; continue; }
    if(c==='"')str=true; else if(c==='{')d++; else if(c==='}'){ d--; if(d===0){i++;break;} } }
  try{ return JSON.parse(html.slice(ini,i)); }catch(e){ return null; } };

window.__ex=function(st){ const sr=st.search['srp'].data.searchResults;
 const num=t=>{const m=String(t||'').replace(/\./g,'').match(/\d+/);return m?+m[0]:null;};
 return sr.items.filter(a=>a.type==='ad').map(a=>{
  const at=a.attr||{},ci=a.contactInfo||{},p=a.price||{},pr=a.priceRating||{};
  const [mm,yy]=String(at.fr||'').split('/');
  // ⚠️ El año siguiente reemplazar 2026 por el año actual
  const mes=yy?(new Date().getFullYear()+1-+yy)*12+(8-(+mm||1)):null;
  const km=num(at.ml);
  return {id:a.id, url:'https://www.mobile.de/fahrzeuge/details.html?id='+a.id,
   t:((a.shortTitle||'')+' '+(a.subTitle||'')).trim().slice(0,62),
   pre:p.grossAmount, ivaD:p.netAmount!=null,
   ahAlta:p.netAmount!=null?Math.round(p.grossAmount-p.netAmount):0,
   baja:p.reducedGross||null, sello:pr.ratingLabel||null,
   fr:at.fr, mes, km, kmAnio:(mes&&km)?Math.round(km/(mes/12)):null, cv:at.pw,
   prop:at.pvo, pais:at.cn, ciu:at.loc, tipo:ci.sellerType, fotos:a.numImages};});};
```

**Filtra patrocinados** (`type === 'ad'`). **`sb=p&od=up` sesga a versión base** → acotar con `pw=`.

### Pasada 2 — fichas (solo Flujo A y B en Fase 2)

Ruta: `state.search.vip.ads[<clave>].data.ad`. Clave puede no ser el id → `Object.keys(...)[0]`.

```js
function planoAttrs(A){
  const o={};
  const rec=(x)=>{ if(!x) return;
    if(Array.isArray(x)) return x.forEach(rec);
    if(typeof x==='object'){
      if(x.label!=null&&x.value!=null){ o[String(x.label).slice(0,45)]=String(x.value).slice(0,60); return; }
      Object.values(x).forEach(rec); } };
  rec(A); return o; }

const F=ad.features||[];
const eq={ techo:F.some(f=>/Panorama|Schiebedach|Glasdach/i.test(f)),
  cuero:F.some(f=>/Lederausstattung|Leder\b/i.test(f)),
  navi:F.some(f=>/Navigation/i.test(f)), camara:F.some(f=>/Rückfahrkamera|Kamera/i.test(f)),
  acc:F.some(f=>/Abstandstempomat|Abstandsregel/i.test(f)),
  led:F.some(f=>/LED|Matrix|Xenon/i.test(f)), asientosCal:F.some(f=>/Sitzheizung/i.test(f)),
  virtual:F.some(f=>/Digitales Cockpit|Head-Up/i.test(f)),
  audio:F.some(f=>/Bang|Bose|Harman|Canton|Dynaudio/i.test(f)),
  enganche:F.some(f=>/Anhängerkupplung/i.test(f)),
  awd:F.some(f=>/Allrad|4MOTION|quattro|xDrive/i.test(f)),
  garantia:F.some(f=>/Garantie/i.test(f)) };
```

Etiquetas útiles: `CO₂-Emissionen (komb.)` · `Erstzulassung` · `Kilometerstand` · `Leistung` · `Schadstoffklasse` · `Anzahl der Fahrzeughalter` · `Getriebe` · `Farbe`.

Avisos: CO₂ falta a menudo (6/20 fichas) → estimar y decirlo · `vehicleCondition` solo `"used"` sin `Unfallfrei` no declara estar libre · <15 features = anuncio pobre (salvo topes de gama) · no hay VIN ni fecha de publicación.

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

Trampas: `/auto/volkswagen-golf-gti.html` y `-golf-r.html` devuelven el Golf entero · `-cla.html` y `-1er.html` devuelven la marca entera. Validar `ms` contra `<h1>`.

### Orden de reintento mobile.de bloqueado

`www.mobile.de` → `web_fetch` ficha (`/fahrzeuge/details.html?id=`) → `web_fetch` listado → marcar PARCIAL con aviso.

---

## 💰 Presupuesto de peticiones

| Fuente | Concurrencia | Pausa | Máx/llamada | Por sesión |
|---|---:|---:|---:|---:|
| AutoScout24 | 3 | 0.7s | 12 | — |
| mobile.de contar | 2 | 1.5s | 8 | — |
| mobile.de fichas | 2 | 1.5s | 12 | — |
| **mobile.de total** | — | — | — | **45** (avisar a 35) |

Reglas generales:
- `Runtime.evaluate` muere a 45s.
- **NUNCA 2 llamadas a mobile.de en mismo `browser_batch`.**
- Usar `textContent`, no `innerText` (vacío en pestañas de fondo).
- `JSON.stringify` de 25 fichas se trunca → guardar en `window.__POOL` y pedir `slice()`.

---

## ⚠️ Trampas críticas (las que causaron fallos reales)

| Trampa | Consecuencia | Solución |
|---|---|---|
| `__INITIAL_PROPS__` = `undefined` en Coches.net | Pierde tasación, fecha, DGT | Esperar hidratación (2-3 reintentos, 1.5s) — pero **aceptar** método degradado (texto visible) en Fase 1 para no gastar tokens |

### Ejemplo concreto: esperar hidratación de `__INITIAL_PROPS__`

```javascript
// Coches.net: esperar a que __INITIAL_PROPS__ se hidrate
async function waitForInitialProps(maxRetries = 3, delay = 1500) {
  for (let i = 0; i < maxRetries; i++) {
    const props = window.__INITIAL_PROPS__;
    if (props && props.pageProps && props.pageProps.ads) {
      return props; // ✅ Hidratado
    }
    
    if (i < maxRetries - 1) {
      console.log(`⏳ Reintento ${i + 1}/${maxRetries}...`);
      await new Promise(resolve => setTimeout(resolve, delay));
    }
  }
  
  // ⚠️ Timeout: usar método degradado (leer texto visible)
  console.warn('⚠️ __INITIAL_PROPS__ no disponible, usando método degradado');
  return null;
}

// Uso:
const data = await waitForInitialProps();
if (data) {
  // Extraer de JSON (tasación, fecha, DGT disponibles)
  const ads = data.pageProps.ads;
} else {
  // Fallback: leer tarjetas visibles del DOM
  const cards = document.querySelectorAll('.ad-card');
}
```

**Regla:** En Fase 1, aceptar método degradado tras 1 reintento (no gastar tokens). En Fase 2, hacer 3 reintentos completos.

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

**`priceRankIndicator` Coches.net:** `3` = «Precio justo», `4` = «Buen precio», `5` y `2` sin confirmar.

**`publicationDate` Coches.net:** mediana de días publicados = indicador de rotación (factor 1 de vendibilidad).

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
| **Peticiones típicas** | 15-20 | 30-40 |
| **Output** | Foto general (mediana, hueco, N) | Detalle por unidad + veredicto |
