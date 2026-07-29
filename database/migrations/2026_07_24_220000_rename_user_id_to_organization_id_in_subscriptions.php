<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // Renombrar user_id a organization_id para compatibilidad con Organization::class
            $table->renameColumn('user_id', 'organization_id');

            // Actualizar índice compuesto
            $table->dropIndex('subscriptions_user_id_stripe_status_index');
            $table->index(['organization_id', 'stripe_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('subscriptions_organization_id_stripe_status_index');
            $table->index(['user_id', 'stripe_status']);
            $table->renameColumn('organization_id', 'user_id');
        });
    }
};
