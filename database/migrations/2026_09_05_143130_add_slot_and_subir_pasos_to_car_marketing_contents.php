<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reglas del nuevo esquema (04-sep-2026):
     *
     *  - Redes sociales (instagram, tiktok, facebook): 3 publicaciones + 3 stories por canal.
     *    Se distingue con la columna `kind` ('post' | 'story') y la posición `slot` (1..3).
     *    Unique compuesto: (car_id, channel, kind, slot).
     *
     *  - Portales web (milanuncios, coches_net, wallapop, facebook marketplace):
     *    1 ficha única reutilizable para los 4 portales. Se distingue con
     *    `kind='ad'` y `slot=1` (slot es obligatorio pero no aporta info aquí).
     *    Mismo unique: (car_id, channel, kind, slot).
     *
     *  - Migración retrocompatible:
     *    · Filas existentes (sin kind/slot) se migran a kind='ad' slot=1.
     *    · Para los canales de red social se respeta el unique antiguo
     *      (car_id, channel) mapeando a (kind='post', slot=1).
     *    · El unique viejo se ELIMINA; se crea el nuevo.
     */
    public function up(): void
    {
        // 1) Añadir columnas nuevas (nullable temporalmente para backfill).
        Schema::table('car_marketing_contents', function (Blueprint $table) {
            $table->string('kind', 16)->nullable()->after('channel');
            $table->unsignedTinyInteger('slot')->nullable()->after('kind');
            $table->text('subir_pasos')->nullable()->after('photo_tips');
        });

        // 2) Backfill: filas existentes → kind='ad', slot=1 (portales web legacy)
        //    EXCEPTO si channel es de red social (instagram/tiktok) → kind='post', slot=1.
        //    (chunkById ya ordena por id internamente — no hace falta orderBy.)
        DB::table('car_marketing_contents')
            ->whereNull('kind')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $isSocial = in_array($row->channel, ['instagram', 'tiktok'], true);
                    DB::table('car_marketing_contents')
                        ->where('id', $row->id)
                        ->update([
                            'kind' => $isSocial ? 'post' : 'ad',
                            'slot' => 1,
                        ]);
                }
            });

        // 3) Quitar nullability: kind y slot son obligatorios desde ahora.
        Schema::table('car_marketing_contents', function (Blueprint $table) {
            $table->string('kind', 16)->default('ad')->change();
            $table->unsignedTinyInteger('slot')->default(1)->change();
        });

        // 4) Quitar unique viejo (car_id, channel); crear el nuevo (car_id, channel, kind, slot).
        Schema::table('car_marketing_contents', function (Blueprint $table) {
            $table->dropUnique(['car_id', 'channel']);
            $table->unique(['car_id', 'channel', 'kind', 'slot'], 'cmc_car_channel_kind_slot_unique');
        });
    }

    public function down(): void
    {
        Schema::table('car_marketing_contents', function (Blueprint $table) {
            $table->dropUnique('cmc_car_channel_kind_slot_unique');
            $table->unique(['car_id', 'channel']);
            $table->dropColumn(['kind', 'slot', 'subir_pasos']);
        });
    }
};
