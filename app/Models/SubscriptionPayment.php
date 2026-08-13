<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'subscription_payment_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'subscription_payment_id',
        'subscription_invoice_id',
        'business_id',
        'amount',
        'payment_method',
        'payment_reference',
        'payment_proof',
        'payment_gateway',
        'gateway_transaction_id',
        'status',
        'paid_at',
        'notes',

        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function invoice()
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id', 'subscription_invoice_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
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
