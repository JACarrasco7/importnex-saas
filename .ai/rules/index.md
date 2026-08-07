<!-- filepath: .ai/rules/index.md -->
# Reglas durables del proyecto

> **Convención:** convenciones, decisiones, trampas y restricciones del proyecto.
> Cada regla vive en su propio archivo con frontmatter `glob` + `title` + `note`.
> Antes de editar código: leer las reglas cuyos globs aplican al path y `grep -rin 'keyword' .ai/rules`.

## Cómo añadir una regla nueva

Usa `php artisan boost:record-rule --glob='app/Models/**' --title='...' --note='...'` o crea el archivo a mano en `.ai/rules/` siguiendo el formato:

```markdown
---
glob: 'app/Models/**'
title: 'Breve título descriptivo'
---

Una o dos líneas explicando la regla. Sin ensayos.
```

## Índice de reglas

| Globs | Título | Archivo |
|---|---|---|
| `database/migrations/**` | Backup antes de migraciones destructivas | [db-backup.md](db-backup.md) |
| `app/Models/Task.php,database/migrations/**tasks*` | `old_task=1` requiere confirmación | [old-task-confirm.md](old-task-confirm.md) |
| `**/*.{php,vue,js}` | No instalar dependencias sin aprobar | [no-deps-without-approval.md](no-deps-without-approval.md) |
| `resources/js/**,resources/views/**` | Paleta JJ Import Motors obligatoria | [brand-jj-import-motors.md](brand-jj-import-motors.md) |
| `package.json,vite.config.js` | Usuario lanza `npm run build` | [no-frontend-build.md](no-frontend-build.md) |
| `app/Http/Middleware/*` | Multi-tenancy vía `organization_id` | [multitenancy-org.md](multitenancy-org.md) |
| `app/Http/Controllers/**,app/Services/**` | Backend Laravel 13 / PHP 8.5 | [backend.md](backend.md) |
| `resources/js/**,resources/css/**` | Frontend Vue 3 + Tailwind v4 | [frontend.md](frontend.md) |
| `app/Services/Billing/**,config/subscription.php` | Cashier + Stripe webhooks idempotentes | [billing.md](billing.md) |
| `database/migrations/**` | Multi-tenancy isolation | [multitenancy.md](multitenancy.md) |
| `resources/lang/**,resources/js/i18n/**` | Paridad es/en i18n | [i18n.md](i18n.md) |
| `routes/web.php,scripts/**` | Deploy Forge seguro | [deployment.md](deployment.md) |
| `resources/css/**,resources/js/Components/**` | Design system JJ Import Motors | [design-system.md](design-system.md) |
| `tests/Feature/**,tests/Unit/**` | PHPUnit 13 + SQLite :memory: | [testing.md](testing.md) |
| `database/migrations/**,database/factories/**` | Migraciones + factories seguras | [migrations.md](migrations.md) |
| `**/*.{php,vue,js,md}` | Search hygiene: no bucles de búsqueda | [search-hygiene.md](search-hygiene.md) |

## 📊 Reglas Sprint G (sistema IA)

8 reglas scoped creadas para activar automáticamente por glob. Cada una se carga solo cuando Boost toca un archivo del path correspondiente. **Ahorro estimado: 60-70% tokens por turno** vs leer todo.
| `resources/js/i18n/**` | i18n sync es/nen antes de cambios | [i18n-sync.md](i18n-sync.md) |
