# Anti-patrones — Reglas duras del skill

> Cargar cuando Claude dude de si una práctica es correcta. Estas son **reglas duras** que bloquean errores históricos.
> Cualquier excepción debe justificarse EXPLÍCITAMENTE en el chat.

---

## 🛡️ Los 6 anti-patrones

| # | Anti-patrón | Regla dura |
|---|---|---|
| **A1** | Descartar por silencio | "Si el anuncio NO declara siniestro, NO asumir que lo tiene. Sello `man`, sigue en pool." |
| **A2** | Saltar mobile.de | "Si mobile.de no está en tabla cobertura con estado OK o bloqueada+intentos, NO hay veredicto." |
| **A3** | IEDMT sin fuente | "El CO₂ y PVP vienen de km77 o BOE. NUNCA de 'modelo similar'. Marcar `co2_confirmado: false` si estimado." |
| **A4** | Veredicto sin cuartil bajo | "Si ahorro contra cuartil bajo es negativo, veredicto de margen es NO, aunque mediana diga SÍ." |
| **A5** | Informe sin precio máximo | "Todo informe Flujo A incluye precio máximo de compra. Sin excepción." |
| **A6** | Tabla sin enlaces | "Toda tabla de candidatos incluye columna ENLACE clickable. Si no hay URL, se construye desde el ID." |

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
1. `www.mobile.de` (en lugar de `suchen.` o `m.`)
2. `web_fetch` ficha individual (`/fahrzeuge/details.html?id=`)
3. `web_fetch` listado
4. Solo entonces: marcar bloqueada en la tabla con los 3 intentos

---

### A3 — IEDMT sin fuente

**Error típico:** Claudeestima CO₂ y PVP basándose en "modelos similares" o de cabeza.

**Regla:** El CO₂ y PVP deben venir de fuentes verificables:

| Fuente | Cuándo |
|---|---|
| **km77.com** | SIEMPRE intentar primero. `web_fetch` sin navegador. |
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
