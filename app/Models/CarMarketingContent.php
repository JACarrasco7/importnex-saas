<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarMarketingContent extends Model
{
    protected $fillable = [
        'car_id', 'channel', 'kind', 'slot', 'title', 'description',
        'hashtags', 'photo_tips', 'subir_pasos', 'status',
        'generated_at', 'published_at', 'source',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'photo_tips' => 'array',
        'slot' => 'integer',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public const CHANNELS = [
        'milanuncios', 'coches_net', 'wallapop', 'tiktok', 'instagram', 'facebook',
    ];

    /** Canales web (portales): reutilizan la misma ficha base. */
    public const PORTAL_CHANNELS = ['milanuncios', 'coches_net', 'wallapop', 'facebook'];

    /** Redes sociales: 3 posts + 3 stories por canal (slot 1..3). */
    public const SOCIAL_CHANNELS = ['instagram', 'tiktok', 'facebook'];

    public const CHANNEL_INSTAGRAM = 'instagram';

    public const CHANNEL_TIKTOK = 'tiktok';

    public const STATUSES = ['draft', 'published', 'archived'];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    /** Tipo de pieza de marketing: publicación, story o ficha de portal. */
    public const KINDS = ['post', 'story', 'ad'];

    public const KIND_POST = 'post';

    public const KIND_STORY = 'story';

    public const KIND_AD = 'ad';

    /** Nº de slots (publicaciones/stories) por red social. */
    public const SLOTS_PER_SOCIAL = 3;

    /** Origen del contenido:
     *  - `zip`: importado del ZIP de Claude (ValuationPackageIngestor)
     *  - `ai`: generado con IA desde el panel (CarMarketingController::generate)
     *  - `manual`: creado a mano desde cero (sin endpoint hoy)
     */
    public const SOURCES = ['zip', 'ai', 'manual'];

    public const SOURCE_ZIP = 'zip';

    public const SOURCE_AI = 'ai';

    public const SOURCE_MANUAL = 'manual';

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Scope: contenido de redes sociales (posts + stories).
     */
    public function scopeSocial($query)
    {
        return $query->whereIn('kind', [self::KIND_POST, self::KIND_STORY]);
    }

    /**
     * Scope: fichas de portales web (milanuncios/coches_net/wallapop/facebook).
     */
    public function scopePortals($query)
    {
        return $query->where('kind', self::KIND_AD);
    }
}
