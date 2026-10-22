<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Foto o documento del encargo. Por defecto INTERNO: solo sale al portal del
 * cliente si `visibility = client`. Se sirve por ruta (nunca URL pública).
 */
class OrderMedia extends Model
{
    use SoftDeletes;

    public const VISIBILITY_CLIENT = 'client';

    public const VISIBILITY_INTERNAL = 'internal';

    public const COLLECTIONS = ['inspection', 'transport', 'documents', 'delivery', 'other'];

    protected $table = 'order_media';

    protected $fillable = [
        'organization_id', 'import_order_id', 'order_approval_id', 'uploaded_by', 'collection',
        'disk', 'path', 'original_name', 'mime', 'size', 'caption', 'visibility',
    ];

    public function importOrder(): BelongsTo
    {
        return $this->belongsTo(ImportOrder::class);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
