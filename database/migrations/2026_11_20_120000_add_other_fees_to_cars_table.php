<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gastos de tramitación que no son ITV, COC, DGT ni honorarios:
 * gestoría, placas de exportación + seguro temporal del traslado y placas
 * españolas (`costes.gestoria` + `costes.otros` del ZIP de la skill).
 *
 * Antes la gestoría se sumaba a `professional_fees` (que la ficha pública
 * ignora porque pinta los honorarios aparte) y `otros` no se guardaba, así
 * que el total aproximado del cliente salía ~430 € por debajo del real.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->decimal('other_fees', 10, 2)->default(0)->after('professional_fees');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('other_fees');
        });
    }
};
