<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'notification_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'notification_id',
        'business_id',
        'branch_id',
        'type',
        'title',
        'message',
        'reference_type',
        'reference_id',
        'url',
        'data',
        'dedupe_key',
        'date_created',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function recipients()
    {
        return $this->hasMany(NotificationRecipient::class, 'notification_id', 'notification_id');
    }
}
