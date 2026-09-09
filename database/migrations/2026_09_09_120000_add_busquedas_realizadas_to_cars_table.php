<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * C2 auditoría 09-sep-2026: URLs de las búsquedas de mercado que originaron
 * la investigación. El panel admin las muestra en la pestaña Mercado de
 * Show.vue (módulo Sources -> "Búsquedas realizadas").
 *
 * Formato JSON:
 *   [
 *     { pais: 'DE'|'ES', portal: str, url: str, descripcion: str,
 *       params: { marca, modelo, anio_min, ... }, generado_el: ISO8601 },
 *     ...
 *   ]
 *
 * Viven en el campo JSON `mercado.busquedas_realizadas` del ZIP que
 * escribe la skill (empaquetar.py::generar_busquedas_realizadas). El
 * ingestor los extrae y los guarda aquí.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            // Nullable: los coches anteriores a la fecha no tendrán este campo.
            $table->json('busquedas_realizadas')->nullable()->after('comparables_list');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('busquedas_realizadas');
        });
    }
};
