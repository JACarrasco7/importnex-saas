# Qué se puede mejorar — auditoría del 9 de agosto de 2026

Escrito después de auditar los seis portales y de rehacer el informe del Astra OPC.
Ordenado por lo que más daño hace hoy.

---

## 1 · El comparable se invierte en los extremos — **fallo de lógica, no de datos**

La fórmula actual proyecta cada unidad española sobre la ficha objetivo con
`±8,5 %/año` y `±2 % por 10.000 km`, multiplicativos y sin límite.

Con la muestra real del Astra J OPC (objetivo: 2014, 102.000 km) sale esto:

| Unidad española | Proyecta a |
|---|---:|
| 17.990 € · 2012 · **186.000 km** | **24.584 €** ← la más alta de las ocho |
| 22.650 € · 2014 · 135.000 km | 24.145 € |
| 25.000 € · 2016 · **33.500 km** | **17.907 €** ← la más baja |

**Un coche con 186.000 km acaba argumentando que el objetivo vale más que un coche
con 33.500 km.** El ajuste extrapola muy por fuera de su rango válido y da la vuelta
a la realidad. No es un detalle: ese 24.584 € entró en la mediana del informe.

### Lo que propongo

**a) Filtro de admisión antes de proyectar.** Solo entra en la muestra lo que está
a `±2 años` y `±40 % de km` del objetivo. Lo demás no es comparable, es otro coche.

**b) Tope al ajuste.** Ningún ajuste individual pasa del `±25 %`. Si hace falta más,
la unidad no servía.

**c) Y lo más importante: dejar de usar la mediana como cifra de decisión.**
El cliente no compra la mediana. El cliente compara contra **el coche más barato
que puede conseguir aquí y ahora en condiciones equivalentes**. Ese es el número que
decide la operación. La mediana sirve para el discurso comercial; el suelo, para el
veredicto.

Comparativa de los tres métodos sobre los mismos ocho coches:

| Método | Comparable | Ahorro |
|---|---:|---:|
| Actual (los 8 proyectados, mediana) | 22.559 € | +27,3 % |
| Solo cercanos con ajuste capado | 23.424 € | +29,9 % |
| Regresión precio ~ año + km | 20.627 € | +20,4 % |

**Diez puntos de diferencia eligiendo método, con los mismos datos.** Eso, por sí solo,
dice que con n=8 no hay una cifra puntual defendible. La regresión además saca
coeficiente de km **positivo** (+0,0033 €/km): más kilómetros, más dinero. Absurdo, y
es la prueba de que la muestra no da para un estimador puntual.

**Conclusión honesta: con menos de 15 comparables reales no se da un número, se da un
rango, y el veredicto lo manda el suelo.**

---

## 2 · Comparamos precios reales contra precios pedidos — **sesgo sistemático a nuestro favor**

Todo el lado español son **precios de anuncio**. Nuestro precio final es un precio
**real**: lo que el cliente paga. Estamos restando peras de manzanas, y siempre en la
dirección que nos favorece.

Un coche de segunda mano no se vende por lo que pide el anuncio. Y **ahora ya podemos
medir cuánto se desvía**, porque tenemos el dato que faltaba:

- Coches.net → `publicationDate` y `priceDrop`
- Milanuncios → `publishDate` y `updateDate`
- AutoUncle (lado alemán) → días publicado y precio anterior

Ejemplo del propio informe: el profesional de Milanuncios lleva **65 días** a 15.900 €.
Ese coche no se va a vender a 15.900 €.

### Lo que propongo

**Descuento de negociación en función de los días publicado**, aplicado al comparable
español antes de calcular el ahorro:

| Días en el mercado | Descuento sobre el precio pedido |
|---|---:|
| < 15 | 0 % — todavía es precio de salida |
| 15-45 | −3 % |
| 46-90 | −6 % |
| > 90 | −10 % |

Son valores de partida, no verdad revelada. **Hay que calibrarlos** con el cierre del
bucle: cuando se venda un coche que teníamos medido, se anota precio pedido, días y
precio real. Con veinte observaciones la tabla deja de ser una estimación.

**Impacto:** entre 3 y 8 puntos de margen en casi todas las operaciones. Es la
corrección que más cambia los veredictos, y va en nuestra contra. Por eso hay que
hacerla.

---

## 3 · El factor demanda ya se puede medir — deja de ser una casilla

Es el factor de más peso (30 de 100) y hasta hoy era una estimación razonada. Ya no
hace falta: **`publicationDate` está en Coches.net y `publishDate` en Milanuncios**.

### Índice de rotación

```
rotacion(modelo, version) = mediana de dias publicados de los anuncios vivos
```

Un modelo cuyos anuncios llevan de mediana 20 días rota; uno con 120 días, no.

**Reparto propuesto**, a calibrar:

| Mediana de días publicados | Puntos (sobre 30) |
|---|---:|
| ≤ 25 | 30 |
| 26-50 | 24 |
| 51-90 | 16 |
| 91-150 | 9 |
| > 150 | 4 |

**Cómo construirlo sin gastar sesiones:** cada vez que se mida un modelo, guardar en
`datos_mercado.json` la lista de días publicados. El índice se va llenando solo con el
trabajo normal. En diez modelos ya hay base para comparar unos contra otros, que es lo
único que importa.

**Ojo con el sesgo:** los anuncios vivos sobrerrepresentan los que no se venden (los
rápidos desaparecen). Sirve para **comparar modelos entre sí**, no como cifra absoluta.

---

## 4 · Mejoras de extractores

### 4.1 · Deduplicar entre fuentes

Un mismo coche puede estar en Wallapop **y** en Milanuncios. Hoy contaría dos veces y
falsearía tanto la escasez como la mediana.

Clave de deduplicación: `(año, km ±2 %, CV, precio ±3 %)`. Si coinciden, es el mismo.

### 4.2 · Varias consultas por modelo, y unir

Wallapop y Milanuncios buscan por texto libre y **se pierden anuncios que escriben la
versión de otra forma**. Hoy lanzamos una sola consulta.

Para el Astra OPC habría que lanzar: `astra opc`, `astra gtc opc`, `astra j opc`,
`astra 280`. Unir y deduplicar. Cuesta tres peticiones más y sube la cobertura, que es
justo lo que sostiene el factor escasez.

### 4.3 · Leer la descripción también en el lado alemán

Es el bloqueante que aparece en todos los informes. En España ya lo tenemos resuelto
—la descripción viene en la tarjeta de Wallapop y en el JSON de Milanuncios— y ha
servido: **el Astra de 20.000 € que usábamos como comparable resultó ser un Stage 2**.

En Alemania sigue faltando. AutoUncle no trae el texto libre. Hay que abrir el anuncio
de origen de los 3-5 finalistas. Son 3-5 peticiones y es barato comparado con ofertar
un coche preparado.

### 4.4 · Paginar Coches.net de verdad

Hoy leemos la primera página. Con `&pg=N` y `totalPages` se recorre entero, filtrando
`items[].hp` en local. Para gamas grandes son muchas páginas, así que: **acotar con
`&pf=` y `&fr=` primero**, y verificar `initialSearch` en cada página por la
persistencia de filtros.

### 4.5 · Campos que ya vienen y no usamos

| Campo | Fuente | Para qué |
|---|---|---|
| `priceDrop` | Coches.net | Marca quién ya ha bajado: señal de negociación |
| `seller.ratings.average` | Coches.net | Reputación del vendedor profesional |
| `environmentalLabel` | Coches.net | Etiqueta DGT sin ir a km77 |
| `warrantyMonths` | Coches.net · Milanuncios | Separa el techo del mercado |
| `updateDate` | Milanuncios | Si se ha reactivado el anuncio |
| `fotos` | Wallapop · Milanuncios | Menos de 5 fotos = anuncio pobre, señal de prisa |

---

## 5 · Cosas menores pero baratas

- **Registrar la fecha y hora de captura por unidad.** Hoy el informe dice "medido el
  9 de agosto"; debería decirlo por anuncio, porque los precios se mueven. En la
  auditoría de hoy ya habían desaparecido dos coches alemanes del listado del día 8.
- **Guardar el HTML o el JSON crudo de cada medición** junto al informe. Si un anuncio
  desaparece, el informe deja de ser verificable. Pesa poco y salva discusiones.
- **Avisar cuando la muestra alemana también sea de precios pedidos.** El mismo sesgo
  del punto 2 existe en el otro lado, y ahí nos perjudica: si el alemán negocia, el
  coste real baja y el ahorro sube. No lo estamos contando.

---

## Orden de ataque

| # | Qué | Esfuerzo | Efecto |
|---|---|---|---|
| 1 | Filtro de admisión + tope de ajuste + veredicto por el suelo | Bajo | **Arregla un fallo de lógica activo** |
| 2 | Descuento por días publicado en el comparable español | Bajo | Quita 3-8 puntos de margen inflado |
| 3 | Índice de rotación con `publicationDate` | Medio | Desbloquea el factor de más peso |
| 4 | Descripción alemana de los finalistas | Bajo | Evita ofertar coches preparados |
| 5 | Deduplicación y varias consultas por modelo | Medio | Mejora escasez y cobertura |
| 6 | Paginación completa de Coches.net | Medio | Muestras de verdad, no primera página |
| 7 | Calibrar la tabla de descuento con cierres reales | Continuo | Convierte la estimación en dato |

Los dos primeros son media tarde y cambian todos los veredictos que salgan a partir de
ahora. El resto puede ir entrando con el trabajo normal.
