<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'preset',
        'primary_color',
        'secondary_color',
        'accent_color',
        'font_family',
        'font_size_base',
        'sidebar_config',
        'header_config',
        'footer_config',
        'content_config',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    protected $casts = [
        'sidebar_config' => 'array',
        'header_config'  => 'array',
        'footer_config'  => 'array',
        'content_config' => 'array',
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
