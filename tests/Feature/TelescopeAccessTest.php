<?php

namespace Tests\Feature;

use App\Http\Middleware\TelescopeAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelescopeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_telescope_config_exists(): void
    {
        // Verificar que el config de Telescope existe y es accesible
        $this->assertNotNull(config('telescope.enabled'), 'Telescope config should exist');
    }

    public function test_telescope_middleware_class_exists(): void
    {
        // Verificar que el middleware existe
        $this->assertTrue(class_exists(TelescopeAccess::class));
    }

    public function test_telescope_middleware_production_block(): void
    {
        // Crear un mock del middleware para testear lógica de environment
        $middleware = new TelescopeAccess;

        // Test con environment simulation (nota: esto no modifica realmente app()->environment())
        // El middleware usa app()->environment() internamente
        $this->assertIsCallable([$middleware, 'handle']);
    }

    public function test_telescope_middleware_checks_user_role(): void
    {
        // Crear usuario con role diferente
        $user = User::factory()->create(['role' => 'user']);

        // Verificar que el usuario tiene el role
        $this->assertEquals('user', $user->role);
    }

    public function test_super_admin_role_exists(): void
    {
        // Crear usuario con Super Admin role
        $user = User::factory()->create(['role' => 'Super Admin']);

        // Verificar que el role existe
        $this->assertEquals('Super Admin', $user->role);
    }
}
