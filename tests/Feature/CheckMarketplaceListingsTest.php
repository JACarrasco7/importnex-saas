<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tests del comando `marketplace:check-listings` y del servicio
 * `ListingAvailabilityChecker`.
 *
 * Cubre el flujo completo (12-oct-2026):
 *  - 200 + sin keyword -> sigue activo, marca checked_at
 *  - 404 -> desactiva + alert + unavailable_reason=url_404
 *  - 200 con keyword "vendido" -> desactiva + alert + reason=url_sold_keyword
 *  - 5xx -> NO desactiva (transitorio)
 *  - timeout -> NO desactiva
 *  - 401/403 -> NO desactiva (anti-bot)
 *  - sin url_link -> se salta sin tocar nada
 *  - dry-run no persiste cambios
 *  - idempotente: dos pasadas seguidas no duplican alertas
 *  - reactivacion: si el anuncio vuelve a estar disponible, limpia el reason
 */
class CheckMarketplaceListingsTest extends TestCase
{
    use RefreshDatabase;

    private function makeCar(array $attrs = []): Car
    {
        $org = Organization::factory()->create(['is_public' => true, 'slug' => Str::random(6)]);

        return Car::factory()->create(array_merge([
            'organization_id' => $org->id,
            'is_marketplace' => true,
            'status' => 'Located',
            'verdict' => 'Buy',
            'purchase_price' => 18000,
            'url_link' => 'https://example.com/ad/1',
        ], $attrs));
    }

    public function test_200_without_sold_keyword_keeps_car_active(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html>Coche disponible, contacta con nosotros.</html>', 200),
        ]);

        $car = $this->makeCar();
        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        $car->refresh();
        $this->assertTrue((bool) $car->is_marketplace);
        $this->assertNull($car->marketplace_unavailable_reason);
        $this->assertNotNull($car->marketplace_checked_at);
        $this->assertSame(0, Alert::where('reference_id', $car->id)->count());
    }

    public function test_404_deactivates_and_creates_alert(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Not Found', 404),
        ]);

        $car = $this->makeCar();
        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        $car->refresh();
        $this->assertFalse((bool) $car->is_marketplace);
        $this->assertSame('url_404', $car->marketplace_unavailable_reason);
        $this->assertNotNull($car->marketplace_checked_at);

        $alert = Alert::where('reference_id', $car->id)->first();
        $this->assertNotNull($alert);
        $this->assertSame('car_listing_unavailable', $alert->alert_type);
        $this->assertSame($car->organization_id, $alert->organization_id);
        $this->assertStringContainsString('404', $alert->message);
    }

    public function test_sold_keyword_deactivates_and_creates_alert(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html>Este anuncio ya no esta disponible. Ha sido vendido.</html>', 200),
        ]);

        $car = $this->makeCar();
        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        $car->refresh();
        $this->assertFalse((bool) $car->is_marketplace);
        $this->assertSame('url_sold_keyword', $car->marketplace_unavailable_reason);

        $alert = Alert::where('reference_id', $car->id)->first();
        $this->assertNotNull($alert);
        $this->assertStringContainsString('vendido', $alert->message);
    }

    public function test_sold_keyword_english_deactivates(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html>This listing is no longer available.</html>', 200),
        ]);

        $car = $this->makeCar();
        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        $car->refresh();
        $this->assertFalse((bool) $car->is_marketplace);
        $this->assertSame('url_sold_keyword', $car->marketplace_unavailable_reason);
    }

    public function test_sold_keyword_german_deactivates(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html>Das Inserat wurde entfernt. Das Fahrzeug wurde verkauft.</html>', 200),
        ]);

        $car = $this->makeCar();
        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        $car->refresh();
        $this->assertFalse((bool) $car->is_marketplace);
        $this->assertSame('url_sold_keyword', $car->marketplace_unavailable_reason);
    }

    public function test_5xx_does_no_t_deactivate(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Internal Server Error', 503),
        ]);

        $car = $this->makeCar();
        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        $car->refresh();
        $this->assertTrue((bool) $car->is_marketplace);
        $this->assertNull($car->marketplace_unavailable_reason);
        $this->assertSame(0, Alert::where('reference_id', $car->id)->count());
    }

    public function test_403_does_no_t_deactivate(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Forbidden', 403),
        ]);

        $car = $this->makeCar();
        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        $car->refresh();
        $this->assertTrue((bool) $car->is_marketplace);
        $this->assertSame(0, Alert::where('reference_id', $car->id)->count());
    }

    public function test_timeout_does_no_t_deactivate(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        $car = $this->makeCar();
        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        $car->refresh();
        $this->assertTrue((bool) $car->is_marketplace);
        $this->assertNull($car->marketplace_unavailable_reason);
    }

    public function test_car_without_url_link_is_skipped(): void
    {
        $car = $this->makeCar(['url_link' => null]);

        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        $car->refresh();
        $this->assertTrue((bool) $car->is_marketplace);
        $this->assertNull($car->marketplace_checked_at);
    }

    public function test_car_not_in_marketplace_is_skipped(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Not Found', 404),
        ]);

        $car = $this->makeCar(['is_marketplace' => false]);
        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        // No se reactiva solo por una comprobacion. El admin debe decidir.
        $car->refresh();
        $this->assertFalse((bool) $car->is_marketplace);
        $this->assertNull($car->marketplace_unavailable_reason);
    }

    public function test_dry_run_does_not_persist_changes(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Not Found', 404),
        ]);

        $car = $this->makeCar();
        $this->artisan('marketplace:check-listings --dry-run')->assertExitCode(0);

        $car->refresh();
        $this->assertTrue((bool) $car->is_marketplace);
        $this->assertNull($car->marketplace_unavailable_reason);
        $this->assertNull($car->marketplace_checked_at);
        $this->assertSame(0, Alert::where('reference_id', $car->id)->count());
    }

    public function test_two_runs_do_not_duplicate_alerts(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Not Found', 404),
        ]);

        $car = $this->makeCar();
        $this->artisan('marketplace:check-listings')->assertExitCode(0);
        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        $alerts = Alert::where('reference_id', $car->id)
            ->where('alert_type', 'car_listing_unavailable')
            ->count();
        $this->assertSame(1, $alerts);
    }

    public function test_reactivation_clears_unavailable_reason(): void
    {
        // El coche se marco como unavailable en una pasada anterior (404).
        // El admin lo ha reactivado a mano (is_marketplace=true) pero olvida
        // limpiar el reason. La siguiente pasada con 200 debe limpiarlo.
        $car = $this->makeCar();
        $car->update(['marketplace_unavailable_reason' => 'url_404']);

        Http::fake([
            'example.com/*' => Http::response('<html>Coche disponible</html>', 200),
        ]);
        $this->artisan('marketplace:check-listings')->assertExitCode(0);

        $car->refresh();
        $this->assertNull($car->marketplace_unavailable_reason);
        $this->assertTrue((bool) $car->is_marketplace);
    }

    public function test_resolved_alerts_do_not_block_new_alert_after_reactivation(): void
    {
        $car = $this->makeCar();

        // 1ra: 404 -> crea alerta, desactiva
        Http::fake(['example.com/*' => Http::response('Not Found', 404)]);
        $this->artisan('marketplace:check-listings')->assertExitCode(0);
        $alert1 = Alert::where('reference_id', $car->id)->first();
        $this->assertNotNull($alert1);
        $alert1->markAsResolved();

        // El admin reactiva el coche
        $car->refresh();
        $car->is_marketplace = true;
        $car->marketplace_unavailable_reason = null;
        $car->save();

        // 2da: 404 de nuevo -> se crea nueva alerta
        $this->artisan('marketplace:check-listings')->assertExitCode(0);
        $alerts = Alert::where('reference_id', $car->id)
            ->where('alert_type', 'car_listing_unavailable')
            ->get();
        // Solo 1 activa (la primera resuelta NO cuenta, pero la nueva si)
        $this->assertSame(1, $alerts->where('resolved', false)->count());
    }

    public function test_org_filter_only_processes_target_org(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Not Found', 404),
        ]);

        $org1 = Organization::factory()->create(['is_public' => true, 'slug' => 'a']);
        $org2 = Organization::factory()->create(['is_public' => true, 'slug' => 'b']);

        $car1 = Car::factory()->create(['organization_id' => $org1->id, 'is_marketplace' => true, 'url_link' => 'https://example.com/1', 'status' => 'Located']);
        $car2 = Car::factory()->create(['organization_id' => $org2->id, 'is_marketplace' => true, 'url_link' => 'https://example.com/2', 'status' => 'Located']);

        $this->artisan("marketplace:check-listings --org={$org1->id}")->assertExitCode(0);

        $car1->refresh();
        $car2->refresh();
        $this->assertFalse((bool) $car1->is_marketplace);
        $this->assertTrue((bool) $car2->is_marketplace);
    }
}
