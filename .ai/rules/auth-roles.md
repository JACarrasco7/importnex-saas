# Auth & Roles — convenciones de ImportnexCore

> **Auditoría ronda 4 (sep-2026):** `spatie/laravel-permission` está declarado en `composer.json:19` pero **NO se usa en ningún sitio** del código. 0 usos de `Role::`, `assignRole()`, `hasRole()`, `givePermissionTo()`, ni `Gate::define`. El sistema real usa columna `users.role` con magic strings: `'owner'`, `'operator'`, `'Super Admin'` (ver `app/Models/User.php`, `app/Http/Middleware/TelescopeAccess.php`).

## Estado actual (medido 13-sep-2026)

- **NO implementar `Role::assignRole()` pensando que funciona**: NO está conectado. Verificado: **no existe** `config/permission.php`, **no hay** migraciones de Spatie y **no existen** las tablas `roles`, `permissions`, `model_has_roles`, `role_has_permissions`. No hay nada a medias que funcione por accidente.
- Para auth real usar: `auth()->user()->role === 'owner'`, o `isOwner()` / `isOperator()` / `scopeOwner()` / `scopeOperator()` en `User`.
- **TelescopeAccess middleware** (`app/Http/Middleware/TelescopeAccess.php`): checkea `if ($user->role !== 'Super Admin')`.
- ⚠️ **`$user->isSuperAdmin()` NO EXISTE** (esta regla lo citaba por error hasta el 13-sep-2026; `grep` no lo encuentra en `app/`). El valor `'Super Admin'` sí se usa, pero como string suelto.
- ⚠️ **Cuidado con los DOS `isOwner()`**: `User::isOwner()` es rol (`role === 'owner'`), pero `Organization::isOwner()` lee la **columna** `organizations.is_owner` ("esta organización ES la plataforma") y **NO es un rol**. Se llama en ~12 sitios (billing, límites de plan, alertas). **NO migrar esos a `hasRole()`.**

## Decisión pendiente

**Plan completo y detallado: [`docs/planes/PLAN_SPATIE_PERMISOS.md`](../../docs/planes/PLAN_SPATIE_PERMISOS.md)**

- **(a) Implementar Spatie de verdad** (config + migraciones + seeder de migración de datos + wrappers de compatibilidad) — coste 2-3 días, riesgo alto de regresiones en permisos.
- **(b) Desinstalar la dependencia**: `composer remove spatie/laravel-permission`. No hay nada que limpiar. Riesgo casi nulo. **Recomendada** mientras no haya necesidad concreta.

> **Esta regla NO está aprobada por el usuario todavía.** Decisión **APARCADA** el 13-sep-2026 ("déjalo para más adelante"). Esperar decisión antes de actuar.
> Mientras tanto: **NO usar la API de Spatie** en código nuevo. Usar los magic strings de `users.role`.
