---
glob: 'app/Http/Middleware/*,app/Models/**'
title: 'Multi-tenancy vía organization_id'
---

Todas las tablas de negocio tienen `organization_id`. Usar middleware `organization` / `has.organization` / `plan.limit` ya registrados en `bootstrap/app.php`. Validar tenant en queries Eloquent.
