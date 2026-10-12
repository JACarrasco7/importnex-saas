<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices para los filtros frecuentes del mapa de mercado:
     * categoria, segmento, rango_precio, tipo_cliente y veredicto.
     * Mejoran groupBy/where de reportes, admin y API pública.
     */
    public function up(): void
    {
        Schema::table('market_models', function (Blueprint $table) {
            $table->index(['organization_id', 'categoria'], 'market_models_org_categoria_idx');
            $table->index('segmento', 'market_models_segmento_idx');
            $table->index('rango_precio', 'market_models_rango_precio_idx');
            $table->index('tipo_cliente', 'market_models_tipo_cliente_idx');
            $table->index('veredicto', 'market_models_veredicto_idx');
        });
    }

    public function down(): void
    {
        Schema::table('market_models', function (Blueprint $table) {
            $table->dropIndex('market_models_org_categoria_idx');
            $table->dropIndex('market_models_segmento_idx');
            $table->dropIndex('market_models_rango_precio_idx');
            $table->dropIndex('market_models_tipo_cliente_idx');
            $table->dropIndex('market_models_veredicto_idx');
        });
    }
};
