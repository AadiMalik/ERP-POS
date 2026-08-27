<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'notification_template_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'notification_template_id',
        'business_id',
        'name',
        'title',
        'body',
        'image',
        'data',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $casts = [
        'data' => 'array',
        'is_deleted' => 'boolean',
        'date_created' => 'datetime',
        'date_updated' => 'datetime',
        'date_deleted' => 'datetime',
    ];

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

    public function broadcasts()
    {
        return $this->hasMany(BroadcastNotification::class, 'template_id', 'notification_template_id');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', 0);
    }
}
