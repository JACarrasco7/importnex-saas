# Planificador — asistente de planificación, plan de barrido y prompt improver

> **Módulo de planificación del skill.** Se carga ANTES de ejecutar cualquier búsqueda en encargos abiertos/vagos. Complementa `briefing_encargo.md` (qué preguntar) y `guia_prompts.md` (plantillas de prompt).
>
> **Flujo de arranque completo** (ver `../SKILL.md` §ARRANQUE): detección de flujo → **PASO 0 cache** → briefing → **PLAN DE FASE** (este archivo).

---

## 💡 ASISTENTE DE PLANIFICACIÓN — encargos abiertos/vagos (16-ago-2026)

> **El problema:** El usuario dice "busca algo para 15.000 €" o "qué merece la pena" y la IA lanza 50+ peticiones sin dirección clara. Se queman tokens y el resultado no es útil.
> **La solución:** La IA ayuda al usuario a construir un PLAN paso a paso con estrategia de embudo, filtrando progresivamente desde muy abierto a muy específico.

### Protocolo en 4 pasos

```
PASO 1 — DETECTAR el tipo de encargo (flujo automático) → `../SKILL.md` §Detección
  └─ ¿URL concreta? → Flujo A (evaluación unitaria, sin ayuda de planificación)
  └─ ¿Modelo concreto? → Paso 2 (mejorar prompt si falta críticos)
  └─ ¿Solo presupuesto + requisitos? → Flujo D (sondeo de modelos primero)
  └─ ¿"Qué merece la pena"? → Flujo C + Paso 2 (definir segmentos juntos)

PASO 0 — CHECK DE CACHE (siempre, antes de planificar)
  └─ ¿El modelo/cliente ya tiene encargo o medición previa?
  └─ Leer `../memoria/encargos.md` + `../memoria/modelos-medidos.md` + `indice.json` (Desktop)
  └─ Si <3 semanas → mostrar resumen + preguntar ¿delta / refrescar / nuevo? (NO re-buscar)

PASO 2 — MEJORAR el prompt (Prompt Improver)
  └─ Detectar vaguedad: <50 chars o <3 parámetros
  └─ Proponer prompt mejorado con completar críticos
  └─ Preguntar SOLO lo que falta (máx 4 cosas)
  └─ Opción "busca tú / lo que puedas"

PASO 3 — PLANIFICAR la estrategia de embudo (Plan de fase)
  └─ Mostrar el PLAN DE BARRIDO (mercados, filtros, bandas, fuentes, asesor de filtros)
  └─ Explicar el enfoque: del MÁS abierto al MÁS específico
  └─ Pedir OK ANTES de ejecutar la fase (Protocolo de Mando, ver `../SKILL.md`)

PASO 4 — EJECUTAR la fase en cascada con checkpoints
  └─ Fase 1 → Informe + lista de candidatos + checkpoint
  └─ Usuario elige → Fase 2 → Informe unidad + checkpoint
  └─ Veredicto → Dossier + ZIP
```

### Ejemplo práctico paso a paso

**Usuario dice:** "busca algo para 15.000 €"

**Claude responde (PASO 1 + 2):**
```
Tengo el presupuesto pero me falta definir QUÉ tipo de coche. ¿Me ayudas?

1. Finalidad: ¿para reventa / uso personal / familiar?
2. Segmento: ¿compacto / SUV / berlina / deportivo?
3. Origen: ¿importación de Alemania o compra nacional?
4. Año mínimo / KM máximo?

O si prefieres, dime "busca tú" y propongo yo lo más rentable del mercado.
```

**Usuario responde:** "uso personal, compacto, Alemania, 2018+, ≤100k"

**Claude responde (PASO 3):**
```
📋 PLAN DE FASE — Compactos 2018+ ≤100k desde 15.000 €

1. Mercados:
   - 🇩🇪 Alemania: techo ≈ 11.250 € (transporte 900 + ausfuhr 114 + ITV 115 + honorarios 1.500)
   - 🇪🇸 España: techo ≈ 14.650 € (honorarios 1.500)

2. Filtros: ≥2018 · ≤100.000 km · Gasolina/Diesel · 5p · ≤11.250 € (DE)
   Filtros por URL: minyear/maxkm/minpower (Coches.net) · resto por clic (mobile.de)
   → ver `../memoria/filtros-portales.md`

3. Bandas de precio a recorrer:
   - 5.000-7.000 € (suelo, muchas unidades)
   - 7.000-9.000 € (dulce, equilibrio km/precio)
   - 9.000-11.250 € (techo, mejores acabados)

4. Fuentes Fase 1 (sondeo rápido):
   - mobile.de (3-5 páginas)
   - Coches.net (2-3 páginas)
   - AutoUncle (1 captura)

5. Entregable:
   - Informe de MODELOS (5-8 modelos que caben con precio-desde)
   - Tú eliges 2-3 → investigo a fondo (candidatos reales)

Tokens estimados: ~12 peticiones (~4.000 tokens). ¿OK?
```

**Usuario:** "OK"

**Claude ejecuta (PASO 4) — Flujo D:**
```
D1: Sondeo ES+DE (8 peticiones) → Encontré 7 modelos que caben
D2: INFORME DE MODELOS con tabla por país×año×motor
CP-D: Elige 2-3 modelos → cada uno pasa a Flujo B (candidatos con enlaces)
```

### ¿Cuándo aplicar cada herramienta?

| Herramienta | Cuándo usar | Token ahorro |
|---|---|---|
| **PASO 0 cache** | Siempre — encargo que ya se investigó <3 semanas | Evita re-buscar (ahorro completo) |
| **Prompt Improver** | Prompt <50 chars o <3 parámetros | Evita búsquedas al 50% irrelevantes |
| **Briefing encargo** | Modelo concreto pero falta críticos (año/km/presupuesto) | Evita Fase 2 del 80% (candidatos fuera de rango) |
| **Plan de barrido** | Libertad de búsqueda (cómo buscar) | Claridad de qué esperar, reduces retro-ajustes |
| **Asesor de filtros** | Al construir el plan de fase | Evita probar filtros a ciegas (filtros-portales.md) |
| **Flujo D (embudo)** | Presupuesto + requisitos SIN modelo | ¡HUGE ahorro! Sondeo (8) → B (15-50) → A (35-70) |
| **Flujo A directo** | URL concreta o "evalúa este" | No aplica (ya es específico) |

### Estrategia de embudo visualizada

```
🎯 OBJETIVO: encontrar el mejor coche de 15.000 €

NIVEL 1 — Sondeo barato (Fase 1, D1)
  Filtros amplios → 7 modelos que caben en el presupuesto
  Coste: 8 peticiones (~3.000 tokens)
  └─ Output: Informe de MODELOS (solo nombres + precio-desde)

       ↓ Usuario elige 2-3 modelos

NIVEL 2 — Búsqueda media (Flujo B)
  3 fuentes → Top 5 candidatos por modelo con enlaces
  Coste: 15-20 peticiones por modelo (~6.000 tokens)
  └─ Output: Informe MODELO + candidatos con enlaces + CP1

       ↓ Usuario elige 1 candidato

NIVEL 3 — Investigación profunda (Flujo A)
  7 fuentes → Análisis completo del candidato elegido
  Coste: 35-50 peticiones (~12.000 tokens)
  └─ Output: Informe UNIDAD + Dossier + ZIP

       ↓ Veredicto

FIN
```

**Sin embudo (anti-patrón):** 70 peticiones por modelo × 3-5 modelos = 210-350 peticiones (~60.000+ tokens)
**Con embudo:** 8 + (15-20 × 2-3) + 35-50 = 73-113 peticiones (~25.000 tokens) → **58% de ahorro**

### Reglas para el asistente de planificación

1. **PASO 0 (cache) SIEMPRE** antes de planificar — no re-buscar lo ya hecho.
2. **PASO 1 (detectar) SIEMPRE ANTES de cualquier búsqueda.** No adivinar, preguntar.
3. **PASO 2 (mejorar) solo si es vago.** <50 chars o <3 parámetros.
4. **PASO 3 (planificar) SIEMPRE incluye checkpoints explícitos.** "CP-D: elige modelos", "CP1: elige candidato".
5. **PASO 4 (ejecutar) la fase completa, en cascada, con el OK del usuario ANTES** (Protocolo de Mando). Dentro de la fase no se vuelve a preguntar salvo emergencia.
6. **Mantener siempre la opción "busca tú / lo que puedas".** No bloquear por falta de detalle.
7. **Documentar el plan en el cuaderno de sesión.** Para que las correcciones del usuario afecten a búsquedas futuras.

---

## 🛠️ PROMPT IMPROVER — refinar prompts vagos (12-ago-2026)

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

## 🧭 ASESOR DE FILTROS — plan de fase con filtros por portal (16-ago-2026)

> Al construir el PLAN DE FASE, usar `../memoria/filtros-portales.md` para saber QUÉ filtros aplicar por URL (barato) vs solo por clic (navegación real). Evita probar a ciegas.

**Cómo usarlo (3 líneas):**
1. **Filtros por URL** → van en la URL directa de la búsqueda (Fase 1 barata): Coches.net `minyear/maxkm/minpower`, paginación `pagina=N`.
2. **Filtros solo por clic** → navegación real (Desktop): combustible/versión en mobile.de, marca/modelo en Coches.net.
3. **Doble pasada por kW/CV** → si el modelo es tope de gama (OPC/GTI/R/M/AMG/RS/Type R/N/Performance), 2ª búsqueda por potencia.

**Formato en el plan de fase:**
```
Filtros: ≥2018 · ≤100k · gasolina · ≤11.250 €
  Coches.net (URL): minyear=2018&maxkm=100000&minpower=110
  mobile.de (clic): Kraftstoffart=gasolina + doble pasada kW si tope
```

---

## 🔗 Referencias cruzadas

- 📄 **Briefing de encargo (qué preguntar):** `briefing_encargo.md`
- 📄 **Plantillas de prompt por flujo:** `guia_prompts.md`
- 📄 **Flujo D (descubrimiento) detallado:** `../SKILL.md` §FLUJO D
- 📄 **Filtros verificados por portal:** `../memoria/filtros-portales.md`
- 📄 **Cache de modelos/encargos:** `../memoria/modelos-medidos.md` + `../memoria/encargos.md`
