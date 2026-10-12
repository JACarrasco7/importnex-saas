<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla persistente del mapa de mercado (datos_mercado.json) — skill estudio-mercado.
     * Una fila = un modelo/versión del mapa vigente. Upsert por slug.
     * NO duplica scouting_mercado/modelos_mercado (que quedan como histórico de chat-IA).
     */
    public function up(): void
    {
        Schema::create('market_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');

            // L1 — clave canónica + variantes de lookup
            $table->string('slug')->unique();
            $table->json('alias')->nullable();

            $table->string('categoria')->default('alta_rotacion'); // showstoppers|alta_rotacion|gemas_economicas
            $table->json('categorias_secundarias')->nullable();
            $table->string('modelo');
            $table->string('version')->nullable();

            // Oferta y precios
            $table->integer('oferta_de')->nullable();
            $table->integer('oferta_es')->nullable();
            $table->decimal('mediana_de', 10, 2)->nullable();
            $table->decimal('mediana_es', 10, 2)->nullable();
            $table->decimal('precio_desde_de', 10, 2)->nullable();
            $table->decimal('precio_desde_es', 10, 2)->nullable();
            $table->string('sello_precio_de')->nullable();
            $table->string('sello_precio_es')->nullable();

            // Hueco (bruto compatible con historial + neto con costes)
            $table->decimal('hueco_pct', 6, 2)->nullable();
            $table->decimal('hueco_neto_pct', 6, 2)->nullable();
            $table->integer('coste_importacion_estimado')->nullable();
            $table->integer('iedmt_estimado')->nullable();

            // Rotación separada DE/ES (L9) + demanda
            $table->integer('rotacion_dias_de')->nullable();
            $table->integer('rotacion_dias_es')->nullable();
            $table->string('rotacion_fuente')->nullable();
            $table->string('demanda_trends')->nullable();

            // Capa 1 — estadísticas (L8)
            $table->integer('transferencias_mes_dgt')->nullable();
            $table->integer('matriculaciones_kba')->nullable();

            // Clasificación
            $table->string('veredicto')->default('amarillo'); // verde|amarillo|rojo
            $table->string('mejor_mercado')->default('DE');   // DE|ES|paridad
            $table->string('fuente_medicion')->default('estudio'); // estudio|flujo_b|flujo_a|flujo_e_delta
            $table->integer('confianza_precio')->nullable();  // 1-5
            $table->boolean('oportunidad')->default(false);
            $table->boolean('pendiente_fase2')->default(false);
            $table->json('query_reejecutable')->nullable();

            $table->text('nota')->nullable();
            $table->decimal('tasacion_pro', 10, 2)->nullable();
            $table->date('refrescar_antes_de')->nullable();
            $table->string('schema_version')->default('1.1');

            $table->timestamps();

            $table->index(['categoria', 'veredicto']);
            $table->index('refrescar_antes_de');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_models');
    }
};
