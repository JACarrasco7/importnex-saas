<?php

namespace Tests\Feature\Audit;

use App\Models\Car;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoría de venta (6.7): sin honorarios propios en el coche, el contrato y
 * el encargo usan la escala de SU empresa, no un importe fijo de otra.
 */
class CompanyDefaultFeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_fee_follows_the_company_scale(): void
    {
        $org = Organization::factory()->create(['company' => ['fee_base' => '900', 'fee_premium' => '1300', 'fee_threshold' => '25000']]);

        $cheap = Car::factory()->create(['organization_id' => $org->id, 'purchase_price' => 12000, 'professional_fees' => 0]);
        $dear = Car::factory()->create(['organization_id' => $org->id, 'purchase_price' => 26000, 'professional_fees' => 0]);

        $this->assertSame(900.0, $cheap->defaultProfessionalFee());
        $this->assertSame(1300.0, $dear->defaultProfessionalFee());
    }

    public function test_default_fee_uses_the_standard_scale_without_company_fees(): void
    {
        $org = Organization::factory()->create(['company' => []]);

        $this->assertSame(1000.0, Car::factory()->create(['organization_id' => $org->id, 'purchase_price' => 15000, 'professional_fees' => 0])->defaultProfessionalFee());
        $this->assertSame(1500.0, Car::factory()->create(['organization_id' => $org->id, 'purchase_price' => 40000, 'professional_fees' => 0])->defaultProfessionalFee());
    }
}
