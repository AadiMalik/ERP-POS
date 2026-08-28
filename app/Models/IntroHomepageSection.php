<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroHomepageSection extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_homepage_sections';
    protected $primaryKey = 'intro_homepage_section_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'section_key',
        'title',
        'subtitle',
        'content',
        'content_json',
        'image',
        'button_text',
        'button_link',
        'display_order',
        'is_enabled',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted'
    ];

    protected $appends = ['image_url'];

    protected $casts = [
        'content_json' => 'array',
        'is_enabled' => 'boolean'
    ];

    public function getImageUrlAttribute()
    {
        return !empty($this->image) ? asset('public/uploads/intro/sections/' . $this->image) : null;
    }
}
