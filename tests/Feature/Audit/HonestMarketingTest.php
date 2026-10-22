<?php

namespace Tests\Feature\Audit;

use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoría de venta (D2/D5): la portada, los precios y el registro solo
 * prometen lo que el producto hace. Los planes salen de config/subscription.php,
 * que es lo que cobra Stripe y lo que limita la app.
 */
class HonestMarketingTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_plans_come_from_the_billing_config(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Welcome')
                ->has('plans', count(config('subscription.plans')))
                ->where('plans.0.key', 'starter')
                ->where('plans.0.price', config('subscription.plans.starter.price'))
                ->where('plans.0.limits.cars', config('subscription.plans.starter.cars_limit'))
                ->where('trialDays', config('subscription.trial_days'))
                ->where('exampleUrl', null));
    }

    public function test_landing_links_the_operator_site_as_example_when_published(): void
    {
        Organization::factory()->create(['is_public' => true, 'is_owner' => true]);

        $this->get('/')->assertInertia(fn ($page) => $page->where('exampleUrl', route('marketplace.index')));
    }

    public function test_pricing_page_shows_real_limits_without_stripe_ids(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/PricingPublic')
                ->where('plans.1.key', 'pro')
                ->where('plans.1.limits.users', config('subscription.plans.pro.users_limit'))
                ->where('plans.2.features.custom_branding', true)
                ->missing('plans.0.stripe_price_id'));
    }

    public function test_plans_no_longer_sell_ai_credits(): void
    {
        foreach (config('subscription.plans') as $plan) {
            $this->assertArrayNotHasKey('ai_credits', $plan['features']);
            $this->assertTrue($plan['features']['ai_own_key']);
            $this->assertTrue($plan['features']['claude_skill']);
        }
    }

    public function test_public_texts_have_no_unprovable_claims(): void
    {
        $spanish = file_get_contents(resource_path('js/i18n/es.js'));
        $guest = file_get_contents(resource_path('js/Layouts/GuestLayout.vue'));
        $landing = file_get_contents(resource_path('js/Pages/Welcome.vue'));

        foreach (['SLA 99', 'Account manager', 'Soporte dedicado 24/7', '90% de accuracy', 'inventario de 200 vehículos', 'me ahorra 3 horas', '80% de ahorro', '3x rotación', 'Soporte por email y chat'] as $claim) {
            $this->assertStringNotContainsString($claim, $spanish, "Texto sin respaldo: {$claim}");
        }
        foreach (['>80%<', '>3x<', '>24/7<'] as $claim) {
            $this->assertStringNotContainsString($claim, $guest);
        }
        // Las cifras de «clientes» del bloque final (vehículos gestionados,
        // margen medio…) eran inventadas; los mockups de pantallas sí llevan datos de ejemplo.
        foreach (['cta_metric_vehicles', 'cta_metric_margin', '€18k'] as $claim) {
            $this->assertStringNotContainsString($claim, $landing);
        }
    }
}
