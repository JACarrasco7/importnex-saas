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
        // §3.5 — Registro de cierres (KPIs de negocio)
        // Mapea la estructura JSON de operaciones_cierre.md §15 a BD
        Schema::create('cierres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('coche_id'); // slug del coche, ej: "opel-astra-opc-2012-38347146649056"
            $table->foreignId('car_id')->nullable()->constrained()->onDelete('set null');
            $table->date('fecha_investigacion');
            $table->string('veredicto'); // "Comprar", "Comprar si baja...", "Dudoso", "Descartar"
            $table->decimal('precio_objetivo', 10, 2)->nullable();
            $table->date('fecha_venta')->nullable();
            $table->decimal('precio_final', 10, 2)->nullable();
            $table->string('cliente')->nullable();
            $table->string('plataforma')->nullable(); // Wallapop, Milanuncios, Coches.net, etc.
            $table->integer('dias_hasta_venta')->nullable();
            $table->text('comentario')->nullable();
            $table->enum('estado', ['vendido', 'no_vendido', 'pendiente'])->default('pendiente');
            $table->timestamps();
            $table->softDeletes();

            // Índices para consultas frecuentes (KPIs)
            $table->index(['organization_id', 'estado']);
            $table->index(['organization_id', 'fecha_investigacion']);
            $table->index(['organization_id', 'fecha_venta']);
            $table->index('coche_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres');
    }
};
