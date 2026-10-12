<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columnas para que la tarea programada `marketplace:check-listings`
 * pueda registrar cuando se reviso un anuncio y por que se desactivo.
 *
 * - `marketplace_checked_at`: ultima vez que se comprobo la URL original.
 *   Util para el admin panel ("revisado por ultima vez: hace 3 horas").
 * - `marketplace_unavailable_reason`: motivo del auto-desactivado
 *   (`url_404`, `url_5xx`, `url_sold_keyword`, `url_timeout`, `url_other`).
 *   NULL significa que el anuncio sigue disponible o que nunca se ha
 *   revisado desde que se publico.
 *
 * Se persiste en la fila (no en una tabla aparte) para que el admin lo
 * vea en la ficha del coche junto al toggle `is_marketplace`, y para
 * no añadir JOINs al listado del marketplace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->timestamp('marketplace_checked_at')->nullable()->after('marketplace_views');
            $table->string('marketplace_unavailable_reason')->nullable()->after('marketplace_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['marketplace_checked_at', 'marketplace_unavailable_reason']);
        });
    }
};
