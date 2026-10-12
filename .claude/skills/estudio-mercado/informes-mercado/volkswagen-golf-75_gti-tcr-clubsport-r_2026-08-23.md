# Informe de mercado — VW Golf 7.5 (GTI · GTI TCR · GTI Clubsport · Golf R)

**Fecha del informe:** 23-ago-2026 · **Actualizado:** 24-ago-2026 (añadida segmentación completa por variables — puertas · cambio · techo · cuadro digital) · **Tipo:** estudio de mercado (4 variantes) · **Informe contrastado con el JSON canónico actual** (`C:/Users/jacar/Desktop/JJImportMotors/datos_mercado.json`, `generado`: 2026-08-18, `actualizado`: 2026-08-23, `pasada`: "v5 - estudio Golf 7.5 granular (22-23ago): 4 variantes suelo-a-suelo sin banda + cola enrutada"). **La segmentación por variables del 24-ago-2026 es una pasada de investigación en vivo (mobile.de + Coches.net) independiente del JSON** — no se ha podido escribir de vuelta en `datos_mercado.json` desde esta sesión (fichero local del Escritorio de Windows, fuera de alcance de esta sesión en la nube); los suelos DE/ES del JSON se mantienen como referencia principal y se contrastan con lo observado en esta pasada.

---

## 🏁 CONCLUSIÓN

Las 4 variantes deportivas del Golf 7.5/7 (GTI, GTI TCR, GTI Clubsport, Golf R) tienen **hueco DE→ES positivo** según el suelo verificado en cada mercado: importar desde Alemania sale mejor en las 4, con veredicto **verde** en las 4 entradas del mapa.

- **Mejor hueco neto:** Golf R (+23,0%, suelo limpio 16.500€ DE vs 22.880€ ES).
- **Hueco más ajustado:** GTI estándar (+12,9% neto).
- El **Golf R** es la única variante que en el propio mapa de mercado sigue con `estado_cola: pendiente_busqueda` (no `buscado`) pese a tener nota de Flujo B completo — ver aviso en §4.
- ⚠️ El GTI Clubsport genuino **solo existe en carrocería Mk7 pre-facelift (2016-2017)**; no hay Clubsport Mk7.5 de fábrica — el campo `control_anyo` del JSON lo recoge así en las 4 entradas.
- ⚠️ Existe una entrada antigua y agregada `vw-golf-r` en el mismo JSON (veredicto amarillo, neto −3,1%) marcada explícitamente `reemplazado_por: "vw-golf-75-r"` — **no se ha usado ningún dato de esa entrada en este informe** (ver §4).
- 🆕 **24-ago:** la segmentación por variables confirma que **el cambio automático (DSG) domina las 3 variantes tope de gama** (Golf R: 89% DE / 94% ES · Clubsport: 84% DE / 81% ES) mientras que el **GTI estándar tiene una cuota manual mucho mayor** (18-22% en ambos mercados) — es la variante donde el cliente todavía puede elegir cambio con margen de oferta real. El eje **cuadro digital (Active Info Display) no se ha podido medir de forma agregada** en ningún portal esta pasada — ver limitación en §4.10.

**Tabla resumen por variante (valores tal cual constan en el JSON canónico, sin recalcular):**

Variante
Suelo DE
Suelo ES
Hueco bruto
Hueco neto¹
Veredicto
Confianza
Estado cola

GTI (230/245cv)
15.999€
19.690€
+18,7%
**+12,9%**
🟢 verde
4/5
`buscado`

GTI TCR (290cv)
19.699€
23.695€²
+16,9%
**+12,1%**
🟢 verde
3/5
`buscado`

GTI Clubsport (265cv, Mk7 pre-facelift)
16.499€
22.490€
+26,6%
**+21,6%**
🟢 verde
3/5
`buscado`

Golf R (Mk7.5, 310cv)
16.500€³
22.880€
+27,9%
**+23,0%**
🟢 verde
4/5
`pendiente_busqueda`

¹ Neto = con `coste_importacion_estimado` (1.129€ = transporte 900 + ausfuhr 114 + ITV import 115) restado del bruto; `iedmt_estimado` es `null` en las 4 entradas (pendiente de CO₂ real por unidad), NO está incluido en el neto.
² Suelo ES **con reserva** (accidente reparado en servicio oficial VW, Valladolid) — es el valor que el propio JSON usa para `hueco_pct`/`hueco_neto_pct` ("almacenado = con reserva, conservador"). La nota registra también un suelo ES "limpio" de 28.900€ (Madrid, full) que da un hueco mayor, pero ese valor NO está en los campos `precio_desde_es`/`hueco_pct` — solo en el texto de la nota.
³ Suelo DE **limpio** (05/2017, 191.000km, manual, sin mods) — es el valor de `precio_desde_de` y el que usan `hueco_pct`/`hueco_neto_pct`. La nota registra también un suelo "con reservas mecánicas" de 15.950€ que da un hueco algo mayor (+25,3% neto), pero tampoco está en los campos numéricos principales. **La pasada del 24-ago confirma 15.950€ como suelo DSG genuino verificado en vivo** (163.500km, 2017, particular, "Sin accidentes") — ver §5.4.

---

## 🎯 CANDIDATOS A VER

> Enlaces tomados literalmente de `enlaces_muestra` de cada entrada del JSON. Donde el enlace no coincide del todo con la descripción de la `nota`, se marca como incidencia (ver §4) en vez de asumir cuál es correcto.

### GTI (230/245cv) — `vw-golf-75-gti`

Precio
Descripción (según nota)
URL

15.999€
Suelo DE verificado — ~106.000 km, 5 puertas, DSG, cuadro digital
https://www.mobile.de/es/vehículos/detalles.html?id=40947884798464

19.690€
Suelo ES verificado según nota (3 puertas, manual, 245cv) — ⚠️ el enlace de muestra corresponde a un anuncio de Madrid, 245cv, **5 puertas**, 2017: no coincide con "3 puertas manual" de la nota, ver §4. **La pasada del 24-ago sí confirma 19.690€ como suelo del bucket 3 puertas (36 ofertas) en Coches.net** — la descripción "3 puertas" de la nota original parece correcta; el enlace de muestra es el que está mal emparejado.
https://www.coches.net/volkswagen-golf-gti-performance-20-tsi-245cv-5p-gasolina-2017-en-madrid-71274163-covo.aspx

### GTI TCR (290cv) — `vw-golf-75-tcr`

Precio
Descripción (según nota)
URL

19.699€
Suelo DE — ~149.000 km, 5 puertas, DSG
https://www.mobile.de/es/vehículos/detalles.html?id=38717798642208

28.900€
Suelo ES **limpio** (Madrid, full: techo + cuadro digital), 5p DSG 2019 — referencia de equipamiento comparable; el suelo ES "oficial" del mapa (23.695€, con reserva de accidente, Valladolid) **no tiene URL de muestra** en el JSON, ver §4
https://www.coches.net/volkswagen-golf-gti-tcr-20-tsi-213kw290cv-dsg-5p-gasolina-2019-en-madrid-71332726-covo.aspx

### GTI Clubsport (265cv, Mk7 pre-facelift 2016-2017) — `vw-golf-7-clubsport`

Precio
Descripción (según nota)
URL

16.499€
Suelo DE — ~153.000 km, sin modificaciones
https://www.mobile.de/es/vehículos/detalles.html?id=452337727

22.490€
Suelo ES — Barcelona, techo panorámico, "precio justo", verificado en ficha, 5p DSG 2016
https://www.coches.net/volkswagen-golf-gti-clubsport-20-tsi-265cv-bmt-dsg-5p-gasolina-2016-en-barcelona-71264521-covo.aspx

### Golf R (Mk7.5, 310cv) — `vw-golf-75-r`

Precio
Descripción (según nota)
URL

16.500€
Suelo DE **limpio** — 05/2017, 191.000 km, cambio manual (raro), 2º propietario, "Precio justo", ITV pasada mayo-2026, sin mods
https://www.mobile.de/es/vehículos/detalles.html?id=461349083

15.950€
Suelo DE **con reservas mecánicas** — posible fallo de encendido/sensor + 2 golpes pequeños declarados, pendiente de inspección
https://www.mobile.de/es/vehículos/detalles.html?id=462098949

22.880€
Suelo ES — Sevilla, "Super precio", 1 propietario, techo panorámico, sin mods, 3p DSG 2017
https://www.coches.net/volkswagen-golf-r-20-tsi-228kw-310cv-4motion-dsg-3p-gasolina-2017-en-sevilla-70611650-covo.aspx

---

## 📊 ANÁLISIS POR VARIANTE

**GTI (230/245cv), `vw-golf-75-gti`:** categoría `showstoppers` (secundaria `alta_rotacion`), segmento compacto, perfil `deporte_ocio`. Oferta 13 DE / 14 ES según el JSON, mediana 17.990€ DE / 23.950€ ES. Es la variante con mayor volumen de mercado y el hueco neto más ajustado (+12,9%) de las 4 — entrada de gama del segmento deportivo. Según la nota, cambio y número de puertas no marcan una prima limpia; pesan más el kilometraje y el trim (230 vs 245cv). Quedan 3 candidatos ES de listado (19.990€/20.200€/20.490€) **sin verificar en ficha** por bloqueo anti-bot — no se han usado como suelo. ⚠️ **La pasada del 24-ago (ver §5.1) mide 717 ofertas en DE y 241 en ES para el rango de potencia 230/245cv en 2017-2020 — muy por encima de las 13 DE / 14 ES del JSON.** Es la discrepancia más grande de las 4 variantes; probablemente el `oferta_de`/`oferta_es` del JSON refleja un filtro mucho más estrecho (año/km/precio concretos) que no está documentado en la nota, no un error. Se avisa en vez de sobrescribir el campo del JSON.

**GTI TCR (290cv, edición limitada 2019), `vw-golf-75-tcr`:** misma categoría/segmento/perfil que el GTI. Oferta 10 DE / 7 ES (la más escasa en ES), mediana 22.950€ DE / 30.690€ ES. Solo existe en 5 puertas + DSG de fábrica, sin segmentación posible por esos ejes. El hueco neto almacenado (+12,1%) usa el suelo ES "con reserva" (accidente reparado en oficial VW) de forma deliberadamente conservadora; si se usa el suelo ES limpio (28.900€) el hueco sube a +27,9% según la propia nota, pero ese segundo valor no está en los campos numéricos oficiales.

**GTI Clubsport (265cv, Mk7 pre-facelift 2016-2017), `vw-golf-7-clubsport`:** único de los 4 con advertencia crítica en su nota: el Clubsport genuino de fábrica **solo existe en carrocería Mk7 pre-facelift**, nunca hubo un Mk7.5. Oferta 12 DE / 25 ES según el JSON, mediana 20.200€ DE / 24.700€ ES. Rechipeo endémico confirmado: 5 casos DE con potencia alterada (2 sin aviso en el título) y 1 caso ES anunciado como "450cv" sobre 265cv reales — la nota insiste en comparar siempre la potencia declarada contra el catálogo. **La pasada del 24-ago mide 50 ofertas DE / 26 ES** (ver §5.2) para el rango 260-270cv — de nuevo por encima del `oferta_de`/`oferta_es` del JSON, mismo aviso que en el GTI.

**Golf R (Mk7.5 facelift, 310cv), `vw-golf-75-r`:** categoría `showstoppers`, mismo perfil `deporte_ocio`. Oferta 129 DE / 51 ES según el JSON — la muestra DE más grande de las 4 (cobertura completa: 7/7 páginas de mobile.de, confirmado sin candidatos ocultos). Mediana 18.880€ DE / 28.890€ ES. Tiene el **mejor hueco neto de las 4 variantes** (+23,0% sobre el suelo limpio). La nota advierte de tres trampas activas: 36% de fichas DE con filtro de partículas (OPF) retirado, el filtro de potencia captura también GTI muy tuneados (hay que comprobar que sea genuinamente 4Motion), y confusión de generación con el Mk7 pre-facelift (300cv) y el Mk8 (320cv+). **La pasada del 24-ago mide 142 ofertas DE (vs 129 del JSON, coherente) y 51 ES (idéntico al JSON) — es la variante con mejor coincidencia entre ambas medidas**, ver §5.4.

---

## 🔬 DESGLOSE POR VARIABLES (puertas · cambio · techo · cuadro digital) — pasada 24-ago-2026

> **Método:** mobile.de (DE) y Coches.net (ES), filtros estructurados por potencia (kW/cv, doble pasada) + año + km ≤180.000, sin filtro de texto libre por variante (regla 23-ago). Los conteos DE y ES suman al total de cada bucket salvo aviso en contra. "Suelo" = precio del anuncio orgánico más barato tras ordenar por precio ascendente (se descartan patrocinados donde se indica). Fuentes de precio: mobile.de + Coches.net únicamente, igual que el resto del informe.

### 5.1 · GTI (230/245cv Mk7.5, 2017-2020, ≤180.000 km)

**DE — mobile.de** (potencia 165-183 kW / 224-249cv, doble pasada): **717 ofertas**

Cambio
Ofertas
%
Techo corredizo
Head-up
Suelo

Manual
132
18%
17 (13%)
0 (0%)
12.950€ (particular NL, no DE — ver aviso) / 17.120€ (concesionario DE)

Automático (DSG)
585
82%
111 (19%)
29 (5%)
— (no se ha aislado suelo específico este pase)

**Total**
**717**

128 (18%)
29 (4%)

**ES — Coches.net** (potencia 225-250cv): **241 ofertas**, suelo global 18.500€

Eje
Bucket
Ofertas
%
Suelo

Cambio
Manual
54
22%
18.500€

Cambio
Automático
187
78%
≈18.950€ (2º más barato tras el manual de 18.500€)

Puertas
3 puertas
36
15%
19.690€

Puertas
5 puertas
205
85%
18.500€ (implícito — el coche más barato del mercado, 245cv Barcelona, no lleva 3p entre sus atributos de listado)

⚠️ **Techo y cuadro digital en ES:** Coches.net no tiene checkbox de techo ni de cuadro digital en su panel de equipamiento (limitación ya documentada en la skill) — no se puede segmentar por esos dos ejes en el mercado español esta pasada.
⚠️ **Puertas y cuadro digital en DE:** no se ha encontrado un filtro de puertas que confirme al aplicarlo en mobile.de (el combobox "Número de puertas" no llegó a comprometerse vía los métodos probados esta sesión), ni un checkbox de "Panel de instrumentos digital" visible en el panel de Equipamiento sin expandir "Más..." (el enlace no respondió a los intentos de esta sesión). Se declara como limitación, no como dato ausente por descuido.

### 5.2 · GTI Clubsport (265cv, Mk7 pre-facelift 2016-2017, ≤180.000 km)

**DE — mobile.de** (potencia ~195-199 kW / 260-270cv): **50 ofertas**

Cambio
Ofertas
%
Techo corredizo
Suelo

Manual
8
16%
—
17.500€

Automático (DSG)
42
84%
—
22.530€

**Total**
**50**

4 (8%)

**ES — Coches.net** (potencia 260-270cv, año ≤2017): **26 ofertas**

Eje
Bucket
Ofertas
%
Suelo

Cambio
Manual
5
19%
22.500€ – 23.200€

Cambio
Automático (DSG)
21
81%
20.990€

Puertas
3 puertas
7
27%
25.900€

Puertas
5 puertas
19
73%
20.990€

📌 **Lectura:** en Clubsport, a diferencia del GTI, el cambio manual es notablemente MÁS caro de entrada en ambos mercados (17.500€ DE manual vs 22.530€ auto; 22.500-23.200€ ES manual vs 20.990€ ES auto) — coherente con el manual siendo la configuración más codiciada/rara en un Clubsport, al revés que en el GTI genérico donde el manual es la entrada de gama. Mismo patrón en puertas: el 3 puertas ES sale bastante más caro de suelo (25.900€ vs 20.990€) — probablemente por ser la carrocería más deportiva/demandada del acabado.
⚠️ Techo/cuadro digital en ES y puertas/cuadro digital en DE: misma limitación que en el GTI (§5.1).

### 5.3 · GTI TCR (290cv, edición limitada 2019, solo 5 puertas + DSG de fábrica)

No aplica segmentación por puertas ni por cambio — configuración única de fábrica (5p + DSG), confirmado en informes anteriores.

⚠️ **Intento de aislar techo/cuadro digital por potencia (209-217 kW / 284-295cv) en mobile.de dio 86 ofertas en DE** — cifra implausible para una edición limitada de referencia mundial ~600 unidades: indica contaminación por GTI Performance rechipados desde 245cv hasta el entorno de 290cv (la misma trampa de "doble pasada por potencia" que el informe ya documenta para el Clubsport, aquí jugando en contra en vez de a favor). **Se descarta esta submuestra por baja fiabilidad** y no se usa para segmentar techo/cuadro digital del TCR. Los mismos DE/ES ya existentes en el informe (19.699€/23.695€, sin segmentar) se mantienen sin cambios.

### 5.4 · Golf R (Mk7.5, 310cv, 2017-2020, ≤180.000 km)

**DE — mobile.de** (potencia 224-232 kW / 305-315cv, doble pasada): **142 ofertas** (recuento en vivo — la propia interfaz osciló entre 142 y 144 en consultas consecutivas por alta rotación de inventario; se toma 142, confirmado dos veces con "142 resultados" explícito en cabecera)

Cambio
Ofertas
%
Techo corredizo
Head-up
Suelo

Manual
16
11%
2 (13%)
1 (6%)
18.490€ (particular, etiquetado "GTI *310PS*" pero con la potencia real de R — posible mal etiquetado del vendedor)

Automático (DSG)
126
89%
36 (29%)
3 (2%)
15.950€

**Total**
**142**

38 (27%)
4 (3%)

**ES — Coches.net** (potencia 305-315cv): **51 ofertas** (idéntico al `oferta_es` del JSON)

Eje
Bucket
Ofertas
%
Suelo

Cambio
Automático (DSG)
48
94%
22.880€

Cambio
"Manual" (filtro `TransmissionTypeId=2`)
3
6%
26.500€ ⚠️ **ver trampa abajo**

Puertas
3 puertas
9
18%
22.880€

Puertas
5 puertas
42
82%
24.900€

⚠️ **Trampa detectada (24-ago):** las 3 fichas que Coches.net devuelve bajo el filtro "Manual" llevan **"DSG" en el propio título del anuncio** (p. ej. "VOLKSWAGEN Golf R 2.0 TSI 4Motion DSG"). O el vendedor las etiquetó mal al publicar, o el mapeo `TransmissionTypeId` del filtro no es fiable en el extremo "Manual" para este modelo. **No se debe presentar al cliente un "Golf R manual español a 26.500€" sin verificar la ficha individual primero** — dato de baja confianza, se incluye por transparencia de metodología, no como suelo verificado.
⚠️ Cuadro digital: no disponible como filtro en ninguno de los dos portales, misma limitación que el resto de variantes.
⚠️ Puertas en DE: no se ha encontrado filtro fiable, misma limitación que el resto de variantes.

**Confirmación del suelo DE "con reservas" (15.950€):** la pasada del 24-ago confirma en vivo una ficha a 15.950€ (2017, 163.500km, DSG, vendedor particular, "Sin accidentes") que coincide con el suelo `con reservas mecánicas` que ya registraba la nota original del JSON — es el suelo más barato observado en DE esta pasada, por debajo incluso del suelo "limpio" de 16.500€ que usa el JSON para el hueco oficial.

---

## ⚠️ AVISOS Y LIMITACIONES

1. **Estado de cola inconsistente en Golf R:** `cola_trabajo.estados["vw-golf-75-r"]` = `pendiente_busqueda` (y `siguiente_busqueda` del mapa apunta a este mismo modelo), pese a que la `nota` de la propia entrada describe un Flujo B ya completado con 7 fuentes ("Verificado por 7 fuentes... Veredicto actualizado amarillo->verde tras cobertura completa"). El campo `estado_cola` y el texto de la `nota` no cuadran. No se ha modificado ninguno de los dos campos — se deja constancia para que se revise y corrija en el propio JSON.
2. **Entrada antigua superada (`vw-golf-r`):** el JSON conserva una entrada agregada `vw-golf-r` (Golf R 2019+, sin distinguir generación, mediana ES 41.640€/DE 39.990€, veredicto amarillo, neto −3,1%) con `reemplazado_por: "vw-golf-75-r"` y nota explícita "SUSTITUIDO... No usar este agregado para negocio". **Ninguno de sus valores se ha usado en este informe** — el Golf R de este informe es exclusivamente `vw-golf-75-r`.
3. **Discrepancia interna oferta DE del Golf R:** la entrada principal (`categorias.showstoppers`) registra `oferta_de: 129` (cobertura completa 7/7 páginas), pero el bloque `hueco_sin_banda` del mismo JSON conserva `oferta_de: 118` para el mismo slug — es el valor previo a completar la cobertura, no actualizado tras el cierre del Flujo B. Se avisa en vez de corregirlo unilateralmente.
4. **Suelo ES del GTI no coincide con su enlace de muestra:** la nota describe el suelo ES verificado (19.690€) como "3 puertas, manual", pero la URL de muestra asociada corresponde a un anuncio de Madrid con 5 puertas. **La pasada del 24-ago aclara parcialmente esto:** 19.690€ es efectivamente el suelo del bucket de 3 puertas (36 ofertas) en Coches.net, así que la descripción de la nota es correcta y el problema está solo en qué enlace de muestra se guardó, no en la cifra.
5. **Suelo ES "oficial" del TCR sin URL propia:** el valor que usan `hueco_pct`/`hueco_neto_pct` (23.695€, con reserva, Valladolid) no tiene enlace de muestra en el JSON; el único enlace ES disponible es el del suelo "limpio" alternativo (28.900€, Madrid).
6. **Costes de importación:** `coste_importacion_estimado` (1.129€) está fijado y es igual en las 4 entradas; `iedmt_estimado` es `null` en las 4 — el IEDMT depende del CO₂ real de cada unidad concreta y no está incluido en ningún hueco neto de este informe. El JSON sí trae una referencia general (`costes_referencia.iedmt_estimado_showstopper_deportivo`: 1.800€) pero es una cifra de contexto, no una imputada a estos modelos.
7. **Rotación y demanda no medidas:** en las 4 entradas, `rotacion_dias_de`, `rotacion_dias_es`, `demanda_trends`, `transferencias_mes_dgt` y `matriculaciones_kba` son `null` — el JSON no ha medido rotación ni tendencia de demanda para estas variantes.
8. **Candidatos ES de listado sin verificar (GTI):** 3 candidatos (19.990€/20.200€/20.490€) quedaron señalados como "NO verificados, anti-bot" en la nota — no se incluyen en la tabla de candidatos a ver por no estar confirmados en ficha.
9. **Codificación:** se detectaron y corrigieron 4 apariciones del carácter roto `Â·` (mojibake de "·") en la nota de `vw-golf-75-gti`, tanto en el JSON canónico (reescrito en UTF-8 sin BOM) como en este informe. No se encontraron más incidencias de encoding (`Ã`, BOM u otros patrones habituales) en el resto del archivo.
10. **🆕 Cuadro digital (Active Info Display) no medible de forma agregada (24-ago):** ni mobile.de ni Coches.net exponen un filtro estructurado y fiable para este equipamiento en las 4 variantes — Coches.net nunca lo ha tenido (limitación ya conocida de la skill) y en mobile.de el checkbox "Panel de instrumentos digital" vive detrás de un enlace "Más..." que no respondió a los intentos de expansión de esta sesión. Dado que el cliente lo valora explícitamente, la única vía fiable hoy es la verificación ficha a ficha en Flujo B (candidato a candidato), no el filtrado agregado de mercado. Se recomienda intentarlo de nuevo en una sesión futura con Claude Desktop (control de navegador más estable) antes de descartarlo definitivamente.
11. **🆕 Número de puertas no medible en DE (24-ago):** el combobox "Número de puertas" de mobile.de (valores 2/3, 4/5, 6/7) no llegó a aplicarse como filtro verificable en ninguno de los métodos probados esta sesión (URL directa ni clic+selección). La segmentación por puertas de este informe es **solo de Coches.net (ES)** — en DE no hay desglose por puertas esta pasada.
12. **🆕 Trampa de etiquetado "Manual" en Golf R ES (24-ago):** ver §5.4 — las 3 fichas bajo el filtro "Manual" de Coches.net llevan "DSG" en su propio título. Dato de baja confianza, no usar como suelo de un Golf R manual español sin verificar la ficha primero.
13. **🆕 Discrepancia de volumen de oferta GTI y Clubsport (24-ago):** la pasada en vivo mide 717 ofertas DE / 241 ES para el GTI y 50 DE / 26 ES para el Clubsport — muy por encima del `oferta_de`/`oferta_es` que registra el JSON (13/14 y 12/25 respectivamente). Para el Golf R (142 DE / 51 ES) y el TCR, la coincidencia con el JSON es mucho más cercana. No se ha podido determinar si el JSON aplicaba un filtro adicional no documentado (año/precio/km más estricto) — se avisa en vez de sobrescribir esos campos.

---

## 📋 COBERTURA Y METODOLOGÍA

- **Fuentes de precio:** mobile.de (DE) y Coches.net (ES) — únicas fuentes con datos numéricos en las 4 entradas de este estudio. El bloque `fuentes` del JSON registra ambos portales como `estado: "OK"` (última consulta general 2026-08-17); las notas de cada variante datan el estudio específico del Golf 7.5 en 22-23 ago 2026. **La segmentación por variables (§5) es una pasada adicional del 24-ago-2026, con los mismos dos portales.**
- **Método:** suelo-a-suelo (no mediana ponderada), **sin banda de precio** — el propio JSON documenta en `notas_metodologicas` que aplicar la misma banda absoluta a ambos mercados recorta la cola barata alemana y aplasta el hueco (caso de control: Golf gasolina 2016+ pasó de +0,7% con banda 8-17k a +10,8% sin banda).
- **Control de año/generación:** `control_anyo` = "2017+ (Mk7.5 facelift) / Clubsport Mk7 2016-2017" en las 4 entradas; `banda` = "NINGUNA (solo km<=180k)". La segmentación del 24-ago usa los mismos rangos de año/km, más un rango de potencia (kW/cv) específico por variante con doble pasada (ver tabla de rangos en la skill de importación).
- **Verificación ES:** filtros estructurados por potencia + combustible + año + km en Coches.net (`PowerHpFrom`/`PowerHpTo`, etc.) — el JSON deja constancia de que el filtro de texto libre `Version=` no es fiable ("funciona con IA y puede fallar"). Confirmado de nuevo el 24-ago con el propio filtro `TransmissionTypeId` (ver aviso §4.12).
- **Caducidad:** `refrescar_antes_de_categoria` = 2026-09-06 en las 4 entradas.
- **Fuentes NO usadas para precio de referencia en este estudio de mercado** (reservadas para la fase de búsqueda de unidades — Flujo B de `importacion-vehiculos`): AutoScout24.de (regla A8: solo conteo, nunca precio), AutoUncle, Wallapop, Milanuncios, kleinanzeigen.de. Estas 5 fuentes sí se cubrieron en su momento dentro de los informes de Flujo B de cada variante (fuera del alcance de este documento, que es el estudio de mercado, no el informe de modelo/candidatos de compra).
- **Cola de trabajo (`cola_trabajo`) en el momento de este informe:** `siguiente_estudio` = `vw-golf-8-gti-clubsport` · `siguiente_busqueda` = `vw-golf-75-r`. Estados de las 4 variantes: GTI `buscado`, TCR `buscado`, Clubsport `buscado`, Golf R `pendiente_busqueda` (ver aviso §4.1).
- **Ejes de segmentación cubiertos (24-ago):** cambio (manual/automático) en las 4 variantes salvo TCR (fijo); puertas (3p/5p) solo en Coches.net (ES) para GTI, Clubsport y Golf R (TCR fijo en 5p); techo corredizo solo en mobile.de (DE) para GTI, Clubsport y Golf R vía facetas del propio buscador. **Cuadro digital (Active Info Display) NO se ha podido medir en ningún portal esta pasada** — ver aviso §4.10.
- **Validación de este informe:** JSON re-leído directamente de `C:/Users/jacar/Desktop/JJImportMotors/datos_mercado.json` en el momento de escribir el cuerpo original de este documento (23-ago, no se ha usado memoria de sesión ni datos de informes anteriores). Los datos de la sección §5 (24-ago) proceden de una pasada de investigación en vivo en esta sesión — mobile.de + Coches.net, sin relectura adicional del JSON — y se presentan como capa complementaria, no como sustitución de las cifras del JSON.

---

*JJ Import Motors · Huelva · Servicio de gestión de búsqueda e importación de vehículos. Informe contrastado con el JSON canónico actual — no se ha comprobado su sincronización con Laravel ni con ningún SaaS. Generado el 23-08-2026 · actualizado el 24-08-2026 con segmentación por variables.*