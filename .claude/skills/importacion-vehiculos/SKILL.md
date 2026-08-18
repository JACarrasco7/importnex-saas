---
name: importacion-vehiculos
version: 3.2.7
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

> 📁 **Compañeros:** `02-flujos/navegacion_real.md` (MÉTODO PREFERIDO — navegar como humano) · `02-flujos/paginas_reales.md` (estructura REAL capturada de los 7 portales) · `02-flujos/playbook_filtrado.md` (técnicas de filtrado/búsqueda para Claude Desktop) · `02-flujos/extractores.md` (URLs, trampas, diccionario) · `03-informes/contrato.md` (JSON + esqueleto) · `05-operaciones/operaciones.md` (carpetas, scripts) · **`06-reglas/anti_patrones.md`** (reglas duras 21) · **`../estudio-mercado/SKILL.md`** (skill hermana: genera el mapa de mercado `datos_mercado.json` que da el criterio de selección)
> 
> 📚 **Módulos especializados:** `03-informes/comparables.md` (ajuste 9 claves) · `04-negocio/costes.md` (IEDMT + desglose) · `04-negocio/riesgos.md` (motores problemáticos) · `05-operaciones/operaciones_cierre.md` (cierre + KPIs + sync)
>
> 📄 **Informes ( outputs finales):** `03-informes/informe_tecnico.md` (análisis interno 15 secciones + score 0-100) · `03-informes/dossier_cliente.md` (PDF profesional para cliente, 15 secciones, genera confianza)
>
> 🧠 **Memoria persistente (12-ago-2026):** `memoria/MEMORIA.md` (léeme primero) · `memoria/modelos-medidos.md` · `memoria/vendedores-confianza.md` · `memoria/trampas-encontradas.md` · `memoria/mejoras-aplicadas.md`
>
> 🎯 **Briefing de encargo (12-ago-2026):** `01-arranque/briefing_encargo.md` — preguntas previas OBLIGATORIAS antes de navegar (año mín, km máx, presupuesto, potencia si tope de gama). Ahorra tokens y evita re-búsquedas.

---

## ⚡ REFERENCIA RÁPIDA

```
Umbrales objetivo: Nicho ≥10% | Rotación ≥10% | Tramo 8-14k ≥12%
Umbrales mínimos (EXIT 3): Nicho 8% | Rotación 10% | Tramo 8-14k 10%
Costes fijos: ver `04-negocio/costes.md` (transporte + ausfuhr + ITV + honorarios) — §1.4 single source of truth
Fuentes: 7 (Wallapop, Milanuncios, Coches.net, mobile.de, AS24.de, AutoUncle, kleinanzeigen.de)
Método: navegación real estilo humano SIEMPRE primero → ver `02-flujos/navegacion_real.md`
Equipamiento: comparar a MÁXIMO equipamiento por defecto (la unidad DE suele venir full: cuadro digital, techo, LED). Un ES "más barato" sin ese equipamiento NO es comparable → ajustar con primas de `03-informes/comparables.md`
Playbook de filtrado: `02-flujos/playbook_filtrado.md` · estructura real: `02-flujos/paginas_reales.md`
Trampas top 3: countryCode SIEMPRE | navegación real primero (screenshot+clic), degradado si no se ve | mobile.de directo NUNCA saltar
Anti-patrones bloqueados: 21 (A1-A21, ver §Anti-patrones)
ENLACES: TODO lo que se entregue lleva enlace al anuncio (ficha) y fuentes con URL (A21)
📥 ACK ENTENDER: 1 línea de comprensión antes de todo encargo (qué → para qué → entregable) — `01-arranque/guia_prompts.md` §ACK
Camino fijo: waypoint 📍 en cada mensaje · desviaciones = misión lateral con retorno ↩⃾ (A14)
Plan de fase 3-5 líneas ANTES de cada fase (Protocolo de Mando) · cuaderno de sesión en informes\_sesion\
Protocolo de Mando: usuario aprueba cada fase · IA ejecuta la fase completa · pausa solo en emergencias
Checkpoints: CP-D tras informe MODELOS (elegir modelos) | CP1 tras informe MODELO (esperar elección de candidato) | CP2 tras comparable | CP3 antes de veredicto
Flujo D (cliente sin modelo): sondeo modelos ES+DE → informe MODELOS (país×año×motor) → embudo a B
Origen DE vs ES: si no se especifica, buscar en ambos mercados y comparar dónde sale mejor → 04-negocio/costes.md §Origen
Briefing encargo: preguntar críticos ANTES de navegar → `01-arranque/briefing_encargo.md`
Tope de gama: doble pasada por kW SIEMPRE → `02-flujos/playbook_filtrado.md` §Doble pasada
Cierre: AUDITORÍA DE CIERRE al elegir candidato único (o abortar) → 5 dimensiones + 3 salidas a memoria
PASO 0: CHECK DE CACHE antes de navegar (encargos + modelos-medidos + indice.json) → no re-buscar lo hecho
Mando: PROTOCOLO DE MANDO — usuario aprueba cada fase, IA ejecuta la fase completa, pausa solo en emergencias
```

---

## 🎯 LOS 5 FLUJOS — leer PRIMERO

| Flujo | Disparador | Profundidad | Output | ZIP Laravel |
|---|---|---|---|---|
| **A: UNIDAD** | URL pegada o "evalúa este" | Fase 1 + Fase 2 | Informe UNIDAD (15 sec) + dossier + folleto | ✅ Sí |
| **B: MODELO** | "busca [modelo]" sin URL | Fase 1 + Fase 2 si pasa | Informe MODELO + top 5 | ❌ No |
| **C: MERCADO** | "qué merece la pena", "top modelos" | Solo Fase 1, N modelos | Informe BUSQUEDA | ❌ No |
| **D: DESCUBRIMIENTO** | Cliente SIN modelo concreto (presupuesto + requisitos: año/km/cv/combustible) | Sondeo barato ES+DE — SOLO modelos y motorizaciones, sin anuncios | Informe de MODELOS por país × año | ❌ No |
| **E: STOCK** | "stock recurrente", "catálogo bajo pedido", "busca coches por categorías/segmentos" | Listados → informe de búsqueda (NO anuncios) | Informe de STOCK (Markdown+PDF+JSON) | ❌ No (catálogo, no valoración) |

> **Flujo E · STOCK (17-ago-2026):** ver `02-flujos/stock-marketing.md`. **Es BÚSQUEDA de coches, NO marketing**: entregable = informe de búsqueda con datos de mercado (nº anuncios, mediana, hueco), NUNCA anuncios/copy IG/FB. El marketing es un flujo posterior separado. Reglas: listado-first (A17) + sellos de precio + ejemplos ilustrativos no lista cerrada (A19) + checkpoint cada X.

> **CASCADA (12-ago-2026):** Flujo B **nunca** salta a "¿evalúo el candidato X?" sin entregar antes el INFORME MODELO + top 5 con enlaces + CP1. El usuario elige el candidato → **se convierte a Flujo A** → ahí sí: informe UNIDAD + dossier + folleto + ZIP. Los informes NO salen todos a la vez, son en cascada con checkpoint entre fases.

> **🌍 ORIGEN DE vs ES (12-ago-2026):** el encargo puede ser de un coche de **Alemania** (importación) o de **España** (compra nacional). Si el usuario NO especifica origen, buscar el modelo en **AMBOS mercados** y comparar **dónde sale mejor** (coste total puesto en Huelva). El origen ganador determina los costes: DE = transporte+ausfuhr+ITV import+IEDMT; ES = sin esos costes. Ver `04-negocio/costes.md` §Origen.

### Detección automática de flujo

```
¿Es "stock/catálogo/busca coches por categorías/segmentos" (BUSCAR coches, no publicar)?
├── SÍ → FLUJO E (STOCK) — ver 02-flujos/stock-marketing.md
├── NO ↓
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

**🔴 REGLA DURA UNIVERSAL (17-ago-2026):** todo encargo se asigna a **UN flujo** (A/B/C/D/E) y sigue SU camino con Protocolo de Mando (plan de fase → OK → ejecutar → waypoint 📍 → auditoría de cierre). **Si el encargo NO encaja en ninguno de los 5 flujos, PREGUNTAR al usuario qué flujo aplicar — NUNCA improvisar.** Fallo real 17-ago: "stock recurrente" no encajaba (no existía Flujo E) y Claude improvisó un .docx fuera del camino. Tras añadir Flujo E, si vuelve a aparecer un caso no previsto, la regla es preguntar antes de ejecutar.

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
7. **D1 NO pagina (eficiencia):** D1a enumera con 2 lecturas por mercado (asc = suelo + desc = techo) + facetas de marca + semilla `memoria/modelos-medidos.md`; D1b difiere el precio-desde a 1 consulta por modelo solo si falta. El anuncio individual solo se investiga cuando el embudo es pequeño (Flujo A/B).

**Antes de navegar en CUALQUIER flujo (A/B/C/D/E) → PASO 0 CACHE + briefing de encargo:**

### 🗂️ PASO 0 — CHECK DE CACHE (16-ago-2026, ampliado 17-ago) — NO re-buscar lo ya hecho

> **Se ejecuta SIEMPRE al recibir un encargo, ANTES de cualquier navegación o plan de fase — en TODOS los flujos, incluido E (stock/marketing).**
> Fallo real 17-ago: encargo de stock re-buscó el Astra OPC (ya medido 10-ago) y el Golf GTI (pendiente desde 12-ago) sin ofrecer delta. El PASO 0 no estaba asociado al arranque de flujos no-estándar.

```
¿Ya existe investigación de este modelo/cliente?
1. Leer `memoria/encargos.md` (¿encargo previo del cliente o del modelo?)
2. Leer `memoria/modelos-medidos.md` (¿medición previa? — campo refrescar_antes_de)
3. Cruce con `indice.json` (Desktop) + `datos_mercado.json` (regla frescura <3 semanas).
   → `datos_mercado.json` lo genera la skill hermana `estudio-mercado`, **ruta pactada (L2): `C:/Users/jacar/Desktop/JJImportMotors/datos_mercado.json`**.
   → Si NO existe o caducó: avisar "mapa no encontrado/caducado; considera ejecutar estudio-mercado", usar `memoria/modelos-medidos.md` como fallback y marcar el criterio como "sin estudio de mercado".

CASOS:
- Informe <3 semanas (refrescar_antes_de en futuro) → mostrar resumen + preguntar:
    ¿🔄 delta (solo cambios) / 🔁 refrescar completo / 🆕 buscar de nuevo?
- Encargo previo del MISMO cliente → retomar contexto (presupuesto, modalidad M1/M2/M3, preferencias)
- Sin datos → investigación nueva normal
```

**Regla dura:** si hay cache reciente (<3 semanas), **NO** re-hacer las 7 fuentes. Se ofrece delta primero. Ahorro de tokens completo en ese caso.

1. Extraer parámetros dados (modelo, año mín, km máx, presupuesto...).
2. Preguntar SOLO los críticos que falten (tabla de faltantes) + **modalidad de honorarios M1/M2/M3**.
3. Si es tope de gama → confirmar potencia (activa doble pasada).
4. Guardar encargo en memoria al cerrar.
> Fallo real 12-ago: se navegó sin preguntar potencia → se perdió el OPC de 8.999 € mal etiquetado.

---

## 💡 PLANIFICACIÓN DE ENCARGOS — ver `01-arranque/planificador.md` (16-ago-2026)

> **Toda la planificación vive en `01-arranque/planificador.md`**: Asistente de planificación (FASE 0 + pasos) + Plan de barrido + Prompt Improver + Asesor de filtros + embudo visualizado.
>
> **Regla (17-ago-2026):** el **PLAN DE BÚSQUEDA con filtros y embudo es OBLIGATORIO en TODO encargo, antes de navegar** (todos los flujos, no solo los vagos). Se aplica el **protocolo** del planificador:
> **PASO 0 ENTENDER la petición (📥 ACK de 1 línea SIEMPRE + preguntas precisas solo si hay duda de QUÉ/PARA QUÉ/ENTREGABLE — ver `01-arranque/guia_prompts.md` §ACK y §Árbol de decisión) → PASO 1 detectar flujo → PASO 2 cache → PASO 3 refinar briefing + aclarar intención/entregable (SIEMPRE, ver `01-arranque/guia_prompts.md` §Intención) → PASO 3b FIJAR MODELOS candidatos con encaje ES/DE y OK del usuario (ver `01-arranque/planificador.md` PASO 3b) → PASO 4 PLAN DE BÚSQUEDA (filtros URL/clic + bandas + segmentación + lotes, con OK del usuario — ver `01-arranque/planificador.md` PASO 4) → PASO 5 ejecutar en cascada.**
> **NUNCA abrir el primer portal sin el plan aprobado** (fallo real 17-ago: se navegó con filtros implícitos y el usuario pidió "proponer el plan antes de nada"). El plan se apoya en el mapa de mercado (`datos_mercado.json`) para la segmentación y priorización.
>
> Cargar `01-arranque/planificador.md` completo antes de navegar en cualquier encargo. Referencias: briefing (`01-arranque/briefing_encargo.md`), plantillas de prompt (`01-arranque/guia_prompts.md`), filtros por portal (`memoria/filtros-portales.md`).

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
2. **Fuente bloqueada → reintentar.** Primero navegación real (recarga + espera + clic en filtros) → si captcha, 1-2 reintentos → método técnico de `02-flujos/extractores.md` → solo entonces bloquear.
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
> Si la versión buscada es un **tope de gama / acabado especial** (`OPC`, `GTI`, `R`, `M`, `AMG`, `RS`, `Type R`, `N`, `Performance`...), el filtro por variante de texto NO es suficiente — se pierde coches genuinos mal etiquetados (caso real: OPC 8.999 € con título "Opel Astra"). SIEMPRE hacer la búsqueda 2 por **kW** (campo estructurado del permiso) y cruzar por unión de IDs. Ver `02-flujos/playbook_filtrado.md` §Doble pasada. Trampa documentada en `memoria/trampas-encontradas.md`.

Para Alemania, orden: mobile.de directo → AutoScout24.de directo → AutoUncle (NUNCA única) → kleinanzeigen.de.

---

## � EL CAMINO — mapa fijo de pasos + protocolo de desviación (15-ago-2026)

> **Objetivo: cero ambigüedad sobre en qué punto del flujo estamos y qué falta.** Cada flujo es una secuencia NUMERADA. En cada mensaje, Claude declara su posición con un waypoint; si el usuario desvía, se responde y se RETOMA.

### Los mapas (una línea por paso) — versión con PROTOCOLO DE MANDO

```
FLUJO D: 1 plan de fase → 2 EJECUTAR sondeo ES+DE → 3 INFORME DE MODELOS
          → CP-D (usuario elige 2-3 modelos) → cada modelo entra en FLUJO B

FLUJO B: 1 plan de fase → 2 EJECUTAR Fase 1 (3 fuentes) → 3 INFORME MODELO+top5
          → CP1 (usuario elige candidato) → 4 plan de fase Fase 2 → 5 EJECUTAR Fase 2
          → 6 INFORME UNIDAD → CP3 veredicto → dossier → ZIP (→ FIN)

FLUJO A: 1 plan de fase → 2 EJECUTAR Fase 1+2 → 3 INFORME UNIDAD → CP3 → dossier → ZIP

FLUJO E: 1 plan de fase (categorías + nº) → 2 EJECUTAR listados (no fichas, A17)
          → 3 INFORME STOCK (Markdown + PDF + JSON) → auditoría de cierre → encargos.md
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

## � PROTOCOLO DE MANDO — guiado por fase, ejecución automática DENTRO de la fase (16-ago-2026)

> **Filosofía (decisión del usuario 16-ago):** *"nunca debería ser modo automático; deberían haber acciones automáticas según fases, pero siempre debe ser guiado por el usuario."*
> El usuario aprueba **cada fase**; dentro de la fase la IA ejecuta **toda la fase** sin pedir OK a cada paso.

**El ciclo por fase (se repite en cada fase del camino):**

```
1. 📋 PLAN DE FASE (3-5 líneas): objetivo · fuentes · filtros (asesor de filtros) · presupuesto tokens · entregable
2. ✅ OK del usuario (o correcciones → se aplican y se re-presenta el plan)
3. 🚀 EJECUCIÓN AUTOMÁTICA completa de la fase (waypoint 📍 por mensaje, sin preguntar)
4. 🧐 AUDITORÍA DE FASE (4 checks internos) → entregar resultado + checkpoint (CP)
5. ➡️ Siguiente fase → volver al paso 1
```

**Ejemplo en el chat:**
```
📍 Fase 2/4 — Flujo B
📋 Plan de fase: Fase 2 en las 4 fuentes que faltan (Wallapop, Milanuncios, AS24, kleinanzeigen)
   con los filtros del encargo · ~12 peticiones · entregable: INFORME MODELO completo 7/7
   ¿Ejecuto?
```

**La ÚNICA decisión del usuario (decisión de negocio):** QUÉ candidato investigar (CP-D/CP1/CP3). El resto es ejecución de la skill.

**Pausas SOLO por emergencia (dentro de la fase, se avisa y se espera):**
1. **Presupuesto al 80%** (contador) sin veredicto claro → STOP + preguntar invertir o PARCIAL.
2. **Fuente bloqueada** tras reintentos (recarga + navegación real + `02-flujos/extractores.md`) → declarar + preguntar si degradar.
3. **Hallazgo crítico** (bandera roja de seguridad, veredicto 🟡/🔴 que cambia la fase) → avisar y decidir con el usuario.
4. **Desviación de camino** (A14) o cambio de filtros del encargo (A13) → declarar, no callar.

**NUNCA preguntar dentro de una fase aprobada:** "¿continúo?", "¿descargo las fotos?", "¿sigo con la siguiente página?" — el lote de la fase se ejecuta completo y se informa al cierre. El informe se entrega y **se espera la instrucción del usuario** — no se le pregunta qué candidato, es él quien elige.

> **Mapas de camino por flujo:** ver §EL CAMINO (arriba) — versión con PROTOCOLO DE MANDO.

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
2. El cuaderno se RELEE antes de cada plan de fase (¿algo de aquí cambia el plan?).
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
| Cierras o abortas un encargo | `memoria/encargos.md` (cliente→flujo→resultado→refrescar antes de) |
| Mides un modelo nuevo o evalúas una URL | `memoria/modelos-medidos.md` (12 campos, con `refrescar antes de`) **+ `datos_mercado.json`** (ver §MAPA DE MERCADO abajo: la medición profunda de Flujo A/B alimenta el mapa) |
| Verificas un filtro/URL que funciona (o falla) en un portal | `memoria/filtros-portales.md` (fecha + parámetro) |
| Detectas una trampa nueva en un portal | `memoria/trampas-encontradas.md` |
| Un vendedor responde bien/mal o se negocia | `memoria/vendedores-confianza.md` |
| Aplicas una mejora al skill | `memoria/mejoras-aplicadas.md` |
| Aprendes algo sobre el usuario (preferencia, disgusto) | `.claude/memoria/preferencias.md` (en el proyecto) |
| Cometes un error que debe evitarse | `.claude/memoria/errores-pasados.md` (en el proyecto) |
| Tomas una decisión con justificación importante | `.claude/memoria/decisiones.md` (en el proyecto) |

**Cuándo actualizar:** en cuanto ocurre (no esperar al final). Al cerrar la conversación, verifica que la memoria está al día.

**Detalles completos:** ver `memoria/MEMORIA.md` del skill.

### 🗺️ MAPA DE MERCADO — integración con la skill `estudio-mercado` (17-ago-2026)

> **Comunicación bidireccional** entre skills vía `datos_mercado.json` (lo genera `../estudio-mercado/`; esquema en `../estudio-mercado/schema_datos_mercado.md`).

**LEER el mapa — TODOS los flujos, no solo los ambiguos:**
- ⚡ **Eficiencia (17-ago-2026):** cargar `datos_mercado.json` UNA vez al inicio de la sesión y mantenerlo en contexto; releer SOLO justo antes de escribir (merge E10). No re-leer el JSON en cada paso.
- **Flujo A (URL):** al evaluar una unidad, el mapa da CONTEXTO inmediato del modelo (mediana DE/ES, hueco bruto/neto, rotación, demanda, veredicto) → mejora los comparables y el veredicto sin navegar extra. Si el modelo no está en el mapa, la medición de esta evaluación LO AÑADE (feedback).
- **Flujo B (modelo):** antes del barrido de 7 fuentes, mirar el mapa → si el modelo tiene veredicto 🔴 (hueco neto <0, ES mejor), avisar ANTES de gastar peticiones ("el mapa dice que este modelo no tiene negocio de importación, ¿seguro que investigo?"). Ahorra barridos completos.
- **Flujo C/D/E (ambiguos):** PASO 0 cache + PASO 3b FIJAR MODELOS (ya cubierto en `01-arranque/planificador.md`).

**ESCRIBIR en el mapa — feedback de toda medición profunda (L3, 17-ago-2026):**
- **Flujo B (modelo barrido 7 fuentes):** tiene medianas reales → al cerrar, volcar/actualizar la entrada del modelo en `datos_mercado.json` con `fuente_medicion: flujo_b` (medianas frescas + `refrescar_antes_de_categoria` = hoy + cadencia de su categoría).
- **Flujo A (URL evaluada):** mide UNA unidad → NO escribe medianas (corrompería la estadística). SOLO añade entrada nueva (si el modelo no existe) o actualiza `nota`/`enlaces_muestra`, con `fuente_medicion: flujo_a`.
- **Regla de frescura:** si el mapa ya tiene el modelo fresco (< cadencia), no se re-vuelca; solo se actualiza `nota` si aporta algo nuevo.
- **Ruta pactada (L2):** `C:/Users/jacar/Desktop/JJImportMotors/datos_mercado.json`. Si no existe al cerrar → crear con `schema_version` y `ruta_canonica`; si existe → releer y MERGE por `slug` (no sobrescribir otras entradas).
- Actualizar también `memoria/modelos-medidos.md` como hasta ahora (registro histórico); el JSON es la capa consumible por ambas skills.
- **Aprendizaje automático (17-ago-2026):** ① si el precio de un anuncio real está >15% bajo `mediana_de` y el mapa es 🟢 → marcar `oportunidad: true`. ② si el veredicto del mapa contradice el resultado real del cierre → anotar la calibración en `nota`. ③ si el modelo NO está en el mapa (sin match de `slug`/`alias`) → añadirlo con `fuente_medicion: flujo_a` y medianas null (lo medirá el próximo estudio).

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

### 🏁 AUDITORÍA DE CIERRE — al elegir el candidato único (16-ago-2026)

> **Trigger:** el usuario elige UN candidato y el Flujo A termina (informe unidad + dossier + ZIP) — o dice "este es" / "cerramos". También si el encargo se **aborta** sin elección: se audita igualmente (aprender por qué no cerró). Es el cierre del embudo: el único momento donde se puede medir si el embudo AHORRÓ de verdad.

**Las 5 dimensiones (respuestas cortas, SIN navegar — solo mirar hacia atrás):**

| # | Dimensión | Qué medir |
|---|---|---|
| 1 | **Eficiencia** | Peticiones reales vs presupuesto del plan · ¿en qué fuente/paso se desbordó? |
| 2 | **Embudo** | Niveles recorridos (D→B→A) · candidatos por nivel · en qué nivel se descartó el 80% · ¿se gastó Fase 2 en alguno que caía por filtro temprano? |
| 3 | **Correcciones** | Nº de correcciones del usuario + causa raíz (briefing incompleto / plan mal calibrado / fuente que falló) |
| 4 | **Checkpoints** | ¿Se respetaron CP-D/CP1/CP2/CP3? ¿Cuál se saltó y qué costó? |
| 5 | **Resultado** | Candidato final (modelo, precio, origen, score) + dato de mercado aprendido |
| 6 | **Mapa de mercado** | ¿Volqué la medición a `datos_mercado.json`? ¿con qué `fuente_medicion` (`flujo_b`/`flujo_a`)? ¿actualicé o añadí entrada? (L4 — OBLIGATORIA) |

**Salidas obligatorias (3):**
1. `memoria/retrospectiva.md` → entrada de sesión con las 5 dimensiones (plantilla de cierre).
2. `memoria/modelos-medidos.md` → el modelo elegido con precio real verificado + fecha.
3. `memoria/encargos.md` → el encargo registrado (flujo, resultado, `refrescar antes de` = hoy + cadencia de su categoría: 2-4 sem) + ejecutar `py scripts/sync_indice.py` para actualizar `indice.json`.
4. **Cada corrección/fallo ≥ 1** → trampa en `memoria/trampas-encontradas.md` o anti-patrón propuesto en `06-reglas/anti_patrones.md` (proponer el texto al usuario, no editar a ciegas).
5. Si se verificó/negó un filtro o URL de portal durante la sesión → `memoria/filtros-portales.md` (fecha + parámetro).

**Regla de oro:** todo cierre produce ≥ 1 línea de aprendizaje. Si el embudo funcionó SIN correcciones → registrar el patrón en "lo que funcionó" como referencia reutilizable (para repetirlo, no solo para evitar errores).

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
| **E: STOCK** | Listados 15-25 (no fichas, A17) | — | 25 (3 categorías) |

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
- Si SÍ es tope de gama → doble pasada por kW (imprescindible, ver `02-flujos/playbook_filtrado.md` §Doble pasada).

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
- **mobile.de:** NUNCA >45 en una sesión. Avisar a 35 (regla dura, ver `02-flujos/extractores.md` §Presupuesto).

**Si se supera el budget sin veredicto:** STOP. Mostrar resumen parcial + preguntar si invertir más o cerrar como PARCIAL.

### Early exits (ABORTAR si se cumple)

| Exit | Condición | Acción |
|---|---|---|
| **EXIT 1** | Hueco <8% O <3 comparables ES | Informe rápido. "No sale." Actualizar `datos_mercado.json`. FIN. |
| **EXIT 2** | Hueco 8-15% | "Justo. ¿Invierto en Fase 2?" PREGUNTAR. |
| **EXIT 3** | Margen < umbral mínimo (Nicho 8%, Rotación 10%, 8-14k 10%) | Informe reducido sin publicidad. Entre umbral mínimo y objetivo (ej: Nicho 8-10%), avisar "margen justo, posible si vendibilidad ≥70". |

### Priorización por ROI y Deduplicación — ver `02-flujos/playbook_filtrado.md`

> Movido a `02-flujos/playbook_filtrado.md`: **Priorización por ROI** (`PRIORIDAD = MargenEstimado × VendibilidadEstimada × Urgencia`, puntuar "sin medir" y proponer top 3) y **Deduplicación entre fuentes** (huella año/km±2%/cv/precio±3%/combustible).

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

**Variaciones estacionales:** Anotar en §10 si el análisis es en temporada baja (otoño/invierno): "Precio de temporada baja, posible subida 5-8% en primavera". Ver tabla completa en `04-negocio/costes.md`.

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
**Checkpoint CP1:** Entregar el informe MODELO y **esperar la instrucción del usuario** (él elige candidato). NO preguntar "¿qué candidato investigo?" — si el encargo está completo, el usuario decide por iniciativa propia y desde ahí todo es ejecución por fases. Ver §PROTOCOLO DE MANDO.
```

### CASCADA DE INFORMES — qué sale y cuándo (12-ago-2026)

```
ENCARGO (Flujo B: MODELO)
│
├─ Fase 1 (3 fuentes) → 📋 INFORME MODELO + top 5 con ENLACES
│                      └ CP1: ¿Fase 2 o eliges candidato?
│
├─ Fase 2 (7 fuentes) → 📋 INFORME MODELO completo (7 fuentes)
│                      └ CP2: ¿aprobar candidato a fondo? (tras comparables, antes de veredicto)
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
| **2 · Avance con candidato** (usuario elige uno) | **INFORME DE UNIDAD** | Las 15 secciones de `03-informes/informe_tecnico.md` (o las 11 no negociables del flujo MODELO) SOLO del candidato elegido | `informe_unidad_<modelo>_<unidad>.md` + esqueletos `.txt` |
| **3 · Cierre** (veredicto 🟢/🔵) | **ZIP Laravel** | `informe.json` + `manifest.json` + `contenido/*.txt` + `fotos/` | `[coche_id].zip` → se sube a Laravel |

### 🗺️ MAPA DE PDFs y 📁 RUTAS DE GUARDADO — ver `05-operaciones/operaciones.md`

> **Detalle completo movido a `05-operaciones/operaciones.md`** (tabla 8 PDFs, reglas duras, rutas de guardado, aclaraciones `informe.json`).
> **Resumen crítico (no negociable):**
> 1. Claude genera los **PDFs de investigación** (búsqueda/unidad) y los **esqueletos `.txt` [MARCADOR]**; Laravel genera los PDFs de **venta** (dossier, ficha, folleto) con Blade + Browsershot.
> 2. `.md` del usuario → `informes\<marca>\<modelo>\` · JSON/ZIP → `laravel\` (NUNCA AppData\Roaming\Claude).
> 3. `informe.json` solo existe DENTRO del ZIP (lo genera `empaquetar.py`).
> 4. Normalizar nombres: minúsculas, sin tildes, guiones (`vw\tiguan`), fecha `YYYY-MM-DD`.

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

**🔴 5. REGLA A21 — ENLACES EN TODO (17-ago-2026 · la que el usuario más repite):** NO existe entrega sin enlaces. **Todo** candidato, comparable, fila de tabla, comparativa, informe, dossier, JSON y ZIP incluye **el enlace directo al anuncio** (ficha del vehículo, no búsqueda/filtro — A6) **y las fuentes con su URL** (sección "Fuentes consultadas" con cada portal y estado). Un dato, cifra o afirmación sin su enlace/fuente NO se entrega como concluido: se declara cómo se obtuvo o se pregunta. Revisar la entrega final con lupa: si cualquier candidato carece de enlace, el trabajo está incompleto.


### ⚡ EJECUCIÓN EN CASCADA — tras aprobar el plan de fase (16-ago-2026, ver §PROTOCOLO DE MANDO)

> **Sustituye al antiguo "modo automático".** La ejecución es automática DENTRO de la fase aprobada; el usuario aprueba cada fase. La ÚNICA decisión de negocio del usuario es QUÉ candidato investigar.

```
FASE APROBADA → EJECUTAR:

1. Briefing: reconocer parámetros (no preguntar si no falta nada — si falta, plan de fase)
2. Ejecutar la fase aprobada (ej. Fase 1 = 3 fuentes) → 📋 INFORME MODELO + top 5 con enlaces
3. ENTREGAR informe y ESPERAR (el usuario elige candidato → CP-D/CP1)

⏸️ ÚNICA PAUSA LEGÍTIMA: el usuario indica el candidato
   "investiga el de 8.999 €" → 1 candidato
   "investiga estos 3" / "compáralos" → varios → comparativa antes
   "el mejor" → Claude propone 1 (con justificación) y sigue

TRAS ELEGIR CANDIDATO (nueva fase aprobada → ejecutar)
4. 📸 Fotos: descargar automáticamente
5. Si VARIOS → 📊 COMPARATIVA primero (tabla lado a lado), luego informes
6. 📋 INFORME UNIDAD completo (15 sec, score 0-100)
7. 🟢/🔵 → 📄 DOSSIER CLIENTE
8. 📦 ZIP completo: informe.json + manifest + esqueletos .txt + fotos
```

**Solo PAUSAR y preguntar (además del OK de fase):**
1. **Veredicto 🟡/🔴** → entregar informe y pedir decisión (no generar dossier)
2. **Banderas críticas de seguridad** (VIN ausente, no declara "libre de accidentes") → avisar y marcar en el plan de negociación, PERO seguir generando el paquete
3. **Encargo incompleto/vago** → briefing/plan de fase (preguntar solo lo que falta)

**NUNCA preguntar dentro de una fase aprobada:** "¿continúo?", "¿descargo las fotos?", "¿genero el informe?". El informe MODELO se entrega y **se espera la instrucción del usuario** — no se le pregunta, es él quien elige el candidato.

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

> 📄 **Ver `03-informes/informe_tecnico.md` para estructura completa con score 0-100 y bloques `[MARCADOR]`.** Resumen rápido:

1. Cabecera (coche_id · score global · recomendación)
2. Cobertura 7 fuentes con ⭐ confianza por fuente
3. Oferta española (comparables con sello 🟢🟡🔴 + cuartiles + días medio)
4. Oferta alemana (ídem + portal + vendedor + VB + cambios precio)
5. Candidato seleccionado (ficha completa + ficha técnica + hallazgos)
6. **Comparable ajustado** → cálculo línea a línea (ver `03-informes/comparables.md`)
7. **Coste puesto en Huelva** → desglose + análisis sensibilidad IEDMT (ver `04-negocio/costes.md`)
8. Margen y veredicto (contra 4 referencias: mediana + Q1 + ajustado + mínimo)
9. **Vendibilidad** 5 factores justificados (100 puntos)
10. **Plan de negociación** con mensaje alemán + precio tope + backups
11. Riesgos y banderas (con plan de mitigación por riesgo)
12. Alternativas reales con URLs
13. **Predicción de venta** (4 escenarios: óptimo/base/conservador/pesimista)
14. Acción inmediata (pasos numerados con plazo)
15. Score global de oportunidad (6 dimensiones, 0-100)

**_outputs del informe UNIDAD (archivos .txt en ZIP):**
- `informe-interno.txt` (análisis JJ Import Motors · ver `03-informes/informe_tecnico.md`)
- `dossier-cliente.txt` (PDF profesional para cliente · ver `03-informes/dossier_cliente.md`) — solo si veredicto 🟢/🔵
- `ficha-publicitaria.txt` (venta en portales + folleto del coche · contrato.md §publicidad — incluye `[VALORACION]`, texto de venta presentable para el folleto)
- `redes-sociales.txt` + `anuncio-portales.txt` (ver contrato.md)

**Cuándo emitir dossier cliente:** 🟢 Comprar siempre · 🔵 Comprar si baja de precio siempre · 🟡 Dudoso solo si el cliente pidió evaluarlo · 🔴 Descartar nunca (carta breve en su lugar).

**⚠️ QUIÉN GENERA CADA PDF (revisado 18-ago-2026):**
- **Claude SOLO genera 2 PDFs** (siempre para el equipo): el **informe de búsqueda** y el **informe de unidad** (`informe_busqueda_*.pdf` / `informe_unidad_*.pdf`, con plantilla de marca + Chrome).
- **Claude genera el TEXTO (esqueletos `.txt` [MARCADOR])** de TODOS los demás documentos: `ficha-publicitaria.txt` (ficha + folleto del coche), `informe-interno.txt`, `dossier-cliente.txt`. **Claude decide qué se pone y qué NO** en cada uno — especialmente en el folleto del cliente.
- **Laravel SOLO maqueta**: convierte los esqueletos `.txt` en PDF (Blade + Browsershot) cuando el coche ya está en inventario. Laravel **no decide contenido**: muestra lo que Claude escribió.
- **El folleto del coche** (`folleto-coche.blade.php`) se genera desde `ficha-publicitaria.txt`. Claude escribe el bloque `[VALORACION]` (1-2 frases de venta) y `[ARGUMENTO]`/`[EQUIPAMIENTO]` presentables. **PROHIBIDO** en el folleto/cliente: margen, honorarios, negociación, estrategia de venta, `verdict_reasoning`, `recommendation` — son internos (informe interno) y nunca van al folleto.
- Claude NO genera los PDFs de venta (ficha, folleto, dossier, informe interno) durante la investigación — esos salen del panel cuando el coche está en inventario.

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

## 📈 VENDIBILIDAD — 5 factores, 100 puntos — ver `03-informes/comparables.md`

> **Movido a `03-informes/comparables.md`**: los 5 factores (Demanda 30 · Escasez 25 · Atractivo 20 · Equipamiento 15 · Km/historial 10), la puntuación por umbrales y la **Matriz de decisión** (Margen × Vendibilidad → 🟢/🔵/🟡/🔴). Cargar ese archivo en Flujo A para calcular y justificar la vendibilidad.

---

## 🛡️ ANTI-PATRONES BLOQUEADOS

Las 21 reglas duras (A1-A21) viven en `06-reglas/anti_patrones.md`. Cargarlas cuando se duda de una práctica o antes de cerrar un informe.

> 🔴 **A21 — ENLACES SIEMPRE (17-ago-2026 · regla que el usuario más repite):** TODO lo que se entregue —candidatos, comparables, comparativas, informes, dossier, JSON/ZIP— lleva SIEMPRE el **enlace directo al anuncio** (ficha del vehículo) y las **fuentes con su URL**. Un dato sin su enlace no se entrega como concluido: se indica cómo se obtuvo o se pide permiso. Sin enlaces la entrega NO vale.

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
- **A15** La búsqueda web/snippets NO es método de sondeo — D1 SIEMPRE con navegación real (datos inconsistentes)
- **A16** El sondeo D1 es por FILTROS, no por modelo: una pasada con los filtros del encargo devuelve TODOS los modelos; prohibido elegir 3-4 a mano ni dejar "otros por explorar" sin sondear. Potencia = mínimo ≥Xcv, no solo la variante tope.
- **A21** ENLACES SIEMPRE (17-ago-2026): TODO lo que se entregue lleva enlace directo al anuncio (ficha) + fuentes con URL. Candidatos, comparables, comparativas, informes, dossier, JSON y ZIP. Un dato sin su enlace NO se entrega como concluido. Es la regla que el usuario más repite.

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
├── informe.json                    ← JSON completo del CONTRATO (`03-informes/contrato.md`)
├── manifest.json                   ← Metadatos del paquete
├── contenido/
│   ├── ficha-publicitaria.txt      ← Esqueleto [BLOQUE] → ficha-coche + folleto del coche (el bloque [VALORACION] alimenta "Nuestra valoración" del folleto, SOLO texto de venta al cliente, sin datos internos)
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

**Qué hace Laravel con cada archivo:** ver `03-informes/contrato.md`.

**Lo que NUNCA va en el ZIP:** PDFs pre-generados, Excels, datos de otros coches, anotaciones internas tipo "(NUEVO)" o "revisión anterior".

---

## 📄 JSON por flujo

| Flujo | Salida JSON | Estructura |
|---|---|---|
| **A: UNIDAD** | `informe.json` dentro del ZIP | `{_meta, vehiculo, anuncio, investigacion, balance, veredicto, costes, mercado, avisos, publicidad}` — un solo coche, contrato completo |
| **B: MODELO** | `informe.json` suelto en `export/` | Misma estructura que A, pero SIN `publicidad` (no se generan esqueletos de venta). El usuario decide si promover a Flujo A después. |
| **C: MERCADO** | `informe.json` agregado | `{_meta, modelos: [{modelo, segmento, hueco_pct, n_uds_de, vendibilidad_estimada, mejor_anuncio_url}, ...]}` — N entradas, sin detalle por unidad. Se guarda en `export/scouting_<fecha>.json` para histórico. |
| **E: STOCK** | `stock_<fecha>.json` en `export/` | `{_meta, categorias: [{nombre, modelos: [{modelo, n_uds_de, n_uds_es, mediana_de, mediana_es, hueco_pct, veredicto, enlace_ejemplo}], ...}]}` — catálogo por categorías para Laravel. Sin `publicidad`. |

---

## ✅ CHECKLIST

**Antes de gastar**
- [ ] Detecté el flujo correcto (A/B/C/D/E)
- [ ] **A21: pensé qué enlaces llevará la entrega (anuncio + fuentes) antes de cerrar**
- [ ] Tabla cobertura con las fuentes que apliquen al flujo
- [ ] Consulté `indice.json` y comprobé frescura
- [ ] Miré el registro de clientes (Flujo B)
- [ ] Confirmé la **modalidad de honorarios M1/M2/M3** del encargo (3 fallos reales por asumir)
- [ ] Encargo abierto sin URL → mostré el **PLAN DE FASE** (ver `01-arranque/planificador.md`) antes de navegar
- [ ] Si amplié filtros del encargo (año, km, precio), lo declaré ANTES (A13)
- [ ] Waypoint 📍 en cada mensaje · tras cada desviación retomé el paso (A14)
- [ ] **PASO 0 cache**: consulté `memoria/encargos.md` + `memoria/modelos-medidos.md` + `indice.json` (frescura <3 sem)
- [ ] **Mapa de mercado** (`datos_mercado.json`, ruta pactada): lo consulté para contexto del modelo (A/B) o para fijar modelos (C/D/E) · si medí un modelo, volqué la medición al mapa al cerrar con `fuente_medicion` (L4) y lo registré en el checklist de la auditoría de cierre
- [ ] Cuaderno de sesión al día (correcciones con hora, releído antes de cada plan de fase)
- [ ] **PLAN DE BÚSQUEDA mostrado y aprobado ANTES de abrir el primer portal** (filtros URL/clic · bandas · segmentación · lotes · presupuesto · OK) — ver `01-arranque/planificador.md` PASO 4
- [ ] Plan de fase aprobado por el usuario ANTES de cada fase (Protocolo de Mando)
- [ ] Auditoría de fase pasada al completar cada paso (entregable · camino · correcciones · cobertura)
- [ ] Auditoría de cierre al elegir candidato único o abortar (eficiencia · embudo · correcciones · checkpoints · resultado → 3 salidas)

**Al medir**
- [ ] Fase 1 con las 3 fuentes obligatorias (Coches.net, mobile.de, AutoUncle)
- [ ] `powertype=kw` · Verifiqué `initialSearch` en Coches.net
- [ ] PVP y CO₂ de km77 (si Flujo A)
- [ ] Medí DE en mobile.de directo, no solo AutoUncle
- [ ] Usé navegación real primero (navegar, filtrar, leer visible) antes que inyección JS
- [ ] Ante bloqueo, probé recarga + navegación real + `02-flujos/extractores.md` antes de marcar
- [ ] No descarté por silencio (A1) · mobile.de en cobertura OK (A2)
- [ ] CO₂ de km77 o BOE, no estimación (A3)

**Al evaluar (Flujo A)**
- [ ] Filtro admisión: ±2 años, ±40% km, ajustes capados ±25%
- [ ] <15 comparables → rango · Ajuste línea a línea · Descripción entera
- [ ] Regla 6/6000 · CO₂ (estimado → decirlo)
- [ ] Ahorro contra mediana **y** cuartil bajo (A4)
- [ ] Precio máximo de compra en informe (A5)
- [ ] Tablas con columna ENLACE (A6)
- [ ] **A21: todo dato/candidato/comparable lleva enlace al anuncio + fuentes con URL** (sin enlaces la entrega no vale)

**Al cerrar**
- [ ] Actualicé `datos/registro_cierres.json` → Ver `05-operaciones/operaciones_cierre.md`
- [ ] Calculé KPIs mensuales si aplica → Ver `05-operaciones/operaciones_cierre.md`

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
| **`03-informes/comparables.md`** | Flujo A: ajuste de comparable | 9 claves, filtro admisión, comparable sin muestra, detección competencia, primas equipamiento |
| **`04-negocio/costes.md`** | Flujo A: desglose económico | IEDMT (fórmula + tabla), precio máximo de compra, IVA, moneda extranjera, ejemplo completo |
| **`04-negocio/riesgos.md`** | Flujo A/B: motor problemático | Tabla riesgos por motor (DQ200, EA888, N47, etc.), verificación, reglas descarte automático |
| **`05-operaciones/operaciones_cierre.md`** | Cierre de venta + KPIs | Registro cierre JSON, KPIs, changelog, sync Desktop ↔ Skill, encargo permanente |

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
