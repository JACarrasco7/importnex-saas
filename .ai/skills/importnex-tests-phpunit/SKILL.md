---
name: importnex-tests-phpunit
description: Testing PHPUnit 13 con SQLite in-memory. Aplica cuando se habla de test, phpunit, test Feature, test Unit, RefreshDatabase, factory, actingAs, multi-tenant test, mock, spy, Mockery, Pest vs PHPUnit, coverage, test lento, test flaky, seeders en test, sqlite in-memory, faker, factory state, assertDatabaseHas, assertDatabaseMissing.
---

# Testing ImportnexCore (PHPUnit 13)

## Stack

- `phpunit/phpunit` v13.2.
- `mockery/mockery` v1.6 para mocks/stubs.
- `nunomaduro/collision` v8.9 (CLI errors prettifier).
- SQLite `:memory:` en testing (`phpunit.xml` env `DB_CONNECTION=sqlite`).
- **NO Pest** (decisión proyecto: PHPUnit puro).

## Configuración crítica (`phpunit.xml`)

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="MAIL_MAILER" value="array"/>
    <env name="STRIPE_SECRET" value="sk_test_fake"/>
    <env name="MISTRAL_API_KEY" value="test-key"/>
</php>
```

## Reglas inquebrantables

1. **Todo test que toca BD usa `RefreshDatabase`** (trait) o `DatabaseTransactions`.
2. **Tests multi-tenant crean mínimo 2 organizaciones** para verificar aislamiento.
3. **Factories con `for($org)` o `forOrganization($org)`** — nunca `$model::factory()->create()` sin org.
4. **`actingAs($user)` en cada test Feature** que requiera autenticación.
5. **HTTP tests con `assertStatus()`, `assertRedirect()`, `assertSeeText()`** — no con dump.
6. **No usar `dd()` o `dump()`** — usar `dump()` solo en debug temporal y borrarlo.
7. **Mocks solo para servicios externos** (Stripe, Mistral, Forge) — no mockear tu propio código.
8. **Tests deben correr en <100ms cada uno** (lentos = smell de acoplamiento).

## Patrón: Feature test

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_car(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->for($org)->create();
        Car::factory()->for($org)->count(3)->create();

        $response = $this->actingAs($user)
            ->post('/cars', [
                'brand' => 'Seat',
                'model' => 'Ibiza',
                'year' => 2020,
            ]);

        $response->assertRedirect('/cars');
        $this->assertDatabaseHas('cars', [
            'brand' => 'Seat',
            'organization_id' => $org->id,
        ]);
    }

    public function test_user_cannot_see_other_orgs_cars(): void
    {
        [$orgA, $orgB] = Organization::factory()->count(2)->create();
        $userA = User::factory()->for($orgA)->create();
        $carB = Car::factory()->for($orgB)->create();

        $response = $this->actingAs($userA)->get("/cars/{$carB->id}");

        $response->assertForbidden();
    }
}
```

## Patrón: Unit test con Mockery

```php
use Mockery;

class MistralBridgeTest extends TestCase
{
    public function test_falls_back_to_small_model_after_three_failures(): void
    {
        $http = Mockery::mock(\Illuminate\Http\Client\Factory::class);
        $http->shouldReceive('withToken')->andReturnSelf();
        $http->shouldReceive('timeout')->andReturnSelf();
        $http->shouldReceive('post')
            ->times(3)
            ->andThrow(new \Illuminate\Http\Client\ConnectionException());

        $bridge = new MistralBridge($http, config('services.mistral'));
        $result = $bridge->valueFromUrl('https://example.com/car/123');

        $this->assertArrayHasKey('fallback_used', $result);
        $this->assertTrue($result['fallback_used']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

## Anti-patrones (NUNCA)

- ❌ Test sin `RefreshDatabase` que crea filas en `:memory:` (no se limpia).
- ❌ Asumir orden de IDs o timestamps en asserts.
- ❌ Test que depende de otro test (orden, filas compartidas).
- ❌ Usar `sleep()` en tests (lentos + flaky).
- ❌ Mockear Eloquent (mejor usar SQLite in-memory).
- ❌ Test que hace HTTP a localhost (lento y flaky).
- ❌ Asserciones con `assertEquals(true, $var)` en lugar de `assertTrue($var)`.

## Comandos útiles

```bash
# Correr toda la suite
php artisan test --compact

# Filtrar por nombre
php artisan test --compact --filter=test_user_can_create_a_car

# Correr un archivo
php artisan test --compact tests/Feature/Cars/CreateCarTest.php

# Con coverage
php artisan test --coverage --min=70

# Solo unit
php artisan test --testsuite=Unit

# Solo feature
php artisan test --testsuite=Feature
```

## Organización

```
tests/
├── TestCase.php                          # base class
├── Feature/
│   ├── Auth/
│   ├── Billing/
│   ├── Cars/
│   ├── MultiTenancy/
│   └── ...
├── Unit/
│   ├── Services/
│   ├── Models/
│   └── ...
└── Fixtures/
    └── factories/
```

## Estado actual (2026-08-06)

- 210 tests, 13 fallidos pre-existentes (no regresiones).
- ✅ RefreshDatabase en todos los Feature.
- ✅ SQLite in-memory.
- ✅ Mockery para servicios externos.
- ⚠️ Coverage <70% en `app/Services/`.
- ⚠️ Tests E2E no implementados (Dusk pendiente).