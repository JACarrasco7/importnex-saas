# Rediseño del flujo, de principio a fin

Pensado desde cero el 9 de agosto de 2026, después de auditar los seis portales.
Parte de que hay **tres escenarios de entrada distintos** y de que hoy los tres usan
el mismo camino, que sirve bien para uno solo.

---

# 0 · El error que acabo de encontrar: el equipamiento

Antes de nada, porque invalida parte de cómo puntuamos.

**La tabla de primas de equipamiento se aplica a ciegas.** Damos +600-1.200 € por cuero,
+500-1.000 € por LED o xenón, +400-800 € por CarPlay… sin comprobar si en **esa versión
concreta** eso venía de serie.

Comprobado en la ficha de equipamiento de km77 del Astra OPC:

| Elemento | En el Astra OPC | Lo que hicimos |
|---|---|---|
| Faros de xenón | **De serie** | Le dimos 2 puntos ❌ |
| Navegador Navi 950 IntelliLink | **De serie** | Le dimos puntos por GPS ❌ |
| Espejo electrocromático, sensor de lluvia, climatizador bizona, Bluetooth, llantas de 19", asientos High Performance | **De serie** | Los listamos como equipamiento destacado ❌ |
| Tapicería de cuero Nappa | **Paquete de 2.183 €**, incluye asientos calefactables | 3 puntos ✅ correcto, y corto |
| Sensores de aparcamiento | **218 € opcional** | ✅ correcto |
| Techo panorámico | Pack de 416 € **que elimina el Navi 950** | No aplicaba |

**Si algo va de serie en la versión, su prima es CERO**, porque todos los comparables
españoles lo llevan también. Solo diferencia lo que era opcional.

El factor 4 del Astra debería haber sido **4, no 7**. La vendibilidad baja de 74 a 71.
El veredicto no cambia —sigue por encima de 65— pero el número estaba inflado.

**Y una bandera que salió sola:** el anuncio alemán dice «asiento eléctrico», y en esta
versión km77 lo marca **«No disponible»**. O el anuncio exagera, o AutoUncle tradujo
mal, o no es la versión que dice. Hay que preguntarlo.

**Regla nueva: antes de puntuar el factor 4 o de ajustar equipamiento en el comparable,
se lee `…/datos/equipamiento` de km77 y se clasifica cada extra en de serie / opcional /
no disponible. Solo puntúa lo opcional.** Es una petición, y `web_fetch` la lee sin
navegador.

---

# 1 · El espinazo común

Los tres escenarios comparten el mismo motor. Lo que cambia es por dónde entran.

```
                 ESCENARIO A          ESCENARIO B           ESCENARIO C
                 (una URL)         (cliente concreto)     (barrido propio)
                      |                    |                     |
                 coche fijo          perfil y presupuesto    nada fijo
                      |                    |                     |
                      |            elegir 3-5 modelos      CRIBA BARATA
                      |                    |               (2 peticiones
                      |                    |                 por modelo)
                      |                    |                     |
                      +--------------------+---------------------+
                                           |
                                  FICHA TECNICA CANONICA
                                  (km77: PVP, CO2, tipo,
                                   equipamiento de serie)
                                           |
                                    MERCADO ESPANOL
                              (Wallapop + Milanuncios + Coches.net,
                               deduplicado, con dias publicado)
                                           |
                                    NIVEL DE CONFIANZA
                                  (cuantos comparables reales)
                                           |
                              +------------+------------+
                              |                         |
                        >=8 comparables           <8 comparables
                        cifra + intervalo        rango y discurso
                              |                   de exclusividad
                              +------------+------------+
                                           |
                                    COSTE Y VEREDICTO
                                           |
                                       REGISTRO
                            (siempre, tambien los NO)
```

**Lo que hoy no existe y es el agujero más gordo: el registro.** La skill dice
«¿está en el registro?» como paso 0, pero `datos_mercado.json` **no existe en la
carpeta**. Cada sesión empieza de cero. En el escenario C eso es fatal: vuelves a medir
lo mismo.

---

# 2 · Escenario A — me pasas una URL

**Lo que tienes:** un coche concreto. **Lo que falta:** todo lo español.

El coche alemán es gratis: una petición y ya tengo precio, km, año, versión y, si es
mobile.de o Kleinanzeigen, **la descripción entera**. Así que **todo el presupuesto de
peticiones se va al lado español**, que es donde se decide.

**Orden:**

1. Leer la URL. Si es AutoUncle, saltar al anuncio de origen para tener el texto libre.
2. **km77**: ficha de la versión → PVP, CO₂, tipo, y la lista de qué es de serie.
   Ya sé si el «cuero» del anuncio suma o no suma.
3. **Criba inmediata antes de gastar más**: siniestro declarado, `Stage`/`Chiptuning`,
   regla 6/6.000, país distinto de DE, km/año > 30.000, título que no cuadra.
   **Si cae aquí, se acabó. Una petición gastada, no quince.**
4. España: Wallapop + Milanuncios + Coches.net con **varias consultas por modelo**,
   deduplicado.
5. Comparable, coste, veredicto.

**Salida:** el informe completo, con enlaces.

**Lo que hay que añadir:** el paso 3 no está formalizado. Hoy se lee la descripción
tarde, cuando ya se ha medido todo. Debe ser lo segundo que pase.

---

# 3 · Escenario B — un cliente concreto

**Lo que tienes:** presupuesto, uso, gustos. **Lo que falta:** qué modelos.

El error clásico aquí es proponer modelos que en Alemania no existen a ese precio.
Por eso **España va primero, pero para acotar, no para valorar**.

**Orden:**

1. **Cuestionario** — el que ya está en la skill, y con dos preguntas que faltan:
   *¿qué tres coches ha mirado ya?* y *¿qué le echó para atrás de cada uno?* Eso da el
   perfil real mucho mejor que las preferencias declaradas.
2. **3-5 modelos**, con al menos uno que no habría pedido. **Contra el registro
   primero**: si ya está medido y es reciente, no se vuelve a medir.
3. **Criba barata sobre esos modelos** (la del escenario C, apartado 4). Descarta los
   que no tienen hueco antes de rastrear.
4. Rastreo a fondo solo de los 2 que sobrevivan.
5. **Comparativa entre finalistas**, no informe suelto: la decisión del cliente es
   entre coches, no sobre un coche.

**Lo que hay que añadir:** la comparativa entre finalistas debería incluir **coste de
uso** —seguro, consumo, mantenimiento— porque el cliente compara eso, no solo el precio.
Un OPC de 280 CV y un Golf 1.5 no cuestan lo mismo de tener.

---

# 4 · Escenario C — dime tú qué es rentable

Este es el que peor funciona hoy y el que más margen de mejora tiene.

**El problema:** hoy se mide modelo por modelo, a fondo, y se gasta la sesión entera en
tres o cuatro. Y muchas veces el resultado es «ninguno sale».

## La criba barata: dos peticiones por modelo

Con lo auditado ahora se puede saber si un modelo merece la pena **con dos navegaciones**:

**Petición 1 — AutoUncle.** El `<title>` da, sin tocar el DOM:
`«N coches de ocasión · precios desde X»`. Con eso tengo **suelo alemán y volumen**.

**Petición 2 — Coches.net.** `__INITIAL_PROPS__` da en una sola página:
`totalResults`, **35 precios españoles**, y **los días publicado de cada uno**.
Con eso tengo **nivel de precio español, volumen y rotación**.

De ahí salen los cuatro números que deciden si vale la pena mirar:

| Indicador | De dónde | Para qué |
|---|---|---|
| Suelo alemán | título de AutoUncle | ¿hay coche barato? |
| Cuartil bajo español | Coches.net | ¿contra qué compito? |
| Ratio de volumen | ambos | escasez de verdad |
| **Mediana de días publicado** | Coches.net | **rotación = demanda** |

**Veinte modelos son cuarenta peticiones.** Cabe en una sesión. Hoy no caben cuatro.

## El ranking

Con esos cuatro números se ordena y solo se rastrean a fondo los tres primeros.

```
prioridad = margen_bruto_estimado  x  vendibilidad_estimada  x  probabilidad_de_cierre
```

- **margen_bruto_estimado** = (cuartil bajo español − suelo alemán − 3.300 € de costes fijos) / cuartil bajo
- **vendibilidad_estimada** = rotación + escasez, que son los dos que ya se miden barato
- **probabilidad_de_cierre** = penaliza lo que suele caerse: modelos muy preparados
  (OPC, Type R, GTI, RS), muy poca oferta alemana (<10 unidades), o precios de anuncio
  muy viejos

Ese tercer factor es el que hoy no existe y explica los barridos fallidos: **modelos que
sobre el papel salen y en la práctica se caen siempre en la criba de siniestros o
preparaciones**. Si de cinco OPC alemanes dos van tuneados, la probabilidad de cierre de
ese modelo es 0,6, y eso debe entrar en el ranking.

**Se calibra solo** con el registro de descartes: cada coche que se cae y por qué.

---

# 5 · Duplicidad — cómo detectarla bien

Hoy no se detecta. Un mismo coche en Wallapop y en Milanuncios cuenta **dos veces**, e
infla la escasez y ensucia la mediana. Y un coche republicado tras caducar aparece como
dos coches distintos.

## La clave: los kilómetros

`km` es un número de seis cifras. Dos coches del mismo modelo, mismo año y misma potencia
que además coincidan al kilómetro exacto, en una muestra de veinte, prácticamente no
existen.

| Nivel | Condición | Qué hacer |
|---|---|---|
| **Idéntico** | mismo `km` exacto **y** mismo `año` **y** mismo `cv` | Mismo coche. Fusionar |
| **Probable** | `\|Δkm\| < 1.000` **y** mismo año **y** mismo cv **y** `\|Δprecio\| < 5 %` | Mismo coche republicado o con precio actualizado. Fusionar y avisar |
| **Sospechoso** | mismo año, mismo cv, misma provincia, `\|Δprecio\| < 3 %` | Marcar para revisar a ojo. No fusionar solo |

**Al fusionar:** se queda el **precio más bajo** y se anota *«también en X a Y €»*. Si
las fechas de publicación difieren, la buena es **la más antigua**: es cuando empezó a
intentar venderlo, y es lo que alimenta la rotación.

**Casos que hay que cazar además:**

- **Reventa de importador.** Un coche que ya estaba en Alemania y ahora aparece en
  España. Se detecta por la descripción (*«importado»*, *«traído de Alemania»*) — el
  Astra de 19.990 € de Wallapop lo dice literalmente. **No es competencia normal:
  es alguien haciendo lo mismo que nosotros, y su precio es la referencia real.**
- **El mismo profesional con varias fichas.** `seller.contractId` de Coches.net lo
  identifica.
- **Anuncios zombi.** Publicados hace más de un año y nunca actualizados. En Milanuncios
  vi uno de 2020. Se excluyen de la mediana: no son mercado.

---

# 6 · Las cuentas, rehechas

## 6.1 · El comparable tiene niveles de confianza, no una cifra

Ya está en la skill el filtro de admisión. Lo que falta es que **el nivel de confianza
decida qué se puede decir**:

| Comparables que casan las 7 claves | Qué se publica |
|---|---|
| **≥ 15** | Mediana y cuartil bajo. Porcentaje de ahorro con una cifra decimal |
| **8-14** | Mediana **y** cuartil bajo, y el ahorro **como rango**, no como número |
| **4-7** | Solo cuartil bajo y suelo. **Nada de porcentajes**: «cuesta X menos que el más barato que hay hoy en España» |
| **1-3** | Ni medianas ni porcentajes. El discurso es la unidad concreta contra la unidad concreta |
| **0** | Exclusividad pura: «aquí no existe» |

El Astra OPC está en 8-14 y le dimos un 27,3 % con una decimal. **Falsa precisión.**

## 6.2 · Dos formas de expresar el valor, no una

Hoy siempre decimos «ahorra X €». Cuando la muestra es mala, ese número es frágil.
Pero hay otra forma que **no depende de ningún ajuste porcentual**:

> **Por el mismo dinero que el más barato de España, te llevas 40.000 km menos y dos
> años más nuevo.**

Es incontestable: son dos coches reales, dos precios reales, dos fichas reales. No hay
fórmula que discutir.

**Regla:** si la muestra da para el ahorro en euros, se dan **las dos** cosas. Si no da,
se da solo la mejora de ficha a igualdad de precio. **Nunca se deja al cliente sin
argumento por culpa de una muestra pobre.**

## 6.3 · Coste de propiedad, no solo precio de compra

Falta entero y el cliente lo tiene en la cabeza. Con la ficha de km77 ya tenemos consumo
y CO₂, y con la potencia fiscal sale el impuesto de circulación. Añadir al informe una
línea de **coste anual estimado** (seguro + consumo a 15.000 km + circulación) cambia
conversaciones: un ahorro de 2.000 € en la compra se come en tres años si el seguro es
600 € más caro.

## 6.4 · El ahorro que sí es nuestro

Recordatorio de lo que ya está y conviene no perder: **no hay margen, hay honorarios**.
El «ahorro» es del cliente. Lo que hay que vigilar es que el ahorro siga siendo positivo
**después** de los honorarios, que es como está planteado. Correcto hoy.

Lo que falta es lo contrario: **cuánto ahorro sacrificamos por cobrar más**. Si subir de
1.500 a 2.250 € de honorarios tira la operación por debajo del umbral, hay que verlo
escrito. Una línea de sensibilidad: *«con honorarios de 2.250 € el ahorro pasa del
12,1 % al 8,0 %»*.

---

# 7 · Más datos que se pueden sacar

| Fuente | Qué más hay | Para qué |
|---|---|---|
| **km77** | `…/datos/equipamiento`: de serie / opcional / no disponible **con precio de cada opción** | **Arregla el factor 4 y el ajuste de equipamiento.** Lo más importante de esta lista |
| **km77** | `…/mediciones-propias` | Consumo real y prestaciones medidas: material de contenido de calidad |
| **Coches.net** | `priceDrop` | Quién ya ha bajado el precio: señal de negociación |
| **Coches.net** | `seller.ratings.average` y `totalReviews` | Reputación del profesional. También identifica al mismo vendedor con varias fichas |
| **Coches.net** | Ficha de detalle del anuncio | Equipamiento completo y tasación individual. Hoy solo leemos el listado |
| **Milanuncios** | `updateDate` frente a `publishDate` | Anuncio reactivado, o zombi |
| **Wallapop · Milanuncios** | Nº de fotos | Menos de 5 fotos suele ser prisa o algo que no se enseña |
| **Kleinanzeigen directo** | Descripción entera y **otros anuncios del mismo vendedor** | La criba que hoy falta, y detectar chatarreros |
| **mobile.de** | `priceRating` y precio anterior | Negociar allí con su propio dato |

---

# 8 · Qué haría primero

| # | Qué | Esfuerzo | Por qué |
|---|---|---|---|
| 1 | **Equipamiento contra km77** antes de puntuar | Bajo | Corrige un error activo. Una petición |
| 2 | **Crear el registro** `datos_mercado.json` y usarlo de verdad | Bajo | Sin él, el escenario C no escala |
| 3 | **Criba barata de dos peticiones** por modelo | Medio | Convierte el barrido en algo viable |
| 4 | **Deduplicación por km** | Bajo | Arregla escasez y mediana a la vez |
| 5 | **Criba de siniestros como paso 2**, no como paso 12 | Bajo | Deja de gastar sesiones en coches que se van a caer |
| 6 | **Niveles de confianza** que limiten qué se afirma | Bajo | Quita la falsa precisión |
| 7 | **Valor expresado también como mejora de ficha** | Bajo | Argumento que no depende de la muestra |
| 8 | Coste de propiedad en el informe | Medio | El cliente ya lo está pensando |
| 9 | Probabilidad de cierre en el ranking | Continuo | Se calibra con los descartes |

Los seis primeros son trabajo de una tarde entre todos y arreglan lo que hoy está roto
o inflado. Del 7 al 9 son mejora, no corrección.
