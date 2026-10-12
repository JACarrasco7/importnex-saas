<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bucle skill↔SaaS: veredicto corregido por humano + leads del catálogo + histórico de precios.
     */
    public function up(): void
    {
        // #1 — veredicto puede venir de la IA o ser corregido por humano (bucle de calibración)
        Schema::table('market_models', function (Blueprint $table) {
            $table->string('veredicto_fuente')->default('ia')->after('veredicto'); // ia|humano
        });

        // #2 — leads capturados desde el catálogo público /mercado ("Me interesa")
        Schema::create('market_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_model_id')->constrained('market_models')->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('nombre')->nullable();
            $table->string('contacto');
            $table->decimal('presupuesto', 10, 2)->nullable();
            $table->text('mensaje')->nullable();
            $table->string('estado')->default('nuevo'); // nuevo|contactado|cerrado|perdido
            $table->string('origen')->default('mercado'); // mercado|web|rrss
            $table->timestamps();

            $table->index(['estado', 'market_model_id']);
        });

        // #4 — histórico de precios por modelo (tendencia, time series)
        Schema::create('market_model_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_model_id')->constrained('market_models')->onDelete('cascade');
            $table->decimal('mediana_de', 10, 2)->nullable();
            $table->decimal('mediana_es', 10, 2)->nullable();
            $table->decimal('hueco_pct', 6, 2)->nullable();
            $table->decimal('hueco_neto_pct', 6, 2)->nullable();
            $table->string('fuente_medicion')->nullable();
            $table->date('medido_el');
            $table->timestamps();

            $table->index(['market_model_id', 'medido_el']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_model_history');
        Schema::dropIfExists('market_leads');
        Schema::table('market_models', function (Blueprint $table) {
            $table->dropColumn('veredicto_fuente');
        });
    }
};
