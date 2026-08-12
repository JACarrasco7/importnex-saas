# Ejemplo real — Flujo A: Opel Astra OPC 2012

> **Fuente:** caso golden #1 (rastreo real Jul-Ago 2026).
> **Veredicto:** Comprar (confianza media).

---

## El caso

| Campo | Valor |
|-------|-------|
| Coche | Opel Astra OPC 2.0T 280cv |
| Año | 10/2012 |
| Precio DE | 8.999 € |
| Coste total | 19.428 € |
| Semáforo | 🟢 |
| Margen | +5.362 € |
| Veredicto | **Comprar** |

## Por qué se compró

- **Exclusividad:** solo 2 comparables ES (Asturias + Barcelona) — nicho sin competencia.
- El skill NO pudo calcular `precio_medio` (regla: <5 comparables no da cifra puntual) → EXIT 2 interno.
- Aun así, el precio DE ya era rentable contra ambos comparables ES.
- Sin `precio_medio` ni `ahorro_estimado` numéricos en el JSON (coherente con la regla A4).

## Lección

El skill da veredicto por **exclusión directa**: cuando hay muy pocos comparables pero el precio ya es rentable contra todos, compra. No exige muestra completa para decidir.

---

# Ejemplo real — Flujo A: VW Tiguan 2017 (descartado)

> **Fuente:** casos golden #3-6 (rastreo real).
> **Veredicto:** Descartar (confianza alta).

## El caso

| Campo | Valor |
|-------|-------|
| Coche | VW Tiguan 1.4 TSI 150cv DSG |
| Año | 02/2017 |
| Precio DE | 15.490 € |
| Coste total | 17.153 € |
| Semáforo | 🔴 |
| Margen | +28 € |
| Veredicto | **Descartar** |

## Por qué se descartó

- El `coste_total` supera el `precio_medio` ES → **margen negativo / residual**.
- Aunque el precio publicado está por debajo del mercado, los costes de importación lo eliminan.
- 4 de 4 Tiguan descartados con el mismo patrón (margen <0 salvo +28€).

## Lección

El skill descarta correctamente **sin necesidad de Fase 2** (ya hay veredicto con margen negativo). La regla A4 (cuartil bajo manda) funciona.
