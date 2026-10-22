# 04 · Encargos, contrato y portal del cliente

**Estado:** 🟢 · Tests: `tests/Feature/ImportOrders/*` (41)

Módulo nuevo (rama `feat/encargos-portal`): encargo con 13 estados y reglas de
transición, contrato firmado con código de 6 dígitos por email, pagos manuales
(transferencia, efectivo o enlace de Stripe), aprobaciones en un clic, mensajes,
avisos por email y portal único `/mi-encargo/{token}`.

| ID | Prio | Hallazgo | Estado |
| --- | --- | --- | --- |
| 4.1 | P0 | Contacto, IBAN y CIF del contrato y del portal eran los de JJ | ✅ De la empresa (módulo 03) |
| 4.2 | P1 | Referencias con prefijo fijo `JJIM-` | ✅ `ORDERS_REFERENCE_PREFIX` (por defecto `ENC`) |
| 4.3 | P1 | El aviso del panel pedía configurar el CIF en `.env` | ✅ Enlaza a Ajustes → Datos de empresa |
| 4.4 | P2 | Cobro automático por Stripe (webhook) de los pagos del encargo | Aparcado: el webhook actual es el de las suscripciones y usa otra cuenta |
| 4.5 | P2 | WhatsApp por API y tiempo real (Reverb) | Aparcado: hoy botones `wa.me` y refresco cada 30 s |
