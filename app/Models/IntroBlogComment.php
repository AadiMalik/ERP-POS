<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroBlogComment extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_blog_comments';
    protected $primaryKey = 'intro_blog_comment_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'intro_blog_id',
        'name',
        'email',
        'comment',
        'status',
        'moderation_note',
        'moderatedby_id',
        'moderated_at',
        'ip_address',
        'is_deleted',
        'deletedby_id',
        'date_created',
        'date_deleted'
    ];

    protected $casts = [
        'moderated_at' => 'datetime'
    ];

    public function blog()
    {
        return $this->belongsTo(IntroBlog::class, 'intro_blog_id', 'intro_blog_id');
    }
}
