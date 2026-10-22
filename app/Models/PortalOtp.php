<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Código de verificación de 6 dígitos para actos firmados en el portal. */
class PortalOtp extends Model
{
    public const PURPOSE_SIGN_CONTRACT = 'sign_contract';

    public const PURPOSE_APPROVE = 'approve';

    public const PURPOSE_CANCEL = 'cancel';

    protected $fillable = [
        'organization_id', 'import_order_id', 'purpose', 'subject_type', 'subject_id',
        'code_hash', 'channel', 'sent_to', 'attempts', 'expires_at', 'consumed_at', 'ip',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function importOrder(): BelongsTo
    {
        return $this->belongsTo(ImportOrder::class);
    }
}
