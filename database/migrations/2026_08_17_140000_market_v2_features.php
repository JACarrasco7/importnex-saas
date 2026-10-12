<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanda v2: publicación configurable, foto de muestra, score de vendibilidad y pipeline de leads.
     */
    public function up(): void
    {
        Schema::table('market_models', function (Blueprint $table) {
            $table->boolean('publicar_en_catalogo')->default(false)->after('oportunidad');
            $table->string('foto_url')->nullable()->after('publicar_en_catalogo');
            $table->unsignedTinyInteger('vendibilidad')->nullable()->after('foto_url'); // 0-100
        });

        Schema::table('market_leads', function (Blueprint $table) {
            $table->string('nota')->nullable()->after('mensaje');
        });
    }

    public function down(): void
    {
        Schema::table('market_models', function (Blueprint $table) {
            $table->dropColumn(['publicar_en_catalogo', 'foto_url', 'vendibilidad']);
        });
        Schema::table('market_leads', function (Blueprint $table) {
            $table->dropColumn('nota');
        });
    }
};
