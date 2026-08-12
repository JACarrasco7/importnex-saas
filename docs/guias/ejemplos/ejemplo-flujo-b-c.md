# Ejemplo — Flujo B y C (modelo + mercado)

> Ejemplos orientativos de lo que devuelven los flujos B (modelo) y C (mercado).

---

## Flujo B — Investigar un modelo (ej. Golf GTI Clubsport)

**Petición:** "Investiga el Golf GTI Clubsport — ¿hay oportunidades de compra?"

**Salida típica:**

```
Golf GTI Clubsport (segmento Nicho):
- 12 unidades en DE (mobile.de + AutoScout24)
- Mediana DE: 26.800 € | Mediana ES: 34.500 €
- Hueco: 22.4%
- Mejores candidatas:
  1. [URL] 2021, 25.400 € → margen +4.100 € (Comprar)
  2. [URL] 2020, 27.100 € → margen +2.300 € (Comprar si baja)
  3. [URL] 2022, 29.900 € → margen -200 € (Descartar)
```

**Qué hacer:** revisar las unidades con hueco ≥10%, cruzar vendibilidad (recalls KBA, equipamiento).

---

## Flujo C — Escanear el mercado (deportivos 15-40k)

**Petición:** "Escanea el mercado de deportivos entre 15-40k€"

**Salida típica (scouting):**

```
Scouting deportivos 15-40k — 7 modelos escaneados:
- Golf GTI:      hueco 22%  | 12 uds DE | Recomendación: COMPRAR
- Audi S3:       hueco 15%  |  9 uds DE | Recomendación: COMPRAR
- BMW M240i:     hueco 11%  |  4 uds DE | Recomendación: MARGINAL
- Opel Astra OPC:hueco 30%  |  3 uds DE | Recomendación: COMPRAR (nicho)
- Mercedes C43:  hueco -3%  |  6 uds DE | Recomendación: NO
- ...
Resumen: 2 modelos con hueco claro, 1 marginal, 4 sin hueco.
```

**Qué hacer:** elige el modelo con mejor hueco + rotación, luego baja a Flujo B (investigar unidades) o Flujo A (URL concreta).

**Dónde queda:** se guarda en BD (`scouting_mercado`), consultable vía `GET /api/scouting`.
