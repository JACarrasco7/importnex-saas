# Guía 05 — Leer los informes

> Cómo interpretar los informes de valoración y el briefing PDF que genera el skill.

---

## 1. El informe de valoración (JSON → ficha en app)

Cada evaluación de Flujo A/B se importa al expediente del coche en la app. Incluye:

| Bloque | Qué contiene |
|--------|--------------|
| `vehiculo` | Marca, modelo, año, km, CO₂, combustible |
| `anuncio` | Precio publicado, URL, portal |
| `investigacion` | 9 aspectos (fiabilidad, recalls, precio de mercado, homologación, etc.) |
| `balance` | Costes: transporte, IEDMT, honorarios |
| `veredicto` | Comprar / Comprar si baja / Dudoso / Descartar + confianza |
| `mercado` | Precio medio ES, comparables, semáforo |
| `avisos` | Advertencias (CO₂ no confirmado, comparables sin URL, etc.) |

## 2. El briefing PDF (para el cliente)

Un PDF con membrete de JJ Import Motors y tarjetas resumen. Se adjunta al expediente vía API (`POST /api/cars/{car}/briefing-pdf`).

## 3. Señales de alerta en el informe

- ⚠️ "CO₂ no confirmado por COC" → el IEDMT puede variar.
- ⚠️ "N comparables descartados por no tener URL" → muestra reducida.
- ⚠️ Confianza "baja" → revisar antes de decidir.

## 4. El semáforo

- 🟢 **green** → margen cómodo, compra clara.
- 🟡 **amber** → marginal, condiciones.
- 🔴 **red** → descartar.

## 5. Veredictos y su acción

| Veredicto | Acción |
|-----------|--------|
| Comprar | Procede a compra |
| Comprar si baja | Negocia hasta `precio_objetivo` |
| Dudoso | Revisa comparables |
| Descartar | No comprar |
