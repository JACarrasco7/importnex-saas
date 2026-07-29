<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarDocument extends Model
{
    use HasFactory;

    protected $table = 'car_documents';

    protected $fillable = ['car_id', 'organization_id', 'name', 'doc_key', 'doc_type', 'group', 'status', 'url', 'uploaded_at', 'notes'];

    protected $casts = ['uploaded_at' => 'datetime'];

    public const STATUS_PENDING = 'pending';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_NOT_APPLICABLE = 'not_applicable';

    public const GROUP_SELLER_ORIGIN = 'seller_origin';
    public const GROUP_PURCHASE_TRANSPORT = 'purchase_transport';
    public const GROUP_SPAIN_PROCEDURES = 'spain_procedures';

    protected static function booted()
    {
        static::addGlobalScope('organization', function ($query) {
            if (auth()->check() && auth()->user()->organization_id) {
                $query->where('organization_id', auth()->user()->organization_id);
            }
        });
    }

    public function car()
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
