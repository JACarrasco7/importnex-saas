<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientContactLog extends Model
{
    use HasFactory;

    protected $table = 'client_contact_logs';

    protected $fillable = ['organization_id', 'client_id', 'contact_date', 'channel', 'summary'];

    protected $casts = [
        'contact_date' => 'date',
    ];

    protected static function booted()
    {
        static::addGlobalScope('organization', function ($query) {
            if (auth()->check() && auth()->user()->organization_id) {
                $query->where('organization_id', auth()->user()->organization_id);
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
