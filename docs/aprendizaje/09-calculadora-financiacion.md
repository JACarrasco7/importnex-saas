# 09 — Calculadora de financiación: ¿contra qué calcula?

## Tu duda, respondida directamente

**No hay banco. No hay acuerdo con ninguna entidad. Es una SIMULACIÓN matemática pura.**

Es exactamente lo mismo que hacen Idealista, Coches.net, Mobile.de o cualquier portal de coches: una calculadora orientativa con la fórmula estándar de préstamo, un TIN de referencia editable y un disclaimer legal.

## Qué hace exactamente

Bloque en la ficha del coche (`MarketplaceShow.vue`, commit `83ccc2b`):

```
Precio del coche:   18.500 €   (editable)
Entrada:            20% ▓▓▓░░░  (slider 0-50%)
Plazo:              60 meses   (selector 12/24/36/48/60/72/84)
TIN:                7,99%      (editable)

→ Cuota mensual:    ~304 €/mes
→ Total a pagar:    21.240 €
→ Intereses:        2.740 €

"Cálculo orientativo, sujeto a aprobación bancaria."
```

Todo se recalcula **en el navegador, en tiempo real, sin tocar el servidor**.

## La matemática (sistema de amortización francés)

Es la fórmula que usan TODOS los bancos para préstamos con cuota fija:

$$\text{cuota} = C \cdot \frac{i \cdot (1+i)^n}{(1+i)^n - 1}$$

Donde:
- $C$ = capital a financiar = precio − entrada
- $i$ = interés mensual = TIN / 12 / 100
- $n$ = número de meses

En JavaScript son 5 líneas:

```js
const principal = price * (1 - downPayment / 100);
const i = tin / 12 / 100;
const n = months;
const cuota = principal * (i * Math.pow(1 + i, n)) / (Math.pow(1 + i, n) - 1);
```

**No necesitas ningún banco para esto** porque no estás CONCEDiendo crédito: estás mostrando una estimación. El TIN del 7,99% es un valor de referencia del mercado (lo típico de un préstamo de coche en España), y el usuario puede cambiarlo.

## ¿Por qué existe si no vendemos financiación?

Porque responde a la **pregunta nº1 del comprador de coche**: *"¿cuánto me cuesta AL MES?"*

El cerebro humano no procesa "18.500€" (número grande, abstracto). Procesa "300€/mes" (encaja en su nómina). La calculadora:

1. **Convierte precio absoluto → cuota mensual** = el coche "parece" más asequible.
2. **Mantiene al usuario en tu página** jugando con el slider en lugar de irse a calcularlo a otro sitio (y no volver).
3. **Cualifica el lead:** quien juega 2 minutos con la financiación y luego envía la solicitud, llega mucho más convencido.

## Sobre vuestro modelo de negocio (importación, no compraventa)

Tienes razón en el matiz: JJ Import Motors **no vende coches de stock, ofrece servicio de importación**. Por eso:

- La calculadora es **genérica y con disclaimer**: no promete nada, orienta.
- El precio editable es el `purchase_price` de la oferta de importación (precio en origen + servicio), no un "precio de venta" con margen de compraventa.
- **Y sí:** si el SaaS se vende a compraventas tradicionales, la misma calculadora les sirve tal cual — ellos SÍ financian (vía financieras de los fabricantes o bancos partners). Es un componente que escala a los dos modelos.

### Si algún día quisieras financiación REAL

El siguiente nivel sería integrar una fintech de crédito al consumo (tipo Pepper, Dineo, Younited Credit tienen APIs de pre-scoring). El flujo sería: usuario mete datos → API devuelve cuota real pre-aprobada. Pero eso es otra liga (contratos, regulación, KYC). La simulación actual cubre el 95% del valor con 0% de la complejidad legal.

## Detalles legales/UX que no son opcionales

- **Disclaimer SIEMPRE visible:** "Cálculo orientativo, sujeto a aprobación bancaria". Sin él, un usuario podría reclamar que le "ofreciste" esa cuota.
- **TIN editable:** si lo fijas oculto, parece una oferta vinculante. Editable = herramienta del usuario.
- **No guardar nada en BD:** es una simulación anónima. No es un dato de negocio (todavía).

> **Regla reutilizable:** Las calculadoras orientativas son de las features con mejor ROI que existen: ~20 líneas de JS, cero backend, cero riesgo legal (con disclaimer), y atacan la objeción nº1 del cliente ("¿puedo permitírmelo?"). Cualquier e-commerce de ticket alto (>500€) debería tener una.
