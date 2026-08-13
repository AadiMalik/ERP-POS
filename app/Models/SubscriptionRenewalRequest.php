<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionRenewalRequest extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'subscription_renewal_request_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'subscription_renewal_request_id',
        'business_id',
        'business_subscription_id',
        'requested_package_id',
        'requested_billing_cycle',
        'status',
        'requested_notes',
        'reviewer_notes',
        'reviewed_by',
        'reviewed_at',
        'resulting_subscription_invoice_id',

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

    public function subscription()
    {
        return $this->belongsTo(BusinessSubscription::class, 'business_subscription_id', 'business_subscription_id');
    }

    public function requestedPackage()
    {
        return $this->belongsTo(Package::class, 'requested_package_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
    public function updatedby()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }
    public function deletedby()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
