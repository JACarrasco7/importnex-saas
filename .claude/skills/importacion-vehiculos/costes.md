# Desglose económico e IEDMT — Flujo A

> **Cargar cuando:** Se va a calcular el coste total y margen de un coche (Flujo A).
> **No cargar para:** Flujo B (solo rango) ni Flujo C (sin desglose).

---

## 💰 Fórmula del precio final (según ORIGEN)

> **Regla (12-ago-2026):** el origen cambia TODOS los costes. Si el usuario no especifica origen, calcular **AMBAS variantes** y comparar dónde sale mejor (coste total puesto en Huelva).

### 🇩🇪 Origen DE (importación)
```
PRECIO FINAL = Precio anuncio DE
             + Transporte (900 € Huelva)
             + Ausfuhrkennzeichen + seguro (114 €)
             + ITV importación + tasas DGT (115 €)
             + IEDMT (según CO₂ y antigüedad)
             + Honorarios (1.500-2.250 €)
```

### 🇪🇸 Origen ES (compra nacional)
```
PRECIO FINAL = Precio anuncio ES
             + Traslado local (0-300 €, según distancia)
             + Gestoría / transferencia (~150 €)
             + ITV en vigor (0 € si la tiene; ~50 € si toca)
             + Tarifa de gestión ES (~500 €, validar con el usuario)
```
> **SIN transporte DE, SIN ausfuhr, SIN ITV importación, SIN IEDMT.** El coche ES ya está matriculado y con impuestos pagados.
> **⚠️ TARIFA ES REDUCIDA (15-ago-2026):** si la unidad está en España, NO se cobran los 1.500 € de importación. Se cobra una **tarifa de gestión reducida (~500 €)** — confirmar el importe exacto con el usuario en cada encargo (caso Tiguan 15-ago: se asumieron 1.500 € por defecto y no aplicaba).
> **⚠️ Canarias/Baleares:** IGIC, no IVA. El traslado peninsular extra NO compite en igualdad de condiciones — descartar o avisar.

### ⚖️ Comparativa DE vs ES (cuando origen no especificado)
```
Coste total DE = precio DE + 900 + 114 + 115 + IEDMT + honorarios
Coste total ES = precio ES + traslado + gestoría + honorarios

→ Elegir el menor. Si empatan (<300 €), preferir ES (menos riesgo: sin trámites de importación).
```
### 🎯 Techo de precio de búsqueda (desde presupuesto del cliente)

> **Regla (actualizada 15-ago-2026):** para FILTRAR (Flujo B/C), el techo del coche sale del presupuesto del cliente según la **modalidad de honorarios** del encargo (M1/M2/M3, ver `briefing_encargo.md` §Modalidades). **3 fallos reales por asumir** (12-ago y 15-ago ×2): ya NO se asume — se pregunta o se reformula la interpretación en 1 línea.

```
Techo coche DE = presupuesto − transporte 900 − ausfuhr 114 − ITV 115 − IEDMT − honorarios(solo en M1)
Techo coche ES = presupuesto − traslado − gestoría − honorarios(solo en M1)

M1 · Incluidos  → restar honorarios del techo
M2 · Aparte     → NO restar honorarios (se cobran fuera del presupuesto)
M3 · No se cobran → NO restar (tarifa 0 €; cliente especial)

Ej M2/M3 (María, 9.000 €, 15-ago): DE ≈ 7.870 € · ES ≈ 8.550-8.850 €
```
**Peso del coste fijo según precio de compra:**

| Compra | Peso coste fijo | Viabilidad |
|---|---|---|
| 3.000-6.000 € | 50-100% | ❌ Imposible |
| 8.000 € | 34% | ✅ Umbral 12% |
| 14.000 € | 22% | ✅ Cómodo |
| 25.000 € | 15% | ✅ Muy cómodo |
| 35.000 € | 11% | ✅ Óptimo |

---

## 🧾 IEDMT (Orden HAC/1501/2025, vigor 1-ene-2026)

**Fórmula:**

```
valor mercado = precio tabla ministerial × coef antigüedad (Anexo IV)
minoración    = valor mercado × (IVA + tipo) / (1 + IVA + tipo)   ← art.69
base imponible = valor mercado − minoración
IEDMT          = base imponible × tipo CO₂
```

**Coeficientes de antigüedad (Anexo IV):**

| Años | % | Años | % | Años | % |
|---|---:|---|---:|---|---:|
| ≤1 | 100 | 4-5 | 47 | 8-9 | 24 |
| 1-2 | 84 | 5-6 | 39 | 9-10 | 19 |
| 2-3 | 67 | 6-7 | 34 | 10-11 | 17 |
| 3-4 | 56 | 7-8 | 28 | 11-12 | 13 |
| | | | | **>12** | **10** |

**Tipos impositivos según CO₂:**

| Emisiones | Tipo |
|---|---:|
| ≤120 g/km | 0% |
| 121-159 g/km | 4,75% |
| 160-199 g/km | 9,75% |
| ≥200 g/km | 14,75% |

**Fuente del CO₂:** km77 (no estimar). Si no está en km77, consultar BOE o marcar `co2_confirmado: false` en el JSON.

> **⚠️ Regla (12-ago-2026): NUNCA estimar el IEDMT "de oído".** Caso real: se estimó 700-1.200 € para un Astra OPC de 2012 y el cálculo real fue ~280 € (porque con >12 años el coeficiente cae a 10%). Siempre calcular con la tabla de coeficientes + CO₂ real, no con un rango mental. Si falta el CO₂, buscar en km77 antes de dar cifra.

---

## ⚖️ Regla de cálculo: dos IEDMT

Claude calcula el IEDMT **dos veces** — con y sin minoración art.69 — para que el gestor fiscal elija.

**En el JSON (`costes`):**
```json
{
  "iedmt_estimado": 1850,
  "iedmt_sin_minoracion": 2100,
  "iedmt_metodologia": "PVP km77: 38.500€. Antigüedad: 4 años (47%). CO₂: 165 g/km (9,75%). Con minoración art.69: 1.850€. Sin minoración: 2.100€."
}
```

**Laravel (`Car::calculateIEDMT()`):** Recalcula internamente como verificación. Si la diferencia entre la estimación de Claude y el cálculo de Laravel es >10%, se marca en `avisos`.

---

## 💵 Precio máximo de compra (solo Flujo A)

**Fórmula:**

```
precio_max = comparable_objetivo × (1 − umbral)
           − transporte − ausfuhr − ITV/tasas − IEDMT − honorarios
```

**Ejemplo:**
```
comparable_objetivo: 36.850 € (tras ajuste)
umbral: 12% (segmento Rotación)

precio_max = 36.850 × 0,88 − 900 − 114 − 115 − 1.850 − 2.000
           = 32.428 − 4.979
           = 27.449 €

→ Ofertar máximo 27.500 € por el coche en Alemania
```

**Regla:** Va en todo informe de Flujo A. **En una línea** al final del informe §10 (Qué hacer).

---

## 🧾 IVA

**Caso 1: Particular sin NIF-IVA**
- Paga `grossAmount` siempre (precio con IVA alemán incluido).
- No puede deducir IVA.

**Caso 2: Empresa con NIF-IVA intracomunitario**
- Paga `netAmount` (precio sin IVA alemán).
- Paga IVA español (21%) al importar (modelo 309).

**⚠️ Regla 6/6000:** Si el coche tiene <6 meses **O** <6.000 km → 21% IVA español (modelo 309) aunque sea usado.

---

## 💱 Anuncios en moneda extranjera

**CHF (Francos suizos):** Convertir a EUR usando tipo de cambio del día (verificar en Google: `CHF to EUR`).

```
Ejemplo: 25.000 CHF × 1.05 = 26.250 EUR
```

**Regla:** Si el anuncio está en CHF, convertir y anotar en §6 (Desglose): "Precio original: 25.000 CHF → 26.250 EUR (tipo cambio: 1.05)"

**GBP (Libras esterlinas):** Mismo procedimiento. Reino Unido = volante a la derecha, NO importar.

---

## 📊 Ejemplo completo de desglose (§6 del informe)

```
COSTE PUESTO EN HUELVA — VW Golf GTI Clubsport 2017

Precio anuncio DE:              28.900 € (mobile.de, 11/08/2026)
Transporte Huelva:                 900 € (tarifa estándar Alemania-Huelva)
Ausfuhrkennzeichen + seguro:       114 € (matrícula temporal + seguro 15 días)
ITV importación:                    95 € (ITV específica importación)
Tasas DGT:                          20 € (tasa tráfico)
IEDMT (con minoración art.69):   1.850 € (PVP 38.500€ × 47% × 9,75%)
  ↳ Sin minoración:              2.100 € (gestor fiscal elige)
Honorarios JJ Import:            2.000 € (contrato Pro)

─────────────────────────────────────────
TOTAL:                          33.879 €

Precio objetivo ES (tras ajuste):36.850 €
AHORRO ESTIMADO:                 2.971 € (8,1%)
PRECIO MÁXIMO A OFERTAR EN DE:  27.500 €
```

**Regla:** Mostrar el desglose en §6 del informe con cada línea justificada (de dónde sale el dato).
