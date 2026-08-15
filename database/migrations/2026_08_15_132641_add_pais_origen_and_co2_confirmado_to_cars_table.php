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
        Schema::table('cars', function (Blueprint $table) {
            // §contrato — origen del anuncio ("Alemania" | "España"). Condiciona los costes en el informe.
            $table->string('pais_origen')->nullable()->after('city');

            // §contrato — flag: el CO₂ viene confirmado por COC/BOE (true) o es estimado (false/null).
            $table->boolean('co2_confirmado')->nullable()->after('co2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['pais_origen', 'co2_confirmado']);
        });
    }
};
