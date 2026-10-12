<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reglas del nuevo esquema (05-sep-2026):
     *
     *  - Redes sociales (instagram, tiktok, facebook): 3 publicaciones + 3 stories por canal.
     *    kind='post'|'story', slot=1..3. Unique compuesto: (car_id, channel, kind, slot).
     *
     *  - Portales web (milanuncios, coches_net, wallapop, facebook marketplace):
     *    1 ficha única reutilizada. kind='ad', slot=1. Mismo unique.
     *
     *  - Migración retrocompatible:
     *    · Filas existentes → kind='ad' slot=1 (o 'post' slot=1 si channel∈{instagram,tiktok}).
     *    · El unique viejo (car_id, channel) se elimina AL FINAL, después de
     *      añadir un índice explícito sobre car_id (FK necesita un índice que
     *      cubra car_id al hacer dropUnique — error MySQL 1553 si no).
     */
    public function up(): void
    {
        // 0) Detectar estado parcial con Schema::hasColumn (portable a sqlite).
        $hasKind = Schema::hasColumn('car_marketing_contents', 'kind');
        $hasSlot = Schema::hasColumn('car_marketing_contents', 'slot');
        $hasPasos = Schema::hasColumn('car_marketing_contents', 'subir_pasos');
        // Índices no tienen helper portable; intentamos la operación y si
        // falla es que ya existe (idempotencia).
        $hasCarIdIndex = $this->indexExists('car_marketing_contents', 'cmc_car_id_index');
        $hasNewUnique = $this->indexExists('car_marketing_contents', 'cmc_car_channel_kind_slot_unique');
        $hasOldUnique = $this->indexExists('car_marketing_contents', 'car_marketing_contents_car_id_channel_unique');

        // 1) Columnas nuevas (nullable para backfill) + índice sobre car_id.
        Schema::table('car_marketing_contents', function (Blueprint $table) use ($hasKind, $hasSlot, $hasPasos, $hasCarIdIndex) {
            if (! $hasKind) {
                $table->string('kind', 16)->nullable()->after('channel');
            }
            if (! $hasSlot) {
                $table->unsignedTinyInteger('slot')->nullable()->after('kind');
            }
            if (! $hasPasos) {
                $table->text('subir_pasos')->nullable()->after('photo_tips');
            }
            if (! $hasCarIdIndex) {
                $table->index('car_id', 'cmc_car_id_index');
            }
        });

        // 2) Backfill: filas existentes → kind/slot por defecto.
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

        // 3) Quitar nullability (idempotente: si ya son NOT NULL no falla).
        if (! $hasKind || ! $hasSlot) {
            Schema::table('car_marketing_contents', function (Blueprint $table) {
                $table->string('kind', 16)->default('ad')->change();
                $table->unsignedTinyInteger('slot')->default(1)->change();
            });
        }

        // 4) Crear NUEVO unique (si no existe) + borrar el viejo (si existe).
        if (! $hasNewUnique) {
            Schema::table('car_marketing_contents', function (Blueprint $table) {
                $table->unique(
                    ['car_id', 'channel', 'kind', 'slot'],
                    'cmc_car_channel_kind_slot_unique',
                );
            });
        }
        if ($hasOldUnique) {
            Schema::table('car_marketing_contents', function (Blueprint $table) {
                $table->dropUnique(['car_id', 'channel']);
            });
        }
    }

    /**
     * Comprueba si existe un índice por nombre (portable a MySQL/sqlite).
     */
    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::connection()->getDriverName();
        try {
            if ($driver === 'mysql') {
                $rows = DB::select(
                    'SHOW INDEX FROM '.$table.' WHERE Key_name = ?',
                    [$index]
                );
            } else {
                // sqlite: PRAGMA index_list
                $rows = DB::select(
                    'SELECT name FROM sqlite_master WHERE type = ? AND tbl_name = ? AND name = ?',
                    ['index', $table, $index]
                );
            }

            return count($rows) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    public function down(): void
    {
        Schema::table('car_marketing_contents', function (Blueprint $table) {
            $table->unique(['car_id', 'channel']);
            $table->dropUnique('cmc_car_channel_kind_slot_unique');
            $table->dropIndex('cmc_car_id_index');
            $table->dropColumn(['kind', 'slot', 'subir_pasos']);
        });
    }
};
