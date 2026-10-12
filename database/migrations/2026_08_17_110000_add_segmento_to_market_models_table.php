<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Segmentación del mapa de mercado: tipo de vehículo + banda de precio.
     * (El campo segmento ya existía en modelos_mercado; aquí lo añadimos a market_models.)
     */
    public function up(): void
    {
        Schema::table('market_models', function (Blueprint $table) {
            $table->string('segmento')->nullable()->after('categoria'); // compacto|suv|berlina|deportivo|familiar|urbano
            $table->string('rango_precio')->nullable()->after('segmento'); // 0-8k|8-14k|14-25k|25k+
        });
    }

    public function down(): void
    {
        Schema::table('market_models', function (Blueprint $table) {
            $table->dropColumn(['segmento', 'rango_precio']);
        });
    }
};
