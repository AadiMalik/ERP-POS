<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'payment_transaction_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'payment_transaction_id',
        'business_id',
        'order_id',
        'user_id',
        'payment_gateway_id',
        'provider_code',
        'environment',
        'payment_method_code',
        'client_platform',
        'internal_reference',
        'gateway_reference',
        'gateway_transaction_id',
        'amount',
        'currency',
        'status',
        'failure_code',
        'failure_reason',
        'verified_at',
        'verification_method',
        'refund_of_transaction_id',
        'refunded_amount',
        'meta',
        'createdby_id',
        'date_created',
        'date_updated',
    ];

    protected $casts = [
        'meta' => 'array',
        'verified_at' => 'datetime',
        'date_created' => 'datetime',
        'date_updated' => 'datetime',
    ];

    /** Statuses after which this transaction can never change again. */
    public const TERMINAL_STATUSES = ['paid', 'failed', 'cancelled', 'expired', 'refunded', 'partially_refunded'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function refundOf()
    {
        return $this->belongsTo(self::class, 'refund_of_transaction_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }
}
