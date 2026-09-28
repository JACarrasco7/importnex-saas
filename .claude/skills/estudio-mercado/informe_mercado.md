# 📄 Plantilla de informe de mercado — estudio-mercado (28-sep-2026 v0.5.0)

> **Para quién es este informe:** para **ti** (Jacar). Lenguaje de negocio, sin jerga técnica. Sondeo rápido del mercado: solo el número, el hueco y los enlaces para que **investigues tú** abriendo los listados.
>
> **Regla de ORO (23-ago-2026 v0.3.8): la nube NO decide nada por su cuenta.** Cada decisión que pueda tomarse de dos formas está resuelta aquí con un SI/ENTONCES explícito. Si una situación no está contemplada, se PARA y pregunta. NO improvisa.

---

## 🚦 REGLAS ESTRICTAS SI/ENTONCES (la nube NO improvisa)

> Estas reglas son **condicionales explícitas**. La nube las aplica en este orden y **NO se sale de ellas**:

| Situación | Comportamiento OBLIGATORIO |
|---|---|
| Hay que decidir si incluir el desglose por variables o los mejores anuncios | **NO se incluyen.** Desde v0.5.0 (28-sep-2026) el informe es un **sondeo**: nada de fichas de anuncios ni desglose por equipamiento. Si el usuario los pide expresamente, se añaden como anexo al final y se avisa en 1 línea. |
| Hay que decidir si incluir §FUENTES con conteo y fecha | **SIEMPRE.** Es la única sección imprescindible: sin ella el informe no sirve (el usuario no puede rehacer la búsqueda). |
| Hay que decidir si incluir comparables | **SIEMPRE incluir al menos 1 comparable** de modelos ya estudiados (ver `modelos-medidos.md`). Si no hay ninguno, poner 1 línea "Sin comparables todavía" y seguir. |
| Hay que decidir el formato del archivo | **SIEMPRE 1 único Markdown** (`<marca>_<segmento>_<enfoque>_<YYYY-MM-DD>.md`). NO PDF, NO duplicados, NO varios formatos a la vez. |
| Hay que decidir los gastos fijos (1.500 € vs otro) | **PREGUNTAR** al usuario al inicio (ver §CUÁNDO PREGUNTAR). NO asumir 1.500 € ni inventar otra cifra. |
| Hay que decidir el IVA de importación / IEDMT | **NO incluir en las tablas.** SOLO mencionar en la sección "💶 Desglose de los 1.500 €" con el orden de magnitud realista (+3.500 a +5.500 € todo incluido). NO calcular por coche. |
| Hay que decidir si el suelo es fiable | Marcar ✅ (verificado en ficha) · 👁️ (solo listado) · ⚠️ (con reserva/siniestro/financiado). NUNCA sin marca. |
| Hay cobertura incompleta (ej. "solo página 1/6") | **DECLARARLO siempre** en §COBERTURA con número exacto ("se han revisado X de Y anuncios"). NO omitir. |
| Hay datos contradictorios entre el mapa y el informe | **PREVALECE el `datos_mercado.json`** (fuente de verdad). NO mezclar datos recordados de sesiones pasadas. |
| El usuario no ha pedido algo que la plantilla obliga | **SE HACE IGUAL** (desglose, comparables, resumen para copiar, sección de gastos). NO preguntar "¿lo quieres?". |
| El usuario pide algo NO contemplado en la plantilla | **HACERLO**, pero añadir 1 línea al final: "He añadido [X] porque me lo has pedido explícitamente." |
| El usuario dice "estudia X" sin más detalle | **PREGUNTAR** al usuario antes de empezar (ver §CUÁNDO PREGUNTAR). NO asumir versión/año/precio/km. |
| El usuario dice "completa el Golf R DE" sin más | **PREGUNTAR** qué versión exactamente (R 310cv, R 20 Years, R Performance...) y cuántos anuncios más quiere revisar. NO asumir. |
| Hay 2 versiones razonables para elegir | **PREGUNTAR** al usuario. NO elegir la "más probable". |
| La nube detecta un error en datos del JSON | **AVISAR** al usuario con 1 línea ("El JSON dice X pero el anuncio dice Y, ¿cuál uso?"). NO corregir sola. |

---

## ❓ §CUÁNDO PREGUNTAR (regla de oro — mejor preguntar 1 vez que inventar 1 dato)

> **Principio (23-ago-2026 v0.3.9):** la nube prefiere **preguntar UNA sola vez** a inventar. Las decisiones de negocio (precios, versiones, año, km, perfil) NO se asumen. Las decisiones mecánicas (formato, secciones, checklist) ya están resueltas y NO se preguntan.

### ✅ SIEMPRE preguntar (antes de empezar el estudio)

| Si el usuario dice... | La nube PREGUNTA... |
|---|---|
| "Estudia el Golf" | ¿Qué versión? (GTI, TCR, Clubsport, R, R-Line) · ¿año mín/máx? · ¿km máx? · ¿precio máx? · ¿equipamiento imprescindible? |
| "Mira el Astra OPC" | ¿Qué generación? (J, K) · ¿gasolina o diesel? · ¿kilometraje máx? · ¿manual o automático? |
| "Busca un SUV compacto" | ¿Presupuesto? · ¿año mín? · ¿km máx? · ¿gasolina/diesel/híbrido? · ¿marca preferida? · ¿uso principal? |
| "Hazme un estudio de mercado" | ¿De qué modelo/marca? (si no lo dice) |
| "Completa el X" | ¿Qué parte exactamente? ¿qué versión? |
| "Importa este MD" | ¿Es `informes/<marca>/<marca>_<segmento>_<enfoque>_<YYYY-MM-DD>.md`? ¿hay sesión activa o es en frío? |

### ❌ NUNCA preguntar (ya está decidido por la plantilla)

- Si incluir desglose por variables o los mejores anuncios → **NO** (desde v0.5.0).
- Si incluir §FUENTES con URL + parámetros + conteo + fecha → **SIEMPRE sí**.
- Si incluir comparables → **SIEMPRE sí**.
- Formato del archivo → **SIEMPRE 1 .md**.
- Orden de secciones → **fijo**.
- Checklist al final → **siempre**.
- Si el suelo es fiable → marcar ✅/👁️/⚠️, **nunca sin marca**.
- Si la cobertura es incompleta → **declararla**.

### 🤔 PREGUNTAR si hay 2+ opciones razonables

| Situación | La nube PREGUNTA |
|---|---|
| 2 versiones encajan con el perfil | "¿Versión A o versión B?" |
| 3+ motorizaciones para un modelo | "¿Gasolina o diesel? ¿potencia mín?" |
| El usuario da precio "alrededor de 20k" | "¿Margen ±2k € o estricto?" |
| El perfil del usuario es vago | "¿Buscas para ti o para revender?" |
| Coche con 2 carrocerías (3p/5p, SB) | "¿Solo SB, solo 3p, o ambas?" |

### 📝 FORMATO de la pregunta

Cuando la nube tiene que preguntar, usa **este formato exacto** (1 línea por pregunta, sin rodeos):

```
Para no inventar datos, necesito confirmar 4 cosas:

1. ¿Versión? (GTI 230cv / GTI 245cv Performance / TCR / R)
2. ¿Año mín? (ej. 2017+)
3. ¿Km máx? (ej. ≤150.000 km)
4. ¿Equipamiento imprescindible? (techo solar / cuadro digital / LED / nada)

(Una vez me respondas, lanzo el estudio sin más paradas hasta el informe final.)
```

---

## ✅ CHECKLIST OBLIGATORIO ANTES DE ENTREGAR (la nube lo rellena y lo muestra)

> **Antes de dar el informe por terminado**, la nube escribe este bloque al final (con ✅/❌ en cada línea). NO entregar si hay algún ❌ sin resolver.

```
✅ Check — Estructura completa (9 secciones, en orden):
  ✅ 1. §CONCLUSIÓN — tabla resumen + 4-6 líneas
  ✅ 2. §VERSIÓN A VERSIÓN — tabla de números + nota de 1 línea
  ✅ 3. §COMPARABLES (al menos 1; "Sin comparables todavía" si no hay)
  ✅ 4. §TRAMPAS ("Sin trampas detectadas" si no hay)
  ✅ 5. §RESUMEN PARA COPIAR (1 párrafo, sin enlaces)
  ✅ 6. §DESGLOSE 1.500 € GASTOS
  ✅ 7. §COBERTURA Y METODOLOGÍA
  ✅ 8. §FUENTES CONSULTADAS — URL exacta de cada medición + conteo + fecha
  ✅ 9. §CHECKLIST (auto-verificación)

✅ Check — Datos consistentes:
  ✅ Suelos DE/ES con marca de fiabilidad (✅/👁️/⚠️)
  ✅ Columna "Puesto en Huelva" = suelo DE + 1.500 €
  ✅ Columna "Ahorro real" = suelo ES − puesto Huelva
  ✅ URLs completas y visibles (no "ver [enlace]")
  ✅ Sin jerga IA (sincronizado, merge, volcado, fuente_medicion)
  ✅ Cobertura incompleta declarada con números

✅ Check — Archivos:
  ✅ UN solo .md, sin duplicados
  ✅ Nombre: <marca>_<segmento>_<enfoque>_<YYYY-MM-DD>.md
  ✅ Sin PDF generado
```

---

## 📐 ESTRUCTURA OBLIGATORIA DEL INFORME (orden fijo)

> Las secciones van **exactamente en este orden**. NO reordenar. NO omitir.

| # | Sección | Obligatoria | Si falta muestra |
|---|---|:---:|---|
| 1 | 🏁 CONCLUSIÓN (párrafo + tabla resumen) | ✅ | — |
| 2 | 🎯 VERSIÓN A VERSIÓN (tabla de números + nota de 1 línea) | ✅ | — |
| 3 | 🧩 COMPARABLES | ✅ | Poner "Sin comparables todavía" |
| 4 | ⚠️ TRAMPAS | ✅ si hay | Poner "Sin trampas detectadas" |
| 5 | 📋 RESUMEN PARA COPIAR | ✅ | — |
| 6 | 💶 DESGLOSE 1.500 € GASTOS | ✅ | — |
| 7 | 📋 COBERTURA Y METODOLOGÍA (+ 📁 ARCHIVO GENERADO) | ✅ | — |
| 8 | 🔗 FUENTES CONSULTADAS (URL + parámetros + conteo + fecha) | ✅ | — |
| 9 | ✅ CHECKLIST (auto-verificación) | ✅ | — |

Guardar como: `informes\<marca>\<marca>_<segmento>_<enfoque>_<YYYY-MM-DD>.md`

> `<segmento>` ∈ `compacto · suv · berlina · deportivo · familiar · urbano` (valores del schema) · `<enfoque>` ∈ `deportivo · generalista · accesible · premium · mixto`. Marca en corto y minúsculas (`vw`, `audi`, `bmw`, `mercedes`...).
> Estudio de un modelo concreto (no la marca entera): `<marca>-<modelo>_<segmento>_<enfoque>_<YYYY-MM-DD>.md`.
>
> **Lo que se ELIMINÓ de la plantilla el 28-sep-2026 (v0.5.0) — y NO se vuelve a poner:** fichas con los 2 mejores anuncios, desglose por variables (cambio/techo/cuadro digital), §3.b segmentación amplia y §3.c limitaciones dentro de cada ficha. Esto es un **sondeo de mercado**, no un dossier: el detalle de anuncios lo aporta el usuario abriendo las URLs de la sección 8.

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

## 🎯 VERSIÓN A VERSIÓN — el matiz de cada una

> Los números están en la tabla de la conclusión. Aquí, solo lo que **cambia la decisión**, versión a versión (2-3 líneas cada una, sin tablas y **sin abrir anuncios ni fichas** — eso lo haces tú con las URLs de la sección 8).

- **GTI (230/245cv)** — DE tiene 5x la oferta de ES; el hueco es real pero justito. Ojo con el Performance de 245cv: cambia el filtro de potencia.
- **GTI TCR (290cv)** — el hueco más grande del estudio. La muestra española es corta (n=3): el suelo ES puede ser puntual.
- **GTI Clubsport (265cv)** — solo existe en Mk7 (2016-2017). Si aparece algo etiquetado "7.5", es un error de etiquetado.
- **Golf R (310cv)** — el ganador en riesgo/beneficio (hueco con mercado 6x mayor). Lupa en el re-chipeo: 5 de 14 anuncios DE llevan "stage 1" silencioso.

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

##  DESGLOSE DE LOS 1.500 € DE GASTOS FIJOS

| Concepto | Estimado | Notas |
|---|---:|---|
| Transporte Alemania → Huelva | 1.000 € | Camión cerrado, ≈7-10 días según ruta |
| ITV + homologación | 200 € | Sube si hay reformas |
| Gestoría + ausfuhr | 300 € | Baja en Alemania + matrícula provisional |
| **TOTAL gastos fijos** | **1.500 €** | Sin IVA ni impuesto de matriculación |

**Lo que NO incluye** (se calcula por coche concreto en Flujo A): **IVA de importación** (21% sobre precio + transporte + seguro) e **IEDMT** (según CO₂ y antigüedad; en híbridos enchufables puede ser 0). Orden de magnitud todo incluido: **+3.500 € a +5.500 €**; bastante más en V6/V8 muy contaminantes.

> Si quieres que el informe use otra cifra fija (ej. "suma 2.000 € porque mis gastos son más altos"), se recalcula en 1 minuto.

---

## � ARCHIVO GENERADO

```
informes/vw/vw-compacto-deportivo_2026-08-23.md
```

(El mapa `datos_mercado.json` lo actualiza Copilot cuando le pases este MD — la nube no tiene acceso a tu disco.)

---

## �📋 COBERTURA Y METODOLOGÍA

- **Fuentes:** solo mobile.de (DE) + Coches.net (ES). El resto de portales quedan para buscar unidades concretas.
- **Filtros comunes:** año ≥2017 · km ≤180.000 · precio como se acordara · gastos fijos 1.500 €.
- **Aislamiento por versión:** potencia (kW/cv) + combustible + carrocería. El campo de texto libre `Versions[]`/`Version=` NO se usa como filtro en Coches.net.
- **Muestra:** N anuncios DE / N ES por versión. Es una foto del mercado de hoy, no una mediana robusta.
- **Presupuesto:** 2 peticiones por versión y mercado (conteo + suelo). **No se abren fichas ni anuncios** — fiabilidad 👁️ por defecto; ✅ solo si el usuario pide verificar a mano.
- **Cobertura incompleta** (si la hay): declarar qué quedó sin revisar, con números.

---

## 🔗 FUENTES CONSULTADAS — re-ejecutables (OBLIGATORIA)

> **Para qué sirve:** que puedas **rehacer la búsqueda tú mismo** y quedarte con los anuncios que quieras. Un número sin su enlace no es un dato: es una opinión. **Sin esta sección el informe NO se entrega.**

Una entrada **por cada versión medida** (las mismas filas de la tabla de la conclusión). Formato literal:

```
**🔗 Fuentes (re-ejecutables)**

- 🇩🇪 mobile.de: `<URL>` (marca VW=25200, modelo Golf=14, potencia 224-232 kW = 305-315 cv, año ≥2017, km ≤170.000, precio ≤40.000 €, solo vendedores en Alemania (cn=DE), sin daños, orden precio ascendente) → **30 anuncios** (medido 16 de septiembre de 2026)
- 🇪🇸 Coches.net: `<URL>` (marca VW=MakeIds[0]=47, modelo Golf=ModelIds[0]=89, potencia 305-315 cv, año ≥2017, km ≤170.000, precio ≤40.000 €, orden precio ascendente) → **3 anuncios** (medido 16 de septiembre de 2026)
```

**Reglas duras de esta sección:**
1. **Una URL por portal y por versión**, con TODOS los filtros de la medición. No vale la URL de la marca entera.
2. Los **parámetros van escritos al lado** (marca=ID, modelo=ID, potencia en kW y en cv, año, km, precio, país del vendedor, orden) para que se vea qué se filtró.
3. Se añade el **conteo medido** de cada URL más la **fecha de medición**. Si al abrirla el conteo no cuadra → declararlo en §COBERTURA.
4. Se generan con el script, **nunca a mano** (un número copiado a mano se cuela mal):
   `py .claude/skills/importacion-vehiculos/scripts/fuentes.py --seccion --fecha YYYY-MM-DD --precio-max 40000 --pais-de DE --spec "Marca|Modelo|Etiqueta|cv_min|cv_max|anio_desde|anio_hasta|km|carroceria|anuncios_de|anuncios_es|versions_es|q_de"` → **todos los `--spec` del informe en UNA sola llamada** (el conteo de anuncios va POR VERSIÓN dentro de su `--spec`).
5. **Filtro de versión:** cuando dos versiones comparten modelo (GTI vs R, S line vs RS), el campo `versions_es` mete `Versions[0]` en la URL de Coches.net y `q_de` el texto libre en mobile.de. Sin eso, las dos versiones dan la MISMA URL y la medición no es reproducible.
5. Los IDs salen del catálogo compartido (`references/mobile-de-ids.json`, idéntico en las dos skills): **no se inventan nunca**. Un ID inventado no falla: devuelve otra marca.
6. Si un modelo no está en el catálogo, el script avisa del plan B (`q=` en mobile.de, `Versions[0]` en coches.net) → ese aviso se declara en §COBERTURA, no se esconde.

---

## ✅ CHECKLIST — auto-verificación antes de entregar (obligatorio)

> La nube rellena este bloque al final del informe con ✅ en cada línea. NO entregar si hay algún ❌ sin resolver.

```
✅ Estructura completa (9 secciones, en orden):
  ✅ 1. §CONCLUSIÓN con párrafo + tabla resumen
  ✅ 2. §VERSIÓN A VERSIÓN (nota por versión, SIN tablas de anuncios)
  ✅ 3. §COMPARABLES (al menos 1)
  ✅ 4. §TRAMPAS (al menos 1 si las hay)
  ✅ 5. §RESUMEN PARA COPIAR (1 párrafo, sin enlaces)
  ✅ 6. §DESGLOSE 1.500 € GASTOS
  ✅ 7. §COBERTURA Y METODOLOGÍA
  ✅ 8. §FUENTES CONSULTADAS — URL + parámetros + conteo + fecha de CADA medición
  ✅ 9. §CHECKLIST

✅ Datos consistentes:
  ✅ Suelos DE/ES con marca de fiabilidad (👁️/✅/⚠️)
  ✅ Columna "Puesto en Huelva" = suelo DE + 1.500 €
  ✅ Columna "Ahorro real" = suelo ES − puesto Huelva
  ✅ URLs completas y visibles (no "ver [enlace]")
  ✅ Sin jerga IA (sincronizado, merge, volcado, fuente_medicion)
  ✅ Cobertura incompleta declarada con números

✅ Lo que NO debe aparecer (eliminado en v0.5.0):
  ✅ Sin fichas "2 mejores anuncios"
  ✅ Sin desglose por variables (cambio / techo / cuadro digital)
  ✅ Sin §3.b segmentación amplia ni §3.c de límites por ficha

✅ Archivos:
  ✅ UN solo .md, sin duplicados
  ✅ Nombre: <marca>_<segmento>_<enfoque>_<YYYY-MM-DD>.md
  ✅ Sin PDF generado

✅ Mensaje de cierre:
  ✅ Última línea: "Archivo: informes/<marca>/<archivo>.md. Pásale
     este MD a Copilot en VS Code y dile 'importa este MD al mapa'."
```
