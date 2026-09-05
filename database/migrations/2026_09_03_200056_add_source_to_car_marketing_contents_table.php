<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Origen del contenido de marketing: 'zip' (importado del paquete de
     * Claude), 'ai' (generado desde el panel) o NULL (legacy, creado antes
     * del 03-sep-2026). Permite distinguir quién escribió cada canal.
     */
    public function up(): void
    {
        Schema::table('car_marketing_contents', function (Blueprint $table) {
            $table->string('source', 16)->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('car_marketing_contents', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
