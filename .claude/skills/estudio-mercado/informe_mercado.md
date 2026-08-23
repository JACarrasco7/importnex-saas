# 📄 Plantilla de informe de mercado — estudio-mercado (23-ago-2026 v0.3.8)

> **Para quién es este informe:** para **ti** (Jacar). Lenguaje de negocio, sin jerga técnica. El objetivo es que en 1 minuto sepas: **¿cuál de estos coches merece la pena importar? ¿cuál es su precio real en Alemania y en España? ¿cuánto me costaría puesto en Huelva?**.
>
> **Regla de ORO (23-ago-2026 v0.3.8): la nube NO decide nada por su cuenta.** Cada decisión que pueda tomarse de dos formas está resuelta aquí con un SI/ENTONCES explícito. Si una situación no está contemplada, se PARA y pregunta. NO improvisa.

---

## 🚦 REGLAS ESTRICTAS SI/ENTONCES (la nube NO improvisa)

> Estas reglas son **condicionales explícitas**. La nube las aplica en este orden y **NO se sale de ellas**:

| Situación | Comportamiento OBLIGATORIO |
|---|---|
| Hay que decidir si incluir el desglose por variables | **SIEMPRE incluirlo.** Si no hay muestra suficiente (>2 anuncios por combinación) → poner 1 línea "No hay muestra suficiente para segmentar" y omitir la tabla. NO decidir "es opcional". |
| Hay que decidir si incluir comparables | **SIEMPRE incluir al menos 1 comparable** de modelos ya estudiados (ver `modelos-medidos.md`). Si no hay ninguno, poner 1 línea "Sin comparables todavía" y seguir. |
| Hay que decidir el formato del archivo | **SIEMPRE 1 único Markdown** (`<marca>-<modelo>_<YYYY-MM-DD>.md`). NO PDF, NO duplicados, NO varios formatos a la vez. |
| Hay que decidir los gastos fijos | **Por defecto 1.500 €** (1.000 € transporte + 200 € ITV + 300 € gestoría/ausfuhr). SOLO cambiar si el usuario dice explícitamente "mis gastos son X €". NO asumir, NO preguntar. |
| Hay que decidir el IVA de importación / IEDMT | **NO incluir en las tablas.** SOLO mencionar en la sección "💶 Desglose de los 1.500 €" con el orden de magnitud realista (+3.500 a +5.500 € todo incluido). NO calcular por coche. |
| Hay que decidir si el suelo es fiable | Marcar ✅ (verificado en ficha) · 👁️ (solo listado) · ⚠️ (con reserva/siniestro/financiado). NUNCA sin marca. |
| Hay cobertura incompleta (ej. "solo página 1/6") | **DECLARARLO siempre** en §COBERTURA con número exacto ("se han revisado X de Y anuncios"). NO omitir. |
| Hay datos contradictorios entre el mapa y el informe | **PREVALECE el `datos_mercado.json`** (fuente de verdad). NO mezclar datos recordados de sesiones pasadas. |
| El usuario no ha pedido algo que la plantilla obliga | **SE HACE IGUAL** (desglose, comparables, resumen para copiar, sección de gastos). NO preguntar "¿lo quieres?". |
| El usuario pide algo NO contemplado en la plantilla | **HACERLO**, pero añadir 1 línea al final: "He añadido [X] porque me lo has pedido explícitamente." |

---

## ✅ CHECKLIST OBLIGATORIO ANTES DE ENTREGAR (la nube lo rellena y lo muestra)

> **Antes de dar el informe por terminado**, la nube escribe este bloque al final (con ✅/❌ en cada línea). NO entregar si hay algún ❌ sin resolver.

```
✅ Check — Estructura completa:
  ✅ §CONCLUSIÓN con párrafo + tabla resumen
  ✅ §CANDIDATOS con 1-2 por versión + URL visible
  ✅ §DESGLOSE POR VARIABLES (o "no hay muestra" justificado)
  ✅ §COMPARABLES (al menos 1)
  ✅ §TRAMPAS (al menos 1, si las hay)
  ✅ §RESUMEN PARA COPIAR (1 párrafo)
  ✅ §ARCHIVO GENERADO
  ✅ §DESGLOSE 1.500 € GASTOS
  ✅ §COBERTURA Y METODOLOGÍA (al final)

✅ Check — Datos consistentes:
  ✅ Suelos DE/ES con marca de fiabilidad (✅/👁️/⚠️)
  ✅ Columna "Puesto en Huelva" = suelo DE + 1.500 €
  ✅ Columna "Ahorro real" = suelo ES − puesto Huelva
  ✅ URLs completas y visibles (no "ver [enlace]")
  ✅ Sin jerga IA (sincronizado, merge, volcado, fuente_medicion)
  ✅ Cobertura incompleta declarada con números

✅ Check — Archivos:
  ✅ UN solo .md, sin duplicados
  ✅ Nombre: <marca>-<modelo>_<YYYY-MM-DD>.md
  ✅ Sin PDF generado
```

---

## 📐 ESTRUCTURA OBLIGATORIA DEL INFORME (orden fijo)

> Las secciones van **exactamente en este orden**. NO reordenar. NO omitir.

| # | Sección | Obligatoria | Si falta muestra |
|---|---|:---:|---|
| 1 | 🏁 CONCLUSIÓN (párrafo + tabla resumen) | ✅ | — |
| 2 | 🎯 LOS 2 MEJORES ANUNCIOS POR VERSIÓN | ✅ | — |
| 3 | 📊 DESGLOSE POR VARIABLES | ✅ | Poner 1 línea y omitir tablas |
| 4 | 🧩 COMPARABLES | ✅ | Poner "Sin comparables todavía" |
| 5 | ⚠️ TRAMPAS | ✅ si hay | Poner "Sin trampas detectadas" |
| 6 | 📋 RESUMEN PARA COPIAR | ✅ | — |
| 7 | 💶 DESGLOSE 1.500 € GASTOS | ✅ | — |
| 8 | 📁 ARCHIVO GENERADO | ✅ | — |
| 9 | 📋 COBERTURA Y METODOLOGÍA | ✅ | — |
| 10 | ✅ CHECKLIST (auto-verificación) | ✅ | — |

Guardar como: `informes\mercado\<marca>-<modelo>_<YYYY-MM-DD>.md`

---

## 🏁 CONCLUSIÓN — para decidir en 1 minuto

> Primer párrafo: 4-6 líneas. Qué se estudió, veredicto general, mejor versión, si conviene importar y por qué. Lenguaje claro, sin jerga.

**VW Golf 7.5 (GTI · TCR · Clubsport · R)** — estudio del 23 de agosto de 2026.

- Las 4 versiones tienen **precio real más barato en Alemania**: traído sale entre 1.200 € y 4.000 € más barato que en España una vez descontados los gastos (transporte, ITV, gestoría).
- **La mejor oportunidad clara** es el Golf R (310cv): 4.000 € de ahorro neto comprando en Alemania.
- La más floja es el GTI estándar: solo 1.200 € de ahorro (mismo coche que ya se vende en España barato).
- **Trampa importante:** el Clubsport NO existe en Golf 7.5 (es Mk7, 2016-2017). Si ves Clubsport etiquetado como 7.5 es un error.
- **Trampa importante:** el 36% de los Golf R alemanes llevan "stage 1" silencioso (escape + centralita). Todo anuncio por debajo de 17.000 € en DE hay que verificarlo ficha a ficha.

### Tabla resumen — cuánto ahorras trayéndolo desde Alemania

> Columnas: Versión · Precio más bajo en Alemania · Precio puesto en Huelva (DE + gastos) · Precio más bajo en España · Ahorro real frente a España · ¿Conviene?

| Versión | Suelo Alemania | **Puesto en Huelva¹** | Suelo España | Ahorro real² | ¿Conviene? |
|---|---:|---:|---:|---:|:---:|
| GTI (230/245cv) | 15.999 € | **17.499 €** | 19.690 € | **2.191 €** | ✅ sí |
| GTI TCR (290cv) | 19.699 € | **21.199 €** | 28.900 €³ | **7.701 €** | ✅ **muy bien** |
| GTI Clubsport (265cv, **Mk7**) | 16.499 € | **17.999 €** | 22.490 € | **4.491 €** | ⚠️ ojo, no es 7.5 |
| Golf R (310cv) | 16.899 €⁴ | **18.399 €** | 22.880 € | **4.481 €** | ✅ **el mejor** |

¹ **Puesto en Huelva = precio Alemania + 1.500 € de gastos fijos** (1.000 € transporte + 200 € ITV + 300 € gestoría/ausfuhr). IVA de importación aparte, se calcula para cada coche según su CO₂ y año.
² **Ahorro real = suelo España − puesto en Huelva** (lo que te ahorras de verdad vs comprar en ES).
³ Suelo con accidente reparado en taller oficial en 28.900 €. Los 23.695 € son "precio con reserva".
⁴ Suelo sin verificar motor (anuncio dice 310cv, no se abrió ficha). El suelo limpio verificado es 16.899 €.

> **Leyenda de fiabilidad** (junto a cada precio):
> - ✅ **Verificado** = se abrió la ficha y se confirmó el estado.
> - 👁️ **De listado** = solo se vio el precio/año/km del listado. Sin abrir ficha.
> - ⚠️ **Con reserva** = precio publicado, pero el anuncio menciona siniestro, km dudosos o financiación.

---

## 🎯 LOS 2 MEJORES ANUNCIOS POR VERSIÓN (para ver ahora)

> Solo los que pasan filtro: precio fiable, equipamiento acorde y vendedor decente. Click en el enlace → ves el anuncio original.
>
> **Columna "Puesto en Huelva"**: precio alemán + 1.500 € de gastos fijos estimados (1.000 € transporte + 200 € ITV + 300 € gestoría/ausfuhr). **IVA de importación aparte** (depende del CO₂ y año del coche, se calcula cuando hay unidad concreta).
>
> **Columna "Ahorro real"**: suelo España − puesto en Huelva. Es lo que te ahorras de verdad.

### GTI (230/245cv)

| Precio | Puesto Huelva | Año | Km | Por qué mola | Enlace |
|---:|---:|---|---:|---|---|
| 15.999 € | **17.499 €** | 2017 | 106.726 | Suelo Alemania, 5p DSG, cuadro digital, sin accidentes | https://www.mobile.de/es/vehículos/detalles.html?id=40947884798464 |
| 19.690 € | — | 2017 | 155.361 | Suelo España, 3p manual, 245cv Performance | https://www.coches.net/volkswagen-golf-gti-performance-20-tsi-245cv-5p-gasolina-2017-en-madrid-71274163-covo.aspx |

> Traer el GTI de Alemania te sale por **17.499 € puesto en Huelva** (sin IVA). El mismo coche en España cuesta **19.690 €** → te ahorras **2.191 €**.

### GTI TCR (290cv)

| Precio | Puesto Huelva | Año | Km | Por qué mola | Enlace |
|---:|---:|---|---:|---|---|
| 19.699 € | **21.199 €** | 2019 | 149.702 | Suelo Alemania, 5p DSG, completo | https://www.mobile.de/es/vehículos/detalles.html?id=38717798642208 |
| 28.900 € | — | 2019 | 117.949 | Suelo España limpio, con techo y cuadro digital | https://www.coches.net/volkswagen-golf-gti-tcr-20-tsi-213kw290cv-dsg-5p-gasolina-2019-en-madrid-71332726-covo.aspx |

> Traer el TCR te sale por **21.199 €** vs **28.900 €** en España → te ahorras **7.701 €**. El TCR tiene el hueco más grande de todos los Golf del estudio.

### GTI Clubsport (265cv) — **Mk7, no Mk7.5**

| Precio | Puesto Huelva | Año | Km | Por qué mola | Enlace |
|---:|---:|---|---:|---|---|
| 16.499 € | **17.999 €** | 2016 | 153.000 | Suelo Alemania original, sin tocar | https://www.mobile.de/es/vehículos/detalles.html?id=452337727 |
| 22.490 € | — | 2016 | 143.000 | Suelo España, techo solar, "precio justo" | https://www.coches.net/volkswagen-golf-gti-clubsport-20-tsi-265cv-bmt-dsg-5p-gasolina-2016-en-barcelona-71264521-covo.aspx |

> Traer el Clubsport Mk7 te sale por **17.999 €** vs **22.490 €** en España → te ahorras **4.491 €**. Ojo: Clubsport NO existe en Mk7.5 (si te ofrecen Clubsport 7.5 es un error).

### Golf R (310cv)

| Precio | Puesto Huelva | Año | Km | Por qué mola | Enlace |
|---:|---:|---|---:|---|---|
| 16.899 € | **18.399 €** | 2017 | 176.147 | Suelo Alemania limpio, 5p DSG | https://www.mobile.de/es/vehículos/detalles.html?id=461400725 |
| 22.880 € | — | 2017 | 130.000 | Suelo España "super precio", 3p DSG, techo | https://www.coches.net/volkswagen-golf-r-20-tsi-228kw-310cv-4motion-dsg-3p-gasolina-2017-en-sevilla-70611650-covo.aspx |

> Traer el Golf R te sale por **18.399 €** vs **22.880 €** en España → te ahorras **4.481 €**. El Golf R es el ganador claro en relación riesgo/beneficio.

---

## 📊 DESGLOSE POR VARIABLES — cuánto cambia el precio según equipamiento

> **Esta es la parte que casi siempre se salta la IA.** El mismo coche cambia mucho de precio según **puertas (3p/5p), cambio (manual/DSG), techo solar y cuadro digital**. Aquí se ve con números.
>
> **Cómo se lee:** cada variable se compara sola, manteniendo el resto fijo. Ejemplo: "el GTI 5p DSG es 800 € más barato que el 3p manual en Alemania", pero la DSG en España son +1.200 € más caras.
>
> **Si el usuario no pidió este desglose**, sigue saliendo igual: la segmentación siempre se incluye cuando hay muestra suficiente (>2 anuncios por combinación). Si el mercado no permite separarlo, se dice en 1 línea y se omite la tabla.

### GTI (230/245cv) — Alemania

| Combinación | Cuantos hay | Precio medio | Comentario |
|---|---:|---:|---|
| 3p · manual · sin techo | 4/14 | 16.450 € | El más barato, los caprichosos lo quieren |
| 3p · manual · techo solar | 1/14 | 17.900 € | Raro, +1.450 € |
| 5p · DSG · sin techo | 5/14 | 16.700 € | El típico familiar |
| 5p · DSG · techo solar | 1/14 | 18.200 € | Muy raro, +1.500 € |
| 5p · DSG · **cuadro digital** | 7/14 | 16.700 € | Cuadro digital NO sube el precio en DE (va incluido en el acabado) |

> **Resumen GTI Alemania:** puertas y cambio NO marcan precio claro. Solo el techo solar sube el precio (+1.500 €). El cuadro digital está incluido en cualquier acabado del 2017-2018.

### GTI (230/245cv) — España

| Combinación | Cuantos hay | Precio medio | Comentario |
|---|---:|---:|---|
| 3p · manual · sin techo | 1/3 | 19.690 € | Suelo de partida |
| 5p · DSG · sin techo | 1/3 | 21.990 € | +2.300 € sobre el 3p manual (España prima la practicidad) |
| 5p · DSG · techo + cuadro | 1/3 | 24.500 € | El full equipado en ES lleva ambos y sube 2.500 € |

> **Resumen GTI España:** la 5p DSG se paga +2.300 € sobre la 3p manual. El full sube +2.500 € sobre la misma base.

### GTI TCR (290cv)

> Solo existe 5p + DSG de fábrica → no se puede segmentar. Todos los precios los决定 km y estado. La media real (sin filtros) está en 27.900 € en ES vs 24.500 € en DE.

### GTI Clubsport (Mk7) — Alemania

| Variable | Cuantos hay | Prima |
|---|---:|---:|
| Manual (vs DSG) | 2/12 | **-0 €** (no cambia el precio) |
| Techo solar | 0/12 | No hay Clubsport con techo solar |
| Menos de 50.000 km | 2/12 | +7.000 € sobre la media |
| **Re-chipeado** (stage 1 / OPF quitado) | 5/12 | -2.500 € vs los limpios |

### Golf R (310cv) — Alemania

| Variable | Cuantos hay | Prima |
|---|---:|---:|
| Manual (vs DSG) | 2/14 | **+0 €** (los manuales no se penalizan) |
| Techo solar | 3/14 | +800 € |
| **Re-chipeado** (stage 1 / OPF fuera) | 5/14 | -2.000 € vs los limpios |
| Menos de 80.000 km | 4/14 | +3.000 € |

> **Resumen Golf R Alemania:** km y re-chipeo son los que más mueven el precio. Equipamiento (techo, cuadro digital) está incluido en la mayoría.

### Golf R (310cv) — España

| Variable | Cuantos hay | Prima |
|---|---:|---:|
| 3p (vs 5p) | 1/3 | **-0 €** (raro, pero no penaliza) |
| Techo solar | 1/3 | +1.200 € |
| Menos de 100.000 km | 1/3 | +1.500 € |

---

## 🧩 COMPARABLES — qué se parece y qué hemos estudiado antes

> Para que pongas el dato en contexto sin tener que abrir otro informe.

- **Astra J OPC (julio 2026):** 30% de hueco pero solo 8 anuncios en ES. El Golf R es mejor relación riesgo/beneficio: 22% de hueco con mercado 6 veces más grande.
- **Cupra León VZ (julio 2026):** 8% de hueco, mercado muy estrecho. Si dudas entre Cupra y Golf GTI, el GTI gana por goleada en disponibilidad DE.
- **BMW Serie 1 M Sport (agosto 2026):** 12% de hueco, pero equipamiento DE muy bajo (sin techo, sin cuadro). El Golf R DE viene más equipado de serie.
- **VW Golf 8 GTI (medido en modo "validar"):** suelo DE a 28.500 €, suelo ES a 31.900 €. El Mk7.5 sigue siendo mejor oportunidad que el Mk8 en importarlo.

---

## ⚠️ TRAMPAS QUE TE PUEDEN COSTAR PASTA

1. **Clubsport NO es Mk7.5.** El Clubsport original es Mk7 (2016-2017). Si te ofrecen Clubsport "7.5", es un Mk7 renombrado o un error.
2. **Re-chipeo silencioso en Golf R y Clubsport.** 5 de 14 anuncios DE con OPF quitado o centralita retocada SIN avisar en el título. Hay que leer la descripción y comparar potencia (310cv real vs 350+cv después de stage 1).
3. **"Precio financiado" ≠ precio de contado en España.** Detectados 3 casos donde el financiado era 2.000 € más barato. Usar siempre el contado de la ficha.
4. **Coche "en España" pero matriculado en Alemania.** Hay un TCR en Tarragona (24.999 €) que físicamente sigue en DE sin matricular → NO es suelo español real.
5. **Techo vinilado ≠ techo solar.** En un Clubsport ES detectamos vinilo que imita techo solar pero NO lo es. Verificar en foto.
6. **Cobertura incompleta del Golf R en Alemania.** Solo se revisó la página 1 de 6 (~95 anuncios sin ver). El hueco real del R podría ser MAYOR (más oferta baja encontrada).

---

## 📋 RESUMEN PARA COPIAR (1 párrafo, listo para pegar)

> Párrafo autocontenido. Lo que tú pondrías en un WhatsApp, en una nota o para enseñarle a un socio. Sin enlaces (se pierden al pegar en texto plano).
>
> Los precios "puesto en Huelva" incluyen 1.500 € de gastos fijos estimados (1.000 € transporte + 200 € ITV + 300 € gestoría/ausfuhr). **IVA de importación aparte** (se calcula para cada coche según CO₂ y año).

```
Estudio del VW Golf 7.5 hecho. Las 4 versiones (GTI 230/245cv, GTI TCR 290cv,
Clubsport Mk7 y Golf R 310cv) salen más baratas trayéndolas desde Alemania.

Precios "puesto en Huelva" (precio Alemania + 1.500 € de gastos, IVA aparte):

- GTI 230/245cv: puesto en Huelva 17.499 € vs 19.690 € en España → ahorras 2.191 €
- GTI TCR 290cv: puesto en Huelva 21.199 € vs 28.900 € en España → ahorras 7.701 €
- GTI Clubsport Mk7: puesto en Huelva 17.999 € vs 22.490 € en España → ahorras 4.491 €
- Golf R 310cv: puesto en Huelva 18.399 € vs 22.880 € en España → ahorras 4.481 €

El ganador absoluto en hueco es el GTI TCR (7.700 € de ahorro). El ganador en
relación riesgo/beneficio (mercado grande + hueco decente) es el Golf R.

2 advertencias importantes:
- Clubsport NO existe en 7.5 (es Mk7 de 2016-2017).
- 5 de cada 14 anuncios DE del Golf R llevan "stage 1" silencioso. Todo anuncio
  por debajo de 17.000 € en Alemania hay que verificarlo ficha a ficha.

Comparado con el Astra J OPC (julio, 30% de hueco pero solo 8 anuncios en ES),
el Golf R tiene menos hueco (22%) pero mercado 6 veces más grande → mejor
oportunidad real.

Siguiente paso: si te convence el Golf R o el TCR, mirar unidades concretas
en la próxima sesión.
```

---

## 📁 ARCHIVO GENERADO

```
informes/mercado/volKSwagen-golf-75_2026-08-23.md
```

(El mapa de mercado en `datos_mercado.json` lo actualiza Copilot cuando le digas
"importa este MD al mapa" — la nube no tiene acceso a tu disco.)

---

## 💶 DESGLOSE DE LOS 1.500 € DE GASTOS FIJOS (para que sepas qué incluye)

> **Por qué una cifra redonda de 1.500 € y no el desglose técnico:** el estudio de mercado mira precios de compra en Alemania vs España. Los gastos de traer un coche son **variables según el coche concreto** (CO₂, año, peso, provincia de matriculación). Para decidir si **merece la pena importar** un modelo basta con esa cifra redonda.
>
> Cuando elijas una unidad concreta (Flujo A), entonces sí se calcula el IVA de importación + IEDMT exacto, basado en ficha técnica.

| Concepto | Estimado | Notas |
|---|---:|---|
| Transporte Alemania → Huelva | 1.000 € | Camión cerrado, ≈7-10 días, depende de ruta |
| ITV + homologación | 200 € | Tarifa estándar, sube si hay reformas |
| Gestoría + ausfuhr | 300 € | Baja en Alemania + matriculación provisional |
| **TOTAL gastos fijos** | **1.500 €** | Sin IVA de importación |

**Lo que NO incluye (se calcula por coche en Flujo A):**
- **IVA de importación:** 21% sobre el valor en aduana (precio compra + transporte + seguro). Se puede deducir si el coche es para revender con margen, pero como particular se paga.
- **IEDMT (impuesto de matriculación):** depende del CO₂ y año del coche. Un GTI de 2017 con ≈170 g/km CO₂ paga alrededor de 700-1.000 €. Un TCR más potente, ≈1.500-2.000 €. Un Golf R, ≈1.800-2.500 €.
- **Seguro de tránsito** (≈100 €) — opcional.
- **Gestión de placas y matrícula española** (≈150 €).

**Orden de magnitud realista total:**
- Coche barato traerlo (sin IVA ni IEDMT): +1.500 €
- Coche "traído y matricular" todo incluido: +3.500 € a +5.500 € dependiendo del modelo

> Si quieres que el informe use una cifra distinta (ej. "suma 2.000 € porque mis gastos son más altos"), lo recalculo en 1 minuto.

---

## 📋 COBERTURA Y METODOLOGÍA (esto es solo si quieres saber cómo se midió)

- **Fuentes:** solo mobile.de (Alemania) + Coches.net (España). El resto de portales quedan para buscar unidades concretas.
- **Filtros comunes:** año 2017-2019 · km ≤ 180.000 · Volkswagen Golf 7.5 (excepto Clubsport, que es Mk7).
- **Cuántos anuncios se miraron:** ~14 por versión en Alemania, ~3 por versión en España.
- **Verificación de equipamiento:** filtros estructurados (potencia + combustible + año + km) en Coches.net. El filtro "Version=" texto libre NO funciona.
- **Cobertura incompleta del Golf R en Alemania:** solo página 1 de 6. Hace falta mirar las otras 5 antes de lanzar una campaña.
- **Tamaño de muestra:** 2-9 verificados por lado. Es una foto del mercado hoy, no una mediana robusta.
- **Pendiente:** cubrir las páginas 2-6 del Golf R en Alemania antes de ofertar basado en el suelo alemán.

---

## ✅ CHECKLIST — auto-verificación antes de entregar (obligatorio)

> La nube rellena este bloque al final del informe con ✅ en cada línea. NO entregar si hay algún ❌ sin resolver.

```
✅ Estructura completa:
  ✅ §CONCLUSIÓN con párrafo + tabla resumen
  ✅ §CANDIDATOS con 1-2 por versión + URL visible
  ✅ §DESGLOSE POR VARIABLES (o "no hay muestra" justificado)
  ✅ §COMPARABLES (al menos 1)
  ✅ §TRAMPAS (al menos 1 si las hay)
  ✅ §RESUMEN PARA COPIAR (1 párrafo)
  ✅ §ARCHIVO GENERADO
  ✅ §DESGLOSE 1.500 € GASTOS
  ✅ §COBERTURA Y METODOLOGÍA (al final)

✅ Datos consistentes:
  ✅ Suelos DE/ES con marca de fiabilidad (✅/👁️/⚠️)
  ✅ Columna "Puesto en Huelva" = suelo DE + 1.500 €
  ✅ Columna "Ahorro real" = suelo ES − puesto Huelva
  ✅ URLs completas y visibles (no "ver [enlace]")
  ✅ Sin jerga IA (sincronizado, merge, volcado, fuente_medicion)
  ✅ Cobertura incompleta declarada con números

✅ Archivos:
  ✅ UN solo .md, sin duplicados
  ✅ Nombre: <marca>-<modelo>_<YYYY-MM-DD>.md
  ✅ Sin PDF generado

✅ Mensaje de cierre:
  ✅ Última línea: "Archivo: informes/mercado/<archivo>.md. Pásale
     este MD a Copilot en VS Code y dile 'importa este MD al mapa'."
```
