<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §marketplace (25-sep-2026) — visibilidad pública y toggle desde la ficha.
 *
 * REGLA: manda el toggle del operador. Un coche marcado se publica.
 *
 * Exclusiones automáticas (hechos objetivos, no configuración):
 *   - La organización no es pública.
 *   - El coche ya no está en venta: `Delivered` (vendido/entregado) o
 *     `Discarded` (descartado).
 *
 * BUG QUE ESTE TEST BLOQUEA (25-sep-2026): antes se EXIGÍA `status=Delivered`,
 * es decir, solo se publicaban coches YA VENDIDOS. Como el default del status
 * es `Located`, la web pública salía siempre vacía. El test
 * `test_un_coche_en_located_marcado_si_aparece_en_la_web` reproduce ese caso.
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
            'status' => 'Located',
        ], $attrs));
    }

    private function admin(Organization $org): User
    {
        return User::factory()->create(['organization_id' => $org->id]);
    }

    // ── Car::marketplaceStatus() ────────────────────────────────────────────

    public function test_status_sin_marcar_no_es_visible(): void
    {
        $car = $this->car($this->org(true), ['is_marketplace' => false]);

        $status = $car->marketplaceStatus();

        $this->assertFalse($status['visible']);
        $this->assertCount(3, $status['checks']);
        $this->assertFalse($status['checks'][0]['ok'], 'is_marketplace debe estar en false');
    }

    /**
     * EL BUG: un coche recién localizado (default `Located`) marcado para
     * publicar DEBE aparecer. Antes no aparecía porque se exigía `Delivered`.
     */
    public function test_un_coche_en_located_marcado_es_visible(): void
    {
        $car = $this->car($this->org(true), [
            'is_marketplace' => true,
            'status' => 'Located',
        ]);

        $this->assertTrue(
            $car->marketplaceStatus()['visible'],
            'Un coche en Located con el toggle marcado debe ser visible (era el bug)'
        );
    }

    public function test_cualquier_estado_en_venta_es_publicable(): void
    {
        // Recorremos el workflow real del kanban menos los terminales.
        foreach (['Located', 'Valuing', 'Offered', 'Reserved', 'Purchased', 'In_transit', 'Processing'] as $status) {
            $car = $this->car($this->org(true), ['is_marketplace' => true, 'status' => $status]);

            $this->assertTrue(
                $car->marketplaceStatus()['visible'],
                "El estado {$status} debe poder publicarse"
            );
        }
    }

    public function test_coche_ya_entregado_no_se_publica(): void
    {
        $car = $this->car($this->org(true), ['is_marketplace' => true, 'status' => 'Delivered']);

        $this->assertFalse($car->marketplaceStatus()['visible'], 'un coche ya vendido no se publica');
    }

    public function test_coche_descartado_no_se_publica(): void
    {
        $car = $this->car($this->org(true), ['is_marketplace' => true, 'status' => 'Discarded']);

        $this->assertFalse($car->marketplaceStatus()['visible'], 'un coche descartado no se publica');
    }

    public function test_organizacion_privada_no_publica(): void
    {
        $car = $this->car($this->org(false), ['is_marketplace' => true, 'status' => 'Located']);

        $this->assertFalse($car->marketplaceStatus()['visible']);
    }

    /**
     * El veredicto YA NO es requisito: el operador decide con el toggle.
     * (Antes exigía Buy / Buy if price drops y bloqueaba publicar por sorpresa.)
     */
    public function test_el_veredicto_no_bloquea_la_publicacion(): void
    {
        foreach (['Buy', 'Buy if price drops', 'Doubtful', 'Discard', null] as $verdict) {
            $car = $this->car($this->org(true), [
                'is_marketplace' => true,
                'status' => 'Located',
                'verdict' => $verdict,
            ]);

            $this->assertTrue(
                $car->marketplaceStatus()['visible'],
                'El veredicto '.var_export($verdict, true).' no debe bloquear'
            );
        }
    }

    public function test_visible_cuando_cumple_todo(): void
    {
        $car = $this->car($this->org(true), ['is_marketplace' => true, 'status' => 'Offered']);

        $status = $car->marketplaceStatus();

        $this->assertTrue($status['visible']);
        $this->assertCount(3, array_filter($status['checks'], fn ($c) => $c['ok']));
    }

    // ── Integración: la web pública lo muestra ─────────────────────────────

    public function test_un_coche_en_located_marcado_si_aparece_en_la_web(): void
    {
        $org = $this->org(true);
        $this->car($org, ['is_marketplace' => true, 'status' => 'Located']);

        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('cars.data', 1));
    }

    public function test_un_coche_sin_marcar_no_aparece_en_la_web(): void
    {
        $org = $this->org(true);
        $this->car($org, ['is_marketplace' => false, 'status' => 'Located']);

        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('cars.data', 0));
    }

    public function test_un_coche_entregado_no_aparece_aunque_este_marcado(): void
    {
        $org = $this->org(true);
        $this->car($org, ['is_marketplace' => true, 'status' => 'Delivered']);

        $this->get(route('marketplace.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('cars.data', 0));
    }

    public function test_la_ficha_publica_del_coche_marcado_responde_200(): void
    {
        $org = $this->org(true);
        $car = $this->car($org, ['is_marketplace' => true, 'status' => 'Located']);

        $this->get(route('marketplace.show', $car->id))->assertOk();
    }

    public function test_la_ficha_publica_de_un_coche_sin_marcar_da_404(): void
    {
        $org = $this->org(true);
        $car = $this->car($org, ['is_marketplace' => false, 'status' => 'Located']);

        $this->get(route('marketplace.show', $car->id))->assertNotFound();
    }

    // ── Endpoint del toggle ────────────────────────────────────────────────

    public function test_toggle_publica_y_avisa(): void
    {
        $org = $this->org(true);
        $car = $this->car($org, ['status' => 'Located']);

        $response = $this->actingAs($this->admin($org))
            ->patch(route('cars.toggle-marketplace', $car->id), ['is_marketplace' => true]);

        $response->assertRedirect();
        $this->assertTrue($car->fresh()->is_marketplace);
        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, 'publicado'));
    }

    public function test_toggle_avisa_cuando_el_coche_esta_entregado(): void
    {
        $org = $this->org(true);
        $car = $this->car($org, ['status' => 'Delivered']);

        $response = $this->actingAs($this->admin($org))
            ->patch(route('cars.toggle-marketplace', $car->id), ['is_marketplace' => true]);

        $response->assertSessionHas('success', fn ($msg) => str_contains($msg, 'NO aparece'));
    }

    public function test_toggle_retira_del_marketplace(): void
    {
        $org = $this->org(true);
        $car = $this->car($org, ['is_marketplace' => true, 'status' => 'Located']);

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

    public function test_la_ficha_admin_expone_marketplace_status(): void
    {
        $org = $this->org(true);
        $car = $this->car($org, ['is_marketplace' => true, 'status' => 'Located']);

        $this->actingAs($this->admin($org))
            ->get(route('cars.show', $car->id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Cars/Show')
                ->where('derived.marketplace_status.visible', true)
                ->has('derived.marketplace_status.checks', 3));
    }
}
