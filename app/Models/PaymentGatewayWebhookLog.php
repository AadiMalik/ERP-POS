<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewayWebhookLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'provider_code',
        'business_id',
        'event_id',
        'payload_hash',
        'status',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];
}
