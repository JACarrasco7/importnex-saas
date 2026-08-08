<?php

namespace Tests\Feature;

use App\Http\Requests\StoreCarRequest;
use App\Models\Car;
use App\Models\Client;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private function orgAndUser(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
        ]);

        return [$org, $user];
    }

    public function test_store_car_request_authorizes_authenticated_user_with_org(): void
    {
        [$org, $user] = $this->orgAndUser();
        $request = StoreCarRequest::createFrom(app('Illuminate\Http\Request')->create('/cars', 'POST'));
        $request->setUserResolver(fn () => $user);

        $this->assertTrue($request->authorize());
    }

    public function test_store_car_request_denies_user_without_org(): void
    {
        $user = User::factory()->create(['organization_id' => null]);
        $request = StoreCarRequest::createFrom(app('Illuminate\Http\Request')->create('/cars', 'POST'));
        $request->setUserResolver(fn () => $user);

        $this->assertFalse($request->authorize());
    }

    public function test_store_validates_required_fields(): void
    {
        [$org, $user] = $this->actingAsUser();

        $response = $this->actingAs($user)->post(route('cars.store'), []);

        $response->assertSessionHasErrors(['brand', 'model', 'year', 'fuel', 'transmission', 'purchase_price', 'status', 'traffic_light']);
    }

    public function test_store_validates_year_format(): void
    {
        [$org, $user] = $this->actingAsUser();

        $response = $this->actingAs($user)->post(route('cars.store'), $this->validPayload([
            'year' => '2024', // wrong format, expects MM/YYYY
        ]));

        $response->assertSessionHasErrors(['year']);
    }

    public function test_store_validates_status_enum(): void
    {
        [$org, $user] = $this->actingAsUser();

        $response = $this->actingAs($user)->post(route('cars.store'), $this->validPayload([
            'status' => 'invalid_status',
        ]));

        $response->assertSessionHasErrors(['status']);
    }

    public function test_store_validates_traffic_light_enum(): void
    {
        [$org, $user] = $this->actingAsUser();

        $response = $this->actingAs($user)->post(route('cars.store'), $this->validPayload([
            'traffic_light' => 'magenta',
        ]));

        $response->assertSessionHasErrors(['traffic_light']);
    }

    public function test_store_rejects_client_from_other_org(): void
    {
        [$org, $user] = $this->actingAsUser();
        $otherOrg = Organization::factory()->create();
        $foreignClient = Client::factory()->create(['organization_id' => $otherOrg->id]);

        $response = $this->actingAs($user)->post(route('cars.store'), $this->validPayload([
            'client_id' => $foreignClient->id,
        ]));

        $response->assertSessionHasErrors(['client_id']);
    }

    public function test_update_denies_car_from_other_org(): void
    {
        [$org, $user] = $this->actingAsUser();
        $otherOrg = Organization::factory()->create();
        $foreignCar = Car::factory()->create(['organization_id' => $otherOrg->id]);

        // Route Model Binding scoped to user.organization returns 404 for foreign cars,
        // which is the desired secure behavior (don't leak existence of other org's data).
        $response = $this->actingAs($user)->patch(route('cars.update', $foreignCar), $this->validPayload());

        $response->assertStatus(404);
    }

    public function test_update_accepts_own_org_car(): void
    {
        [$org, $user] = $this->actingAsUser();
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $response = $this->actingAs($user)->patch(route('cars.update', $car), $this->validPayload());

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('cars.index'));
    }

    public function test_store_rejects_negative_purchase_price(): void
    {
        [$org, $user] = $this->actingAsUser();

        $response = $this->actingAs($user)->post(route('cars.store'), $this->validPayload([
            'purchase_price' => -100,
        ]));

        $response->assertSessionHasErrors(['purchase_price']);
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function actingAsUser(): array
    {
        [$org, $user] = $this->orgAndUser();

        return [$org, $user];
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'brand' => 'Audi',
            'model' => 'A3',
            'year' => '06/2020',
            'fuel' => 'Diesel',
            'transmission' => 'Manual',
            'purchase_price' => 15000,
            'status' => 'Located',
            'traffic_light' => 'green',
            'client_id' => null,
        ], $overrides);
    }
}
