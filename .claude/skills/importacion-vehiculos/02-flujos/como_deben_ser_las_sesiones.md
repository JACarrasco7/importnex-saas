# 🗓️ Cómo deben ser TODAS las sesiones de estudio/búsqueda (21-ago-2026)

> **Documento maestro del flujo de trabajo conjunto** de las dos skills:
> `estudio-mercado` (mide el mercado) + `importacion-vehiculos` (busca unidades).
> Léelo SIEMPRE al inicio de sesión y al lanzar cualquier modelo.
> Lee también: `schema_datos_mercado.md` §Cola de trabajo (el enrutador `siguiente_*`).

---

## 🎯 Principio rector: UN MODELO POR PASADA, NUNCA UN SEGMENTO DE GOLPE

> ⚠️ **Error detectado 18-21 ago-2026:** barrer un segmento entero (ej. "Compactos deportivos" = GTI + Cupra + Astra OPC + Focus ST + i30N + Mégane RS...) en una sola sesión:
> - Supera el límite de 5h de Claude sin terminar.
> - Mezcla generaciones y equipamientos sin que el usuario vea el patrón.
> - Al final las unidades NO encajan en la búsqueda real (los Golf que salen no son los que el cliente pide).
> - Se pierden 3 días sin resultado accionable.

**La unidad de trabajo es el MODELO (marca + versión), no el segmento.** El segmento solo sirve para PRIORIZAR qué modelo toca a continuación.

---

## 🔁 El pipeline rígido (no es opcional)

```
① SEGMENTACIÓN (prompt maestro / conversación)
   → define los 6 segmentos y sus modelos concretos
      ↓
② ESTUDIO DE MERCADO (skill estudio-mercado) — UN MODELO POR PASADA
   → verificar el mercado de UN modelo (¿hueco? ¿demanda? ¿rotación?)
   → resultado: veredicto 🟢/🟡/🔴 + estado_cola = estudiado
      ↓  SOLO si 🟢 o 🟡 con hueco positivo
③ BÚSQUEDA (skill importacion-vehiculos) — UN MODELO POR PASADA
   → Flujo B (modelo) → si hay candidato → Flujo A (unidad)
   → resultado: candidatos con enlaces + estado_cola = buscado
      ↓
④ FEEDBACK (automático)
   → la búsqueda vuelca mediciones reales al mapa (fuente_medicion: flujo_b)
   → estado_cola = buscado · actualizar siguiente_*
```

**Regla de oro:** NO se busca unidades de un modelo sin que su mercado esté verificado primero. La búsqueda a ciegas es el error de los 3 días.

---

## 📋 Formato de una sesión tipo (modelo por modelo)

Cada sesión trabaja sobre **UN modelo** y tiene 5 fases con PARADA OBLIGATORIA entre ellas:

```
SESIÓN — [Modelo + versión] · [fecha]

FASE A · PREPARACIÓN (2 min)
  □ Estado en la cola (pendiente_estudio / estudiado / pendiente_busqueda)
  □ ¿Ya medido? (PASO 0 cache: modelos-medidos.md + indice.json)
  □ Criterios REALES del cliente (no asumidos): precio máx, km máx, año mín,
    equipamiento imprescindible, color. Los escribe el USUARIO.
  □ Perfil objetivo resumido en 1 línea → ACK del usuario
  ↓ PARADA: usuario confirma el modelo y el perfil

FASE B · ESTUDIO DE MERCADO (5 min) — skill estudio-mercado
  □ ES (Coches.net): oferta, suelo, mediana, sello
  □ DE (mobile.de): oferta orgánica, suelo orgánico, mediana orgánica, sello, full equipamiento
  □ Hueco bruto y neto + veredicto + estado_cola = estudiado
  ↓ PARADA: mostrar resultado del mercado al usuario

FASE C · DECISIÓN DE ENCAJE (1 min)
  □ ¿El hueco es real y el modelo encaja en el perfil? (regla ≥3 señales / neto >0)
  □ Usuario decide: SEGUIR (búsqueda) / AJUSTAR (filtros) / DESCARTAR (siguiente modelo)
  ↓ PARADA: usuario decide

FASE D · BÚSQUEDA DE UNIDADES (5 min) — skill importacion-vehiculos (SOLO si seguir)
  □ Flujo B: listado modelo-por-modelo → 6-8 tarjetas orgánicas
  □ 1-2 candidatos con enlace (precio/km/año/equipamiento/vendedor)
  □ estado_cola = buscado + feedback al mapa (fuente_medicion: flujo_b)
  ↓ PARADA: mostrar candidatos al usuario

FASE E · CIERRE (2 min)
  □ ¿Elige un candidato? → Flujo A (unidad) en sesión siguiente o ahora
  □ Volcar medición real al mapa + actualizar siguiente_*
  □ Resumen 3 líneas para el histórico (modelos-medidos.md)
```

**Total: ~15 min por modelo** (vs 3 días por segmento). 12 modelos = 3 horas bien invertidas con resultado accionable en cada parada.

---

## 💬 EJEMPLO REAL — plan + conversación completa (Golf 7.5 TCR)

> **Objetivo:** ver EXACTAMENTE cómo se ve una sesión buena (eficiente en tokens) vs una sesión que quema contexto. El formato es SIEMPRE compacto: 1 línea de ACK, tablas, sin párrafos de relleno.

### 1) El PLAN que Claude muestra (FASE A — compácto, ~120 tokens)

```
📋 PLAN — VW Golf 7.5 TCR (siguiente_estudio)
· Estado cola: pendiente_estudio → ESTE estudio lo pasa a estudiado
· Mercados: DE (mobile.de) + ES (Coches.net) · perfil: [a rellenar por usuario]
· Filtros: ≥2018 · ≤120.000 km · ≥200 kW · 5p · SIN banda · full (cuadro digital+techo+LED)
· Embudo: suelo → mediana (1 listado asc por mercado) · SOLO orgánicas en DE (top/tic/ads fuera)
· Presupuesto: ~4 peticiones (2 ES + 2 DE) · sin fichas (A17)
· PARADAS: tras FASE B (resultado) · tras FASE C (decisión)
¿OK?
```

### 2) La conversación tipo (lo que el usuario ve)

```
👤 USUARIO: "Siguiente: Golf 7.5 TCR. Estúdialo."
🤖 CLAUDE (FASE A):
   "📋 PLAN [arriba]. Perfil objetivo que necesito: precio máx, km máx, año mín, 
    equipamiento imprescindible, color. ¿Me lo das o busco con el perfil estándar showstopper?"
👤 USUARIO: "25.000 € máx, 100.000 km, 2018+, full, blanco o gris."
🤖 CLAUDE (FASE B — estudia):
   "### Golf 7.5 TCR · 2026-08-21
    · DE: oferta 214 (org 198) · suelo org 23.900 € · mediana 27.400 € · sello Buen precio
    · ES: oferta 9 · suelo 26.900 € · mediana 29.800 € · sello Precio justo
    · Hueco bruto: +8,0% · neto: +0,5% (coste 2.929 + IEDMT 1.800)
    · Full: cuadro digital+techo+LED sí en el tramo alto · estado_cola → estudiado
    ↓ PARADA: ¿SEGUIR (buscar unidades) / AJUSTAR (afinar km/precio) / DESCARTAR?"  [≈150 tokens]
👤 USUARIO: "SEGUIR"
🤖 CLAUDE (FASE D — Flujo B, 6-8 tarjetas orgánicas):
   "### Candidatos Golf 7.5 TCR (6 orgánicas DE, full)
    1. 24.900 € · 2019 · 61k km · cuadro virtual+techo+LED · Autohaus HH · [URL]
    2. 25.400 € · 2018 · 74k km · full+ACC · Privatanbieter · [URL]
    3. 27.100 € · 2020 · 38k km · full · Händler 4.8 (112) · [URL]
    → 1 y 2 encajan perfil (≤25k, ≤100k, full) · estado_cola → buscado
    ↓ PARADA: ¿eliges candidato (Flujo A) / siguiente modelo?"  [≈180 tokens]
👤 USUARIO: "El 2" → Flujo A en la MISMA sesión o la siguiente.
```

### 3) Eficiencia de tokens — reglas duras de conversación

| ❌ NO hacer | ✅ Hacer |
|---|---|
| Párrafos explicando "he analizado el mercado..." | Tablas/1 línea por modelo, solo números + veredicto |
| Repetir criterios del perfil en cada mensaje | El perfil se fija 1 vez en FASE A y se asume (referencia al ACK) |
| Narrar cada clic/navegación | Solo el RESULTADO (oferta/suelo/mediana), no el "cómo" |
| Mostrar las 20 tarjetas de un listado | Solo las 6-8 orgánicas y SOLO las que encajan |
| 3-4 mensajes de relleno entre fases | Un solo mensaje por fase con la PARADA al final |
| Preguntar "¿continúo?" repetido | La PARADA está en el plan aprobado; solo se pregunta en C/E |
| Guardar cada paso en memoria | Solo se escribe al mapa/cola al CIERRE de fase (B/D/E) |

**Métrica objetivo por modelo:** ~3 mensajes de Claude (FASE B + FASE D + cierre) ≈ 450-550 tokens de salida + ~200 del plan. Un estudio de 12 modelos ≈ 7-9k tokens totales (vs 60k+ del barrido por segmento).

---

## 🚦 Reglas anti-bucle (obligatorias en ambas skills)

| Señal | Acción | Excepción |
|---|---|---|
| 0-1 encaje en 6-8 tarjetas | Parar · ajustar filtros (km +20%, año −1, full→semi-full) · reintentar **1 vez** | Si el perfil era irreal, pedir criterios nuevos al usuario |
| 3+ reintentos fallidos en el mismo modelo | 🛑 **DESCARTAR** modelo, pasar al siguiente (`siguiente_*`) | Nunca insistir más |
| Captchas/página caída ×2 | Marcar bloqueada · seguir con otra fuente · reintentar al final | Si todas caen, abortar sesión |
| Modelo sin hueco neto (neto <0) | Avisar ANTES de gastar peticiones → `descartado` | Solo re-estudiar si cambia el mercado |
| 5h de sesión alcanzadas | Guardar estado en la cola (`siguiente_*` + `estado_cola`) y continuar en la próxima sesión | El progreso nunca se pierde |
| **Sesión corta (<5h) interrumpida** | Marcar el modelo `estudiando` con `nota` del progreso (fase alcanzada, listados leídos, pendientes) para reanudar | No dejar el progreso solo en el chat |
| **Dos sesiones en paralelo / merge** | Al escribir la cola, MERGE por slug: solo cambian los `estados` que esta sesión tocó y `siguiente_*` (E10) | Nunca sobrescribir estados de modelos ajenos |

> **Alias de modelo (dry-run 1):** al crear la entrada de un modelo por primera vez (ej. "Golf 7.5 TCR"), añadir `alias` razonables (`golf-75-tcr`, `golf-tcr`, `golf 7.5 tcr`) para que el lookup L1 no falle y no se dupliquen slugs.

---

## 🗂️ Los 6 segmentos (solo para priorizar, NUNCA para barrer de golpe)

| Segmento | Modelos concretos (1 a 1) | Categoría |
|---|---|---|
| Compactos deportivos | Golf 7.5 TCR · Golf 8 GTI/Clubsport · Golf R · Cupra León VZ · Seat León Cupra · Astra J OPC · Focus ST · i30 N · Mégane RS · A3 S3/RS3 | showstoppers |
| Compactos aspiracionales | Golf 7/8 R-Line · Seat León FR · A3 S-Line · Clase A · Serie 1 M Sport · i30 N Line | alta_rotación |
| SUVs deportivos | Ateca Cupra · Formentor VZ/VZ 310 · Tiguan R · RSQ5 · GLA 35/45 | showstoppers |
| SUVs aspiracionales/utilitarios | Tiguan 1.4/2.0 TSI · Ateca Xperience/FR · Q3 · Q5 · X1 · Karoq | alta_rotación |
| Shooting brake deportivos | Arteon SB R/R-Line · Golf 7/8 Variant R · BMW 3/4 Touring M · A4/A6 Avant | showstoppers |
| Shooting brake aspiracionales | Golf Variant · León ST · Octavia Combi · A4 Avant 2.0 TDI · Serie 3 Touring | familia/diario |

**Prioridad dentro de un segmento** (para `siguiente_estudio`):
1. Modelos que el usuario nombra explícitamente.
2. Caducidad más próxima (`refrescar_antes_de_categoria`).
3. Demanda ES conocida (Golf y compactos VAG lideran, coches.net ene-2026).
4. Modelos "NO medido" del mapa (nunca quedan sin estudiar).

---

## 🧭 Inicio de sesión (checklist 30 segundos)

```
□ Leer cola_trabajo del mapa → ¿cuál es siguiente_estudio / siguiente_busqueda?
□ Si siguiente_estudio → montar sesión FASE A-D del MD (mercado primero)
□ Si siguiente_busqueda → montar sesión FASE D (búsqueda, mercado ya medido)
□ Si nada pendiente → proponer modelos "NO medido" por prioridad
□ Antes de escribir: ver §EJEMPLO REAL (plan + conversación) para el formato compacto de tokens
```

## ⛔ REGLAS DE ORO — la nube NO improvisa, NO decide por su cuenta (23-ago-2026 v0.3.8)

> **Problema detectado (23-ago):** la IA recibía zonas con "si conviene, si hay muestra, si el usuario lo pide, opcionalmente…" → interpretaba y se salía del flujo. Esto se acabó.
>
> **A partir de ahora, toda decisión que pueda tener 2 formas está resuelta de antemano** en la plantilla `informe_mercado.md` (§REGLAS ESTRICTAS SI/ENTONCES) o aquí mismo. La nube **NO consulta, NO propone alternativas, NO improvisa**.

### 🔒 Comportamiento OBLIGATORIO en cada paso

| Momento | Comportamiento OBLIGATORIO |
|---|---|
| Inicio de sesión | **LEER** este MD + `informe_mercado.md` + `schema_datos_mercado.md`. Sin esto, NO empezar. |
| Antes de generar el informe | **APLICAR las 10 secciones en orden fijo** de la plantilla. Sin saltarse ninguna. |
| Si falta muestra para segmentar | Poner 1 línea justificada y seguir. NO decidir "lo omito porque es largo". |
| Si el usuario pide algo no contemplado | Hacerlo + añadir 1 línea: "He añadido [X] porque me lo has pedido explícitamente." |
| Si hay ambigüedad (ej. "estudia el Golf") | PARAR y preguntar UNA sola vez. NO asumir versión/año/precio. |
| Antes de dar el informe por terminado | **RELLENAR el CHECKLIST final** de la plantilla con ✅/❌. NO entregar si hay ❌. |

### 🚫 Comportamiento PROHIBIDO

- ❌ Inventar datos, URLs, precios, candidatos.
- ❌ Mezclar datos recordados con datos actuales del JSON.
- ❌ Decidir formato (siempre 1 .md; PDF solo si el usuario lo pide explícito).
- ❌ Decidir gastos (por defecto 1.500 €; cambiar SOLO si el usuario da otra cifra).
- ❌ Saltar secciones de la plantilla "porque el informe ya es largo".
- ❌ Cambiar el orden de las secciones (el orden es parte del contrato).
- ❌ Usar jerga IA en el informe (sincronizado, merge, volcado, fuente_medicion).
- ❌ Generar PDF, ZIP, ni formatos extra sin petición explícita.
- ❌ Crear un informe modelo separado si el usuario solo pidió completar mercado.
- ❌ Decir "sincronizado" o "volcado OK" si no ha verificado el JSON.
- ❌ Añadir sesión narrativa ("he consultado 3 portales, he cruzado datos…") → solo el resultado.

### 📏 Si la nube tiene una duda que NO está resuelta aquí

**PARAR y preguntar UNA sola vez.** Nada de "interpretaré lo más razonable". Si la pregunta es trivial, decidir con la regla por defecto (1.500 € gastos, 1 .md, 10 secciones, etc.). Si la pregunta es de negocio (ej. "qué versión del Golf estudio"), preguntar al usuario.

---

## 🔓 Excepciones y atajos por flujo (21-ago-2026, tras dry-run)

> El pipeline rígido (mercado antes que búsqueda) NO bloquea nunca el encargo del usuario. Matriz de aplicación:

| Flujo | ¿Mercado previo? | Atajo si falta |
|---|---|---|
| **A (URL)** | Exento | Al cerrar: vuelco al mapa; modelo nuevo con medianas → `buscado`, sin → `pendiente_estudio` |
| **B (modelo)** | Requerido | **Mini-estudio inline** (1 listado ES + 1 DE + cruce = 4-6 peticiones) → vuelco con `fuente_medicion: mini_estudio` → continuar. NUNCA abortar |
| **C (mercado/top)** | No — ES el estudio | Sondea y vuelca cada modelo tocado (`flujo_e_delta`) + cola actualizada |
| **D (descubrimiento)** | No — es descubrimiento | Los modelos del informe MODELOS entran a la cola como `pendiente_estudio` |
| **E (stock)** | No — igual que C | Igual que C |

**"Mercado verificado" = veredicto 🟢/🟡 Y confianza_precio ≥3 Y pendiente_fase2=false Y no caducado.** Un veredicto verde con confianza 2 (caso `cupra-leon` pre-corrección) NO cuenta.

**El usuario manda (L6 + Protocolo de Mando):** si insiste en buscar un modelo sin estudio o marcado 🔴 → aviso de 1 línea y **proceder**, anotando en el mapa `nota: "a criterio del usuario (L6)"`. El pipeline es el camino por defecto, no una camisa de fuerza.

**Mini-estudio inline (plantilla):**
```
1. Coches.net: ?fi=Price&or=1 → oferta_es + suelo + mediana visual (1-2 lecturas)
2. mobile.de: &sb=p&od=up solo base-result-listing-* → oferta_de + suelo + mediana (1-2 lecturas)
3. Cruce: hueco bruto/neto → veredicto provisional
4. Volcar al mapa (mini_estudio, confianza 2-3) + estado_cola
   → continuar con la búsqueda del Flujo B en la MISMA sesión
```

---

## 📦 Output por modelo (siempre igual, siempre comparable)

```
### [Marca Modelo Versión] · [fecha]
- DE: oferta X (orgánica Y) · suelo orgánico Z € · mediana W € · sello
- ES: oferta X · suelo Z € · mediana W € · sello
- Hueco bruto: ±V% · neto: ±N% · equipable: ±E%
- Candidatos (2-3): [precio][año][km][equipamiento][vendedor] → URL
- Veredicto: 🟢/🟡/🔴 · Mejor mercado: DE/ES/paridad · Encaja perfil: SÍ/NO
- estado_cola: [estudiado | buscado | descartado]
- Próximo modelo sugerido: [X] (siguiente_* del mapa)
```

---

## 📄 REGLAS DE ENTREGA — escrito para el usuario, no para otra IA (23-ago-2026)

> **Problema real detectado (23-ago):** el usuario recibió 8+ archivos por el mismo trabajo — 3 MD del mismo estudio (nube sin persistencia re-genera todo) + 1 PDF por cada MD (extensión Drive guarda PDF+MD a la vez) + un informe MODELO del Golf R separado cuando solo se le pidió **completar la cobertura de la sección 6 del estudio existente**. Nada quedó ni más resumido ni más legible.
>
> **Principio (23-ago-2026 v2):** el informe es **para Jacar**, no para otra IA. Lenguaje de negocio, sin jerga técnica. La estructura y el tono van en `informe_mercado.md` (la plantilla obliga).

**Reglas duras de entrega (obligatorias):**

1. **NUNCA regenerar un estudio/encargo ya cerrado desde cero.** Si el mapa (`datos_mercado.json`) o `modelos-medidos.md` ya tiene el modelo con `refrescar_antes_de` vigente → hacer **delta** (solo cambios) o preguntar. La nube no persiste: si el usuario dice "retoma el estudio", **leer el informe previo que él pegue** y partir de ahí, NO re-hacerlo.
2. **UN solo formato de entrega: Markdown (`.md`).** NO generar PDF salvo petición explícita (los enlaces no funcionan en PDF → inútil para candidatos con URL). NO múltiples copias del mismo documento.
3. **Completar/ampliar una variante = ACTUALIZAR el informe existente, NUNCA crear otro informe.** Si el usuario pide "completa el Golf R DE", se actualiza la sección 6 del `estudio_golf75_*.md` (o se entrega el bloque actualizado para fusionar), no un `informe_modelo_golf-r_*.md` nuevo.
4. **Un informe MODELO (Flujo B) SOLO cuando el usuario pide explícitamente "busca unidades del X"** — no cuando pide completar cobertura de mercado. Distinguir: *estudio de mercado* (suelos/hueco) vs *informe modelo* (candidatos 7 fuentes).
5. **En la nube sin acceso al JSON:** entregar el informe `.md` y al final imprimir una sola línea clara para el usuario:
   *"Archivo: `informes/mercado/<archivo>.md`. Cuando quieras volcarlo al mapa, pásale ese MD a Copilot en VS Code y dile 'importa este MD al mapa de mercado'."*
   (NO intentar escribir la ruta de Windows. NO usar jerga de "sincronización" / "merge" / "volcado". Palabras de persona.)
6. **Desglose por variables OBLIGATORIO.** Puertas (3p/5p), cambio (manual/DSG), techo solar, cuadro digital — siempre que haya muestra suficiente. Es donde se ve el valor real del coche y casi siempre se salta. Si no hay muestra, se dice en 1 línea y se omite la tabla.
7. **Resumen para copiar al final:** el usuario debe poder copiar 1 párrafo con los datos clave (suelos, hueco, veredicto, advertencia principal) sin re-leer el informe. Sin enlaces (no funcionan pegados en WhatsApp/notas).
8. **Comparables con modelos ya estudiados.** "El Astra OPC de julio tenía X hueco, este Golf R tiene Y → mejor/peor relación". Evita que el usuario tenga que abrir 3 informes para poner el dato en contexto.
9. **Puesto en Huelva = precio Alemania + 1.500 € de gastos fijos estimados** (1.000 € transporte + 200 € ITV + 300 € gestoría/ausfuhr). Es la cifra que el usuario ve para decidir. IVA de importación + IEDMT NO se incluyen — se mencionan en la sección "💶 Desglose de los 1.500 €" para que sepa qué falta. El cálculo técnico del `hueco_neto_pct` (con IEDMT exacto) se mantiene en el mapa `datos_mercado.json` para uso interno, pero el informe va con la cifra redonda. Si el usuario da otra cifra ("mis gastos son 2.000 €"), recalcular todas las tablas.
