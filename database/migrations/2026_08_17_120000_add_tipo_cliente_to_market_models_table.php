<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Segmentación por TIPO DE CLIENTE (perfil de comprador objetivo) en market_models.
     */
    public function up(): void
    {
        Schema::table('market_models', function (Blueprint $table) {
            $table->string('tipo_cliente')->nullable()->after('rango_precio'); // primer_coche|familia|premium_imagen|deporte_ocio|diario_eficiencia|negocio_reventa|impacto_showstopper
            $table->json('tipos_cliente_secundarios')->nullable()->after('tipo_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('market_models', function (Blueprint $table) {
            $table->dropColumn(['tipo_cliente', 'tipos_cliente_secundarios']);
        });
    }
};
