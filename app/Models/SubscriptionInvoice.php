<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoice extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'subscription_invoice_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'subscription_invoice_id',
        'business_id',
        'business_subscription_id',
        'package_id',
        'invoice_no',
        'billing_cycle',
        'period_start',
        'period_end',
        'subtotal',
        'discount',
        'discount_type',
        'discount_amount',
        'tax',
        'tax_amount',
        'total',
        'status',
        'due_date',
        'notes',

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

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_invoice_id', 'subscription_invoice_id');
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
