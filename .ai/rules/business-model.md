# Modelo de negocio — REGLA DE ORO

> **JJ Import Motors NO vende coches.** Gestionamos la **compra** de coches para
> clientes, tanto en España como importados (sobre todo desde Alemania).
> El cliente es quien compra el coche — nosotros solo le cobramos honorarios
> por el servicio de búsqueda, importación y gestión.

## Implicaciones en cualquier output (código, copy, UI, marketing)

| Concepto de VENDEDOR (NO usar) | Servicio que SÍ prestamos |
|---|---|
| Vender | Gestionar la compra |
| Stock / EN STOCK | Localizado / En valoración / En tránsito / Comprado (estado de gestión) |
| Reservar (un cliente) | Reservado para ti (estado temporal del encargo) |
| Precio final (al cliente) | Honorarios + gastos reembolsables (el cliente paga al vendedor original) |
| **Garantía** | **NO la damos.** El cliente negocia garantía con el vendedor. |
| **Financiación** | **NO la ofrecemos.** El cliente se financia por su cuenta. |
| Prueba / test drive | NO somos concesionario, no hay prueba en nuestro local. El cliente va al vendedor original si quiere ver el coche antes. |
| Logística de entrega | Sí la gestionamos (transporte + trámites + entrega en domicilio). |
| "Llave en mano" | NO — nosotros no entregamos llaves, el cliente recoge o recibe del vendedor / transporte. Decir "gestión llave en mano" si quiere ser figurado, mejor evitar. |

## Caption del precio en el dossier

**Regla del caption (caption debajo del precio en el hero):**

```
Precio: <precio del coche en €>     <- el precio del coche (campo `precio`)
Caption: + gastos gestión de compra  <- UNA sola línea, sin desglose
```

**NUNCA** poner en el caption del precio:
- ❌ `IVA incluido` — no vendemos, no cobramos IVA
- ❌ `Llave en mano` — no entregamos llaves propias
- ❌ `Sin sorpresas` — suena a marketing de stock
- ❌ `Garantía 12 meses` — no la ofrecemos
- ❌ `+ transporte + ITV + COC + DGT + honorarios` — desglose redundante: el ZIP
  ya trae el cálculo total del precio cliente en el esqueleto; el caption solo
  recuerda que se suma el coste de gestión de compra
- ❌ `Compra + transporte + trámites + honorarios JJ Import Motors` — versión
  anterior, demasiado prolija

**Por qué solo "+ gastos gestión de compra":**
El precio que muestra el dossier es el **precio del coche** (`precio` del ZIP /
`purchase_price` del Car). A ese precio hay que añadir los **gastos de gestión
de compra** (lo que cobra JJ Import Motors por el servicio de búsqueda,
verificación, negociación, transporte, trámites). No desglosamos el transporte
ni los trámites en el caption porque ese desglose ya viene en el informe
interno y, si el ZIP lo trae, en los bloques `[COSTE]` del informe cliente.

## Copy / UI prohibida (cualquier idioma)

- ❌ `IVA incluido` (no vendemos, no hay IVA nuestro)
- ❌ `Garantía 12 meses` / `Garantía mecánica` / `Cobertura mecánica completa`
- ❌ `Financiación` / `Cuotas` / `60 cuotas sin entrada` / `Solicitar simulación`
- ❌ `Reserva tu prueba sin compromiso` / `Llámanos y organizamos la prueba`
- ❌ `Llave en mano` / `Sin sorpresas` / `Garantía 12 meses` (en el caption del precio)
- ❌ `EN STOCK` / `Stock limitado` / `Dossier exclusivo` (esto último OK si es para el cliente, pero el original "stock limitado" implica vender)
- ❌ `Tu próxima compra` / `Hazte con él`
- ❌ `Consigue tu` / `Llévate tu` / `Reserva el tuyo`
- ❌ `Te lo financiamos` / `Aprobación en 24-48h`

## Copy correcta (lo que sí podemos decir)

- ✅ `Gestionamos la compra` / `Te ayudamos a comprar`
- ✅ `Compra, transporte y trámites incluidos` (gestión integral)
- ✅ `Localizado en España` / `Importado desde Alemania` (origen verificado)
- ✅ `Historial verificado` / `Origen verificado`
- ✅ `Informe de oportunidad` (no "exclusivo" + "stock")
- ✅ `Veredicto JJ Import Motors` (análisis interno, sin prometer nada)
- ✅ `¿Por qué este coche?` (argumentos de compra)
- ✅ `Comparativa de mercado` (datos objetivos)
- ✅ `¿Seguimos adelante?` / `Hablar por WhatsApp` (la conversación, no la venta)
- ✅ `Precio total cliente` si el ZIP lo trae (es lo que paga el cliente, calculado por Claude con todos los conceptos: compra + transporte + ITV + COC + DGT + honorarios JJ Import Motors)

## Pros / Cons en el dossier

**Mostrar SOLO lo bueno al cliente.** Las contraindicaciones (margen bajo, CO2
alto, kilometraje alto, etc.) son análisis INTERNO — van al informe interno del
equipo, NO al dossier público. Si el ZIP trae `EN_CONTRA`, el dossier debe
**ignorarlo** por defecto (mostrar solo `A_FAVOR`).

Configurable si el cliente lo pide expresamente, pero por defecto:
- ❌ No mostrar `Aspectos a considerar` / `EN_CONTRA` en el dossier público.
- ❌ No mostrar `TIPS` "cosas que debes saber antes de comprar" — eso es consejo
  para un comprador en concesionario, no para nuestro cliente de gestión.

## Cambio automático (siempre)

`Cambio` debe salir **limpio**, sin coletillas técnicas:

| Modelo trae… | Mostrar |
|---|---|
| `Automático (doble embrague)` | `Automático` |
| `Manual 6 velocidades` | `Manual` |
| `DSG7` | `Automático` |
| `Tiptronic` | `Automático` |

Aplicar a: `cambioTxt` en el blade, etiquetas en PDFs, descripciones en ZIP.

## Donde vive esta regla

- `.claude/skills/importacion-vehiculos/SKILL.md` — el skill la menciona al generar
  informes (en el bloque "prohibido").
- `.ai/rules/business-model.md` — este archivo (regla de oro del copilot).
- `resources/views/public/car-dossier.blade.php` — aplicar siempre.
- `resources/views/jj-import/ficha-coche.blade.php` — aplicar siempre.
- Plantillas ZIP (`empaquetar.py`) — al redactar campos, NO usar las copy prohibidas.

## Si el usuario corrige algo prohibido en una review

Tomar nota mental: ya está documentado aquí. No replicar el error en siguientes
trabajos. Si el usuario lo repite, **mantener la regla y avisar** que está aquí
para no tener que repetir.
