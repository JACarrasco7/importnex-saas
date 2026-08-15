---
name: "importacion-vehiculos"
description: "Negocio de JJ Import Motors (Huelva): ofertar importaciones de vehiculos UE a Espania sin stock, cobrando honorarios. ORDEN: 1) es VENDIBLE en Espania? 2) hay hueco en Alemania? 3) sale a cuenta? Puntua la VENDIBILIDAD con 5 factores: demanda del modelo, escasez de la configuracion, atractivo, equipamiento sobre el estandar espaniol e historial. Matriz: alta vendibilidad con margen bajo SE PUBLICA IGUAL, trae audiencia. Compara por FICHA TECNICA, nunca por medianas globales. FUENTES ESPANIA por orden: Wallapop y Milanuncios (ahi publican hasta Flexicar y OcasionPlus), Coches.net por su tasacion, AutoScout24.es poco representativa. ALEMANIA: mobile.de primero, kleinanzeigen.de alternativa, autouncle.de agregador. Cuenta countryCode y usa powertype=kw. LEE LA DESCRIPCION ENTERA. Usar al pegar enlaces, al decir 'buscame coches' o al pedir contenido para redes."
---

# Importación de vehículos UE → España (JJ Import Motors)

Localizar coches en la UE y **ofertar su importación** a clientes españoles.

## El negocio

**JJ Import Motors no compra coches. No tiene stock. No inmoviliza capital.**

Encontrar un coche bueno en Alemania → ofertarlo (redes, portales) o a un cliente concreto → si alguien lo quiere, gestionar la importación → **cobrar honorarios**.

1. **No calcules "margen". Calcula el AHORRO DEL CLIENTE.**
2. **El riesgo no es financiero, es reputacional.**
3. **El contenido es el negocio.** Sin stock, el único activo es la audiencia y la lista de gente esperando.

**Base de datos:** app Laravel en `https://dev.aktive.cloud/importnexcore`.
**Registro de mediciones:** `datos_mercado.json` en la carpeta de trabajo del usuario.

---

# EL ORDEN — primero vendible, después rentable

```
0. ¿Está en el REGISTRO y es reciente?      -> no midas nada
1. ¿Es VENDIBLE en España?                  -> los 5 factores
   PUERTA A: ¿hay comparable real? (>=5 coches españoles)
2. ¿Hay hueco en Alemania?                  -> test de nivel de precio
   PUERTA B: ¿está >=15 % por debajo?
3. ¿Sale a cuenta?                          -> ficha técnica y desglose
```

> **El error histórico fue hacerlo al revés:** buscar diferencia de precio y luego comprobar si se vendía. **Un coche con poco margen pero muy atractivo sirve; uno con margen que nadie quiere, no.**

## Las dos reglas que evitan ir y volver veinte veces

**1. CAPTURA TODO DE UNA PASADA.** En la misma respuesta ya vienen total, país de cada coche, mediana, cuartil bajo, versión, acabado, kilómetros y combustible. Guárdalo en `window.__ES` / `window.__DE` y trabaja sobre eso.

**2. AGRUPA POR DOMINIO. Nunca alternes país.** Cada cambio de dominio obliga a navegar y cuesta una llamada. Diez modelos son dos navegaciones, no veinte.

---

# EL SISTEMA DE VENDIBILIDAD

**Un coche no se vende por una sola razón.** Se puntúa sobre 100, y solo después se mira el margen.

| # | Factor | Peso | Qué mide y de dónde sale |
|---|---|---:|---|
| **1** | **Demanda del modelo** | 30 | Cuánta gente lo busca. **Se mide con matriculaciones o velocidad de rotación, NO con stock.** GANVAM, ANFAC, Faconauto, o antigüedad de anuncio en Coches.net |
| **2** | **Escasez de la configuración** | 25 | Cuántas hay **de esa versión exacta** en España. Poca oferta = el cliente no puede comparar |
| **3** | **Atractivo y contenido** | 20 | Si genera vistas. Deportivos, carrocerías raras, colores llamativos, potencia |
| **4** | **Equipamiento sobre el estándar español** | 15 | Qué lleva la alemana que el stock español no lleva |
| **5** | **Kilómetros e historial** | 10 | Libro sellado, propietarios, km/año, ITV, sin siniestros ni modificaciones |

**Reparto de puntos:**

- **Demanda:** top-10 nacional 30 · presencia fuerte 22 · conocido pero minoritario 14 · nicho 6
- **Escasez:** ≤20 uds 25 · 20-50 21 · 50-100 16 · 100-300 10 · >300 4
- **Atractivo:** icónico o carrocería rara 18-20 · deportivo conocido 14-17 · familiar premium 10-13 · utilitario 4-8
- **Equipamiento:** techo 4 · cuero 3 · tracción total 3 · Matrix/LED 2 · audio premium 2 · virtual/HUD 1
- **Historial:** libro sellado 3 · un propietario 2 · <15.000 km/año 3 · ITV recién pasada 2

> **NO SAQUES LA DEMANDA DEL STOCK.** Se probó y los dos primeros factores se anulaban entre sí: todos los modelos salían entre 44 y 56, sin capacidad de distinguir. **El stock dice lo que hay, no lo que se busca.** Mientras el factor 1 esté estimado, **da casillas razonadas, no notas numéricas** — un número con decimales que no se sostiene es peor que decir "pendiente".

## La matriz de decisión

| | **Margen ≥10 %** | **Margen <10 %** |
|---|---|---|
| **Vendibilidad ≥65** | **COMPRA PRIORITARIA** — se oferta ya, con desglose y campaña | **OFERTA DE CONTENIDO** — se publica igual. Cobras honorarios completos y el retorno es la audiencia y el registro de demanda |
| **Vendibilidad <65** | **SOLO BAJO PEDIDO** — hay dinero pero no público. Al registro, no se publica en frío | **DESCARTAR** — ni una foto |

> **La casilla azul es la que más se ignora.** Un coche con 5 % de margen y vendibilidad alta **sí se oferta**: el negocio no vive del margen por operación, vive de tener perfil, audiencia y lista de espera. Ese coche cuesta lo mismo de gestionar y trae los clientes de los tres siguientes.

## La ficha de cada candidata — seis bloques, en este orden

**A · POR QUÉ ESTE MODELO** — cuántas hay en España de esa versión, qué acabado domina aquí, por qué se compra.
**B · POR QUÉ ESTA UNIDAD** — qué tiene que no tengan las españolas. **Aquí se justifica el precio**, no en el desglose.
**C · EL PRECIO EN ESPAÑA** — mediana y cuartil bajo de la versión exacta, en **al menos dos plataformas**, distinguiendo concesionario de particular, con el ajuste línea a línea.
**D · EL COSTE DESDE ALEMANIA** — desglose completo, sin cifras redondas sin explicar.
**E · MARGEN Y VEREDICTO** — ahorro contra mediana **y** contra cuartil bajo, casilla de la matriz y acción.
**F · RIESGOS Y BANDERAS** — descripción completa del vendedor, riesgo mecánico del modelo, qué parte es estimación.

---

# FUENTES — jerarquía real del mercado

## España

| Fuente | Perfil | Peso | Nota |
|---|---|---|---|
| **Wallapop** | **Particular y profesional** | **ALTO** | **Publican hasta Flexicar y OcasionPlus.** Es donde mira la gente |
| **Milanuncios** | Mucho particular | **ALTO** | El suelo del mercado. Marca bajadas de precio |
| **Coches.net** | Concesionario | **ALTO** | **Tasación española + FECHA DE PUBLICACIÓN.** 35 anuncios por página vía `__INITIAL_PROPS__`, no 8 |
| **AutoScout24.es** | Concesionario | **BAJO** | **Poco usada en España.** Sirve para contar y por su JSON limpio, **no como referencia de precio** |
| Franquicias | Cadenas con garantía | — | El techo del mercado |

> **AutoScout24.es no representa el mercado español.** Es cómoda porque su JSON es limpio, pero **la referencia de precio tiene que salir de Wallapop, Milanuncios y Coches.net.** Un margen calculado solo con AutoScout24 está inflado en torno a 8 puntos.

> **TRAMPA: AutoScout24.es rellena con coches extranjeros** cuando España no tiene el modelo, sin avisar. El CUPRA León VZ daba "6 ofertas" españolas: los 6 estaban en Alemania. **Cuenta `location.countryCode` siempre.**

## Alemania

| Fuente | Peso | Nota |
|---|---|---|
| **mobile.de** | **PRINCIPAL** | Donde se compra de verdad. **20-40 % más barato que AutoScout24.de** |
| **kleinanzeigen.de** | Medio | Mucho particular, precios bajos. Hoy llega vía AutoUncle; sin extractor propio |
| **autouncle.de** | **ALTO** | **VALIDADO.** Agrega mobile.de + Autoscout24 + Kleinanzeigen + concesionarios en una navegación. `s[min_hp]` aísla la versión. Trae **días publicado** por anuncio |
| AutoScout24.de | Bajo | Plan B cuando mobile.de bloquea. Año: `fregfrom`, no `desde` |

> **Comprobado:** el Arteon de mobile.de a 20.589 € estaba **por debajo del coche más barato de todo AutoScout24.de**. Medir Alemania en AutoScout24 subestima el hueco.

## Las tasaciones no son lo mismo

**mobile.de** (`Sehr guter Preis` / `Guter Preis` / `Fairer Preis`) mide el **mercado alemán** — sirve para negociar allí.
**Coches.net** (`Super precio` / `Buen precio` / `Precio justo` / `Precio alto`) mide el **español** — **este es el dato bueno**.

## COBERTURA OBLIGATORIA — LAS 7 FUENTES, SIEMPRE

> **No se da por terminada una búsqueda con fuentes sin peinar.**
> Verificado el 15-ago-2026 (Tiguan cliente): se dejaron Wallapop, Milanuncios y AutoUncle
> como «parciales porque son webs 100 % JS» sin navegarlas. Eso **no es válido**:
> si la fuente es 100 % JS, se navega con el navegador. No es excusa para saltarla.

**La búsqueda SOLO está completa cuando las 7 fuentes están peinadas.** Si una no se pudo
cubrir, **no se entrega el resultado como definitivo**: se marca la fuente como pendiente y
se dice EXPLÍCITAMENTE en el informe de búsqueda qué falta y por qué, antes de presentar
candidatos.

| Bloque | Fuentes (peinar TODAS) | Obligatorio en |
|---|---|---|
| **España** | Wallapop · Milanuncios · Coches.net · AutoScout24.es | Todo cliente concreto en España |
| **Alemania** | mobile.de · kleinanzeigen.de · autouncle.de | Toda importación |

**Reglas:**

1. **Ninguna fuente se salta por ser «difícil».** Wallapop y Milanuncios son 100 % JS →
   navegador. AutoUncle entra por `h2`/`h3`, nunca por `body.textContent`. kleinanzeigen.de
   hoy llega vía AutoUncle. Todas son peinables.
2. **Si el presupuesto de peticiones no da para todo**, se prioriza por peso de la tabla
   (Wallapop/Milanuncios/Coches.net en ES; mobile.de/autouncle.de en DE) **y se declara
   la cobertura real** en el informe. Nunca silenciar una fuente sin peinar.
3. **Contraste cruzado obligatorio**: mismo candidato en 2+ fuentes se deduce por
   `(año, km ±2 %, CV, precio ±3 %)` y se queda el precio más bajo anotando «también en X».
4. **El informe de búsqueda lista UNA fila por fuente** (URL, filtros, nº resultados, uso)
   — ver «Qué lleva un informe de valoración». Si una fuente no sirvió, se lista igualmente
   con el porqué.

---

# QUÉ SCRAPER ALIMENTA CADA FACTOR

| Factor | Fuente | Estado | Consulta |
|---|---|---|---|
| **1 · Demanda** | Coches.net `publicationDate` | **RESUELTO 8-ago-2026** | `__INITIAL_PROPS__.initialResults.items[].publicationDate`. La mediana de días publicados es el indicador de rotación |
| | GANVAM / ANFAC | MANUAL | Matriculaciones por modelo, carga a mano |
| **2 · Escasez** | AutoScout24.es | FUNCIONA | `powertype=kw` + recuento de `countryCode` |
| **3 · Atractivo** | Criterio | NO NECESITA | A futuro, calibrar con el rendimiento real de las publicaciones |
| **4 · Equipamiento** | mobile.de ficha | FUNCIONA | `features` contra el acabado dominante español |
| **5 · Historial** | mobile.de ficha | FUNCIONA | Propietarios, ITV, km/año y **descripción completa** |
| **Precio ES particular** | Wallapop · Milanuncios | **FUNCIONA** | Extractores validados 8-ago-2026 |
| **Precio ES tasación** | Coches.net | **FUNCIONA** | `priceAverageIndicator` y `priceRankIndicator`, 35 por página |
| **Precio DE** | mobile.de | FUNCIONA | Presupuesto 45, pausa 1,5 s, aborto al 403 |

**Pendiente:** construir el índice de rotación con `publicationDate` · calibrar `priceRankIndicator` · extractor propio de kleinanzeigen.de.

---

# EL COMPARABLE POR FICHA TÉCNICA

**Un coche no es un modelo. Es una ficha técnica.** Dos "Arteon Shooting Brake 2.0 TDI" se llevan 5.000 € según acabado y kilómetros.

## Las nueve claves

Coincidir en las siete primeras, **ajustar** por las dos últimas:

| # | Clave | Cómo se fija |
|---|---|---|
| 1 | **Modelo** | Slug o `ms` validado contra el `<h1>` |
| 2 | **Versión / potencia** | **Por potencia en kW**, nunca por precio ni texto |
| 3 | **Carrocería** | Berlina, Sportback, Variant, Shooting Brake, SUV |
| 4 | **Motorización** | Gasolina, diésel, PHEV |
| 5 | **Cambio** | El manual descuenta 1.500-2.500 € |
| 6 | **Año** | ±1 año |
| 7 | **Kilómetros** | ±20 % |
| 8 | **Acabado y equipamiento** | Tabla de primas |
| 9 | **Historial** | Libro sellado, propietarios, ITV |

> **El acabado es clave 8, no clave 1.** R-Line, S-Line, M Sport, VZ, RS, Elegance **no son el mismo coche**: 1.000-1.500 € de diferencia.

**Si no puedes casar las siete primeras, no tienes comparable.** Dilo y cambia el discurso a exclusividad.

## Fórmula

```
Comparable = mediana española (Wallapop/Milanuncios/Coches.net) que casa en
             modelo+versión+carrocería+motor+cambio
             ± AÑO           (7-10 % por año)
             ± KM            (~2 % por cada 10.000 km)
             ± ACABADO       (R-Line/S-Line/M Sport sobre básico: 1.000-1.500 €)
             + EQUIPAMIENTO  que tiene el candidato y la muestra no
             − EQUIPAMIENTO  que tiene la muestra y el candidato no
             ± HISTORIAL     (libro sellado, 1 dueño, ITV nueva: +300-600 €)
```

**Enseña el ajuste línea a línea.** Si pasa del 15 %, la muestra no servía.

## FILTRO DE ADMISIÓN — antes de proyectar nada

> **El ajuste se invierte en los extremos. Comprobado el 9-ago-2026.**
> Con la muestra del Astra J OPC (objetivo 2014 / 102.000 km), el coche de
> **186.000 km proyectaba a 24.584 €, la cifra más alta de las ocho**, por encima del
> de 33.500 km, que proyectaba a 17.907 €. La fórmula extrapola fuera de su rango
> válido y da la vuelta a la realidad.

**Reglas, en este orden:**

1. **Solo entra en la muestra lo que está a ±2 años y ±40 % de km del objetivo.**
   Lo demás no es comparable, es otro coche. Se lista y se dice por qué queda fuera.
2. **Ningún ajuste individual pasa del ±25 %.** Si hace falta más, la unidad no servía.
3. **Con menos de 15 comparables reales no se da una cifra puntual, se da un rango.**
   Medido: los mismos ocho coches dan +27,3 %, +29,9 % o +20,4 % según el método
   (mediana de proyectados, cercanos con tope, o regresión). **Diez puntos eligiendo
   método.** Una regresión sobre esa muestra saca coeficiente de km positivo —más
   kilómetros, más dinero—, que es la prueba de que no da para estimador puntual.
4. **El veredicto lo manda el suelo, no la mediana.** El cliente no compra la mediana:
   compara contra el coche más barato que puede conseguir aquí en condiciones
   equivalentes. La mediana es el número del discurso comercial; el cuartil bajo y el
   suelo son los del veredicto.

## PRECIO PEDIDO ≠ PRECIO DE VENTA

> **Sesgo sistemático a nuestro favor, sin corregir todavía.** Todo el lado español son
> precios de **anuncio**. Nuestro precio final es **real**. Restamos peras de manzanas
> y siempre en la dirección que nos beneficia.

Ya se puede medir, porque tenemos los días publicado: `publicationDate` en Coches.net,
`publishDate` en Milanuncios, días publicado en AutoUncle.

**Tabla de descuento propuesta, PENDIENTE DE CALIBRAR Y DE APROBAR** — no aplicarla
sin decirlo:

| Días publicado | Descuento sobre el precio pedido |
|---|---:|
| < 15 | 0 % |
| 15-45 | −3 % |
| 46-90 | −6 % |
| > 90 | −10 % |

**Se calibra con el cierre del bucle:** cada coche vendido que tuviéramos medido aporta
precio pedido, días y precio real. Con veinte observaciones deja de ser estimación.
**Impacto esperado: 3-8 puntos de margen, en nuestra contra.** Mientras no esté
calibrada, al menos **decir en el informe que el comparable es precio pedido.**

## ÍNDICE DE ROTACIÓN — el factor 1 ya se puede medir

```
rotacion(modelo, version) = mediana de dias publicados de los anuncios vivos
```

| Mediana de días | Puntos sobre 30 |
|---|---:|
| ≤ 25 | 30 |
| 26-50 | 24 |
| 51-90 | 16 |
| 91-150 | 9 |
| > 150 | 4 |

**Se llena solo:** cada vez que midas un modelo, guarda la lista de días publicados en
`datos_mercado.json`. **Sesgo conocido:** los anuncios vivos sobrerrepresentan los que
no se venden. Sirve para **comparar modelos entre sí**, no como cifra absoluta.

> **CASO REAL — Arteon Shooting Brake.** Dio **VERDE con 19,2 %** usando 30.900 €, mediana que mezclaba berlinas con Shooting Brake y R-Line con Elegance. El comparable correcto era el único Shooting Brake 147 kW español: 28.400 € con 116.600 km. Ajustando −1.250 € de acabado y −1.396 € por 24.569 km más → **25.754 €**. Contra 24.981 € puesto en Huelva: **3,0 %. NO SALE.**

## Primas de equipamiento en España

| Extra | Prima | | Extra | Prima |
|---|---:|---|---|---:|
| **Automático / DSG** | +1.500-3.000 € | | Audio premium | +300-700 € |
| **Techo panorámico** | +800-1.500 € | | Cuadro digital / HUD | +300-700 € |
| **Tracción total** | +800-1.500 € | | Cámara + sensores | +300-600 € |
| **Cuero** | +600-1.200 € | | Enganche | +300-600 € |
| **LED / Matrix** | +500-1.000 € | | CarPlay | +400-800 € |
| **ACC** | +400-800 € | | | |

**Lo que RESTA:** colores raros (marrón, beige, verde) se quedan meses parados — ve a blanco, gris, negro y azul; en deportivos el rojo funciona · manual en premium o SUV −1.500-2.500 € · sin libro sellado · tela clara · ex-flota sin historial.

## El hueco está en la CONFIGURACIÓN

> **Škoda Superb:** 132 unidades en franja en España, **solo 3 con techo panorámico**.

El Formentor tiene 1.542 en España pero **de la versión base**; el GTI tiene 310 y está bien surtido **en todas** las configuraciones. **Mide la configuración que vas a ofertar, no la gama.**

---

# LAS DOS PUERTAS

## Puerta A · ¿Hay comparable español?

| Coches españoles reales | Lectura |
|---|---|
| **≥15** | Comparable sólido |
| **5-14** | Justo: mediana **y** cuartil bajo, y dilo |
| **1-4** | **No hay comparable.** Ni medianas ni porcentajes |
| **0** | Exclusividad pura: el discurso es "aquí no existe" |

## Puerta B · Test de nivel de precio — 2 peticiones

| Alemania vs España | Qué hacer |
|---|---|
| **≥25 % por debajo** | Rastreo completo |
| **15-25 %** | Rastreo, avisando del margen justo |
| **0-15 %** | **Solo cuartil bajo.** Dilo antes de empezar |
| **Por encima** | **No rastrees.** Solo con cliente concreto |

> **Audi S3:** ratio de oferta 6,7:1, excelente en apariencia. Pero mediana alemana en AutoScout24.de 37.235 € contra 32.790 € española. **En mobile.de, en cambio, el S3 barato está en 22.900 €** — de ahí la importancia de medir el canal correcto.

---

# EL RATIO DE OFERTA — pista débil, nunca veredicto

Alemania matricula **2,8 veces** más. Solo por encima de 2,8 hay excedente.

| Modelo | Ratio | Qué pasó |
|---|---:|---|
| Seat León | 1,1:1 | Ratio malo y **sí salía**: 17 % |
| CUPRA Formentor | 4,3:1 | Ratio bueno y **no salía**: −21,6 % |
| Golf GTI | 5,5:1 | Ratio bueno y **no salía** |
| BMW M2 | 2,9:1 | Parecía escasez española: era **global** |

**Cuatro de cinco veces engañó.** Filtros simétricos, cierra los dos lados, por configuración, y con <30 resultados sospecha del filtro.

---

# PRESUPUESTO DE PETICIONES

## mobile.de: 45 por sesión

```js
window.__NM=0; window.__BLOQ=false;
window.__fm=async function(url){
  if(window.__NM>=45) throw new Error('PRESUPUESTO AGOTADO ('+window.__NM+')');
  window.__NM++;
  const r=await fetch(url,{credentials:'include',signal:AbortSignal.timeout(11000)});
  const t=await r.text();
  if(/Zugriff verweigert|Access denied/i.test(t)){ window.__BLOQ=true; throw new Error('403'); }
  return t; };
```

Avisa al llegar a 35. **Si se agota, para y dilo.**

## Reglas de navegador — comprobadas en sesión

- **`innerText` devuelve cadena vacía en pestañas de fondo.** El layout no se calcula si la
  pestaña no está visible y todas las tarjetas salen en blanco. **Usa siempre `textContent`.**
- **No leas `location.href` ni construyas cadenas con `?…&…`** dentro del código que ejecutas:
  dispara el bloqueo de la herramienta y pierdes la llamada entera.
- **Nada de comillas dobles dentro de un `browser_batch`.** `a[href*="/item/"]` rompe el JSON
  de la llamada. Usa comillas simples o saca esa llamada fuera del batch.
- **Máximo ~8 pasadas de scroll de 0,9 s por llamada.** Más congela el renderizador y
  `Runtime.evaluate` muere a los 45 s. Si se congela, recupera con `navigate`.
- **Devuelve agregados o rebanadas.** Un `JSON.stringify` de 25 fichas se trunca a medias.
  Guarda en `window.__POOL` y pide `slice(0,7)`, `slice(7,14)`…
- **Cierra las pestañas que abras.**

## Tamaño de tanda — el límite de 45 segundos

`Runtime.evaluate` **muere a los 45 s**, el script sigue corriendo y **congela el renderizador**.

| Fuente | Concurrencia | Pausa | **Máx. por llamada** |
|---|---:|---:|---:|
| AutoScout24 | 3 | 0,7 s | **12** |
| mobile.de contar | 2 | 1,5 s | **8** |
| mobile.de fichas | 2 | 1,5 s | **12** |

> **NUNCA dos llamadas a mobile.de en el mismo `browser_batch`** — doblan el ritmo real.
> **Si el renderizador se congela**, hasta `1+1` da timeout. Recupera con `navigate`.

**O mides ratios O rastreas a fondo.** No caben las dos cosas en una sesión.

---

# EXTRACTORES

> **EL CODIGO VIVE EN `tools/extractores.js` DE LA CARPETA DE TRABAJO.**
> Ahi estan `__cochesnet`, `__milanuncios`, `__wallapop`, `__autouncle`, `__autoscout`
> y `__norm`, todos validados el 9 de agosto de 2026 y con salida normalizada comun.
> **Pega el bloque que toque en la consola de la pestana abierta.** Aqui solo quedan
> las URLs, las trampas y lo que hay que verificar. Si cambias un extractor, cambialo
> alli, no aqui.

## Lo que devuelve cada fuente

| Fuente | Como | Por pagina | Lo mejor que trae |
|---|---|---:|---|
| **Coches.net** | `__INITIAL_PROPS__` | **35** | Tasacion en euros, **fecha de publicacion**, etiqueta DGT, valoracion del vendedor, `priceDrop` |
| **Milanuncios** | `__INITIAL_PROPS__` (misma casa) | **41** | **Contado separado del financiado**, fecha de publicacion, **descripcion entera** |
| **Wallapop** | DOM `[class*="RetrievalItemCard"]` | ~50 | Anio, km, CV, combustible y **descripcion entera** en la propia tarjeta |
| **AutoUncle** | DOM `article` | 25 | **Dias publicado**, portal de origen, tasacion y **enlace al anuncio real** |
| **AutoScout24** | `__NEXT_DATA__` | 20 | Solo para **contar**. Nunca como referencia de precio |
| **km77** | `web_fetch`, sin navegador | — | PVP, CO2, **tipo de IEDMT ya calculado**, etiqueta DGT |

## URLs

```
Coches.net   /<marca-slug>/<modelo-slug>/segunda-mano/?fr=<anio>&pf=<precioMin>&pg=<pagina>
Milanuncios  /coches-de-segunda-mano/?s=<marca>%20<modelo>%20<version>
Wallapop     /app/search?keywords=<marca>%20<modelo>%20<version>&category_ids=100
AutoUncle    /es/coches-segunda-mano/<Marca>/<Modelo>/f-gasolina/g-manual
                ?s[min_year]=&s[max_km]=&s[min_hp]=
AutoScout24  .es /lst/<marca>/<modelo>?atype=C&desde=<anio>&powerfrom=&powerto=&powertype=kw
             .de /lst/<marca>/<modelo>?atype=C&fregfrom=<anio>...     <-- fregfrom, no desde
km77         /coches/<marca>/<modelo>/<anio-gama>/<carroceria>/<acabado>/<version>/datos
```

**Para CUALQUIER modelo:** `listFiltersOptions.vehicles` de Coches.net es la tabla
maestra de **165 marcas con sus IDs y todos sus modelos**. Pasa marca y modelo por el
slug (minusculas, sin tildes, guiones) y tienes la URL. Validado con los casos dificiles:
`Leon -> leon`, `Mercedes-Benz -> mercedes-benz`, `Altea XL -> altea-xl`.

## Las trampas, una por una

> **COCHES.NET RECUERDA LOS FILTROS.** Tras navegar con `pf=12000`, las navegaciones
> siguientes devuelven el conjunto recortado **aunque la URL vaya limpia**. El filtro
> vive en la sesion. **Verifica `initialSearch` en cada medicion.**

> **El `<h1>` de Coches.net miente en cuanto usas parametros.** Decia «2.581 OPEL Astra»
> mientras `totalResults` decia 1.375. **Fiate de `totalResults`.**

> **Coches.net no filtra por potencia ni version por URL.** `pw`, `pwt`, `hpf`, `pot`,
> `?text=` y los slugs de version se ignoran. Se aisla filtrando `items[].hp` en local.

> **COCHES.NET ORDENA POR «RELEVANCIA», NO POR PRECIO — y hay que paginar TODO.**
> Verificado el 15-ago-2026 (Tiguan): 434 unidades gasolina; se revisaron 6 páginas
> (54 fichas) y se colaron candidatos buenos en páginas no miradas. Para cliente
> concreto: filtrar por precio ≤ tope con `pf=` y **recorrer TODAS las páginas con `pg=`
> hasta agotar resultados**. Si no se puede paginar todo, **DECIRLO** y marcar cobertura
> parcial en el informe de búsqueda.

> **MILANUNCIOS: dos precios por anuncio.** `price.cashPrice.value` es el contado,
> `price.financedPrice.value` el financiado. **El contado es el MAYOR.** En el DOM se
> confunden; en el JSON no.

> **PRECIO FINANCIADO COMO GANCHO — en TODOS los portales.** MUY CAR y Flexicar muestran
> como precio grande el **financiado**, no el contado (verificado 15-ago-2026, Tiguan).
> Antes de dar un precio, confirma que es el **contado**: en Milanuncios usa
> `price.cashPrice.value`; en Coches.net/Wallapop abre la ficha y busca «contado». Un
> precio financiado metido en la tabla infla el ahorro y falsifica el ranking.

> **WALLAPOP: sube por el DOM solo hasta el nodo con UN enlace `/item/`.** Mas arriba
> esta el contenedor de resultados y las 50 tarjetas devuelven el mismo texto. Descarta
> por debajo de 3.000 EUR (son piezas) y **filtra por titulo**: la busqueda es laxa.

> **AUTOUNCLE: nunca `document.body.textContent`.** Trae 300.000 caracteres de payload
> RSC de Next.js antes del contenido. Entra por `h2`/`h3`. Y **sin marca y modelo en la
> ruta, `/suche?fuel_type=...` redirige a PUCH.**

> **AUTOSCOUT24 RELLENA CON COCHES EXTRANJEROS SIN AVISAR.** «37 ofertas para Opel Astra
> gasolina» 198-214 kW: 11 DE, 6 NL, 2 BE, 1 LU, **0 ES**. **Cuenta `countryCode` siempre.**
> Y **ignora `powertype=ps`**: filtra en kW. `kW = CV / 1,36`.

## Calibraciones

**`priceRankIndicator` de Coches.net:** `3` = «Precio justo», `4` = «Buen precio».
Sube con la ganga; `5` y `2` sin confirmar contra el texto.

**`ms` de mobile.de validados:**

| Modelo | `ms` | | Modelo | `ms` |
|---|---|---|---|---|
| VW Golf | `25200;;29;` | | Audi S3 | `1900;19;;` |
| VW Golf GTI | `25200;;29;GTI` | | Audi RS3 | `1900;36;;` |
| VW Golf R | `25200;;29;R 4MOTION` | | Audi TT | `1900;23;;` |
| VW Arteon | `25200;64;;` | | Audi A3 | `1900;8;;` |
| CUPRA Formentor | `3;5;;` | | Seat Leon | `22500;9;;` |
| CUPRA Leon | `3;6;;` | | Mercedes Clase A | `17200;;4;` |
| BMW Serie 1 | `3500;;20;` | | Mercedes CLA | `17200;;45;` |
| BMW M2 | `3500;117;;` | | Mercedes A45 AMG | `17200;229;;` |

**Slugs de AutoScout24:** volkswagen `golf golf-gti golf-r arteon passat t-roc` · cupra
`leon formentor` · audi `a3 a4 a5 s3 s4 rs3 tt` · seat `leon` · bmw `serie-1 serie-2
serie-3 serie-4 m2` · mercedes-benz `cla a-180 a-45-amg cla-45-amg` · skoda `octavia
superb kodiaq` · volvo `v40 v60 xc60` · ford `focus kuga` · honda `civic` · hyundai
`i30` · opel `astra` · kia `niro` · mazda `3` · porsche `cayman` · peugeot `308`.
En **.de** los BMW son `1er 2er 3er 4er`. Dan 404: `mercedes-benz/clase-a`, `/clase-c`,
`bmw/m140i`, `/m135i`, `/m240i`, `/330e`, `toyota/gr-yaris`.

## mobile.de — el codigo largo tambien esta en `tools/extractores.js`

```
/fahrzeuge/search.html?dam=false&isSearchRequest=true
  &ms=<make>;<model>;<modelGroup>;<desc>&p=<min>:<max>&ml=:<kmMax>&fr=<anio>:
  &pw=<minKW>:<maxKW>&tr=AUTOMATIC_GEAR&fe=SUNROOF&s=Car&vc=Car&sb=p&od=up&lang=de
```

**Siempre `&lang=de`.** Con sesión española redirige a otro frontend **donde `__INITIAL_STATE__` no existe**.

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

**Trampas:** `/auto/volkswagen-golf-gti.html` y `-golf-r.html` devuelven **el Golf entero**; `-cla.html` y `-1er.html` devuelven **la marca entera**.

> **VALIDA EL `ms` CONTRA EL `<h1>`.** Que `model` o `modelGroup` no estén vacíos **no basta**.
> **`modelDescription` es difuso** — `R 4MOTION` cuela R-Line. **Prefiere `pw=` en kW.**

### Pasada 1 — listado

Ruta: `window.__INITIAL_STATE__.search['srp'].data.searchResults` (clave literal `'srp'`).

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
  const mes=yy?(2026-+yy)*12+(8-(+mm||1)):null; const km=num(at.ml);
  return {id:a.id, url:'https://suchen.mobile.de/fahrzeuge/details.html?id='+a.id,
   t:((a.shortTitle||'')+' '+(a.subTitle||'')).trim().slice(0,62),
   pre:p.grossAmount, ivaD:p.netAmount!=null,
   ahAlta:p.netAmount!=null?Math.round(p.grossAmount-p.netAmount):0,
   baja:p.reducedGross||null, sello:pr.ratingLabel||null,
   fr:at.fr, mes, km, kmAnio:(mes&&km)?Math.round(km/(mes/12)):null, cv:at.pw,
   prop:at.pvo, pais:at.cn, ciu:at.loc, tipo:ci.sellerType, fotos:a.numImages};});};
```

**Filtra patrocinados con `type === 'ad'`** — de 26 elementos solo 16-19 son orgánicos.
**Guarda en `window.__POOL` y devuelve solo agregados.**

> **`sb=p&od=up` sesga hacia versión base y más kilómetros.** Acota con `pw=`.

### Pasada 2 — fichas

Ruta: `state.search.vip.ads[<clave>].data.ad`. **La clave puede no ser el id** — usa `Object.keys(...)[0]`.

**`attributes` viene anidado y no siempre con `tag`.** Aplánalo buscando pares `{label,value}`:

```js
function planoAttrs(A){
  const o={};
  const rec=(x)=>{ if(!x) return;
    if(Array.isArray(x)) return x.forEach(rec);
    if(typeof x==='object'){
      if(x.label!=null&&x.value!=null){ o[String(x.label).slice(0,45)]=String(x.value).slice(0,60); return; }
      Object.values(x).forEach(rec); } };
  rec(A); return o; }

const F=ad.features||[];              // alimenta el factor 4
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

**Avisos:** el **CO₂ falta a menudo** (6 de 20 fichas) — estímalo y **dilo** · `vehicleCondition` solo `"used"` sin `Unfallfrei` **no declara** estar libre de accidentes · **menos de 15 `features`** = anuncio pobre · **no hay VIN ni fecha de publicación**.

### LEE LA DESCRIPCIÓN ENTERA

> **`dam=false` NO lee el texto libre.** Dos Golf GTI pasaron el filtro de siniestros declarando *"hatte ein leichten Auffahrunfall"* (golpe por alcance, sensor de volante averiado) y *"professionell auf Stage 1 machen lassen"*. Un Superb declaraba **chiptuning a 210 CV** solo en el texto.

**Contrasta el título con el modelo pedido:** un "CUPRA Formentor" del barrido era un **CUPRA León Sportstourer**.

### Criba de nivel 1

| Motivo | Condición |
|---|---|
| Regla 6/6.000 | `mes < 7 \|\| km < 6000` |
| Siniestro | `Reparierter Unfallschaden` **o mención en descripción** |
| Modificado | `Stage`, `Chiptuning` en título **o descripción** |
| Uso intensivo | `kmAnio > 30000` |
| País imprevisto | `pais !== 'DE'` |
| Solo profesionales | `NUR AN AUTOHÄNDLER` |
| Modelo equivocado | el título no coincide |

Manda a fichas **15-25**.

### Diccionario alemán

`AHK` enganche · `SHZ` calefactables · `PANO`/`Schiebedach` techo · `RFK`/`KAM` cámara · `ACC` · `HUD` · `NAVI` · `VIRTUAL` · `LED`/`MATRIX`/`IQ.LIGHT` · `LEDER` cuero · `DCC` · `STANDHZ` · `SCHECKHEFTGEPFLEGT` libro sellado · `1.HAND` · `TÜV NEU` · `UNFALLFREI` · `VB` negociable · `Schaltgetriebe` **manual** · `Automatik` · `NUR AN AUTOHÄNDLER` · `Auffahrunfall` golpe por alcance · `Leistungssteigerung` aumento de potencia · `Zahnriemen NEU` · `Batterie-Zertifikat`.

---

# EL ENCARGO PERMANENTE

## Segmento 1 · NICHO — más de 20.000 €

*Superventas:* Golf GTI y R, CUPRA León VZ, Formentor VZ, Octavia RS.
*Exóticos:* Arteon y Shooting Brake, Astra OPC, S3/RS3, M2/M140i/M240i, i30 N, Civic Type R, Focus ST, T-Roc R, Volvo V60/V90/XC60 T8, Cayman, Audi TT.

**Qué buscar:** **Full Equip** — panorámico, Virtual Cockpit, asientos deportivos, paquete Black, Matrix.

## Segmento 2 · ROTACIÓN — 8.000 a 20.000 €

Golf 7, León FR, Clase A y CLA, A3 S-Line, Serie 1 M Sport. **Y los PHEV: Passat GTE, 330e, A3 e-tron.**

> **Medido en bloque el segmento está flojo:** España mejor surtida en casi todo. **Lo único con recorrido son los PHEV**, por el 0 % de IEDMT y la etiqueta CERO.

> **TRAMO 8.000-14.000 €:** comparable por versión exacta, **umbral del 12 %**, y **casi toda la oferta alemana barata es MANUAL**.

## Criterios comunes

| Criterio | Valor |
|---|---|
| **Franja** | 8.000 - 45.000 € |
| **Cambio** | Automático por defecto — **excepciones por modelo** |
| **Equipamiento** | Techo: **bonus que puntúa mucho, NO filtro duro** |
| **Antigüedad** | 2017 en adelante |
| **Kilometraje** | Máx. 170.000 km. **Si merece la pena con más, se enseña y se dice por qué** |
| **Siniestros** | Ninguno |
| **Zona** | Huelva → transporte `suroeste` (900 €) |

**Solo manuales:** Astra OPC, Civic Type R, i30 N, Focus ST, Megane RS, Peugeot 308 GTi.

**Objetivo actual: llenar el perfil y darse a conocer.** Un coche vistoso con ahorro justo puede valer más que uno aburrido con ahorro bueno.

**Ventaja geográfica:** no hay importadores en Huelva y sí hay demanda. **Un cliente concreto vence a cualquier ratio.**

---

# DESGLOSE Y VEREDICTO

| Concepto | Ejemplo |
|---|---:|
| Precio del anuncio (Alemania) | 8.500,00 € |
| Transporte (suroeste) | 900,00 € |
| **Matrícula de exportación alemana** (*Ausfuhrkennzeichen*, 15 días) y seguro | 114,00 € |
| ITV de importación, homologación y tasas DGT | 115,00 € |
| IEDMT *(24.000 € × 24 % × 4,75 %)* | 274,00 € |
| **COSTE TOTAL EN ESPAÑA** | **9.903,61 €** |
| Honorarios | + 1.500,00 € |
| **PRECIO FINAL AL CLIENTE** | **11.403,61 €** |
| Comparable ajustado | 13.790,00 € |
| **Ahorro del cliente** | **+2.386 € (17,3 %)** |

**Umbrales:** ≥10 % general · **≥12 %** en el tramo 8-14k y con garantía de concesionario · 6-10 % justo · 0-6 % no compensa **por margen** — pero mira la matriz: puede ser oferta de contenido.

> **Da el ahorro contra la mediana Y contra el cuartil bajo.** Si contra el cuartil bajo sale negativo, **el veredicto de margen es NO**.

**Verifica la aritmética con Python antes de presentar.**

**Presenta también los NO.** **No generes el paquete sin confirmación clara.**

---

# COSTES, IVA E IEDMT

**Coste fijo: 3.000-3.800 € siempre.** Transporte (~900 € a Huelva) + *Ausfuhrkennzeichen* alemán y seguro (114 €) + ITV de importación y tasas DGT (115 €) + IEDMT + honorarios (1.500-2.250 €).

| Compra | Peso del coste fijo | Viabilidad |
|---|---|---|
| 3.000-6.000 € | 50-100 % | **Imposible** |
| **8.000 €** | **34 %** | Viable con comparable quirúrgico y umbral del 12 % |
| 14.000 € | 22 % | Cómodo |
| 25.000 € | 15 % | Muy cómodo |
| 35.000 € | 11 % | Óptimo |

**Transporte:** `suroeste` 900 € · `oeste` 1.000 € · `centro` 1.100 € · `norte`/`este` 1.250 €. **Agrupando 2-3 coches baja a ~400 €** — 2,5 puntos de ahorro a cada uno.

**Honorarios:** 1.500 € hasta 15.000 € de compra, 2.250 € hasta 30.000 €.

> **NOMENCLATURA — no confundir las dos matrículas.**
> La **matrícula verde** es la **provisional ESPAÑOLA**, la que se pide aquí para circular
> mientras se tramita la matriculación definitiva.
> La alemana es el ***Ausfuhrkennzeichen*** (matrícula de exportación), válida **15 días** y
> con seguro incluido. **Son cosas distintas.** Si el coche viene en camión no hace falta ninguna.
> Los ~114 € del desglose son el *Ausfuhrkennzeichen* alemán, no la matrícula verde.

## IVA — el usuario opera como particular, sin NIF-IVA

**mobile.de muestra siempre el precio BRUTO.** Un particular paga `grossAmount` en los dos regímenes:

| En el JSON | Significa | Particular | Con NIF-IVA |
|---|---|---|---|
| `netAmount` **existe** | *MwSt. ausweisbar* | `grossAmount` | `netAmount` |
| **no existe** | *Differenzbesteuerung* §25a | `grossAmount` | `grossAmount` |

**No multipliques por 1,19.** Con `netAmount`, `grossAmount − netAmount` es lo que costaría de menos estando de alta (2.500-3.500 €/coche). **Da el acumulado por barrido.**

**Regla de 6 meses y 6.000 km:** "medio de transporte nuevo" con **menos de 6 meses O menos de 6.000 km** → **21 % de IVA español** (modelo 309) además del IEDMT. En 30.000 €, **6.300 € de sorpresa**.

## IEDMT — el método correcto

**La base NO es lo que se paga por el coche.** Es el valor que Hacienda le asigna. Norma
vigente: **Orden HAC/1501/2025, de 17 de diciembre** (BOE de 23-dic-2025), en vigor desde el
1 de enero de 2026. Sustituye a la Orden HAC/1484/2024.

```
valor de mercado = precio medio de la tabla ministerial x coeficiente de antiguedad (Anexo IV)
minoracion       = valor de mercado x (IVA + tipo) / (1 + IVA + tipo)   <-- SOLO si estuvo
                                                                            matriculado fuera
base imponible   = valor de mercado - minoracion
IEDMT            = base imponible x tipo por CO2
```

**La minoración es el paso que más se olvida.** El artículo 69 de la Ley 38/1992 manda restar
del valor de mercado el importe residual de las cuotas de los impuestos indirectos cuando el
vehículo **ya estuvo matriculado en el extranjero** — que es siempre nuestro caso. Baja el
impuesto en torno a un 23 %. **Da las dos cifras y que el gestor confirme cuál aplica.**

**Coeficientes del Anexo IV**, por años desde la primera matriculación:

| Años | % | Años | % | Años | % |
|---|---:|---|---:|---|---:|
| hasta 1 | 100 | más de 4 hasta 5 | 47 | más de 8 hasta 9 | 24 |
| más de 1 hasta 2 | 84 | más de 5 hasta 6 | 39 | más de 9 hasta 10 | 19 |
| más de 2 hasta 3 | 67 | más de 6 hasta 7 | 34 | más de 10 hasta 11 | 17 |
| más de 3 hasta 4 | 56 | más de 7 hasta 8 | 28 | más de 11 hasta 12 | 13 |
| | | | | **más de 12** | **10** |

Tipos: **0 %** ≤120 g/km · **4,75 %** 121-159 · **9,75 %** 160-199 · **14,75 %** ≥200. Las
comunidades pueden subirlo un 15 %.

**Los tramos tienen filo:** 119 g/km paga 0 €; 121 paga 4,75 %. **Los PHEV pagan 0 %** — pero comprueba el precio español antes de celebrarlo.

> **El coeficiente manda sobre la cilindrada.** Un impuesto que parece «demasiado bajo para un
> 2.0 turbo» casi siempre es un coche que ha pasado los **doce años** y ha caído al **10 %**.
> Medido: Astra OPC 280 CV de 2014 → **280 €**. El mismo coche de 2021 → **1.430 €**.
> **Antes de dudar del cálculo, mira la edad.** Y si una cifra de referencia no cuadra,
> despéjala: `importe / (tipo × PVP)` da el coeficiente que se usó, y de ahí la edad supuesta.
> Así se detecta una tasación vieja o un tramo mal aplicado.

**El PVP sale de km77**, no de la cabeza: `precio sin impuestos × (1 + IVA + tipo)`. La tabla
del BOE puede asignar otro valor a la versión concreta; la diferencia suele ser de decenas de
euros, pero **dilo**.

**No des asesoramiento fiscal.** Remite al gestor.

---

# INVESTIGAR EL MODELO

Caché primero. Caducidades: recalls 6 meses · seguro y piezas 12 · averías 18 · homologación 24.

**Los 9 aspectos:** averías del motor · **recalls en el KBA** (`kfz-rueckrufe.de`) · precio de mercado · fiabilidad **incluida la caja** · homologación · **etiqueta DGT literal** · seguro · piezas · otros.

**Riesgo mecánico:** *DQ200 2015: ~25 % de incidencia antes de 180.000 km, reparación 1.400-2.000 € → riesgo esperado ≈ 425 €.* Comprobar: DQ200 (`7-Gang DSG` seco), HPFP del N54, cadena EA888 gen2, DPF en diésel urbano, batería en PHEV, correa en 1.4 TSI. **No se resta del ahorro, se comunica.**

**Señales buenas:** ITV/TÜV nueva · `Scheckheftgepflegt` · `Batterie-Zertifikat` en PHEV · vendedor que declara un desperfecto · descripción que dice *unfallfrei, Serienzustand, ohne Umbauten*.

**Alerta:** `Reparierter Unfallschaden` · `Stage`/chip · `Raucher-Paket` · `Export` · B2B sin garantía ni ITV · `im Kundenauftrag` · vendedor solo por WhatsApp.

**Costes escondidos:** correa en 1.4 TSI (600-900 €) · ITV a petición (300-400 €) · titularidad italiana.

---

# ENTREGABLES OBLIGATORIOS POR FASE

> **Cada fase produce SU entregable, en orden, y NO se mezclan en un mismo archivo.**
> Verificado el 15-ago-2026 (Tiguan cliente): se creó un único `informe_tiguan_cliente.md`
> que mezclaba búsqueda y valoración, y el informe de unidad solo apareció al pedirlo.
> Eso es un fallo de la skill: la fase 1 es CANDIDATOS + INFORME DE BÚSQUEDA, y la
> valoración de una unidad solo llega cuando el usuario avanza con ella.

| Fase | Entregable | Qué es | Cuándo se genera |
|---|---|---|---|
| **1 · Búsqueda** | **Informe de búsqueda** + candidatos | Cobertura por fuente (URL, filtros, nº resultados), tabla de candidatos con precio/año/km/enlace, qué se excluyó y por qué | Al terminar el barrido de TODAS las fuentes. Es la conclusión de la fase 1 |
| **2 · Avance con un candidato** | **Informe de la unidad / valoración** | Las 11 secciones no negociables, SOLO del/los candidato(s) que el usuario elige | Cuando el usuario avanza con un candidato concreto. NO antes |
| **3 · Cierre** | **ZIP empaquetado** | `empaquetar.py` → `paquetes/` con informe.json, manifest.json y contenido/ | Al confirmar el coche |

**Reglas:**

1. **La fase 1 acaba con el informe de búsqueda y la lista de candidatos.** No se escribe
   informe de valoración en la fase 1: solo cobertura + candidatos.
2. **No se mezclan búsqueda y valoración en el mismo archivo.** El informe de búsqueda es
   `informe_busqueda_<modelo>.md`; el de unidad es `informe_unidad_<modelo>_<unidad>.md`.
3. **El informe de la unidad NO se genera en la fase 1 ni para todos los finalistas.**
   Se genera cuando el usuario avanza con un candidato concreto, y solo para ese/os.
4. **El ZIP se genera al cerrar coche**, no cuando el usuario lo recuerda.
5. **Todo informe se guarda en la carpeta de trabajo del usuario.**

---

# EL PAQUETE .zip

```bash
python3 empaquetar.py informes/<coche_id>.json --salida paquetes
python3 cache_investigacion.py guardar informes/<coche_id>.json
```

> **El ZIP es entregable obligatorio de la fase de cierre.** Si no se generó, la fase no
> está terminada. No se da por cerrado un coche sin su paquete.

**Campos que rompen el cálculo:** `costes.pvp_nuevo`, `costes.otros` (~114 €), `costes.honorarios`, `veredicto.precio_objetivo`, `mercado.*` cuadrando con `comparables`.

Contenido: `informe.json` · `manifest.json` · `contenido/informe-interno.txt` · `contenido/ficha-publicitaria.txt` · **`contenido/redes-sociales.txt`** · **`contenido/anuncio-portales.txt`** · `fotos/`.

> **Los dos esqueletos nuevos NO están implementados en `empaquetar.py`.** Hasta que se añadan, el copy se da en el chat.

**`redes-sociales.txt`** — `[GANCHO]`, `[POST_LARGO]`, `[POST_CORTO]`, `[STORIES]`, `[HASHTAGS]`, `[PIE_FOTO]`. **Enseña los números.** **Nunca prometas el ahorro máximo.** Etiqueta CERO o ECO al gancho. **En exóticos, el gancho es que aquí no existe.**

**`anuncio-portales.txt`** — `[TITULO]`, `[DESCRIPCION]`, `[FICHA_RAPIDA]`, `[QUE_INCLUYE]`, `[AVISO_LEGAL]`. El aviso legal **no es opcional**: servicio bajo pedido, no stock.

**Cortafuegos:** en documentos de cliente **ningún importe interno** más allá del precio final y los honorarios.

**Subida:** `https://dev.aktive.cloud/importnexcore/cars/import-valuation` → **Subir ZIP**.

**Informes en PDF**, no HTML y **nunca un CSV suelto**. WeasyPrint con `@page` (A4, márgenes
15/13/16 mm), `thead { display: table-header-group }` y `page-break-inside: avoid`.

### Qué lleva un informe de valoración — no negociable

Un CSV con precios **no es un informe**. Contenido mínimo, en este orden:

1. **Qué se ha medido y dónde** — tabla con **una fila por fuente**: URL o ruta consultada,
   filtros exactos, número de resultados y para qué se usa. Si una fuente no ha servido,
   **también se lista, diciendo por qué**.
2. **La oferta española** — una fila por unidad: precio, año, km, particular o profesional,
   fuente y nota. Al pie, mediana, cuartil bajo y tramo de la Puerta A.
3. **La oferta alemana** — igual, más **días publicado**, **portal de origen** y tasación del
   agregador. Se dice qué unidades se excluyen y por qué.
4. **El candidato** — matriculación, km/año, ubicación, color, cambio, equipamiento línea a
   línea, ficha técnica de km77, CO₂, etiqueta DGT y **enlace**.
5. **El comparable ajustado** — la fórmula visible y **una fila por unidad española** con el
   ajuste de año y el de km por separado. Al pie, mediana y cuartil bajo.
6. **El coste puesto en Huelva** — con una columna de *«de dónde sale»* en cada línea, y el
   IEDMT desarrollado paso a paso, incluida la variante sin minoración.
7. **Margen y veredicto** — contra mediana y contra cuartil bajo, tabla de vendibilidad con
   los cinco factores **justificados uno a uno**, y casilla de la matriz.
8. **Riesgos y banderas** — con estado: `pendiente` · `bloqueante` · `descartado` ·
   `confirmar con gestor`. Lo que no se ha comprobado **se dice que no se ha comprobado**.
9. **Alternativas** — las de más margen y por qué no son la primera opción.
10. **Qué hacer** — pasos numerados y accionables.
11. **Pie de fuentes** con fecha, **un párrafo aparte de «lo que es estimación»** y el aviso
    de que no es asesoramiento fiscal ni legal.

**Cada número tiene que poder rastrearse hasta una fuente o hasta una fórmula escrita en el
propio documento.** Lo estimado lleva su etiqueta.

**Verifica toda la aritmética con Python antes de generar el PDF**, incluidos los porcentajes
del texto corrido, no solo los de las tablas.

---

# REGISTRO DE DEMANDA Y CIERRE DEL BUCLE

**Registro:** quién, qué modelo o segmento, presupuesto (**y si es del coche o todo incluido**), configuración imprescindible, plazo, y en qué plataforma miraba. **Antes de valorar cualquier candidato, comprueba si encaja con alguien.**

**Cierre:** ahorro y coste estimado vs. real · días hasta la primera pregunta y hasta el cierre · por qué se cayó · desde qué plataforma llegó. **Y las descartadas.**

---

# FLUJO 2 — CLIENTE CONCRETO

**A.** Presupuesto **y si es del coche o todo incluido** · uso · **km/año y trayecto** (pocos km cortos → **nunca diésel moderno**) · plazo · plazas · combustible y cambio · equipamiento · km y año · marcas vetadas · **etiqueta DGT** · **dónde ha mirado**.

**B.** 3-5 **modelos** con **al menos una alternativa que no habría pedido**.
**C.** Peina España y saca la franja. **D.** Rastrea Alemania dentro de la franja.
**E.** 3-5 finalistas: `python3 comparativa_cliente.py`.
**F.** Si importar no compensa, **dilo**.

> **LA TARIFA DEPENDE DE DÓNDE ESTÁ LA UNIDAD — se decide ANTES de desglosar.**
> Verificado el 15-ago-2026 (Tiguan cliente): el cliente pedía «todo incluido» y el flujo
> asumió los 1.500 € de importación sin comprobar si el coche estaba ya en España.

| Ubicación de la unidad | Qué se cobra | Qué se descuenta del desglose |
|---|---|---|
| **En España** (concesionario/particular ES) | **Tarifa de gestión reducida** (~500 €, validar con el usuario) | NO transporte · NO *Ausfuhrkennzeichen* · NO IEDMT · NO ITV de importación |
| **En Alemania / UE** | Honorarios de importación (1.500-2.250 €) + coste fijo completo (transporte, Ausfuhr, IEDMT, ITV) | Nada: aplica el desglose completo |
| **Canarias / Baleares** | Ojo: IGIC, no IVA. El traslado peninsular extra NO compite en igualdad | Restar o descartar si no encaja en presupuesto |

**En el desglose de un coche en España** se muestra precio + tarifa de gestión, y la línea
«ahorro frente a importar» como argumento comercial. **En un coche en Alemania**, el desglose
completo de la sección «DESGLOSE Y VEREDICTO».

**Entregables del flujo (obligatorios, en orden):**
1. Fase 1 → informe de búsqueda + candidatos (al terminar el barrido).
2. Fase 2 → informe de la unidad SOLO del candidato en el que avance.
3. Fase 3 → ZIP al cerrar coche. — Ver «ENTREGABLES OBLIGATORIOS POR FASE».

---

# CHECKLIST

**Antes de gastar**
- [ ] Miré el registro
- [ ] Capturo todo de una pasada
- [ ] Agrupé por dominio

**Al medir**
- [ ] Conté `countryCode`
- [ ] Usé `powertype=kw`, no `ps`
- [ ] Verifiqué `initialSearch` en Coches.net — **recuerda los filtros de la navegación anterior**
- [ ] El PVP y el CO₂ salen de **km77**, no de una estimación
- [ ] Apliqué la **minoración del artículo 69** y di también la cifra sin minorar
- [ ] La referencia de precio español sale de **Wallapop, Milanuncios o Coches.net**, no solo de AutoScout24
- [ ] Medí Alemania en **mobile.de**, no en AutoScout24.de
- [ ] Validé el `ms` contra el `<h1>`

**Al evaluar**
- [ ] Puntué la **vendibilidad** antes que el margen
- [ ] Comparable que casa en las siete claves
- [ ] **Filtro de admisión aplicado**: solo ±2 años y ±40 % de km, ajustes capados al ±25 %
- [ ] Con <15 comparables **di un rango, no una cifra**, y deja que mande el suelo
- [ ] Dije que el comparable español es **precio pedido**, no precio de venta
- [ ] Ajuste de acabado, equipamiento e historial línea a línea
- [ ] Leí la descripción entera
- [ ] Regla 6 meses/6.000 km
- [ ] CO₂, y si falta lo estimé y lo dije
- [ ] Aritmética verificada con Python
- [ ] Ahorro contra mediana **y** cuartil bajo
- [ ] Asigné casilla de la matriz

**Al entregar**
- [ ] El resultado es un **PDF**, no un CSV
- [ ] Cada fuente aparece con su URL, sus filtros y su recuento
- [ ] Hay un apartado explícito de **«lo que es estimación»**

**Cobertura y entregables (obligatorio, añadido 15-ago-2026)**
- [ ] Peiné **TODAS las 7 fuentes**: ES (Wallapop, Milanuncios, Coches.net, AutoScout24.es) y DE (mobile.de, kleinanzeigen.de, autouncle.de). Ninguna se salta por ser «100 % JS» — se navega con el navegador
- [ ] Si una fuente quedó sin peinar, **lo dije explícitamente** en el informe de búsqueda, con el motivo, ANTES de presentar candidatos
- [ ] Deduplicé entre fuentes por `(año, km ±2 %, CV, precio ±3 %)`
- [ ] Generé el **informe de búsqueda** al terminar el barrido — es el entregable de la fase 1, con cobertura por fuente y tabla de candidatos
- [ ] NO generé informe de valoración en la fase 1: búsqueda y valoración van en archivos separados
- [ ] Generé el **informe de la unidad** SOLO del candidato en el que avanzó el usuario, no de todos los finalistas
- [ ] Generé el **ZIP** al cerrar coche (`empaquetar.py`) — la fase no está terminada sin él
- [ ] Confirmé **contado vs financiado** en cada precio (MUY CAR/Flexicar muestran el financiado como grande)
- [ ] Apliqué la **tarifa según ubicación**: unidad en España → tarifa de gestión reducida (~500 €); en Alemania/UE → honorarios completos + coste fijo; Canarias/Baleares → IGIC y traslado extra

**Siempre**
- [ ] Todo candidato lleva su enlace
- [ ] Dije qué parte es estimación
- [ ] Un falso positivo es mucho peor que un falso negativo
- [ ] No afirmé haber visto/medido algo sin comprobarlo — si no está en los datos, digo que no está
- [ ] No hay margen, hay honorarios
- [ ] Nada de asesoramiento fiscal ni legal

---

# ROADMAP

**Hecho el 8 de agosto de 2026:** Wallapop y Milanuncios arreglados · Coches.net reescrito
sobre `__INITIAL_PROPS__` (35 por página, tasación y **fecha de publicación**) · AutoUncle
validado con `s[min_hp]` · km77 fijado como fuente de PVP, CO₂ y tipo · método correcto del
IEDMT con la minoración del artículo 69 · nomenclatura de matrículas corregida.

**Hecho el 9 de agosto de 2026:** auditoría completa de los seis portales · Milanuncios
reescrito sobre `__INITIAL_PROPS__` (41 anuncios frente a 7) · Wallapop con selector estable
y **descripción completa** · Coches.net con etiqueta DGT, reputación del vendedor y
`priceDrop` · `priceRankIndicator` calibrado · AutoUncle con enlace al anuncio de origen ·
librería única en `tools/extractores.js` · generador de informes con enlaces en
`tools/informe_importacion.py` · **detectado y corregido el fallo de inversión del comparable**.

**Del rastreador, por urgencia:**
1. **Calibrar la tabla de descuento por días publicado** con cierres reales. Es lo que más
   inflado tiene el margen hoy: 3-8 puntos.
2. **Construir el índice de rotación** con `publicationDate` y `publishDate`. Desbloquea el
   factor 1, que pesa 30.
3. **Leer la descripción del lado alemán** de los 3-5 finalistas abriendo el anuncio de
   origen. Es el bloqueante que aparece en todos los informes.
4. **Deduplicar entre fuentes** por `(año, km ±2 %, CV, precio ±3 %)`: un coche puede estar
   en Wallapop y en Milanuncios y hoy contaría dos veces.
5. **Varias consultas por modelo y unir**: la búsqueda por texto se pierde anuncios que
   escriben la versión de otra forma.
6. **Paginación completa de Coches.net** con `pg`, verificando `initialSearch` en cada página.
7. **Extractor propio de `kleinanzeigen.de`** y **caché de fichas de km77**.

**Análisis completo en `MEJORAS_SISTEMA_2026-08-09.md` de la carpeta de trabajo.**

**Rehacer con el método nuevo:** los 4 Golf R y toda la lista larga del 6 de agosto, que arrastran los fallos de `countryCode` y `powertype`.

**Sin medir:** BMW M240i · Volvo V90 y XC60 T8 · Mercedes Clase A sumando versiones · Toyota GR Yaris · Golf 8 GTI Clubsport · Audi RS4/RS6 · Mercedes C43.

**Lado Laravel (lo hace el usuario):** plantillas Blade · los dos esqueletos en `empaquetar.py` · campos `iva_deducible` y `ahorro_si_alta` · validador 6/6.000 · tarifas reales de transporte · registro de demanda · **importar `datos_mercado.json`** · **tabla de primas de equipamiento** · **índice de vendibilidad**.

**Fuera del sistema:** alta fiscal con el gestor, con el acumulado de `ahorro_si_alta` como argumento.

