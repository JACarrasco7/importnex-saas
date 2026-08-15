# Anti-patrones — Reglas duras del skill

> Cargar cuando Claude dude de si una práctica es correcta. Estas son **reglas duras** que bloquean errores históricos.
> Cualquier excepción debe justificarse EXPLÍCITAMENTE en el chat.

---

## 🛡️ Los 14 anti-patrones

| # | Anti-patrón | Regla dura |
|---|---|---|
| **A1** | Descartar por silencio | "Si el anuncio NO declara siniestro, NO asumir que lo tiene. Sello `man`, sigue en pool." |
| **A2** | Saltar mobile.de | "Si mobile.de no está en tabla cobertura con estado OK o bloqueada+intentos, NO hay veredicto." |
| **A3** | IEDMT sin fuente | "El CO₂ y PVP vienen de km77 o BOE. NUNCA de 'modelo similar'. Marcar `co2_confirmado: false` si estimado." |
| **A4** | Veredicto sin cuartil bajo | "Si ahorro contra cuartil bajo es negativo, veredicto de margen es NO, aunque mediana diga SÍ." |
| **A5** | Informe sin precio máximo | "Todo informe Flujo A incluye precio máximo de compra. Sin excepción." |
| **A6** | Tabla sin enlaces | "Toda tabla de candidatos incluye columna ENLACE clickable. Si no hay URL, se construye desde el ID. Los enlaces de comparables van SIEMPRE al anuncio directo (ficha del vehículo), NUNCA a una búsqueda/filtro del portal." |
| **A7** | Cobertura incompleta | "Siempre se intentan las 7 fuentes. Con <7 NO hay cifras ni veredicto: informe PARCIAL + preguntar al usuario." |
| **A8** | AutoScout24 como precio | "AutoScout24 NUNCA da precio de referencia (agrega feeds sin cribar). Solo para contar ofertas." |
| **A9** | Afirmar sin comprobar | "NUNCA decir 'sí lo vi en mi barrido' sin comprobarlo. Si no está en los datos capturados, digo que no está. Un falso positivo es peor que un falso negativo." |
| **A10** | Precio financiado como contado | "El precio grande de MUY CAR/Flexicar (y portales ES) suele ser el FINANCIADO, no el contado. Confirmar el contado antes de meterlo en la tabla: Milanuncios `price.cashPrice.value`, Coches.net/Wallapop abrir ficha y buscar 'contado'." |
| **A11** | Paginación parcial | "Coches.net ordena por relevancia, NO por precio. Para cliente concreto hay que recorrer TODAS las páginas con `pg=` filtrando por precio `pf=`. Si no se puede paginar todo, DECIRLO y marcar cobertura parcial." |
| **A12** | Página 1 como listado | "Ordenar por precio ascendente y leer SOLO la página 1 = sesgo hacia lo más barato/viejo (caso María 15-ago: 526 resultados, se enseñaron 8 de 3.000-4.200 € y se perdieron DS4/308/Astra). El listado del cliente cubre TODO el rango de presupuesto: todas las páginas o bandas de precio. Si se trunca, DECLARARLO." |
| **A13** | Filtros alterados sin declarar | "Cualquier ampliación/relajación de los filtros del encargo (año 2016→2012, km, precio) se declara ANTES de navegar y se marca en el informe. Nunca cambiar los criterios del cliente en silencio." |
| **A14** | Abandonar el camino en silencio | "El flujo es un camino numerado con waypoint 📍 en cada mensaje. Una pregunta lateral del usuario es una misión lateral: se responde y se RETOMA el paso (↩⃾ Vuelvo al paso N). Si tras una desviación el entregable de la fase no llegó, es un fallo. Un cambio de destino real se declara (🔀 Cambio de camino)." |

---

## 🔍 Detalle de cada uno

### A1 — Descartar por silencio

**Error típico:** Anuncio no dice "unfallfrei" → Claude asume que tuvo siniestro.

**Regla:** El silencio NUNCA es descalificante. Solo se descarta por silencio + otras señales (precio muy bajo, vendedor B2B sin garantía, fotos inconsistentes). Una sola pieza de información faltante = `sello man` + acción ("preguntar al vendedor").

**Casos reales:**
- ❌ "No dice Unfallfrei" → descartado
- ✅ "No dice Unfallfrei" → `sello man` + "pedir COC o TÜV-Bericht"

---

### A2 — Saltar mobile.de

**Error típico:** Mobile.de da 403 o se bloquea. Claude sigue adelante con AS24 + AutoUncle y da veredicto.

**Regla:** mobile.de es la fuente **PRINCIPAL** para Alemania. Si no aparece en la tabla de cobertura con estado OK o bloqueada+intentos documentados, NO se emite veredicto. Se marca informe como **PARCIAL** y se pide al usuario decidir.

**Orden de reintento para mobile.de:**
1. `www.mobile.de` (en lugar de `suchen.` o `m.`) → recarga + espera
2. Intentar de nuevo con 2-3 s de pausa (captcha transitorio)
3. Solo entonces: marcar bloqueada en la tabla con los intentos documentados

---

### A3 — IEDMT sin fuente

**Error típico:** Claude estima CO₂ y PVP basándose en "modelos similares" o de cabeza.

**Regla:** El CO₂ y PVP deben venir de fuentes verificables:

| Fuente | Cuándo |
|---|---|
| **km77.com** | SIEMPRE intentar primero. Navegación normal (screenshot + lectura de la ficha de datos). |
| **BOE** Orden HAC/1501/2025 | Si km77 no tiene esa versión exacta. |
| **Estimación declarada** | Solo si 1 y 2 fallan. Marcar `co2_confirmado: false`. |

**NUNCA:**
- Usar CO₂ de "otro modelo similar"
- Usar PVP de cabeza o de otro acabado
- Dar IEDMT sin decir de dónde sale el CO₂

---

### A4 — Veredicto sin cuartil bajo

**Error típico:** La mediana da 19% de ahorro. Claude dice "COMPRA PRIORITARIA". El cuartil bajo da -8%. El cliente no compra porque nadie vende tan barato.

**Regla:** El veredicto de margen se toma **contra la mediana Y el cuartil bajo**. Si contra el cuartil bajo el ahorro es negativo, el veredicto de margen es **NO**, aunque la mediana diga SÍ.

**Por qué:** El cliente no compra la mediana — compara contra el coche más barato que puede conseguir en España en condiciones equivalentes. La mediana es el número del discurso comercial; el cuartil bajo y el suelo son los del veredicto.

---

### A5 — Informe sin precio máximo

**Error típico:** Claude da el desglose y el veredicto, pero no calcula el **precio máximo de compra** (lo que se puede pagar al alemán).

**Regla:** Todo informe de Flujo A incluye el precio máximo de compra, en una línea:

```
precio_max = comparable_objetivo × (1 − umbral)
           − transporte − ausfuhr − ITV/tasas − IEDMT − honorarios
```

**Por qué:** Cambia cómo se entra a negociar. Sin este número, no se sabe hasta cuánto pujar.

---

### A6 — Tabla sin enlaces

**Error típico:** Claude muestra una tabla de candidatos con precio/año/km pero sin URLs. El usuario tiene que buscar los anuncios manualmente.

**Regla:** Toda tabla de candidatos lleva columna **ENLACE** clickable. Si la fuente no da URL directa, se construye desde el ID:

---

### A7 — Cobertura incompleta (12-ago-2026)

**Error típico:** Se dan cifras y veredicto con solo 2-3 fuentes (ej. mobile.de + Coches.net) dejando Wallapop, Milanuncios, kleinanzeigen, AS24 sin mirar.

**Regla:** Se intentan SIEMPRE las 7 fuentes (ni más ni menos). Si alguna falla, reintentar 1-2 veces y luego marcarla `bloqueada (intentos)`. Con <7 fuentes NO se dan cifras ni veredicto → informe **PARCIAL** + preguntar al usuario si continúa o acepta PARCIAL.

---

### A8 — AutoScout24 como precio (12-ago-2026)

**Error típico:** Usar AutoScout24.de como referencia de precio. Agrega feeds de varios portales sin cribarlos → anuncios dañados/siniestrados y fechas mal etiquetadas se cuelan (caso real 12-ago: coche siniestrado a 2.499 € como "más barato").

**Regla:** AutoScout24 **solo sirve para contar** ofertas (N uds). NUNCA para precio de referencia. Precio DE = mobile.de; precio ES = Coches.net.

| Fuente | Construcción URL |
|---|---|
| mobile.de | `https://www.mobile.de/fahrzeuge/details.html?id=<id>` |
| Coches.net | Slug del anuncio en URL |
| AutoScout24 | `/lst/<marca>/<modelo>/<id>.htm` |
| AutoUncle | Enlace al portal de origen (sí lo trae) |
| Milanuncios | `/coches-de-segunda-mano/<id>.htm` |
| Wallapop | `/app/item/<id>` |

---

## ✅ Verificación rápida

Antes de cerrar cualquier informe, verificar:

- [ ] ¿Descarté algún candidato por silencio? (A1)
- [ ] ¿mobile.de está en la cobertura con OK o bloqueada+intentos? (A2)
- [ ] ¿CO₂ y PVP vienen de km77 o BOE? (A3)
- [ ] ¿Veredicto contra cuartil bajo también? (A4)
- [ ] ¿Informe Flujo A tiene precio máximo? (A5)
- [ ] ¿Todas las tablas tienen columna ENLACE? (A6)

---

## 📚 Origen de cada anti-patrón

| # | Origen | Fecha |
|---|---|---|
| A1 | "El silencio no es descalificante" — corregido tras debate con usuario | 10-ago-2026 |
| A2 | Falla móvil.de durante sprint scraper | 10-ago-2026 |
| A3 | IEDMT erróneo por CO₂ estimado de similar | 09-ago-2026 |
| A4 | Astra OPC: veredicto positivo con cuartil bajo negativo | 09-ago-2026 |
| A5 | Regla histórica del skill original | pre-09-ago |
| A6 | "El usuario no puede ver el coche sin PDF" — feedback directo | 11-ago-2026 |
