<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSendLog extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'document_send_log_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'document_send_log_id',
        'business_id',
        'module',
        'record_id',
        'channel',
        'recipient',
        'status',
        'message',
        'createdby_id',
        'date_created',
    ];

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
