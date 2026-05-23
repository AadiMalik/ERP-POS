<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'enable_email_notifications',
        'mail_mailer',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'mail_from_address',
        'mail_from_name',

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
