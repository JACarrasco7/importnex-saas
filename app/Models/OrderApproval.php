<?php

namespace App\Models;

use App\Enums\ApprovalKind;
use App\Enums\ApprovalStatus;
use App\Enums\UnitDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Petición de aprobación al cliente (coste extra o decisión sobre el coche). */
class OrderApproval extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id', 'import_order_id', 'created_by', 'kind', 'title', 'description',
        'amount', 'status', 'decision', 'client_comment', 'due_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => ApprovalKind::class,
            'status' => ApprovalStatus::class,
            'decision' => UnitDecision::class,
            'amount' => 'decimal:2',
            'due_at' => 'datetime',
            'reminded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'otp_verified_at' => 'datetime',
        ];
    }

    public function importOrder(): BelongsTo
    {
        return $this->belongsTo(ImportOrder::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(OrderMedia::class);
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }

    /** Importe a partir del cual se pide código de verificación. */
    public function requiresOtp(): bool
    {
        if ($this->kind === ApprovalKind::UnitDecision) {
            return true;
        }

        return (float) $this->amount >= (float) config('import_orders.otp_amount_threshold', 300);
    }
}
