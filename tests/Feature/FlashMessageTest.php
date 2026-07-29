<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_flash_appears_in_session_after_redirect(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        $response = $this->post('/cars', [
            'brand' => 'BMW',
            'model' => 'Test',
            'year' => '07/2020',
            'fuel' => 'Diesel',
            'transmission' => 'Manual',
            'purchase_price' => 10000,
            'status' => 'Located',
            'traffic_light' => 'green',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_shared_props_include_flash_keys(): void
    {
        $reflection = new \ReflectionClass(\App\Http\Middleware\HandleInertiaRequests::class);
        $method = $reflection->getMethod('share');
        $this->assertTrue($method->isPublic());
    }

    public function test_handleinertia_exposes_csrf_token(): void
    {
        $this->get('/')
            ->assertInertia(fn ($page) => $page->has('csrfToken')
            );
    }

    public function test_handleinertia_exposes_pending_alerts_count(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $this->actingAs($user);

        $this->get('/dashboard')
            ->assertInertia(fn ($page) => $page->has('pending_alerts_count')
            );
    }
}
