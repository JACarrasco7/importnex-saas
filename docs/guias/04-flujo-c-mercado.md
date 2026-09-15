# Guía 04 — Flujo C: escanear el mercado

> Cuándo: revisión periódica de oportunidades — qué modelos/segmentos tienen hueco de precio en Alemania vs España.

---

## 1. Qué pedirle al skill

> "Escanea el mercado de deportivos entre 15-40k€"

El skill hará Flujo C: escaneará las fuentes, agregará por modelo y detectará **huecos de mercado** (modelos más baratos en DE que su equivalente en ES).

---

## 1b. Revisar un SEGMENTO desde modelos conocidos ⭐ (el caso más habitual)

Casi nunca quieres "el mercado entero": quieres **una familia concreta de coches** y saber
**qué rivales tiene cada marca** antes de ofertar nada.

**Se lo pides así** — nombrando 2-3 modelos que definan el segmento:

> "Quiero revisar **SUVs deportivas**: Audi **RSQ3**, VW **Tiguan R** y similares. Dame un
> plan de búsqueda **por marca** con sus modelos equivalentes."

**Lo que hace el skill:**
1. Reconoce el segmento (SUV + deportivo / `segmento=suv` + `tipo_cliente=deporte_ocio`).
2. **Completa la lista de rivales por marca** — no te da solo los 2 que has nombrado: añade
   los equivalentes (BMW X3 M40i, Mercedes GLA 45 AMG, Cupra Formentor VZ, Porsche Macan…).
3. Mide **todos con la misma vara** (mismo control de año, mismo equipamiento de referencia).

**Lo que te devuelve** — el *Informe BÚSQUEDA* (checklist obligatorio del Flujo C):

| Por cada modelo | Qué es |
|---|---|
| **Oferta** | nº de unidades en 🇩🇪 y 🇪🇸 |
| **Suelo** | el más barato de cada mercado (orden precio ascendente) |
| **Mediana** | precio de referencia real de la muestra |
| **Hueco** | `hueco_pct` bruto y `hueco_neto_pct` (ya con los costes de importación) |
| **Veredicto** | 🟢 merece la pena · 🟡 dudoso · 🔴 no |

Y sobre todo una **comparativa entre modelos**: *cuál de toda la familia tiene el mejor hueco
y cuál no merece ni abrirse*. Eso es lo que responde a "¿por dónde empiezo?".

Cierra con un checkpoint (**CP-C**): *"Mapa de oportunidades listo. ¿Cuáles quieres ofertar?"*
— el skill **espera** a que elijas antes de gastar peticiones en unidades concretas.

### ⚠️ Tres cosas que hay que mirar al leer el informe

1. **Suelo ≠ mediana.** El anuncio más barato casi siempre es el peor de la muestra
   (siniestro, 300.000 km, importado de terceros). **Para decidir se usa la mediana**; el
   suelo sirve para saber si el hueco es real o solo un anzuelo.
2. **Control de año.** Si se comparan años distintos, la mezcla de generaciones **invierte el
   signo del hueco**. Caso real: el Mercedes A 45 AMG daba **-15%** sin control y **+6,2%**
   con control 2019+ en ambos mercados. Si el informe no dice el control, pídelo.
3. **Oferta escasa = poca fiabilidad.** Un modelo con 12 unidades en España no da una mediana
   sólida. El skill lo marca, pero conviene mirarlo.

### 🔗 Y siempre con los enlaces: compruébalo tú mismo

El informe **no se entrega sin los enlaces de cada medición.** Debajo de cada versión verás su
bloque de fuentes:

```
Golf R Variant Mk7.5 (310cv, 2017-2020)

- 🇩🇪 mobile.de: `https://suchen.mobile.de/fahrzeuge/search.html?dam=0&fr=2017%3A2020&ml=%3A180000&ms=25200%3B14%3B%3B%3B&pw=224%3A232&c=EstateCar` (marca VW=25200, modelo Golf=14, familiar, 2017-2020, km≤180.000, 224-232 kW = 305-315 cv, precio ascendente)
- 🇪🇸 Coches.net: `https://www.coches.net/segunda-mano/?MakeIds[0]=47&ModelIds[0]=89&ArrBodyType=4&PowerHpFrom=305&PowerHpTo=315&MaxKms=180000&MinYear=2017&MaxYear=2020&fi=Price&or=1`
```

Pégalos en el navegador y verás **el mismo listado** con el que se midió: de ahí salen el
conteo, el suelo y la mediana. Los parámetros van escritos al lado a propósito, para que veas
**qué se filtró** — si un número no cuadra con lo que ves al abrir el enlace, dímelo.

---

## 2. Salida típica

Un **scouting** con:
- Modelos escaneados
- Hueco de precio por modelo (`hueco_pct`)
- Nº de unidades disponibles en DE
- Recomendación aproximada por modelo
- Resumen ejecutivo

## 3. Dónde se guarda

Cada escaneo se importa a la BD (tabla `scouting_mercado` + `modelos_mercado`). Puedes consultarlos:

- **API:** `GET /api/scouting` (autenticado por token, scoped por organización)
- **App:** en la web (endpoint público de marketplaces) — sección de oportunidades

## 4. Presupuesto típico

Flujo C: **hasta 100 peticiones** (~7 modelos, 12-18 peticiones por modelo). Avisar al 50 (50) y al 80 (80).

## 5. Cuándo hacerlo

- Semanal o quincenal según volumen de mercado.
- Antes de decidir "qué modelo importamos ahora".
