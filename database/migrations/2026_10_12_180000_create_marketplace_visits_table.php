<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Contador global de visitas al listado publico del marketplace.
 *
 * Tabla con UNA sola fila (singleton id=1) que mantiene el total
 * acumulado de visitas a GET /marketplace (no a la ficha individual;
 * eso ya lo cuenta cars.marketplace_views).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visits_count')->default(0);
            $table->timestamp('first_visited_at')->nullable();
            $table->timestamp('last_visited_at')->nullable();
            $table->timestamps();
        });

        // Singleton: forzamos id=1 para que MarketplaceVisit::current()
        // sea un lookup trivial. Si por accidente se borra la fila, el
        // modelo la recrea en el siguiente acceso.
        DB::table('marketplace_visits')->insertOrIgnore([
            'id' => 1,
            'visits_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_visits');
    }
};
