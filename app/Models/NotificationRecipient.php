<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationRecipient extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'notification_recipient_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'notification_recipient_id',
        'notification_id',
        'user_id',
        'is_read',
        'read_at',
        'date_created',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id', 'notification_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
