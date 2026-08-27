<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastNotificationRecipient extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'broadcast_notification_recipient_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'broadcast_notification_recipient_id',
        'broadcast_notification_id',
        'user_id',
        'user_fcm_token_id',
        'fcm_token',
        'status',
        'attempts',
        'sent_at',
        'response',
        'error_message',
        'date_created',
        'date_updated',
    ];

    protected $casts = [
        'response' => 'array',
        'sent_at' => 'datetime',
        'date_created' => 'datetime',
        'date_updated' => 'datetime',
    ];

    public function broadcast()
    {
        return $this->belongsTo(
            BroadcastNotification::class,
            'broadcast_notification_id',
            'broadcast_notification_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fcmToken()
    {
        return $this->belongsTo(UserFcmToken::class, 'user_fcm_token_id', 'user_fcm_token_id');
    }
}
