<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarMarketingContent;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CarMarketingTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_show_page_loads(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->get(route('cars.marketing', $car));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Cars/Marketing')->where('car.id', $car->id));
    }

    public function test_generate_creates_draft_content(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                        'title' => 'BMW X5 2020 - Impecable',
                        'description' => 'Coche importado de Alemania...',
                        'hashtags' => ['#bmw', '#x5'],
                        'photo_tips' => ['Usa foto delantera como portada'],
                    ]),
                ]],
            ], 200),
        ]);

        $org = Organization::factory()->withAi('anthropic', 'claude-3-5-sonnet-latest', 'sk-test-fake')->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->post(route('cars.marketing.generate', $car), [
            'channel' => 'milanuncios',
        ]);
        $response->assertRedirect();

        $content = CarMarketingContent::where('car_id', $car->id)->where('channel', 'milanuncios')->first();
        $this->assertNotNull($content);
        $this->assertEquals('draft', $content->status);
        $this->assertEquals('BMW X5 2020 - Impecable', $content->title);
    }

    public function test_save_updates_draft(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->post(route('cars.marketing.save', $car), [
            'channel' => 'tiktok',
            'title' => '¡Oferta increíble!',
            'description' => 'Coche de ensueño',
            'hashtags' => ['#coches', '#importacion'],
            'photo_tips' => ['Video con música trending'],
        ]);
        $response->assertRedirect();

        $content = CarMarketingContent::where('car_id', $car->id)->where('channel', 'tiktok')->first();
        $this->assertNotNull($content);
        $this->assertEquals('¡Oferta increíble!', $content->title);
        $this->assertEquals('draft', $content->status);
    }

    public function test_publish_marks_as_published(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        CarMarketingContent::create([
            'car_id' => $car->id,
            'channel' => 'instagram',
            'title' => 'Test',
            'description' => 'Test desc',
            'status' => 'draft',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('cars.marketing.publish', $car), [
            'channel' => 'instagram',
        ]);
        $response->assertRedirect();

        $content = CarMarketingContent::where('car_id', $car->id)->where('channel', 'instagram')->first();
        $this->assertEquals('published', $content->status);
        $this->assertNotNull($content->published_at);
    }

    public function test_marketing_index_loads(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->get(route('marketing.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Marketing/Index'));
    }
}
