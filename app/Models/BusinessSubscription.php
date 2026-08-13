<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSubscription extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'business_subscription_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'business_subscription_id',
        'business_id',
        'package_id',
        'billing_cycle',
        'start_at',
        'end_at',
        'grace_period_ends_at',
        'subtotal',
        'discount',
        'discount_type',
        'discount_amount',
        'tax',
        'tax_amount',
        'total',
        'payment_status',
        'payment_method',
        'payment_reference',
        'status',
        'cancelled_at',
        'renewed_from_subscription_id',

        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class, 'business_subscription_id', 'business_subscription_id');
    }

    public function renewedFrom()
    {
        return $this->belongsTo(BusinessSubscription::class, 'renewed_from_subscription_id', 'business_subscription_id');
    }

    public function history()
    {
        return $this->hasMany(SubscriptionHistory::class, 'business_subscription_id', 'business_subscription_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function getRemainingDaysAttribute()
    {
        if (empty($this->end_at)) {
            return null;
        }

        return now()->diffInDays($this->end_at, false);
    }
}
