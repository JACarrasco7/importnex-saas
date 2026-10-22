# 08 · Repositorio, dependencias, instalación y despliegue

**Estado:** 🔴 repositorio público (8.14)

| ID | Prio | Hallazgo | Evidencia | Estado |
| --- | --- | --- | --- | --- |
| 8.1 | P1 | `README.md` era el de Laravel por defecto | — | ✅ |
| 8.2 | P1 | `.env.example` sin variables del SaaS | — | ✅ |
| 8.3 | P0 | Credenciales reales en el seeder | — | ✅ (rotar) |
| 8.7 | P1 | **69 tests fallaban en Linux** (servidor, CI): Inertia busca las páginas en `js/pages` y la carpeta es `js/Pages`. En Windows no se nota | `vendor/inertiajs/inertia-laravel/config/inertia.php` | ✅ `config/inertia.php` |
| 8.8 | P1 | El seeder de demo crea «JJ Import Motors» con `owner@jjimportmotors.com` / contraseña fija | `DemoDataSeeder` | Pendiente (S): empresa ficticia y contraseña por `.env` |
| 8.9 | P1 | Sin monitorización de errores en producción (Sentry, Flare, Bugsnag…) | `composer.json` | Pendiente (S) — elegir servicio |
| 8.10 | P1 | Copias de seguridad: dependen de Forge y no están documentadas ni probadas | — | Pendiente (S): activar backups diarios en Forge y probar una restauración |
| 8.11 | P1 | `.env.example` trae `APP_DEBUG=true` y `LOG_LEVEL=debug` (correcto en local; en producción debe ser `false` / `warning`) | `.env.example` | Documentado en README |
| **8.14** | **P0** | **El repositorio de GitHub `JACarrasco7/importnex-saas` es público**: cualquiera puede copiar el SaaS entero, y el historial guarda las contraseñas que estaban en el seeder, la IP del servidor (`forge-mysql-tunnel.bat`) y todo el material interno de JJ. Borrar ficheros no limpia el historial | github.com/JACarrasco7/importnex-saas («Public») | ⏳ **tú**: GitHub → Settings → General → Danger zone → Change visibility → Private |
| 8.4 | P1 | Material interno de JJ en el repo | `git ls-files` | 🟡 Tanda 1 ✅ (22-oct): `JJ Import Motors/`, `docs/informes/`, `docs/contexto-jj-import-motors/`, `docs/marketing/`, `untitled/`, `reports/`, `storage/log/`, `scripts/tmp-car-71.php`, `scripts/i18n-report.txt` → movidos a `Desktop\JJImportMotors\_sacado-del-repo-2026-10-22\` y en `.gitignore`. Tanda 2 ⏳: herramientas de tu flujo con IA (`.claude/`, `.ai/memory`, `docs/memoria-desktop`, `docs/claude-desktop`, `docs/ecommerce.md`, `sync.manifest.json`, `subir-informe.*`, `forge-mysql-tunnel.bat`, `scripts/sync-desktop.ps1`…) a un repo privado aparte, cuando cierres los cambios que tienes a medias en esos ficheros |
| 8.15 | P1 | La skill de importación se entrega a cada comprador, pero está escrita para JJ (honorarios 1.500 €, «Puesto en Huelva», marca y tono de JJ) | `.claude/skills/importacion-vehiculos` | Pendiente (M): versión genérica que lea los datos de la empresa |
| 8.5 | P1 | Tres formas de desplegar (Forge, Docker, Railway) | `railway.json`, `Dockerfile` | ⏳ Recomendado: Forge; quitar Railway |
| 8.12 | P2 | Dependencias instaladas sin usar: `spatie/laravel-permission`, `laravel/sanctum`, `maatwebsite/excel` (solo `app/Imports/CarsImport.php`, que nadie llama) | `grep` | ✅ desinstaladas (D3) y `CarsImport` borrado; el `composer.lock` pierde 10 paquetes y ningún otro cambia de versión |
| 8.13 | P2 | Código muerto: `TestimonialController` vacío, tabla `testimonials` sin modelo, columnas de Cashier en `users` (`stripe_id`, `pm_type`…) de cuando el cliente era el usuario | `grep`, esquema | Borrar controlador (S); tabla y columnas con migración y tu OK |
| 8.6 | P2 | 25 auditorías y planes antiguos en `docs/` | — | Archivar con la tanda 2 de 8.4 |

## Despliegue mínimo en Forge

| Paso | Comando / ajuste |
| --- | --- |
| Antes | Copia de seguridad de la base de datos |
| Código | `git pull` · `composer install --no-dev -o` (quita las 3 librerías desinstaladas) · `npm ci && npm run build` |
| Base de datos | `php artisan migrate --force` |
| Cachés | `php artisan optimize` |
| Procesos | Scheduler (`schedule:run` cada minuto) y, si `QUEUE_CONNECTION=database`, un worker |
| Comprobar | `/health`, `/login`, `/register`, `/marketplace`, `/pricing` |
