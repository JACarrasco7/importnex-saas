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
        Schema::table('investigation_cache', function (Blueprint $table) {
            // Drop the old unique index on clave_modelo (will become a composite unique)
            $table->dropUnique('investigation_cache_clave_modelo_unique');

            // Add organization_id (multi-tenant isolation — CRITICAL security fix §10.3)
            $table->foreignId('organization_id')
                ->after('id')
                ->nullable()
                ->constrained()
                ->onDelete('cascade');

            // Add deleted_at for soft deletes (§10.5)
            $table->softDeletes();

            // New composite unique: clave is unique PER organization
            $table->unique(['organization_id', 'clave_modelo'], 'inv_cache_org_clave_unique');

            // Index for scoped tenant lookups
            $table->index(['organization_id', 'marca', 'modelo'], 'inv_cache_org_search_idx');
        });
    }

    public function down(): void
    {
        Schema::table('investigation_cache', function (Blueprint $table) {
            $table->dropUnique('inv_cache_org_clave_unique');
            $table->dropIndex('inv_cache_org_search_idx');

            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('organization_id');

            // Restore original unique index
            $table->unique('clave_modelo');
        });
    }
};
