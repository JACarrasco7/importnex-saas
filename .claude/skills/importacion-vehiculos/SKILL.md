---
name: importacion-vehiculos
description: >
  Negocio JJ Import Motors (Huelva): servicio de búsqueda e importación de coches
  (desde Alemania y dentro de España). NO compra stock, solo oferta el servicio
  con honorarios fijos. Tres flujos: UNIDAD (URL concreta), MODELO (buscar un modelo),
  MERCADO (escanear oportunidades). Usa 7 fuentes. Genera ZIP para Laravel.
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
  - calcular coste importacion
  - iedmt
  - precio maximo de compra
---

# Búsqueda e importación de vehículos — JJ Import Motors

Localizar coches (desde Alemania y dentro de España) y **ofertar el servicio de importación/gestión** a clientes. **NO compramos stock** — solo honorarios por el servicio. El cliente es quien compra el coche.

> 📁 **Compañeros:** `navegacion_real.md` (MÉTODO PREFERIDO — navegar como humano) · `paginas_reales.md` (estructura REAL capturada de los 7 portales) · `playbook_filtrado.md` (técnicas de filtrado/búsqueda para Claude Desktop) · `extractores.md` (URLs, trampas, diccionario) · `contrato.md` (JSON + esqueleto) · `operaciones.md` (carpetas, scripts) · **`anti_patrones.md`** (reglas duras 6)
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
Anti-patrones bloqueados: 8 (ver §Anti-patrones)
Checkpoints: CP1 tras informe MODELO (esperar elección de candidato) | CP2 tras comparable | CP3 antes de veredicto
Origen DE vs ES: si no se especifica, buscar en ambos mercados y comparar dónde sale mejor → costes.md §Origen
Briefing encargo: preguntar críticos ANTES de navegar → `briefing_encargo.md`
Tope de gama: doble pasada por kW SIEMPRE → `playbook_filtrado.md` §Doble pasada
```

---

## 🎯 LOS 3 FLUJOS — leer PRIMERO

| Flujo | Disparador | Profundidad | Output | ZIP Laravel |
|---|---|---|---|---|
| **A: UNIDAD** | URL pegada o "evalúa este" | Fase 1 + Fase 2 | Informe UNIDAD (15 sec) + dossier + folleto | ✅ Sí |
| **B: MODELO** | "busca [modelo]" sin URL | Fase 1 + Fase 2 si pasa | Informe MODELO + top 5 | ❌ No |
| **C: MERCADO** | "qué merece la pena", "top modelos" | Solo Fase 1, N modelos | Informe BUSQUEDA | ❌ No |

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
→ FLUJO C (MERCADO) — preguntar preferencias al usuario
```

**Antes de navegar en Flujo A/B → briefing de encargo (`briefing_encargo.md`):**
1. Extraer parámetros dados (modelo, año mín, km máx, presupuesto...).
2. Preguntar SOLO los críticos que falten (tabla de faltantes).
3. Si es tope de gama → confirmar potencia (activa doble pasada).
4. Guardar encargo en memoria al cerrar.
> Fallo real 12-ago: se navegó sin preguntar potencia → se perdió el OPC de 8.999 € mal etiquetado.

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

## 🧠 ACTUALIZACIÓN DE MEMORIA — Triggers automáticos

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

Las 6 reglas duras (A1-A6) viven en `anti_patrones.md`. Cargarlas cuando se duda de una práctica o antes de cerrar un informe.

**Resumen rápido:**
- **A1** No descartar por silencio (sello `man`, no exclusión)
- **A2** mobile.de SIEMPRE en cobertura (OK o bloqueada+intentos)
- **A3** CO₂ y PVP de km77 o BOE, nunca estimación
- **A4** Veredicto contra mediana Y cuartil bajo
- **A5** Precio máximo de compra en todo informe Flujo A
- **A6** Tablas con columna ENLACE clickable
- **A7** Cobertura completa: siempre las 7 fuentes, nunca cifras con <7 sin PARCIAL
- **A8** AutoScout24 solo para contar, NUNCA precio

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
│   ├── dossier-cliente.txt        ← Esqueleto [BLOQUE] → dossier.blade.php (PDF cliente)
│   ├── informe-interno.txt         ← Esqueleto [BLOQUE] → informe-interno.blade.php (PDF equipo)
│   ├── redes-sociales.txt          ← [GANCHO] [POST_LARGO] [STORIES] [HASHTAGS]
│   └── anuncio-portales.txt        ← [TITULO] [DESCRIPCION] [AVISO_LEGAL]
└── fotos/
```

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
- [ ] Detecté el flujo correcto (A/B/C)
- [ ] Tabla cobertura con las fuentes que apliquen al flujo
- [ ] Consulté `indice.json` y comprobé frescura
- [ ] Miré el registro de clientes (Flujo B)

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
