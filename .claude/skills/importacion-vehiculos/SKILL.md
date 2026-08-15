---
name: importacion-vehiculos
description: >
  Negocio JJ Import Motors (Huelva): servicio de búsqueda e importación de coches
  (desde Alemania y dentro de España). NO compra stock, solo oferta el servicio
  con honorarios fijos. Cuatro flujos: UNIDAD (URL concreta), MODELO (buscar un modelo),
  MERCADO (escanear oportunidades), DESCUBRIMIENTO (cliente sin modelo: sondear
  modelos/motorizaciones que caben en presupuesto y embudar a MODELO).
  Usa 7 fuentes. Genera ZIP para Laravel.
triggers:
  - evalúa este coche
  - evalúa este anuncio
  - mira este mobile.de
  - busca golf gti
  - busca modelo
  - qué hay de mercedes
  - que merece la pena
  - qué oportunidades hay
  - escanea el mercado
  - dime 10 modelos rentables
  - busca para cliente
  - cliente quiere un coche
  - qué modelos caben en el presupuesto
  - revisa en qué mercado es mejor opción
  - qué modelos y motorizaciones
  - calcular coste importacion
  - iedmt
  - precio maximo de compra
---

# Búsqueda e importación de vehículos — JJ Import Motors

Localizar coches (desde Alemania y dentro de España) y **ofertar el servicio de importación/gestión** a clientes. **NO compramos stock** — solo honorarios por el servicio. El cliente es quien compra el coche.

> 📁 **Compañeros:** `navegacion_real.md` (MÉTODO PREFERIDO — navegar como humano) · `paginas_reales.md` (estructura REAL capturada de los 7 portales) · `playbook_filtrado.md` (técnicas de filtrado/búsqueda para Claude Desktop) · `extractores.md` (URLs, trampas, diccionario) · `contrato.md` (JSON + esqueleto) · `operaciones.md` (carpetas, scripts) · **`anti_patrones.md`** (reglas duras 14)
> 
> 📚 **Módulos especializados:** `comparables.md` (ajuste 9 claves) · `costes.md` (IEDMT + desglose) · `riesgos.md` (motores problemáticos) · `operaciones_cierre.md` (cierre + KPIs + sync)
>
> 📄 **Informes ( outputs finales):** `informe_tecnico.md` (análisis interno 15 secciones + score 0-100) · `dossier_cliente.md` (PDF profesional para cliente, 15 secciones, genera confianza)
>
> 🧠 **Memoria persistente (12-ago-2026):** `MEMORIA.md` (léeme primero) · `memoria/modelos-medidos.md` · `memoria/vendedores-confianza.md` · `memoria/trampas-encontradas.md` · `memoria/mejoras-aplicadas.md`
>
> 🎯 **Briefing de encargo (12-ago-2026):** `briefing_encargo.md` — preguntas previas OBLIGATORIAS antes de navegar (año mín, km máx, presupuesto, potencia si tope de gama). Ahorra tokens y evita re-búsquedas.

---

## ⚡ REFERENCIA RÁPIDA

```
Umbrales objetivo: Nicho ≥10% | Rotación ≥10% | Tramo 8-14k ≥12%
Umbrales mínimos (EXIT 3): Nicho 8% | Rotación 10% | Tramo 8-14k 12%
Costes fijos: ver `costes.md` (transporte + ausfuhr + ITV + honorarios) — §1.4 single source of truth
Costes fijos: Transporte 900€ + Ausfuhr 114€ + ITV 115€ + Honorarios 1.500-2.250€
Fuentes: 7 (Wallapop, Milanuncios, Coches.net, mobile.de, AS24.de, AutoUncle, kleinanzeigen.de)
Método: navegación real estilo humano SIEMPRE primero → ver `navegacion_real.md`
Playbook de filtrado: `playbook_filtrado.md` · estructura real: `paginas_reales.md`
Trampas top 3: countryCode SIEMPRE | navegación real primero (screenshot+clic), degradado si no se ve | mobile.de directo NUNCA saltar
Anti-patrones bloqueados: 16 (A1-A16, ver §Anti-patrones)
Camino fijo: waypoint 📍 en cada mensaje · desviaciones = misión lateral con retorno ↩⃾ (A14)
Micro-plan 3-5 líneas antes de CADA búsqueda · cuaderno de sesión en informes\_sesion\
Checkpoints: CP-D tras informe MODELOS (elegir modelos) | CP1 tras informe MODELO (esperar elección de candidato) | CP2 tras comparable | CP3 antes de veredicto
Flujo D (cliente sin modelo): sondeo modelos ES+DE → informe MODELOS (país×año×motor) → embudo a B
Origen DE vs ES: si no se especifica, buscar en ambos mercados y comparar dónde sale mejor → costes.md §Origen
Briefing encargo: preguntar críticos ANTES de navegar → `briefing_encargo.md`
Tope de gama: doble pasada por kW SIEMPRE → `playbook_filtrado.md` §Doble pasada
```

---

## 🎯 LOS 4 FLUJOS — leer PRIMERO

| Flujo | Disparador | Profundidad | Output | ZIP Laravel |
|---|---|---|---|---|
| **A: UNIDAD** | URL pegada o "evalúa este" | Fase 1 + Fase 2 | Informe UNIDAD (15 sec) + dossier + folleto | ✅ Sí |
| **B: MODELO** | "busca [modelo]" sin URL | Fase 1 + Fase 2 si pasa | Informe MODELO + top 5 | ❌ No |
| **C: MERCADO** | "qué merece la pena", "top modelos" | Solo Fase 1, N modelos | Informe BUSQUEDA | ❌ No |
| **D: DESCUBRIMIENTO** | Cliente SIN modelo concreto (presupuesto + requisitos: año/km/cv/combustible) | Sondeo barato ES+DE — SOLO modelos y motorizaciones, sin anuncios | Informe de MODELOS por país × año | ❌ No |

> **CASCADA (12-ago-2026):** Flujo B **nunca** salta a "¿evalúo el candidato X?" sin entregar antes el INFORME MODELO + top 5 con enlaces + CP1. El usuario elige el candidato → **se convierte a Flujo A** → ahí sí: informe UNIDAD + dossier + folleto + ZIP. Los informes NO salen todos a la vez, son en cascada con checkpoint entre fases.

> **🌍 ORIGEN DE vs ES (12-ago-2026):** el encargo puede ser de un coche de **Alemania** (importación) o de **España** (compra nacional). Si el usuario NO especifica origen, buscar el modelo en **AMBOS mercados** y comparar **dónde sale mejor** (coste total puesto en Huelva). El origen ganador determina los costes: DE = transporte+ausfuhr+ITV import+IEDMT; ES = sin esos costes. Ver `costes.md` §Origen.

### Detección automática de flujo

```
¿Hay URL en el input?
├── SÍ → FLUJO A (UNIDAD)
├── NO ↓
¿Hay modelo+versión concreto?
├── SÍ → FLUJO B (MODELO)
├── NO ↓
¿Hay CLIENTE con presupuesto y requisitos (año/km/cv/combustible)?
├── SÍ → FLUJO D (DESCUBRIMIENTO) — sondear modelos que caben y embudar
├── NO ↓
→ FLUJO C (MERCADO) — preguntar preferencias al usuario
```

### 🔍 FLUJO D · DESCUBRIMIENTO — cliente sin modelo (15-ago-2026)

> **El problema que resuelve:** el cliente trae presupuesto y requisitos pero NO modelo ("9.000 € todo incluido, 2016+, gasolina, +120cv, 5p, ¿qué mercado es mejor?"). El usuario tampoco sabe qué pedir. Navegar directo a anuncios reales quema peticiones y sesga (caso María 15-ago). El Flujo D particiona la búsqueda en un **embudo de 3 pasos**.

**D1 · Sondeo de modelos (barato, sin anuncios) — navegación real OBLIGATORIA (A15):**
- **MÉTODO: navegación real SIEMPRE** (Coches.net ES + mobile.de DE) con los filtros del encargo. La **búsqueda web** (snippets de Google/agregadores) está **PROHIBIDA como sondeo** (A15): da cifras inconsistentes y contradice lo verificado con navegación real. Degradado solo si el portal está bloqueado, declarando reintentos (A2/A7).
- **El sondeo es por FILTROS, no por modelo (A16):** una pasada con los filtros del encargo (año, km, combustible, potencia mínima, precio ≤ techo por origen según M1/M2/M3) devuelve **TODOS** los modelos/motorizaciones que caben. Se listan **TODOS los que salen**; prohibido elegir 3-4 a mano ni dejar "otros por explorar" sin sondear.
- **Potencia = filtro MÍNIMO (≥Xcv):** versiones 125/130/150 valen igual si cumplen el mínimo. Nunca sondear solo la variante tope (ej. buscar el León 150cv y descartar el 125cv).
- **Rango de año APROBADO se respeta (A13):** si el usuario amplió (2016→2012), se filtra con el ampliado, no el estricto.
- **Eficiencia — D1 en dos sub-pasadas, sin paginar (D1a enumera, D1b afina):**
  - **D1a · ENUMERAR modelos (solo nombres, 2 lecturas por mercado):**
    1. **Lectura asc (suelo):** filtros del encargo + orden precio asc → leer SOLO página 1 → modelos que caben holgado 🟢.
    2. **Lectura desc (techo):** mismos filtros + orden precio desc → leer SOLO página 1 → modelos que caben justo 🟡. **Con asc+desc se cubre TODO el rango en 2 páginas**, sin paginar hasta el techo.
    3. **Facetas de marca/modelo con conteo** (si el portal las muestra en el lateral): anotar marcas/modelos con nº de resultados — enumera el mercado completo sin abrir anuncios ni paginar.
    4. **Semilla de modelos (`memoria/modelos-medidos.md`):** el segmento (gasolina compacto +120cv, ≤150k km…) tiene una lista finita conocida (~10-15 modelos). Partir de ella para no redescubrir el mercado; el barrido solo añade modelos raros.
  - **D1b · PRECIO-DESDE (diferido, solo si falta):** el precio exacto por modelo NO se necesita en la primera pasada. Solo para los modelos que D1a dejó sin precio claro → 1 consulta por modelo (marca+modelo + orden asc + página 1). El nº de resultados de la faceta ya es señal de interés: no abrir fichas ni leer más de 1 página.
  - **El anuncio individual solo se investiga cuando el embudo es pequeño** (Flujo A/B): en D1 el anuncio concreto no aporta nada — solo interesa modelo + precio-desde + nº de oferta.
- Se recopila: nombre de modelo + motorización + precio-desde verificado + nº resultados. **NO fichas, NO vendedores, NO fotos, NO anuncios individuales.**
- Presupuesto: ~4-6 peticiones por mercado (2 de D1a + 1-2 de D1b + facetas/semilla). La paginación completa es de Flujo B.

**D2 · INFORME DE MODELOS (entregable del Flujo D):**
- Organizado **por país × año × motorización**, con veredicto de encaje por modelo:
  - 🟢 **Cabe holgado** — precio-desde bien por debajo del techo
  - 🟡 **Cabe justo** — solo unidades altas de km o acabado base
  - 🔴 **No cabe** — fuera de techo en ambos mercados (listado igualmente, para descartar y no volver a mirar)
  - 🇩🇪/🇪🇸 **Mejor mercado** — con la nota de si la diferencia cubre los costes fijos de importar
- Guardar en `informes\<segmento>\informe_modelos_<fecha>.md` (si hay marca candidata) o `informes\descubrimiento\<cliente>_<fecha>.md`.
- **CP-D: entregar y ESPERAR.** El usuario elige 2-3 modelos → cada uno pasa a Flujo B.

**D3 · Embudo (particionar y disminuir):**
```
D sondeo → INFORME DE MODELOS (país × año × motorización)
              └ usuario elige 2-3 modelos
                  ↓ cada modelo → FLUJO B (7 fuentes, candidatos reales con enlaces)
                      └ usuario elige candidato → FLUJO A (unidad + dossier + ZIP)
```
- Cada nivel gasta MÁS peticiones que el anterior y estrecha el conjunto: sondeo (8) → Flujo B (15-50) → Flujo A (35-70). Nunca al revés.
- Si el usuario pide "investiga estos modelos" → Flujo B en serie, en el orden que dio.
- El sondeo D1 se guarda en `memoria/modelos-medidos.md` (fecha + techos) para reutilizar en futuros encargos similares.

**Reglas duras del Flujo D:**
1. En D1 NO se abren fichas de anuncios individuales — es sondeo de modelos, no búsqueda de candidatos.
2. El INFORME DE MODELOS NO es un listado de anuncios: sin enlaces a unidades concretas, solo modelos/motorizaciones con precio-desde.
3. No se pasa a Flujo B sin que el usuario elija modelos (CP-D).
4. **D1 SIEMPRE con navegación real (A15).** La búsqueda web/snippets NO es método válido de sondeo — datos inconsistentes (caso 15-ago: Focus ES "~9.900 €" cuando la navegación real daba 3.000-6.990 €).
5. **El informe D2 lista TODOS los modelos** que salen con los filtros (A16), sin "otros por explorar" pendientes: si un modelo cumple las specs, se sondea en la misma pasada.
6. **El requisito de potencia es mínimo (≥Xcv):** filtrar por kW/cv mínimos, no buscar una variante concreta; versiones 125/130/150 cumplen +120cv.
7. **D1 NO pagina (eficiencia):** D1a enumera con 2 lecturas por mercado (asc = suelo + desc = techo) + facetas de marca + semilla `modelos-medidos.md`; D1b difiere el precio-desde a 1 consulta por modelo solo si falta. El anuncio individual solo se investiga cuando el embudo es pequeño (Flujo A/B).

**Antes de navegar en Flujo A/B → briefing de encargo (`briefing_encargo.md`):**
1. Extraer parámetros dados (modelo, año mín, km máx, presupuesto...).
2. Preguntar SOLO los críticos que falten (tabla de faltantes) + **modalidad de honorarios M1/M2/M3**.
3. Si es tope de gama → confirmar potencia (activa doble pasada).
4. Guardar encargo en memoria al cerrar.
> Fallo real 12-ago: se navegó sin preguntar potencia → se perdió el OPC de 8.999 € mal etiquetado.

**📋 PLAN DE BARRIDO previo — encargos ABIERTOS sin URL (15-ago-2026):**
> Cuando el usuario pide "revisa qué hay / qué modelos / qué mercado es mejor" SIN modelo concreto (mucha libertad), eso es **FLUJO D**: primero el sondeo de modelos (D1) + INFORME DE MODELOS (D2), y el Flujo B solo con los modelos que el usuario elija. El plan de barrido se muestra igualmente (mercados, filtros, bandas, techo por origen) pero el entregable inmediato es el INFORME DE MODELOS, no candidatos. Si ya hay modelo concreto pero libertad de cómo buscar, el plan de barrido aplica al Flujo B directo. Fallo real 15-ago (María, 9.000 €): se navegó a anuncios reales sin modelo elegido y se entregó un medio informe PARCIAL.
>
> Con los parámetros del briefing cerrados, mostrar el plan en 5-8 líneas y pedir OK:
> 1. **Mercados** (ES + DE) con techo de compra por origen.
> 2. **Filtros exactos**: año, km, combustible, cv, puertas, precio máx.
> 3. **Bandas de precio** a recorrer (ej. 3-5k / 5-7k / 7k-techo) — no solo el suelo (A12).
> 4. **Cobertura**: fuentes de Fase 1 y nº de páginas por fuente.
> 5. **Entregable esperado**: informe de búsqueda con N candidatos en TODO el rango.
>
> Con el OK → ejecutar sin volver a preguntar. El plan sustituye al briefing cuando no falta ningún parámetro.

**🛠️ Prompt Improver (12-ago-2026) — refinar prompts vagos:**
> Antes de ejecutar, detectar si el prompt del usuario es vago y proponer uno MEJOR con briefing completo. Detalle en `guia_prompts.md`.

**Reglas rápidas:**
- **<50 chars** → probablemente vago → mejorar
- **50-200 chars** → revisar si tiene 3+ parámetros
- **>200 chars** → complejo, preguntar solo si falta crítico
- **NUNCA preguntar más de 4 cosas a la vez**
- **SIEMPRE** permitir "busca tú" / "lo que puedas"
- **SIEMPRE** mostrar prompt mejorado listo + pedir confirmación

**Ejemplo de mejora:**
```
Usuario: "busca GTI"

Claude responde:
Casi lo tengo. Solo falta:
  • Versión (¿GTI / GTI Performance / GTI Clubsport?)
  • Presupuesto máximo
  · Finalidad (¿personal / reventa?)

Prompt mejorado:
  "VW Golf GTI 7.5 Performance 2020+, presupuesto 35k puesto en Huelva,
   km máx 80.000, automático DSG, para reventa"

Si OK, ejecuto (~50 capturas).
```

En caso de duda: **preguntar antes de gastar tokens**.

---

## ⛔ ARRANQUE OBLIGATORIO — Tabla de cobertura 7 fuentes

> Fallo real 10-ago-2026: se midieron 2 fuentes, se dieron 5 candidatos y veredicto. Un anuncio de 8.999 € en mobile.de ganaba por >3.000 €.

**Rellenar ANTES de cualquier candidato, mediana o veredicto:**

| # | Fuente | País | Fase 1 (sondeo) | Fase 2 (profunda) |
|---|---|---|---|---|
| 1 | Wallapop | ES | ❌ | ✅ |
| 2 | Milanuncios | ES | ❌ | ✅ |
| 3 | Coches.net | ES | ✅ | ✅ |
| 4 | mobile.de | DE | ✅ | ✅ |
| 5 | AutoScout24.de | DE | ❌ | ✅ |
| 6 | AutoUncle | DE | ✅ | ✅ |
| 7 | kleinanzeigen.de | DE | ❌ | ✅ |

**Flujo A y B:** Fase 1 rellena 3 filas (Coches.net, mobile.de, AutoUncle). Fase 2 rellena las 4 restantes.
**Flujo C:** Solo Fase 1 por modelo (3 filas). No hay Fase 2.

Estados: `OK` · `0 resultados` · `bloqueada (motivo + intentos)` · `sin extractor`. Fuente sin cubrir → informe marcado **PARCIAL**.

**4 reglas duras:**
1. **No parar al tener candidatos.** Se recorren TODAS las fuentes y luego se ordena.
2. **Fuente bloqueada → reintentar.** Primero navegación real (recarga + espera + clic en filtros) → si captcha, 1-2 reintentos → método técnico de `extractores.md` → solo entonces bloquear.
3. **Método degradado se declara.** Si Coches.net no muestra la tasación/rotación en pantalla (están en estado JS inaccesible) y se lee solo el texto visible, decirlo.
4. **COBERTURA COMPLETA OBLIGATORIA (12-ago-2026).** Se intentan SIEMPRE las 7 fuentes, ni más ni menos. NO dar cifras ni veredicto con <7 sin marcar el informe **PARCIAL** y preguntar al usuario. Fuente degradada/bloqueada se declara con sus intentos; nunca "no la miré" sin documentar el reintento.

**🔎 Para qué sirve cada fuente (fiabilidad):**

| Fuente | Rol | Fiabilidad precio | Nota |
|---|---|---|---|
| **mobile.de** | Precio DE (REFERENCIA) | 🟢 Alta | NUNCA saltar (A2). Doble pasada en tope de gama. |
| **Coches.net** | Precio ES (REFERENCIA) | 🟢 Alta | Mediana + tasación + rotación. |
| **AutoUncle** | Rotación DE (días publicado) | 🟡 Solo contar | Agregador. NO es referencia de precio. |
| **AutoScout24.de** | CONTAR oferta DE | 🔴 NO precio | NUNCA dar cifras de precio con AS24 (agrega feeds sin cribar → anuncios engañosos). Solo para N de ofertas. |
| **kleinanzeigen.de** | Chollos particulares DE | 🟡 Precio + VB | Verificar VB (negociable). |
| **Wallapop** | Chollos particulares ES | 🟡 Precio negociable | También compra nacional. |
| **Milanuncios** | Chollos particulares ES | 🟡 Precio negociable | También compra nacional. |

**Regla de oro:** 2 fuentes de precio fiables (mobile.de DE + Coches.net ES). El resto complementa (oferta, chollos, rotación) pero NUNCA sustituye a las 2 de referencia.

**Regla dura #4 — DOBLE PASADA por potencia (12-ago-2026):**
> Si la versión buscada es un **tope de gama / acabado especial** (`OPC`, `GTI`, `R`, `M`, `AMG`, `RS`, `Type R`, `N`, `Performance`...), el filtro por variante de texto NO es suficiente — se pierde coches genuinos mal etiquetados (caso real: OPC 8.999 € con título "Opel Astra"). SIEMPRE hacer la búsqueda 2 por **kW** (campo estructurado del permiso) y cruzar por unión de IDs. Ver `playbook_filtrado.md` §Doble pasada. Trampa documentada en `memoria/trampas-encontradas.md`.

Para Alemania, orden: mobile.de directo → AutoScout24.de directo → AutoUncle (NUNCA única) → kleinanzeigen.de.

---

## � EL CAMINO — mapa fijo de pasos + protocolo de desviación (15-ago-2026)

> **Objetivo: cero ambigüedad sobre en qué punto del flujo estamos y qué falta.** Cada flujo es una secuencia NUMERADA. En cada mensaje, Claude declara su posición con un waypoint; si el usuario desvía, se responde y se RETOMA.

### Los mapas (una línea por paso)

```
FLUJO D: 1 briefing+cuaderno → 2 micro-plan sondeo → 3 sondeo ES+DE → 4 INFORME DE MODELOS
          → CP-D (usuario elige 2-3 modelos) → cada modelo entra en FLUJO B

FLUJO B: 1 briefing+cuaderno → 2 micro-plan Fase 1 → 3 Fase 1 (3 fuentes) → 4 INFORME MODELO+top5
          → CP1 (usuario elige candidato) → 5 Fase 2 (7 fuentes) → 6 micro-plan fichas → 7 INFORME UNIDAD
          → CP3 veredicto → dossier → ZIP (→ FIN)

FLUJO A: 1 briefing+cuaderno → 2 micro-plan → 3 Fase 1+2 → 4 INFORME UNIDAD → CP3 → dossier → ZIP
```

### Protocolo de waypoint (en cada mensaje)

```
📍 Camino: Flujo B · paso 4/7 — entregando INFORME MODELO
```

### Protocolo de desviación — misiones laterales

- El usuario pregunta algo fuera del paso actual → es una **misión lateral**: se responde y se declara el retorno:
  `↩️ Respondido. Vuelvo al paso 3 (sondeo Coches.net).`
- El usuario cambia el destino real (otro modelo, otro cliente, otro mercado) → **cambio de camino** declarado:
  `🔀 Cambio de camino: nuevo Flujo B (Golf GTI), paso 1.`
- **PROHIBIDO** abandonar el camino en silencio (A14): si tras una desviación el informe de fase no llegó, es un fallo.

---

## 📋 MICRO-PLAN antes de CADA búsqueda — no solo la primera (15-ago-2026)

> **Regla dura:** ninguna ronda de navegación empieza sin micro-plan aprobado. El plan inicial (§PLAN DE BARRIDO) cubre el arranque; este protocolo cubre TODAS las búsquedas siguientes. Preguntar mucho está BIEN: cada OK del usuario es una corrección barata (1 línea) frente a una búsqueda cara (10-40 peticiones).

**Formato del micro-plan (3-5 líneas, en el chat):**
```
📍 Camino: Flujo B · paso 3
📋 Siguiente búsqueda: Coches.net, págs 2-5 del listado ordenado por precio
   Filtros: ≤8.850 € · ≥2016 · ≤150k km · gasolina · ≥120cv
   Objetivo: completar la banda 5-8k (A12) · ~6 peticiones
   ¿OK?
```

**Cuándo hace falta micro-plan nuevo:**
- Al cambiar de fuente, de mercado o de banda de precio.
- Al cambiar CUALQUIER filtro o el techo (A13: se declara el cambio).
- Al pasar de fase (sondeo → fichas → informes).
**Cuándo NO hace falta (lote ya aprobado):** el mismo listado, la página siguiente, la misma banda. Se ejecuta y se informa al terminar el lote.

---

## 📓 CUADERNO DE SESIÓN — aprendizaje en vivo (15-ago-2026)

> **El problema:** las correcciones del usuario ("no me cuentes honorarios", "prefiero concesionario", "sin Canarias") se aplicaban una vez y se perdían dentro de la sesión; el aprendizaje solo existía al cierre. El cuaderno lo arregla.

**Archivo:** `informes\_sesion\sesion_<fecha>_<encargo>.md` — se crea en el briefing y se actualiza EN EL MOMENTO.

```markdown
# 📓 Sesión 2026-08-15 — Tiguan cliente María
## Parámetros fijados (fuente de verdad de la sesión)
- Presupuesto 18-20k M1 · 2017+ · ≤160k km · gasolina · 5p · tarifa ES reducida 500 €
## Correcciones del usuario (se aplican YA)
- [12:15] "quita los de Canarias" → filtro IGIC activo desde ya
- [12:40] "prefiero concesionario" → priorizar profesional en ranking
## Preferencias detectadas (no dichas, inferidas)
- Valora equipamiento (4Motion/DSG) por encima de km bajos
## Pendiente al cierre
- Volcar correcciones a memoria/preferencias · registrar trampa financiado-vs-contado
```

**Reglas:**
1. Toda corrección del usuario entra al cuaderno CON hora y se aplica de inmediato — no solo en la siguiente búsqueda.
2. El cuaderno se RELEE antes de cada micro-plan (¿algo de aquí cambia el plan?).
3. Al cierre, el apartado "Pendiente" se vuelca a `memoria/` del skill y a `.claude/memoria/preferencias.md`.
4. Si el entorno no permite escribir el cuaderno, se mantiene en el contexto del chat con el mismo formato y se entrega el texto al cierre.

---

## 🧐 AUDITORÍA DE FASE — checklist al completar CADA paso (15-ago-2026)

Al terminar cualquier paso del camino, 4 líneas internas (no se molesta al usuario salvo fallo):
```
□ Entregable del paso guardado en su ruta (§DÓNDE SE GUARDA CADA COSA)
□ Waypoint correcto — no quedó una misión lateral sin retorno (A14)
□ Correcciones del cuaderno aplicadas en este paso
□ Cobertura real declarada (fuentes peinadas/bloqueadas, páginas leídas — A7/A12)
```
Si algo falla → se corrige ANTES de avanzar al siguiente paso. No se acumula deuda de fase.

---

## �🧠 ACTUALIZACIÓN DE MEMORIA — Triggers automáticos

Durante la conversación, Claude debe actualizar la memoria cuando detecte:

| Situación | Archivo a actualizar |
|---|---|
| Mides un modelo nuevo o evalúas una URL | `memoria/modelos-medidos.md` |
| Detectas una trampa nueva en un portal | `memoria/trampas-encontradas.md` |
| Un vendedor responde bien/mal | `memoria/vendedores-confianza.md` |
| Aplicas una mejora al skill | `memoria/mejoras-aplicadas.md` |
| Aprendes algo sobre el usuario (preferencia, disgusto) | `.claude/memoria/preferencias.md` (en el proyecto) |
| Cometes un error que debe evitarse | `.claude/memoria/errores-pasados.md` (en el proyecto) |
| Tomas una decisión con justificación importante | `.claude/memoria/decisiones.md` (en el proyecto) |

**Cuándo actualizar:** en cuanto ocurre (no esperar al final). Al cerrar la conversación, verifica que la memoria está al día.

**Detalles completos:** ver `MEMORIA.md` del skill.

### 🔁 APRENDIZAJE CONTINUO — retrospectiva al cerrar sesión (12-ago-2026)

Al finalizar cada conversación, registrar qué se aprendió (plantilla en `memoria/retrospectiva.md`):

```
SESIÓN <fecha> — <modelo/encargo>
✅ Lo que funcionó:
  · <qué fue bien>
❌ Errores cometidos:
  · <error> → corregido en <archivo>
🧠 Aprendizaje nuevo:
  · <trampa, preferencia, dato de mercado>
📝 Ajustes aplicados:
  · <qué se cambió en el skill>
```

**Regla:** cada conversación debe producir AL MENOS una línea de aprendizaje. Si el usuario detecta un fallo, ese fallo se convierte en regla/anti-patrón/trampa documentado — no en un "lo siento" sin más.

---

## 🔄 FASES — Sistema de dos pasadas

```
FASE 1: SONDEO RÁPIDO                    FASE 2: INVESTIGACIÓN PROFUNDA
Solo Coches.net + mobile.de + AutoUncle  Las 4 que faltan
Solo precios, año, km, versión          Fichas mobile.de, descripciones
15-20 peticiones                        30-40 peticiones
Objetivo: test de hueco (≥15%)          Objetivo: veredicto completo
```

### Token budget consciente (ANTES de empezar)

Al detectar flujo, declara al usuario el presupuesto estimado:

| Flujo | Fase 1 | Fase 2 | Total máx |
|---|---:|---:|---:|
| **A: UNIDAD** | 15-20 | 35-50 | 70 |
| **B: MODELO** | 15-20 | 20-30 | 50 |
| **C: MERCADO** | 12-18 por modelo | — | 100 (7 modelos) |
| **D: DESCUBRIMIENTO** | D1 sondeo 4-8 | — (D2 informe, sin navegar) | 8 + embudo a B (15-50) o A (35-70) |

**Frase de apertura:**
> "Esto gastará ~{N} peticiones. ¿Procedo?"

En Flujo C con muchos modelos, avisar cuando se supera 50% del budget.

### Contador de peticiones (§2.4 — trackear en tiempo real)

Llevar cuenta mental de peticiones por fuente durante la sesión. Avisar al usuario al llegar a umbrales:

```
Contador manual:
  mobile.de:   X / 45 (avisar a 35 = 78%)
  AutoScout24: X / 36 (6 llamadas de 12 máx, pausa 0.7s)
  Coches.net:  X / 35
  Resto:       X / 20

Avisar al 50% del budget total → "Vamos por ~{N} peticiones de {máx}. ¿Sigo?"
Avisar al 80% del budget total → "Ya vamos por {N}. Si no hay hueco claro, paramos."
```

### Optimización de fases (12-ago-2026 · ahorro de tokens)

Técnicas para gastar MENOS tokens sin perder precisión:

**1. Orden de fuentes por valor (Fase 1):**
```
1º mobile.de    → la más rica en DE (precio, sello, fichas) — NUNCA saltar
2º Coches.net   → la referencia ES (mediana, priceRankIndicator)
3º AutoUncle    → rotación (días + % cambio) — solo 1 captura
```
Si en mobile.de + Coches.net ya se ve hueco claro (<8% o >8% decisivo), **AutoUncle se puede omitir en Fase 1** (ahorro ~2 capturas).

**2. Capturas multi-tarjeta (Pareto):**
- 1 captura de página entera = 10-20 tarjetas. NO capturar tarjeta a tarjeta.
- Solo fichas individuales para los 3 mejores candidatos (Flujo A/B).

**3. Reutilizar contexto (NO rebuscar):**
- Los datos ya leídos en Fase 1 viven en el contexto. **No volver a navegar** a por algo ya visto.
- Guardar resultados de búsqueda en `memoria/modelos-medidos.md` para no repetir en futuras sesiones.
- ⚠️ Fallo real 12-ago: Claude rebuscó lo que ya tenía → 2x tokens. Evitar.

**4. Precisar la búsqueda la 1ª vez (evitar re-búsquedas):**
- Aplicar TODOS los filtros críticos del encargo en la PRIMERA búsqueda (año mín, km máx, precio tope).
- Así el listado ya viene filtrado y no hay que repetir con filtros más finos.

**5. Doble pasada solo si aplica:**
- Si el modelo NO es tope de gama → 1 sola búsqueda (ahorra 2-3 capturas).
- Si SÍ es tope de gama → doble pasada por kW (imprescindible, ver `playbook_filtrado.md` §Doble pasada).

**6. Stop temprano (anti-desperdicio):**
- <3 resultados tras filtros duros → relajar filtros (no hacer más capturas de vacíos).
- <5 comparables ES → puede que no haya hueco; avisar antes de Fase 2.
- Hueco <8% → EXIT 1 directo (no entrar en Fase 2).

**7. Caché de encargos:**
- Si el encargo ya está en `memoria/modelos-medidos.md` → mostrar resultado previo + preguntar si refrescar (delta), NO rehacer todo.
- Refresco: solo re-medir las fuentes cambiadas (delta update), no las 7.


**Reglas:**
- **Flujo A:** total máx 70. Avisar a 35 (50%) y a 56 (80%).
- **Flujo B:** total máx 50. Avisar a 25 (50%) y a 40 (80%).
- **Flujo C:** total máx 100 (7 modelos). Avisar al 50% (50) y al 80% (80).
- **mobile.de:** NUNCA >45 en una sesión. Avisar a 35 (regla dura, ver extractores.md §Presupuesto).

**Si se supera el budget sin veredicto:** STOP. Mostrar resumen parcial + preguntar si invertir más o cerrar como PARCIAL.

### Early exits (ABORTAR si se cumple)

| Exit | Condición | Acción |
|---|---|---|
| **EXIT 1** | Hueco <8% O <3 comparables ES | Informe rápido. "No sale." Actualizar `datos_mercado.json`. FIN. |
| **EXIT 2** | Hueco 8-15% | "Justo. ¿Invierto en Fase 2?" PREGUNTAR. |
| **EXIT 3** | Margen < umbral mínimo (Nicho 8%, Rotación 10%, 8-14k 12%) | Informe reducido sin publicidad. Entre umbral mínimo y objetivo (ej: Nicho 8-10%), avisar "margen justo, posible si vendibilidad ≥70". |

### Priorización por ROI (Flujo B y C)

Cuando hay >3 modelos sin medir, aplicar scoring automático **antes** de empezar:

```
PRIORIDAD = MargenEstimado × VendibilidadEstimada × Urgencia
```

| Factor | Cálculo | Ejemplo |
|---|---|---|
| **MargenEstimado** | Ratio histórico del segmento | Nicho: 18%, Rotación: 10% |
| **VendibilidadEstimada** | Atractivo del modelo | Deportivo: 80, Premium: 60, Utilitario: 40 |
| **Urgencia** | ¿Hay cliente esperando? | Cliente concreto: 100, Sin cliente: 30, >1 mes sin medir: +20 |

**Tabla de priorización:**

| Modelo | Segmento | Margen est. | Vend. est. | Urgencia | PRIORIDAD |
|---|---|---:|---:|---:|---:|
| Golf 8 GTI CS | Nicho | 18% | 90 | 50 | **8.100** |
| Mercedes CLA | Rotación | 10% | 65 | 110 | **7.150** |
| BMW M240i | Nicho | 18% | 85 | 30 | **4.590** |
| Volvo XC60 T8 | Nicho | 15% | 70 | 30 | **3.150** |

**Regla:** Antes de cada sesión, puntuar los "sin medir" y proponer el top 3 al usuario.

### Deduplicación entre fuentes

**Problema:** Mismo coche en Wallapop y Milanuncios cuenta 2 veces. Infla la muestra.

**Solución:** Normalizar antes de contar:

```
Para cada coche en el pool:
  huella = (año, km_redondeado(±2%), cv, precio_redondeado(±3%), combustible)

Si huella ya existe → es duplicado → contar 1 vez, anotar fuentes
```

**Output:** "8 coches únicos en España (12 anuncios contando duplicados: 4 en Wallapop, 5 en Milanuncios, 3 en Coches.net)"

**Cuándo aplicar:**
- **Fase 1:** Después de recolectar Coches.net + mobile.de + AutoUncle
- **Fase 2:** Después de recolectar las 4 fuentes restantes
- **Flujo C:** Después de cada modelo escaneado

**Regla:** Si la huella coincide pero el precio difiere >10%, NO es duplicado (puede ser versión distinta).

---

## 📊 TIPOS DE INFORME — uno por flujo

### INFORME TIPO MODELOS (Flujo D) — cliente sin modelo concreto

Sondeo de modelos/motorizaciones que caben en el presupuesto. **Sin anuncios individuales, sin enlaces a unidades, sin vendedores.** Guardar en `informes\descubrimiento\<cliente>_<fecha>.md`.

```markdown
# 🔍 Informe de MODELOS — <cliente> · <fecha>
*Encargo: ≥2016 · ≤150.000 km · gasolina · +120cv · 5p · 9.000 € M3 (sin honorarios)*
*Techo de compra: ES ≈ 8.550-8.850 € · DE ≈ 7.870 € (+1.129 € import)*

## 🇪🇸 España (Coches.net, filtros verificados)
| Modelo / motorización | Años | Precio-desde | Nº uds | Encaje |
|---|---|---|---|---|
| Peugeot 308 1.2 PureTech 130 | 2016-2019 | 6.900 € | 38 | 🟢 holgado |
| Opel Astra 1.4 Turbo 150 | 2016-2018 | 8.200 € | 25 | 🟡 justo |
| Renault Mégane TCe 130 | 2017+ | 10.990 € | 14 | 🔴 no cabe |

## 🇩🇪 Alemania (mobile.de, filtros verificados)
| Modelo / motorización | Años | Precio-desde | Nº uds | Encaje | +import |
|---|---|---|---|---|---|
| Opel Astra 1.4 Turbo 150 | 2015-2018 | 5.972 €* | 210 | 🟢 holgado | 7.101 € |
| VW Golf 1.4 TSI 125 | 2015-2018 | 7.400 € | 480 | 🟡 justo | 8.529 € |

## Mejor mercado por modelo
| Modelo | Mejor mercado | Motivo |
|---|---|---|
| Opel Astra 1.4T | 🇩🇪 DE | hueco ~1.400 € tras importar |
| Peugeot 308 | 🇪🇸 ES | ya cabe sin trámites |

## Siguiente paso
Elige 2-3 modelos y los investigo a fondo (Flujo B: 7 fuentes, candidatos con enlaces).
```

*Precio-desde = mínimo VERIFICADO (no patrocinado, no siniestrado, potencia confirmada). ⚠️ si el mínimo es de rango genérico sin confirmar cv → marcarlo (doble pasada pendiente en Flujo B).*

### INFORME TIPO BUSQUEDA (Flujo C)

Tabla multi-modelo. Cada fila: modelo, segmento, hueco%, N uds DE, vendibilidad estimada, enlace al mejor anuncio. Sin comparable ajustado, sin IEDMT. Solo Fase 1.

**Dimensiones de atractivo** — cuando el usuario da preferencias libres ("algunos atractivos pasionales y otros económicos funcionales"), clasificar hallazgos en:

| Dimensión | Qué busca | Ejemplos |
|---|---|---|
| 🔥 **Pasionales** | Deportivos, icónicos, deseables | GTI, OPC, M, RS, Type R, N |
| 💼 **Premium funcional** | Calidad, comodidad, imagen | Arteon, Clase A, A3, Serie 1 |
| 🛠️ **Económico funcional** | Buen precio, fiable, revende bien | León FR, Octavia, Golf TSI |
| 🌱 **Eco/Etiqueta** | CERO/ECO, PHEV, bajo consumo | GTE, 330e, e-tron, Niro |

El informe BUSQUEDA agrupa por estas 4 dimensiones y el usuario elige cuáles profundizar (Flujo B).

**Variaciones estacionales:** Anotar en §10 si el análisis es en temporada baja (otoño/invierno): "Precio de temporada baja, posible subida 5-8% en primavera". Ver tabla completa en `costes.md`.

### INFORME TIPO MODELO (Flujo B)

**OBLIGATORIO tras la criba de Fase 1 → checkpoint CP1 ANTES de Fase 2.**

- Tabla cobertura 7 fuentes (Fase 2)
- Mediana y cuartil bajo ES + DE
- Vendibilidad estimada (5 factores)
- Top 5 candidatos con enlaces
- Sin desglose por unidad
- Cacheable 2-3 semanas. Delta updates al refrescar.

**NUNCA saltar del resumen informal al "¿evalúo el candidato X?" sin entregar primero el INFORME TIPO MODELO completo. El usuario debe revisar los candidatos con sus enlaces antes de decidir.**

**Plantilla del INFORME TIPO MODELO (entregar SIEMPRE en Flujo B):**

```markdown
# 📋 Informe MODELO — Opel Astra J OPC
*Encargo: ≥2012 · ≤130.000 km · manual · gasolina · 280 cv · buen precio*

## 1. Cobertura de fuentes
| Fuente | País | Estado | N uds | Nota |
|---|---|---|---|---|
| mobile.de | DE | ✅ | 40 | doble pasada (OPC + 271-290 cv) |
| Coches.net | ES | ✅ | 6 | 2 duplicados + 2 H GTC descartados |
| AutoUncle | DE | ⏭️ omitido | — | hueco claro, ahorro (permitido) |
| Wallapop | ES | ⏳ Fase 2 | — | — |
| Milanuncios | ES | ⏳ Fase 2 | — | — |
| AS24.de | DE | ⏳ Fase 2 | — | — |
| kleinanzeigen.de | DE | ⏳ Fase 2 | — | — |

## 2. Precio de mercado
- **ES:** mediana 17.490 € · cuartil bajo 14.845 € · rango 13.790–26.500 € (n=6)
- **DE:** desde 8.999 € (segundo: 10.950 €)

## 3. Hueco detectado
- Mejor DE vs cuartil bajo ES: **-39%** → hueco ALTO (≥15%) → pasa a Fase 2 si OK

## 4. Vendibilidad estimada (5 factores)
| Factor | Valor |
|---|---|
| Demanda | alta (deportivo nicho, 6 uds ES) |
| Oferta DE | 40 uds → rotación media |
| Competencia | baja (solo 6 uds en ES) |
| Urgencia cliente | personal |
| Estacionalidad | estable |

## 5. 🏆 Top 5 candidatos (con ENLACES)
| # | Precio | Año | Km | Vendedor | Enlace |
|---|---|---|---|---|---|
| 1 | 8.999 € | 10/2012 | 106.000 | Particular | [mobile.de](URL) |
| 2 | 10.950 € | 05/2014 | 129.000 | Concesionario | [mobile.de](URL) |
| ... | | | | | |

## 6. Coste puesto en Huelva (mejor candidato)
≈ 12.400–13.700 € (transporte + ausfuhr + ITV + honorarios + IEDMT)

---
**Checkpoint CP1:** Entregar el informe MODELO y **esperar la instrucción del usuario** (él elige candidato). NO preguntar "¿qué candidato investigo?" — si el encargo está completo, el usuario decide por iniciativa propia y desde ahí todo es automático. Ver §MODO AUTOMÁTICO.
```

### CASCADA DE INFORMES — qué sale y cuándo (12-ago-2026)

```
ENCARGO (Flujo B: MODELO)
│
├─ Fase 1 (3 fuentes) → 📋 INFORME MODELO + top 5 con ENLACES
│                      └ CP1: ¿Fase 2 o eliges candidato?
│
├─ Fase 2 (7 fuentes) → 📋 INFORME MODELO completo (7 fuentes)
│                      └ CP2: ¿qué candidato investigo a fondo?
│
└─ ELIGES UNO → se convierte a FLUJO A (UNIDAD)
    │
    ├─ Fase 1+2 → 📋 INFORME UNIDAD (15 sec, score 0-100)
    │            └ CP3: veredicto (Comprar/Dudoso/Descartar)
    │
    └─ Si 🟢/🔵 → 📄 DOSSIER CLIENTE (15 sec) + 📦 ZIP Laravel
```

| Informe | Cuándo | En el mensaje del encargo? |
|---|---|---|
| 📋 Informe BÚSQUEDA/MODELO | Fin Fase 1 (Flujo B) | ✅ Sí |
| 📋 Informe UNIDAD | Al elegir un candidato (→ Flujo A) | ❌ Después |
| 📄 Dossier cliente | Tras veredicto 🟢/🔵 | ❌ Al final |
| 📦 ZIP Laravel | Tras dossier aprobado | ❌ Al final |
| 🎨 Folleto publicidad / ficha | **Lo genera LARAVEL** (no Claude), cuando el coche está en inventario | — |
### 📋 ESTRUCTURA DE INFORMES — entregables obligatorios por fase (15-ago-2026)

> **Cada fase produce SU entregable, en orden. No se mezclan en un mismo archivo y NO se espera a que el usuario los pida.**
> Fallo real 15-ago (Tiguan cliente): se creó un único `.md` de valoración al final, y faltaron el informe de búsqueda (fase 1), el informe de unidad y el ZIP. Eso es un fallo de la skill, no una decisión del usuario.

| Fase | Entregable | Contenido mínimo | Formato / archivo |
|---|---|---|---|
| **1 · Búsqueda** (fin de Fase 1/2 de fuentes) | **INFORME DE BÚSQUEDA + candidatos** | Cobertura por fuente (URL, filtros, nº resultados, estado), tabla de candidatos con precio/año/km/enlace, qué se excluyó y por qué, qué fuente quedó sin peinar y por qué | `informe_busqueda_<modelo>.md` (o en el chat si breve). NO es un informe de valoración |
| **2 · Avance con candidato** (usuario elige uno) | **INFORME DE UNIDAD** | Las 15 secciones de `informe_tecnico.md` (o las 11 no negociables del flujo MODELO) SOLO del candidato elegido | `informe_unidad_<modelo>_<unidad>.md` + esqueletos `.txt` |
| **3 · Cierre** (veredicto 🟢/🔵) | **ZIP Laravel** | `informe.json` + `manifest.json` + `contenido/*.txt` + `fotos/` | `[coche_id].zip` → se sube a Laravel |

### 🗺️ MAPA DE PDFs — TIPOS y DÓNDE SE CREA CADA UNO (15-ago-2026)

> **Hay 7 PDFs en total: 3 los genera CLAUDE (investigación) y 4 los genera LARAVEL (venta/documento).**
> **El briefing PDF ya NO existe** (eliminado 15-ago-2026). El status de cliente 'Briefing' (pipeline) y `briefing_encargo.md` (cuestionario previo) NO son el PDF briefing y se mantienen.

| # | PDF | Tipo | Quién lo genera | De qué sale | Dónde se crea |
|---|---|---|---|---|---|
| 1 | `informe_busqueda_*.pdf` | Investigación (búsqueda) | **CLAUDE** | Markdown Fase 1 | HTML de marca → Chrome headless, plantilla `assets/plantilla_pdf_marca.html` |
| 2 | `informe_unidad_*.pdf` | Investigación (unidad) | **CLAUDE** | `informe_tecnico.md` (15 sec) | Idem plantilla de marca |
| 3 | informe técnico unidad (Flujo A) | Investigación (técnico) | **CLAUDE** | `informe_tecnico.md` | Idem plantilla de marca |
| 4 | Dossier cliente | Venta / cliente | **LARAVEL** | `contenido/dossier-cliente.txt` | Blade `ficha-coche.blade.php` (documento cliente) |
| 5 | Ficha del coche | Venta / cliente | **LARAVEL** | `contenido/ficha-publicitaria.txt` | Blade `ficha-coche.blade.php` · `PaqueteValoracionController@ficha` · ruta `cars.ficha` |
| 6 | Informe interno | Venta / equipo | **LARAVEL** | `contenido/informe-interno.txt` | Blade `informe-interno.blade.php` · `PaqueteValoracionController@interno` · ruta `cars.informe-interno` |
| 7 | Folleto institucional | Marketing / público | **LARAVEL** | estático (sin esqueleto) | Blade `folleto.blade.php` · `JJImportFolletoController@download` · ruta `jj-import.folleto` |

**Reglas duras del mapa:**
1. **Claude NUNCA genera los PDFs de venta** (ficha, informe interno, folleto) — los hace Laravel con Blade + Browsershot tras recibir el ZIP.
2. **Laravel NUNCA genera los PDFs de investigación** — los hace Claude en el Desktop con la plantilla de marca.
3. El **informe interno** (margen, honorarios, URLs de comparables) es SOLO equipo; el **dossier/ficha** es para el cliente (sin margen).
4. Los esqueletos `.txt` (`contenido/*.txt`) son la ÚNICA entrada de Laravel: `ficha-publicitaria.txt`, `informe-interno.txt`, `dossier-cliente.txt`.

**Reglas duras:**
1. **La fase 1 acaba con el informe de búsqueda y la lista de candidatos.** No se escribe valoración en la fase 1.
2. **No se mezclan búsqueda y valoración en el mismo archivo.** `informe_busqueda_*.md` ≠ `informe_unidad_*.md`.
3. **El informe de unidad NO se genera en la fase 1 ni para todos los finalistas.** Solo del candidato en el que el usuario avanza.
4. **El ZIP se genera al cerrar coche**, no cuando el usuario lo recuerda. Sin ZIP la fase 3 no está terminada.
5. **En Flujo A (URL directa):** el informe de búsqueda se limita a la cobertura de fuentes del mercado; el informe de unidad sale al evaluar la URL.

### 📁 DÓNDE SE GUARDA CADA COSA — rutas por tipo de archivo (15-ago-2026)

> **Regla 1: los `.md` que LEE EL USUARIO van al Desktop, organizados por marca/modelo.**
> **Regla 2: los JSON de contrato y los ZIPs van a `laravel/` (los procesan los scripts Python y Laravel).**
> NUNCA escribir nada en `AppData\Roaming\Claude\...\outputs\` (fallo real 15-ago, Tiguan: el informe se escribió ahí y el usuario no lo veía).

**Tabla única de rutas — QUÉ archivo va DÓNDE:**

| Archivo | Ruta | Quién lo usa | Cuándo |
|---|---|---|---|
| `informe_busqueda_<fecha>.md` | `informes\<marca>\<modelo>\` | El usuario (lectura) | Fin Fase 1 (Flujo B/C) |
| `informe_unidad_<fecha>.md` | `informes\<marca>\<modelo>\` | El usuario (lectura) | Fase 2, candidato elegido |
| `comparativa_<fecha>.md` | `informes\<marca>\<modelo>\` | El usuario (lectura) | Si compara varios candidatos |
| `export\flujo-a-<coche_id>.json` | `laravel\` | `empaquetar.py` | Flujo A (entrada del ZIP) |
| `export\flujo-b-<modelo>-<fecha>.json` | `laravel\` | Laravel (histórico cacheable) | Flujo B |
| `export\flujo-c-<fecha>.json` | `laravel\` | Laravel (scouting) | Flujo C |
| `<coche_id>.zip` | `laravel\paquetes\` | Subida a Laravel | Cierre Flujo A |
| `informe.json` | **SOLO dentro del ZIP** | Laravel | Lo genera `empaquetar.py` — NO existe suelto |
| Fotos del candidato | `<coche_id>_fotos\` junto al JSON de `export\` | `empaquetar.py` | Se descargan al elegir candidato |

**Aclaraciones que evitan confusión (15-ago-2026):**

1. **`informe.json` NO es un archivo suelto.** Es una entrada DENTRO del ZIP que
   genera `empaquetar.py` a partir de `export\flujo-a-<coche_id>.json`. Si estás
   buscando un "informe JSON" en la carpeta del modelo, no existe: existe el
   `flujo-a-*.json` en `laravel\export\` y el ZIP en `laravel\paquetes\`.
2. **Los `.md` de `informes\` son para el USUARIO.** No los procesa ningún script.
   Son lectura humana: búsqueda, unidad, comparativa.
3. **Los JSON de `laravel\export\` son para las MÁQUINAS.** Los lee `empaquetar.py`
   o Laravel (`php artisan importnex:import-valuation`). No hace falta abrirlos a mano.
4. **Los PDFs no los genera Claude** — salen de Laravel (Blade + Browsershot) tras
   subir el ZIP.

```
C:\Users\jacar\Desktop\JJImportMotors\
├── informes\                        ← SOLO .md para el USUARIO, por marca/modelo
│   └── <marca>\<modelo>\            ← ej. vw\tiguan
│       ├── informe_busqueda_<fecha>.md
│       ├── informe_unidad_<fecha>.md
│       └── comparativa_<fecha>.md
└── laravel\                         ← trabajo de scripts y contrato con Laravel
    ├── export\
    │   ├── flujo-a-<coche_id>.json  ← entrada de empaquetar.py (Flujo A)
    │   ├── flujo-b-<modelo>-<fecha>.json
    │   └── flujo-c-<fecha>.json
    └── paquetes\
        └── <coche_id>.zip           ← contiene informe.json + manifest + contenido\ + fotos\
```

**Reglas de guardado:**
1. Crear carpetas con `New-Item -ItemType Directory -Force`.
2. **Normalizar nombres:** marca y modelo en minúsculas, sin tildes, con guiones
   (`vw\tiguan`, `opel\astra`, `audi\a3`). Fecha en `YYYY-MM-DD`.
3. **Nunca fuera del Desktop.** Si un informe quedó en otra ruta (outputs de la
   sesión, AppData, temp), copiarlo a la estructura del Desktop.

### 📸 FOTOS REALES · ENLACES DE ANUNCIO · FUENTES CON URL (15-ago-2026 · v2.9.4)

> **Reglas duras exigidas por el usuario en cada entrega.** Fallo real 15-ago (Tiguan): se subieron **capturas de pantalla** en vez de las fotos reales del anuncio, enlaces genéricos y sin la lista de fuentes.

**1. Fotos = descargadas del ANUNCIO, NUNCA capturas.**
- Las fotos del candidato son las **imágenes reales del anuncio** (URLs `https://...jpg|png|webp` de la ficha del portal), descargadas a `<coche_id>_fotos\`.
- **PROHIBIDO** subir capturas de pantalla del navegador ni screenshots del listado.
- Van en el JSON como **`vehiculo.fotos`** (Laravel las descarga desde ahí al importar).
- Si el portal bloquea la descarga (hotlink), reintentar con User-Agent de navegador y avisar de las que fallen — **nunca** sustituirlas por capturas.

**2. Enlaces = del ANUNCIO individual, NUNCA genéricos.**
- Toda URL de candidato/comparable es la **ficha del vehículo** (ej. `mobile.de/fahrzeuge/details.html?id=<id>`, slug de Coches.net, `/app/item/<id>` de Wallapop).
- **PROHIBIDO** usar URLs de búsqueda/filtro del portal (`?sortOption=...&categories=...`), páginas de listado o el dominio raíz. Si la fuente no da URL directa, construirla desde el ID (A6).

**3. Fuentes = SIEMPRE documentadas con su URL en el informe.**
- Todo informe (búsqueda y unidad) incluye al final la sección **"Fuentes consultadas"**: cada fuente con su estado (OK / 0 resultados / bloqueada+intentos) y su enlace cuando aplique.
- Se registran las fuentes del flujo (no solo las que dieron candidatos); si alguna quedó sin peinar, se declara.
- En el JSON van en el bloque `fuentes` (o en `avisos` si alguna quedó bloqueada).

**4. Organización = SIEMPRE por marca/modelo.**
- Todo lo que genera Claude se guarda en `informes\<marca>\<modelo>\` (y `laravel\export\` para los JSON) — nunca suelto ni en AppData. Normalizar nombres (minúsculas, sin tildes, guiones).


### ⚡ MODO AUTOMÁTICO EN CASCADA (12-ago-2026) — regla dura

> **Automatizar todo lo que es trabajo de Claude. La ÚNICA decisión que le corresponde al usuario es QUÉ candidato investigar (decisión de negocio).**

```
ENCARGO COMPLETO → automático:

FASE 1 (automática)
1. Briefing: reconocer parámetros (no preguntar si no falta nada)
2. Fase 1 (3 fuentes) → 📋 INFORME MODELO + top 5 con enlaces
3. ENTREGAR informe MODELO y ESPERAR (el usuario elige candidato)

⏸️ ÚNICA PAUSA LEGÍTIMA: el usuario indica el candidato
   "investiga el de 8.999 €" → 1 candidato
   "investiga estos 3" / "compáralos" → varios → comparativa antes
   "el mejor" → Claude propone 1 (con justificación) y sigue

TRAS ELEGIR CANDIDATO (todo automático, sin preguntar)
4. 📸 Fotos: descargar automáticamente
5. Si VARIOS → 📊 COMPARATIVA primero (tabla lado a lado), luego informes
6. 📋 INFORME UNIDAD completo (15 sec, score 0-100)
7. 🟢/🔵 → 📄 DOSSIER CLIENTE automático
8. 📦 ZIP completo: informe.json + manifest + esqueletos .txt + fotos
```

**Solo PAUSAR y preguntar en estos casos:**
1. **Veredicto 🟡/🔴** → entregar informe y pedir decisión (no generar dossier)
2. **Banderas críticas de seguridad** (VIN ausente, no declara "libre de accidentes") → avisar y marcar en el plan de negociación, PERO seguir generando el paquete
3. **Encargo incompleto/vago** → briefing (preguntar solo lo que falta)

**NUNCA preguntar:** "¿continúo?", "¿qué candidato investigo?", "¿descargo las fotos?", "¿genero el informe?". El informe MODELO se entrega y **se espera la instrucción del usuario** — no se le pregunta, es él quien elige el candidato.

### 📊 COMPARATIVA DE CANDIDATOS — cuando el usuario pide investigar VARIOS

> Si el usuario dice "investiga estos 3" / "compáralos" / "mírame los 5", Claude **primero hace una comparativa lado a lado** y SOLO DESPUÉS genera los informes individuales (o solo del ganador, si el usuario lo pide).

**Plantilla de comparativa:**

```markdown
# 📊 Comparativa — Opel Astra J OPC (3 candidatos)

| # | Precio | Año | Km | Vendedor | Estado | Coste Huelva | Ahorro vs ES | Score |
|---|---|---|---|---|---|---|---|---|
| 1 | 8.999 € | 10/2012 | 106.000 | Particular Laatzen | 🟡 sin VIN/accid. | ≈11.900 € | 31,9% | 80 |
| 2 | 10.950 € | 05/2014 | 129.000 | Concesionario 5★ | 🟢 garantía | ≈13.900 € | 24,1% | 75 |
| 3 | 12.000 € | 08/2014 | 77.640 | Particular Hoven | 🟡 negociable | ≈15.000 € | 18,3% | 70 |

## Recomendación
- **Ganador: #1 (8.999 €)** — mejor ahorro, pero SIN VIN → verificar recalls antes de pagar.
- **Alternativa segura: #2 (10.950 €)** — concesionario con garantía, menos riesgo.
- Decisión: te genero el informe UNIDAD completo + paquete del que elijas (o de ambos).
```

**Reglas de la comparativa:**
1. Tabla lado a lado con las columnas clave (precio, año, km, vendedor, estado, coste Huelva, ahorro, score).
2. Incluir ENLACES de cada candidato (A6).
3. Marcar banderas por candidato (🟢 limpio / 🟡 pendiente de verificar / 🔴 descartado).
4. Recomendar 1 ganador + 1 alternativa segura.
5. Esperar la elección del usuario → generar informe UNIDAD + paquete solo de lo elegido.

### INFORME TIPO UNIDAD (Flujo A) — el completo, 15 secciones

> 📄 **Ver `informe_tecnico.md` para estructura completa con score 0-100 y bloques `[MARCADOR]`.** Resumen rápido:

1. Cabecera (coche_id · score global · recomendación)
2. Cobertura 7 fuentes con ⭐ confianza por fuente
3. Oferta española (comparables con sello 🟢🟡🔴 + cuartiles + días medio)
4. Oferta alemana (ídem + portal + vendedor + VB + cambios precio)
5. Candidato seleccionado (ficha completa + ficha técnica + hallazgos)
6. **Comparable ajustado** → cálculo línea a línea (ver `comparables.md`)
7. **Coste puesto en Huelva** → desglose + análisis sensibilidad IEDMT (ver `costes.md`)
8. Margen y veredicto (contra 4 referencias: mediana + Q1 + ajustado + mínimo)
9. **Vendibilidad** 5 factores justificados (100 puntos)
10. **Plan de negociación** con mensaje alemán + precio tope + backups
11. Riesgos y banderas (con plan de mitigación por riesgo)
12. Alternativas reales con URLs
13. **Predicción de venta** (4 escenarios: óptimo/base/conservador/pesimista)
14. Acción inmediata (pasos numerados con plazo)
15. Score global de oportunidad (6 dimensiones, 0-100)

**_outputs del informe UNIDAD (archivos .txt en ZIP):**
- `informe-interno.txt` (análisis JJ Import Motors · ver `informe_tecnico.md`)
- `dossier-cliente.txt` (PDF profesional para cliente · ver `dossier_cliente.md`) — solo si veredicto 🟢/🔵
- `ficha-publicitaria.txt` (venta en portales · contrato.md §publicidad)
- `redes-sociales.txt` + `anuncio-portales.txt` (ver contrato.md)

**Cuándo emitir dossier cliente:** 🟢 Comprar siempre · 🔵 Comprar si baja de precio siempre · 🟡 Dudoso solo si el cliente pidió evaluarlo · 🔴 Descartar nunca (carta breve en su lugar).

**⚠️ Quién genera cada PDF (12-ago-2026):** Claude genera los **esqueletos `.txt` [MARCADOR]** dentro del ZIP. Los PDFs finales (`dossier`, `ficha-publicitaria`, `folleto`) los **genera Laravel** (Blade + Browsershot) cuando el coche ya está en el sistema. Claude NO genera PDFs, NO genera el folleto publicitario ni la ficha durante la investigación — esos salen del panel cuando el coche está en inventario.

---

## 🔁 CONTROL DE FRESCURA — DELTA UPDATES

Antes de rehacer, consultar `indice.json` SIEMPRE primero.

| Antigüedad | Acción |
|---|---|
| < 2-3 semanas | Chequeo rápido. Si no hay mejora → CERRADO. Mostrar solo **cambios** (delta). |
| > 3 semanas o sin datos | Búsqueda completa (Fase 1 + Fase 2). |

**Formato delta al refrescar modelo cacheado:**

```
🔄 ACTUALIZACIÓN Golf GTI Clubsport (hace 18 días)

CAMBIOS:
- Mediana ES: 34.500 → 33.800 (-700€, -2.0%) 📉
- Hueco: 22.4% → 19.8% (-2.6pp) ⚠️ Se estrecha

LO DEMÁS: sin cambios significativos.
¿Rehacer análisis completo? (tokens: ~40)
```

---

## 📈 VENDIBILIDAD — 5 factores, 100 puntos

| # | Factor | Peso | Fuente | Estado |
|---|---:|---|---|---|
| 1 | Demanda del modelo | 30 | Coches.net `publicationDate` (mediana días) | ✅ |
| 2 | Escasez configuración | 25 | AS24.es `countryCode` + recuento | ✅ |
| 3 | Atractivo | 20 | Criterio cualitativo | Manual |
| 4 | Equipamiento sobre std ES | 15 | mobile.de `features` vs acabado ES | ✅ |
| 5 | Km e historial | 10 | mobile.de ficha: propietarios, ITV, km/año | ✅ |

**Puntuación:** Demanda: top-10=30, fuerte=22, minoritario=14, nicho=6 · Escasez: ≤20=25, 20-50=21, 50-100=16, 100-300=10, >300=4 · Atractivo: icónico=18-20, deportivo=14-17, premium=10-13, utilitario=4-8 · Equipamiento: techo=4, cuero=3, AWD=3, LED=2, audio=2, HUD=1 · Historial: libro=3, 1dueño=2, <15k/año=3, ITV=2.

### Matriz de decisión (solo Flujo A)

| | Margen ≥10% | Margen <10% |
|---|---|---|
| **Vendibilidad ≥65** | 🟢 COMPRA PRIORITARIA | 🔵 OFERTA DE CONTENIDO |
| **Vendibilidad <65** | 🟡 SOLO BAJO PEDIDO | 🔴 DESCARTAR |

> La casilla azul se ignora siempre: coche con 5% margen y vendibilidad alta **sí se oferta**. Trae los clientes de los 3 siguientes.

---

## 🛡️ ANTI-PATRONES BLOQUEADOS

Las 16 reglas duras (A1-A16) viven en `anti_patrones.md`. Cargarlas cuando se duda de una práctica o antes de cerrar un informe.

**Resumen rápido:**
- **A1** No descartar por silencio (sello `man`, no exclusión)
- **A2** mobile.de SIEMPRE en cobertura (OK o bloqueada+intentos)
- **A3** CO₂ y PVP de km77 o BOE, nunca estimación
- **A4** Veredicto contra mediana Y cuartil bajo
- **A5** Precio máximo de compra en todo informe Flujo A
- **A6** Tablas con columna ENLACE clickable
- **A7** Cobertura completa: siempre las 7 fuentes, nunca cifras con <7 sin PARCIAL
- **A8** AutoScout24 solo para contar, NUNCA precio
- **A9** No afirmar "lo vi" sin comprobarlo — si no está en los datos, no está
- **A10** Precio contado confirmado (el financiado de MUY CAR/Flexicar no vale)
- **A11** Paginación completa del listado (relevancia ≠ precio)
- **A12** Página 1 ordenada por precio NO es "el listado" — cubrir TODO el rango del presupuesto (bandas de precio)
- **A13** Filtros del encargo alterados (año, km, precio) se declaran ANTES de navegar, nunca en silencio
- **A14** Nunca abandonar el camino en silencio: desviación → responder → ↩⃾ volver al paso; cambio de destino → 🔀 declararlo

---

## 🚪 LAS DOS PUERTAS

### Puerta A · ¿Hay comparable español? (Flujo A)

| Coches ES reales | Lectura |
|---|---|
| ≥15 | Comparable sólido |
| 5-14 | Justo: mediana **y** cuartil bajo |
| 1-4 | No hay comparable. Ni medianas ni %. |
| 0 | Exclusividad pura |

### Puerta B · Test de nivel de precio (Flujo A y B)

| DE vs ES | Acción |
|---|---|
| ≥25% por debajo | Rastreo completo |
| 15-25% | Rastreo, avisando del margen justo |
| 0-15% | Solo cuartil bajo. Dilo antes de empezar. |
| Por encima | No rastrear. Solo cliente concreto.

---

## 📦 ZIP PARA LARAVEL — solo Flujo A

```
[coche_id].zip
├── informe.json                    ← JSON completo del CONTRATO (contrato.md)
├── manifest.json                   ← Metadatos del paquete
├── contenido/
│   ├── ficha-publicitaria.txt      ← Esqueleto [BLOQUE] → folleto.blade.php
│   ├── dossier-cliente.txt        ← Esqueleto [BLOQUE] → documento del cliente (ficha-coche) en Laravel
│   ├── informe-interno.txt         ← Esqueleto [BLOQUE] → informe-interno.blade.php (PDF equipo)
│   ├── redes-sociales.txt          ← [GANCHO] [POST_LARGO] [STORIES] [HASHTAGS]
│   └── anuncio-portales.txt        ← [TITULO] [DESCRIPCION] [AVISO_LEGAL]
└── fotos/
```

**Reglas duras del `informe.json` del ZIP (15-ago-2026):**

1. 🔴 **`anuncio.descripcion_original` = texto literal COMPLETO del anuncio** (pegado tal cual, sin resumir ni corregir) + `descripcion_traducida` completa. Laravel las muestra en la ficha. Un resumen traducido NO vale: se pierde el original.
2. 🔴 **`vehiculo.equipamiento` = lista COMPLETA** de la sección `Ausstattung`/features del anuncio (no solo los 15 destacados del informe humano). Laravel lo muestra y lo usa para el ajuste de comparable y la ficha publicitaria.
3. 🔴 **`mercado.comparables[].url` = URL directa de la FICHA del anuncio**, nunca una búsqueda/filtro del portal (A6). Sin URL, el comparable se descarta al importar.
4. ✅ Campos extra del anuncio si están visibles: `dias_publicado`, `tuv_vigente_hasta`, `precio_publicado` vs `precio_negociado`, `carroceria`, `color_interior`. Laravel los guarda en `Car.notes`.

**Qué hace Laravel con cada archivo:** ver `contrato.md`.

**Lo que NUNCA va en el ZIP:** PDFs pre-generados, Excels, datos de otros coches, anotaciones internas tipo "(NUEVO)" o "revisión anterior".

---

## 📄 JSON por flujo

| Flujo | Salida JSON | Estructura |
|---|---|---|
| **A: UNIDAD** | `informe.json` dentro del ZIP | `{_meta, vehiculo, anuncio, investigacion, balance, veredicto, costes, mercado, avisos, publicidad}` — un solo coche, contrato completo |
| **B: MODELO** | `informe.json` suelto en `export/` | Misma estructura que A, pero SIN `publicidad` (no se generan esqueletos de venta). El usuario decide si promover a Flujo A después. |
| **C: MERCADO** | `informe.json` agregado | `{_meta, modelos: [{modelo, segmento, hueco_pct, n_uds_de, vendibilidad_estimada, mejor_anuncio_url}, ...]}` — N entradas, sin detalle por unidad. Se guarda en `export/scouting_<fecha>.json` para histórico. |

---

## ✅ CHECKLIST

**Antes de gastar**
- [ ] Detecté el flujo correcto (A/B/C/D)
- [ ] Tabla cobertura con las fuentes que apliquen al flujo
- [ ] Consulté `indice.json` y comprobé frescura
- [ ] Miré el registro de clientes (Flujo B)
- [ ] Confirmé la **modalidad de honorarios M1/M2/M3** del encargo (3 fallos reales por asumir)
- [ ] Encargo abierto sin URL → mostré el **PLAN DE BARRIDO** antes de navegar
- [ ] Si amplié filtros del encargo (año, km, precio), lo declaré ANTES (A13)
- [ ] Waypoint 📍 en cada mensaje · tras cada desviación retomé el paso (A14)
- [ ] Cuaderno de sesión al día (correcciones con hora, releído antes de cada micro-plan)
- [ ] Micro-plan aprobado antes de CADA búsqueda nueva (fuente/banda/filtros/fase)
- [ ] Auditoría de fase pasada al completar cada paso (entregable · camino · correcciones · cobertura)

**Al medir**
- [ ] Fase 1 con las 3 fuentes obligatorias (Coches.net, mobile.de, AutoUncle)
- [ ] `powertype=kw` · Verifiqué `initialSearch` en Coches.net
- [ ] PVP y CO₂ de km77 (si Flujo A)
- [ ] Medí DE en mobile.de directo, no solo AutoUncle
- [ ] Usé navegación real primero (navegar, filtrar, leer visible) antes que inyección JS
- [ ] Ante bloqueo, probé recarga + navegación real + `extractores.md` antes de marcar
- [ ] No descarté por silencio (A1) · mobile.de en cobertura OK (A2)
- [ ] CO₂ de km77 o BOE, no estimación (A3)

**Al evaluar (Flujo A)**
- [ ] Filtro admisión: ±2 años, ±40% km, ajustes capados ±25%
- [ ] <15 comparables → rango · Ajuste línea a línea · Descripción entera
- [ ] Regla 6/6000 · CO₂ (estimado → decirlo)
- [ ] Ahorro contra mediana **y** cuartil bajo (A4)
- [ ] Precio máximo de compra en informe (A5)
- [ ] Tablas con columna ENLACE (A6)

**Al cerrar**
- [ ] Actualicé `datos/registro_cierres.json` → Ver `operaciones_cierre.md`
- [ ] Calculé KPIs mensuales si aplica → Ver `operaciones_cierre.md`

**Estructura de informes (15-ago-2026)**
- [ ] Fase 1 → entregué INFORME DE BÚSQUEDA + candidatos (no un informe de valoración)
- [ ] Fase 2 → INFORME DE UNIDAD solo del candidato en el que avanzó el usuario
- [ ] Fase 3 → ZIP generado (sin ZIP la fase no está terminada)
- [ ] Búsqueda y valoración en archivos separados (`informe_busqueda_*` ≠ `informe_unidad_*`)
- [ ] No afirmé haber visto un anuncio sin comprobarlo (A9) · Confirmé contado vs financiado (A10) · Paginé todas las páginas (A11)

**Contenido del JSON del ZIP (15-ago-2026)**
- [ ] `anuncio.descripcion_original` = texto literal COMPLETO del anuncio (no resumido) + `descripcion_traducida`
- [ ] `vehiculo.equipamiento` = lista COMPLETA del anuncio (Ausstattung), no solo los 15 destacados
- [ ] `mercado.comparables[].url` = URL directa de la ficha (nunca búsqueda/filtro) (A6)
- [ ] Campos extra si están visibles: `dias_publicado`, `tuv_vigente_hasta`, `precio_publicado`, `carroceria`, `color_interior`

---

## 📚 MÓDULOS ESPECIALIZADOS

Cargar solo cuando se necesite la sección específica:

| Módulo | Cuándo cargar | Contenido |
|---|---|---|
| **`comparables.md`** | Flujo A: ajuste de comparable | 9 claves, filtro admisión, comparable sin muestra, detección competencia, primas equipamiento |
| **`costes.md`** | Flujo A: desglose económico | IEDMT (fórmula + tabla), precio máximo de compra, IVA, moneda extranjera, ejemplo completo |
| **`riesgos.md`** | Flujo A/B: motor problemático | Tabla riesgos por motor (DQ200, EA888, N47, etc.), verificación, reglas descarte automático |
| **`operaciones_cierre.md`** | Cierre de venta + KPIs | Registro cierre JSON, KPIs, changelog, sync Desktop ↔ Skill, encargo permanente |

---

## 🏢 NEGOCIO

**JJ Import Motors** (Huelva, España)
- **Modelo:** Servicio de búsqueda e importación de coches (desde Alemania y dentro de España). **NO compramos stock** — solo ofertamos el servicio de gestión con honorarios fijos. El cliente compra el coche.
- **Segmentos:** Nicho (≥20k€, margen ≥15%) y Rotación (8-20k€, margen ≥10%)
- **Fuentes:** 7 portales (3 ES + 4 DE)
- **Entregable:** ZIP con informe + esqueletos Blade + fotos → Laravel
- **Caché:** Endpoint `/api/investigation-cache` para reutilizar investigación por modelo

**Datos de marca:**
- Teléfono: `675 70 14 39`
- Email: `jjimportmotors@gmail.com`
- Web: `https://dev.aktive.cloud/importnexcore`
- Colores: `#1A306D` (estoril), `#38393D` (asphalt), `#BEC0C3` (platinum), `#E8590C` (accent)
