<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroPage extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_pages';
    protected $primaryKey = 'intro_page_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'seo_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'robots_index',
        'robots_follow',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted'
    ];

    protected $appends = ['og_image_url'];

    protected $casts = [
        'robots_index' => 'boolean',
        'robots_follow' => 'boolean'
    ];

    public function getOgImageUrlAttribute()
    {
        return !empty($this->og_image) ? asset('public/uploads/intro/pages/' . $this->og_image) : null;
    }
}
