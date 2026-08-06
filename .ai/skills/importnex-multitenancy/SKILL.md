---
name: importnex-multitenancy
description: Patrón multi-tenant por organization_id en ImportnexCore. Aplica cuando se habla de tenant, organización, middleware organization, scoping de queries, factories multi-tenant, tests con organización, aislamiento de datos entre tenants, subdominios por organización, suscripciones por organización, billing por organización, roles por organización, permisos por organización, Bouncer, Gate::define con tenant, cache por organización.
---

# Multi-tenancy ImportnexCore

## Principio fundamental

**Todos los datos de negocio están aislados por `organization_id`.** No existen datos cross-tenant. Un usuario pertenece a una organización y solo ve/edita datos de esa organización.

## Reglas inquebrantables

1. **Toda tabla de negocio lleva `organization_id` (FK a `organizations.id`, soft-delete aware)**.
2. **Toda query Eloquent dentro de controladores autenticados debe filtrar por `organization_id`** del usuario actual.
3. **Toda validación `exists:` debe restringirse a la organización** (`Rule::exists` con closure).
4. **Las factories y seeders crean datos con `organization_id`** (usar `Organization::factory()`).
5. **Los tests multi-tenant crean mínimo 2 organizaciones** y verifican aislamiento.

## Stack

| Componente | Archivo |
|---|---|
| Middleware validar acceso | `app/Http/Middleware/EnsureOrganization.php` (alias `organization`) |
| Middleware requerir org activa | `app/Http/Middleware/EnsureHasOrganization.php` (alias `has.organization`) |
| Modelo organización | `app/Models/Organization.php` (trait `HasSubscriptions`, relación con User) |
| User → Organization | `app/Models/User.php` → `belongsTo(Organization::class)` |
| Roles y permisos | Spatie `spatie/laravel-permission` v8, scoped por organization |

## Patrón de query scope

```php
// Eloquent global scope manual (NO usar GlobalScope, usamos where explícito)
Car::query()
    ->where('organization_id', auth()->user()->organization_id)
    ->latest()
    ->paginate();
```

## Patrón de validación

```php
'email' => [
    'required', 'email',
    Rule::exists('users', 'email')->where('organization_id', $orgId),
],
```

## Patrón de test multi-tenant

```php
use RefreshDatabase;

it('no permite ver coches de otra organización', function () {
    $orgA = Organization::factory()->create();
    $orgB = Organization::factory()->create();
    $userA = User::factory()->for($orgA)->create();
    $carB = Car::factory()->for($orgB)->create();

    $response = $this->actingAs($userA)->get("/cars/{$carB->id}");

    $response->assertForbidden();
});
```

## Anti-patrones (NUNCA)

- ❌ `Car::all()` en producción (siempre con where organization_id).
- ❌ `auth()->user()->isAdmin()` sin verificar también que el recurso pertenece a su org.
- ❌ Cachear queries sin tag por organización (`Cache::tags([$orgId])`).
- ❌ Jobs/queues sin `organization_id` en el payload (riesgo de leak cross-tenant).
- ❌ `Sharing data between organizations` aunque sea para reporting.

## Archivos críticos a leer antes de tocar multi-tenancy

- `app/Http/Middleware/EnsureOrganization.php`
- `app/Models/Organization.php`
- `database/migrations/*create_organizations_table*`
- `database/factories/OrganizationFactory.php`
- `app/Models/User.php` (relación con Organization)
- `tests/Feature/MultiTenancy/*` (si existe)

## Convenciones del proyecto

- FK siempre `organization_id`, no `org_id` ni `tenant_id`.
- SoftDeletes en todas las tablas de negocio.
- Subscription se filtra por organización + usuario.
- Permisos Spatie se cachean por organización.