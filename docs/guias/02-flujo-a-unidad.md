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

## 7. Comprobar el suelo a mano (ficha del coche)

En la ficha del coche, pestaña **Mercado**, hay dos bloques de enlaces:

- **Búsquedas realizadas** — las URLs reales que usó el skill (vienen dentro del ZIP).
- **Ver suelo en portales** — enlaces **generados por Laravel** desde los datos del coche
  y ordenados por **precio ascendente** en cada portal:
  - 🇩🇪 `mobile.de` — marca + modelo (cuando hay un `modelId` verificado) + año ±1. La
    potencia va en **kW** (`cv × 0,7355 ±4`), que es lo que entiende el portal.
  - 🇪🇸 `coches.net` — `MakeIds[0]` + `Versions[0]` (nombre limpio del modelo) + potencia
    en **CV** (`±5`).

Sirven para abrir el listado real y comparar **a mano** el precio de esta unidad con el
suelo del mercado, sin depender de que el ZIP traiga los enlaces (los coches importados
antes del 09-sep-2026 no los traen). La etiqueta `informe` indica que ese portal **ya**
tenía una búsqueda real en el informe.

> ⚠️ **mobile.de y los `modelId`.** El portal identifica cada modelo con un número que
> **cambia con el tiempo**. Si el coche ya tiene una búsqueda real en el informe se
> reutiliza su `modelId`; si no, el enlace filtra por texto (`q=`) porque mandar `ms=`
> sin `modelId` deja la página en modo formulario (0 resultados). Si abres el enlace y no
> ves tarjetas, afina los filtros a mano en el portal.

> ⚠️ Estos enlaces son **material interno** (`.ai/rules/mercado.md`): solo se ven en el
> panel admin, nunca en el marketplace público ni en el tracking del cliente.
