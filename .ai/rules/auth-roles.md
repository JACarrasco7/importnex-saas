# Auth & Roles — convenciones de ImportnexCore

> **Auditoría ronda 4 (sep-2026):** `spatie/laravel-permission` está declarado en `composer.json:19` pero **NO se usa en ningún sitio** del código. 0 usos de `Role::`, `assignRole()`, `hasRole()`, `givePermissionTo()`, ni `Gate::define`. El sistema real usa columna `users.role` con magic strings: `'owner'`, `'operator'`, `'Super Admin'` (ver `app/Models/User.php`, `app/Http/Middleware/TelescopeAccess.php`).

## Estado actual

- **NO implementar `Role::assignRole()` pensando que funciona**: NO está conectado. La tabla `roles` puede existir vacía o no existir según seeders.
- Para auth real usar:
  - `auth()->user()->role === 'owner'` o `isOwner()` / `isOperator()` en `User`.
  - `$user->isSuperAdmin()` para acceso total.
- **TelescopeAccess middleware** (`app/Http/Middleware/TelescopeAccess.php`): checkea `if ($user->role !== 'Super Admin')`.

## Decisión pendiente

- **(a) Implementar Spatie de verdad** (seeder, policies, replace `isOwner()` por `hasRole()`) — coste 2-3 días, riesgo alto de regresiones en permisos.
- **(b) Desinstalar la dependencia**: `composer remove spatie/laravel-permission` y borrar `config/permission.php` si existe. Limpia ~5MB del vendor.

> **Esta regla NO está aprobada por el usuario todavía.** Esperar decisión antes de actuar.
> Mientras tanto: **NO usar la API de Spatie** en código nuevo. Usar magic strings de `users.role`.
