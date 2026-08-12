# Guía 02 — Flujo A: evaluar un coche concreto (URL)

> Cuándo: un cliente te pasa la URL de un coche (mobile.de, AutoScout24, Wallapop, etc.) y quieres saber si merece la pena comprarlo para revenderlo.

---

## 1. Qué pedirle al skill

> "Evalúa este coche: <pega la URL>"

El skill hará Flujo A: rastreará el anuncio, buscará comparables en 7 fuentes, calculará costes (transporte, IEDMT, honorarios) y emitirá un **veredicto**.

## 2. Umbrales de decisión

| Señal | Valor | Acción |
|-------|-------|--------|
| **Hueco de margen** | ≥10% | Nicho objetivo — buena oportunidad |
| Hueco de margen | 8–10% | Marginal — comprar solo si vendibilidad alta |
| Hueco de margen | <8% | **Descartar** |
| **Rotación** | ≥10% | OK |
| **Tramo 8-14k** | ≥12% | OK |

## 3. Costes que siempre debe incluir

- Transporte Alemania→España
- Ausfuhr / ITV / tasas DGT
- **IEDMT** (según CO₂ y antigüedad — verificado en `config/iedmt.php`)
- Honorarios del skill

## 4. El veredicto

- **Comprar** → procede a la compra, genera briefing.
- **Comprar si baja** → necesita `precio_objetivo` (te lo dirá el skill). Negocia hasta ese precio.
- **Dudoso** → revisa los comparables.
- **Descartar** → no comprar.

## 5. Tras el veredicto

1. Adjunta el **briefing PDF** al expediente del coche (lo hace el skill vía API).
2. Si se compra y luego se vende → **registra el cierre** (ver `06-cierre-venta`).

## 6. Presupuesto típico

Flujo A: **hasta 70 peticiones** (avisar al usuario a las 35). Si se agota sin veredicto → pausa, muestra resumen parcial.
