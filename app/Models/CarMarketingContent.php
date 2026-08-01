<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarMarketingContent extends Model
{
    protected $fillable = [
        'car_id', 'channel', 'title', 'description',
        'hashtags', 'photo_tips', 'status', 'generated_at', 'published_at',
    ];

    protected $casts = [
        'hashtags' => 'array',
        'photo_tips' => 'array',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public const CHANNELS = [
        'milanuncios', 'coches_net', 'wallapop', 'tiktok', 'instagram', 'facebook',
    ];

    public const STATUSES = ['draft', 'published', 'archived'];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }
}
