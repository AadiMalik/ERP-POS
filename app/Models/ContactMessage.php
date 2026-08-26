<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'contact_message_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'contact_message_id',
        'business_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'reply_message',
        'replied_at',
        'repliedby_id',
        'is_deleted',
        'deletedby_id',
        'date_created',
        'date_deleted',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function repliedby()
    {
        return $this->belongsTo(User::class, 'repliedby_id');
    }
}
