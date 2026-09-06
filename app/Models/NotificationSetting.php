<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'payment_due_alert_enabled',
        'payment_due_days_before',
        'credit_limit_alert_enabled',
        'credit_limit_threshold_percent',
        'supplier_payment_reminder_enabled',
        'supplier_payment_reminder_days_before',
        'order_status_alert_enabled',
        'new_order_alert_enabled',
        'website_order_notify_pos_enabled',
        'sound_enabled',
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
