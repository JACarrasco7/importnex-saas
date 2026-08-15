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

## 🚦 Reglas duras del Prompt Improver

1. **NUNCA** mejorar un prompt que ya está completo (desperdicia tokens).
2. **NUNCA** preguntar más de 4 cosas a la vez (abrumas al usuario).
3. **SIEMPRE** preguntar primero los críticos (modelo, presupuesto, finalidad).
4. **SIEMPRE** permitir "busca tú" / "lo que puedas" (no bloquear).
5. **SIEMPRE** mostrar el prompt mejorado listo para que el usuario diga "OK" o lo modifique.
6. **SIEMPRE** guardar el briefing confirmado en `memoria/modelos-medidos.md` al cerrar.

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