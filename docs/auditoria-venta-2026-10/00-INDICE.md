# Auditoría de venta — Importnex como SaaS

**Ronda 1:** 22-oct-2026 (aislamiento, alta, marca) · **Ronda 2:** 22-oct-2026 (base de
datos, cobro, flujos, pantallas, RGPD, operación) · **Ronda 3:** 22-oct-2026 (decisiones
D1–D5: web por empresa, textos honestos, dependencias, limpieza del repo, IA).

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
| 6 | Web pública, marketplace, dossier y PDF | 🟢 web por empresa en `/web/{slug}` | [06](06-publico-marketplace-pdf.md) |
| 7 | Valoraciones, API y mercado | 🟢 | [07](07-valoraciones-api-mercado.md) |
| 8 | Repositorio, dependencias, instalación y despliegue | 🔴 el repositorio de GitHub es público (8.14) | [08](08-repo-instalacion-despliegue.md) |
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
| **P0-12** | 6 | Las empresas nuevas **no podían publicar** ni usar su formulario público, y si se activaba sus coches salían en el marketplace de JJ con el WhatsApp de JJ | `PublicCarRequestController`, `Car::scopePublicMarketplace` | ✅ web por empresa (`CompanySiteTest`) |
| **P0-14** | 8 | **El repositorio `JACarrasco7/importnex-saas` es público en GitHub**: el código del SaaS, el historial con las contraseñas que estaban en el seeder, la IP del servidor y el material interno de JJ están a la vista de cualquiera | github.com («Public») | ⏳ **tú**: pasarlo a privado |
| **P0-15** | 6 | Con webs por empresa, la escala de honorarios de JJ (1.000 € / 1.500 €) salía en el desglose y en «Transparencia» de todas las empresas | `CostBreakdown` | ✅ honorarios por empresa en Ajustes |
| **P0-13** | 2 | Faltan los documentos legales del SaaS: condiciones del servicio, política de privacidad de la plataforma y **contrato de encargado del tratamiento** (art. 28 RGPD). Las empresas guardan DNI y datos de sus clientes en la plataforma | no existen | ⏳ abogado |

## Decisiones tomadas (22-oct-2026)

| # | Decisión | Qué se ha hecho |
| --- | --- | --- |
| D1 | Una web por empresa | `/web/{slug}` con catálogo, fichas, comparador y formulario de cada empresa, con su marca, logo, WhatsApp, honorarios y aviso legal. Se activa en Ajustes → Web pública (con dirección editable). Solo se ve si la cuenta está al día. `/marketplace` sigue siendo la web de la operadora y redirige las fichas de otras empresas a su web |
| D2 | Quitar lo que no se puede demostrar | Fuera cifras, testimonios, «chat», SLA, account manager, «ilimitados», FAQ falsas y la comparativa inventada. Portada, precios y facturación leen los planes de `config/subscription.php` |
| D3 | Desinstalar librerías sin uso | `spatie/laravel-permission`, `laravel/sanctum`, `maatwebsite/excel` fuera (−10 paquetes del lock, ninguna otra versión cambia) |
| D4 | Limpiar el repo | Tanda 1 hecha (material de JJ y borradores, ver 08 · 8.4). Tanda 2 pendiente: herramientas de tu flujo con IA |
| D5 | IA con la clave de cada empresa | Sin «créditos de IA». Ajustes recomienda Claude; los planes incluyen la skill para Claude. Todas las funciones en todos los planes; cambian los límites y Enterprise oculta la marca de la plataforma |

## LISTO PARA VENDER

| Criterio | Estado |
| --- | --- |
| Alta de una empresa nueva de principio a fin | ✅ test + recorrido con navegador |
| Cobro: prueba, alta, cambio de plan, cancelación, webhooks | ✅ código; ⏳ configurar precios en Stripe y probar con tarjetas de prueba |
| Datos aislados entre empresas | ✅ revisado modelo a modelo |
| Sin datos de JJ en el producto | ✅ salvo el folleto PDF estático (solo en la web de la operadora) y la skill, que sigue escrita para JJ (8.15) |
| Web pública de cada empresa | ✅ |
| Portada y precios sin afirmaciones sin respaldo | ✅ `HonestMarketingTest` |
| Código privado | ⏳ P0-14 |
| Documentos legales del SaaS | ⏳ P0-13 |
| Instalación desde cero (`README.md`, `.env.example`, seeder) | ✅ |
| Tests en verde en Linux | ✅ 118 de 129 ficheros sin fallos (5 con tests omitidos a propósito porque necesitan Chrome). 10 no se pueden ejecutar en mi entorno (comandos de consola y envío de contraseñas) y 1 lee un fichero de `.claude/` que no está en git: **pásalos en tu equipo** con `php artisan test --compact` |
| Build sin errores | ✅ |
| Monitorización de errores y copias de seguridad | ⏳ P1 (ver 08) |

## Orden de trabajo propuesto

| Paso | Qué | Esfuerzo | Depende de |
| --- | --- | --- | --- |
| 1 | Poner el repositorio de GitHub en privado (P0-14) | S | tú |
| 2 | Desplegar las ramas `feat/encargos-portal` y `audit/venta` (copia de BD, `composer install`, `migrate`, `npm run build`) | S | tú |
| 3 | Rellenar Ajustes → Datos de empresa de JJ (incluidos honorarios) y rotar las contraseñas del seeder | S | tú |
| 4 | Documentos legales + página «Condiciones» y aceptación en el alta | M | abogado |
| 5 | Stripe: precios, webhook y prueba de punta a punta con tarjetas de prueba | S | tú |
| 6 | Documentos del coche a disco privado; eliminar empresa y sus datos (RGPD) | M | — |
| 7 | Skill genérica para compradores (8.15) y tanda 2 de limpieza del repo | M | — |
| 8 | Monitorización de errores y copias de seguridad probadas | S | elegir servicio |

## Lo que tienes que hacer tú

1. **Pasar el repositorio de GitHub a privado** (Settings → General → Danger zone → Change visibility).
2. Rotar en producción las contraseñas de los dos usuarios que estaban en el seeder (siguen en el historial público hasta el paso 1).
3. Copia de seguridad, `composer install` (quita las 3 librerías), `php artisan migrate` y `npm run build` al desplegar.
4. Revisar Ajustes → Datos de empresa de JJ (CIF, dirección, IBAN, logo, honorarios) y Ajustes → Web pública.
5. Configurar Stripe (`STRIPE_PRICE_*`, `STRIPE_WEBHOOK_SECRET`) y probar un alta con tarjeta de prueba.
6. Encargar a un abogado los documentos de P0-13 y la revisión del contrato de encargo.
