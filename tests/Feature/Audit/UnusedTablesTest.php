<?php

namespace Tests\Feature\Audit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Auditoría de venta: sin tabla de testimonios ni columnas de Stripe en
 * usuarios (la facturación es de la empresa), y la migración se deshace.
 */
class UnusedTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_testimonials_and_user_billing_columns_are_gone(): void
    {
        $this->assertFalse(Schema::hasTable('testimonials'));
        foreach (['stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at'] as $column) {
            $this->assertFalse(Schema::hasColumn('users', $column), $column);
        }
        $this->assertTrue(Schema::hasColumn('organizations', 'stripe_id'));
        $this->assertNotNull(User::factory()->create()->id);
    }

    public function test_migration_can_be_rolled_back_and_repeated(): void
    {
        $migration = require database_path('migrations/2026_10_23_110000_drop_unused_testimonials_and_user_billing_columns.php');

        $migration->down();
        $this->assertTrue(Schema::hasTable('testimonials'));
        $this->assertTrue(Schema::hasColumn('users', 'stripe_id'));

        $migration->up();
        $this->assertFalse(Schema::hasTable('testimonials'));
        $this->assertFalse(Schema::hasColumn('users', 'pm_type'));
    }
}
