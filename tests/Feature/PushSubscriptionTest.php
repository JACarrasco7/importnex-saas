<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_creates_record(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);

        $this->actingAs($user);

        $response = $this->postJson(route('push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => [
                'p256dh' => 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QTpQtUbVlUls0VJXg7A8u-Ts1XbjhazAkj7Ihbj0yhkU5k0M',
                'auth' => 'tBHItJI5svbpez7KI4CCXg',
            ],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        ]);
    }

    public function test_subscribe_requires_url(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson(route('push.subscribe'), [
            'endpoint' => 'not-a-url',
            'keys' => ['p256dh' => 'aaa', 'auth' => 'bbb'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('endpoint');
    }

    public function test_subscribe_is_idempotent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $payload = [
            'endpoint' => 'https://example.com/push/abc',
            'keys' => ['p256dh' => 'key1', 'auth' => 'key2'],
        ];

        // Primera vez: crea
        $this->postJson(route('push.subscribe'), $payload)->assertOk();
        $this->assertEquals(1, PushSubscription::where('user_id', $user->id)->count());

        // Segunda vez: actualiza (mismo endpoint)
        $payload['keys']['p256dh'] = 'newkey';
        $this->postJson(route('push.subscribe'), $payload)->assertOk();
        $this->assertEquals(1, PushSubscription::where('user_id', $user->id)->count());
        $this->assertEquals('newkey', PushSubscription::first()->p256dh);
    }

    public function test_unsubscribe_removes_record(): void
    {
        $user = User::factory()->create();
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://example.com/push/abc',
            'p256dh' => 'key1',
            'auth' => 'key2',
        ]);

        $this->actingAs($user);

        $response = $this->deleteJson(route('push.unsubscribe'), [
            'endpoint' => 'https://example.com/push/abc',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'deleted' => 1]);
        $this->assertDatabaseMissing('push_subscriptions', ['user_id' => $user->id]);
    }

    public function test_vapid_key_returns_disabled_when_not_configured(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id]);
        $this->actingAs($user);

        $response = $this->getJson(route('push.vapid-key'));
        $response->assertOk();
        $response->assertJson(['enabled' => false]);
    }

    public function test_vapid_key_returns_onesignal_config_when_configured(): void
    {
        $org = Organization::factory()->create([
            'onesignal_app_id' => 'test-app-id-123',
            'onesignal_api_key' => 'test-api-key',
        ]);
        $user = User::factory()->create(['organization_id' => $org->id]);
        $this->actingAs($user);

        $response = $this->getJson(route('push.vapid-key'));
        $response->assertOk();
        $response->assertJson([
            'enabled' => true,
            'app_id' => 'test-app-id-123',
        ]);
    }
}
