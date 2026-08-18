---
name: estudio-mercado
version: 0.2.1
description: >
  Estudio profundo del mercado de coches de 2ª mano en España y Alemania para
  JJ Import Motors. Genera un mapa de mercado persistente (datos_mercado.json)
  con estadísticas reales (oferta, precios, rotación, demanda, hueco, veredicto)
  por categoría y modelo. Por defecto compara a MÁXIMO equipamiento (la unidad
  alemana suele venir full: techo, cuadro digital, LED...). Alimenta la skill
  hermana importacion-vehiculos (Flujo E stock, Flujo C mercado, Flujo D
  descubrimiento) para que las búsquedas partan de criterios de mercado
  objetivos en vez de leer anuncios a ciegas.
---

# Estudio de mercado de coches — JJ Import Motors (ES + DE)

> **Skill hermana de `importacion-vehiculos`.** Esta skill NO busca coches para un cliente ni valora unidades: **estudia el mercado** y deja un **mapa de datos persistente** listo para que la otra skill decida QUÉ modelos merecen la pena ANTES de buscar nada.
>
> **Motivo (17-ago-2026):** en las pruebas de stock, la skill de importación no tenía criterio de selección ("¿por qué no puse ningún Mercedes?"). El criterio debe venir de un **estudio de mercado previo**, no de leer anuncios al vuelo.

---

## 🎯 Qué resuelve

1. **Criterio de selección explícito.** Antes de buscar un coche, saber qué modelos tienen hueco real (diferencia de precio ES vs DE que cubre costes de importación) y cuáles son puro atractivo visual.
2. **Datos reales, no solo anuncios.** Oferta + precios (portales) + matriculaciones/parque (DGT/KBA) + demanda (Google Trends) + rotación (días publicados).
3. **Eficiencia.** El estudio se hace UNA vez y se refresca por categoría (showstoppers 2 sem · rotación 3 · gemas 4); N encargos de búsqueda lo reutilizan en vez de re-escaneo de cero.

---

## 🔗 Relación con `importacion-vehiculos`

| | `estudio-mercado` (esta) | `importacion-vehiculos` |
|---|---|---|
| **Rol** | Genera el mapa de mercado | Busca/valora coches concretos |
| **Output** | `datos_mercado.json` + informe de mercado | Informe de búsqueda + dossier + ZIP |
| **Cadencia** | Periódico por categoría (2-4 sem) | Por encargo de cliente |
| **Consume** | Portales + fuentes públicas | `datos_mercado.json` (PASO 0) + portales |

**Contrato de integración:** `importacion-vehiculos` lee `datos_mercado.json` en su **PASO 0** y en **FIJAR MODELOS (PASO 3b)** para:
- Decidir qué modelos candidatos proponer (con encaje ES/DE).
- Justificar el criterio ("Mercedes CLA entra porque hueco X% y demanda alta; Cupra no, porque paridad ES≈DE").
- No re-sondear lo que el mapa ya tiene fresco (según `refrescar_antes_de_categoria` de cada modelo).

**📍 RUTA PACTADA del mapa (L2 · 17-ago-2026):** el archivo vive SIEMPRE en `C:/Users/jacar/Desktop/JJImportMotors/datos_mercado.json` (campo `ruta_canonica` en el JSON). Si `importacion-vehiculos` no lo encuentra ahí → NO fallback silencioso: avisa "mapa de mercado no encontrado; considera ejecutar estudio-mercado" y continúa con `modelos-medidos.md` declarándolo. Ambas skills escriben/leen en esa misma ruta.

### 🔁 Comunicación BIDIRECCIONAL — aprendizaje de cada encargo (17-ago-2026)

> El mapa no solo se alimenta del estudio periódico: **cada búsqueda concreta de la skill hermana lo enriquece**.

**① importación → mapa (feedback tras medir):**
- Flujo A (URL evaluada) y Flujo B (modelo barrido con 7 fuentes) producen **mediciones profundas reales** (medianas DE/ES, nº anuncios, hueco). Al cerrar, esa medición se vuelca al mapa: nueva entrada o actualización con `refrescar_antes_de_categoria = hoy + cadencia de su categoría` (L7).
- Resultado: el mapa acumula conocimiento de CADA encargo — las búsquedas específicas hacen más listas las ambiguas.

**② mapa → importación (contexto previo):**
- **Flujo A:** contexto del modelo (mediana, hueco, rotación, veredicto) disponible ANTES de medir → comparables y veredicto más rápidos y sólidos. Si el modelo no estaba, esta evaluación lo añade.
- **Flujo B:** chequeo de veredicto ANTES del barrido → si el mapa marca 🔴 (hueco neto <0, ES mejor), avisar al usuario antes de gastar 15-50 peticiones.
- **Flujo C/D/E:** PASO 0 + FIJAR MODELOS (criterio de selección).

**Regla de frescura:** lo que el mapa tiene fresco NO se re-mide desde importación (PASO 0); lo caducado se re-estudia aquí o se refresca con la próxima medición de un encargo.

---

## 📊 Las 3 capas de datos (de gratis a pago)

> Detalle completo en `fuentes_datos.md`. Resumen:

**Capa 1 · Pública y gratuita (usar SIEMPRE):**
- **DGT (ES)** — transferencias de vehículos de ocasión mensuales, por marca/modelo.
- **KBA (DE)** — matriculaciones, parque y bajas de Alemania.
- **GANVAM / FACONAUTO / ANFAC (ES)** — informes mensuales de precios VO y matriculaciones.
- **Google Trends** — demanda de búsqueda por modelo (ES vs DE).

**Capa 2 · Portales (navegación real, como la skill hermana):**
- **mobile.de (DE)** — precio de referencia DE, oferta, sellos "Sehr guter/Guter/Fairer Preis", días publicados.
- **Coches.net (ES)** — precio de referencia ES, oferta, sellos "Buen precio/Precio justo", rotación.
- **AutoScout24.de** — SOLO contar oferta (A8: NUNCA precio).
- Wallapop / Milanuncios / kleinanzeigen / AutoUncle — complemento (chollos, rotación).

**Capa 3 · Pago (futuro, el salto a SaaS vendible):**
- **DAT / Schwacke (DE)** — tasación profesional VO alemana.
- **Eurotax / Glasses (ES)** — tasación profesional.
- **mobile.de / AS24 Market Insights** — API de precios medios y tendencias.

> **Regla:** empezar con Capa 1 + 2 (gratis). El esquema de `datos_mercado.json` deja hueco para la Capa 3 (`tasacion_pro` opcional) sin romper nada.

---

## 🎯 Criterios de selección POR CATEGORÍA (heredado de importación, regla 1c)

> El veredicto de un modelo depende de la categoría a la que sirve. NO un único criterio (hueco %) para todos.

| Categoría | Criterio principal | Métricas clave |
|---|---|---|
| 🔥 **Showstoppers** (atracción) | ATRACTIVO visual + demanda | demanda (Trends), oferta escasa, equipamiento/versión deportiva. El hueco es secundario |
| ⚙️ **Alta rotación** (masivo) | HUECO % + demanda masiva + fiabilidad | hueco ES vs DE, nº anuncios ES (mercado continuo), coste mantenimiento |
| 💎 **Gemas económicas** (primer coche) | ACCESIBILIDAD + durabilidad | precio bajo, coste asegurar/mantener, fiabilidad, rotación rápida |

**Umbrales de hueco objetivo (heredados, `importacion-vehiculos` SKILL §REFERENCIA):**
- Nicho ≥10% · Rotación ≥10% · Tramo 8-14k ≥12% (objetivo)
- Mínimos (EXIT 3): Nicho 8% · Rotación 10% · Tramo 8-14k 10%

---

## 🎯 SEGMENTACIÓN del mercado (17-ago-2026)

> Además de categoría (para qué sirve el coche) y origen, el mapa se segmenta por **tipo de vehículo** y **banda de precio**. Así se puede estudiar/filtrar por sub-mercado y cruzar veredictos con contexto.

**2 dimensiones de segmentación:**

| Dimensión | Valores | Para qué sirve |
|---|---|---|
| **Segmento (tipo)** | `compacto` · `suv` · `berlina` · `deportivo` · `familiar` · `urbano` | Saber dónde hay oferta/hueco por tipo (ej. hay más hueco en berlina premium que en suv) |
| **Rango de precio (origen DE)** | `0-8k` · `8-14k` · `14-25k` · `25k+` | Cruza con los umbrales de tramo (el tramo 8-14k exige ≥12%) y con la accesibilidad de gemas |

**Reglas:**
1. Cada modelo lleva su `segmento` y `rango_precio` (una banda, en origen DE).
2. En FASE 0 se puede acotar el estudio a un segmento o banda ("estudia berlinas de 14-25k").
3. El veredicto de categoría se interpreta con el segmento: un deportivo de 25k+ es showstopper por atractivo (el hueco pesa menos); un urbano de 8-14k es gema por accesibilidad (el hueco pesa más).
4. En el refresco delta, priorizar por segmento si una banda se ha movido mucho.
5. El dashboard `/mercado` filtra por segmento y banda.

### 👤 TIPOS DE CLIENTE — tercera dimensión (17-ago-2026)

> Perfil de comprador objetivo. Los 3 del prompt de stock (showstoppers / rotación / gemas) son tipos reales; se añaden más. Cada modelo lleva `tipo_cliente` (principal) + `tipos_cliente_secundarios`.

| tipo_cliente | Perfil | Modelos de ejemplo (por defecto) | Vínculo categoría |
|---|---|---|---|
| `impacto_showstopper` | Quien quiere llamar la atención / tráfico | Golf R, Cupra Leon/Ateca, Arteon SB, Astra OPC, A5 SB, Focus RS, Serie 4 GC | showstoppers |
| `deporte_ocio` | Entusiasta / weekend car | GTI, R, OPC, Focus ST/RS, Mini Cooper S, i30N, Mégane RS | showstoppers |
| `primer_coche` | Conductor novel (asegurar y mantener barato) | Fiesta, Polo, Ibiza, A1, Mini Cooper, Mazda 3 | gemas_economicas |
| `familia` | Espacio/seguridad, uso diario | Ateca, Tiguan, Golf Sportsvan, León ST, Touran, Scenic | alta_rotacion / gemas |
| `diario_eficiencia` | Muchos km, consumo bajo | Golf TDI, León FR TDI, A3 30 TDI, Clase A 180d | alta_rotacion |
| `premium_imagen` | Marca/estatus, a menudo financiado | Clase A/CLA AMG, A3 S-Line, A5, Serie 1/3 M Sport, Arteon | alta_rotacion / showstoppers |
| `negocio_reventa` | Compra para revender (margen) | transversal — depende del hueco neto | todas |

**Reglas del tipo de cliente:**
1. `tipo_cliente` es el perfil objetivo principal del modelo; `tipos_cliente_secundarios` los otros que aplican.
2. En FASE 0 se puede acotar por cliente ("estudia coches para primer coche").
3. El dashboard `/mercado` filtra por tipo de cliente.
4. Es complementario a categoría (para qué sirve el coche) y segmento (tipo de vehículo): un Astra OPC = showstopper + deportivo + deporte_ocio.

> **Modelos sugeridos por el usuario (18-ago-2026):**
> - **VW Arteon (Solo Shooting Brake).** "No se vende mucho pero es un coche atractivo, depende de motorización y equipamiento; solo vería el Shooting Brake." → segmento `berlina`, tipo `impacto_showstopper`/`premium_imagen`. Al estudiarlo: fijar versión **SB** y equipamiento alto (cuadro digital, techo, LED) — aplica la regla de máximo equipamiento.
> - Revisar siempre si un modelo tiene variante **SB/GT/Kombi** que el usuario solo quiera (A13: no cambiar el filtro en silencio, pero tampoco asumir que quiere todas las carrocerías).

### 🎯 ESTUDIOS DIRIGIDOS (17-ago-2026) — construir la BBDD poco a poco

> No hace falta el estudio masivo de 3 categorías para tener BBDD útil. Se puede estudiar **por marca, por modelo, por segmento, por rango o por tipo de cliente**, y cada pasada enriquece el mismo `datos_mercado.json`.

**Disparadores:** "estudia la marca Audi" · "estudia el Golf 8" · "estudia berlinas de 25k+" · "estudia coches para primer coche" · "estudia todos los Mercedes del segmento premium".

**Cómo se ejecuta:** mismo flujo (FASE 1 ES → FASE 2 DE → cruce → guardar), pero el ALCANCE está acotado:
- `por_marca`: todos los modelos relevantes de la marca (ej. Audi: A1, A3, A4, A5, A6, Q2...), con su segmento/categoría/tipo_cliente.
- `por_modelo`: un modelo concreto, en todas sus versiones/motorizaciones.
- `por_segmento` / `por_rango` / `por_tipo_cliente`: el sub-mercado completo que cumple el criterio (A19: explorar todo lo que cumple, no solo ejemplos).

**Reglas:**
1. Cada pasada hace **MERGE por `slug`** en `datos_mercado.json` (no borra entradas de otras pasadas) y recalcula el **índice `marcas`**.
2. `tipo_estudio` en la cabecera indica cómo se generó la última pasada.
3. Un modelo estudiado vía dirigida entra con `fuente_medicion: estudio` (mediana fiable) o se marca `pendiente_fase2` si no se aisló la versión.
4. La caducidad (`refrescar_antes_de_categoria`) se asigna igual por su categoría; una pasada dirigida puede refrescar solo los modelos tocados.
5. El resultado alimenta también `../importacion-vehiculos/memoria/modelos-medidos.md` (histórico compartido) y el SaaS vía `market:import`.

---

## �️ EQUIPAMIENTO — comparar a MÁXIMO equipamiento por defecto (18-ago-2026)

> **Motivo (feedback usuario 18-ago):** en el estudio hay que comparar la unidad alemana con el **máximo equipamiento posible** (suele venir full: techo, cuadro digital de instrumentos, LED, HUD...), NO contra la unidad española base. Un precio ES "3% más barato a igual año" NO es comparable si el alemán trae cuadro digital + techo y el español no. **El equipamiento ES el argumento que más mueve a los jóvenes / compradores de coche moderno.**

**Regla por defecto (si el usuario no indica lo contrario):** el estudio mide y compara la variante **full / máximo equipamiento** de cada modelo:
- **En DE (mobile.de):** filtrar por equipamiento real en la sección `Ausstattung` (checkbox): `Schiebedach` (techo) · `Virtual Cockpit / Digitales Kombiinstrument` (cuadro digital) · `Sitzheizung` (calefacción asientos) · `Head-Up-Display` · `LED/MATRIX` · `Navi` · `ACC`. Anotar cuáles vienen de serie en la versión.
  - **ES/selectores estables (18-ago):** la versión ES de mobile.de (`/es/s/auto`) usa `data-testid` estables para marcar los checkboxes (ver `../importacion-vehiculos/02-flujos/playbook_filtrado.md` §Selectores ESTABLES). Para unidad "full" reproducible, marcar los 5: Interior → `Panel de instrumentos digital` (cuadro digital) + `Pantalla Head-up` + `Calefacción de asiento` · Extras → `Techo panorámico` + `Faros LED`.
- **En ES (Coches.net):** el filtro de equipamiento es MUY limitado — **solo hay checkbox de techo** (`Techo solar`), NO hay filtro de cuadro digital. Solución: filtrar por techo en ES como proxy + en DE por el conjunto full; o abrir 1-2 fichas de muestra por mercado (excepción puntual a A17) para confirmar equipamiento real del tramo de precio.

**Cómo afecta al hueco:** si ES es "más barato" pero básico y DE es full, el hueco REAL (a igualdad de equipamiento) es MAYOR que el bruto: el valor del equipamiento (techo +1.000-1.500 €, cuadro digital +500-1.000 €, LED +500 €, HUD +300 € — ver `importacion-vehiculos/03-informes/comparables.md` §Primas) se suma al lado DE como valor entregado, no como sobrecoste.

**Reglas:**
1. **Anotar SIEMPRE el nivel de equipamiento** de la mediana: `equipamiento_nivel: "base" | "medio" | "full"` por mercado (campo nuevo en el esquema) + lista de ítems clave (`equipamiento_de` / `equipamiento_es`).
2. Si ES y DE no comparan el mismo nivel (ES base vs DE full), **NO dar el hueco como definitivo**: ajustar el precio ES al alza con las primas de equipamiento de `comparables.md` y anotarlo en `nota`.
3. El veredicto de showstoppers (atractivo) ya valora equipamiento; en alta_rotación y gemas, el equipamiento full DE es argumento de venta aunque el hueco bruto sea menor.
4. La `query_reejecutable` incluye los filtros de equipamiento usados (p.ej. `Schiebedach` en mobile.de, techo en Coches.net) para poder re-medir igual.

---

## 🚫 REGLA SEAT/CUPRA — CORREGIDA (18-ago-2026)

> **Corrección crítica del feedback del usuario:** la v1/v2 concluyó "nunca ofertar Seat/Cupra, España más barata (Martorell)". **ESO ES INCORRECTO.** El Cupra León más barato en España está a **19.500 €**; en Alemania los más baratos están a **15-16.000 €**. La conclusión errónea salió de la **mediana con banda ≥20k en ambos mercados**, que recortó la cola barata alemana (el mismo error de la banda que el propio estudio v2 identificó en Golf). La marca nacional NO invierte el arbitraje: **el suelo DE es más bajo**.

**Regla nueva:**
1. **La nacionalidad de la marca NO es criterio.** Seat/Cupra se fabrican en Martorell, pero el mercado alemán sigue teniendo unidades más baratas (mayor rotación, más parque, configuraciones distintas).
2. **SIEMPRE mirar el `precio_desde` (suelo) SIN banda en ambos mercados** antes de declarar "España más barata" — no solo la mediana con banda.
3. Medir Seat/Cupra **sin banda de precio** (o con banda solo ES) y recalcular el hueco con el suelo DE (15-16k) vs suelo ES (19,5k).
4. Actualizar los modelos `seat-leon` y `cupra-leon` del mapa con la medición corregida y `nota` explicando el cambio.
5. **Mantener la regla metodológica** "verificar el `h1` del listado" (la trampa de versión sigue siendo válida) — lo que se corrige es la conclusión de negocio.

---

## �🔀 Flujo de trabajo (una pasada de estudio)

```
FASE 0 — ALCANCE del estudio (con el usuario):
  └─ ¿Estudio COMPLETO por categorías o DIRIGIDO? (por marca / modelo / segmento / rango / tipo_cliente)
  └─ Si completo: ¿qué categorías? (showstoppers / rotación / gemas / todas)
  └─ Si dirigido: ¿qué marca/modelo/segmento/rango/cliente? (ej. "Audi", "Golf 8", "berlinas 14-25k", "primer coche")
  └─ ¿Rango de año/km/precio base? (si no, el mercado manda)
  └─ ¿Qué profundidad? (rápido: solo hueco · completo: hueco + rotación + demanda)
  └─ ACK de 1 línea + OK del usuario

FASE 1 — ES (Coches.net, navegación real):
  └─ Por categoría → listados ordenados por precio → recoger por modelo:
       oferta_es, precio_desde_es, mediana_es, sello de precio, rotación ES (si visible)
  └─ Listado-first (A17): NO abrir fichas; solo 1-2 muestras para confirmar trim/potencia

**FASE 2 — DE (mobile.de, navegación real):**
  └─ Mismos modelos → oferta_de, precio_desde_de, mediana_de, sello "Sehr guter/..."
  └─ rotacion_dias_de (AutoUncle/días publicados) separado de ES (L9)
  └─ Doble pasada por kW para topes de gama (GTI/R/M/AMG/RS/OPC) — ver playbook

FASE 3 — CRUCE y veredicto:
  └─ hueco_pct (bruto) = (mediana_es − mediana_de) / mediana_es × 100  ← comparable con historial y umbrales
  └─ hueco_neto_pct = con costes de importación (fórmulas en §Cálculo)
  └─ Veredicto según categoría (tabla de criterios) + nota de mejor mercado (DE/ES)
  └─ Rotación (días en stock) + demanda (Google Trends, 1 consulta por modelo top)

FASE 4 — GUARDAR el mapa:
  └─ Escribir datos_mercado.json en la ruta pactada (L2), con `schema_version` y `ruta_canonica`
  └─ Antes de escribir, RELEER el JSON actual y hacer MERGE por `slug` (no sobrescribir entradas de otra sesión — E10)
  └─ Asignar `slug` canónico + `alias` a cada modelo (L1, normalización en §Esquema)
  └─ Generar informe de mercado (markdown) para el usuario
  └─ Actualizar `refrescar_antes_de_categoria` POR CATEGORÍA (L7): showstoppers +2 sem, rotación +3, gemas +4
  └─ Sincronizar también `../importacion-vehiculos/memoria/modelos-medidos.md` (registro histórico compartido):
       cada modelo del estudio entra con sus 12 campos para que la skill hermana lo tenga aunque el JSON falte

### ⚠️ Regla de confianza (17-ago-2026)
- Un veredicto 🟢 exige `confianza_precio ≥ 3` (precio de anuncio contrastado o tasación). Con confianza 1-2 (precio dudoso) el máximo permitido es 🟡.
- Si `precio_desde_de` está >15% bajo `mediana_de` con veredicto verde → `oportunidad: true` (chollo escondido).
```

**Presupuesto objetivo:** 1-2 peticiones por modelo y mercado (listados, no fichas). Un estudio de 3 categorías × 10 modelos ≈ 60-80 peticiones, UNA vez cada 2-4 semanas según categoría (el refresco en delta gasta mucho menos).

### ⚡ EFICIENCIA del estudio (17-ago-2026)
- **Facetas de marca/modelo con conteo** (como D1a de importación): enumerar el mercado sin abrir listados → solo los modelos con señal de interés pasan a lectura de listado.
- **Reutilizar `query_reejecutable`** de cada modelo en el refresco delta: NO redescubrir filtros/URLs del estudio anterior (ahorro ~50%).
- **Estudio por lotes con checkpoint:** en FASE 0 proponer partir por categoría (1 categoría por sesión si es grande) y confirmar antes de la siguiente, igual que el Flujo E.
- **Sello de precio como primer filtro:** priorizar las muestras con sello "Sehr guter"/"Buen precio" en el listado para el precio-desde sin abrir fichas.

---

## 🧮 Cálculo del hueco (2 métricas, 17-ago-2026 corregido)

> ⚠️ **Compatibilidad:** el histórico de `modelos-medidos.md` y los umbrales (Nicho ≥10% etc.) usan el hueco BRUTO. El campo `hueco_pct` del mapa usa la MISMA fórmula para que cache, umbrales y comparables sean coherentes. El neto (con costes) es una métrica ADICIONAL.

```
HUECO BRUTO (hueco_pct — compatible con historial y umbrales):
  hueco_pct = (mediana_es − mediana_de) / mediana_es × 100

  Verificación contra histórico: Astra OPC 2012 → (15200−10500)/15200 = 30,9% ✓ · Astra 2013 → (16400−12400)/16400 = 24,4% ✓

HUECO NETO (hueco_neto_pct — con costes de importación, para decidir negocio):
  coste_importacion = transporte 900 + ausfuhr 114 + ITV 115 + IEDMT_estimado   (sin honorarios)
  precio_puesto_huelva_de = mediana_de + coste_importacion
  hueco_neto_pct = (mediana_es − precio_puesto_huelva_de) / mediana_es × 100

  hueco_neto > 0 → comprar en DE y traer sale mejor
  hueco_neto ≈ 0 → paridad real (el bruto puede ser positivo pero sin negocio)
  hueco_neto < 0 → sale mejor ES
```

> **Uso:** `hueco_pct` para comparar con umbral Nicho ≥10% (EXIT 1 si <8%) y con el histórico. `hueco_neto_pct` para el veredicto de negocio. Ejemplo real: Golf GTI bruto 11,3% pero neto ~5% — el bruto pasa umbral, el neto dice que la importación apenas deja margen.
> IEDMT según CO₂ y antigüedad: ver `importacion-vehiculos/04-negocio/costes.md` §IEDMT (coeficientes Anexo IV + tipos por emisiones). Para el estudio se usa un IEDMT estimado por segmento; el exacto se calcula en Flujo A cuando hay unidad concreta.

---

## 📄 Métricas por modelo (qué se guarda)

Para cada modelo/versión, el mapa guarda (esquema completo en `schema_datos_mercado.md`):

1. `slug` + `alias` — clave canónica y variantes de lookup (L1).
2. `categoria` + `categorias_secundarias` — categoría principal única + secundarias (L5).
3. `oferta_de` / `oferta_es` — nº de anuncios (tamaño del mercado).
4. `mediana_de` / `mediana_es` — precio de referencia (mobile.de / Coches.net).
5. `precio_desde_de` / `precio_desde_es` — suelo verificado.
6. `hueco_pct` — hueco de importación BRUTO (compatible con historial/umbrales).
7. `hueco_neto_pct` — hueco con costes de importación (veredicto de negocio) + `coste_importacion_estimado` / `iedmt_estimado`.
8. `rotacion_dias_de` / `rotacion_dias_es` — días en stock SEPARADOS (L9; ES a menudo null) + `rotacion_fuente`.
9. `demanda_trends` — tendencia Google Trends (creciente/estable/decreciente).
10. `transferencias_mes_dgt` / `matriculaciones_kba` — estadísticas Capa 1 (L8).
11. `veredicto` — 🟢/🟡/🔴 según criterio de su categoría.
12. `mejor_mercado` — DE / ES / paridad.
13. `fuente_medicion` — estudio / flujo_b / flujo_a / flujo_e_delta (L3).
14. `confianza_precio` (1-5) y `oportunidad` (chollo) — calidad del dato + alertas.
15. `nota` — matices (motorizaciones no comparables, pendiente de doble pasada...).
16. `tasacion_pro` — (Capa 3, opcional) precio de tasación DAT/Eurotax.
17. `refrescar_antes_de_categoria` — caducidad según su categoría (L7: +2/+3/+4 sem).

---

## 🔄 Cadencia de refresco

- **Por categoría (L7), no global:** showstoppers rotan rápido → **2 semanas** · alta rotación → **3 semanas** · gemas estables → **4 semanas**.
- **Delta:** en el refresco, re-consultar SOLO lo caducado; los modelos estables se revalidan con 1 lectura, no 4.
- **Trigger manual:** el usuario pide "actualiza el estudio de mercado" o antes de una campaña de stock.
- **Cruce con `importacion-vehiculos`:** su PASO 0 mira `refrescar_antes_de_categoria` de cada modelo → si caducó, ofrece refresco antes de usarlo.
- **Categoría sin datos:** declararlo explícitamente ("categoría X sin estudio → ofrecer delta"), nunca dejarlo en silencio.

---

## 🧠 APRENDIZAJE AUTOMÁTICO (17-ago-2026)

> La comunicación con la skill hermana debe volver la información más precisa con cada uso. Tres mecanismos automáticos:

1. **Calibración de veredictos.** Cuando `importacion-vehiculos` cierra un encargo, puede contrastar el veredicto del mapa con el resultado real (ej. mapa 🟢 pero la venta no cuajó, o al revés). Si hay desacuerdo: añadir nota de calibración en el modelo y, si se repite 3+ veces, proponer ajustar umbrales (hueco ≥10% / confianza).
2. **Feedback de oportunidades (chollos).** Si la skill hermana detecta en un anuncio real un `precio_desde_de` >15% por debajo de `mediana_de` con veredicto verde → marca `oportunidad: true` en el mapa al volcar la medición.
3. **Recomendación de próximo estudio.** Al refrescar, priorizar la categoría cuya caducidad es más inminente (la lista `refrescar_antes_de` por categoría lo indica): "la próxima pasada toca showstoppers (caduca 2026-08-31)".
4. **Cross-trigger de modelos nuevos.** Si `importacion-vehiculos` busca un modelo que NO está en el mapa (sin match de `slug`/`alias`), se registra como candidato pendiente de estudio en la `nota` del mapa (o se añade con `fuente_medicion: flujo_a` y medianas null) → la próxima pasada de estudio lo mide.
5. **Bucle de veredicto humano ↔ SaaS (17-ago-2026).** El admin de Laravel (`/mercado/admin`) permite corregir un veredicto y lo marca `veredicto_fuente: humano`. Al escribir/refrescar el mapa, RESPETAR esos veredictos corregidos (no sobrescribir) y conservar la fuente. El comando `market:export` devuelve el JSON actualizado (con `veredicto_fuente`) para que esta skill lo relea como base. La corrección humana es la calibración final: gana sobre la IA.

---

## ⛔ Reglas duras heredadas (de `importacion-vehiculos`)

- **A2** mobile.de SIEMPRE para precio DE · **A8** AS24 NUNCA para precio (solo contar).
- **A11/A12** paginar o usar bandas; página 1 sola sesga hacia lo barato.
- **A15** navegación real SIEMPRE; snippets de búsqueda web NO valen como sondeo.
- **A17** listado-first; no abrir fichas en el estudio (solo 1-2 muestras).
- **A19** no acotarse a los ejemplos: explorar TODO el segmento.
- **A21** todo dato lleva su enlace/fuente.
- **L6 · El mapa asesora, el usuario decide (17-ago-2026):** si el veredicto del mapa es 🔴 pero el usuario quiere el modelo igual (ej. marketing visual), avisar en 1 línea y ejecutar. Nunca bloquear ni insistir.
- **L3 · fuentes de medición:** `estudio` (esta skill), `flujo_b`, `flujo_a`, `flujo_e_delta` (skill hermana). Las medianas solo las escriben estudio/flujo_b; flujo_a solo añade/nota/enlaces.

---

## 📤 Output

| Archivo | Formato | Destino |
|---|---|---|
| `datos_mercado.json` | JSON (mapa de mercado persistente) | **RUTA PACTADA** `C:/Users/jacar/Desktop/JJImportMotors/datos_mercado.json` (L2) |
| `informe_mercado_<fecha>.md` | Markdown (resumen para el usuario) | `informes\mercado\` |

> El `datos_mercado.json` es la **fuente de verdad de criterio** para la skill hermana y el **origen de datos del SaaS Laravel** (comando `market:import`). Mantenerlo al día es la misión de esta skill.

---

## 📚 Referencias

- Esquema del mapa: `schema_datos_mercado.md`
- Fuentes de datos (Capas 1/2/3): `fuentes_datos.md`
- Costes/IEDMT: `importacion-vehiculos/04-negocio/costes.md`
- Trampas de portales: `importacion-vehiculos/memoria/trampas-encontradas.md`
- Playbook de filtrado: `importacion-vehiculos/02-flujos/playbook_filtrado.md`

---

## 🚀 MEJORAS v2 — habilidades y bucle con el SaaS (17-ago-2026)

1. **Informe de estudio por marca (plantilla).** Cuando el estudio es `por_marca`, el informe sigue una plantilla fija: resumen de la marca → modelos estudiados (tabla con hueco/veredicto/vendibilidad) → mejor candidato por segmento → notas por modelo. Guardar en `informes\mercado\<marca>_<fecha>.md`.
2. **Priorización automática del estudio.** En FASE 0, si el usuario no acota, recomendar estudiar lo más caducado primero (leer `refrescar_antes_de` por categoría del JSON): "la siguiente pasada toca showstoppers (caduca el 31-08)". No preguntar a ciegas.
3. **IEDMT estimado por tramo de CO₂.** Para el hueco neto, estimar IEDMT por segmento/motorización con la fórmula de `importacion-vehiculos/04-negocio/costes.md` §IEDMT (coef. Anexo IV + tipo por emisiones) y guardarlo en `iedmt_estimado`. El exacto se calcula en Flujo A con la unidad concreta.
4. **Modo "solo validar".** Para un modelo con cache reciente, refresco ultraligero: 1 lectura por portal (mobile.de/Coches.net), confirmar que mediana y hueco siguen en rango, actualizar `refrescar_antes_de_categoria` y NO re-buscar. Ahorro máximo en el delta.
5. **Fotos de muestra por modelo.** En FASE 4, recoger 1-2 URLs de foto reales (nunca capturas) por modelo top y guardarlas en `foto_url` → el catálogo del SaaS no arranca sin imagen. (Requiere abrir la ficha SOLO de esos modelos top — excepción puntual a A17.)
6. **Tendencias.** El comando `market:export` del SaaS devuelve `historial` (últimas 5 mediciones) por modelo. Al refrescar, comparar la mediana actual con la anterior: si sube/baja >8%, anotarlo en `nota` y en el informe (señal de mercado, no solo foto del día).
7. **Consulta opcional al SaaS.** Si el usuario tiene el SaaS conectado, `importacion-vehiculos` puede leer `/api/market` (token) en vez del JSON local como fuente del mapa. El JSON local sigue siendo la fuente por defecto.
8. **Calibración con correcciones humanas.** Los veredictos corregidos en `/mercado/admin` (`veredicto_fuente: humano`) son la señal de calibración: si la IA marca 🟢 y el humano lo corrige a 🔴 repetidamente en un segmento, ajustar el peso de ese criterio en futuras pasadas. Documentar cada ajuste en `../importacion-vehiculos/memoria/mejoras-aplicadas.md`.
