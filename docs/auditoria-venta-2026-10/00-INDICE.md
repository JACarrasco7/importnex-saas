# Auditoría de venta — Importnex como SaaS (22-oct-2026)

Objetivo: dejar el producto **vendible como SaaS** (cada empresa con su cuenta y
su suscripción) tocando solo lo necesario. Lo que no bloquea la venta queda como
P2 en cada plan.

Prioridades: **P0** bloquea la venta (roto, inseguro o filtra datos entre
empresas) · **P1** un comprador lo nota · **P2** mejora futura.

## Estado por módulo

| # | Módulo | Estado | Plan |
| --- | --- | --- | --- |
| 1 | Alta, cuentas, planes y equipo | 🟢 P0 resueltos | [01](01-alta-planes-equipo.md) |
| 2 | Aislamiento entre empresas y seguridad | 🟢 P0 resueltos | [02](02-multiempresa-seguridad.md) |
| 3 | Marca propia de cada empresa | 🟡 P0 resueltos, quedan P1 | [03](03-marca-por-empresa.md) |
| 4 | Encargos, contrato y portal del cliente | 🟢 | [04](04-encargos-portal.md) |
| 5 | Coches, clientes y solicitudes | 🟢 | [05](05-coches-clientes-solicitudes.md) |
| 6 | Web pública, marketplace, dossier y PDF | 🟡 | [06](06-publico-marketplace-pdf.md) |
| 7 | Valoraciones, API y mercado | 🟢 P0 resueltos | [07](07-valoraciones-api-mercado.md) |
| 8 | Repositorio, instalación y despliegue | 🟡 necesita decisión | [08](08-repo-instalacion-despliegue.md) |

## Hallazgos P0 (todos resueltos en la rama `audit/venta`)

| ID | Hallazgo | Evidencia | Arreglo |
| --- | --- | --- | --- |
| P0-1 | Un cliente nuevo se registraba y entraba en un bucle de redirecciones: sin empresa → `/onboarding` → `/onboarding`… La ruta para crear la empresa no existía | `EnsureOrganization` redirigía a una ruta protegida por él mismo | El alta pide el nombre de la empresa y la crea con prueba gratuita; `/organization/create` para cuentas sin empresa |
| P0-2 | Al acabar la prueba gratis no se bloqueaba nada: se podía usar sin pagar | No existía ningún middleware de suscripción | `EnsureActiveSubscription`: sin plan activo solo deja ver planes, facturación y perfil |
| P0-3 | El token de la API era único y global; con `?org=` se leía o escribía en cualquier empresa | `ImportToken` resolvía la organización por nombre | Token propio por empresa (guardado con hash, solo ve su empresa); el global queda para uso interno |
| P0-4 | Importar un paquete de valoración desde el panel lo guardaba siempre en «JJ Import Motors», fuera cual fuera la empresa del usuario | `ValuationImportController` | Se importa en la empresa del usuario |
| P0-5 | Estadísticas de Marketing contaban contenidos de todas las empresas | `MarketingController` | Filtradas por la empresa del usuario |
| P0-6 | «JJ Import Motors», sus teléfonos, email y «Huelva» estaban escritos en ~40 ficheros: contrato, emails, portal, web pública, dossier, PDF, aviso legal | `grep` | Perfil de empresa por organización (`App\Support\Brand`) usado en todo lo que ve el cliente final |
| P0-7 | Contraseñas reales de dos usuarios en `DatabaseSeeder` (en el historial de git) | `database/seeders/DatabaseSeeder.php` | Seeder sin credenciales (lee `SEED_ADMIN_*`). **Cambiar esas contraseñas en producción** |

## LISTO PARA VENDER — checklist

| Criterio | Estado |
| --- | --- |
| Alta de una empresa nueva de principio a fin | ✅ (test) |
| Cobro: la prueba caduca y obliga a elegir plan | ✅ (test) — falta configurar precios en Stripe |
| Datos aislados entre empresas (panel y API) | ✅ revisado modelo a modelo |
| Sin datos de JJ en el producto (marca, contrato, emails, web) | ✅ salvo P1 del módulo 3 |
| Cada empresa configura su marca, CIF, IBAN y logo | ✅ |
| Equipo: invitar y quitar usuarios con límite por plan | ✅ |
| Instalación desde cero documentada (`README.md`) y `.env.example` completo | ✅ |
| Seeder sin credenciales; demo con `DemoDataSeeder` | ✅ |
| Tests en verde | ✅ en el sandbox, salvo los que usan el broker de contraseñas (fallo del entorno, no del código): pasar en local |
| Build sin errores | ✅ |
| Aviso legal / RGPD por empresa | ✅ `/legal?org=<slug>`; revisar textos con abogado |
| Repositorio limpio de material interno de JJ | ⏳ decisión pendiente (módulo 8) |
| Backups y despliegue documentados | ✅ README; backups los gestiona Forge |

## Lo que tienes que hacer tú

1. Cambiar en producción las contraseñas de los dos usuarios que estaban en el seeder.
2. Antes de desplegar: copia de seguridad de la base de datos y `php artisan migrate`.
3. Entrar en **Ajustes → Datos de empresa** de JJ y revisar CIF, dirección, IBAN y logo
   (la migración copia los que había; lo que falte sale como «pendiente» en el contrato).
4. Configurar precios en Stripe (`STRIPE_PRICE_*`) antes de abrir el registro al público.
5. Decidir qué hacer con el material interno de JJ en el repositorio (módulo 8).
6. Revisión legal de contrato y aviso legal genéricos.
