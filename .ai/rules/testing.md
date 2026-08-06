# Testing Rules — PHPUnit 13

> Activar por glob: `tests/Feature/**`, `tests/Unit/**`.

---

## Stack

- **PHPUnit 13.2** (NO Pest).
- **Mockery 1.6** para stubs.
- **Collision 8.9** errores prettifier.
- **SQLite `:memory:`** en tests.
- **Trains**: `RefreshDatabase` o `DatabaseTransactions`.

## Configuración crítica (`phpunit.xml`)

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_DRIVER" value="array"/>
<env name="SESSION_DRIVER" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER" value="array"/>
<env name="STRIPE_SECRET" value="sk_test_fake"/>
<env name="MISTRAL_API_KEY" value="test-key"/>
```

## Reglas inquebrantables

1. **Todo test con BD usa `RefreshDatabase`.**
2. **Tests multi-tenant** mínimo 2 organizaciones.
3. **Factories** con `for($org)`.
4. **`actingAs($user)`** en cada Feature con auth.
5. **Mocks** solo servicios externos (Stripe, Mistral, Forge).
6. **NO `sleep()`** ni HTTP a localhost.
7. **Tests <100ms** cada uno.
8. **Asserciones específicas** (`assertTrue` > `assertEquals(true)`).

## Patrón Feature

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

        $response = $this->actingAs($user)
            ->post('/cars', ['brand' => 'Seat', 'model' => 'Ibiza', 'year' => 2020]);

        $response->assertRedirect('/cars');
        $this->assertDatabaseHas('cars', [
            'brand' => 'Seat',
            'organization_id' => $org->id,
        ]);
    }
}
```

## Patrón Unit + Mockery

```php
use Mockery;

class MistralBridgeTest extends TestCase
{
    public function test_falls_back_after_three_failures(): void
    {
        $http = Mockery::mock(\Illuminate\Http\Client\Factory::class);
        $http->shouldReceive('withToken')->andReturnSelf();
        $http->shouldReceive('timeout')->andReturnSelf();
        $http->shouldReceive('post')->times(3)
            ->andThrow(new \Illuminate\Http\Client\ConnectionException());

        $bridge = new MistralBridge($http, config('services.mistral'));
        $result = $bridge->valueFromUrl('https://example.com/car/123');

        $this->assertTrue($result['fallback_used']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
```

## Comandos

```bash
php artisan test --compact
php artisan test --compact --filter=test_user_can_create_a_car
php artisan test --compact tests/Feature/Cars/CreateCarTest.php
php artisan test --coverage --min=70
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

## Organización

```
tests/
├── TestCase.php
├── Feature/
│   ├── Auth/      Billing/    Cars/
│   ├── MultiTenancy/  ...
├── Unit/
│   ├── Services/  Models/   ...
└── Fixtures/
    └── factories/
```

## NO HACER

- ❌ Test con BD sin `RefreshDatabase`.
- ❌ Asumir orden de IDs o timestamps.
- ❌ Test depende de otro (orden, filas compartidas).
- ❌ `sleep()` en tests.
- ❌ Mockear Eloquent (usar SQLite :memory:).
- ❌ HTTP a localhost.
- ❌ `assertEquals(true, $var)` (usar `assertTrue`).
