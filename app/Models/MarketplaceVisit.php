<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Contador global de visitas al listado publico del marketplace.
 *
 * Singleton (id=1). Se accede siempre via MarketplaceVisit::current()
 * que garantiza que la fila existe aunque la migracion no se haya
 * ejecutado o alguien la haya borrado por error.
 */
class MarketplaceVisit extends Model
{
    protected $table = 'marketplace_visits';

    protected $fillable = [
        'visits_count',
        'first_visited_at',
        'last_visited_at',
    ];

    protected $casts = [
        'visits_count' => 'integer',
        'first_visited_at' => 'datetime',
        'last_visited_at' => 'datetime',
    ];

    /**
     * Devuelve la unica fila del contador. La crea si no existe
     * (defensa frente a migraciones parciales o borrados manuales).
     */
    public static function current(): self
    {
        $row = static::query()->whereKey(1)->first();

        if (! $row) {
            $now = now();
            DB::table('marketplace_visits')->insertOrIgnore([
                'id' => 1,
                'visits_count' => 0,
                'first_visited_at' => null,
                'last_visited_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $row = static::query()->whereKey(1)->firstOrFail();
        }

        return $row;
    }

    /**
     * Registra una visita y devuelve el contador actualizado.
     */
    public static function recordView(): int
    {
        $now = now();
        $row = static::current();

        DB::table('marketplace_visits')
            ->where('id', 1)
            ->update([
                'visits_count' => DB::raw('visits_count + 1'),
                'last_visited_at' => $now,
                'first_visited_at' => $row->first_visited_at ?? $now,
                'updated_at' => $now,
            ]);

        return ((int) $row->visits_count) + 1;
    }
}
