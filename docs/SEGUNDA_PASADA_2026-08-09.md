# Segunda pasada — qué más se puede mejorar

9 de agosto de 2026. Escrito después de que me corrigieras lo del asiento eléctrico,
que resultó ser el hilo del que tirar.

---

# 0 · Rectifico: km77 no vale para juzgar el coche alemán

Ayer di por bandera que el anuncio alemán mencionara asiento eléctrico, porque km77
lo marca **«No disponible»** en el Astra OPC. Tú has visto fotos de OPC con asiento
eléctrico. Tienes razón y el error es mío, pero es un error **útil**, porque señala
algo que no teníamos claro:

> **km77 es la tarifa ESPAÑOLA. Opel Alemania no vendía el mismo catálogo que Opel
> España.** Lo que aquí no se ofrecía, allí podía ser opción de catálogo o venir en un
> paquete distinto.

Eso parte el uso de km77 en dos, y solo uno de los dos era correcto:

| Para qué | ¿Vale km77? |
|---|---|
| Saber qué llevan **los comparables españoles** y por tanto qué NO diferencia | **Sí.** Es exactamente la tarifa que se vendió aquí |
| Saber qué puede llevar **el coche alemán** | **No.** Otro catálogo, otras opciones, otros paquetes |
| Decidir si un extra del anuncio alemán es mentira | **No.** Y yo lo usé para eso |

**Y aquí está lo bueno:** si el asiento eléctrico no estaba en la tarifa española, no es
una bandera roja. **Es un argumento de venta.** Un OPC con algo que aquí no se podía
pedir es exactamente la casilla «equipamiento sobre el estándar español», que es el
factor 4 entero.

Estuve a punto de tirar la mejor baza del coche tomándola por un fallo.

## Lo que hay que hacer en su lugar: rareza medida, no catálogo

Los catálogos son un lío: cambian por año de fabricación, por país y por paquete.
**No hace falta ninguno.** Ya estamos descargando muestras de los dos mercados, así que
se cuenta:

```
rareza_ES = anuncios espanoles de esa version SIN el extra / total
rareza_DE = anuncios alemanes de esa version CON el extra / total
```

- Extra que tienen **casi todos** los españoles → **prima 0**. No diferencia.
- Extra que tienen **pocos** españoles y **sí** el candidato → **prima real**, y su
  tamaño depende de lo escaso que sea.
- Extra que en España **no tiene ninguno** → no es prima, es **exclusividad**, y va al
  gancho del anuncio, no a la hoja de cálculo.

Es empírico, no depende de ninguna tarifa, y **se calcula gratis** porque los datos ya
están descargados. Y de paso responde a la pregunta que de verdad importa, que no es
«¿venía de serie?» sino **«¿lo tiene la competencia española que este cliente va a ver?»**.

La tabla de primas fijas de la skill (techo +800-1.500 €, cuero +600-1.200 €…) pasa a
ser **el tope**, no el valor: se aplica entera si en España no lo tiene casi nadie, y se
reduce a cero según sube la frecuencia.

---

# 1 · Segmentos que no están en el encargo permanente

La skill tiene dos segmentos: nicho por encima de 20.000 € y rotación de 8.000 a 20.000 €.
Ambos son turismos. Hay mercado fuera de ahí y no lo estamos mirando.

## 1.1 · Camper y autocaravana — el que más me llama

Medido ahora mismo en AutoUncle:

| | Alemania |
|---|---|
| **VW California** | **3.780 unidades, desde 21.278 €** |
| **VW Transporter** | **1.930 unidades, desde 4.999 €** |

Por qué encaja con este negocio mejor que casi nada:

- **Ticket alto.** Los costes fijos de 3.000-3.800 € pesan un 10 % en un coche de
  35.000 €, frente al 34 % en uno de 8.000 €.
- **España está desabastecida** y los precios de camper aquí son notoriamente altos.
  Hay que medirlo, pero la asimetría es conocida.
- **Alemania es el mercado camper de Europa.** Volumen y rotación real.
- **Coeficiente de antigüedad distinto en el BOE**: las autocaravanas tienen su propia
  tabla, que tarda **18 años** en bajar al 10 % en lugar de 12. Eso sube el IEDMT
  respecto a un turismo de la misma edad. **Hay que calcularlo con la tabla que toca,
  no con la de turismos.**
- **Comprador con plazo largo.** Quien busca camper espera meses. Encaja perfectamente
  con «no tengo stock, te lo traigo».

**Riesgo propio:** la conversión camper puede necesitar homologación de reforma en
España si no viene homologada de fábrica. Un California de fábrica no, una Transporter
camperizada por un tercero **sí**, y eso son 300-1.000 € y semanas. **Distinguir
siempre fábrica de camperización.**

## 1.2 · Furgoneta industrial

Transporter, Vito, Transit Custom, Ducato. Volumen alemán enorme y demanda española
constante. Dos particularidades que cambian las cuentas:

- **El comprador suele ser empresa o autónomo.** Le interesa el IVA deducible, que a
  ti como particular no. **Eso cambia el argumento de venta entero**: no es «ahorras X»,
  es «te lo traigo y te lo puedes deducir».
- **Muchas van sin IVA deducible (§25a) o con él.** El JSON de mobile.de ya lo dice con
  `netAmount`. Hoy ese campo se usa para calcular lo que ahorrarías estando de alta;
  para un cliente empresa es **información de venta directa**.

## 1.3 · Eléctricos

La skill nombra los PHEV y casi no los eléctricos puros. Alemania lleva años tirando
precios de eléctricos usados. **IEDMT 0 %, etiqueta CERO.**

**Pero el riesgo es distinto a todo lo demás y hay que tratarlo aparte:** el valor del
coche es la batería. Sin **certificado de salud de batería (SoH)** no se oferta. Ese es
el equivalente al libro de mantenimiento en un térmico, y es innegociable.

## 1.4 · Todoterreno de verdad

Wrangler, Defender, Land Cruiser, G. Precios españoles muy altos, demanda estable, y
son coches que la gente busca por configuración exacta —que es justo donde el sistema
funciona bien—. Merece una criba barata.

## 1.5 · Clásicos y +25 años

Ya nos tropezamos con ellos por accidente: la búsqueda mal formada devolvió ocho Puch
Pinzgauer entre 11.774 y 69.000 €. Hay mercado. Requiere saber de matrícula histórica y
del régimen fiscal propio, así que **no lo abriría todavía**, pero conviene tenerlo
anotado como sector con demanda y sin importadores en la zona.

## 1.6 · El tramo por debajo de 8.000 € sigue sin salir, salvo por una vía

Los costes fijos se comen todo. **La única vía es agrupar**: si el transporte baja de
900 a ~400 € trayendo tres coches, y son para tres clientes que ya han dicho que sí,
el coste fijo por coche baja de 3.300 a ~2.800 €. Sigue siendo mucho sobre 6.000 €.
**Lo dejaría cerrado salvo pedido concreto**, que es lo que ya dice la skill.

---

# 2 · El cambio estructural: dejar de barrer y empezar a vigilar

Esto es lo que más cambiaría del sistema entero.

**Hoy:** cada cierto tiempo se hace un barrido, se miran N modelos, y se ve lo que hay
**ese día**. El candidato del Astra llevaba 10 días publicado. Los coches buenos vuelan.
**Un barrido mensual solo ve las sobras.**

**Alternativa:** los portales ya tienen alertas. AutoUncle tiene *Suchagenten*, mobile.de
tiene búsquedas guardadas. Se configuran una vez por modelo y configuración objetivo, y
**el mercado te avisa a ti**.

Encaja con la lógica del negocio mejor que barrer: no tienes stock ni capital
inmovilizado, así que **tu único activo es llegar antes**. Una alerta es llegar antes;
un barrido mensual es llegar tarde y barato.

**Y se puede automatizar por nuestro lado:** una tarea programada semanal que corra la
criba barata sobre los modelos del encargo permanente y escriba en el registro. Sin
que tengas que pedirlo. Con eso el índice de rotación se construye solo, que es lo que
falta para el factor 1.

---

# 3 · Los dos bucles que están abiertos

## 3.1 · El registro de demanda no existe

La skill lo describe bien y **no hay ningún fichero**. Cada persona que pregunta y no
compra es información que se tira: qué modelo quería, qué presupuesto, por qué no cerró.

Es lo más barato de arreglar de toda la lista y lo que más vale a medio plazo: **con
veinte registros sabes qué buscar sin adivinar**, y el escenario C deja de ser
«a ver qué encuentro» para ser «tengo tres personas esperando esto».

Cuatro campos bastan: quién, qué quería, presupuesto (y si es del coche o todo
incluido), y por qué no cerró.

## 3.2 · El contenido no se mide

«El contenido es el negocio», dice la skill. Pero no hay forma de saber qué publicación
trajo preguntas. Sin eso, el factor 3 —atractivo, 20 puntos— es opinión mía para siempre.

Basta con anotar por publicación: modelo, fecha, alcance y **preguntas recibidas**. A
los diez posts ya se ve qué tipo de coche mueve a tu audiencia, que **no tiene por qué
coincidir con lo que se vende bien en el mercado general**. Puede que a tu perfil le
funcionen los deportivos y a tu bolsillo los familiares. Conviene saberlo.

---

# 4 · El riesgo reputacional no tiene mitigación escrita

La skill dice, con razón, que el riesgo aquí no es financiero sino reputacional. Y luego
no dice qué hacer con él. Faltan cuatro cosas, y son de escribir una vez:

1. **Quién asume qué entre la compra y la entrega.** Si el coche llega con un golpe del
   transporte, ¿quién responde? El seguro de transporte, y hay que tenerlo por escrito.
2. **Qué se promete y qué no.** «Coche localizado y gestionado» no es «coche revisado
   por mí». Si no lo has visto en persona, tiene que estar dicho antes, no después.
3. **Señal y cancelación.** Si el cliente se echa atrás con el coche ya comprado en
   Alemania, ahí sí hay capital inmovilizado, que es justo lo que el modelo evita.
   **Sin señal, el riesgo financiero vuelve por la puerta de atrás.**
4. **Qué pasa si la ITV de importación falla.** Es el momento en que aparecen las
   sorpresas y hay que saber quién paga.

No es trabajo de análisis, es una página. Pero es la que evita el problema que la propia
skill identifica como el único grave.

---

# 5 · Detalles fiscales que faltan

- **Tageszulassung / Vorführwagen.** Matriculados un día para cuadrar objetivos de
  concesionario. Pueden caer en la regla de **6 meses o 6.000 km** y llevarse un 21 %
  de IVA español encima. La skill tiene la regla, pero no advierte de que **estos coches
  son justo los que la disparan**, y son los más atractivos por precio y km.
- **Coeficiente de autocaravanas.** Tabla distinta, 18 años. Si abrimos ese segmento,
  hay que meterla.
- **Recargo autonómico.** La skill dice que las comunidades pueden subir el tipo un 15 %.
  **Andalucía aplica o no aplica: hay que confirmarlo con el gestor y fijarlo**, porque
  entra en todas las cuentas.
- **Ex-flota y ex-renting alemán.** Suelen tener libro completo y muchos km, y precio
  bajo. No es mala señal automáticamente, pero cambia el discurso.

---

# 6 · Orden que propongo para esta segunda tanda

| # | Qué | Esfuerzo | Por qué |
|---|---|---|---|
| 1 | **Rareza medida** en lugar de la tabla fija de primas | Bajo | Corrige el factor 4 de verdad, sin depender de catálogos |
| 2 | **Registro de demanda** — crear el fichero y usarlo | Muy bajo | El bucle abierto más valioso |
| 3 | **Criba barata de camper y furgoneta** | Bajo | Dos peticiones cada uno, y el ticket alto es donde este negocio funciona |
| 4 | **Alertas en AutoUncle y mobile.de** de los modelos del encargo | Bajo | Dejar de ver solo las sobras |
| 5 | **Tarea programada semanal** que alimente el registro | Bajo | Construye la rotación sola |
| 6 | **Página de condiciones**: riesgo, señal, qué se promete | Bajo | Cubre el único riesgo grave del modelo |
| 7 | **Confirmar el recargo andaluz** con el gestor | Muy bajo | Entra en todas las cuentas |
| 8 | Medir el mercado camper español a fondo | Medio | Si confirma la asimetría, cambia el encargo permanente |

Del 1 al 3 son la tarde de mañana. El 4 y el 5 cambian cómo funciona el negocio, no solo
cómo mido.
