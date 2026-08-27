<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFcmToken extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'user_fcm_token_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_fcm_token_id',
        'business_id',
        'user_id',
        'fcm_token',
        'device_id',
        'device_type',
        'is_active',
        'last_seen_at',
        'last_used_at',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_used_at' => 'datetime',
        'date_created' => 'datetime',
        'date_updated' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }
}
