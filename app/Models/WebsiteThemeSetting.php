<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteThemeSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'theme_preset',
        'primary_color',
        'secondary_color',
        'accent_color',
        'background_color',
        'surface_color',
        'text_color',
        'heading_color',
        'border_color',
        'success_color',
        'warning_color',
        'error_color',
        'font_pairing',
        'font_size_base',
        'button_style',
        'typography_style',
        'favicon',
        'business_hours',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'og_image',
        'whatsapp_number',
        'social_links',
        'free_delivery_enabled',
        'free_delivery_min_amount',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    protected $casts = [
        'social_links' => 'array',
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
