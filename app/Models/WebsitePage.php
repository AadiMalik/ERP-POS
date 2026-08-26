<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsitePage extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'page_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'page_id',
        'business_id',
        'slug',
        'title',
        'content',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_image',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $appends = ['og_image_url'];

    public function getOgImageUrlAttribute()
    {
        return !empty($this->og_image) ? asset('public/uploads/website/page/' . $this->og_image) : null;
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }
}
