# Importnex SaaS — Progreso de implementación

**Fecha:** 31 julio 2026
**Estado:** ✅ DESPLEGADO EN DEV - Sistema funcional en dev.aktive.cloud/importnexcore
**Plan vigente:** `PLAN_IMPLEMENTACION_COMPLETO.md`
**Deploy:** VPS dev.aktive.cloud con subpath /importnexcore

---

## ✅ Lo que ya funciona (no se toca)

- Multi-tenancy con global scopes
- Auth con Breeze + middleware `organization`
- CRUD completo: coches, clientes, contactos, mensajes, alertas
- Dashboard, Kanban, Mapa Leaflet, Finanzas, Planificador
- Suscripciones con Cashier
- Importación CSV/XLSX + importación JSON del chat
- Subida de fotos y documentos
- Código 100% en inglés
- **Tests: 195 ✅ passed · 0 risky · 0 failed (757 assertions)**
- **DEPLOY PRODUCCIÓN: ✅ https://dev.aktive.cloud/importnexcore**

---

## 🚀 Deployment dev.aktive.cloud (30/07/2026)

### Información de conexión

**Servidor:** VPS dev.aktive.cloud
**URL producción:** https://dev.aktive.cloud/importnexcore
**SSH:** `ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud`
**Directorio proyecto:** `/var/www/importnex-saas`
**Apache:** Subpath configurado en `/etc/apache2/sites-enabled/000-default-le-ssl.conf`

### Credenciales demo

**Usuario:** carra@admin.com
**Password:** demo1234
**Organización:** JJ Import Motors (ID: 1)
**Plan:** pro (trial hasta ~29/08/2026)

**Usuarios en BD:**
- ID 2: carra@admin.com | Carra | owner
- ID 3: jmepegounpeo@admin.com | Jmepegounpeo | owner

### Configuración servidor

**Tech stack:**
- PHP 8.3 + Composer 2.8.4
- MySQL 8.0.46
- Apache con PHP-FPM
- Redis Server
- Node.js 20 (para build)

**Configuración Apache:**
```apache
Alias /importnexcore /var/www/importnex-saas/public

<Directory /var/www/importnex-saas/public>
    Options +FollowSymLinks
    AllowOverride All
    Require all granted

    RewriteEngine On
    RewriteBase /importnexcore
    RewriteRule ^index\.php$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /importnexcore/index.php [L]
</Directory>
```

**Variables de entorno (.env):**
```
APP_URL=https://dev.aktive.cloud/importnexcore
APP_NAME=Importnex
APP_KEY=base64:TeBtlinZCiVikQxDfcel0eAkBMURmZpXu7br2v/yAoE=
DB_HOST=127.0.0.1
DB_DATABASE=importnex_saas
DB_USERNAME=importnex
DB_PASSWORD="Importnex2026Saas#"
```

### Comandos útiles

**Desplegar cambios:**
```bash
# Subir archivos modificados
scp -i "C:\Users\jacar\.ssh\id_ed25519_nopass" archivo.php root@dev.aktive.cloud:/var/www/importnex-saas/path/

# Limpiar cachés
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud "cd /var/www/importnex-saas; php artisan route:clear && php artisan config:clear && php artisan cache:clear"

# Rebuild assets
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud "cd /var/www/importnex-saas; npm run build"
```

**Verificar logs:**
```bash
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud "tail -f /var/www/importnex-saas/storage/logs/laravel.log"
```

**Acceso BD:**
```bash
ssh -i "C:\Users\jacar\.ssh\id_ed25519_nopass" root@dev.aktive.cloud "mysql -uimportnex -p'Importnex2026Saas#' importnex_saas"
```

### Correcciones aplicadas en producción

**Iconos de Leaflet (30/07):**
- Copiados a `public/build/assets/`:
  - marker-icon.png
  - marker-icon-2x.png
  - marker-shadow.png
- Configurados en [MapaLeaflet.vue](resources/js/Components/MapaLeaflet.vue) con prefijo `/importnexcore`

**Inconsistencia en estados de vehículos (30/07):**
- Creadas constantes en [Car.php](app/Models/Car.php):
  - `STATUSES` (11 estados completos)
  - `ACTIVE_STATUSES` (7 estados para KPIs)
  - `KANBAN_STATUSES` (8 estados para kanban)
- Actualizados:
  - [CarController.php](app/Http/Controllers/CarController.php) - Validaciones
  - [CarKanbanController.php](app/Http/Controllers/CarKanbanController.php) - Queries
  - [DashboardController.php](app/Http/Controllers/DashboardController.php) - KPIs
  - [CarFactory.php](database/factories/CarFactory.php) - Factory

### Verificación final

**Test completados:**
- ✅ Login funciona (carra@admin.com / demo1234)
- ✅ Dashboard carga correctamente
- ✅ Dropdown de estados muestra 11 opciones
- ✅ Kanban muestra 8 columnas correctas
- ✅ Iconos de Leaflet cargan (sin 404)
- ✅ Assets compilados en public/build/

**Accesos directos:**
- Dashboard: https://dev.aktive.cloud/importnexcore/dashboard
- Cars: https://dev.aktive.cloud/importnexcore/cars
- Kanban: https://dev.aktive.cloud/importnexcore/cars-kanban
- Map: https://dev.aktive.cloud/importnexcore/cars-map
- Clients: https://dev.aktive.cloud/importnexcore/clients

---

## 🆕 Auditoría de inconsistencias (29/07 + 30/07)

| # | Inconsistencia | Severidad | Solución |
|---|---|---|---|
| 1 | Migration `2026_07_25_000005` tenía un bloque no-op para `client_contact_logs` (la columna la añade otra migration) | BAJA | Limpiado el bloque + comentario explicando de dónde viene la columna |
| 2 | `CarChecklistFactory` no incluía los nuevos campos `kind`/`priority`/`section` ni `organization_id` | MEDIA | Factory actualizada con todos los campos y constantes del modelo |
| 3 | `CarDocumentFactory` no incluía `doc_key`/`status`/`group` ni claves del expediente | MEDIA | Factory actualizada con las 17 claves reales + grupos + estados |
| 4 | `CarController::store()` y `update()` no aceptaban los nuevos campos enriquecidos (`verdict`, `research`, `pros`, etc.) | **ALTA** | Añadidos al `$request->only([...])` en ambas acciones |
| 5 | `FILESYSTEM_DISK=s3` en `.env` pero sin paquete AWS instalado (workaround con `public` disk en tests) | BAJA | OK — config tiene `throw=false`, tests usan `Storage::fake()`. Documentado en `PROGRESO.md` |
| 6 | `CarVerificationController` no escribe en los nuevos campos enriquecidos | BAJA | **Fuera de alcance** — el plan dijo que la IA de la app se queda como está. Solo se mantienen los 5 campos legacy (`traffic_light`, `valuation`, `recommendation`, `red_flags`, `tips`) |

### Test nuevo para validar #4
- `CarCrudTest::test_can_update_enriched_valuation_fields` — verifica que el form puede enviar y guardar `verdict`, `verdict_confidence`, `verdict_reasoning`, `market_avg/min/max`, `estimated_saving`, `pros`, `cons`.

---

## 📋 Estado del plan nuevo

| Fase | Estado |
|---|---|
| F1 — Esquema valoración | ✅ |
| F2 — Semáforo automático | ✅ |
| F3 — Checklist con listas fijas | ✅ |
| F4 — Documentos como expediente | ✅ |
| F5 — Puente con el chat | ✅ |
| F6 — Vistas | ✅ |
| F7 — Limpieza PROGRESO | ✅ |
| F8 — Deployment DEV | ✅ 30/07/2026 |
| F9 — Correcciones iconos Leaflet | ✅ 30/07/2026 |
| F10 — Corrección estados vehículos | ✅ 30/07/2026 |
| F11 — Puente automático chat→servidor (API token) | ⏳ Código listo en local; falta desplegar al servidor y fijar `IMPORTNEX_CHAT_IMPORT_TOKEN` en el `.env` real (30/07/2026) |
| F12 — IA multi-proveedor por organización | ✅ 31/07/2026 |
| F13 — Widget chat IA flotante | ✅ 31/07/2026 |

---

## 📝 Notas operativas

- **IEDMT:** siempre se muestra como estimación. El cálculo ahora aplica % oficial por tramos de CO2.
- **Multi-tenancy:** todos los queries pasan por modelos con global scope.
- **Cashier:** `Organization` es `Billable`.
- **Importar informes:** JSON en `storage/app/importnex/import/` + `php artisan importnex:import-valuation --org="JJ Import Motors"`. El importer traduce ES → EN automáticamente.
- **Subpath routing:** Middleware `ForceRootUrl` fuerza todos los links con `/importnexcore` prefix.
- **Dotenv issue:** El servidor requiere `.env` sin `safeLoad()` directo - configuración bootstrap/app.php ajustada.

---

## 🔗 Referencias

- [LAUNCH.md](LAUNCH.md) - Checklist producción completa
- [inconsistencias-corregidas.md](../memories/repo/inconsistencias-corregidas.md) - Auditoría 26/07
- [correcciones-aplicadas-2026-07-30.md](../memories/repo/correcciones-aplicadas-2026-07-30.md) - Auditoría 30/07
- [inconsistencias-encontradas-2026-07-30.md](../memories/repo/inconsistencias-encontradas-2026-07-30.md) - Detalle técnico
- [PLAN_IMPLEMENTACION_COMPLETO.md](PLAN_IMPLEMENTACION_COMPLETO.md) - Plan original

---

**Última actualización:** 30 julio 2026 — Deployment DEV completado + seeders activados. Sistema listo para pruebas con datos demo.

**Datos demo creados:**
- 6 coches (BMW, Audi, Mercedes, VW, Porsche, Tesla)
- 32 clientes
- 12 contactos
- 16 contact logs
- 4 usuarios de demo + 2 usuarios específicos (carra@jjimportmotors.com, jmepegounpeo@jjimportmotors.com)
