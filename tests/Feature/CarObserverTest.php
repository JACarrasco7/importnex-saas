<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarChecklist;
use App\Models\Organization;
use App\Models\User;
use App\Support\CarChecklistDefinitions;
use App\Support\CarDocumentDefinitions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_car_seeds_checklist_with_six_milestones(): void
    {
        $org = $this->makeOrg();

        $car = Car::factory()->create(['organization_id' => $org->id]);

        $milestones = $car->checklists()->milestones()->get();
        $this->assertCount(6, $milestones);

        $keys = $milestones->pluck('item_key')->toArray();
        $this->assertContains('deposit_paid', $keys);
        $this->assertContains('transport_contracted', $keys);
        $this->assertContains('coc_ordered', $keys);
        $this->assertContains('itv_passed', $keys);
        $this->assertContains('iedmt_paid', $keys);
        $this->assertContains('registered', $keys);
    }

    public function test_creating_car_seeds_inspection_points(): void
    {
        $org = $this->makeOrg();

        $car = Car::factory()->create(['organization_id' => $org->id]);

        $inspections = $car->checklists()->inspections()->get();
        $this->assertSame(80, $inspections->count());
    }

    public function test_creating_car_seeds_seventeen_document_slots(): void
    {
        $org = $this->makeOrg();

        $car = Car::factory()->create(['organization_id' => $org->id]);

        $this->assertSame(17, $car->documents()->count());
        $this->assertSame(17, $car->documents()->where('status', 'pending')->count());
    }

    public function test_traffic_light_stays_unchanged_when_no_market_avg(): void
    {
        $org = $this->makeOrg();

        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'traffic_light' => 'amber',
            'purchase_price' => 10000,
            'market_avg' => null,
        ]);

        $this->assertSame('amber', $car->fresh()->traffic_light);
    }

    public function test_traffic_light_becomes_green_when_total_below_market_avg(): void
    {
        $org = $this->makeOrg();

        // co2=119 → IEDMT=0. Total=10000+1000+200+300+100+1500=13100.
        // market_avg=20000 → ratio=0.655 → green.
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'year' => '01/2024',
            'purchase_price' => 10000,
            'transport' => 1000,
            'itv_fee' => 200,
            'coc_fee' => 300,
            'dgt_fees' => 100,
            'professional_fees' => 1500,
            'co2' => 119,
            'new_price' => 20000,
            'manual_tax_base' => 0,
            'boe_confirmed' => true,
            'market_avg' => 20000,
        ]);

        $this->assertSame('green', $car->fresh()->traffic_light);
    }

    public function test_traffic_light_becomes_amber_when_within_five_percent(): void
    {
        $org = $this->makeOrg();

        // IEDMT=0. Total=16000+1000+200+300+100+1500=19100.
        // market_avg=18200 → ratio=1.05 → amber (en el límite, <=1.05).
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'year' => '01/2024',
            'purchase_price' => 16000,
            'transport' => 1000,
            'itv_fee' => 200,
            'coc_fee' => 300,
            'dgt_fees' => 100,
            'professional_fees' => 1500,
            'co2' => 119,
            'new_price' => 20000,
            'manual_tax_base' => 0,
            'boe_confirmed' => true,
            'market_avg' => 18200,
        ]);

        $this->assertSame('amber', $car->fresh()->traffic_light);
    }

    public function test_traffic_light_becomes_red_when_above_five_percent(): void
    {
        $org = $this->makeOrg();

        // IEDMT=0. Total=25000+2000+400+500+200+2000=30100.
        // market_avg=15000 → ratio=2.01 → red.
        $car = Car::factory()->create([
            'organization_id' => $org->id,
            'year' => '01/2024',
            'purchase_price' => 25000,
            'transport' => 2000,
            'itv_fee' => 400,
            'coc_fee' => 500,
            'dgt_fees' => 200,
            'professional_fees' => 2000,
            'co2' => 119,
            'new_price' => 20000,
            'manual_tax_base' => 0,
            'boe_confirmed' => true,
            'market_avg' => 15000,
        ]);

        $this->assertSame('red', $car->fresh()->traffic_light);
    }

    public function test_completed_checklist_milestone_does_not_get_unmarked_when_doc_received(): void
    {
        $org = $this->makeOrg();
        $car = Car::factory()->create(['organization_id' => $org->id]);

        CarChecklist::where('car_id', $car->id)
            ->where('item_key', 'coc_ordered')
            ->update(['completed' => true, 'completed_at' => now()]);

        $cocDoc = $car->documents()->where('doc_key', 'coc')->first();
        $cocDoc->update(['status' => 'received']);

        $stillCompleted = CarChecklist::where('car_id', $car->id)
            ->where('item_key', 'coc_ordered')
            ->value('completed');
        $this->assertTrue((bool) $stillCompleted);
    }

    public function test_receiving_itv_document_marks_itv_milestone(): void
    {
        $org = $this->makeOrg();
        $car = Car::factory()->create(['organization_id' => $org->id]);

        $itvDoc = $car->documents()->where('doc_key', 'itv_import')->first();
        $itvDoc->update(['status' => 'received']);

        $milestoneCompleted = CarChecklist::where('car_id', $car->id)
            ->where('item_key', 'itv_passed')
            ->value('completed');
        $this->assertTrue((bool) $milestoneCompleted);
    }

    public function test_definitions_count_matches_seeded_rows(): void
    {
        $this->assertSame(86, app(CarChecklistDefinitions::class)->totalCount());
        $this->assertSame(17, app(CarDocumentDefinitions::class)->totalCount());
    }

    private function makeOrg(): Organization
    {
        $org = Organization::factory()->create();
        User::factory()->create(['organization_id' => $org->id]);
        return $org;
    }
}
