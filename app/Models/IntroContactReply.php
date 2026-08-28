<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroContactReply extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_contact_replies';
    protected $primaryKey = 'intro_contact_reply_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'intro_contact_inquiry_id',
        'reply_message',
        'send_status',
        'error_message',
        'repliedby_id',
        'date_created'
    ];

    public function inquiry()
    {
        return $this->belongsTo(IntroContactInquiry::class, 'intro_contact_inquiry_id', 'intro_contact_inquiry_id');
    }

    public function repliedby()
    {
        return $this->belongsTo(User::class, 'repliedby_id');
    }
}
