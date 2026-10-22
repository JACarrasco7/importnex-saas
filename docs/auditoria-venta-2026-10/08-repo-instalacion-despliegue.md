# 08 · Repositorio, dependencias, instalación y despliegue

**Estado:** 🟡

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
| 8.4 | P1 | Material interno de JJ en el repo (`untitled/`, `JJ Import Motors/`, `docs/informes`, `docs/memoria-desktop`, `docs/claude*`, `.ai/memory`, `subir-informe.*`, `forge-mysql-tunnel.bat`) | `git ls-files` | ⏳ D4 |
| 8.5 | P1 | Tres formas de desplegar (Forge, Docker, Railway) | `railway.json`, `Dockerfile` | ⏳ Recomendado: Forge; quitar Railway |
| 8.12 | P2 | Dependencias instaladas sin usar: `spatie/laravel-permission`, `laravel/sanctum`, `maatwebsite/excel` (solo `app/Imports/CarsImport.php`, que nadie llama) | `grep` | ⏳ D3 |
| 8.13 | P2 | Código muerto: `TestimonialController` vacío, tabla `testimonials` sin modelo, columnas de Cashier en `users` (`stripe_id`, `pm_type`…) de cuando el cliente era el usuario | `grep`, esquema | Borrar controlador (S); tabla y columnas con migración y tu OK |
| 8.6 | P2 | 25 auditorías y planes antiguos en `docs/` | — | Archivar con 8.4 |

## Despliegue mínimo en Forge

| Paso | Comando / ajuste |
| --- | --- |
| Antes | Copia de seguridad de la base de datos |
| Código | `git pull` · `composer install --no-dev -o` · `npm ci && npm run build` |
| Base de datos | `php artisan migrate --force` |
| Cachés | `php artisan optimize` |
| Procesos | Scheduler (`schedule:run` cada minuto) y, si `QUEUE_CONNECTION=database`, un worker |
| Comprobar | `/health`, `/login`, `/register`, `/marketplace`, `/pricing` |
