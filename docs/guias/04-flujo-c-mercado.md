# Guía 04 — Flujo C: escanear el mercado

> Cuándo: revisión periódica de oportunidades — qué modelos/segmentos tienen hueco de precio en Alemania vs España.

---

## 1. Qué pedirle al skill

> "Escanea el mercado de deportivos entre 15-40k€"

El skill hará Flujo C: escaneará las fuentes, agregará por modelo y detectará **huecos de mercado** (modelos más baratos en DE que su equivalente en ES).

## 2. Salida típica

Un **scouting** con:
- Modelos escaneados
- Hueco de precio por modelo (`hueco_pct`)
- Nº de unidades disponibles en DE
- Recomendación aproximada por modelo
- Resumen ejecutivo

## 3. Dónde se guarda

Cada escaneo se importa a la BD (tabla `scouting_mercado` + `modelos_mercado`). Puedes consultarlos:

- **API:** `GET /api/scouting` (autenticado por token, scoped por organización)
- **App:** en la web (endpoint público de marketplaces) — sección de oportunidades

## 4. Presupuesto típico

Flujo C: **hasta 100 peticiones** (~7 modelos, 12-18 peticiones por modelo). Avisar al 50 (50) y al 80 (80).

## 5. Cuándo hacerlo

- Semanal o quincenal según volumen de mercado.
- Antes de decidir "qué modelo importamos ahora".
