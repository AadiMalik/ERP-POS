<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionHistory extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'subscription_history';
    protected $primaryKey = 'subscription_history_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'subscription_history_id',
        'business_id',
        'business_subscription_id',
        'event_type',
        'from_status',
        'to_status',
        'from_package_id',
        'to_package_id',
        'notes',
        'meta',
        'createdby_id',
        'date_created',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function subscription()
    {
        return $this->belongsTo(BusinessSubscription::class, 'business_subscription_id', 'business_subscription_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
