# Extractores validados — 8 agosto 2026

Probados en vivo sobre Opel Astra J GTC OPC. Estado real, no teórico.

---

## 1 · Wallapop — **ARREGLADO**

Antes marcado como ROTO. El fallo era subir demasiados niveles en el DOM: se llegaba
al contenedor de resultados y todas las tarjetas devolvían el mismo texto.

**La clave: sube por el DOM hasta que el ancestro contenga MÁS DE UN enlace `/item/`.
Ese es el límite de la tarjeta.**

```
https://es.wallapop.com/app/search?keywords=<marca>%20<modelo>&category_ids=100
```

```js
const T=e=>(e?.textContent||'').replace(/\s+/g,' ').trim();
window.__WP=function(){
 const seen={},out=[];
 [...document.querySelectorAll('a[href*="/item/"]')].forEach(a=>{
  const h=a.getAttribute('href'); if(seen[h]) return; seen[h]=1;
  let c=a,card=null;
  for(let i=0;i<9&&c;i++){
    const links=new Set([...c.querySelectorAll('a[href*="/item/"]')].map(x=>x.getAttribute('href')));
    if(links.size>1) break;                 // <-- el limite de la tarjeta
    if(/€/.test(T(c))) { card=c; break; }
    c=c.parentElement;
  }
  if(!card) return;
  const t=T(card).replace(/^Image \d+ of \d+/,'');
  const pr=(t.match(/([\d.]{4,9})\s*€/)||[])[1];
  const km=(t.match(/(\d[\d.]{2,8})\s*km/i)||[])[1];
  const cv=(t.match(/(\d{2,4})\s*cv/i)||[])[1];
  const an=(t.match(/(20[0-2]\d)\s*·/)||[])[1];   // el anio va antes del separador
  out.push({slug:h.replace('/item/',''), p:pr?+pr.replace(/\./g,''):null,
    a:an?+an:null, km:km?+km.replace(/\./g,''):null, cv:cv?+cv:null, t:t.slice(0,95)});
 });
 return out;
};
```

**Devuelve año, km, CV y combustible en la propia tarjeta** — no hace falta abrir el anuncio.
Descarta precios < 3.000 € (son piezas). La búsqueda por palabra clave es laxa: **filtra por título**.

**Aviso:** el scroll infinito no cargó más allá de ~51 anuncios únicos. No insistas con
12 pasadas de scroll — congela el renderizador y `Runtime.evaluate` muere a los 45 s.

---

## 2 · Milanuncios — **ARREGLADO**

```
https://www.milanuncios.com/coches-de-segunda-mano/?s=<marca>%20<modelo>%20<version>
```

Selector: **`article`**. Sin misterio.

```js
const T=e=>(e?.textContent||'').replace(/\s+/g,' ').trim();
window.__MA=function(){
 return [...document.querySelectorAll('article')].map(a=>{
  const t=T(a);
  const pr=[...t.matchAll(/([\d.]{4,9})\s*€/g)].map(m=>+m[1].replace(/\./g,'')).filter(x=>x>2000);
  const km=(t.match(/([\d.]{3,9})\s*kms/i)||[])[1];
  const cv=(t.match(/(\d{2,4})\s*cv/i)||[])[1];
  return {contado:pr.length?Math.max(...pr):null,     // OJO: el contado es el MAYOR
          financiado:pr.length>1?Math.min(...pr):null,
          km:km?+km.replace(/\./g,''):null, cv:cv?+cv:null,
          gar:/Garantía/i.test(t), t:t.slice(0,90)};
 }).filter(x=>x.t.length>30);
};
```

**Trampa nueva:** muestra dos precios, *"Precio al contado (IVA incluido)"* y
*"Precio financiado"*. **El contado es el MAYOR de los dos.** Si coges el menor te
inventas una rebaja del 6-8 % que no existe.

De 14 `article` solo ~7 tienen contenido; el resto son huecos de carga perezosa. Fíltralos por longitud.

---

## 3 · Coches.net — **SIGUE FLOJO**

- `/<marca>/<modelo>/segunda-mano/?fr=<anio>` funciona, pero **`pw` (potencia) se ignora**.
- `/<marca>-<modelo>-<version>/segunda-mano/` → **cae al listado general** (259.367 coches).
- `?text=` → **se ignora**, devuelve el catálogo entero.
- Sigue sacando **8 tarjetas** de las que haya.

**Conclusión: no sirve para versiones concretas.** Para un OPC, un VZ o un R hay que ir
por Wallapop y Milanuncios. Coches.net solo vale para el total del `<h1>` a nivel de gama.

---

## 4 · AutoScout24.es — **funciona para contar, y confirma la trampa**

Prueba en vivo: `/lst/opel/astra?atype=C&desde=2012&powerfrom=198&powerto=214&powertype=kw&fuel=B`

`<h1>` = **"37 ofertas para Opel Astra gasolina"**.
Reparto real de `location.countryCode` en la página 1: **DE 11 · NL 6 · BE 2 · LU 1 · ES 0**.

**Cero coches españoles presentados como oferta española.** Contar `countryCode` no es una
precaución teórica: sin ella, este modelo habría dado "37 unidades en España" y la escasez
se habría puntuado al revés.

---

## 5 · AutoUncle — **VALIDADO, y es el mejor agregador**

Agrega mobile.de + Autoscout24 + Kleinanzeigen + concesionarios en una sola navegación.

```
https://www.autouncle.de/es/coches-segunda-mano/<Marca>/<Modelo>/f-gasolina/g-manual
   ?s[min_year]=2014&s[max_km]=140000&s[min_hp]=250
```

- `s[min_hp]` **filtra por potencia de verdad** — resuelve el problema de aislar la versión.
- `/f-gasolina/`, `/g-manual/` van **en la ruta**, no como parámetro.
- **Sin marca, `/suche?fuel_type=...` redirige a PUCH.** Marca y modelo son obligatorios.
- El `<title>` da el total y el precio mínimo sin tocar el DOM.

**Listado** — selector `article`, un extractor idéntico al de AutoUncle ya probado.
Cada tarjeta trae precio, tasación AutoUncle, **días publicado**, portal de origen y valoración.

> **Los días publicados son el dato que faltaba para el factor demanda.** Está en cada tarjeta
> de AutoUncle, del lado alemán. Falta el equivalente español.

**Ficha** — `https://www.autouncle.de/es/d/<id>` con la sección *"Datos técnicos & Equipamiento"*:

```js
const T=e=>(e?.textContent||'').replace(/\s+/g,' ').trim();
const h=[...document.querySelectorAll('h2,h3')].find(e=>/Datos t[eé]cnicos/i.test(T(e)));
let p=h.parentElement, out='';
for(let i=0;i<4&&p;i++){ if(T(p).length>400){ out=T(p); break; } p=p.parentElement; }
```

Devuelve año, km, acabado, cambio, motor, **consumo**, color, puertas, carrocería y la lista
de equipamiento entera. **En español ya traducido.**

**Aviso:** `document.body.textContent` en AutoUncle trae el payload RSC de Next.js
(300.000 caracteres de rutas CSS). **Nunca leas el body — ve por `h2`/`h3`.**

**Falta:** la ficha de AutoUncle **no trae la descripción libre del vendedor**. Para la criba
de siniestros y modificaciones sigue habiendo que abrir el anuncio origen.

---

## 6 · Reglas de la sesión que se confirmaron

- **`innerText` devuelve vacío en pestañas de fondo.** El layout no se calcula si la
  pestaña no está visible. **Usa siempre `textContent`.**
- **`location.href` con query string dispara el bloqueo de la herramienta.** No lo leas.
- **Nada de comillas dobles dentro de un `browser_batch`.** `a[href*="/item/"]` rompe el
  JSON de la llamada; usa `a[href*='/item/']` o saca la llamada del batch.
- **Máximo ~8 pasadas de scroll de 0,9 s por llamada.** Más congela el renderizador.

---

## 7 · Cómo leer un volcado HTML guardado

`au_parse.py` (en esta misma carpeta) acepta un fichero de AutoUncle guardado a mano
y devuelve CSV. Útil cuando hay filtros o sesión que no se pueden reproducir por URL.

```bash
python3 tools/au_parse.py <fichero.html> salida.csv
```
