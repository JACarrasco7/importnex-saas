<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarMarketingContent;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
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
        $this->assertEquals(CarMarketingContent::SOURCE_AI, $content->source,
            'El contenido generado con IA debe llevar source=ai');
    }

    public function test_generate_overrides_zip_source_to_ai(): void
    {
        // Coexistencia ZIP↔IA: si el canal venía del ZIP y el operador genera
        // con IA, el contenido pasa a ser de origen IA.
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                        'title' => 'Regenerado',
                        'description' => 'Nuevo texto',
                        'hashtags' => [],
                        'photo_tips' => [],
                    ]),
                ]],
            ], 200),
        ]);

        $org = Organization::factory()->withAi('anthropic', 'claude-3-5-sonnet-latest', 'sk-test-fake')->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        CarMarketingContent::create([
            'car_id' => $car->id,
            'channel' => 'instagram',
            'kind' => CarMarketingContent::KIND_POST,
            'slot' => 1,
            'title' => 'Del ZIP',
            'description' => 'Texto importado',
            'status' => 'draft',
            'source' => CarMarketingContent::SOURCE_ZIP,
        ]);

        $this->actingAs($user);

        $this->post(route('cars.marketing.generate', $car), ['channel' => 'instagram'])
            ->assertRedirect();

        $content = CarMarketingContent::where('car_id', $car->id)
            ->where('channel', 'instagram')
            ->where('kind', CarMarketingContent::KIND_POST)
            ->where('slot', 1)
            ->first();
        $this->assertEquals('Regenerado', $content->title);
        $this->assertEquals(CarMarketingContent::SOURCE_AI, $content->source);
        $this->assertNull($content->published_at,
            'Regenerar con IA debe limpiar published_at (contenido nuevo, no es el publicado anterior)');
    }

    public function test_save_keeps_existing_source(): void
    {
        // Editar y guardar un borrador del ZIP NO cambia su origen.
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        CarMarketingContent::create([
            'car_id' => $car->id,
            'channel' => 'tiktok',
            'kind' => CarMarketingContent::KIND_POST,
            'slot' => 1,
            'title' => 'Del ZIP',
            'description' => 'Texto importado',
            'status' => 'draft',
            'source' => CarMarketingContent::SOURCE_ZIP,
        ]);

        $this->actingAs($user);

        $this->post(route('cars.marketing.save', $car), [
            'channel' => 'tiktok',
            'kind' => CarMarketingContent::KIND_POST,
            'slot' => 1,
            'title' => 'Editado a mano',
            'description' => 'Texto retocado',
            'hashtags' => [],
            'photo_tips' => [],
        ])->assertRedirect();

        $content = CarMarketingContent::where('car_id', $car->id)
            ->where('channel', 'tiktok')
            ->where('kind', CarMarketingContent::KIND_POST)
            ->where('slot', 1)
            ->first();
        $this->assertEquals('Editado a mano', $content->title);
        $this->assertEquals(CarMarketingContent::SOURCE_ZIP, $content->source,
            'Guardar una edición NO debe cambiar el origen');
    }

    public function test_save_updates_draft(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->post(route('cars.marketing.save', $car), [
            'channel' => 'tiktok',
            'kind' => CarMarketingContent::KIND_POST,
            'slot' => 1,
            'title' => '¡Oferta increíble!',
            'description' => 'Coche de ensueño',
            'hashtags' => ['#coches', '#importacion'],
            'photo_tips' => ['Video con música trending'],
        ]);
        $response->assertRedirect();

        $content = CarMarketingContent::where('car_id', $car->id)
            ->where('channel', 'tiktok')
            ->where('kind', CarMarketingContent::KIND_POST)
            ->where('slot', 1)
            ->first();
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
            'kind' => CarMarketingContent::KIND_POST,
            'slot' => 1,
            'title' => 'Test',
            'description' => 'Test desc',
            'status' => 'draft',
        ]);

        $this->actingAs($user);

        $response = $this->post(route('cars.marketing.publish', $car), [
            'channel' => 'instagram',
        ]);
        $response->assertRedirect();

        $content = CarMarketingContent::where('car_id', $car->id)
            ->where('channel', 'instagram')
            ->where('kind', CarMarketingContent::KIND_POST)
            ->where('slot', 1)
            ->first();
        $this->assertEquals('published', $content->status);
        $this->assertNotNull($content->published_at);
    }

    public function test_publish_without_kind_publishes_all_channel_pieces(): void
    {
        // v2: sin kind/slot, publish marca TODAS las piezas del canal
        // (el botón del panel es "canal publicado").
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        foreach ([1, 2, 3] as $slot) {
            CarMarketingContent::create([
                'car_id' => $car->id,
                'channel' => 'tiktok',
                'kind' => CarMarketingContent::KIND_POST,
                'slot' => $slot,
                'title' => "Post {$slot}",
                'description' => 'Desc',
                'status' => 'draft',
            ]);
        }
        CarMarketingContent::create([
            'car_id' => $car->id,
            'channel' => 'tiktok',
            'kind' => CarMarketingContent::KIND_STORY,
            'slot' => 1,
            'title' => 'Story 1',
            'description' => 'Desc',
            'status' => 'draft',
        ]);

        $this->actingAs($user);

        $this->post(route('cars.marketing.publish', $car), ['channel' => 'tiktok'])
            ->assertRedirect();

        $this->assertSame(4, CarMarketingContent::where('car_id', $car->id)
            ->where('channel', 'tiktok')
            ->where('status', CarMarketingContent::STATUS_PUBLISHED)->count(),
            'Sin kind/slot se publican TODAS las piezas del canal');
    }

    public function test_publish_with_kind_and_slot_publishes_single_piece(): void
    {
        // v2: con kind+slot se publica SOLO esa pieza.
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        foreach ([1, 2] as $slot) {
            CarMarketingContent::create([
                'car_id' => $car->id,
                'channel' => 'instagram',
                'kind' => CarMarketingContent::KIND_POST,
                'slot' => $slot,
                'title' => "Post {$slot}",
                'description' => 'Desc',
                'status' => 'draft',
            ]);
        }

        $this->actingAs($user);

        $this->post(route('cars.marketing.publish', $car), [
            'channel' => 'instagram',
            'kind' => CarMarketingContent::KIND_POST,
            'slot' => 2,
        ])->assertRedirect();

        $this->assertSame(1, CarMarketingContent::where('car_id', $car->id)
            ->where('channel', 'instagram')
            ->where('status', CarMarketingContent::STATUS_PUBLISHED)->count());
        $piece = CarMarketingContent::where('car_id', $car->id)
            ->where('status', CarMarketingContent::STATUS_PUBLISHED)->first();
        $this->assertEquals(2, $piece->slot, 'La pieza publicada debe ser el post 2');
    }

    public function test_generate_social_channel_targets_post_slot_1(): void
    {
        // v2: generar con IA en un canal social SIEMPRE escribe (kind=post, slot=1),
        // nunca crea filas espurias 'ad' ni toca stories.
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'text',
                    'text' => json_encode([
                        'title' => 'Regenerado IA',
                        'description' => 'Texto IA',
                        'hashtags' => [],
                        'photo_tips' => [],
                    ]),
                ]],
            ], 200),
        ]);

        $org = Organization::factory()->withAi('anthropic', 'claude-3-5-sonnet-latest', 'sk-test-fake')->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $this->post(route('cars.marketing.generate', $car), ['channel' => 'instagram'])
            ->assertRedirect();

        $rows = CarMarketingContent::where('car_id', $car->id)->where('channel', 'instagram')->get();
        $this->assertCount(1, $rows, 'Generate en social debe crear exactamente 1 fila');
        $this->assertEquals(CarMarketingContent::KIND_POST, $rows[0]->kind);
        $this->assertEquals(1, $rows[0]->slot);
        $this->assertEquals('Regenerado IA', $rows[0]->title);
        $this->assertEquals(CarMarketingContent::SOURCE_AI, $rows[0]->source);
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

    public function test_marketing_index_orders_by_price_asc_and_includes_source(): void
    {
        // Convención de listados de vehículos: precio más bajo primero.
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $caro = Car::factory()->create(['organization_id' => $org->id, 'purchase_price' => 30000]);
        $barato = Car::factory()->create(['organization_id' => $org->id, 'purchase_price' => 10000]);

        CarMarketingContent::create([
            'car_id' => $barato->id,
            'channel' => 'instagram',
            'kind' => CarMarketingContent::KIND_POST,
            'slot' => 1,
            'title' => 'Del ZIP',
            'description' => 'Texto',
            'status' => 'draft',
            'source' => CarMarketingContent::SOURCE_ZIP,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('marketing.index'));
        $response->assertStatus(200);

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Marketing/Index')
            ->where('stats.from_zip', 1)
            ->has('cars.data', 2)
            ->where('cars.data.0.id', $barato->id)
            ->where('cars.data.1.id', $caro->id)
            ->where('cars.data.0.marketing_contents.0.source', 'zip'));
    }

    public function test_car_show_exposes_marketing_contents(): void
    {
        // F3: la ficha del coche expone marketingContents para el badge N/6.
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $car = Car::factory()->create(['organization_id' => $org->id]);

        foreach (['instagram' => 'post', 'tiktok' => 'post', 'wallapop' => 'ad'] as $channel => $kind) {
            CarMarketingContent::create([
                'car_id' => $car->id,
                'channel' => $channel,
                'kind' => $kind,
                'slot' => 1,
                'title' => 'Test '.$channel,
                'description' => 'Desc',
                'status' => 'draft',
                'source' => CarMarketingContent::SOURCE_ZIP,
            ]);
        }

        $this->actingAs($user);

        $response = $this->get(route('cars.show', $car));
        $response->assertStatus(200);

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('car.marketing_contents', 3)
            ->where('car.marketing_contents.0.source', 'zip')
            ->where('car.marketing_contents.0.channel', 'instagram'));
    }
}
