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
| `resources/js/i18n/**` | i18n sync es/nen antes de cambios | [i18n-sync.md](i18n-sync.md) |
