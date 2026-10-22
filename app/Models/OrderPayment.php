<?php

namespace App\Models;

use App\Enums\PaymentKind;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Pago del cliente (señal, resto de honorarios, coste extra). Se marcan a mano
 * desde el panel (transferencia, efectivo o tarjeta vía enlace de Stripe).
 */
class OrderPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'import_order_id', 'order_approval_id', 'kind', 'concept', 'amount',
        'status', 'method', 'stripe_payment_link', 'reference', 'due_date', 'paid_at',
        'recorded_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'kind' => PaymentKind::class,
            'status' => PaymentStatus::class,
            'method' => PaymentMethod::class,
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function importOrder(): BelongsTo
    {
        return $this->belongsTo(ImportOrder::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
