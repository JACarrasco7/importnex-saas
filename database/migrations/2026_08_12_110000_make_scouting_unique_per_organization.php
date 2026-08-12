<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auditoría 3 (#1) — Multi-tenant: scouting_id deja de ser unique global.
     *
     * El upsert de storeMercado() buscaba por scouting_id sin scoping de org,
     * con lo que un scouting de la org A podía reasignarse a la org B.
     * Ahora la unicidad es (organization_id, scouting_id).
     */
    public function up(): void
    {
        Schema::table('scouting_mercado', function (Blueprint $table) {
            $table->dropUnique('scouting_mercado_scouting_id_unique');
            $table->unique(['organization_id', 'scouting_id'], 'scouting_org_scouting_unique');
        });
    }

    public function down(): void
    {
        Schema::table('scouting_mercado', function (Blueprint $table) {
            $table->dropUnique('scouting_org_scouting_unique');
            $table->unique('scouting_id');
        });
    }
};
