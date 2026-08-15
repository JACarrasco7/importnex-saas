<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda la descripción original del anuncio (DE) junto a la traducida,
     * para mostrarlas juntas en la ficha (punto 8 del rediseño).
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->text('original_description')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('original_description');
        });
    }
};
