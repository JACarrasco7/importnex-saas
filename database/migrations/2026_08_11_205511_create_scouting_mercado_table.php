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
        Schema::create('scouting_mercado', function (Blueprint $table) {
            $table->id();
            $table->string('scouting_id')->unique();
            $table->integer('schema_version')->default(1);
            $table->string('flujo')->default('C');
            $table->timestamp('generado_el');
            $table->string('origen')->default('chat-ia');
            $table->text('preferencias_usuario')->nullable();
            $table->integer('modelos_escaneados')->default(0);
            $table->integer('modelos_con_hueco')->default(0);
            $table->integer('modelos_sin_hueco')->default(0);
            $table->text('resumen_ejecutivo')->nullable();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('modelos_mercado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scouting_mercado_id')->constrained('scouting_mercado')->onDelete('cascade');
            $table->string('modelo');
            $table->string('segmento')->nullable();
            $table->decimal('hueco_pct', 8, 2)->nullable();
            $table->integer('n_uds_de')->default(0);
            $table->decimal('mediana_es', 10, 2)->nullable();
            $table->decimal('mediana_de', 10, 2)->nullable();
            $table->integer('vendibilidad_estimada')->nullable();
            $table->string('recomendacion_aprox')->nullable();
            $table->string('mejor_anuncio_url')->nullable();
            $table->json('fuente_cobertura')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Auditoría 3 (#8) — drop modelos_mercado primero (FK a scouting_mercado)
        Schema::dropIfExists('modelos_mercado');
        Schema::dropIfExists('scouting_mercado');
    }
};
