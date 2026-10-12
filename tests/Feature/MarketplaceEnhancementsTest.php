<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarPhoto;
use App\Models\CarPublicLink;
use App\Models\NewsletterSubscription;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketplace_view_increments_counter_on_first_view(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 'test']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'purchase_price' => 10000,
            'marketplace_views' => 0,
        ]);

        $this->get("/marketplace/{$car->id}")->assertOk();
        $this->assertEquals(1, $car->fresh()->marketplace_views);
    }

    public function test_marketplace_view_first_view_returns_views_in_response(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 'test']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'purchase_price' => 10000,
            'marketplace_views' => 5,
        ]);

        $response = $this->get("/marketplace/{$car->id}");
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->where('car.marketplace_views', 6));
    }

    public function test_marketplace_view_404_when_not_public(): void
    {
        $org = Organization::factory()->create(['is_public' => false]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);

        $this->get("/marketplace/{$car->id}")->assertStatus(404);
    }

    public function test_newsletter_subscribe_persists_email(): void
    {
        $response = $this->postJson('/newsletter/subscribe', [
            'email' => 'test@example.com',
            'locale' => 'es',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('newsletter_subscriptions', [
            'email' => 'test@example.com',
            'source' => 'marketplace_popup',
        ]);
    }

    public function test_newsletter_subscribe_dedupes_existing_email(): void
    {
        NewsletterSubscription::create([
            'email' => 'returning@example.com',
            'source' => 'marketplace_popup',
        ]);

        $this->postJson('/newsletter/subscribe', [
            'email' => 'RETURNING@example.com',
        ])->assertOk();

        $this->assertEquals(1, NewsletterSubscription::count());
        $this->assertDatabaseHas('newsletter_subscriptions', ['email' => 'returning@example.com']);
    }

    public function test_newsletter_validates_email_format(): void
    {
        $this->postJson('/newsletter/subscribe', [
            'email' => 'not-an-email',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_newsletter_rate_limit_after_5_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/newsletter/subscribe', [
                'email' => "user{$i}@test.com",
            ]);
        }
        $response = $this->postJson('/newsletter/subscribe', [
            'email' => 'spam@test.com',
        ]);
        $response->assertStatus(429);
    }

    public function test_newsletter_unsubscribe_marks_timestamp(): void
    {
        NewsletterSubscription::create([
            'email' => 'unsub@test.com',
            'source' => 'marketplace_popup',
        ]);

        $this->deleteJson('/newsletter/unsubscribe', [
            'email' => 'unsub@test.com',
        ])->assertOk();

        $this->assertNotNull(NewsletterSubscription::first()->fresh()->unsubscribed_at);
    }

    public function test_marketplace_index_passes_request_url_for_public_org(): void
    {
        $org = Organization::factory()->create([
            'is_public' => true,
            'slug' => 'jj-import-motors',
        ]);
        Car::factory()->count(2)->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);

        $response = $this->get('/marketplace');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceIndex')
            ->where('requestUrl', url('/request/jj-import-motors'))
        );
    }

    public function test_marketplace_index_request_url_null_when_no_public_org(): void
    {
        // Sin ninguna organización pública: la CTA no debe romperse
        $this->get('/marketplace')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/MarketplaceIndex')
                ->where('requestUrl', null)
            );
    }

    public function test_marketplace_show_passes_request_url(): void
    {
        $org = Organization::factory()->create([
            'is_public' => true,
            'slug' => 'jj-import-motors',
        ]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);

        $response = $this->get("/marketplace/{$car->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->where('requestUrl', url('/request/jj-import-motors'))
        );
    }

    public function test_marketplace_show_request_url_null_when_org_not_public(): void
    {
        $org = Organization::factory()->create([
            'is_public' => false,
            'slug' => 'private-org',
        ]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);

        // El coche es público (toggle is_marketplace=true) PERO la org no.
        // El scope publicMarketplace lo excluye: el controller debe 404
        // antes de pasar requestUrl.
        $this->get("/marketplace/{$car->id}")->assertStatus(404);
    }

    public function test_marketplace_show_serves_photos_as_objects(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);
        CarPhoto::create([
            'car_id' => $car->id,
            'organization_id' => $org->id,
            'url' => 'cars/'.$car->id.'/front.jpg',
            'photo_type' => 'front',
            'sort_order' => 1,
        ]);

        $response = $this->get("/marketplace/{$car->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->has('car.photos', 1)
            ->where('car.photos.0.url', 'cars/'.$car->id.'/front.jpg')
        );
    }

    public function test_marketplace_index_supports_sort(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        $cheap = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'purchase_price' => 5000,
        ]);
        $expensive = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'purchase_price' => 50000,
        ]);

        $response = $this->get('/marketplace?sort=price_asc');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceIndex')
            ->loadDeferredProps('default', fn ($prop) => $prop
                ->where('cars.data.0.id', $cheap->id)
                ->where('cars.data.1.id', $expensive->id)
            )
            ->where('sort', 'price_asc')
        );
    }

    public function test_marketplace_index_filters_by_extended_criteria(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        $bmw = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'BMW',
            'fuel' => 'Diesel',
        ]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'Audi',
            'fuel' => 'Gasoline',
        ]);

        $response = $this->get('/marketplace?brand=BMW&fuel=Diesel');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceIndex')
            ->loadDeferredProps('default', fn ($prop) => $prop
                ->has('cars')
                ->where('cars.total', 1)
                ->where('cars.data.0.id', $bmw->id)
            )
            ->where('filters.brand', 'BMW')
        );
    }

    public function test_marketplace_index_passes_filter_bounds(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'purchase_price' => 8000,
        ]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'purchase_price' => 25000,
        ]);

        $response = $this->get('/marketplace');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceIndex')
            ->where('filterBounds.price.min', 8000)
            ->where('filterBounds.price.max', 25000)
        );
    }

    public function test_marketplace_show_passes_related_cars(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        $bmw1 = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'BMW',
        ]);
        $bmw2 = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'BMW',
        ]);
        Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'brand' => 'Audi', // distinta marca, no debe aparecer
        ]);

        $response = $this->get("/marketplace/{$bmw1->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->has('relatedCars', 1)
            ->where('relatedCars.0.id', $bmw2->id)
        );
    }

    public function test_marketplace_compare_passes_request_url(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        $car1 = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);
        $car2 = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);

        $response = $this->get("/marketplace/compare?ids={$car1->id},{$car2->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceCompare')
            ->where('requestUrl', url('/request/t'))
            ->has('cars', 2)
        );
    }

    public function test_publishing_car_auto_creates_public_link(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => false,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);

        // Al cambiar is_marketplace a true, el observer debe generar un link.
        $car->update(['is_marketplace' => true]);

        $link = $car->fresh()->activePublicLink();
        $this->assertNotNull($link, 'Publishing a car to the marketplace must auto-generate a CarPublicLink.');
        $this->assertSame(32, strlen($link->token));
        $this->assertStringStartsWith(url('/p/'), $link->publicProfileUrl());
    }

    public function test_public_profile_route_shows_car(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);
        $link = CarPublicLink::generateFor($car);

        $response = $this->get("/p/{$link->token}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Public/MarketplaceShow')
            ->where('car.id', $car->id)
            ->where('isSharedProfile', true)
            ->where('publicProfileUrl', url('/p/'.$link->token))
        );
        // El link debe haberse contabilizado como vista.
        $this->assertSame(1, $link->fresh()->views_count);
    }

    public function test_public_profile_route_404_on_invalid_token(): void
    {
        $this->get('/p/esto-no-es-un-token-valido-de-32-chars')->assertStatus(404);
        $this->get('/p/short')->assertStatus(404);
    }

    public function test_public_profile_route_404_on_revoked_token(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);
        $link = CarPublicLink::generateFor($car);
        $link->forceFill(['revoked_at' => now()])->save();

        $this->get("/p/{$link->token}")->assertStatus(404);
    }

    public function test_public_profile_route_404_when_car_no_longer_public(): void
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
        ]);
        $link = CarPublicLink::generateFor($car);

        // El operador despublica el coche después de generar el link.
        $car->update(['is_marketplace' => false]);

        $this->get("/p/{$link->token}")->assertStatus(404);
    }

    public function test_public_marketplace_does_not_leak_admin_verdict_text(): void
    {
        // El verdict en BD contiene texto admin-interno como
        // "Comprar con confianza baja (cobertura parcial 2/7)" o
        // "Dudoso: kilometraje muy alto para un R de 2024" — eso NO
        // debe llegar nunca al cliente final, ni por la ficha
        // /marketplace/{id} ni por el listado /marketplace.
        $org = Organization::factory()->create(['is_public' => true, 'slug' => 't']);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Comprar con confianza baja (cobertura parcial 2/7)',
            'verdict_confidence' => 'low',
            'verdict_reasoning' => 'Análisis interno que NO debe ver el cliente.',
            'verdict_changes' => 'Notas internas que NO debe ver el cliente.',
            'traffic_light' => 'neutral',
        ]);

        $sensitiveKeys = [
            'verdict', 'verdict_confidence', 'verdict_reasoning',
            'verdict_changes', 'verdict_at', 'traffic_light',
        ];

        // 1) Ficha /marketplace/{id} no expone los campos admin.
        $response = $this->get("/marketplace/{$car->id}")->assertOk();
        $body = $response->getContent();
        foreach ($sensitiveKeys as $key) {
            $this->assertStringNotContainsString(
                '"'.$key.'":',
                $body,
                "La ficha pública filtra el campo admin '{$key}'"
            );
        }
        $this->assertStringNotContainsString(
            'Comprar con confianza baja',
            $body,
            'La ficha pública filtra el texto admin del verdict'
        );

        // 2) Listado /marketplace tampoco expone los campos admin.
        $listingResponse = $this->get('/marketplace')->assertOk();
        $listingBody = $listingResponse->getContent();
        foreach ($sensitiveKeys as $key) {
            $this->assertStringNotContainsString(
                '"'.$key.'":',
                $listingBody,
                "El listado público filtra el campo admin '{$key}'"
            );
        }
        $this->assertStringNotContainsString(
            'Comprar con confianza baja',
            $listingBody,
            'El listado público filtra el texto admin del verdict'
        );

        // 3) La página /p/{token} (perfil público) tampoco.
        $link = CarPublicLink::generateFor($car);
        $profileResponse = $this->get("/p/{$link->token}")->assertOk();
        $profileBody = $profileResponse->getContent();
        foreach ($sensitiveKeys as $key) {
            $this->assertStringNotContainsString(
                '"'.$key.'":',
                $profileBody,
                "El perfil público /p/{token} filtra el campo admin '{$key}'"
            );
        }
    }
}
