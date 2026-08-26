<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteSection extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'section_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'section_id',
        'business_id',
        'type',
        'tagline',
        'tagline_icon',
        'heading',
        'heading_icon',
        'description',
        'image',
        'image_mobile',
        'button_text',
        'button_link',
        'link_type',
        'link_target_id',
        'secondary_button_text',
        'secondary_button_link',
        'secondary_link_type',
        'secondary_link_target_id',
        'countdown_end_at',
        'sort_order',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $appends = ['image_url', 'image_mobile_url'];

    public function getImageUrlAttribute()
    {
        return !empty($this->image) ? asset('public/uploads/website/section/' . $this->image) : null;
    }

    public function getImageMobileUrlAttribute()
    {
        return !empty($this->image_mobile) ? asset('public/uploads/website/section/' . $this->image_mobile) : null;
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }
}
