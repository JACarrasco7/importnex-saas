# Multitenancy Rules — organization_id

> Activar por glob: TODA tabla de negocio (`database/migrations/**`), controlador autenticado (`app/Http/Controllers/**/*.php`).

---

## Regla inquebrantable

**Toda tabla de negocio lleva `organization_id` como FK a `organizations.id`.** Un usuario solo ve/edita datos de SU organización.

## Esquema obligatorio

```php
Schema::create('cars', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')
        ->constrained()
        ->cascadeOnDelete();
    // campos de negocio...
    $table->softDeletes();
    $table->timestamps();
    $table->index('organization_id'); // redundante con FK index, pero OK
});
```

## Scoping en queries

```php
// BIEN
$cars = Car::query()
    ->where('organization_id', auth()->user()->organization_id)
    ->latest()
    ->paginate();

// MAL (rompe tenancy)
$cars = Car::all();
$cars = Car::query()->get(); // sin where
```

## Global scopes: NO usar en multi-tenant

**Lección aprendida (2026-08-07):** NO añadir `static::addGlobalScope('organization', ...)` en modelos. Causas:
- En queue workers / jobs / commands `auth()->user()` es null → leak silencioso de TODAS las orgs.
- Dificulta testing y scopes explícitos.

**Patrón actual**: cada controller/observer valida explícitamente con `where('organization_id', $org->id)` o helpers privados como `AlertController::authorizeAlertAccess()`.

## Validación con scope

```php
'client_id' => [
    'required',
    Rule::exists('clients', 'id')->where('organization_id', $orgId),
],
```

## Factories

```php
// BIEN
$car = Car::factory()->for($org)->create();

// MAL (sin org)
$car = Car::factory()->create();
```

## Tests multi-tenant

```php
public function test_user_cannot_see_other_orgs_cars(): void
{
    [$orgA, $orgB] = Organization::factory()->count(2)->create();
    $userA = User::factory()->for($orgA)->create();
    $carB = Car::factory()->for($orgB)->create();

    $this->actingAs($userA)
        ->get("/cars/{$carB->id}")
        ->assertForbidden();
}
```

## Middleware stack

- `auth` → `organization` (valida acceso) → `has.organization` (org activa) → `plan.limit` (límites).

## Jobs/Queues

- **Payload SIEMPRE con `organization_id`** (para que el job worker pueda re-scoped).
- **NO** cachear cross-tenant sin tags.

```php
public function handle(): void
{
    $org = Organization::findOrFail($this->organizationId);
    // trabajar scoped a $org...
}
```

## Cache

```php
Cache::tags("org:{$orgId}")->remember(...);
```

## NO HACER

- ❌ `Model::all()` en producción.
- ❌ Query sin `where organization_id`.
- ❌ `auth()->user()->isAdmin` como única authz check (verificar resource ownership).
- ❌ Cache compartido entre organizaciones sin tags.
- ❌ Job sin `organization_id` en payload.
- ❌ Test de modelo de negocio sin 2+ organizaciones.
- ❌ Migración de tabla nueva sin `organization_id`.
