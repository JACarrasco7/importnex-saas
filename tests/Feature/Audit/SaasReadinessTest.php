<?php

namespace Tests\Feature\Audit;

use App\Models\Car;
use App\Models\CarMarketingContent;
use App\Models\ImportOrder;
use App\Models\Organization;
use App\Models\User;
use App\Services\ImportOrders\OrderContractService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Auditoría de venta (oct-2026): lo mínimo para operar como SaaS con varias
 * empresas — alta, cobro, aislamiento de datos y marca propia.
 */
class SaasReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_creates_the_company_and_starts_the_trial(): void
    {
        Mail::fake();

        $this->post('/register', [
            'company' => 'Importaciones Norte',
            'name' => 'Ana',
            'email' => 'ana@norte.test',
            'password' => 'Secreto-1234!',
            'password_confirmation' => 'Secreto-1234!',
        ])->assertRedirect(route('onboarding.index'));

        $user = User::where('email', 'ana@norte.test')->firstOrFail();
        $this->assertSame('Importaciones Norte', $user->organization->name);
        $this->assertSame('owner', $user->role);
        $this->assertTrue($user->organization->trial_ends_at->isFuture());
        $this->assertFalse($user->organization->isOwner());
    }

    public function test_user_without_organization_is_sent_to_create_one_instead_of_looping(): void
    {
        $user = User::factory()->create(['organization_id' => null, 'role' => 'owner']);

        $this->actingAs($user)->get('/onboarding')->assertRedirect(route('organization.create'));
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('organization.create'));

        $this->actingAs($user)->post(route('organization.store'), ['name' => 'Mi empresa'])
            ->assertRedirect(route('onboarding.index'));

        $this->assertNotNull($user->fresh()->organization_id);
    }

    public function test_expired_trial_without_subscription_is_sent_to_the_plans_page(): void
    {
        config(['subscription.enforce' => true]);
        $org = Organization::factory()->trialExpired()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('subscriptions.index'));
        $this->actingAs($user)->getJson('/cars')->assertStatus(402);
    }

    public function test_owner_organization_and_active_trials_are_not_blocked(): void
    {
        config(['subscription.enforce' => true]);
        $owner = Organization::factory()->trialExpired()->create(['is_owner' => true]);
        $trial = Organization::factory()->create(['trial_ends_at' => now()->addDays(5)]);

        foreach ([$owner, $trial] as $org) {
            $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
            $response = $this->actingAs($user)->get('/dashboard');
            $this->assertNotSame(route('subscriptions.index'), $response->headers->get('Location'));
        }
    }

    public function test_organization_api_token_only_reaches_its_own_organization(): void
    {
        config(['services.importnex_chat.token' => 'platform-secret']);
        $mine = Organization::factory()->create(['name' => 'Mía']);
        $other = Organization::factory()->create(['name' => 'Otra']);
        $token = $mine->issueApiToken();

        $this->getJson('/api/scouting?org=Otra', ['X-Import-Token' => $token])
            ->assertOk()
            ->assertJsonPath('organization_id', $mine->id);

        $this->getJson('/api/scouting', ['X-Import-Token' => 'wrong'])->assertStatus(401);

        $this->getJson('/api/scouting?org=Otra', ['X-Import-Token' => 'platform-secret'])
            ->assertOk()
            ->assertJsonPath('organization_id', $other->id);
    }

    public function test_api_token_is_stored_hashed_and_rotates(): void
    {
        $org = Organization::factory()->create();
        $first = $org->issueApiToken();
        $second = $org->issueApiToken();

        $this->assertNotSame($first, $second);
        $this->assertNull(Organization::findByApiToken($first));
        $this->assertTrue(Organization::findByApiToken($second)->is($org));
        $this->assertNotSame($second, $org->fresh()->api_token_hash);
    }

    public function test_marketing_stats_only_count_the_users_organization(): void
    {
        $mine = Organization::factory()->create();
        $other = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $mine->id, 'role' => 'owner']);

        foreach ([$mine, $other, $other] as $org) {
            $car = Car::factory()->create(['organization_id' => $org->id]);
            CarMarketingContent::create(['car_id' => $car->id, 'channel' => 'instagram', 'status' => 'published', 'source' => 'zip', 'content' => 'x']);
        }

        $this->actingAs($user)->get('/marketing')
            ->assertInertia(fn (Assert $page) => $page->where('stats.published', 1)->where('stats.from_zip', 1)->etc());
    }

    public function test_contract_and_portal_use_the_organizations_own_company_data(): void
    {
        Mail::fake();
        Storage::fake('local');
        $org = Organization::factory()->create([
            'name' => 'Coches Levante',
            'company' => ['legal_name' => 'Coches Levante SL', 'tax_id' => 'B12345678', 'address' => 'Calle Mayor 1', 'city' => 'Valencia', 'email' => 'hola@levante.test', 'phone' => '611222333', 'iban' => 'ES7620770024003102575766'],
        ]);
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $order = ImportOrder::factory()->withPortal()->create(['organization_id' => $org->id]);

        $contract = app(OrderContractService::class)->issue($order->fresh(), $user);
        $text = $contract->getContractText();

        $this->assertStringContainsString('Coches Levante SL', $text);
        $this->assertStringContainsString('B12345678', $text);
        $this->assertStringContainsString('Tribunales de Valencia', $text);
        $this->assertStringNotContainsString('JJ Import Motors', $text);
        $this->assertStringNotContainsString('Huelva', $text);

        $this->get(route('portal.show', $order->fresh()->portal_token))
            ->assertInertia(fn (Assert $page) => $page
                ->where('orgBrand.name', 'Coches Levante')
                ->where('bank.iban', 'ES7620770024003102575766')
                ->where('contact.email', 'hola@levante.test')
                ->etc());
    }

    public function test_owner_invites_a_team_member_and_operators_cannot(): void
    {
        Notification::fake();
        $org = Organization::factory()->create(['plan' => 'pro']);
        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);
        $operator = User::factory()->create(['organization_id' => $org->id, 'role' => 'operator']);

        $this->actingAs($owner)->post(route('organization.members.store', $org), [
            'name' => 'Luis', 'email' => 'luis@test.dev', 'role' => 'operator',
        ])->assertSessionHas('success');

        $member = User::where('email', 'luis@test.dev')->firstOrFail();
        $this->assertSame($org->id, $member->organization_id);
        Notification::assertSentTo($member, ResetPassword::class);

        $this->actingAs($operator)->post(route('organization.members.store', $org), [
            'name' => 'X', 'email' => 'x@test.dev', 'role' => 'owner',
        ])->assertForbidden();

        $foreign = Organization::factory()->create();
        $this->actingAs($owner)->delete(route('organization.members.destroy', [$foreign, $member]))->assertForbidden();
    }

    public function test_company_profile_is_saved_from_settings(): void
    {
        $org = Organization::factory()->create();
        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->actingAs($owner)->patch(route('organization.update', $org), [
            'name' => 'Nueva SL',
            'company' => ['tax_id' => ' B99999999 ', 'city' => 'Sevilla', 'unknown' => 'x', 'phone' => ''],
        ])->assertRedirect();

        $org->refresh();
        $this->assertSame('Nueva SL', $org->name);
        $this->assertSame(['tax_id' => 'B99999999', 'city' => 'Sevilla'], $org->company);
        $this->assertSame('Sevilla', $org->brand()->get('city'));
    }

    public function test_database_seeder_has_no_hardcoded_credentials(): void
    {
        $source = file_get_contents(database_path('seeders/DatabaseSeeder.php'));

        $this->assertStringNotContainsString('Hash::make(\'', $source);
        $this->assertStringNotContainsString('@jjimportmotors.com', $source);
    }
}
