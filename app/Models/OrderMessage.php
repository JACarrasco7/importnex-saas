<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Mensaje del hilo del encargo (cliente ↔ equipo) o nota interna. */
class OrderMessage extends Model
{
    use SoftDeletes;

    public const AUTHOR_CLIENT = 'client';

    public const AUTHOR_TEAM = 'team';

    public const VISIBILITY_CLIENT = 'client';

    public const VISIBILITY_INTERNAL = 'internal';

    protected $fillable = [
        'organization_id', 'import_order_id', 'author_type', 'user_id', 'body',
        'visibility', 'read_by_client_at', 'read_by_team_at', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'read_by_client_at' => 'datetime',
            'read_by_team_at' => 'datetime',
        ];
    }

    public function importOrder(): BelongsTo
    {
        return $this->belongsTo(ImportOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
