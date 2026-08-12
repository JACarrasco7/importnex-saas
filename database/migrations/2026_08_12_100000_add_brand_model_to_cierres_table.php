<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * §2.4 (auditoría) — Denormaliza brand/model en cierres para KPIs por marca.
     *
     * La relación Cierre→Car via car_id es opcional y no siempre se resuelve
     * (el chat puede no haber creado el Car). Guardando brand/model en el cierre
     * el dashboard filtra por marca sin depender del JOIN con cars.
     */
    public function up(): void
    {
        Schema::table('cierres', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('car_id');
            $table->string('model')->nullable()->after('brand');
            $table->index(['organization_id', 'brand'], 'cierres_org_brand_idx');
        });
    }

    public function down(): void
    {
        Schema::table('cierres', function (Blueprint $table) {
            $table->dropIndex('cierres_org_brand_idx');
            $table->dropColumn(['brand', 'model']);
        });
    }
};
