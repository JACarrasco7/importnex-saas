<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\ContractStatus;
use App\Enums\ImportOrderState;
use App\Enums\PaymentKind;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Encargo de importación: la entidad que une cliente, coche, solicitud,
 * contrato, pagos, aprobaciones y comunicación con el cliente.
 *
 * Multi-tenant SIN global scope (ver .ai/rules/multitenancy.md): filtrar
 * siempre con scopeForOrganization() o comprobar organization_id.
 *
 * @property ImportOrderState $state
 */
class ImportOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id', 'client_id', 'car_id', 'car_request_id', 'assigned_to',
        'reference', 'state', 'state_changed_at', 'fee_amount',
        'expected_delivery_date', 'internal_notes',
    ];

    protected $hidden = ['portal_token', 'portal_token_hash'];

    protected function casts(): array
    {
        return [
            'state' => ImportOrderState::class,
            'state_changed_at' => 'datetime',
            'fee_amount' => 'decimal:2',
            'portal_token' => 'encrypted',
            'portal_token_rotated_at' => 'datetime',
            'portal_last_seen_at' => 'datetime',
            'expected_delivery_date' => 'date',
            'cancellation_requested_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withoutGlobalScopes();
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class)->withoutGlobalScope('organization');
    }

    public function carRequest(): BelongsTo
    {
        return $this->belongsTo(CarRequest::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(ContractAcceptance::class)->withoutGlobalScopes();
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(OrderApproval::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(OrderMedia::class);
    }

    /** @param  Builder<ImportOrder>  $query */
    public function scopeForOrganization(Builder $query, int $organizationId): void
    {
        $query->where('organization_id', $organizationId);
    }

    /** @param  Builder<ImportOrder>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('state', array_map(fn (ImportOrderState $s) => $s->value, ImportOrderState::active()));
    }

    /** Último contrato (el vigente o el último firmado). */
    public function currentContract(): ?ContractAcceptance
    {
        return $this->contracts()
            ->whereNotIn('status', [ContractStatus::Voided->value])
            ->latest('id')
            ->first();
    }

    public function signedContract(): ?ContractAcceptance
    {
        return $this->contracts()->where('status', ContractStatus::Signed->value)->latest('id')->first();
    }

    public function depositPayment(): ?OrderPayment
    {
        return $this->payments()->where('kind', PaymentKind::FeeDeposit->value)
            ->where('status', '!=', PaymentStatus::Cancelled->value)
            ->latest('id')->first();
    }

    public function isDepositSettled(): bool
    {
        if ((float) $this->fee_amount <= 0) {
            return true;
        }

        return $this->payments()
            ->where('kind', PaymentKind::FeeDeposit->value)
            ->where('status', PaymentStatus::Paid->value)
            ->exists();
    }

    public function pendingApprovalsCount(): int
    {
        return $this->approvals()->where('status', ApprovalStatus::Pending->value)->count();
    }

    public function depositAmount(): float
    {
        return round((float) $this->fee_amount / 2, 2);
    }

    public function portalUrl(): ?string
    {
        if (! $this->portal_token) {
            return null;
        }

        return route('portal.show', $this->portal_token);
    }

    public function vehicleLabel(): string
    {
        if (! $this->car) {
            return 'Vehículo por asignar';
        }

        return trim(implode(' ', array_filter([$this->car->brand, $this->car->model, $this->car->version])));
    }

    public static function findByPortalToken(string $token): ?self
    {
        return self::query()->where('portal_token_hash', hash('sha256', $token))->first();
    }
}
