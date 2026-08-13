<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionReminderLog extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'subscription_reminder_log_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'subscription_reminder_log_id',
        'business_id',
        'business_subscription_id',
        'threshold_days',
        'channel',
        'status',
        'sent_at',
        'createdby_id',
        'date_created',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function subscription()
    {
        return $this->belongsTo(BusinessSubscription::class, 'business_subscription_id', 'business_subscription_id');
    }
}
