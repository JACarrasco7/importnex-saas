<?php

namespace Tests\Feature\Car;

use App\Mail\TrackingSharedMail;
use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShareTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_generates_token_and_email(): void
    {
        Mail::fake();
        [$car, $user] = $this->makeContext();

        $response = $this->actingAs($user)->post(route('cars.share-tracking', $car), [
            'email' => 'cliente@example.com',
            'expected_delivery_date' => '2026-12-15',
        ]);

        $response->assertRedirect();
        $car->refresh();
        $this->assertNotEmpty($car->tracking_token);
        $this->assertSame('cliente@example.com', $car->tracking_shared_with_email);
        $this->assertSame('2026-12-15', $car->expected_delivery_date->toDateString());
        Mail::assertSent(TrackingSharedMail::class, 1);
    }

    public function test_revoke_marks_revoked_at(): void
    {
        [$car, $user] = $this->makeContext();
        $car->shareTracking();

        $this->actingAs($user)
            ->delete(route('cars.revoke-tracking', $car))
            ->assertRedirect();

        $car->refresh();
        $this->assertNotNull($car->tracking_revoked_at);
    }

    public function test_regenerate_rotates_token(): void
    {
        [$car, $user] = $this->makeContext();
        $oldToken = $car->tracking_token;
        $car->shareTracking();

        $this->actingAs($user)
            ->post(route('cars.regenerate-tracking', $car))
            ->assertRedirect();

        $car->refresh();
        $this->assertNotSame($oldToken, $car->tracking_token);
        $this->assertNull($car->tracking_revoked_at);
    }

    public function test_guest_cannot_share(): void
    {
        [$car] = $this->makeContext();

        $this->post(route('cars.share-tracking', $car), [
            'email' => 'x@x.com',
        ])->assertRedirect(route('login'));
    }

    public function test_share_blocked_when_status_not_trackable(): void
    {
        [$car, $user] = $this->makeContext();
        $car->forceFill(['status' => 'Located'])->save();

        $this->actingAs($user)
            ->from(route('cars.show', $car))
            ->post(route('cars.share-tracking', $car), [
                'email' => 'cliente@example.com',
            ])
            ->assertRedirect(route('cars.show', $car))
            ->assertSessionHas('error');

        $car->refresh();
        $this->assertNull($car->tracking_token);
    }

    public function test_create_contract_requires_client(): void
    {
        [$car, $user] = $this->makeContext();
        $car->forceFill(['client_id' => null])->save();

        $this->actingAs($user)
            ->from(route('cars.show', $car))
            ->post(route('cars.contract.create', $car))
            ->assertRedirect(route('cars.show', $car))
            ->assertSessionHas('error');

        $this->assertSame(0, $car->contractAcceptances()->count());
    }

    public function test_validation_rejects_bad_email(): void
    {
        [$car, $user] = $this->makeContext();

        $this->actingAs($user)
            ->from(route('cars.show', $car))
            ->post(route('cars.share-tracking', $car), [
                'email' => 'not-an-email',
            ])
            ->assertRedirect(route('cars.show', $car))
            ->assertSessionHasErrors('email');
    }

    /**
     * @return array{0: Car, 1: User}
     */
    private function makeContext(): array
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create([
            'organization_id' => $org->id,
            'role' => 'owner',
        ]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'status' => 'Purchased',
        ]);

        return [$car, $user];
    }
}
