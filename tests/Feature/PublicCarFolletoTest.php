<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarPublicLink;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Folleto PDF retirado (06-sep-2026): JJ Import Motors no vende coches, solo
 * gestiona la compra (España + importación). El dossier público (/c/{token})
 * es ahora el ÚNICO documento para el cliente: informe completo (ficha +
 * veredicto + por qué + comparativa de mercado), sin folleto separado.
 *
 * Esta clase verifica que la retirada quedó completa: la ruta ya no existe
 * y el dossier no ofrece ningún enlace al folleto.
 */
class PublicCarFolletoTest extends TestCase
{
    use RefreshDatabase;

    public function test_folleto_route_no_longer_exists(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'BMW',
            'model' => '320d',
        ]);
        $link = CarPublicLink::generateFor($car);

        $this->get("/c/{$link->token}/folleto")->assertNotFound();
    }

    public function test_dossier_page_does_not_link_to_folleto(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'BMW',
            'model' => '320d',
        ]);
        $link = CarPublicLink::generateFor($car);

        $response = $this->get("/c/{$link->token}");

        $response->assertOk();
        $response->assertDontSee("/c/{$link->token}/folleto");
        $response->assertDontSee('Folleto PDF');
    }

    public function test_dossier_does_not_show_financing_or_test_drive_language(): void
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'BMW',
            'model' => '320d',
            'purchase_price' => 30000,
        ]);
        $link = CarPublicLink::generateFor($car);

        $response = $this->get("/c/{$link->token}");

        $response->assertOk();
        // Regla de oro: NO vendemos coches, gestionamos la compra. Nada de
        // financiación (no la ofrecemos) ni de "reserva tu prueba" (no es
        // stock propio para probar).
        $response->assertDontSee('Financiación');
        $response->assertDontSee('Reserva tu prueba');
        $response->assertDontSee('EN STOCK');
    }
}
