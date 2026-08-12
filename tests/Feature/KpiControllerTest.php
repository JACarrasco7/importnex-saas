<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Cierre;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_kpis_page_loads_with_empty_state(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->actingAs($user);
        $this->withoutVite();

        $response = $this->get(route('kpis.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Kpis/Index')
            ->where('kpis.precision_veredictos.valor', null)
            ->where('cierres', [])
        );
    }

    public function test_kpis_calculates_precision_and_sells(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->actingAs($user);
        $this->withoutVite();

        $mes = now()->format('Y-m-d');

        // 2 veredictos "Comprar", 1 vendido y 1 no vendido → precisión 50%
        Cierre::create([
            'organization_id' => $org->id,
            'coche_id' => 'opel-astra-opc-2012-38347146649056',
            'fecha_investigacion' => now()->subDays(2)->format('Y-m-d'),
            'veredicto' => 'Comprar',
            'precio_objetivo' => 10000,
            'fecha_venta' => now()->addDays(8)->format('Y-m-d'),
            'precio_final' => 10500,
            'dias_hasta_venta' => 10,
            'estado' => 'vendido',
        ]);
        Cierre::create([
            'organization_id' => $org->id,
            'coche_id' => 'opel-astra-gtc-2013-4477881122',
            'fecha_investigacion' => now()->subDays(1)->format('Y-m-d'),
            'veredicto' => 'Comprar si baja',
            'precio_objetivo' => 9000,
            'estado' => 'no_vendido',
        ]);

        $response = $this->get(route('kpis.index', ['periodo' => now()->format('Y-m')]));
        $response->assertInertia(fn ($page) => $page
            ->where('kpis.precision_veredictos.valor', 50)
            ->where('kpis.tasa_falsos_positivos.valor', 50)
            ->where('kpis.tiempo_hasta_venta.valor', 10)
            ->where('kpis.desviacion_precio.valor', 5)
            ->where('cierres.0.estado', 'no_vendido')
            ->where('cierres.1.estado', 'vendido')
            ->where('cierres.1.desviacion_pct', 5)
        );
    }

    public function test_kpis_isolated_by_organization(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org1->id, 'role' => 'owner']);

        $this->actingAs($user);
        $this->withoutVite();

        // Cierre vendido de org2 no debe contar
        Cierre::create([
            'organization_id' => $org2->id,
            'coche_id' => 'bmw-320d-2018-5566778899',
            'fecha_investigacion' => now()->format('Y-m-d'),
            'veredicto' => 'Comprar',
            'precio_objetivo' => 10000,
            'fecha_venta' => now()->format('Y-m-d'),
            'precio_final' => 10000,
            'estado' => 'vendido',
        ]);

        $response = $this->get(route('kpis.index', ['periodo' => now()->format('Y-m')]));
        $response->assertInertia(fn ($page) => $page
            ->where('kpis.precision_veredictos.valor', null)
            ->where('cierres', [])
        );
    }

    public function test_kpis_rejects_invalid_periodo(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->actingAs($user);
        $this->withoutVite();

        $response = $this->get(route('kpis.index', ['periodo' => 'not-a-date']));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('periodo', now()->format('Y-m')));
    }

    public function test_kpis_filters_by_brand(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->actingAs($user);
        $this->withoutVite();

        $mes = now()->format('Y-m-d');

        $bmw = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'BMW',
            'model' => '320d',
        ]);
        $opel = Car::factory()->create([
            'organization_id' => $org->id,
            'brand' => 'Opel',
            'model' => 'Astra',
        ]);

        // Cierre BMW vinculado al car_id; Opel solo por coche_id string
        Cierre::create([
            'organization_id' => $org->id,
            'coche_id' => 'bmw-320d-2018-5566778899',
            'car_id' => $bmw->id,
            'brand' => 'BMW',
            'model' => '320d',
            'fecha_investigacion' => $mes,
            'veredicto' => 'Comprar',
            'precio_objetivo' => 12000,
            'fecha_venta' => now()->format('Y-m-d'),
            'precio_final' => 12500,
            'dias_hasta_venta' => 5,
            'estado' => 'vendido',
        ]);
        Cierre::create([
            'organization_id' => $org->id,
            'coche_id' => 'opel-astra-opc-2012-38347146649056',
            'brand' => 'Opel',
            'model' => 'Astra',
            'fecha_investigacion' => $mes,
            'veredicto' => 'Comprar',
            'precio_objetivo' => 9000,
            'estado' => 'no_vendido',
        ]);

        $response = $this->get(route('kpis.index', [
            'periodo' => now()->format('Y-m'),
            'brand' => 'BMW',
        ]));

        $response->assertInertia(fn ($page) => $page
            ->where('cierres.0.estado', 'vendido')
            ->where('cierres.0.brand', 'BMW')
            ->where('marcas', ['BMW', 'Opel'])
        );
    }

    public function test_kpis_filters_by_pais(): void
    {
        $org = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'owner']);

        $this->actingAs($user);
        $this->withoutVite();

        $mes = now()->format('Y-m-d');

        Cierre::create([
            'organization_id' => $org->id,
            'coche_id' => 'bmw-320d-2018-5566778899',
            'fecha_investigacion' => $mes,
            'veredicto' => 'Comprar',
            'precio_objetivo' => 12000,
            'plataforma' => 'mobile.de',
            'estado' => 'pendiente',
        ]);
        Cierre::create([
            'organization_id' => $org->id,
            'coche_id' => 'opel-astra-opc-2012-38347146649056',
            'fecha_investigacion' => $mes,
            'veredicto' => 'Comprar',
            'precio_objetivo' => 9000,
            'plataforma' => 'Wallapop',
            'estado' => 'pendiente',
        ]);

        $response = $this->get(route('kpis.index', [
            'periodo' => now()->format('Y-m'),
            'pais' => 'mobile.de',
        ]));

        $response->assertInertia(fn ($page) => $page
            ->where('cierres.0.plataforma', 'mobile.de')
            ->where('paises', ['Wallapop', 'mobile.de'])
        );
    }
}
