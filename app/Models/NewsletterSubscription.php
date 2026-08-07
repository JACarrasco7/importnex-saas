<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'email', 'source', 'ip_hash', 'locale', 'verified', 'unsubscribed_at',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'unsubscribed_at' => 'datetime',
    ];
}
