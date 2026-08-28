<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroBlog extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_blogs';
    protected $primaryKey = 'intro_blog_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'intro_blog_category_id',
        'author_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'reading_time',
        'published_at',
        'status',
        'is_featured',
        'seo_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted'
    ];

    protected $appends = ['featured_image_url', 'og_image_url'];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime'
    ];

    public function getFeaturedImageUrlAttribute()
    {
        return !empty($this->featured_image) ? asset('public/uploads/intro/blog/' . $this->featured_image) : null;
    }

    public function getOgImageUrlAttribute()
    {
        return !empty($this->og_image) ? asset('public/uploads/intro/blog/' . $this->og_image) : null;
    }

    public function category()
    {
        return $this->belongsTo(IntroBlogCategory::class, 'intro_blog_category_id', 'intro_blog_category_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags()
    {
        return $this->belongsToMany(
            IntroBlogTag::class,
            'intro_blog_tag',
            'intro_blog_id',
            'intro_blog_tag_id'
        );
    }

    public function comments()
    {
        return $this->hasMany(IntroBlogComment::class, 'intro_blog_id', 'intro_blog_id');
    }
}
