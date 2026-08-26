<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'subscriber_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'subscriber_id',
        'business_id',
        'email',
        'source',
        'status',
        'unsubscribed_at',
        'is_deleted',
        'deletedby_id',
        'date_created',
        'date_deleted',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }
}
