<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastNotification extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'broadcast_notification_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'broadcast_notification_id',
        'business_id',
        'template_id',
        'title',
        'body',
        'image',
        'data',
        'status',
        'total_count',
        'pending_count',
        'success_count',
        'failed_count',
        'cancelled_count',
        'started_at',
        'completed_at',
        'cancelled_at',
        'created_by',
        'is_deleted',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $casts = [
        'data' => 'array',
        'is_deleted' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'date_created' => 'datetime',
        'date_updated' => 'datetime',
        'date_deleted' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id', 'notification_template_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients()
    {
        return $this->hasMany(
            BroadcastNotificationRecipient::class,
            'broadcast_notification_id',
            'broadcast_notification_id'
        );
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', 0);
    }
}
