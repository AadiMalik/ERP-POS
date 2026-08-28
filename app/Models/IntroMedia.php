<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroMedia extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_media';
    protected $primaryKey = 'intro_media_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'filename',
        'original_name',
        'disk_path',
        'mime_type',
        'collection',
        'alt_text',
        'size',
        'is_deleted',
        'createdby_id',
        'deletedby_id',
        'date_created',
        'date_deleted'
    ];

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return !empty($this->filename) ? asset('public/uploads/intro/media/' . $this->filename) : null;
    }
}
