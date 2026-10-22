# Auditoría de venta — Importnex como SaaS

**Ronda 1:** 22-oct-2026 (aislamiento, alta, marca) · **Ronda 2:** 22-oct-2026 (base de
datos, cobro, flujos, pantallas, RGPD, operación).

Objetivo: dejar el producto **vendible como SaaS** (cada empresa con su cuenta y su
suscripción) tocando solo lo necesario. Lo que no bloquea la venta queda como P2.

**P0** bloquea la venta (roto, inseguro, filtra datos o es un riesgo legal) · **P1** un
comprador o un cliente lo nota · **P2** mejora futura. Esfuerzo: S (< 2 h) · M (½–2 días) · L (> 2 días).

## Cómo se ha auditado (no solo tests)

| Qué | Cómo |
| --- | --- |
| Base de datos | Migraciones aplicadas en limpio y revisión tabla a tabla: `organization_id`, claves foráneas, borrados en cascada, índices, tipos de importe |
| Backend | Lectura de controladores, validación y autorización modelo a modelo; Cashier y webhooks de Stripe contra el código del paquete |
| N+1 | Suite de pantallas clave con `Model::preventLazyLoading()` activado: ninguna carga perezosa |
| Pantallas | Aplicación arrancada con datos de demo y recorrida con navegador (escritorio y móvil): portada, precios, registro, panel, inventario, planes, onboarding |
| Flujos | Alta → empresa → onboarding → plan; coche ↔ encargo; cobro y cancelación vía webhook |
| Tests | Suite completa archivo a archivo, leyendo el resultado de cada uno |

## Estado por módulo

| # | Módulo | Estado | Plan |
| --- | --- | --- | --- |
| 1 | Alta, cuentas, planes, cobro y equipo | 🟡 P0 resueltos; falta configurar Stripe | [01](01-alta-planes-equipo.md) |
| 2 | Aislamiento entre empresas, seguridad y RGPD | 🟡 P0 técnicos resueltos; faltan textos legales | [02](02-multiempresa-seguridad.md) |
| 3 | Marca propia de cada empresa | 🟡 | [03](03-marca-por-empresa.md) |
| 4 | Encargos, contrato y portal del cliente | 🟢 | [04](04-encargos-portal.md) |
| 5 | Coches, clientes y solicitudes | 🟢 | [05](05-coches-clientes-solicitudes.md) |
| 6 | Web pública, marketplace, dossier y PDF | 🔴 decisión de producto pendiente | [06](06-publico-marketplace-pdf.md) |
| 7 | Valoraciones, API y mercado | 🟢 | [07](07-valoraciones-api-mercado.md) |
| 8 | Repositorio, dependencias, instalación y despliegue | 🟡 | [08](08-repo-instalacion-despliegue.md) |
| 9 | Panel, alertas, KPIs, idioma y rendimiento | 🟡 | [09](09-panel-alertas-i18n-rendimiento.md) |

## Hallazgos P0

| ID | Módulo | Hallazgo | Evidencia | Estado |
| --- | --- | --- | --- | --- |
| P0-1 | 1 | Alta de empresa en bucle de redirecciones | `EnsureOrganization` | ✅ |
| P0-2 | 1 | Prueba caducada sin bloqueo | sin middleware | ✅ |
| P0-3 | 2 | Token de API global con `?org=` libre | `ImportToken` | ✅ |
| P0-4 | 2 | Importación del panel guardada en la empresa de JJ | `ValuationImportController` | ✅ |
| P0-5 | 2 | Estadísticas de Marketing de todas las empresas | `MarketingController` | ✅ |
| P0-6 | 3 | Marca, teléfonos y ciudad de JJ en ~40 ficheros | `grep` | ✅ |
| P0-7 | 2 | Contraseñas reales en `DatabaseSeeder` | seeder | ✅ (rotar en producción) |
| **P0-8** | 1 | **Cobro roto:** Cashier usaba `User` como cliente de Stripe, pero las suscripciones son de la organización. Cancelar, cambiar de plan y los webhooks fallaban, y la app consultaba la suscripción `default` cuando se crea como `main`, así que un cliente que paga figuraba como «sin suscripción» | `Subscription::owner()` devolvía `null` (test `BillingIntegrityTest`, falla sin el arreglo) | ✅ |
| **P0-9** | 1 | Los webhooks de Stripe no sincronizaban la suscripción local (se sobrescribían los manejadores de Cashier sin llamarlos): una cancelación desde Stripe no llegaba a la app | `StripeWebhookController` | ✅ |
| **P0-10** | 2 | Un usuario podía vincular un contacto a un **cliente de otra empresa** | `ContactController` `exists:clients,id` sin filtrar | ✅ |
| **P0-11** | 6 | Testimonios **inventados** en la portada del producto (práctica comercial desleal) | `Welcome.vue` | ✅ retirados |
| **P0-12** | 6 | Las empresas nuevas **no pueden publicar** ni usar su formulario público: `is_public` no se puede activar desde la app, y si se activa sus coches salen en el marketplace de JJ con el WhatsApp de JJ en la portada | `PublicCarRequestController`, `Car::scopePublicMarketplace` | ⏳ **decisión tuya** (ver abajo) |
| **P0-13** | 2 | Faltan los documentos legales del SaaS: condiciones del servicio, política de privacidad de la plataforma y **contrato de encargado del tratamiento** (art. 28 RGPD). Las empresas guardan DNI y datos de sus clientes en la plataforma | no existen | ⏳ abogado |

## Decisiones que necesito de ti

| # | Decisión | Opciones | Mi recomendación |
| --- | --- | --- | --- |
| D1 | ¿Qué es el marketplace público en el SaaS? (P0-12) | (a) cada empresa tiene su propia web de coches y su formulario; (b) un portal común con los coches de todas las empresas; (c) solo existe el de JJ y las demás empresas no tienen web pública | **(a)**: es lo que promete la portada («marketplace público por organización») y no mezcla clientes de unas empresas con otras. Esfuerzo M |
| D2 | Afirmaciones de la portada y del registro sin respaldo: «80 % menos tiempo», «3x rotación», «IA 24/7», «90 % de accuracy», «Soporte por chat», «Cumple GDPR» | quitarlas, o dejarlas solo si tienes datos que lo demuestren | Quitar las cifras y «chat»; mantener «Cumple RGPD» cuando estén los documentos de P0-13 |
| D3 | `spatie/laravel-permission`, `laravel/sanctum` y `maatwebsite/excel` están instalados y no se usan | desinstalar / dejar | Desinstalar (la regla del repo pide tu OK para tocar dependencias) |
| D4 | Material interno de JJ dentro del repositorio (estudios, memoria de IA, scripts) | moverlo a un repositorio privado / dejarlo | Moverlo antes de enseñar el código |
| D5 | ¿La IA la paga cada empresa con su propia clave o la plataforma? Hoy es «trae tu clave», pero los planes anuncian «créditos de IA» | trae tu clave / créditos incluidos | Trae tu clave y quitar «créditos» de los planes; es lo que funciona hoy |

## LISTO PARA VENDER

| Criterio | Estado |
| --- | --- |
| Alta de una empresa nueva de principio a fin | ✅ test + recorrido con navegador |
| Cobro: prueba, alta, cambio de plan, cancelación, webhooks | ✅ código; ⏳ configurar precios en Stripe y probar con tarjetas de prueba |
| Datos aislados entre empresas | ✅ revisado modelo a modelo |
| Sin datos de JJ en el producto | ✅ salvo el folleto PDF estático (P1) |
| Web pública de cada empresa | ⏳ D1 |
| Documentos legales del SaaS | ⏳ P0-13 |
| Instalación desde cero (`README.md`, `.env.example`, seeder) | ✅ |
| Tests en verde en Linux | ✅ 117 de 127 ficheros sin fallos (5 con tests omitidos a propósito porque necesitan Chrome). Los otros 10 no se pueden ejecutar en mi entorno (comandos de consola y envío de contraseñas): **pásalos en tu equipo** con `php artisan test --compact` |
| Build sin errores | ✅ |
| Monitorización de errores y copias de seguridad | ⏳ P1 (ver 08) |

## Orden de trabajo propuesto

| Paso | Qué | Esfuerzo | Depende de |
| --- | --- | --- | --- |
| 1 | Desplegar las ramas `feat/encargos-portal` y `audit/venta` (copia de BD + `migrate`) | S | tú |
| 2 | Rellenar Ajustes → Datos de empresa de JJ y rotar las contraseñas del seeder | S | tú |
| 3 | Web pública por empresa (D1) | M | D1 |
| 4 | Documentos legales + página «Condiciones» y aceptación en el alta | M | abogado |
| 5 | Stripe: precios, webhook y prueba de punta a punta con tarjetas de prueba | S | tú |
| 6 | Documentos del coche a disco privado; eliminar empresa y sus datos (RGPD) | M | — |
| 7 | Monitorización de errores, copias, limpieza de dependencias (D3) y del repo (D4) | S–M | D3, D4 |
| 8 | P1 de pantallas (idioma, plan del propietario, textos técnicos) | S | — |

## Lo que tienes que hacer tú

1. Rotar en producción las contraseñas de los dos usuarios que estaban en el seeder.
2. Copia de seguridad y `php artisan migrate` al desplegar.
3. Revisar Ajustes → Datos de empresa de JJ (CIF, dirección, IBAN, logo).
4. Configurar Stripe (`STRIPE_PRICE_*`, `STRIPE_WEBHOOK_SECRET`) y probar un alta con tarjeta de prueba.
5. Contestar D1–D5.
6. Encargar a un abogado los documentos de P0-13 y la revisión del contrato de encargo.
