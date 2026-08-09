<?php

use App\Models\Car;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('enriched valuation fields exist in database', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'owner']);

    $car = Car::factory()->create([
        'organization_id' => $org->id,
        'research' => [
            'history' => [
                'finding' => '2 owners',
                'source_url' => 'https://example.com',
                'value' => 2,
                'date' => '2026-08-01',
            ],
        ],
        'pros' => [
            ['point' => 'Good maintenance', 'weight' => 'high'],
            ['point' => 'Low mileage', 'weight' => 'medium'],
        ],
        'cons' => [
            ['point' => 'High price', 'weight' => 'high'],
        ],
        'verdict' => 'Buy',
        'verdict_confidence' => 'high',
        'verdict_reasoning' => 'Well maintained with low mileage',
        'verdict_changes' => 'Price needs to drop by €500',
        'verdict_at' => now(),
        'market_avg' => 25000.00,
        'market_min' => 22000.00,
        'market_max' => 28000.00,
        'estimated_saving' => 3000.00,
        'research_source' => 'app',
        'schema_version' => 1,
    ]);

    $this->assertDatabaseHas('cars', [
        'id' => $car->id,
        'verdict' => 'Buy',
        'verdict_confidence' => 'high',
        'research_source' => 'app',
        'schema_version' => 1,
    ]);

    // Test JSON fields are properly stored
    $this->assertIsArray($car->research);
    $this->assertArrayHasKey('history', $car->research);
    $this->assertIsArray($car->pros);
    $this->assertIsArray($car->cons);
});

test('enriched valuation fields have proper casts', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'owner']);

    $car = Car::factory()->create([
        'organization_id' => $org->id,
        'research' => ['test' => 'data'],
        'pros' => [],
        'cons' => [],
        'market_avg' => '25000.00',
        'verdict_at' => '2026-08-01 12:00:00',
    ]);

    expect($car->research)->toBeArray();
    expect($car->pros)->toBeArray();
    expect($car->cons)->toBeArray();
    expect($car->market_avg)->toBeFloat();
    expect($car->market_avg)->toBe(25000.00);
    expect($car->verdict_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('verdict enum constraints', function () {
    expect(Car::VERDICTS)->toContain('Buy');
    expect(Car::VERDICTS)->toContain('Buy if price drops');
    expect(Car::VERDICTS)->toContain('Doubtful');
    expect(Car::VERDICTS)->toContain('Discard');
});

test('verdict confidence enum constraints', function () {
    expect(Car::VERDICT_CONFIDENCE)->toContain('high');
    expect(Car::VERDICT_CONFIDENCE)->toContain('medium');
    expect(Car::VERDICT_CONFIDENCE)->toContain('low');
});

test('research aspects constant', function () {
    expect(Car::RESEARCH_ASPECTS)->toBeArray();
    expect(Car::RESEARCH_ASPECTS)->toHaveCount(9);
});

test('getResearchGapsAttribute returns missing aspects', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'owner']);

    $car = Car::factory()->create([
        'organization_id' => $org->id,
        'research' => [
            'history' => ['finding' => '2 owners'],
            'maintenance' => ['finding' => 'Regular service'],
        ],
    ]);

    $gaps = $car->research_gaps;

    expect($gaps)->toBeArray();
    expect($gaps)->toHaveCount(7); // 9 total - 2 provided
    expect($gaps[0])->toBe('accident');
});

test('setResearchAspect adds or updates aspect', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'owner']);

    $car = Car::factory()->create([
        'organization_id' => $org->id,
        'research' => [],
    ]);

    $car->setResearchAspect('history', [
        'finding' => '3 owners',
        'source_url' => 'https://example.com',
        'value' => 3,
        'date' => '2026-08-01',
    ]);

    $car->save();

    $this->assertDatabaseHas('cars', [
        'id' => $car->id,
    ]);

    $car->refresh();
    expect($car->research['history']['finding'])->toBe('3 owners');
});

test('verdict_at null when verdict not issued', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'owner']);

    $car = Car::factory()->create([
        'organization_id' => $org->id,
        'verdict' => null,
    ]);

    expect($car->verdict_at)->toBeNull();
});

test('schema_version defaults to 1', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'owner']);

    $car = Car::factory()->create([
        'organization_id' => $org->id,
    ]);

    expect($car->schema_version)->toBe(1);
});

test('market data calculations', function () {
    $user = User::factory()->create();
    $org = Organization::factory()->create();
    $user->organizations()->attach($org->id, ['role' => 'owner']);

    $car = Car::factory()->create([
        'organization_id' => $org->id,
        'market_avg' => 25000.00,
        'market_min' => 22000.00,
        'market_max' => 28000.00,
        'estimated_saving' => 3000.00,
    ]);

    expect($car->market_avg)->toBe(25000.00);
    expect($car->market_min)->toBe(22000.00);
    expect($car->market_max)->toBe(28000.00);
    expect($car->estimated_saving)->toBe(3000.00);
});
