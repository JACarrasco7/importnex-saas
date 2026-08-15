# Briefing de encargo — Preguntas previas OBLIGATORIAS

> **Cargar cuando:** el usuario describe un encargo (Flujo B: "busca un X para un cliente") o un cliente (Flujo A con requisitos).
> **Objetivo:** capturar TODOS los parámetros del encargo ANTES de gastar un solo token en navegación. Evita búsquedas incompletas y repeticiones.
>
> **Regla de oro:** si falta un parámetro que afecta al resultado, PREGUNTAR. No buscar "a ciegas" y descubrir después que el rango era otro.

---

## 🎯 Parámetros mínimos (checklist de arranque)

Antes de abrir cualquier portal, confirmar con el usuario (si no vienen dados):

| # | Parámetro | Ejemplo | ¿Crítico? |
|---|---|---|---|
| 1 | **Modelo + versión** | Opel Astra J OPC | ✅ SIEMPRE · **si NO hay modelo pero sí presupuesto+requisitos → FLUJO D** (sondeo de modelos), no se pregunta el modelo |

**📓 Arranque de sesión (15-ago-2026):** crear el cuaderno `informes\_sesion\sesion_<fecha>_<encargo>.md` con los parámetros confirmados ANTES de la primera búsqueda. Toda corrección del usuario entra en el cuaderno y se aplica YA (ver SKILL.md §CUADERNO DE SESIÓN).
| 2 | **Año mínimo (EZ)** | ≥2012 | ✅ SIEMPRE |
| 3 | **Km máximo** | ≤130.000 | ✅ SIEMPRE |
| 4 | **Presupuesto tope** | ≤15.000 € (o "sin límite") | ✅ SIEMPRE · 🎯 confirmar **modalidad de honorarios M1/M2/M3** (ver abajo) — no asumir |
| 5 | **Potencia (kW/CV)** | 280 CV / 206 kW | 🔶 Si tope de gama |
| 6 | **Combustible** | Gasolina | 🔶 |
| 7 | **Cambio** | Manual / DSG | 🔶 |
| 8 | **Finalidad** | Encargo personal / Revender | 🔶 Cambia el output |
| 9 | **Equipamiento imprescindible** | Techo, cámara, 4Motion | 🔶 |
| 10 | **Color / plazas / puertas** | Negro, 5 puertas | 🟡 Opcional |
| 11 | **Plazo** | Flexibles / urgente | 🟡 Opcional |
| 12 | **¿Ya viste algún candidato?** | URL del 8.999 € | 🟡 Evita rebuscar |
| 13 | **Origen** | DE / ES / el mejor | 🔶 Si no dice → buscar ambos y comparar |

### 💶 Modalidades de honorarios — preguntar SIEMPRE (15-ago-2026)

> **3 fallos reales con lo mismo:** 12-ago (9.000 €, hubo que corregir el techo) · 15-ago Tiguan (tarifa reducida ES asumida mal) · 15-ago María ("quita el coste del servicio" leído como "descuenta" cuando era "no se cobra"). Ya NO se asume: se pregunta o se confirma la interpretación.

| Modo | Qué significa | Techo de búsqueda |
|---|---|---|
| **M1 · Incluidos** | El presupuesto paga coche + logística + honorarios | presupuesto − costes − honorarios |
| **M2 · Aparte** | Honorarios se cobran fuera del presupuesto | presupuesto − costes (SIN restar honorarios) |
| **M3 · No se cobran** | Cliente especial / cortesía / familiar | presupuesto − costes (honorarios = 0 €) |

**Regla de ambigüedad:** frases tipo "quita el coste del servicio", "todo incluido", "sin honorarios" se REFORMULAN en 1 línea antes de ejecutar:
> "Entiendo: no se cobran honorarios a este cliente (M3) → techo completo 9.000 €. Correcto?"

---

## 🧠 Método: preguntar de una vez, no de a poco

### Paso 1 — Reconocer lo que ya sabe
Del mensaje del usuario extraer los parámetros dados (ej: "OPC 2012, 130k máx" → modelo, año mín, km máx).

### Paso 2 — Listar SOLO lo que falta
No repetir lo que ya dio. Mostrar una tabla compacta:

```
ENTIENDO EL ENCARGO:
✅ Modelo: Opel Astra J OPC
✅ Año mín: 2012
✅ Km máx: 130.000
❓ Falta confirmar:
  • Presupuesto tope: ¿cuánto máximo?
  • Potencia exacta: ¿206 kW (280 CV)? (para no perder OPC mal etiquetados)
  • Finalidad: ¿encargo personal o para revender?
```

### Paso 3 — Esperar respuesta antes de navegar
**NO abrir ningún portal hasta que el usuario confirme los críticos (1-4).** Con eso basta para Fase 1.

**⚠️ EXCEPCIÓN (12-ago-2026) — Modo automático:**
> Si TODOS los críticos ya vienen dados en el mensaje (modelo+versión, año mín, km máx, presupuesto, potencia si tope de gama, combustible, cambio, finalidad), **NO esperar respuesta para el briefing**. Confirmar en 1 línea ("Entendido, encargo completo") y ejecutar Fase 1 automática → entregar INFORME MODELO + top 5 → **esperar a que el usuario elija candidato**. Tras elegir: todo automático (fotos + informe UNIDAD + dossier + ZIP; si son varios → comparativa antes). Ver `SKILL.md` §MODO AUTOMÁTICO.

---

## ⚡ Plantillas de pregunta por situación

### Encargo INCOMPLETO (Flujo B)
> ⚠️ Solo si faltan críticos. Si el encargo llega COMPLETO, NO se usa esta plantilla — ver §MODO AUTOMÁTICO.
```
Para afinar la búsqueda del [modelo], ¿me confirmas?
  1. Presupuesto máximo: ______ € (o "sin límite")
  2. Potencia: ___ CV / ___ kW (¿o cualquier versión?)
  3. Equipamiento imprescindible: ______
  4. Finalidad: ¿encargo personal o reventa?
  5. ¿Plazo de entrega?
```

### Cliente con requisitos vagos (Flujo A/B)
```
¿Qué es imprescindible para el cliente?
  • Año mínimo / km máximo
  • Combustible / cambio
  • Presupuesto
  • Algo que NO quiera (marca, motor)
```

### Refinamiento tras resultado
```
Con el resultado actual: ¿filtro más (año, km, precio) o te muestro
los X candidatos que encajan tal cual?
```

---

## 🔍 Detección de TOPE DE GAMA (activa doble pasada)

Si el modelo/versión buscado es un **tope de gama**, preguntar/confirmar la potencia para la doble pasada:

**¿Es tope de gama?** Palabras clave en la versión: `OPC`, `GTI`, `GTD`, `R`, `M`, `AMG`, `RS`, `Type R`, `N`, `GTE`, `ST`, `XRi`, `TS`, `Performance`, `Clubsport`, `+`.

Si SÍ → la búsqueda usa DOBLE PASADA (ver `playbook_filtrado.md` §Doble pasada):
```
Búsqueda 1: variante de texto
Búsqueda 2: modelo base + filtro kW (potencia_tope ±10)
Cruce: unión de IDs
```

> ⚠️ Preguntar la potencia EXACTA si el usuario no la da. No adivinar el rango kW sin saber los CV del tope de gama. Si el usuario no sabe, buscarla (km77/BOE) ANTES de la búsqueda 2.

---

## 💾 Guardar el encargo en memoria

Tras confirmar, apuntar el encargo en `memoria/modelos-medidos.md` (histórico):
```markdown
### [Marca Modelo] · encargo [fecha]
- Año mín: X · Km máx: X · Presupuesto: X €
- Potencia: X kW · Combustible: X
- Finalidad: encargo personal / reventa
- Resultado: [candidato elegido, precio]
```

Esto permite: reutilizar el encargo, ver resultados anteriores, y no repetir búsquedas.

---

## 🆚 Antes vs Después

| Aspecto | Antes | Después |
|---|---|---|
| Búsqueda | Empezaba con modelo + 1-2 filtros | Empieza con encargo completo (4+ parámetros) |
| Repetición | Podía buscar 2 veces lo mismo | Encargo en memoria → no se repite |
| Topes de gama | Se perdía OPC mal etiquetados | Doble pasada automática con kW |
| Tokens | Gasto ciego hasta descubrir filtro | Pregunta 10s ANTES de navegar |
| Resultado | Candidatos que podían no encajar | Candidatos que cumplen TODOS los requisitos |

---

## 📋 Reglas duras del briefing

1. **NUNCA** navegar sin confirmar los críticos (modelo, año mín, km máx, presupuesto) — **salvo que ya vengan dados** (entonces modo automático, ver `SKILL.md` §MODO AUTOMÁTICO).
2. **SIEMPRE** confirmar potencia si es tope de gama (activa doble pasada).
3. **SIEMPRE** guardar el encargo en memoria al cerrarlo.
4. **NUNCA** repetir una búsqueda que ya está en memoria (leer primero).
5. **Preguntar de una vez** (tabla de faltantes), no de a poco.

---

## 💬 Ejemplos de prompts por flujo (12-ago-2026)

### 🔵 FLUJO A — UNIDAD (tienes URL de un anuncio)

**Prompt corto y directo** (recomendado):
```
evalúa este: https://www.mobile.de/fahrzeuge/details.html?id=455589559
```

**Prompt con cliente** (añade contexto):
```
evalúa este Opel para un cliente que busca GTI/OPC, presupuesto 20k:
https://www.mobile.de/fahrzeuge/details.html?id=455589559
```

**Prompt con briefing previo** (si quieres que Claude pregunte lo que falte):
```
tengo un candidato, evalúalo:
https://www.coches.net/segunda-mano/coches/volkswagen-golf-gti-clasico-2020-idABC123

cliente: busca GTI manual, techo, etiqueta C, máx 30k presupuesto total
```

**Claude automáticamente:**
1. Detecta flujo A
2. Lee briefing de encargo → confirma si falta info crítica
3. Valida 7 fuentes (modo degradado si falla alguna)
4. Genera informe interno (15 sec) + dossier cliente (15 sec)
5. Empaqueta ZIP para Laravel

---

### 🟢 FLUJO B — MODELO (buscas un modelo)

**Prompt básico** (sin filtros):
```
busca golf gti
```

**Prompt con briefing completo** (RECOMENDADO):
```
tengo un encargo personal de un Opel Astra J OPC
- matriculación: a partir de 2012
- km máximo: 130.000
- presupuesto: sin límite, pero quiero buen precio
- manual o automático (indiferente)
- gasolina
```

**Prompt con presupuesto y finalidad**:
```
busca VW Golf GTI 7.5 Performance 2020+ para revender
- presupuesto: hasta 35k puesto en Huelva
- km máximo: 60.000
- automático DSG obligatorio
```

**Prompt con URL de candidato + búsqueda adicional** (CASO REAL 12-ago):
```
busca Opel Astra J OPC sin stock, matriculación ≥2012, ≤130.000 km
este es un chollo que ya vi por si lo encuentras también:
https://m.mobile.de/fahrzeuge/details.html?id=38347146649056
```

**Claude automáticamente:**
1. Detecta flujo B
2. **Pregunta lo que falte** (solo si falta algo; si el encargo está completo → NO pregunta, modo automático)
3. **Si es tope de gama** (OPC) → confirma potencia → activa doble pasada
4. Fase 1: mobile.de + Coches.net + AutoUncle (15-20 capturas) — si el origen NO está especificado, buscar en **AMBOS mercados** (DE y ES) y comparar dónde sale mejor
5. **Si hueco claro, omite AutoUncle** (ahorro)
6. Cruce de búsqueda 1 (variante) + búsqueda 2 (kW) → no pierde chollos
7. 📋 INFORME MODELO + top 5 con enlaces (indicando origen DE/ES de cada candidato) → ENTREGAR
8. ⏸️ Esperar a que el usuario elija candidato → desde ahí automático (fotos + informe UNIDAD + dossier + ZIP; comparativa antes si varios)

---

### 🟡 FLUJO C — MERCADO (escanear oportunidades)

**Prompt amplio** (recomendado):
```
qué oportunidades hay ahora mismo para importar a España:
- presupuesto: 25-40k € puesto en Huelva
- tipo: deportivos/premium (GTI, R, M, AMG, RS, OPC)
- año: 2018+
- km máximo: 80.000
- gasolina o híbrido enchufable
```

**Prompt con preferencias pasionales**:
```
escanea el mercado: quiero deportivos pasionales para mí,
presupuesto 30-50k, prioridad pasional (no económico)
```

**Prompt escaneo de nicho**:
```
qué modelos nicho (alto margen, baja oferta) hay ahora mismo
que justifiquen un encargo de cliente con presupuesto 25-40k?
```

**Claude automáticamente:**
1. Detecta flujo C
2. **Pregunta preferencias** (si no las diste)
3. Fase 1 por cada modelo (12-18 capturas/modelo)
4. Tabla agrega: modelo | hueco% | N DE | vendibilidad | semáforo
5. Avisar al 50% del budget (50 capturas) si la lista es larga

---

## 🎯 Plantilla universal de prompt

```
[QUÉ BUSCO]
  Modelo: <marca> <modelo> [versión]
  Año mín: <YYYY> | Km máx: <K>
  Presupuesto: <€> (puesto en Huelva o absoluto)
  Combustible: <gasolina/diesel/híbrido>
  Cambio: <manual/automático/indiferente>

[CONTEXTO]
  Finalidad: <encargo personal / revender / flota>
  Plazo: <flexible / antes de fecha>
  Equipamiento imprescindible: <techo, AWD, etc.>
  Color / puertas: <opcional>

[OPCIONAL]
  Ya he visto: <URL de candidato que te interesa>
  NO quiero: <marca, motor, color>
```

---

## ⚡ Tips cortos

| Quiero... | Escribe... |
|---|---|
| Evaluar 1 coche | `evalúa este: <URL>` |
| Buscar modelo con briefing | `encargo personal X, año Y, km Z` |
| Ver mercado completo | `qué merece la pena, presupuesto X-Yk, <resto>` |
| Refinar una búsqueda | `filtra más por <filtro>` o `amplía a <rango>` |
| Precio de un modelo concreto | `cuánto cuesta traer un X desde Alemania?` |
| Solo ES | `en España, donde encontrar X?` |
| Ver histórico | `qué tengo medido de X?` (lee modelos-medidos.md) |
