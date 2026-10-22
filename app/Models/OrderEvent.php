<?php

namespace App\Models;

use App\Enums\OrderEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Bitácora inmutable del encargo (quién hizo qué y cuándo). */
class OrderEvent extends Model
{
    protected $fillable = [
        'organization_id', 'import_order_id', 'type', 'from_state', 'to_state',
        'actor_type', 'actor_user_id', 'title', 'body', 'visible_to_client', 'meta', 'ip',
    ];

    protected function casts(): array
    {
        return [
            'type' => OrderEventType::class,
            'visible_to_client' => 'boolean',
            'meta' => 'array',
        ];
    }

    public function importOrder(): BelongsTo
    {
        return $this->belongsTo(ImportOrder::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
