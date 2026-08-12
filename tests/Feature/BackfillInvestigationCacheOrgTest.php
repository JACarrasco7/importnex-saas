<?php

namespace Tests\Feature;

use App\Models\InvestigationCache;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillInvestigationCacheOrgTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_modify_records(): void
    {
        $org = Organization::factory()->create(['name' => 'JJ Import Motors']);
        InvestigationCache::factory()->create(['organization_id' => null, 'clave_modelo' => 'a4-2020-1']);

        $this->artisan('skill:backfill-investigation-cache')
            ->assertExitCode(0);

        $this->assertDatabaseHas('investigation_cache', [
            'clave_modelo' => 'a4-2020-1',
            'organization_id' => null,
        ]);
    }

    public function test_apply_fills_organization_id(): void
    {
        $org = Organization::factory()->create(['name' => 'JJ Import Motors']);
        InvestigationCache::factory()->create(['organization_id' => null, 'clave_modelo' => 'a4-2020-2']);
        InvestigationCache::factory()->create(['organization_id' => null, 'clave_modelo' => '320d-2018-3']);

        // --force salta la confirmación; --apply ejecuta el UPDATE
        $this->artisan('skill:backfill-investigation-cache', ['--apply' => true, '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('investigation_cache', [
            'clave_modelo' => 'a4-2020-2',
            'organization_id' => $org->id,
        ]);
        $this->assertDatabaseHas('investigation_cache', [
            'clave_modelo' => '320d-2018-3',
            'organization_id' => $org->id,
        ]);
    }

    public function test_apply_with_force_skips_confirmation(): void
    {
        $org = Organization::factory()->create(['name' => 'JJ Import Motors']);
        InvestigationCache::factory()->create(['organization_id' => null, 'clave_modelo' => 'a4-2020-4']);

        $this->artisan('skill:backfill-investigation-cache', ['--apply' => true, '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('investigation_cache', [
            'clave_modelo' => 'a4-2020-4',
            'organization_id' => $org->id,
        ]);
    }

    public function test_fails_when_org_not_found(): void
    {
        $this->artisan('skill:backfill-investigation-cache', ['--org' => 'NoExiste'])
            ->expectsOutputToContain('Organización no encontrada')
            ->assertExitCode(1);
    }

    public function test_no_records_means_nothing_to_do(): void
    {
        Organization::factory()->create(['name' => 'JJ Import Motors']);

        $this->artisan('skill:backfill-investigation-cache')
            ->expectsOutputToContain('No hay registros con organization_id NULL')
            ->assertExitCode(0);
    }
}
