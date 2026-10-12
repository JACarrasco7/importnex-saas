<?php

namespace Tests\Unit;

use App\Models\Car;
use App\Support\CostBreakdown;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios de la logica de negocio del desglose de costes.
 * (12-oct-2026: honorarios 1k / 1.5k segun precio del coche.)
 */
class CostBreakdownTest extends TestCase
{
    public function test_honorarios_1000_when_car_below_30k(): void
    {
        $this->assertSame(1000, CostBreakdown::honorariosFor(25000.0));
        $this->assertSame(1000, CostBreakdown::honorariosFor(15000.0));
        $this->assertSame(1000, CostBreakdown::honorariosFor(1.0));
    }

    public function test_honorarios_1500_when_car_at_or_above_30k(): void
    {
        $this->assertSame(1500, CostBreakdown::honorariosFor(30000.0));
        $this->assertSame(1500, CostBreakdown::honorariosFor(32000.0));
        $this->assertSame(1500, CostBreakdown::honorariosFor(100000.0));
    }

    public function test_total_includes_all_components(): void
    {
        $car = new Car([
            'purchase_price' => 20000,
            'transport' => 1500,
            'itv_fee' => 300,
            'coc_fee' => 200,
            'dgt_fees' => 100,
        ]);
        $breakdown = CostBreakdown::forCar($car, 500.0);

        $this->assertSame(20000.0, $breakdown['car_price']);
        $this->assertSame(1500.0, $breakdown['transport']);
        $this->assertSame(600.0, $breakdown['processing']);
        $this->assertSame(500.0, $breakdown['iedmt']);
        $this->assertSame(1000, $breakdown['honorarios']);
        // 20000 + 1500 + 600 + 500 + 1000 = 23600
        $this->assertSame(23600.0, $breakdown['total']);
    }

    public function test_total_premium_honorarios_for_big_car(): void
    {
        $car = new Car([
            'purchase_price' => 40000,
            'transport' => 2000,
            'itv_fee' => 400,
            'coc_fee' => 250,
            'dgt_fees' => 150,
        ]);
        $breakdown = CostBreakdown::forCar($car, 1500.0);

        $this->assertSame(1500, $breakdown['honorarios']);
        // 40000 + 2000 + 800 + 1500 + 1500 = 45800
        $this->assertSame(45800.0, $breakdown['total']);
    }

    public function test_has_data_false_when_no_purchase_price(): void
    {
        $car = new Car(['purchase_price' => 0]);
        $breakdown = CostBreakdown::forCar($car);
        $this->assertFalse($breakdown['has_data']);
    }
}
