<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §marketplace (25-sep-2026) — toggle de publicación desde la ficha.
 *
 * Un coche aparece en la web pública SOLO si cumple 4 condiciones:
 *   1. `is_marketplace` = true
 *   2. `status` = Delivered
 *   3. `verdict` IN (Buy, Buy if price drops)
 *   4. La organización tiene `is_public` = true
 *
 * Este test cubre el cálculo del estado (`Car::marketplaceStatus()`, fuente
 * única de verdad compartida con la ficha) y el endpoint del toggle
 * (`PATCH /cars/{car}/marketplace`), incluido el mensaje que avisa de lo que
 * falta cuando el coche está marcado pero aún no es visible — el fallo de UX
 * real: "lo marqué y no aparece".
 */
class CarMarketplaceToggleTest extends TestCase
{
    use RefreshDatabase;

    private function org(bool $public = true): Organization
    {
        // Nombre único: `organizations.name` es UNIQUE y varios tests crean
        // más de una organización en el mismo caso.
        return Organization::create([
            'name' => 'JJ Import Motors '.uniqid(),
            'is_public' => $public,
        ]);
    }

    private function car(Organization $org, array $attrs = []): Car
    {
        return Car::create(array_merge([
            'organization_id' => $org->id,
            'brand' => 'Audi',
            'model' => 'Q2',
            'year' => '2020',
            'fuel' => 'Gasolina',
            'transmission' => 'Manual',
        ], $attrs));
    }

    private function admin(Organization $org): User
    {
        return User::factory()->create(['organization_id' => $org->id]);
    }

    public function test_marketplace_status_devuelve_las_4_condiciones_en_falso_por_defecto(): void
    {
        // Org NO pública: así el coche recién creado falla las 4 condiciones.
        $car = $this->car($this->org(false));

        $status = $car->marketplaceStatus();

        $this->assertFalse($status['visible']);
        $this->assertCount(4, $status['checks']);
        foreach ($status['checks'] as $check) {
            $this->assertFalse($check['ok'], "La condición {$check['key']} debería estar en false");
        }
    }

    public function test_marketplace_status_visible_con_las_4_condiciones(): void
    {
        $car = $this->car($this->org(true), [
            'is_marketplace' => true,
            'status' => 'Delivered',
            'verdict' => 'Buy',
        ]);

        $status = $car->marketplaceStatus();

        $this->assertTrue($status['visible']);
        $this->assertCount(4, array_filter($status['checks'], fn ($c) => $c['ok']));
    }

    public function test_cada_condicion_rota_impide_la_visibilidad(): void
    {
        // 1. Sin is_marketplace
        $car = $this->car($this->org(true), ['status' => 'Delivered', 'verdict' => 'Buy']);
        $this->assertFalse($car->marketplaceStatus()['visible'], 'sin is_marketplace no es visible');

        // 2. Status distinto de Delivered
        $car = $this->car($this->org(true), ['is_marketplace' => true, 'status' => 'Purchased', 'verdict' => 'Buy']);
        $this->assertFalse($car->marketplaceStatus()['visible'], 'status != Delivered no es visible');

        // 3. Verdict negativo
        $car = $this->car($this->org(true), ['is_marketplace' => true, 'status' => 'Delivered', 'verdict' => 'Discard']);
        $this->assertFalse($car->marketplaceStatus()['visible'], 'verdict Discard no es visible');

        // 4. Organización no pública
        $car = $this->car($this->org(false), ['is_marketplace' => true, 'status' => 'Delivered', 'verdict' => 'Buy']);
        $this->assertFalse($car->marketplaceStatus()['visible'], 'organización no pública no es visible');
    }

    public function test_veredicto_buy_if_price_drops_tambien_publica(): void
    {
        $car = $this->car($this->org(true), [
            'is_marketplace' => true,
            'status' => 'Delivered',
            'verdict' => 'Buy if price drops',
        ]);

        $this->assertTrue($car->marketplaceStatus()['visible']);
    }

    public function test_toggle_activa_y_avisa_si_aun_no_es_visible(): void
    {
        $org = $this->org(true);
        $car = $this->car($org, ['status' => 'Purchased', 'verdict' => 'Doubtful']);

        $response = $this->actingAs($this->admin($org))
            ->patch(route('cars.toggle-marketplace', $car->id), ['is_marketplace' => true]);

        $response->assertRedirect();
        $this->assertTrue($car->fresh()->is_marketplace);
        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, 'NO aparece'));
    }

    public function test_toggle_avisa_publicado_cuando_cumple_todo(): void
    {
        $org = $this->org(true);
        $car = $this->car($org, ['status' => 'Delivered', 'verdict' => 'Buy']);

        $response = $this->actingAs($this->admin($org))
            ->patch(route('cars.toggle-marketplace', $car->id), ['is_marketplace' => true]);

        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, 'publicado'));
        $this->assertTrue($car->fresh()->marketplaceStatus()['visible']);
    }

    public function test_toggle_desactiva_retira_del_marketplace(): void
    {
        $org = $this->org(true);
        $car = $this->car($org, [
            'is_marketplace' => true, 'status' => 'Delivered', 'verdict' => 'Buy',
        ]);

        $response = $this->actingAs($this->admin($org))
            ->patch(route('cars.toggle-marketplace', $car->id), ['is_marketplace' => false]);

        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, 'retirado'));
        $this->assertFalse($car->fresh()->is_marketplace);
    }

    public function test_toggle_valida_el_booleano(): void
    {
        $org = $this->org();
        $car = $this->car($org);

        $this->actingAs($this->admin($org))
            ->patch(route('cars.toggle-marketplace', $car->id), ['is_marketplace' => 'no-es-booleano'])
            ->assertSessionHasErrors('is_marketplace');
    }

    public function test_la_ficha_expone_marketplace_status(): void
    {
        $org = $this->org(true);
        $car = $this->car($org, [
            'is_marketplace' => true, 'status' => 'Delivered', 'verdict' => 'Buy',
        ]);

        $this->actingAs($this->admin($org))
            ->get(route('cars.show', $car->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cars/Show')
                ->where('derived.marketplace_status.visible', true)
                ->has('derived.marketplace_status.checks', 4));
    }
}
