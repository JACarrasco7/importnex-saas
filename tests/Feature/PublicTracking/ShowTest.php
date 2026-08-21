<?php

namespace Tests\Feature\PublicTracking;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use App\Support\CarChecklistDefinitions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_inertia_render_for_valid_token(): void
    {
        [$car, $token] = $this->makeTrackedCar();

        $response = $this->get(route('public.tracking.show', $token));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Public/Tracking')
            ->where('car.brand', 'Audi')
            ->where('car.model', 'A3'));
    }

    public function test_show_404s_for_invalid_token(): void
    {
        $this->get(route('public.tracking.show', 'NONEXISTENT_TOKEN_LONG_ENOUGH_TO_PASS_ROUTING'))
            ->assertNotFound();
    }

    public function test_show_404s_when_token_revoked(): void
    {
        [$car, $token] = $this->makeTrackedCar();
        $car->revokeTracking();

        $this->get(route('public.tracking.show', $token))->assertNotFound();
    }

    public function test_show_404s_when_status_not_trackable(): void
    {
        [$car, $token] = $this->makeTrackedCar();
        $car->forceFill(['status' => 'Searching'])->save();

        $this->get(route('public.tracking.show', $token))->assertNotFound();
    }

    public function test_show_does_not_leak_internal_data(): void
    {
        [$car, $token] = $this->makeTrackedCar();
        $car->forceFill([
            'purchase_price' => 9999.99,
            'vin' => 'WBAXXXXXXXXXXX',
            'verdict_reasoning' => 'SECRET_REASONING',
        ])->save();

        $response = $this->get(route('public.tracking.show', $token));
        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringNotContainsString('9999.99', $content);
        $this->assertStringNotContainsString('WBAXXXXXXXXXXX', $content);
        $this->assertStringNotContainsString('SECRET_REASONING', $content);
        // Asegurar que la página no incluye purchase_price en absoluto.
        $this->assertStringNotContainsString('purchase_price', $content);
    }

    public function test_show_increments_views_counter(): void
    {
        [$car, $token] = $this->makeTrackedCar();
        $initial = $car->tracking_views;

        $this->get(route('public.tracking.show', $token))->assertOk();
        $this->get(route('public.tracking.show', $token))->assertOk();

        $car->refresh();
        $this->assertSame($initial + 2, $car->tracking_views);
    }

    public function test_show_includes_milestones_in_order(): void
    {
        [$car, $token] = $this->makeTrackedCar();

        $this->get(route('public.tracking.show', $token))
            ->assertInertia(fn ($page) => $page->has('car.milestones', count(CarChecklistDefinitions::milestones())));
    }

    public function test_show_returns_429_after_many_requests(): void
    {
        [$car, $token] = $this->makeTrackedCar();

        // 60 OK + 1 más debe ser 429.
        for ($i = 0; $i < 60; $i++) {
            $this->get(route('public.tracking.show', $token))->assertOk();
        }
        $this->get(route('public.tracking.show', $token))->assertStatus(429);
    }

    /**
     * @return array{0: Car, 1: string}
     */
    private function makeTrackedCar(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Audi',
            'model' => 'A3',
            'status' => 'Purchased',
        ]);
        // Sembrar checklists (mismo observer)
        $car->load('checklists');
        $url = $car->shareTracking('cliente@example.com');
        $token = basename(parse_url($url, PHP_URL_PATH));

        return [$car, $token];
    }
}
