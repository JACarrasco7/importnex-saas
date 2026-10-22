<?php

namespace Tests\Feature\Audit;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoría de venta (D1): cada empresa tiene su web pública en /web/{slug}
 * con sus coches, su marca y su contacto. Ninguna empresa sale en la web de
 * otra ni en /marketplace (la web de la organización operadora).
 */
class CompanySiteTest extends TestCase
{
    use RefreshDatabase;

    private Organization $platform;

    private Organization $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platform = Organization::factory()->create([
            'name' => 'Operadora', 'slug' => 'operadora', 'is_public' => true, 'is_owner' => true,
            'company' => ['whatsapp' => '600111222'],
        ]);
        $this->company = Organization::factory()->create([
            'name' => 'Importaciones Sur', 'slug' => 'importaciones-sur', 'is_public' => true, 'plan' => 'starter',
            'trial_ends_at' => now()->addDays(10), 'company' => ['whatsapp' => '699888777'],
        ]);
    }

    private function publishedCar(Organization $organization, array $attributes = []): Car
    {
        return Car::factory()->create([
            'organization_id' => $organization->id,
            'is_marketplace' => true,
            'status' => 'Located',
            ...$attributes,
        ]);
    }

    public function test_company_site_lists_only_its_cars_with_its_brand(): void
    {
        $own = $this->publishedCar($this->company);
        $this->publishedCar($this->platform);

        $this->get('/web/importaciones-sur')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/MarketplaceIndex')
                ->where('marketplace.orgName', 'Importaciones Sur')
                ->where('marketplace.homeUrl', route('site.index', ['site' => 'importaciones-sur']))
                ->where('marketplace.requestUrl', route('public.car-request.index', ['slug' => 'importaciones-sur']))
                ->where('orgBrand.name', 'Importaciones Sur')
                ->where('whatsappNumber', '34699888777')
                ->loadDeferredProps('default', fn ($prop) => $prop
                    ->has('cars.data', 1)
                    ->where('cars.data.0.id', $own->id)));
    }

    public function test_platform_marketplace_does_not_show_other_companies_cars(): void
    {
        $platformCar = $this->publishedCar($this->platform);
        $this->publishedCar($this->company);

        $this->get('/marketplace')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('marketplace.orgName', 'Operadora')
                ->where('whatsappNumber', '34600111222')
                ->loadDeferredProps('default', fn ($prop) => $prop
                    ->has('cars.data', 1)
                    ->where('cars.data.0.id', $platformCar->id)));
    }

    public function test_old_marketplace_link_of_a_company_car_redirects_to_its_site(): void
    {
        $car = $this->publishedCar($this->company);

        $this->get('/marketplace/'.$car->id)
            ->assertStatus(301)
            ->assertRedirect(route('site.show', ['site' => 'importaciones-sur', 'car' => $car->id]));
    }

    public function test_company_car_page_uses_company_contact(): void
    {
        $car = $this->publishedCar($this->company);

        $this->get(route('site.show', ['site' => 'importaciones-sur', 'car' => $car->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/MarketplaceShow')
                ->where('car.id', $car->id)
                ->where('whatsappNumber', '34699888777')
                ->where('requestUrl', route('public.car-request.index', ['slug' => 'importaciones-sur', 'car' => $car->id])));
    }

    public function test_a_site_cannot_show_another_companys_car(): void
    {
        $platformCar = $this->publishedCar($this->platform);

        $this->get(route('site.show', ['site' => 'importaciones-sur', 'car' => $platformCar->id]))->assertNotFound();
    }

    public function test_compare_ignores_cars_from_other_companies(): void
    {
        $own = $this->publishedCar($this->company);
        $foreign = $this->publishedCar($this->platform);

        $this->get(route('site.compare', ['site' => 'importaciones-sur', 'ids' => $own->id.','.$foreign->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('cars', 1)->where('cars.0.id', $own->id));
    }

    public function test_platform_slug_redirects_to_marketplace(): void
    {
        $this->get('/web/operadora')->assertStatus(301)->assertRedirect(route('marketplace.index'));
    }

    public function test_site_is_hidden_when_disabled(): void
    {
        $this->company->update(['is_public' => false]);

        $this->get('/web/importaciones-sur')->assertNotFound();
        $this->get(route('public.car-request.index', ['slug' => 'importaciones-sur']))->assertNotFound();
    }

    public function test_site_is_hidden_when_trial_ended_without_plan(): void
    {
        config(['subscription.enforce' => true]);
        $this->company->forceFill(['trial_ends_at' => now()->subDay(), 'subscribed_at' => null])->save();

        $this->get('/web/importaciones-sur')->assertNotFound();
        $this->get(route('public.car-request.index', ['slug' => 'importaciones-sur']))->assertNotFound();
    }

    public function test_request_form_uses_company_context(): void
    {
        $this->get(route('public.car-request.index', ['slug' => 'importaciones-sur']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('marketplace.orgName', 'Importaciones Sur')
                ->where('marketplace.homeUrl', route('site.index', ['site' => 'importaciones-sur'])));
    }

    public function test_company_site_counts_its_own_visits(): void
    {
        $this->get('/web/importaciones-sur')->assertOk();
        $this->get('/web/importaciones-sur')->assertOk();

        $this->assertSame(2, (int) $this->company->fresh()->web_visits);
        $this->assertSame(0, (int) $this->platform->fresh()->web_visits);
    }

    public function test_platform_credit_is_hidden_only_with_custom_branding(): void
    {
        $this->get('/web/importaciones-sur')
            ->assertInertia(fn ($page) => $page->where('marketplace.platformCredit', true));

        $this->company->update(['plan' => 'enterprise']);

        $this->get('/web/importaciones-sur')
            ->assertInertia(fn ($page) => $page->where('marketplace.platformCredit', false));
    }

    public function test_owner_turns_on_the_site_and_chooses_its_address(): void
    {
        $org = Organization::factory()->create(['name' => 'Nueva', 'is_public' => false, 'trial_ends_at' => now()->addDays(5)]);
        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->patch(route('organization.update', $org), ['name' => 'Nueva', 'is_public' => 1, 'slug' => 'nueva-motor'])
            ->assertRedirect();

        $org->refresh();
        $this->assertTrue($org->is_public);
        $this->assertSame('nueva-motor', $org->slug);
        $this->get('/web/nueva-motor')->assertOk();
    }

    public function test_site_address_must_be_valid_unique_and_not_reserved(): void
    {
        $org = Organization::factory()->create(['name' => 'Nueva']);
        $owner = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        foreach (['importaciones-sur', 'marketplace', 'Con Espacios', 'ab'] as $slug) {
            $this->actingAs($owner)
                ->patch(route('organization.update', $org), ['name' => 'Nueva', 'slug' => $slug])
                ->assertSessionHasErrors('slug');
        }
    }

    public function test_panel_shares_the_users_site_url(): void
    {
        $user = User::factory()->create(['organization_id' => $this->company->id, 'role' => 'owner']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('publicSite.url', route('site.index', ['site' => 'importaciones-sur']))
                ->where('publicSite.carUrlBase', route('site.index', ['site' => 'importaciones-sur']).'/coche'));
    }

    public function test_cost_breakdown_uses_the_company_fees(): void
    {
        $this->company->update(['company' => ['whatsapp' => '699888777', 'fee_base' => '900']]);
        $car = $this->publishedCar($this->company, ['purchase_price' => 45000]);

        $this->get(route('site.show', ['site' => 'importaciones-sur', 'car' => $car->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('costBreakdown.honorarios', 900)
                ->where('orgBrand.fees', ['base' => 900, 'premium' => 900, 'threshold' => 30000]));
    }

    public function test_cost_breakdown_keeps_the_default_scale_without_company_fees(): void
    {
        $car = $this->publishedCar($this->platform, ['purchase_price' => 45000]);

        $this->get('/marketplace/'.$car->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('costBreakdown.honorarios', 1500));
    }
}
