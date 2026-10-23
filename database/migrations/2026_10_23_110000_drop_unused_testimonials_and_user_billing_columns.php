<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría de venta (8.x): la facturación (Cashier) cuelga de la empresa, no
 * del usuario, así que las columnas de Stripe de `users` nunca se usan; y la
 * tabla `testimonials` perdió su controlador y sus pantallas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('testimonials');

        if (Schema::hasColumn('users', 'stripe_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['stripe_id']);
            });
        }

        $columns = array_values(array_filter(
            ['stripe_id', 'pm_type', 'pm_last_four', 'trial_ends_at'],
            fn (string $column) => Schema::hasColumn('users', $column),
        ));
        if ($columns !== []) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'stripe_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('stripe_id')->nullable()->index();
                $table->string('pm_type')->nullable();
                $table->string('pm_last_four', 4)->nullable();
                $table->timestamp('trial_ends_at')->nullable();
            });
        }

        if (! Schema::hasTable('testimonials')) {
            Schema::create('testimonials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
                $table->string('author_name', 120);
                $table->string('author_role', 120)->nullable();
                $table->string('author_company', 120)->nullable();
                $table->text('content');
                $table->unsignedTinyInteger('rating')->default(5);
                $table->string('avatar_url', 500)->nullable();
                $table->string('car_purchased', 200)->nullable();
                $table->boolean('is_approved')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['organization_id', 'is_approved', 'sort_order']);
                $table->index(['organization_id', 'is_featured']);
            });
        }
    }
};
