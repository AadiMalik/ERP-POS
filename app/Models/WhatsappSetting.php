<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'enable_whatsapp',
        'provider',
        'api_key',
        'access_token',
        'instance_id',
        'phone_number_id',
        'webhook_url',
        'send_invoice',
        'send_receipt',

        'createdby_id',
        'updatedby_id',

        'date_created',
        'date_updated',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
