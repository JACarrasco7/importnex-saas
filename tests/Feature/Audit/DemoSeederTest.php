<?php

namespace Tests\Feature\Audit;

use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Auditoría de venta (8.8): el seeder de demo crea una empresa ficticia, sin
 * datos ni contraseñas de JJ, y nunca toca una empresa real.
 */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_uses_a_fictitious_company_and_never_a_real_one(): void
    {
        config(['app.debug' => true]);
        $real = Organization::factory()->create(['name' => 'JJ Import Motors']);

        $this->seed(DemoDataSeeder::class);

        $demo = Organization::query()->where('slug', 'demo')->firstOrFail();
        $this->assertSame('Importaciones Demo', $demo->name);
        $this->assertTrue($demo->is_public);
        $this->assertSame(0, $real->cars()->count());
        $this->assertSame(0, User::query()->where('email', 'like', '%jjimportmotors%')->count());
        $this->assertSame(['operator', 'owner'], User::query()->where('organization_id', $demo->id)->orderBy('role')->pluck('role')->all());
        $this->assertGreaterThan(0, $demo->cars()->where('is_marketplace', true)->count());
    }
}
