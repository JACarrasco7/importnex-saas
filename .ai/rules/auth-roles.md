# Auth & Roles — convenciones de ImportnexCore

> **Decidido 22-oct-2026 (auditoría de venta, D3):** `spatie/laravel-permission` se ha **desinstalado** (junto con `laravel/sanctum` y `maatwebsite/excel`, que tampoco se usaban). El sistema real usa la columna `users.role` con magic strings: `'owner'`, `'operator'`, `'Super Admin'` (ver `app/Models/User.php`, `app/Http/Middleware/TelescopeAccess.php`). Si algún día hacen falta permisos finos, ver `docs/planes/PLAN_SPATIE_PERMISOS.md` y pedir aprobación para instalarlo.

## Estado actual (medido 13-sep-2026)

- **NO implementar `Role::assignRole()` pensando que funciona**: NO está conectado. Verificado: **no existe** `config/permission.php`, **no hay** migraciones de Spatie y **no existen** las tablas `roles`, `permissions`, `model_has_roles`, `role_has_permissions`. No hay nada a medias que funcione por accidente.
- Para auth real usar: `auth()->user()->role === 'owner'`, o `isOwner()` / `isOperator()` / `scopeOwner()` / `scopeOperator()` en `User`.
- **TelescopeAccess middleware** (`app/Http/Middleware/TelescopeAccess.php`): checkea `if ($user->role !== 'Super Admin')`.
- ⚠️ **`$user->isSuperAdmin()` NO EXISTE** (esta regla lo citaba por error hasta el 13-sep-2026; `grep` no lo encuentra en `app/`). El valor `'Super Admin'` sí se usa, pero como string suelto.
- ⚠️ **Cuidado con los DOS `isOwner()`**: `User::isOwner()` es rol (`role === 'owner'`), pero `Organization::isOwner()` lee la **columna** `organizations.is_owner` ("esta organización ES la plataforma") y **NO es un rol**. Se llama en ~12 sitios (billing, límites de plan, alertas). **NO migrar esos a `hasRole()`.**

## Decisión tomada (22-oct-2026)

Opción (b) del plan [`docs/planes/PLAN_SPATIE_PERMISOS.md`](../../docs/planes/PLAN_SPATIE_PERMISOS.md): dependencia desinstalada. **No usar la API de Spatie**: usar los magic strings de `users.role`.
