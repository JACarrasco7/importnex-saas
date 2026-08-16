<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca de tiempo de la última verificación con IA. Permite saber cuándo
     * se generó el `ai_analysis_json` sin depender de cambiar el status.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->timestamp('ai_verified_at')->nullable()->after('ai_analysis_json');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('ai_verified_at');
        });
    }
};
