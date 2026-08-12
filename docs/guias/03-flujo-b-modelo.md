# Guía 03 — Flujo B: investigar un modelo

> Cuándo: quieres saber qué unidades de un modelo concreto (ej. "Golf GTI Clubsport") merecen la pena comprar, sin una URL específica.

---

## 1. Qué pedirle al skill

> "Investiga el <modelo> — quiero oportunidades de compra"

El skill hará Flujo B: buscará TODAS las unidades del modelo en las fuentes DE, calculará el hueco de precio y te dirá cuáles tienen margen.

## 2. Salida típica

Una lista de unidades candidatas con:
- URL del anuncio
- Precio publicado (DE)
- Costes estimados de importación
- **Hueco de margen** (cuánto se puede ganar)
- Veredicto por unidad

## 3. Criterios de filtrado

- Prioriza unidades con hueco ≥10%.
- Descarta si el margen tras costes es <8%.
- Cruza con vendibilidad (recalls, equipamiento, antigüedad).

## 4. Presupuesto típico

Flujo B: **hasta 50 peticiones** (avisar a 25). Se puede pedir investigar varios modelos en una sesión, pero vigila el total.

## 5. Flujo C (relacionado)

Si en vez de un modelo quieres un **barrido general** del mercado, usa Flujo C (ver `04-flujo-c-mercado`).
