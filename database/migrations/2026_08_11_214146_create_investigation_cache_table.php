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
        Schema::create('investigation_cache', function (Blueprint $table) {
            $table->id();
            $table->string('clave_modelo')->unique(); // ej: "opel-astra-280cv-gasolina"
            $table->string('marca');
            $table->string('modelo');
            $table->integer('potencia')->nullable(); // CV
            $table->string('combustible')->nullable();
            $table->json('aspectos'); // {problemas_comunes: {hallazgo, fuente, valoracion, fecha}, ...}
            $table->timestamps();

            $table->index(['marca', 'modelo', 'potencia', 'combustible']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investigation_cache');
    }
};
