<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroContactInquiry extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_contact_inquiries';
    protected $primaryKey = 'intro_contact_inquiry_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'intro_contact_inquiry_id',
        'business_id',
        'subscription_invoice_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'is_deleted',
        'deletedby_id',
        'date_created',
        'date_deleted'
    ];

    public function replies()
    {
        return $this->hasMany(IntroContactReply::class, 'intro_contact_inquiry_id', 'intro_contact_inquiry_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function subscriptionInvoice()
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'subscription_invoice_id', 'subscription_invoice_id');
    }
}
