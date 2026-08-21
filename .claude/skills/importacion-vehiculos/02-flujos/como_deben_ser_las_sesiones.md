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

## 🚦 Reglas anti-bucle (obligatorias en ambas skills)

| Señal | Acción | Excepción |
|---|---|---|
| 0-1 encaje en 6-8 tarjetas | Parar · ajustar filtros (km +20%, año −1, full→semi-full) · reintentar **1 vez** | Si el perfil era irreal, pedir criterios nuevos al usuario |
| 3+ reintentos fallidos en el mismo modelo | 🛑 **DESCARTAR** modelo, pasar al siguiente (`siguiente_*`) | Nunca insistir más |
| Captchas/página caída ×2 | Marcar bloqueada · seguir con otra fuente · reintentar al final | Si todas caen, abortar sesión |
| Modelo sin hueco neto (neto <0) | Avisar ANTES de gastar peticiones → `descartado` | Solo re-estudiar si cambia el mercado |
| 5h de sesión alcanzadas | Guardar estado en la cola (`siguiente_*` + `estado_cola`) y continuar en la próxima sesión | El progreso nunca se pierde |

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
```

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
