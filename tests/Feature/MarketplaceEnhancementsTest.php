<?php

namespace Tests\Feature;

use App\Models\Car;
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
            'status' => 'Delivered',
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
            'status' => 'Delivered',
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
            'status' => 'Delivered',
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
}
