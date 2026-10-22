<?php

namespace Tests\Feature\Audit;

use App\Models\Client;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

/**
 * Auditoría de venta: el cobro tiene que funcionar de punta a punta. La
 * suscripción pertenece a la organización (no al usuario) y se llama «main».
 */
class BillingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function subscribe(Organization $org, string $status = 'active'): Subscription
    {
        return Subscription::create([
            'organization_id' => $org->id,
            'type' => Organization::SUBSCRIPTION_TYPE,
            'stripe_id' => 'sub_'.$org->id,
            'stripe_status' => $status,
            'stripe_price' => 'price_pro',
            'quantity' => 1,
        ]);
    }

    public function test_subscription_owner_is_the_organization(): void
    {
        $org = Organization::factory()->create(['stripe_id' => 'cus_1']);
        $subscription = $this->subscribe($org);

        $this->assertTrue($subscription->owner->is($org));
    }

    public function test_paying_organization_with_expired_trial_is_not_blocked(): void
    {
        config(['subscription.enforce' => true]);
        $org = Organization::factory()->trialExpired()->create(['stripe_id' => 'cus_2']);
        $this->subscribe($org);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->assertTrue($org->fresh()->hasActiveSubscription());
        $response = $this->actingAs($user)->get('/dashboard');
        $this->assertNotSame(route('subscriptions.index'), $response->headers->get('Location'));
    }

    public function test_webhook_cancellation_from_stripe_updates_the_local_subscription(): void
    {
        config(['cashier.webhook.secret' => null]);
        $org = Organization::factory()->create(['stripe_id' => 'cus_3', 'plan' => 'pro']);
        $subscription = $this->subscribe($org);

        $this->postJson('/stripe/webhook', [
            'id' => 'evt_1',
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => [
                'id' => $subscription->stripe_id,
                'customer' => 'cus_3',
                'status' => 'canceled',
                'items' => ['data' => []],
            ]],
        ])->assertOk();

        $this->assertSame('canceled', $subscription->fresh()->stripe_status);
        $this->assertSame('starter', $org->fresh()->plan);
        $this->assertFalse($org->fresh()->subscribed(Organization::SUBSCRIPTION_TYPE));
    }

    public function test_contact_cannot_be_linked_to_another_organizations_client(): void
    {
        $mine = Organization::factory()->create();
        $other = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $mine->id, 'role' => 'owner']);
        $foreignClient = Client::factory()->create(['organization_id' => $other->id]);

        $this->actingAs($user)->post(route('contacts.store'), [
            'name' => 'Vendedor',
            'client_id' => $foreignClient->id,
        ])->assertSessionHasErrors('client_id');

        $this->assertSame(0, Contact::withoutGlobalScopes()->where('client_id', $foreignClient->id)->count());
    }
}
