<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auditoría 3 (#12) — índice para el selector de plataformas del dashboard KPIs
     * (distinct sobre cierres.plataforma scoped por organización).
     */
    public function up(): void
    {
        Schema::table('cierres', function (Blueprint $table) {
            $table->index(['organization_id', 'plataforma'], 'cierres_org_plataforma_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cierres', function (Blueprint $table) {
            $table->dropIndex('cierres_org_plataforma_idx');
        });
    }
};
