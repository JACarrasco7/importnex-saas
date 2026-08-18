# Guía para mejorar prompts del usuario (Prompt Improver)

> **Cargar cuando:** el usuario escribe un prompt vago o incompleto y Claude va a actuar directamente sin refinarlo.
> **Objetivo:** antes de ejecutar nada, refinar el prompt para ahorrar tokens + evitar búsquedas incompletas.

---

## 🎯 ¿Cuándo mejorar el prompt?

**SÍ mejorar cuando:**
- Prompt tiene menos de 3 parámetros del briefing
- Solo palabras sueltas: "busca GTI", "qué GTI me recomiendas", "evalúa este"
- Tono impreciso: "uno bueno", "barato", "para cliente"
- No hay URL cuando parece Flujo A

**NO mejorar cuando:**
- El prompt ya tiene briefing completo (año, km, presupuesto, etc.)
- El usuario es explícito: "no preguntes, busca lo que puedas"
- Es una pregunta informativa: "¿cuánto cuesta traer un X?" (no requiere navegación)

---

## 🔄 Flujo del Prompt Improver

```
1. DETECTAR que el prompt es vago
   ↓
2. IDENTIFICAR qué falta (tabla de faltantes)
   ↓
3. PROPONER prompt mejorado con briefing completo
   ↓
4. PEDIR confirmación al usuario (1 mensaje corto)
   ↓
5. EJECUTAR con el prompt confirmado
```

---

## 📐 Reglas de transformación

### Regla 1: Detectar lo que YA está
Extraer del prompt original los parámetros dados. NO repetirlos en el briefing mejorado.

**Ejemplo:**
```
Original: "busca un GTI barato para un cliente"
Extraído: ✅ modelo=Golf GTI  ✅ finalidad=cliente
Falta: presupuesto, año, km, equipamiento, combustible, cambio
```

### Regla 2: Inferir lo razonable
Cuando falten parámetros que se pueden asumir por defecto razonable:
- **Año:** si no dice → "recientes" (sin fijar año mínimo, dejar que el mercado mande)
- **Km:** si no dice → "menos de 100.000 km"
- **Presupuesto:** si no dice → preguntar (no inventar)
- **Cambio:** si no dice → "indiferente (manual o automático)"
- **Tope de gama:** si la versión lo es (GTI/R/M...) → preguntar potencia

### Regla 3: Preguntar SOLO lo crítico
Mostrar **una sola vez** los parámetros críticos que faltan (1-3 preguntas), no todos.

**Críticos que SIEMPRE hay que preguntar si faltan:**
- Modelo + versión (sin él no se puede buscar)
- Presupuesto (cambia la franja de modelos posibles)
- Finalidad (personal vs reventa cambia el output)

**No críticos que se pueden asumir:**
- Color, plazas, equipamiento opcional, plazo, etc.

### Regla 4: Mensaje corto y limpio
El mensaje de mejora debe ser:
- 1 tabla compacta de faltantes
- 1 prompt mejorado listo para enviar
- 1 pregunta de confirmación

NO rellenar con explicaciones largas.

---

## 💬 Plantillas de mejora

### Plantilla A: Prompt vago con intención clara
```
Usuario: "busca un GTI"
        ↓
Claude responde:

Tu prompt es vago. Voy a necesitar:

| Falta | ¿Qué necesitas? |
|---|---|
| Versión | ¿GTI / GTI Performance / GTI Clubsport? |
| Presupuesto | ¿Máximo puesto en Huelva? |
| Finalidad | ¿Para ti o para revender? |
| Año mín + km máx | ¿A partir de qué año y km? |

💡 Si me das esos 4 datos, hago la búsqueda completa (Fase 1+2, ~50 capturas).

Si quieres, propónme tú el briefing que usarías. O dime "busca lo que puedas" y te doy resultados genéricos.
```

### Plantilla B: Prompt con casi todo, falta 1 cosa
```
Usuario: "busca GTI 2020+ para mí, presupuesto 30k"
Extraído: ✅ modelo, ✅ año mín, ✅ presupuesto, ✅ finalidad
Falta: potencia (GTI vs Performance) + km máx
        ↓
Claude responde:

Casi lo tengo. Solo me falta:

| Falta | ¿Cuál? |
|---|---|
| Versión | ¿GTI o GTI Performance/Clubsport? (cambia la potencia) |
| Km máx | ¿Tope de kilómetros? |

Una vez confirmado, ejecuto.
```

### Plantilla C: Flujo A con URL pero sin contexto
```
Usuario: "evalúa este https://www.mobile.de/..."
Extraído: ✅ URL
Falta: cliente, presupuesto, finalidad, equipamiento imprescindible
        ↓
Claude responde:

Tengo la URL. Para el briefing completo:

| Falta | ¿Cuál? |
|---|---|
| Para quién | ¿Para cliente / para ti / análisis técnico? |
| Presupuesto | ¿Máximo total puesto en Huelva? |
| Equipamiento imprescindible | ¿Techo, AWD, automático... |
| Finalidad | ¿Encargo / reventa / flota? |

Si dices "para cliente, 25k, automático, reventa", ejecuto con briefing completo.
```

### Plantilla D: Prompt sobre mercado
```
Usuario: "qué GTI me recomiendas"
        ↓
Claude responde:

Para darte recomendación con criterio, necesito:

| Falta | ¿Cuál? |
|---|---|
| Presupuesto | ¿Rango en € puesto en Huelva? |
| Finalidad | ¿Para ti (pasional) o reventa (margen)? |
| Prioridad | ¿Pasión / economía / fiabilidad? |
| Edad/km | ¿Algún tope? |

Si no quieres definir, te doy un escaneo genérico del mercado actual (~100 capturas, 5-7 modelos).
```

---

## 🎯 Tabla de "Faltantes detectados"

Antes de preguntar, Claude debe mostrar **una tabla compacta** así:

```
ENTENDIDO:
✅ <lo que ya diste>
✅ <lo que ya diste>

❓ Falta:
  • <crítico 1>
  • <crítico 2>

(Si me los das, ejecuto con briefing completo en X capturas)
```

Si el usuario tiene prisa y responde "no preguntes, busca tú", Claude asume defaults razonables y lo indica.

---

## � ENTENDER antes de MEJORAR (17-ago-2026) — FASE 0 obligatoria

> **Diferencia clave:** MEJORAR (secciones de abajo) completa parámetros y estructura el prompt. ENTENDER es previo y distinto: **asegurarse de que has captado QUÉ quiere el usuario antes de ejecutar nada.** Si no entiendes la petición, no la mejores: ACLÁRALA.
> Fallo real 17-ago: el prompt "stock recurrente de publicaciones" se interpretó como marketing (anuncios IG) cuando el usuario quería BÚSQUEDA de catálogo. Faltó confirmar la intención ANTES de todo.

### Método ENTENDER (3 sub-pasos, ~1 mensaje)
1. **PARAFRASEAR** en 1 línea: "He entendido: quieres X, para Y, entregable Z". Si el usuario confirma, listo.
2. **PREGUNTAR LO PRECISO**: solo si hay duda real de intención (qué entregable, para qué, alcance). Máx 2-3 preguntas. Nada de "¿continúo?" ni preguntas por inercia.
3. **CONFIRMAR EXPECTATIVA de formato** si el usuario lo menciona (docx, PDF, anuncios, tabla...): la skill entrega Markdown+JSON+PDF; si el usuario espera otra cosa, se aclara ahí.

### Preguntas precisas de comprensión (ejemplos)
| Señal de que NO entiendes | Pregunta precisa |
|---|---|
| Dijo "publicaciones" / "anuncios" / "posts" | "¿Quieres que BUSQUE coches (catálogo) o que genere los anuncios para publicar?" |
| Dijo "algo rentable" / "lo que sea" | "¿Para reventa con margen, para un cliente concreto, o para llamar la atención (marketing)?" |
| Pide un formato que no es el estándar (.docx, tabla...) | "La skill entrega Markdown+PDF+JSON. ¿Te vale o necesitas otro formato?" |
| Mezcla varias peticiones | "¿Lo hacemos en fases? Primero X, luego Y, con tu OK entre medias." |

> **Regla de oro:** si puedes explicar en 1 línea qué harás y el usuario la aprueba, YA ENTENDISTE. Solo entonces pasa a MEJORAR (parámetros), cache y plan de fase.
>
> **Después de ENTENDER → FIJAR MODELOS antes de buscar** (ver `planificador.md` PASO 3b): se acuerda la lista de modelos candidatos con su encaje ES/DE (dónde sale mejor comprar cada uno) y se espera tu OK antes de valorar ninguna unidad. No es solo buscar coches: es decidir QUÉ modelos tienen sentido primero.

### 📥 ACK (acuse de recibo) — SIEMPRE, en TODO encargo, 1 línea

> **Incluso sin ambigüedad, Claude abre el encargo con un ACK de 1 línea** para que el usuario corrija en 1 palabra si hay desvío. No es un "¿continúo?": es **confirmar la comprensión antes de gastar tokens**.

```
📥 ENTENDIDO — [QUÉ] · [PARA QUÉ] · [ENTREGABLE] · [FLUJO]
Si no es esto, dime en 1 palabra qué cambio y arranco.
```

**Ejemplos (siempre con el verbo correcto):**
- 📥 ENTENDIDO — BUSCAR coches (catálogo) · para montar stock · informe de búsqueda Markdown+PDF+JSON · Flujo E.
- 📥 ENTENDIDO — EVALUAR esta URL · para un cliente · informe unidad + dossier + ZIP · Flujo A.
- 📥 ENTENDIDO — BUSCAR Golf GTI · para reventa · informe modelo + top 5 · Flujo B.
- 📥 ENTENDIDO — CALCULAR cuánto cuesta importar · para decidir · desglose de costes · sin navegar.

**Reglas del ACK:**
1. El ACK se pone ANTES de PASO 0 cache y ANTES del plan de fase. Es lo primero que lee el usuario.
2. Si el usuario responde "sí"/"OK" → seguir. Si corrige algo del ACK → corregir SOLO eso y seguir (no re-hacer el ACK completo).
3. El verbo del ACK viene de la tabla intención→flujo: **BUSCAR / EVALUAR / PUBLICAR / CALCULAR / ASESORAR**. Usar el verbo correcto ya descarta la mitad de confusiones.

### 🌳 Árbol de decisión de comprensión (preguntar SOLO si falta)

```
¿Sé QUÉ quiere (verbo)?        NO → "¿Qué quieres: buscar coches, evaluar una URL, publicar anuncios o calcular coste?"
   └ SÍ ↓
¿Sé PARA QUÉ (finalidad)?       NO → "¿Para reventa, para un cliente o para ti?"
   └ SÍ ↓
¿Sé el ENTREGABLE (formato)?    NO → "¿Qué entregable esperas: informe, dossier, anuncios o ZIP?"
   └ SÍ ↓
¿Sé el ALCANCE (cuánto/cuántos)? NO → "¿Un coche, un modelo o un escaneo de mercado?"
   └ SÍ ↓
📥 ACK de 1 línea → cache → plan de fase
```

> **Anti-inercia:** si los 4 datos están claros, NO se hace ninguna pregunta — solo el ACK. Preguntar por preguntar es el fallo contrario (desperdicia tokens y cabrea al usuario).

## �🧭 Dimensión de INTENCIÓN y ENTREGABLE (17-ago-2026) — se aplica SIEMPRE

> El Prompt Improver clásico (arriba) completa PARÁMETROS que faltan (año, km, presupuesto). Esta dimensión aclara la **INTENCIÓN y el ENTREGABLE que espera el usuario**. Se ejecuta en TODO encargo, aunque los parámetros estén completos.

### Los 3 datos de intención a confirmar (si hay ambigüedad)
1. **QUÉ quiere el usuario**: buscar coches / evaluar / publicar / calcular coste / asesorar.
2. **QUÉ entregable espera**: informe de búsqueda · dossier cliente · anuncios/copy RRSS · ficha marketplace · PDF · JSON/ZIP Laravel.
3. **PARA QUÉ**: tráfico/leads (marketing) · reventa (margen) · cliente final (encargo) · uso personal.

### Tabla de intención → flujo → entregable (contrastar SIEMPRE)

| El usuario dice… | Flujo | Entregable correcto |
|---|---|---|
| "evalúa esta URL" | A (UNIDAD) | Informe unidad + dossier + ZIP |
| "busca [modelo]" | B (MODELO) | Informe modelo + top 5 |
| "qué merece la pena / escanea mercado" | C (MERCADO) | Informe búsqueda |
| "cliente sin modelo + presupuesto" | D (DESCUBRIMIENTO) | Informe de modelos por país |
| "stock recurrente / catálogo bajo pedido / busca por categorías" | E (STOCK) | Informe de búsqueda (Markdown+PDF+JSON) — SIN copy RRSS |

### Regla de oro de intención
**Ante ambigüedad entre BÚSQUEDA y MARKETING (o cualquier par de entregables), PREGUNTAR antes de ejecutar — 1 pregunta, en la misma línea que el plan de fase.** Nunca mezclar ambos en un solo entregable. Si el usuario pide ambos, separar en 2 fases con checkpoint entre ellas.

### ⚡ Colisiones de intención (pares que se mezclan — separar SIEMPRE)

| Colisión típica | Detección | Separación correcta |
|---|---|---|
| **BÚSQUEDA vs MARKETING** | "stock/publicaciones/para RRSS" + "busca coches" | Fase 1: informe de búsqueda → checkpoint → Fase 2: copy RRSS solo si lo pide |
| **EVALUAR vs BUSCAR** | URL pegada + "también mira otros" | Primero Flujo A (URL) → luego Flujo B/C para alternativas |
| **UN modelo vs VARIOS** | "busca X e Y" / "compáralos" | Comparativa primero → informes individuales del elegido |
| **IMPORTAR vs COMPRAR NACIONAL** | no especifica origen | Preguntar origen (DE vs ES) o buscar en ambos y comparar |
| **INFORME vs PUBLICAR** | "dime qué hay" + "haz el anuncio" | Informe primero, publicar después (nunca en el mismo entregable) |

> **Regla:** si en un mismo mensaje conviven DOS verbos de la tabla intención→flujo, es una colisión → dividir en fases con checkpoint. No intentar resolver las dos a la vez.

---

## 🚦 Reglas duras del Prompt Improver

1. **NUNCA** mejorar un prompt que ya está completo (desperdicia tokens).
2. **NUNCA** preguntar más de 4 cosas a la vez (abrumas al usuario).
3. **SIEMPRE** preguntar primero los críticos (modelo, presupuesto, finalidad).
4. **SIEMPRE** permitir "busca tú" / "lo que puedas" (no bloquear).
5. **SIEMPRE** mostrar el prompt mejorado listo para que el usuario diga "OK" o lo modifique.
6. **SIEMPRE** guardar el briefing confirmado en `../memoria/modelos-medidos.md` al cerrar.

---

## 📊 Estimación de ahorro

| Tipo de prompt | Con mejora | Sin mejora |
|---|---|---|
| Vago ("busca GTI") | ~5 tokens preguntar + 50 tokens ejecutar = 55 | 50-200 tokens (rebusca) |
| Medio ("GTI 2020+ para mí") | ~3 tokens preguntar + 50 ejecutar = 53 | 50-100 tokens (puede fallar) |
| Completo (briefing total) | 0 tokens preguntar + 50 ejecutar = 50 | 50 tokens ejecutar |

**El prompt completo SIEMPRE es más barato + mejor resultado.**

---

## 🔍 Detección automática de prompt vago

```
SI mensaje_usuario.length < 50 chars → PROMPT VAGO
   - Menos de 4 parámetros
   - Falta URL en Flujo A
   - Solo palabras sueltas

SI longitud > 200 chars → PROMPT COMPLEJO (probablemente completo)
   - Parsear briefing
   - Solo preguntar si falta crítico

SI 50-200 chars → REVISAR manualmente
   - ¿Tiene 3+ parámetros del briefing?
   - Si no → mejorar
```

---

## 📚 Ejemplos de aplicación rápida

| Usuario dice | Claude hace |
|---|---|
| "busca BMW M3" | Pregunta: versión + presupuesto + km + combustible |
| "GTI 2020 30k" | Asume poco, pregunta: versión (potencia) + cambio |
| "evalúa https://..." | Pregunta: cliente + presupuesto + equipamiento |
| "qué merece la pena" | Pregunta: presupuesto + finalidad + prioridades |
| "Opel Astra OPC ≥2012 ≤130k" | Pregunta: presupuesto + vendedor + URL candidato |
| "Opel Astra OPC 2012+ 130k máx 15k presupuesto manual particular ya tengo https://...id=38347146649056" | EJECUTA directo (briefing completo) |

---

## 💾 Trazabilidad

Cuando Claude mejora un prompt, lo anota en la memoria de sesión:

```
🛠️ Prompt mejorado (12-ago 18:45):
   Original: "busca GTI"
   Mejorado: "busca VW Golf GTI 7.5 Performance 2020+, presupuesto 30k..."
   Ejecutado: 52 capturas
   Resultado: top 5 candidatos
```

Esto ayuda a iterar sobre el Prompt Improver mismo.
