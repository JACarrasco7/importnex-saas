<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Web pública por empresa: cada organización cuenta las visitas a la portada
 * de su web (`/web/{slug}`). El contador global `marketplace_visits` sigue
 * siendo el de `/marketplace`, la web de la organización operadora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedBigInteger('web_visits')->default(0)->after('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('web_visits');
        });
    }
};
